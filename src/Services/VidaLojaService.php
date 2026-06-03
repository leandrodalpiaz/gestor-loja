<?php

namespace App\Services;

use App\Models\Obreiro;
use App\Models\Sessao;
use App\Models\VidaLojaSinal;
use App\Config\Database;
use App\Core\Tenant\ResolvesStoreTenant;
use PDO;
use DateTimeImmutable;

class VidaLojaService
{
    use ResolvesStoreTenant;

    private PDO $db;
    private VidaLojaSinal $sinalModel;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->sinalModel = new VidaLojaSinal();
    }

    /**
     * Varre a lista de obreiros ativos da loja atual e calcula os sinais de ausências recorrentes.
     * Deve ser executado discretamente ao carregar o painel da Vida da Loja.
     */
    public function sincronizarSinaisAutomáticos(): void
    {
        $this->sincronizarSinaisAusencia();
        $this->sincronizarSinaisAgape();
    }

    public function sincronizarSinaisAusencia(): void
    {
        $obreiroModel = new Obreiro();
        $obreiros = $obreiroModel->getAllAtivos(); // Filtra por ativo = true na loja do tenant atual
        
        $lojaId = $this->obterLojaAtualId();

        // 1. Obter todas as sessões realizadas da loja
        $stmt = $this->db->prepare("
            SELECT id, data_hora_inicio, grau_sessao, titulo
            FROM public.sessoes
            WHERE status = 'realizada'
              AND loja_id = :loja_id
            ORDER BY data_hora_inicio DESC
        ");
        $stmt->execute(['loja_id' => $lojaId]);
        $sessoesRealizadas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($sessoesRealizadas)) {
            return;
        }

        $hoje = new DateTimeImmutable('today', new \DateTimeZone('America/Sao_Paulo'));
        $limite60Dias = $hoje->modify('-60 days')->format('Y-m-d H:i:s');

        foreach ($obreiros as $obreiro) {
            $obreiroId = $obreiro['id'];
            $grauObreiro = (string) ($obreiro['grau'] ?? 'Aprendiz');

            // Ignorar obreiros que não estão com status ativo na vida da loja (licenciados, suspensos, inativos, etc.)
            $situacao = strtolower(trim((string) ($obreiro['situacao_quadro'] ?? 'ativo')));
            if ($situacao !== 'ativo') {
                continue;
            }

            // 2. Filtrar sessões compatíveis com o grau do obreiro
            $sessoesCompativeis = array_filter($sessoesRealizadas, function (array $sessao) use ($grauObreiro): bool {
                $grauSessao = trim((string) ($sessao['grau_sessao'] ?? 'Aprendiz'));
                return $this->verificarCompatibilidadeGrau($grauObreiro, $grauSessao);
            });

            if (empty($sessoesCompativeis)) {
                continue;
            }

            // 3. Obter as sessões na janela de 60 dias
            $sessoesJanela = array_filter($sessoesCompativeis, function (array $sessao) use ($limite60Dias): bool {
                return $sessao['data_hora_inicio'] >= $limite60Dias;
            });

            // Se houver menos de 3 sessões na janela de 60 dias, usamos as últimas 3 sessões compatíveis realizadas
            if (count($sessoesJanela) < 3) {
                $sessoesAnalise = array_slice($sessoesCompativeis, 0, 3);
            } else {
                $sessoesAnalise = $sessoesJanela;
            }

            if (count($sessoesAnalise) < 3) {
                // Não há sessões suficientes na história compatível da loja para emitir parecer
                continue;
            }

            // 4. Calcular frequência e presenças
            $totalValidas = 0;
            $presencas = 0;
            $estevePresenteUltima = false;
            $indexSessao = 0;

            foreach ($sessoesAnalise as $sessao) {
                $sessaoId = (int) $sessao['id'];
                
                // Verificar se o obreiro esteve presente
                $presencaStatus = $this->obterStatusPresencaObreiro($sessaoId, $obreiroId);
                
                if ($presencaStatus['presente'] === true) {
                    $presencas++;
                    $totalValidas++;
                    if ($indexSessao === 0) {
                        $estevePresenteUltima = true;
                    }
                } else {
                    // Verificar se foi justificado
                    if ($presencaStatus['justificado'] === true) {
                        // Justificado: desconsidera a sessão do denominador (não penaliza)
                        continue;
                    } else {
                        // Falta não justificada
                        $totalValidas++;
                    }
                }
                $indexSessao++;
            }

            // Se todas as sessões analisadas foram justificadas, evita divisão por zero
            if ($totalValidas === 0) {
                continue;
            }

            $frequencia = ($presencas / $totalValidas) * 100;
            $sinalExistente = $this->sinalModel->obterSinalAberto($obreiroId, 'ausencias_consecutivas');

            if ($frequencia <= 50.0) {
                $nivel = 'alto';
                $detalhes = sprintf("Frequência de %.1f%% calculada sobre as últimas %d reuniões compatíveis avaliadas.", $frequencia, $totalValidas);
                
                if ($sinalExistente) {
                    // Atualiza o nível e detalhes do sinal aberto
                    $this->atualizarSinalManualParaObservacaoSeRetornou($sinalExistente['id'], $estevePresenteUltima, $nivel, $detalhes);
                } else {
                    // Cria sinal novo
                    $this->sinalModel->criar([
                        'obreiro_id' => $obreiroId,
                        'tipo' => 'ausencias_consecutivas',
                        'status' => 'aberto',
                        'nivel' => $nivel,
                        'detalhes' => $detalhes,
                    ]);
                }
            } elseif ($frequencia <= 69.0) {
                $nivel = 'medio';
                $detalhes = sprintf("Frequência de %.1f%% calculada sobre as últimas %d reuniões compatíveis avaliadas.", $frequencia, $totalValidas);

                if ($sinalExistente) {
                    $this->atualizarSinalManualParaObservacaoSeRetornou($sinalExistente['id'], $estevePresenteUltima, $nivel, $detalhes);
                } else {
                    $this->sinalModel->criar([
                        'obreiro_id' => $obreiroId,
                        'tipo' => 'ausencias_consecutivas',
                        'status' => 'aberto',
                        'nivel' => $nivel,
                        'detalhes' => $detalhes,
                    ]);
                }
            } else {
                // Frequência >= 70%
                if ($sinalExistente) {
                    // Se o obreiro esteve presente na última sessão compatível OU a frequência regularizou para >= 70%,
                    // o sinal é movido para 'em_observacao'. Ele NÃO é fechado automaticamente.
                    if ($sinalExistente['status'] === 'aberto') {
                        $detalhes = sprintf("Irmão retomou frequência. Frequência atualizada para %.1f%%.", $frequencia);
                        $this->sinalModel->atualizarStatus($sinalExistente['id'], 'em_observacao', null, $detalhes);
                    }
                }
            }

            // Regra especial: se havia um sinal aberto e o obreiro esteve presente na última sessão compatível,
            // obrigatoriamente move para 'em_observacao', mesmo que o percentual acumulado ainda esteja baixo.
            if ($sinalExistente && $sinalExistente['status'] === 'aberto' && $estevePresenteUltima === true) {
                $detalhes = sprintf("Retorno do irmão constatado na última sessão realizada. Frequência atual de %.1f%%.", $frequencia);
                $this->sinalModel->atualizarStatus($sinalExistente['id'], 'em_observacao', null, $detalhes);
            }
        }
    }

    private function atualizarSinalManualParaObservacaoSeRetornou(int $sinalId, bool $estevePresenteUltima, string $nivel, string $detalhes): void
    {
        // Se esteve presente na última, o status do sinal deve ir para 'em_observacao'
        $status = $estevePresenteUltima ? 'em_observacao' : 'aberto';
        
        $stmt = $this->db->prepare("
            UPDATE public.vida_loja_sinais
            SET nivel = :nivel,
                status = CASE WHEN status = 'aberto' AND :status = 'em_observacao' THEN 'em_observacao' ELSE status END,
                detalhes = :detalhes,
                updated_at = NOW()
            WHERE id = :id
        ");
        $stmt->execute([
            'id' => $sinalId,
            'nivel' => $nivel,
            'status' => $status,
            'detalhes' => $detalhes,
        ]);
    }

    /**
     * Retorna a presença e se ela é justificada para um obreiro e sessão.
     */
    private function obterStatusPresencaObreiro(int $sessaoId, string $obreiroId): array
    {
        // 1. Verificar em presencas_sessao
        $stmt = $this->db->prepare("
            SELECT presente, observacao
            FROM public.presencas_sessao
            WHERE sessao_id = :sessao_id
              AND obreiro_id = :obreiro_id
            LIMIT 1
        ");
        $stmt->execute([
            'sessao_id' => $sessaoId,
            'obreiro_id' => $obreiroId,
        ]);
        $presenca = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($presenca) {
            $presente = (bool) $presenca['presente'];
            $obs = trim((string) ($presenca['observacao'] ?? ''));
            
            // Justificado se o presente for false e a observação não for vazia
            $justificado = (!$presente && $obs !== '');
            
            return [
                'presente' => $presente,
                'justificado' => $justificado,
                'observacao' => $obs
            ];
        }

        // 2. Se não há presencas_sessao, verificar em confirmacoes_sessao (prévia de ausência)
        $stmt = $this->db->prepare("
            SELECT status_confirmacao, observacao
            FROM public.confirmacoes_sessao
            WHERE sessao_id = :sessao_id
              AND obreiro_id = :obreiro_id
            LIMIT 1
        ");
        $stmt->execute([
            'sessao_id' => $sessaoId,
            'obreiro_id' => $obreiroId,
        ]);
        $confirmacao = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($confirmacao) {
            $statusConf = trim((string) ($confirmacao['status_confirmacao'] ?? ''));
            $justificado = ($statusConf === 'ausente');
            
            return [
                'presente' => false,
                'justificado' => $justificado,
                'observacao' => trim((string) ($confirmacao['observacao'] ?? ''))
            ];
        }

        // Sem dados de presença na sessão
        return [
            'presente' => false,
            'justificado' => false,
            'observacao' => null
        ];
    }

    /**
     * Verifica a compatibilidade ritualística de grau.
     */
    private function verificarCompatibilidadeGrau(string $grauObreiro, string $grauSessao): bool
    {
        $grauObreiro = strtolower(trim($grauObreiro));
        $grauSessao = strtolower(trim($grauSessao));

        $niveis = [
            'aprendiz' => 1,
            'aprendiz macom' => 1,
            'companheiro' => 2,
            'companheiro macom' => 2,
            'mestre' => 3,
            'mestre macom' => 3,
            'mestre instalado' => 4,
        ];

        $nvObreiro = $niveis[$grauObreiro] ?? 1;
        $nvSessao = $niveis[$grauSessao] ?? 1;

        // Obreiro pode frequentar sessões de grau menor ou igual ao dele.
        return $nvObreiro >= $nvSessao;
    }

    private function obterLojaAtualId(): int
    {
        return $this->resolveCurrentStoreId($this->db);
    }

    public function sincronizarSinaisAgape(): void
    {
        $obreiroModel = new Obreiro();
        $obreiros = $obreiroModel->getAllAtivos();
        $lojaId = $this->obterLojaAtualId();

        foreach ($obreiros as $obreiro) {
            $obreiroId = $obreiro['id'];

            // Apenas ativos na vida da loja
            $situacao = strtolower(trim((string) ($obreiro['situacao_quadro'] ?? 'ativo')));
            if ($situacao !== 'ativo') {
                continue;
            }

            // Obter todas as sessões realizadas com ágape ativo na loja onde o obreiro esteve presente ritualisticamente
            $stmt = $this->db->prepare("
                SELECT s.id, s.data_hora_inicio, s.agape_modalidade, s.agape_modelo_financeiro, s.agape_valor,
                       ps.presente_agape
                FROM public.sessoes s
                JOIN public.presencas_sessao ps ON ps.sessao_id = s.id
                WHERE s.status = 'realizada'
                  AND s.loja_id = :loja_id
                  AND s.agape_ativo = TRUE
                  AND ps.obreiro_id = :obreiro_id
                  AND ps.presente = TRUE
                ORDER BY s.data_hora_inicio DESC
            ");
            $stmt->execute([
                'loja_id' => $lojaId,
                'obreiro_id' => $obreiroId
            ]);
            $oportunidades = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($oportunidades) < 3) {
                // Necessário pelo menos 3 oportunidades de ágape para analisar
                continue;
            }

            // Tomar as 3 oportunidades mais recentes
            $recentes = array_slice($oportunidades, 0, 3);

            // Verificar se não permaneceu em nenhuma das 3 (presente_agape = false em todas)
            $todasAusentes = true;
            foreach ($recentes as $op) {
                if ($op['presente_agape'] !== false) {
                    $todasAusentes = false;
                    break;
                }
            }

            $sinalExistente = $this->sinalModel->obterSinalAberto($obreiroId, 'baixa_integracao_agape');

            if ($todasAusentes) {
                $nivel = 'medio';
                $detalhes = 'Atenção sugerida para integração fraterna.';

                if ($sinalExistente) {
                    $this->atualizarSinalManualParaObservacaoSeRetornouAgape($sinalExistente['id'], false, $nivel, $detalhes);
                } else {
                    $this->sinalModel->criar([
                        'obreiro_id' => $obreiroId,
                        'tipo' => 'baixa_integracao_agape',
                        'status' => 'aberto',
                        'nivel' => $nivel,
                        'detalhes' => $detalhes,
                    ]);
                }
            } else {
                // Se NÃO faltou nos 3 recentes, mas queremos verificar se retornou em gratuito após baixa participação nos pagos
                $maisRecente = $oportunidades[0];
                $ficouMaisRecente = ($maisRecente['presente_agape'] === true);

                $eGratuito = (
                    $maisRecente['agape_modalidade'] === 'gratuito'
                    || $maisRecente['agape_modelo_financeiro'] === 'oficial_loja'
                    || (float)($maisRecente['agape_valor'] ?? 0) === 0.0
                );

                $barreiraDetectada = false;
                if ($ficouMaisRecente && $eGratuito) {
                    $pagosFaltados = 0;
                    $pagosAnalisados = 0;
                    
                    // Começa a analisar a partir da segunda oportunidade mais recente
                    for ($i = 1; $i < count($oportunidades); $i++) {
                        $op = $oportunidades[$i];
                        $opPago = (
                            $op['agape_modalidade'] === 'pago'
                            && $op['agape_modelo_financeiro'] !== 'oficial_loja'
                            && (float)($op['agape_valor'] ?? 0) > 0.0
                        );
                        
                        if ($opPago) {
                            $pagosAnalisados++;
                            if ($op['presente_agape'] === false) {
                                $pagosFaltados++;
                            }
                        }
                        
                        if ($pagosAnalisados >= 3) {
                            break;
                        }
                    }

                    if ($pagosFaltados >= 2) {
                        $barreiraDetectada = true;
                    }
                }

                if ($barreiraDetectada) {
                    $nivel = 'alto';
                    $detalhes = 'Foi percebida baixa participação em ágapes com rateio e retorno em ágape sem custo individual. Recomenda-se contato fraterno discreto para compreender se há alguma barreira de participação.';

                    if ($sinalExistente) {
                        $this->atualizarSinalManualParaObservacaoSeRetornouAgape($sinalExistente['id'], true, $nivel, $detalhes);
                    } else {
                        $this->sinalModel->criar([
                            'obreiro_id' => $obreiroId,
                            'tipo' => 'baixa_integracao_agape',
                            'status' => 'aberto',
                            'nivel' => $nivel,
                            'detalhes' => $detalhes,
                        ]);
                    }
                } else {
                    if ($sinalExistente) {
                        $estevePresenteUltima = ($oportunidades[0]['presente_agape'] === true);
                        if ($sinalExistente['status'] === 'aberto' && $estevePresenteUltima) {
                            $detalhes = 'Retorno do irmão ao ágape constatado na última sessão.';
                            $this->sinalModel->atualizarStatus($sinalExistente['id'], 'em_observacao', null, $detalhes);
                        }
                    }
                }
            }
        }
    }

    private function atualizarSinalManualParaObservacaoSeRetornouAgape(int $sinalId, bool $estevePresenteUltima, string $nivel, string $detalhes): void
    {
        $status = $estevePresenteUltima ? 'em_observacao' : 'aberto';
        
        $stmt = $this->db->prepare("
            UPDATE public.vida_loja_sinais
            SET nivel = :nivel,
                status = CASE WHEN status = 'aberto' AND :status = 'em_observacao' THEN 'em_observacao' ELSE status END,
                detalhes = :detalhes,
                updated_at = NOW()
            WHERE id = :id
        ");
        $stmt->execute([
            'id' => $sinalId,
            'nivel' => $nivel,
            'status' => $status,
            'detalhes' => $detalhes,
        ]);
    }
}
