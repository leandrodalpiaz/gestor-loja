<?php

namespace App\Models;

use App\Config\Database;
use App\Core\Tenant\ResolvesStoreTenant;
use PDO;
use App\Models\EventoSessao;
use App\Models\ConfiguracaoLoja;
use App\Models\Sessao;
use App\Models\VisitaExternaSessao;

class Balaustre
{
    use ResolvesStoreTenant;

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    private function isUuid(string $value): bool
    {
        return (bool) preg_match(
            '/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[1-5][0-9a-fA-F]{3}-[89abAB][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/',
            trim($value)
        );
    }

    public function salvarPorSessao(int $sessaoId, array $data, ?string $autorId = null): bool
    {
        $sessaoId = max(0, $sessaoId);
        $lojaId = $this->obterLojaAtualId();
        $balaustreId = (int) ($data['balaustre_id'] ?? 0);
        $dadosJson = $this->montarDadosCapturados($data);
        $textoFinalOficial = $this->buildTextoOficial($sessaoId, $dadosJson, $data);
        $visitasExternas = $dadosJson['saco_propostas']['visitas_externas'] ?? [];
        $eventosSessao = $this->montarEventosEstruturados($data, $dadosJson);
        $atual = $sessaoId > 0 ? $this->buscarPorSessao($sessaoId) : ($balaustreId > 0 ? $this->buscarPorId($balaustreId) : null);

        $this->db->beginTransaction();
        try {
            $okBalaustre = false;

            if ($atual) {
                $stmt = $this->db->prepare("
                UPDATE balaustres
                   SET numero_balaustre = :numero_balaustre,
                       sessao_id = :sessao_id,
                       template_versao = :template_versao,
                       texto_final = :texto_final,
                       dados_capturados = CAST(:dados_capturados AS jsonb),
                       preparado_por = :preparado_por,
                       preparado_em = NOW(),
                       status = CASE WHEN status = 'em_votacao' THEN status ELSE 'rascunho' END,
                       apto_votacao = CASE WHEN status = 'em_votacao' THEN apto_votacao ELSE FALSE END,
                       apto_votacao_em = CASE WHEN status = 'em_votacao' THEN apto_votacao_em ELSE NULL END,
                       apto_votacao_por = CASE WHEN status = 'em_votacao' THEN apto_votacao_por ELSE NULL END,
                       updated_at = NOW()
                  WHERE id = :id
                    AND (
                        :sessao_id IS NULL
                        OR EXISTS (
                            SELECT 1 FROM sessoes s
                            WHERE s.id = :sessao_id
                              AND s.loja_id = :loja_id
                        )
                    )
            ");

                $okBalaustre = $stmt->execute([
                    'id' => (int) ($atual['id'] ?? 0),
                    'sessao_id' => $sessaoId > 0 ? $sessaoId : null,
                    'numero_balaustre' => trim((string) ($data['numero_balaustre'] ?? '')) ?: null,
                    'template_versao' => trim((string) ($data['template_versao'] ?? 'oficial-v1')) ?: 'oficial-v1',
                    'texto_final' => $textoFinalOficial !== '' ? $textoFinalOficial : null,
                    'dados_capturados' => $dadosJson !== null ? json_encode($dadosJson, JSON_UNESCAPED_UNICODE) : null,
                    'preparado_por' => $autorId,
                    'loja_id' => $lojaId,
                ]);
            } else {
                $stmt = $this->db->prepare("
            INSERT INTO balaustres (
                sessao_id,
                numero_balaustre,
                template_versao,
                texto_final,
                dados_capturados,
                preparado_por,
                preparado_em,
                status,
                updated_at
            ) VALUES (
                :sessao_id,
                :numero_balaustre,
                :template_versao,
                :texto_final,
                CAST(:dados_capturados AS jsonb),
                :preparado_por,
                NOW(),
                'rascunho',
                NOW()
            )
        ");

                $okBalaustre = $stmt->execute([
                    'sessao_id' => $sessaoId > 0 ? $sessaoId : null,
                    'numero_balaustre' => trim((string) ($data['numero_balaustre'] ?? '')) ?: null,
                    'template_versao' => trim((string) ($data['template_versao'] ?? 'oficial-v1')) ?: 'oficial-v1',
                    'texto_final' => $textoFinalOficial !== '' ? $textoFinalOficial : null,
                    'dados_capturados' => $dadosJson !== null ? json_encode($dadosJson, JSON_UNESCAPED_UNICODE) : null,
                    'preparado_por' => $autorId,
                ]);
            }

            if (!$okBalaustre) {
                $this->db->rollBack();
                return false;
            }

            if ($sessaoId > 0) {
                $okVisitas = (new VisitaExternaSessao())->substituirPorSessao($sessaoId, is_array($visitasExternas) ? $visitasExternas : [], $autorId);
                if (!$okVisitas) {
                    $this->db->rollBack();
                    return false;
                }

                $okEventos = (new EventoSessao())->substituirPorSessao($sessaoId, $eventosSessao, $autorId);
                if (!$okEventos) {
                    $this->db->rollBack();
                    return false;
                }
            }

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return false;
        }
    }

    public function gerarTextoOficialPreview(int $sessaoId, array $data): string
    {
        $dadosJson = $this->montarDadosCapturados($data);
        return $this->buildTextoOficial($sessaoId, $dadosJson, $data);
    }

    public function adicionarVisitanteSessao(int $sessaoId, array $visitante, ?string $autorId = null): bool
    {
        if ($sessaoId <= 0) {
            return false;
        }

        $nome = trim((string) ($visitante['nome'] ?? ''));
        if ($nome === '') {
            return false;
        }

        $atual = $this->buscarPorSessao($sessaoId);
        $dados = [];
        if ($atual && !empty($atual['dados_capturados'])) {
            $decoded = is_string($atual['dados_capturados'])
                ? json_decode($atual['dados_capturados'], true)
                : $atual['dados_capturados'];
            $dados = is_array($decoded) ? $decoded : [];
        }

        if (!isset($dados['palavra_bem_ordem']) || !is_array($dados['palavra_bem_ordem'])) {
            $dados['palavra_bem_ordem'] = ['obreiros' => [], 'visitantes' => []];
        }
        if (!isset($dados['palavra_bem_ordem']['visitantes']) || !is_array($dados['palavra_bem_ordem']['visitantes'])) {
            $dados['palavra_bem_ordem']['visitantes'] = [];
        }

        $dados['palavra_bem_ordem']['visitantes'][] = [
            'nome' => $nome,
            'loja' => trim((string) ($visitante['loja'] ?? '')),
            'oriente' => trim((string) ($visitante['oriente'] ?? '')),
            'potencia' => trim((string) ($visitante['potencia'] ?? '')),
            'grau' => trim((string) ($visitante['grau'] ?? '')),
            'dia_reuniao' => trim((string) ($visitante['dia_reuniao'] ?? '')),
            'fala_resumida' => trim((string) ($visitante['fala_resumida'] ?? '')),
        ];

        if ($atual) {
            $stmt = $this->db->prepare("
                UPDATE balaustres
                   SET dados_capturados = CAST(:dados AS jsonb),
                       preparado_por = :autor,
                       preparado_em = NOW(),
                       updated_at = NOW()
                 WHERE id = :id
            ");
            return $stmt->execute([
                'dados' => json_encode($dados, JSON_UNESCAPED_UNICODE),
                'autor' => $autorId,
                'id' => (int) $atual['id'],
            ]);
        }

        $stmt = $this->db->prepare("
            INSERT INTO balaustres (
                sessao_id, loja_id, template_versao, dados_capturados, preparado_por,
                preparado_em, status, updated_at
            ) VALUES (
                :sessao_id, :loja_id, 'oficial-v1', CAST(:dados AS jsonb), :autor,
                NOW(), 'rascunho', NOW()
            )
        ");

        return $stmt->execute([
            'sessao_id' => $sessaoId,
            'loja_id' => $this->obterLojaAtualId(),
            'dados' => json_encode($dados, JSON_UNESCAPED_UNICODE),
            'autor' => $autorId,
        ]);
    }

    public function validarParaApto(int $balaustreId): array
    {
        $balaustre = $this->buscarPorId($balaustreId);
        if (!$balaustre) {
            return ['ok' => false, 'erro' => 'Balaustre nao encontrado.'];
        }
        $dados = $balaustre['dados_capturados'] ?? null;
        if (is_string($dados)) {
            $dec = json_decode($dados, true);
            $dados = is_array($dec) ? $dec : [];
        }
        $dados = is_array($dados) ? $dados : [];

        $obrigatorios = [
            'abertura' => trim((string) ($dados['blocos']['abertura'] ?? '')),
            'expediente' => trim((string) ($dados['blocos']['expediente'] ?? '')),
            'saco_propostas' => trim((string) ($dados['blocos']['saco_propostas'] ?? '')),
            'ordem_dia' => trim((string) ($dados['blocos']['ordem_dia'] ?? '')),
            'tronco_solidariedade' => trim((string) ($dados['blocos']['tronco_solidariedade'] ?? '')),
            'conclusoes_orador' => trim((string) ($dados['blocos']['conclusoes_orador'] ?? '')),
            'encerramento' => trim((string) ($dados['blocos']['encerramento'] ?? '')),
            'assinaturas' => trim((string) ($dados['blocos']['assinaturas'] ?? '')),
        ];
        foreach ($obrigatorios as $chave => $valor) {
            if ($valor === '') {
                return ['ok' => false, 'erro' => 'Campo obrigatorio nao preenchido para o padrao oficial: ' . str_replace('_', ' ', $chave) . '.'];
            }
        }

        return ['ok' => true];
    }

    private function buildTextoOficial(int $sessaoId, array $dadosJson, array $entrada): string
    {
        $sessao = $sessaoId > 0 ? (new Sessao())->findById($sessaoId) : null;
        $cfg = (new ConfiguracaoLoja())->obter();

        $numeroBalaustre = trim((string) ($entrada['numero_balaustre'] ?? ''));
        $tipoSessao = trim((string) ($sessao['tipo_sessao'] ?? 'Sessao'));
        $grauSessao = trim((string) ($sessao['grau_sessao'] ?? ''));
        $tituloSessao = trim((string) ($sessao['titulo'] ?? ''));
        $dataInicio = trim((string) ($sessao['data_hora_inicio'] ?? ''));
        $dataFim = trim((string) ($sessao['data_hora_fim'] ?? ''));

        $abertura = trim((string) ($dadosJson['blocos']['abertura'] ?? ''));
        if ($abertura === '') {
            $abertura = $this->montarAberturaPadrao($cfg, $tipoSessao, $grauSessao, $tituloSessao, $dataInicio);
        }

        $palavraQuadro = $this->comporPalavraQuadro($dadosJson);
        $palavraVisitantes = $this->comporPalavraVisitantes($dadosJson);
        $palavraComposta = trim($palavraQuadro . ($palavraQuadro !== '' && $palavraVisitantes !== '' ? ' ' : '') . $palavraVisitantes);

        $dadosJson['cabecalho'] = implode("\n", array_filter([
            'ESTADO DO RIO GRANDE DO SUL',
            'ORIENTE DE ' . strtoupper(trim((string) ($cfg['oriente'] ?? ''))),
            'Balaustre nº ' . ($numeroBalaustre !== '' ? $numeroBalaustre : '---'),
            strtoupper(trim($tipoSessao . ' ' . ($grauSessao !== '' ? 'DE ' . $grauSessao : ''))),
        ], static fn ($linha) => trim((string) $linha) !== ''));
        $dadosJson['blocos']['abertura'] = $abertura;
        $dadosJson['blocos']['balaustre'] = trim((string) ($dadosJson['blocos']['balaustre'] ?? 'Sem registro.'));
        $dadosJson['blocos']['expediente'] = trim((string) ($dadosJson['blocos']['expediente'] ?? 'Sem expediente.'));
        $dadosJson['blocos']['saco_propostas'] = trim((string) ($dadosJson['blocos']['saco_propostas'] ?? 'Sem registros.'));
        $dadosJson['blocos']['ordem_dia'] = trim((string) ($dadosJson['blocos']['ordem_dia'] ?? 'Sem registros.'));
        $dadosJson['blocos']['tronco_solidariedade'] = trim((string) ($dadosJson['blocos']['tronco_solidariedade'] ?? 'Sem coleta informada.'));
        $dadosJson['blocos']['conclusoes_orador'] = trim((string) ($dadosJson['blocos']['conclusoes_orador'] ?? 'Sem registro.'));
        $dadosJson['blocos']['encerramento'] = trim((string) ($dadosJson['blocos']['encerramento'] ?? $this->montarEncerramentoPadrao($dataFim)));
        $dadosJson['blocos']['assinaturas'] = trim((string) ($dadosJson['blocos']['assinaturas'] ?? 'Secretario              Guarda da Lei              Veneravel Mestre'));

        if ($palavraComposta !== '' && empty($dadosJson['palavra_bem_ordem']['obreiros']) && empty($dadosJson['palavra_bem_ordem']['visitantes'])) {
            $dadosJson['palavra_obreiros'] = [['nome' => '', 'fala' => $palavraComposta]];
        }

        return BalaustreComposer::build($dadosJson);

        $linhas = [
            'ESTADO DO RIO GRANDE DO SUL',
            '',
            'ORIENTE DE ' . strtoupper(trim((string) ($cfg['oriente'] ?? ''))),
            '',
            'Balaustre n° ' . ($numeroBalaustre !== '' ? $numeroBalaustre : '---'),
            strtoupper(trim($tipoSessao . ' ' . ($grauSessao !== '' ? 'DE ' . $grauSessao : ''))),
            $abertura,
            'BALAUSTRE: ' . trim((string) ($dadosJson['blocos']['balaustre'] ?? 'Sem registro.')),
            'EXPEDIENTE: ' . trim((string) ($dadosJson['blocos']['expediente'] ?? 'Sem expediente.')),
            'SACO DE PROPOSTAS E INFORMACOES: ' . trim((string) ($dadosJson['blocos']['saco_propostas'] ?? 'Sem registros.')),
            'ORDEM DO DIA: ' . trim((string) ($dadosJson['blocos']['ordem_dia'] ?? 'Sem registros.')),
            'TRONCO DE SOLIDARIEDADE: ' . trim((string) ($dadosJson['blocos']['tronco_solidariedade'] ?? 'Sem coleta informada.')),
            'PALAVRA A BEM DA ORDEM EM GERAL E DO QUADRO EM PARTICULAR: ' . ($palavraComposta !== '' ? $palavraComposta : 'Nao utilizada.'),
            'CONCLUSOES DO ORADOR: ' . trim((string) ($dadosJson['blocos']['conclusoes_orador'] ?? 'Sem registro.')),
            'ENCERRAMENTO: ' . trim((string) ($dadosJson['blocos']['encerramento'] ?? $this->montarEncerramentoPadrao($dataFim))),
            trim((string) ($dadosJson['blocos']['assinaturas'] ?? 'Secretario              Guarda da Lei              Veneravel Mestre')),
        ];

        return trim(implode("\n\n", array_filter($linhas, static fn ($linha) => trim((string) $linha) !== '')));
    }

    private function montarAberturaPadrao(array $cfg, string $tipoSessao, string $grauSessao, string $tituloSessao, string $dataInicio): string
    {
        $dataExtenso = $dataInicio !== '' ? date('d/m/Y H\hi', strtotime($dataInicio)) : 'data nao informada';
        $nomeLoja = trim((string) ($cfg['nome_loja'] ?? 'Loja'));
        $numeroLoja = trim((string) ($cfg['numero_loja'] ?? ''));
        $templo = trim((string) ($cfg['nome_templo'] ?? 'Templo nao informado'));
        $oriente = trim((string) ($cfg['oriente'] ?? 'Oriente nao informado'));

        return 'Aos ' . $dataExtenso . ', no ' . $templo . ', pertencente a ' . $nomeLoja
            . ($numeroLoja !== '' ? ' n° ' . $numeroLoja : '')
            . ', no Oriente de ' . $oriente
            . ', reuniram-se os obreiros para realizacao de ' . trim($tipoSessao . ' ' . $grauSessao)
            . ($tituloSessao !== '' ? ' sob o titulo "' . $tituloSessao . '"' : '')
            . '.';
    }

    private function montarEncerramentoPadrao(string $dataFim): string
    {
        if ($dataFim === '') {
            return 'Nada mais havendo, os trabalhos foram encerrados no mesmo grau da abertura.';
        }
        return 'Nada mais havendo, os trabalhos foram encerrados no mesmo grau da abertura as ' . date('H\hi', strtotime($dataFim)) . '.';
    }

    private function comporPalavraQuadro(array $dadosJson): string
    {
        $obreiros = $dadosJson['palavra_bem_ordem']['obreiros'] ?? [];
        if (!is_array($obreiros) || $obreiros === []) {
            return '';
        }
        $linhas = [];
        foreach ($obreiros as $item) {
            if (!is_array($item)) {
                continue;
            }
            $nome = trim((string) ($item['nome'] ?? ''));
            $fala = trim((string) ($item['fala_resumida'] ?? ''));
            $cargo = trim((string) ($item['cargo_no_momento'] ?? ''));
            if ($nome === '' && $fala === '') {
                continue;
            }
            $linhas[] = ($nome !== '' ? $nome : 'Irmao nao identificado')
                . ($cargo !== '' ? ' (' . $cargo . ')' : '')
                . ($fala !== '' ? ' relatou: ' . $fala : '.');
        }
        return implode(' ', $linhas);
    }

    private function comporPalavraVisitantes(array $dadosJson): string
    {
        $visitantes = $dadosJson['palavra_bem_ordem']['visitantes'] ?? [];
        if (!is_array($visitantes) || $visitantes === []) {
            return '';
        }
        $linhas = [];
        foreach ($visitantes as $item) {
            if (!is_array($item)) {
                continue;
            }
            $nome = trim((string) ($item['nome'] ?? ''));
            $loja = trim((string) ($item['loja'] ?? ''));
            $fala = trim((string) ($item['fala_resumida'] ?? ''));
            if ($nome === '' && $fala === '') {
                continue;
            }
            $linhas[] = ($nome !== '' ? $nome : 'Visitante')
                . ($loja !== '' ? ' da loja ' . $loja : '')
                . ($fala !== '' ? ' manifestou: ' . $fala : '.');
        }
        return implode(' ', $linhas);
    }

    private function normalizarComparacao(?string $valor): string
    {
        $valor = strtolower(trim((string) $valor));
        $valor = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $valor) ?: $valor;
        $valor = preg_replace('/[^a-z0-9]+/', ' ', $valor) ?? '';
        return trim($valor);
    }

    private function montarDadosCapturados(array $data): ?array
    {
        $dadosCapturados = trim((string) ($data['dados_capturados'] ?? ''));
        $dadosJson = $dadosCapturados !== '' ? json_decode($dadosCapturados, true) : null;
        if ($dadosCapturados !== '' && !is_array($dadosJson)) {
            $dadosJson = ['rascunho_livre' => $dadosCapturados];
        }
        if (!is_array($dadosJson)) {
            $dadosJson = [];
        }

        $lojasFrequentes = array_values(array_filter(array_map(
            static fn ($item) => trim((string) $item),
            is_array($data['lojas_visitantes_frequentes'] ?? null)
                ? $data['lojas_visitantes_frequentes']
                : preg_split('/\r\n|\r|\n|,/', (string) ($data['lojas_visitantes_frequentes'] ?? ''))
        )));

        $visitanteNomes = is_array($data['palavra_visitante_nome'] ?? null) ? $data['palavra_visitante_nome'] : [];
        $visitanteLojas = is_array($data['palavra_visitante_loja'] ?? null) ? $data['palavra_visitante_loja'] : [];
        $visitanteOrientes = is_array($data['palavra_visitante_oriente'] ?? null) ? $data['palavra_visitante_oriente'] : [];
        $visitantePotencias = is_array($data['palavra_visitante_potencia'] ?? null) ? $data['palavra_visitante_potencia'] : [];
        $visitanteGraus = is_array($data['palavra_visitante_grau'] ?? null) ? $data['palavra_visitante_grau'] : [];
        $visitanteDias = is_array($data['palavra_visitante_dia_reuniao'] ?? null) ? $data['palavra_visitante_dia_reuniao'] : [];
        $visitanteFalhas = is_array($data['palavra_visitante_fala'] ?? null) ? $data['palavra_visitante_fala'] : [];
        $obreiroIds = is_array($data['palavra_obreiro_id'] ?? null) ? $data['palavra_obreiro_id'] : [];
        $obreiroNomes = is_array($data['palavra_obreiro_nome'] ?? null) ? $data['palavra_obreiro_nome'] : [];
        $obreiroCargos = is_array($data['palavra_obreiro_cargo'] ?? null) ? $data['palavra_obreiro_cargo'] : [];
        $obreiroFalhas = is_array($data['palavra_obreiro_fala'] ?? null) ? $data['palavra_obreiro_fala'] : [];

        $totalVisitantes = max(
            count($visitanteNomes),
            count($visitanteLojas),
            count($visitanteOrientes),
            count($visitantePotencias),
            count($visitanteGraus),
            count($visitanteDias),
            count($visitanteFalhas)
        );
        $visitantes = [];
        for ($i = 0; $i < $totalVisitantes; $i++) {
            $nome = trim((string) ($visitanteNomes[$i] ?? ''));
            $loja = trim((string) ($visitanteLojas[$i] ?? ''));
            $fala = trim((string) ($visitanteFalhas[$i] ?? ''));
            if ($nome === '' && $loja === '' && $fala === '') {
                continue;
            }
            $visitantes[] = [
                'nome' => $nome,
                'loja' => $loja,
                'oriente' => trim((string) ($visitanteOrientes[$i] ?? '')),
                'potencia' => trim((string) ($visitantePotencias[$i] ?? '')),
                'grau' => trim((string) ($visitanteGraus[$i] ?? '')),
                'dia_reuniao' => trim((string) ($visitanteDias[$i] ?? '')),
                'fala_resumida' => $fala,
            ];
        }

        $totalObreirosPalavra = max(
            count($obreiroIds),
            count($obreiroNomes),
            count($obreiroCargos),
            count($obreiroFalhas)
        );
        $obreirosPalavra = [];
        for ($i = 0; $i < $totalObreirosPalavra; $i++) {
            $obreiroId = trim((string) ($obreiroIds[$i] ?? ''));
            $nome = trim((string) ($obreiroNomes[$i] ?? ''));
            $fala = trim((string) ($obreiroFalhas[$i] ?? ''));
            if ($obreiroId === '' && $nome === '' && $fala === '') {
                continue;
            }
            $obreirosPalavra[] = [
                'obreiro_id' => $this->isUuid($obreiroId) ? $obreiroId : null,
                'nome' => $nome,
                'cargo_no_momento' => trim((string) ($obreiroCargos[$i] ?? '')),
                'fala_resumida' => $fala,
            ];
        }

        $cargoCodigos = is_array($data['cargo_sessao_codigo'] ?? null) ? $data['cargo_sessao_codigo'] : [];
        $cargoNomes = is_array($data['cargo_sessao_nome'] ?? null) ? $data['cargo_sessao_nome'] : [];
        $cargoTitulares = is_array($data['cargo_sessao_titular_oficial'] ?? null) ? $data['cargo_sessao_titular_oficial'] : [];
        $cargoOcupantes = is_array($data['cargo_sessao_ocupante_nome'] ?? null) ? $data['cargo_sessao_ocupante_nome'] : [];
        $cargoObs = is_array($data['cargo_sessao_observacao'] ?? null) ? $data['cargo_sessao_observacao'] : [];

        $totalCargos = max(
            count($cargoCodigos),
            count($cargoNomes),
            count($cargoTitulares),
            count($cargoOcupantes),
            count($cargoObs)
        );
        $cargosSessao = [];
        for ($i = 0; $i < $totalCargos; $i++) {
            $codigo = strtoupper(trim((string) ($cargoCodigos[$i] ?? '')));
            $cargoNome = trim((string) ($cargoNomes[$i] ?? ''));
            $titularOficial = trim((string) ($cargoTitulares[$i] ?? ''));
            $ocupanteNome = trim((string) ($cargoOcupantes[$i] ?? ''));
            $observacao = trim((string) ($cargoObs[$i] ?? ''));

            if ($codigo === '' && $cargoNome === '' && $ocupanteNome === '' && $titularOficial === '') {
                continue;
            }

            $tipoOcupacao = 'regular';
            if ($ocupanteNome !== '' && $titularOficial !== '') {
                if ($this->normalizarComparacao($ocupanteNome) !== $this->normalizarComparacao($titularOficial)) {
                    $tipoOcupacao = 'ad_hoc';
                }
            }

            $cargosSessao[] = [
                'codigo' => $codigo,
                'cargo_nome' => $cargoNome,
                'titular_oficial' => $titularOficial,
                'ocupante_nome' => $ocupanteNome,
                'tipo_ocupacao' => $tipoOcupacao,
                'observacao' => $observacao,
            ];
        }

        $visitaMembroIds = is_array($data['visita_externa_obreiro_id'] ?? null) ? $data['visita_externa_obreiro_id'] : [];
        $visitaMembroNomes = is_array($data['visita_externa_obreiro_nome'] ?? null) ? $data['visita_externa_obreiro_nome'] : [];
        $visitaLojas = is_array($data['visita_externa_loja'] ?? null) ? $data['visita_externa_loja'] : [];
        $visitaPotencias = is_array($data['visita_externa_potencia'] ?? null) ? $data['visita_externa_potencia'] : [];
        $visitaOrientes = is_array($data['visita_externa_oriente'] ?? null) ? $data['visita_externa_oriente'] : [];
        $visitaDatas = is_array($data['visita_externa_data'] ?? null) ? $data['visita_externa_data'] : [];
        $visitaObs = is_array($data['visita_externa_observacao'] ?? null) ? $data['visita_externa_observacao'] : [];
        $totalVisitasExternas = max(
            count($visitaMembroIds),
            count($visitaMembroNomes),
            count($visitaLojas),
            count($visitaPotencias),
            count($visitaOrientes),
            count($visitaDatas),
            count($visitaObs)
        );
        $visitasExternas = [];
        for ($i = 0; $i < $totalVisitasExternas; $i++) {
            $membroId = trim((string) ($visitaMembroIds[$i] ?? ''));
            $membroNome = trim((string) ($visitaMembroNomes[$i] ?? ''));
            $loja = trim((string) ($visitaLojas[$i] ?? ''));
            $potencia = trim((string) ($visitaPotencias[$i] ?? ''));
            $oriente = trim((string) ($visitaOrientes[$i] ?? ''));
            $dataVisita = trim((string) ($visitaDatas[$i] ?? ''));
            $observacao = trim((string) ($visitaObs[$i] ?? ''));
            if ($membroId === '' && $membroNome === '' && $loja === '' && $potencia === '' && $oriente === '' && $dataVisita === '' && $observacao === '') {
                continue;
            }
            $visitasExternas[] = [
                'obreiro_id' => $membroId !== '' ? $membroId : null,
                'obreiro_nome' => $membroNome,
                'loja' => $loja,
                'potencia_obediencia' => $potencia,
                'oriente' => $oriente,
                'data_visita' => $dataVisita,
                'observacao' => $observacao,
            ];
        }

        $congressoTitulos = is_array($data['congresso_titulo'] ?? null) ? $data['congresso_titulo'] : [];
        $congressoPromotores = is_array($data['congresso_promotor'] ?? null) ? $data['congresso_promotor'] : [];
        $congressoDatas = is_array($data['congresso_data'] ?? null) ? $data['congresso_data'] : [];
        $congressoObs = is_array($data['congresso_observacao'] ?? null) ? $data['congresso_observacao'] : [];
        $totalCongressos = max(count($congressoTitulos), count($congressoPromotores), count($congressoDatas), count($congressoObs));
        $congressos = [];
        for ($i = 0; $i < $totalCongressos; $i++) {
            $titulo = trim((string) ($congressoTitulos[$i] ?? ''));
            $promotor = trim((string) ($congressoPromotores[$i] ?? ''));
            $dataEvento = trim((string) ($congressoDatas[$i] ?? ''));
            $observacao = trim((string) ($congressoObs[$i] ?? ''));
            if ($titulo === '' && $promotor === '' && $dataEvento === '' && $observacao === '') {
                continue;
            }
            $congressos[] = [
                'titulo' => $titulo,
                'promotor' => $promotor,
                'data' => $dataEvento,
                'observacao' => $observacao,
            ];
        }

        $palestraTitulos = is_array($data['palestra_titulo'] ?? null) ? $data['palestra_titulo'] : [];
        $palestraPalestrantes = is_array($data['palestra_palestrante'] ?? null) ? $data['palestra_palestrante'] : [];
        $palestraDatas = is_array($data['palestra_data'] ?? null) ? $data['palestra_data'] : [];
        $palestraObs = is_array($data['palestra_observacao'] ?? null) ? $data['palestra_observacao'] : [];
        $totalPalestras = max(count($palestraTitulos), count($palestraPalestrantes), count($palestraDatas), count($palestraObs));
        $palestras = [];
        for ($i = 0; $i < $totalPalestras; $i++) {
            $titulo = trim((string) ($palestraTitulos[$i] ?? ''));
            $palestrante = trim((string) ($palestraPalestrantes[$i] ?? ''));
            $dataEvento = trim((string) ($palestraDatas[$i] ?? ''));
            $observacao = trim((string) ($palestraObs[$i] ?? ''));
            if ($titulo === '' && $palestrante === '' && $dataEvento === '' && $observacao === '') {
                continue;
            }
            $palestras[] = [
                'titulo' => $titulo,
                'palestrante' => $palestrante,
                'data' => $dataEvento,
                'observacao' => $observacao,
            ];
        }

        $dadosJson['palavra_bem_ordem'] = [
            'lojas_frequentes' => array_values(array_unique($lojasFrequentes)),
            'obreiros' => $obreirosPalavra,
            'visitantes' => $visitantes,
        ];
        $dadosJson['cargos_sessao'] = $cargosSessao;
        $dadosJson['saco_propostas'] = [
            'visitas_externas' => $visitasExternas,
        ];
        $dadosJson['eventos_realizados'] = [
            'congressos' => $congressos,
            'palestras' => $palestras,
        ];
        $dadosJson['blocos'] = [
            'abertura' => trim((string) ($data['bloco_abertura'] ?? '')),
            'balaustre' => trim((string) ($data['bloco_balaustre'] ?? '')),
            'expediente' => trim((string) ($data['bloco_expediente'] ?? '')),
            'saco_propostas' => trim((string) ($data['bloco_saco_propostas'] ?? '')),
            'ordem_dia' => trim((string) ($data['bloco_ordem_dia'] ?? '')),
            'tronco_solidariedade' => trim((string) ($data['bloco_tronco_solidariedade'] ?? '')),
            'conclusoes_orador' => trim((string) ($data['bloco_conclusoes_orador'] ?? '')),
            'encerramento' => trim((string) ($data['bloco_encerramento'] ?? '')),
            'assinaturas' => trim((string) ($data['bloco_assinaturas'] ?? '')),
        ];
        $dadosJson['observacoes_secretaria'] = trim((string) ($data['observacoes_secretaria'] ?? ''));

        return $dadosJson;
    }

    private function montarEventosEstruturados(array $data, array $dadosJson): array
    {
        $eventos = [];

        $congressos = is_array($dadosJson['eventos_realizados']['congressos'] ?? null)
            ? $dadosJson['eventos_realizados']['congressos']
            : [];
        foreach ($congressos as $item) {
            $titulo = trim((string) ($item['titulo'] ?? ''));
            if ($titulo === '') {
                continue;
            }
            $eventos[] = [
                'tipo_evento' => 'congresso',
                'titulo' => $titulo,
                'descricao' => null,
                'data_evento' => trim((string) ($item['data'] ?? '')),
                'local' => null,
                'promotor' => trim((string) ($item['promotor'] ?? '')),
                'loja_relacionada' => null,
                'oriente' => null,
                'observacao' => trim((string) ($item['observacao'] ?? '')),
            ];
        }

        $palestras = is_array($dadosJson['eventos_realizados']['palestras'] ?? null)
            ? $dadosJson['eventos_realizados']['palestras']
            : [];
        foreach ($palestras as $item) {
            $titulo = trim((string) ($item['titulo'] ?? ''));
            if ($titulo === '') {
                continue;
            }
            $eventos[] = [
                'tipo_evento' => 'palestra',
                'titulo' => $titulo,
                'descricao' => null,
                'data_evento' => trim((string) ($item['data'] ?? '')),
                'local' => null,
                'promotor' => trim((string) ($item['palestrante'] ?? '')),
                'loja_relacionada' => null,
                'oriente' => null,
                'observacao' => trim((string) ($item['observacao'] ?? '')),
            ];
        }

        $tiposExtras = [
            'evento_promovido' => [
                'titulo' => is_array($data['evento_promovido_titulo'] ?? null) ? $data['evento_promovido_titulo'] : [],
                'descricao' => is_array($data['evento_promovido_descricao'] ?? null) ? $data['evento_promovido_descricao'] : [],
                'data' => is_array($data['evento_promovido_data'] ?? null) ? $data['evento_promovido_data'] : [],
                'local' => is_array($data['evento_promovido_local'] ?? null) ? $data['evento_promovido_local'] : [],
                'promotor' => is_array($data['evento_promovido_promotor'] ?? null) ? $data['evento_promovido_promotor'] : [],
                'loja' => is_array($data['evento_promovido_loja'] ?? null) ? $data['evento_promovido_loja'] : [],
                'oriente' => is_array($data['evento_promovido_oriente'] ?? null) ? $data['evento_promovido_oriente'] : [],
                'obs' => is_array($data['evento_promovido_observacao'] ?? null) ? $data['evento_promovido_observacao'] : [],
            ],
            'evento_participado' => [
                'titulo' => is_array($data['evento_participado_titulo'] ?? null) ? $data['evento_participado_titulo'] : [],
                'descricao' => is_array($data['evento_participado_descricao'] ?? null) ? $data['evento_participado_descricao'] : [],
                'data' => is_array($data['evento_participado_data'] ?? null) ? $data['evento_participado_data'] : [],
                'local' => is_array($data['evento_participado_local'] ?? null) ? $data['evento_participado_local'] : [],
                'promotor' => is_array($data['evento_participado_promotor'] ?? null) ? $data['evento_participado_promotor'] : [],
                'loja' => is_array($data['evento_participado_loja'] ?? null) ? $data['evento_participado_loja'] : [],
                'oriente' => is_array($data['evento_participado_oriente'] ?? null) ? $data['evento_participado_oriente'] : [],
                'obs' => is_array($data['evento_participado_observacao'] ?? null) ? $data['evento_participado_observacao'] : [],
            ],
            'atividade_social' => [
                'titulo' => is_array($data['atividade_social_titulo'] ?? null) ? $data['atividade_social_titulo'] : [],
                'descricao' => is_array($data['atividade_social_descricao'] ?? null) ? $data['atividade_social_descricao'] : [],
                'data' => is_array($data['atividade_social_data'] ?? null) ? $data['atividade_social_data'] : [],
                'local' => is_array($data['atividade_social_local'] ?? null) ? $data['atividade_social_local'] : [],
                'promotor' => is_array($data['atividade_social_promotor'] ?? null) ? $data['atividade_social_promotor'] : [],
                'loja' => is_array($data['atividade_social_loja'] ?? null) ? $data['atividade_social_loja'] : [],
                'oriente' => is_array($data['atividade_social_oriente'] ?? null) ? $data['atividade_social_oriente'] : [],
                'obs' => is_array($data['atividade_social_observacao'] ?? null) ? $data['atividade_social_observacao'] : [],
            ],
        ];

        foreach ($tiposExtras as $tipo => $colunas) {
            $total = max(
                count($colunas['titulo']),
                count($colunas['descricao']),
                count($colunas['data']),
                count($colunas['local']),
                count($colunas['promotor']),
                count($colunas['loja']),
                count($colunas['oriente']),
                count($colunas['obs'])
            );
            for ($i = 0; $i < $total; $i++) {
                $titulo = trim((string) ($colunas['titulo'][$i] ?? ''));
                $descricao = trim((string) ($colunas['descricao'][$i] ?? ''));
                $dataEvento = trim((string) ($colunas['data'][$i] ?? ''));
                $local = trim((string) ($colunas['local'][$i] ?? ''));
                $promotor = trim((string) ($colunas['promotor'][$i] ?? ''));
                $loja = trim((string) ($colunas['loja'][$i] ?? ''));
                $oriente = trim((string) ($colunas['oriente'][$i] ?? ''));
                $obs = trim((string) ($colunas['obs'][$i] ?? ''));
                if ($titulo === '' && $descricao === '' && $dataEvento === '' && $local === '' && $promotor === '' && $loja === '' && $oriente === '' && $obs === '') {
                    continue;
                }
                $eventos[] = [
                    'tipo_evento' => $tipo,
                    'titulo' => $titulo !== '' ? $titulo : 'Evento sem titulo',
                    'descricao' => $descricao,
                    'data_evento' => $dataEvento,
                    'local' => $local,
                    'promotor' => $promotor,
                    'loja_relacionada' => $loja,
                    'oriente' => $oriente,
                    'observacao' => $obs,
                ];
            }
        }

        if (!empty($data['sessao_branca'])) {
            $eventos[] = [
                'tipo_evento' => 'sessao_branca',
                'titulo' => trim((string) ($data['titulo'] ?? 'Sessao branca')),
                'descricao' => 'Sessao classificada como branca/festiva.',
                'data_evento' => null,
                'local' => trim((string) ($data['templo_local'] ?? '')),
                'promotor' => null,
                'loja_relacionada' => null,
                'oriente' => null,
                'observacao' => null,
            ];
        }

        return $eventos;
    }

    public function marcarAptoVotacao(int $balaustreId, ?string $autorId = null): bool
    {
        $balaustre = $this->buscarPorId($balaustreId);
        if (!$balaustre) {
            return false;
        }

        $dados = $balaustre['dados_capturados'] ?? null;
        if (is_string($dados)) {
            $decoded = json_decode($dados, true);
            $dados = is_array($decoded) ? $decoded : [];
        } else {
            $dados = is_array($dados) ? $dados : [];
        }

        $textoFinalExistente = trim((string) ($balaustre['texto_final'] ?? ''));
        $textoFinal = $textoFinalExistente !== '' ? $textoFinalExistente : BalaustreComposer::build($dados);

        $stmt = $this->db->prepare("
            UPDATE balaustres
              SET apto_votacao = TRUE,
                   apto_votacao_em = NOW(),
                   apto_votacao_por = :autor_id,
                   status = 'apto_votacao',
                   texto_final = :texto_final,
                   updated_at = NOW()
             WHERE id = :id
               AND status <> 'em_votacao'
        ");

        return $stmt->execute([
            'id' => $balaustreId,
            'autor_id' => $autorId,
            'texto_final' => $textoFinal,
        ]);
    }

    public function abrirVotacao(int $balaustreId, ?string $abertoPor = null): array
    {
        $balaustre = $this->buscarPorId($balaustreId);
        if (!$balaustre) {
            return ['ok' => false, 'erro' => 'Balaustre não encontrado.'];
        }
        if (!(bool) $balaustre['apto_votacao']) {
            return ['ok' => false, 'erro' => 'Balaustre ainda não está apto para votação.'];
        }
        if (($balaustre['status'] ?? '') === 'em_votacao') {
            return ['ok' => false, 'erro' => 'A votação deste balaustre já está aberta.'];
        }

        $this->db->beginTransaction();
        try {
            $votacaoStmt = $this->db->prepare("
                INSERT INTO balaustre_votacoes (
                    balaustre_id,
                    aberta_por,
                    aberta_em,
                    status
                ) VALUES (
                    :balaustre_id,
                    :aberta_por,
                    NOW(),
                    'aberta'
                )
                RETURNING id
            ");
            $votacaoStmt->execute([
                'balaustre_id' => $balaustreId,
                'aberta_por' => $abertoPor,
            ]);
            $votacaoId = (int) $votacaoStmt->fetchColumn();

            $presentesStmt = $this->db->prepare("
                SELECT obreiro_id
                FROM presencas_sessao ps
                JOIN sessoes s ON s.id = ps.sessao_id
                WHERE ps.sessao_id = :sessao_id
                  AND s.loja_id = :loja_id
                  AND presente = TRUE
            ");
            $presentesStmt->execute([
                'sessao_id' => (int) $balaustre['sessao_id'],
                'loja_id' => $this->obterLojaAtualId(),
            ]);
            $presentes = $presentesStmt->fetchAll(PDO::FETCH_COLUMN);

            if ($presentes === []) {
                $this->db->rollBack();
                return ['ok' => false, 'erro' => 'Não há presenças registradas para compor a lista apta.'];
            }

            $votanteStmt = $this->db->prepare("
                INSERT INTO balaustre_votantes (
                    votacao_id,
                    obreiro_id,
                    elegivel
                ) VALUES (
                    :votacao_id,
                    :obreiro_id,
                    TRUE
                )
            ");
            foreach ($presentes as $obreiroId) {
                $votanteStmt->execute([
                    'votacao_id' => $votacaoId,
                    'obreiro_id' => (string) $obreiroId,
                ]);
            }

            $this->db->prepare("
                UPDATE balaustres
                   SET status = 'em_votacao',
                       updated_at = NOW()
                 WHERE id = :id
                   AND EXISTS (
                       SELECT 1
                       FROM balaustres b
                       JOIN sessoes s ON s.id = b.sessao_id
                       WHERE b.id = :id
                         AND s.loja_id = :loja_id
                   )
            ")->execute([
                'id' => $balaustreId,
                'loja_id' => $this->obterLojaAtualId(),
            ]);

            $this->db->commit();
            return [
                'ok' => true,
                'votacao_id' => $votacaoId,
                'total_votantes' => count($presentes),
            ];
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['ok' => false, 'erro' => 'Falha ao abrir votação: ' . $e->getMessage()];
        }
    }

    public function listarRecentes(int $limite = 30): array
    {
        $limite = max(1, min($limite, 200));
        $stmt = $this->db->prepare("
            SELECT
                b.*,
                s.titulo AS sessao_titulo,
                s.data_hora_inicio,
                (
                    SELECT COUNT(*)
                    FROM balaustre_votantes bv
                    JOIN balaustre_votacoes v ON v.id = bv.votacao_id
                    WHERE v.balaustre_id = b.id
                      AND v.status = 'aberta'
                ) AS total_votantes_abertos
            FROM balaustres b
            INNER JOIN sessoes s ON s.id = b.sessao_id
            WHERE s.loja_id = :loja_id
            ORDER BY b.updated_at DESC, b.id DESC
            LIMIT :limite
        ");
        $stmt->bindValue('loja_id', $this->obterLojaAtualId(), PDO::PARAM_INT);
        $stmt->bindValue('limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarElegibilidadeDoObreiroNosBalaustres(string $obreiroId, array $balaustreIds): array
    {
        $balaustreIds = array_values(array_filter(array_map('intval', $balaustreIds), static fn ($id) => $id > 0));
        if (!$this->isUuid($obreiroId) || $balaustreIds === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($balaustreIds), '?'));
        $sql = "
            SELECT
                v.balaustre_id,
                bv.elegivel
            FROM balaustre_votacoes v
            JOIN balaustres b ON b.id = v.balaustre_id
            JOIN sessoes s ON s.id = b.sessao_id
            JOIN balaustre_votantes bv ON bv.votacao_id = v.id
            WHERE v.status = 'aberta'
              AND s.loja_id = ?
              AND v.balaustre_id IN ({$placeholders})
              AND bv.obreiro_id = ?
        ";
        $stmt = $this->db->prepare($sql);
        $params = array_merge([$this->obterLojaAtualId()], $balaustreIds, [$obreiroId]);
        $stmt->execute($params);

        $mapa = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $mapa[(int) ($row['balaustre_id'] ?? 0)] = (bool) ($row['elegivel'] ?? false);
        }
        return $mapa;
    }

    public function listarAbertosParaObreiro(string $obreiroId, bool $incluirSemElegibilidade = false): array
    {
        $uuidValido = $this->isUuid($obreiroId);
        if (!$uuidValido && !$incluirSemElegibilidade) {
            return [];
        }

        $sql = "
            SELECT
                b.id,
                b.numero_balaustre,
                b.status,
                b.updated_at,
                s.titulo AS sessao_titulo,
                s.tipo_sessao,
                s.grau_sessao,
                s.data_hora_inicio,
                v.id AS votacao_id,
                CASE
                    WHEN bv.obreiro_id IS NULL THEN FALSE
                    ELSE COALESCE(bv.elegivel, FALSE)
                END AS elegivel
            FROM balaustres b
            JOIN sessoes s ON s.id = b.sessao_id
            JOIN balaustre_votacoes v ON v.balaustre_id = b.id AND v.status = 'aberta'
            LEFT JOIN balaustre_votantes bv ON bv.votacao_id = v.id
            " . ($uuidValido ? "AND bv.obreiro_id = :obreiro_id" : "AND 1 = 0") . "
            WHERE b.status = 'em_votacao'
              AND s.loja_id = :loja_id
        ";

        if (!$incluirSemElegibilidade) {
            $sql .= " AND COALESCE(bv.elegivel, FALSE) = TRUE";
        }

        $sql .= " ORDER BY s.data_hora_inicio DESC, b.id DESC";

        $stmt = $this->db->prepare($sql);
        $params = ['loja_id' => $this->obterLojaAtualId()];
        if ($uuidValido) {
            $params['obreiro_id'] = $obreiroId;
        }
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function registrarVotoPorBalaustre(int $balaustreId, string $obreiroId, string $voto, ?string $justificativa = null): array
    {
        $voto = trim($voto);
        if (!in_array($voto, ['aprovar', 'pedir_correcao', 'rejeitar'], true)) {
            return ['ok' => false, 'erro' => 'Voto inválido.'];
        }

        $votacao = $this->buscarVotacaoAbertaPorBalaustre($balaustreId);
        if (!$votacao) {
            return ['ok' => false, 'erro' => 'Não existe votação aberta para este balaustre.'];
        }

        $elegivelStmt = $this->db->prepare("
            SELECT elegivel
            FROM balaustre_votantes
            WHERE votacao_id = :votacao_id
              AND obreiro_id = :obreiro_id
            LIMIT 1
        ");
        $elegivelStmt->execute([
            'votacao_id' => (int) $votacao['id'],
            'obreiro_id' => $obreiroId,
        ]);
        $elegivel = $elegivelStmt->fetch(PDO::FETCH_ASSOC);
        if (!$elegivel || !(bool) ($elegivel['elegivel'] ?? false)) {
            return ['ok' => false, 'erro' => 'Somente presentes aptos podem votar.'];
        }

        $stmt = $this->db->prepare("
            INSERT INTO balaustre_votos (
                votacao_id,
                obreiro_id,
                voto,
                justificativa,
                votado_em
            ) VALUES (
                :votacao_id,
                :obreiro_id,
                :voto,
                :justificativa,
                NOW()
            )
            ON CONFLICT (votacao_id, obreiro_id)
            DO UPDATE SET
                voto = EXCLUDED.voto,
                justificativa = EXCLUDED.justificativa,
                votado_em = NOW()
        ");

        $ok = $stmt->execute([
            'votacao_id' => (int) $votacao['id'],
            'obreiro_id' => $obreiroId,
            'voto' => $voto,
            'justificativa' => $justificativa !== null && trim($justificativa) !== '' ? trim($justificativa) : null,
        ]);

        return $ok ? ['ok' => true] : ['ok' => false, 'erro' => 'Falha ao registrar voto.'];
    }

    public function encerrarVotacaoPorBalaustre(int $balaustreId): array
    {
        $votacao = $this->buscarVotacaoAbertaPorBalaustre($balaustreId);
        if (!$votacao) {
            return ['ok' => false, 'erro' => 'Não existe votação aberta para este balaustre.'];
        }

        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) FILTER (WHERE voto = 'aprovar') AS total_aprovar,
                COUNT(*) FILTER (WHERE voto = 'pedir_correcao') AS total_correcao,
                COUNT(*) FILTER (WHERE voto = 'rejeitar') AS total_rejeitar
            FROM balaustre_votos
            WHERE votacao_id = :votacao_id
        ");
        $stmt->execute(['votacao_id' => (int) $votacao['id']]);
        $totais = $stmt->fetch(PDO::FETCH_ASSOC) ?: [
            'total_aprovar' => 0,
            'total_correcao' => 0,
            'total_rejeitar' => 0,
        ];

        $rejeitar = (int) ($totais['total_rejeitar'] ?? 0);
        $correcao = (int) ($totais['total_correcao'] ?? 0);
        $aprovar = (int) ($totais['total_aprovar'] ?? 0);

        if ($rejeitar > 0) {
            $statusBalaustre = 'rejeitado';
        } elseif ($correcao > 0) {
            $statusBalaustre = 'em_correcao';
        } elseif ($aprovar > 0) {
            $statusBalaustre = 'aprovado';
        } else {
            $statusBalaustre = 'em_correcao';
        }

        $this->db->beginTransaction();
        try {
            $this->db->prepare("
                UPDATE balaustre_votacoes
                   SET status = 'encerrada',
                       encerrada_em = NOW()
                 WHERE id = :id
            ")->execute(['id' => (int) $votacao['id']]);

            $this->db->prepare("
                UPDATE balaustres
                   SET status = :status,
                       updated_at = NOW()
                 WHERE id = :id
                   AND EXISTS (
                       SELECT 1
                       FROM balaustres b
                       JOIN sessoes s ON s.id = b.sessao_id
                       WHERE b.id = :id
                         AND s.loja_id = :loja_id
                   )
            ")->execute([
                'status' => $statusBalaustre,
                'id' => $balaustreId,
                'loja_id' => $this->obterLojaAtualId(),
            ]);

            $this->db->commit();
            return ['ok' => true, 'status' => $statusBalaustre];
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['ok' => false, 'erro' => 'Falha ao encerrar votacao: ' . $e->getMessage()];
        }
    }

    public function buscarPorSessao(int $sessaoId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM balaustres
            WHERE sessao_id = :sessao_id
              AND loja_id = :loja_id
            LIMIT 1
        ");
        $stmt->execute([
            'sessao_id' => $sessaoId,
            'loja_id' => $this->obterLojaAtualId(),
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function obterResumoVisitantesPorSessao(int $sessaoId): array
    {
        $balaustre = $this->buscarPorSessao($sessaoId);
        if (!$balaustre) {
            return [];
        }

        $dados = $balaustre['dados_capturados'] ?? null;
        if (is_string($dados)) {
            $decoded = json_decode($dados, true);
            $dados = is_array($decoded) ? $decoded : null;
        }
        if (!is_array($dados)) {
            return [];
        }

        $visitantes = $dados['palavra_bem_ordem']['visitantes'] ?? [];
        if (!is_array($visitantes)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static function ($item): ?array {
                if (!is_array($item)) {
                    return null;
                }
                $nome = trim((string) ($item['nome'] ?? ''));
                $loja = trim((string) ($item['loja'] ?? ''));
                $oriente = trim((string) ($item['oriente'] ?? ''));
                $potencia = trim((string) ($item['potencia'] ?? ''));
                $grau = trim((string) ($item['grau'] ?? ''));
                if ($nome === '' && $loja === '') {
                    return null;
                }

                return [
                    'nome' => $nome,
                    'loja' => $loja,
                    'oriente' => $oriente,
                    'potencia' => $potencia,
                    'grau' => $grau,
                    'linha_resumida' => trim($nome
                        . ($grau !== '' ? ' - ' . $grau : '')
                        . ($loja !== '' ? ' - ' . $loja : '')
                        . ($oriente !== '' ? ' - ' . $oriente : '')
                        . ($potencia !== '' ? ' - ' . $potencia : '')),
                ];
            },
            $visitantes
        )));
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT
                b.*,
                s.titulo AS sessao_titulo,
                s.data_hora_inicio,
                s.tipo_sessao,
                s.grau_sessao
            FROM balaustres b
            LEFT JOIN sessoes s ON s.id = b.sessao_id
            WHERE b.id = :id
              AND (s.loja_id = :loja_id OR s.loja_id IS NULL)
            LIMIT 1
        ");
        $stmt->execute([
            'id' => $id,
            'loja_id' => $this->obterLojaAtualId(),
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function buscarVotacaoAbertaPorBalaustre(int $balaustreId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM balaustre_votacoes v
            JOIN balaustres b ON b.id = v.balaustre_id
            JOIN sessoes s ON s.id = b.sessao_id
            WHERE v.balaustre_id = :balaustre_id
              AND s.loja_id = :loja_id
              AND status = 'aberta'
            ORDER BY aberta_em DESC
            LIMIT 1
        ");
        $stmt->execute([
            'balaustre_id' => $balaustreId,
            'loja_id' => $this->obterLojaAtualId(),
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function obterLojaAtualId(): int
    {
        return $this->resolveCurrentStoreId($this->db);
    }
}
