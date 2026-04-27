<?php

namespace App\Core\Http;

use App\Config\Database;
use App\Models\CategoriaFinanceira;
use App\Models\ComprovantePix;
use App\Models\FechamentoMensal;
use App\Models\LancamentoFinanceiro;
use App\Models\MensalidadeStatus;
use App\Models\ObrigacaoFinanceira;
use App\Models\Obreiro;
use App\Models\RegularidadeObreiro;
use PDO;

class TesourariaApiRoutes
{
    public static function dispatch(
        string $requestUri,
        string $method,
        array $session,
        callable $requireTesourariaApiAccess
    ): bool {
        if (!str_starts_with($requestUri, '/api/tesouraria')) {
            return false;
        }

        header('Content-Type: application/json; charset=utf-8');
        $requireTesourariaApiAccess();

        $usuarioId = $session['usuario_id'] ?? 0;

        if ($requestUri === '/api/tesouraria/categorias' && $method === 'GET') {
            $tipo = trim((string) ($_GET['tipo'] ?? ''));
            $categoriaModel = new CategoriaFinanceira();
            $categorias = $tipo !== ''
                ? $categoriaModel->obterPorTipo($tipo)
                : $categoriaModel->obterTodas();
            JsonResponse::send(['ok' => true, 'categorias' => $categorias]);
        }

        if ($requestUri === '/api/tesouraria/caixa' && $method === 'GET') {
            $mes = (int) ($_GET['mes'] ?? date('n'));
            $ano = (int) ($_GET['ano'] ?? date('Y'));
            $lancModel = new LancamentoFinanceiro();
            $lancamentos = $lancModel->obterPorMes($mes, $ano);
            $totais = $lancModel->obterTotaisMes($mes, $ano);
            $porCategoria = $lancModel->obterPorCategoriaMes($mes, $ano);

            JsonResponse::send([
                'ok' => true,
                'lancamentos' => $lancamentos,
                'totais' => $totais,
                'categorias' => $porCategoria,
            ]);
        }

        if ($requestUri === '/api/tesouraria/lancamento/criar' && $method === 'POST') {
            $body = RequestBody::json();
            $lancModel = new LancamentoFinanceiro();
            $ok = $lancModel->criar([
                'tipo' => $body['tipo'] ?? 'entrada',
                'categoria_id' => (int) ($body['categoria_id'] ?? 0),
                'valor' => (float) ($body['valor'] ?? 0),
                'data_lancamento' => $body['data_lancamento'] ?? date('Y-m-d'),
                'descricao' => trim((string) ($body['descricao'] ?? '')) ?: null,
                'obreiro_id' => trim((string) ($body['obreiro_id'] ?? '')) ?: null,
                'mes_ref' => (int) ($body['mes_ref'] ?? date('n')),
                'ano_ref' => (int) ($body['ano_ref'] ?? date('Y')),
                'created_by' => $usuarioId,
            ]);

            JsonResponse::send(['ok' => $ok]);
        }

        if (preg_match('~^/api/tesouraria/lancamento/(\d+)$~', $requestUri, $m) && $method === 'DELETE') {
            $lancModel = new LancamentoFinanceiro();
            $ok = $lancModel->deletar((int) $m[1]);
            JsonResponse::send(['ok' => $ok]);
        }

        if ($requestUri === '/api/tesouraria/comprovantes' && $method === 'GET') {
            $status = $_GET['status'] ?? null;
            $status = in_array($status, ['pendente', 'aprovado', 'rejeitado'], true) ? $status : null;
            $comproModel = new ComprovantePix();
            $comprovantes = $comproModel->obterTodos($status);
            JsonResponse::send(['ok' => true, 'comprovantes' => $comprovantes]);
        }

        if (preg_match('~^/api/tesouraria/comprovantes/(\d+)$~', $requestUri, $m) && $method === 'GET') {
            $comproModel = new ComprovantePix();
            $comprovante = $comproModel->obterPorId((int) $m[1]);
            JsonResponse::send(['ok' => $comprovante !== null, 'comprovante' => $comprovante]);
        }

        if ($requestUri === '/api/tesouraria/comprovantes/aprovar' && $method === 'POST') {
            $body = RequestBody::json();
            $comproModel = new ComprovantePix();
            $lancModel = new LancamentoFinanceiro();
            $obrigacaoModel = new ObrigacaoFinanceira();

            $comprovante = $comproModel->obterPorId((int) ($body['id'] ?? 0));
            if (!$comprovante) {
                JsonResponse::send(['ok' => false]);
            }

            $validacao = [
                'valor' => (float) ($body['valor'] ?? 0),
                'mes' => (int) ($body['mes'] ?? date('n')),
                'ano' => (int) ($body['ano'] ?? date('Y')),
                'rotulo_pagamento' => trim((string) ($body['rotulo_pagamento'] ?? '')) ?: null,
                'categoria_id' => (int) ($body['categoria_id'] ?? 0) ?: null,
                'obrigacao_parcela_id' => (int) ($body['obrigacao_parcela_id'] ?? 0) ?: null,
                'validado_por' => $usuarioId,
            ];
            $comproModel->aprovar((int) ($body['id'] ?? 0), $validacao);

            if (!empty($validacao['obrigacao_parcela_id'])) {
                $obrigacaoModel->quitarParcela((int) $validacao['obrigacao_parcela_id'], [
                    'valor_pago' => $validacao['valor'],
                    'pago_em' => date('Y-m-d'),
                    'categoria_id' => $validacao['categoria_id'],
                    'descricao' => $validacao['rotulo_pagamento'] ?: ('Comprovante PIX #' . (int) $body['id']),
                    'observacao' => 'Baixa via comprovante PIX validado.',
                ], $usuarioId);
            } else {
                $lancModel->criar([
                    'tipo' => 'entrada',
                    'categoria_id' => $validacao['categoria_id'] ?: 1,
                    'valor' => $validacao['valor'],
                    'data_lancamento' => date('Y-m-d'),
                    'descricao' => $validacao['rotulo_pagamento'] ?: 'Comprovante PIX validado',
                    'obreiro_id' => $comprovante['obreiro_id'],
                    'mes_ref' => $validacao['mes'],
                    'ano_ref' => $validacao['ano'],
                    'created_by' => $usuarioId,
                ]);
            }

            if ($comprovante['obreiro_id'] && (($validacao['categoria_id'] ?? null) === null || (int) $validacao['categoria_id'] === 1)) {
                $mensModel = new MensalidadeStatus();
                $mensModel->registrar($comprovante['obreiro_id'], $validacao['mes'], $validacao['ano'], 'pago');
            }

            JsonResponse::send(['ok' => true]);
        }

        if ($requestUri === '/api/tesouraria/obrigacoes-abertas' && $method === 'GET') {
            $obreiroId = trim((string) ($_GET['obreiro_id'] ?? ''));
            if ($obreiroId === '') {
                JsonResponse::send(['ok' => true, 'parcelas' => []]);
            }
            $parcelas = (new ObrigacaoFinanceira())->listarParcelasEmAbertoObreiro($obreiroId);
            JsonResponse::send(['ok' => true, 'parcelas' => $parcelas]);
        }

        if ($requestUri === '/api/tesouraria/comprovantes/rejeitar' && $method === 'POST') {
            $body = RequestBody::json();
            $comproModel = new ComprovantePix();
            $ok = $comproModel->rejeitar((int) ($body['id'] ?? 0), $body['motivo'] ?? '', $usuarioId);
            JsonResponse::send(['ok' => $ok]);
        }

        if (preg_match('~^/api/tesouraria/comprovantes/(\d+)/cancelar$~', $requestUri, $m) && $method === 'POST') {
            $body = RequestBody::json();
            $id = (int) $m[1];
            $motivo = trim((string) ($body['motivo'] ?? ''));
            if ($motivo === '') {
                JsonResponse::send(['ok' => false, 'erro' => 'Motivo do cancelamento é obrigatório']);
                return true;
            }

            $comproModel = new ComprovantePix();
            $comprovante = $comproModel->obterPorId($id);
            if (!$comprovante) {
                JsonResponse::send(['ok' => false, 'erro' => 'Comprovante não encontrado']);
                return true;
            }

            $db = Database::getConnection();
            try {
                $db->beginTransaction();

                // Se houver lançamento vinculado, deletar
                if (!empty($comprovante['lancamento_id'])) {
                    $lancModel = new LancamentoFinanceiro();
                    $lancModel->deletar((int) $comprovante['lancamento_id']);
                }

                // Se houver parcela vinculada, reverter status para pendente
                if (!empty($comprovante['obrigacao_parcela_id'])) {
                    $sql = "UPDATE obrigacao_financeira_parcelas SET status = 'pendente', pago_em = NULL, lancamento_id = NULL, quitado_por = NULL, quitado_em = NULL WHERE id = ?";
                    $stmt = $db->prepare($sql);
                    $stmt->execute([(int) $comprovante['obrigacao_parcela_id']]);
                }

                // Marcar comprovante como cancelado
                $cancelSql = "UPDATE comprovantes_pix SET status = 'cancelado', cancelado_por = ?, cancelado_em = CURRENT_TIMESTAMP, motivo_cancelamento = ? WHERE id = ?";
                $cancelStmt = $db->prepare($cancelSql);
                $cancelStmt->execute([$usuarioId, $motivo, $id]);

                $db->commit();
                JsonResponse::send(['ok' => true]);
            } catch (\Throwable $e) {
                $db->rollBack();
                error_log('[tesouraria] Erro ao cancelar comprovante: ' . $e->getMessage());
                JsonResponse::send(['ok' => false, 'erro' => 'Falha ao cancelar comprovante. Operação revertida.']);
            }
        }

        if ($requestUri === '/api/tesouraria/regularidade' && $method === 'GET') {
            $mes = (int) ($_GET['mes'] ?? date('n'));
            $ano = (int) ($_GET['ano'] ?? date('Y'));
            $regModel = new RegularidadeObreiro();
            $regularidade = $regModel->obterPorMes($mes, $ano);
            JsonResponse::send(['ok' => true, 'regularidade' => $regularidade]);
        }

        if ($requestUri === '/api/tesouraria/regularidade/definir' && $method === 'POST') {
            $body = RequestBody::json();
            $regModel = new RegularidadeObreiro();
            $ok = $regModel->definir(
                (string) ($body['obreiro_id'] ?? ''),
                (int) ($body['mes'] ?? 0),
                (int) ($body['ano'] ?? 0),
                $body['status'] ?? 'irregular',
                $body['observacao'] ?? null,
                $usuarioId
            );
            JsonResponse::send(['ok' => $ok]);
        }

        if ($requestUri === '/api/tesouraria/regularidade/definir-todos' && $method === 'POST') {
            $body = RequestBody::json();
            $obreiroModel = new Obreiro();
            $regModel = new RegularidadeObreiro();

            foreach ($obreiroModel->getAllAtivos() as $ob) {
                $regModel->definir($ob['id'], (int) ($body['mes'] ?? 0), (int) ($body['ano'] ?? 0), $body['status'] ?? 'regular', null, $usuarioId);
            }

            JsonResponse::send(['ok' => true]);
        }

        if ($requestUri === '/api/tesouraria/fechamento' && $method === 'GET') {
            $mes = (int) ($_GET['mes'] ?? date('n'));
            $ano = (int) ($_GET['ano'] ?? date('Y'));
            $fechModel = new FechamentoMensal();

            $fechamento = $fechModel->obter($mes, $ano);
            if (!$fechamento) {
                $mesPrev = $mes - 1;
                $anoPrev = $ano;
                if ($mesPrev < 1) {
                    $mesPrev = 12;
                    $anoPrev--;
                }

                $fechPrev = $fechModel->obter($mesPrev, $anoPrev);
                $saldoSugerido = $fechPrev ? (float) $fechPrev['saldo_final'] : 0;

                $fechModel->criar($mes, $ano, $saldoSugerido);
                $fechamento = $fechModel->obter($mes, $ano);
            }

            $fechModel->recalcularTotais($mes, $ano);
            $fechamento = $fechModel->obter($mes, $ano);

            JsonResponse::send(['ok' => true, 'fechamento' => $fechamento]);
        }

        if ($requestUri === '/api/tesouraria/fechamento/saldo-inicial' && $method === 'POST') {
            $body = RequestBody::json();
            $fechModel = new FechamentoMensal();
            $ok = $fechModel->atualizarSaldoInicial((int) ($body['fechamento_id'] ?? 0), (float) ($body['novo_saldo'] ?? 0), $body['justificativa'] ?? '', $usuarioId);
            JsonResponse::send(['ok' => $ok]);
        }

        if ($requestUri === '/api/tesouraria/fechamento/atualizar-saldo' && $method === 'POST') {
            // DEPRECATED: Use /saldo-inicial instead
            $body = RequestBody::json();
            $fechModel = new FechamentoMensal();
            $ok = $fechModel->atualizarSaldoInicial((int) ($body['fechamento_id'] ?? 0), (float) ($body['novo_saldo'] ?? 0), $body['justificativa'] ?? '', $usuarioId);
            JsonResponse::send(['ok' => $ok]);
        }

        if (preg_match('~^/api/tesouraria/fechamento/(\d+)/lancamentos$~', $requestUri, $m) && $method === 'GET') {
            $fechModel = new FechamentoMensal();
            $fechamento = $fechModel->obterPorId((int) $m[1]);
            if (!$fechamento) {
                JsonResponse::send(['ok' => false]);
            }

            $lancModel = new LancamentoFinanceiro();
            $lancamentos = $lancModel->obterPorMes($fechamento['mes_ref'], $fechamento['ano_ref']);
            JsonResponse::send(['ok' => true, 'lancamentos' => $lancamentos]);
        }

        if (preg_match('~^/api/tesouraria/fechamento/(\d+)/auditoria$~', $requestUri, $m) && $method === 'GET') {
            $fechModel = new FechamentoMensal();
            $fechamento = $fechModel->obterComAuditoria((int) $m[1]);
            if (!$fechamento) {
                JsonResponse::send(['ok' => false]);
            }
            JsonResponse::send(['ok' => true, 'auditoria' => $fechamento['auditoria']]);
        }

        if ($requestUri === '/api/tesouraria/fechamento/fechar' && $method === 'POST') {
            $body = RequestBody::json();
            $fechModel = new FechamentoMensal();

            $mes = (int) ($body['mes'] ?? 0);
            $ano = (int) ($body['ano'] ?? 0);
            $fechamentoId = (int) ($body['fechamento_id'] ?? 0);

            if (($mes <= 0 || $ano <= 0) && $fechamentoId > 0) {
                $fechamento = $fechModel->obterPorId($fechamentoId);
                if ($fechamento) {
                    $mes = (int) $fechamento['mes_ref'];
                    $ano = (int) $fechamento['ano_ref'];
                }
            }

            $ok = ($mes > 0 && $ano > 0) ? $fechModel->fechar($mes, $ano, $usuarioId) : false;
            JsonResponse::send(['ok' => $ok]);
        }

        JsonResponse::error('API nao encontrada.', 404);
    }
}
