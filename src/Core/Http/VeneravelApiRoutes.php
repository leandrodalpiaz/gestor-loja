<?php
declare(strict_types=1);

namespace App\Core\Http;

use App\Models\Balaustre;
use App\Models\Cargo;
use App\Models\ConviteExterno;
use App\Models\Obreiro;
use App\Models\Sessao;
use App\Models\TesourariaExecutive;
use App\Models\OcorrenciaAssistencial;
use App\Models\VidaLojaSinal;
use App\Models\VidaLojaAcompanhamento;

final class VeneravelApiRoutes
{
    public static function dispatch(
        string $requestUri,
        string $method,
        array $session,
        callable $requireVeneravelApiAccess
    ): bool {
        if (!str_starts_with($requestUri, '/api/veneravel')) {
            return false;
        }

        header('Content-Type: application/json; charset=utf-8');
        $requireVeneravelApiAccess();
        $session = $_SESSION;
        $autorId = trim((string) ($session['usuario_id'] ?? '')) ?: null;

        // --- GET /api/veneravel/dashboard ---
        if ($requestUri === '/api/veneravel/dashboard' && $method === 'GET') {
            $sessaoModel = new Sessao();
            $balaustreModel = new Balaustre();
            $cargoModel = new Cargo();
            $obreiroModel = new Obreiro();
            $conviteModel = new ConviteExterno();
            $tesourariaExec = new TesourariaExecutive();

            $proximaSessao = $sessaoModel->obterProximaSessao();
            $sessoes = $sessaoModel->listarFuturas(8);
            $sessaoSelecionadaId = (int) ($_GET['sessao_id'] ?? 0);
            $sessaoEmFoco = null;
            $balaustresRecentes = $balaustreModel->listarRecentes(20);
            $nominata = $cargoModel->listarResumoCargos();
            $resumoCadastros = $obreiroModel->obterResumoSecretaria();
            $obreirosComPendencia = array_values(array_filter(
                $obreiroModel->listarParaSecretaria(['ordenacao' => 'alerta']),
                static fn (array $item): bool => !empty($item['alertas_cadastro'])
            ));

            if ($sessaoSelecionadaId > 0) {
                $sessaoEmFoco = $sessaoModel->findById($sessaoSelecionadaId);
            }
            if (!$sessaoEmFoco && $proximaSessao && !empty($proximaSessao['id'])) {
                $sessaoEmFoco = $sessaoModel->findById((int) $proximaSessao['id']);
            }

            $tz = new \DateTimeZone('America/Sao_Paulo');
            $mesSelecionado = (int) ($_GET['mes'] ?? date('n'));
            $anoSelecionado = (int) ($_GET['ano'] ?? date('Y'));
            $mesSelecionado = max(1, min(12, $mesSelecionado));
            $anoSelecionado = max(2000, min(2100, $anoSelecionado));
            $inicioMes = new \DateTimeImmutable(sprintf('%04d-%02d-01', $anoSelecionado, $mesSelecionado), $tz);
            $fimMes = $inicioMes->modify('last day of this month');

            $sessoesDoMes = $sessaoModel->listarPorPeriodo($inicioMes->format('Y-m-d'), $fimMes->format('Y-m-d'));
            $convitesDoMes = $conviteModel->listarPorPeriodo($inicioMes->format('Y-m-d'), $fimMes->format('Y-m-d'));
            
            $tesourariaResumo = [];
            $tesourariaSerie = [];
            $tesourariaSomatorios = [];
            try {
                $tesourariaResumo = $tesourariaExec->resumoMes($mesSelecionado, $anoSelecionado);
                $tesourariaSerie = $tesourariaExec->serieComparativaTresAnos($anoSelecionado);
                $tesourariaSomatorios = $tesourariaExec->somatoriosAnoPorGrupo($anoSelecionado);
            } catch (\Throwable $e) {
                error_log('Falha ao obter dados financeiros no dashboard Veneravel: ' . $e->getMessage());
            }

            $balaustresAptos = array_values(array_filter(
                $balaustresRecentes,
                static fn (array $item): bool => (string) ($item['status'] ?? '') === 'apto_votacao'
            ));
            $balaustresEmVotacao = array_values(array_filter(
                $balaustresRecentes,
                static fn (array $item): bool => (string) ($item['status'] ?? '') === 'em_votacao'
            ));
            $balaustresPendentesDecisao = array_values(array_filter(
                $balaustresRecentes,
                static fn (array $item): bool => in_array((string) ($item['status'] ?? ''), ['apto_votacao', 'em_votacao'], true)
            ));

            $codigosNominataPrincipal = [
                'VENERAVEL', 'PRIMEIRO_VIGILANTE', 'SEGUNDO_VIGILANTE', 'ORADOR',
                'SECRETARIO', 'TESOUREIRO', 'CHANCELER', 'MESTRE_BANQUETES',
                'GUARDA_DA_LEI', 'ARQUITETO', 'MESTRE_DE_HARMONIA', 'HOSPITALEIRO'
            ];
            $nominataPrincipal = array_values(array_filter(
                $nominata,
                static fn (array $item): bool => in_array((string) ($item['codigo'] ?? ''), $codigosNominataPrincipal, true)
            ));
            $cargosCriticosPendentes = array_values(array_filter(
                $nominataPrincipal,
                static fn (array $item): bool => trim((string) ($item['titular_nome'] ?? '')) === ''
            ));
            $obreirosPendentesCriticos = array_map(function (array $item): array {
                return [
                    'nome' => trim((string) ($item['nome_historico'] ?? '')) !== '' ? (string) $item['nome_historico'] : (string) ($item['nome'] ?? 'Obreiro'),
                    'cim' => (string) ($item['cim'] ?? ''),
                    'alertas' => array_values($item['alertas_cadastro'] ?? []),
                ];
            }, array_slice($obreirosComPendencia, 0, 8));

            $obreirosAtrasoFraterno = [];
            try {
                $stmt = $obreiroModel->getDb()->prepare("
                    SELECT o.id, o.nome, o.nome_historico, o.cim
                    FROM obreiros o
                    WHERE o.ativo = TRUE
                      AND EXISTS (
                          SELECT 1 FROM obreiro_mensalidades m
                          WHERE m.obreiro_id = o.id
                            AND m.pago = FALSE
                            AND m.vencimento < NOW()
                      )
                    LIMIT 20
                ");
                $stmt->execute();
                $obreirosAtrasoFraterno = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            } catch (\Throwable $e) {
                error_log('Erro ao buscar atrasos fraternos: ' . $e->getMessage());
            }

            $auxiliosPendentes = array_values(array_filter(
                (new OcorrenciaAssistencial())->listarRecentes(150),
                static fn (array $item): bool => 
                    !empty($item['necessita_apoio_financeiro']) 
                    && (string) ($item['status'] ?? '') === 'aberta'
            ));

            $authorizer = new \App\Core\Authorization\Authorizer(new \App\Core\Auth\CurrentUser($session), new \App\Core\Authorization\PermissionMap());
            $podeVerSigilosos = $authorizer->hasPermission('vida_loja.sigilo.view');

            (new \App\Services\VidaLojaService())->sincronizarSinaisAutomáticos();
            $sinais = (new VidaLojaSinal())->buscarAbertosPorLoja();
            $acompanhamentos = (new VidaLojaAcompanhamento())->listarPorLoja($podeVerSigilosos, $autorId, 100);
            $obreiros = $obreiroModel->getAllAtivos();

            JsonResponse::send([
                'ok' => true,
                'mes' => $mesSelecionado,
                'ano' => $anoSelecionado,
                'sessoes_mes' => $sessoesDoMes,
                'convites_mes' => $convitesDoMes,
                'tesouraria_resumo' => $tesourariaResumo,
                'tesouraria_serie' => $tesourariaSerie,
                'tesouraria_somatorios' => $tesourariaSomatorios,
                'balaustres_aptos' => $balaustresAptos,
                'balaustres_em_votacao' => $balaustresEmVotacao,
                'balaustres_pendentes_decisao' => $balaustresPendentesDecisao,
                'obreiros_atraso_fraterno' => $obreirosAtrasoFraterno,
                'cargos_criticos_pendentes' => $cargosCriticosPendentes,
                'obreiros_pendentes_criticos' => $obreirosPendentesCriticos,
                'auxilios_pendentes' => $auxiliosPendentes,
                'sinais' => $sinais,
                'acompanhamentos' => $acompanhamentos,
                'obreiros' => $obreiros,
                'podeVerSigilosos' => $podeVerSigilosos,
            ]);
            return true;
        }

        // --- POST /api/veneravel/assistencia/decidir ---
        if ($requestUri === '/api/veneravel/assistencia/decidir' && $method === 'POST') {
            $body = RequestBody::json();
            $id = (int) ($body['ocorrencia_id'] ?? 0);
            $acao = trim((string) ($body['acao'] ?? ''));
            $valorStr = trim((string) ($body['valor_aprovado'] ?? '0'));
            $justificativa = trim((string) ($body['justificativa'] ?? ''));

            if ($id <= 0 || !in_array($acao, ['aprovar', 'recusar'], true)) {
                JsonResponse::send(['ok' => false, 'erro' => 'Dados inválidos para decisão.']);
                return true;
            }

            $valorAprovado = 0.0;
            $status = 'cancelada';
            if ($acao === 'aprovar') {
                $valorStr = str_replace([' ', 'R$', 'r$'], '', $valorStr);
                if (str_contains($valorStr, ',') && str_contains($valorStr, '.')) {
                    $valorStr = str_replace('.', '', $valorStr);
                    $valorStr = str_replace(',', '.', $valorStr);
                } elseif (str_contains($valorStr, ',')) {
                    $valorStr = str_replace(',', '.', $valorStr);
                }
                $valorAprovado = is_numeric($valorStr) ? (float) $valorStr : 0.0;
                if ($valorAprovado <= 0) {
                    JsonResponse::send(['ok' => false, 'erro' => 'Para aprovar, informe um valor maior que zero.']);
                    return true;
                }
                $status = 'em_acompanhamento';
            }

            $model = new OcorrenciaAssistencial();
            $ok = $model->decidirApoio($id, $status, $valorAprovado, $justificativa !== '' ? $justificativa : null, $autorId);

            JsonResponse::send([
                'ok' => $ok,
                'erro' => $ok ? null : 'Não foi possível gravar a decisão.'
            ]);
            return true;
        }

        // --- POST /api/veneravel/sessoes/action ---
        if ($requestUri === '/api/veneravel/sessoes/action' && $method === 'POST') {
            $body = RequestBody::json();
            $sessaoId = (int) ($body['sessao_id'] ?? 0);
            $action = trim((string) ($body['action'] ?? ''));

            if ($sessaoId <= 0 || !in_array($action, ['publicar', 'cancelar', 'reabrir', 'realizar'], true)) {
                JsonResponse::send(['ok' => false, 'erro' => 'Sessão ou ação inválida.']);
                return true;
            }

            $sessaoModel = new Sessao();
            $ok = false;
            $erro = null;

            switch ($action) {
                case 'cancelar':
                    $ok = $sessaoModel->cancelar($sessaoId, $autorId, 'Cancelamento via painel Angular');
                    break;
                case 'reabrir':
                    $ok = $sessaoModel->reabrir($sessaoId, $autorId, 'Reabertura via painel Angular');
                    break;
                case 'publicar':
                    $ok = $sessaoModel->marcarPublicada($sessaoId, $autorId, 'Publicada via painel Angular');
                    break;
                case 'realizar':
                    $ok = $sessaoModel->marcarRealizada($sessaoId, $autorId, 'Realizada via painel Angular');
                    break;
            }

            JsonResponse::send([
                'ok' => $ok,
                'erro' => $ok ? null : 'Não foi possível executar a ação na sessão.'
            ]);
            return true;
        }

        // --- POST /api/veneravel/balaustre/action ---
        if ($requestUri === '/api/veneravel/balaustre/action' && $method === 'POST') {
            $body = RequestBody::json();
            $balaustreId = (int) ($body['balaustre_id'] ?? 0);
            $action = trim((string) ($body['action'] ?? ''));

            if ($balaustreId <= 0 || !in_array($action, ['abrir-votacao', 'encerrar-votacao'], true)) {
                JsonResponse::send(['ok' => false, 'erro' => 'Balaústre ou ação inválida.']);
                return true;
            }

            $balaustreModel = new Balaustre();
            $ok = false;
            $erro = null;

            if ($action === 'abrir-votacao') {
                $res = $balaustreModel->abrirVotacao($balaustreId, $autorId);
                $ok = !empty($res['ok']);
                $erro = $res['erro'] ?? null;
            } elseif ($action === 'encerrar-votacao') {
                $res = $balaustreModel->encerrarVotacaoPorBalaustre($balaustreId);
                $ok = !empty($res['ok']);
                $erro = $res['erro'] ?? null;
            }

            JsonResponse::send([
                'ok' => $ok,
                'erro' => $ok ? null : ($erro ?: 'Falha ao executar ação no balaústre.')
            ]);
            return true;
        }

        // --- POST /api/veneravel/contato/salvar ---
        if ($requestUri === '/api/veneravel/contato/salvar' && $method === 'POST') {
            $body = RequestBody::json();
            $obreiroId = trim((string) ($body['obreiro_id'] ?? ''));
            $resultado = trim((string) ($body['resultado'] ?? ''));

            if ($obreiroId === '' || $resultado === '') {
                JsonResponse::send(['ok' => false, 'erro' => 'Selecione o irmão e preencha o resultado do contato.']);
                return true;
            }

            $payload = [
                'sinal_id' => !empty($body['sinal_id']) ? (int) $body['sinal_id'] : null,
                'obreiro_id' => $obreiroId,
                'data_contato' => trim((string) ($body['data_contato'] ?? date('Y-m-d'))),
                'meio_contato' => trim((string) ($body['meio_contato'] ?? 'whatsapp')),
                'resultado' => $resultado,
                'proximo_acompanhamento' => !empty($body['proximo_acompanhamento']) ? trim((string) $body['proximo_acompanhamento']) : null,
                'nivel_sigilo' => trim((string) ($body['nivel_sigilo'] ?? 'reservado')),
                'observacoes_sigilosas' => !empty($body['observacoes_sigilosas']) ? trim((string) $body['observacoes_sigilosas']) : null,
                'responsavel_id' => $autorId,
            ];

            $model = new VidaLojaAcompanhamento();
            $ok = $model->criar($payload);

            if ($ok && !empty($payload['sinal_id'])) {
                $sinalModel = new VidaLojaSinal();
                $sinal = $sinalModel->buscarPorId($payload['sinal_id']);
                if ($sinal && $sinal['status'] === 'aberto') {
                    $sinalModel->atualizarStatus($sinal['id'], 'em_observacao');
                }
            }

            JsonResponse::send(['ok' => $ok, 'erro' => $ok ? null : 'Não foi possível registrar o contato fraterno.']);
            return true;
        }

        // --- POST /api/veneravel/contato/excluir ---
        if ($requestUri === '/api/veneravel/contato/excluir' && $method === 'POST') {
            $body = RequestBody::json();
            $id = (int) ($body['id'] ?? 0);
            if ($id <= 0) {
                JsonResponse::send(['ok' => false, 'erro' => 'ID de contato inválido.']);
                return true;
            }
            $model = new VidaLojaAcompanhamento();
            $ok = $model->excluir($id);
            JsonResponse::send(['ok' => $ok, 'erro' => $ok ? null : 'Não foi possível excluir o contato.']);
            return true;
        }

        // --- POST /api/veneravel/sinais/acao ---
        if ($requestUri === '/api/veneravel/sinais/acao' && $method === 'POST') {
            $body = RequestBody::json();
            $id = (int) ($body['sinal_id'] ?? 0);
            $status = trim((string) ($body['status'] ?? ''));

            if ($id <= 0 || !in_array($status, ['aberto', 'em_observacao', 'arquivado', 'resolvido'], true)) {
                JsonResponse::send(['ok' => false, 'erro' => 'Parâmetros inválidos para sinal.']);
                return true;
            }

            $model = new VidaLojaSinal();
            $ok = $model->atualizarStatus($id, $status);
            JsonResponse::send(['ok' => $ok, 'erro' => $ok ? null : 'Não foi possível atualizar o status do sinal.']);
            return true;
        }

        return false;
    }
}
