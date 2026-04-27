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
            return true;
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
            return true;
        }

        if ($requestUri === '/api/tesouraria/lancamento/criar' && $method === 'POST') {
            $body = RequestBody::json();
            $tipo = trim((string) ($body['tipo'] ?? 'entrada'));
            $valor = (float) ($body['valor'] ?? 0);
            $mes = (int) ($body['mes_ref'] ?? date('n'));
            $ano = (int) ($body['ano_ref'] ?? date('Y'));

            // Validações
            if (!in_array($tipo, ['entrada', 'saida'], true)) {
                JsonResponse::send(['ok' => false, 'erro' => 'Tipo deve ser "entrada" ou "saida"']);
                return true;
            }

            if ($valor <= 0) {
                JsonResponse::send(['ok' => false, 'erro' => 'Valor deve ser maior que zero']);
                return true;
            }

            if ($mes < 1 || $mes > 12) {
                JsonResponse::send(['ok' => false, 'erro' => 'Mês deve estar entre 1 e 12']);
                return true;
            }

            if ($ano < (int) date('Y') - 1) {
                JsonResponse::send(['ok' => false, 'erro' => 'Ano inválido']);
                return true;
            }

            $lancModel = new LancamentoFinanceiro();
            $ok = $lancModel->criar([
                'tipo' => $tipo,
                'categoria_id' => (int) ($body['categoria_id'] ?? 0) ?: null,
                'valor' => $valor,
                'data_lancamento' => $body['data_lancamento'] ?? date('Y-m-d'),
                'descricao' => trim((string) ($body['descricao'] ?? '')) ?: null,
                'obreiro_id' => trim((string) ($body['obreiro_id'] ?? '')) ?: null,
                'mes_ref' => $mes,
                'ano_ref' => $ano,
                'created_by' => $usuarioId,
            ]);

            JsonResponse::send(['ok' => $ok, 'erro' => $ok ? null : 'Falha ao criar lançamento']);
            return true;
        }

        if (preg_match('~^/api/tesouraria/lancamento/(\d+)$~', $requestUri, $m) && $method === 'DELETE') {
            $lancModel = new LancamentoFinanceiro();
            $ok = $lancModel->deletar((int) $m[1]);
            JsonResponse::send(['ok' => $ok]);
            return true;
        }

        if ($requestUri === '/api/tesouraria/comprovantes' && $method === 'GET') {
            $status = $_GET['status'] ?? null;
            $status = in_array($status, ['pendente', 'aprovado', 'rejeitado'], true) ? $status : null;
            $comproModel = new ComprovantePix();
            $comprovantes = $comproModel->obterTodos($status);
            JsonResponse::send(['ok' => true, 'comprovantes' => $comprovantes]);
            return true;
        }

        if (preg_match('~^/api/tesouraria/comprovantes/(\d+)$~', $requestUri, $m) && $method === 'GET') {
            $comproModel = new ComprovantePix();
            $comprovante = $comproModel->obterPorId((int) $m[1]);
            JsonResponse::send(['ok' => $comprovante !== null, 'comprovante' => $comprovante]);
            return true;
        }

        if ($requestUri === '/api/tesouraria/comprovantes/aprovar' && $method === 'POST') {
            $body = RequestBody::json();
            $comproModel = new ComprovantePix();
            $lancModel = new LancamentoFinanceiro();
            $obrigacaoModel = new ObrigacaoFinanceira();
            $db = Database::getConnection();

            $comprovante = $comproModel->obterPorId((int) ($body['id'] ?? 0));
            if (!$comprovante) {
                JsonResponse::send(['ok' => false, 'erro' => 'Comprovante não encontrado']);
                return true;
            }

            // Validações críticas
            $valor = (float) ($body['valor'] ?? 0);
            $mes = (int) ($body['mes'] ?? date('n'));
            $ano = (int) ($body['ano'] ?? date('Y'));
            $categoriaId = (int) ($body['categoria_id'] ?? 0) ?: null;
            $obrigacaoParcelaId = (int) ($body['obrigacao_parcela_id'] ?? 0) ?: null;

            // Validar valor > 0
            if ($valor <= 0) {
                JsonResponse::send(['ok' => false, 'erro' => 'Valor deve ser maior que zero']);
                return true;
            }

            // Validar mês 1-12
            if ($mes < 1 || $mes > 12) {
                JsonResponse::send(['ok' => false, 'erro' => 'Mês deve estar entre 1 e 12']);
                return true;
            }

            // Validar ano >= current year - 1
            if ($ano < (int) date('Y') - 1) {
                JsonResponse::send(['ok' => false, 'erro' => 'Ano inválido']);
                return true;
            }

            // Validar categoria existe (se informada)
            if ($categoriaId !== null) {
                $catStmt = $db->prepare('SELECT id FROM categorias_financeiras WHERE id = ?');
                $catStmt->execute([$categoriaId]);
                if (!$catStmt->fetch(PDO::FETCH_ASSOC)) {
                    JsonResponse::send(['ok' => false, 'erro' => 'Categoria não encontrada']);
                    return true;
                }
            }

            // Validar parcela existe e pertence ao obreiro (se informada)
            if ($obrigacaoParcelaId !== null) {
                $parcStmt = $db->prepare('SELECT p.id, o.obreiro_id FROM obrigacao_financeira_parcelas p JOIN obrigacoes_financeiras o ON p.obrigacao_id = o.id WHERE p.id = ?');
                $parcStmt->execute([$obrigacaoParcelaId]);
                $parcela = $parcStmt->fetch(PDO::FETCH_ASSOC);
                if (!$parcela || $parcela['obreiro_id'] !== $comprovante['obreiro_id']) {
                    JsonResponse::send(['ok' => false, 'erro' => 'Parcela não encontrada ou não pertence ao obreiro']);
                    return true;
                }
            }

            // Envolver em transação
            try {
                $db->beginTransaction();

                $validacao = [
                    'valor' => $valor,
                    'mes' => $mes,
                    'ano' => $ano,
                    'rotulo_pagamento' => trim((string) ($body['rotulo_pagamento'] ?? '')) ?: null,
                    'categoria_id' => $categoriaId,
                    'obrigacao_parcela_id' => $obrigacaoParcelaId,
                    'validado_por' => $usuarioId,
                ];
                $comproModel->aprovar((int) ($body['id'] ?? 0), $validacao);

                if (!empty($obrigacaoParcelaId)) {
                    $obrigacaoModel->quitarParcela($obrigacaoParcelaId, [
                        'valor_pago' => $valor,
                        'pago_em' => date('Y-m-d'),
                        'categoria_id' => $categoriaId,
                        'descricao' => $validacao['rotulo_pagamento'] ?: ('Comprovante PIX #' . (int) $body['id']),
                        'observacao' => 'Baixa via comprovante PIX validado.',
                    ], $usuarioId);
                } else {
                    $lancModel->criar([
                        'tipo' => 'entrada',
                        'categoria_id' => $categoriaId ?: 1,
                        'valor' => $valor,
                        'data_lancamento' => date('Y-m-d'),
                        'descricao' => $validacao['rotulo_pagamento'] ?: 'Comprovante PIX validado',
                        'obreiro_id' => $comprovante['obreiro_id'],
                        'mes_ref' => $mes,
                        'ano_ref' => $ano,
                        'created_by' => $usuarioId,
                    ]);
                }

                if ($comprovante['obreiro_id'] && (($categoriaId ?? null) === null || (int) $categoriaId === 1)) {
                    $mensModel = new MensalidadeStatus();
                    $mensModel->registrar($comprovante['obreiro_id'], $mes, $ano, 'pago');
                }

                $db->commit();
                JsonResponse::send(['ok' => true]);
            } catch (\Throwable $e) {
                $db->rollBack();
                error_log('[tesouraria] Erro ao aprovar comprovante: ' . $e->getMessage());
                JsonResponse::send(['ok' => false, 'erro' => 'Falha ao validar comprovante. Operação revertida.']);
            }
            return true;
        }

        if ($requestUri === '/api/tesouraria/obrigacoes-abertas' && $method === 'GET') {
            $obreiroId = trim((string) ($_GET['obreiro_id'] ?? ''));
            if ($obreiroId === '') {
                JsonResponse::send(['ok' => true, 'parcelas' => []]);
                return true;
            }
            $parcelas = (new ObrigacaoFinanceira())->listarParcelasEmAbertoObreiro($obreiroId);
            JsonResponse::send(['ok' => true, 'parcelas' => $parcelas]);
            return true;
        }

        if ($requestUri === '/api/tesouraria/comprovantes/rejeitar' && $method === 'POST') {
            $body = RequestBody::json();
            $comproModel = new ComprovantePix();
            $ok = $comproModel->rejeitar((int) ($body['id'] ?? 0), $body['motivo'] ?? '', $usuarioId);
            JsonResponse::send(['ok' => $ok]);
            return true;
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

            // Validar retroativo: não deixa cancelar comprovantes com mais de 30 dias
            if (!empty($comprovante['criado_em'])) {
                $diasAtras = (int) ((time() - strtotime($comprovante['criado_em'])) / 86400);
                if ($diasAtras > 30) {
                    JsonResponse::send(['ok' => false, 'erro' => 'Não é possível cancelar operações com mais de 30 dias. Entre em contato com a administração.']);
                    return true;
                }
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

                // Revert MensalidadeStatus: se foi marcado pago e agora cancela, volta para pendente
                // (apenas se não havia parcela vinculada e category_id era null ou 1)
                if (empty($comprovante['obrigacao_parcela_id']) && !empty($comprovante['mes_ref_validado']) && !empty($comprovante['ano_ref_validado'])) {
                    $mensModel = new MensalidadeStatus();
                    $mensModel->registrar(
                        $comprovante['obreiro_id'],
                        (int) $comprovante['mes_ref_validado'],
                        (int) $comprovante['ano_ref_validado'],
                        'pendente'
                    );
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
            return true;
        }

        if ($requestUri === '/api/tesouraria/regularidade' && $method === 'GET') {
            $mes = (int) ($_GET['mes'] ?? date('n'));
            $ano = (int) ($_GET['ano'] ?? date('Y'));
            $regModel = new RegularidadeObreiro();
            $regularidade = $regModel->obterPorMes($mes, $ano);
            JsonResponse::send(['ok' => true, 'regularidade' => $regularidade]);
            return true;
        }

        if ($requestUri === '/api/tesouraria/regularidade/definir' && $method === 'POST') {
            $body = RequestBody::json();
            $status = trim((string) ($body['status'] ?? 'irregular'));
            $mes = (int) ($body['mes'] ?? 0);
            $ano = (int) ($body['ano'] ?? 0);

            // Validar status
            if (!in_array($status, ['regular', 'irregular'], true)) {
                JsonResponse::send(['ok' => false, 'erro' => 'Status deve ser "regular" ou "irregular"']);
                return true;
            }

            // Validar mês/ano
            if ($mes < 1 || $mes > 12 || $ano < (int) date('Y') - 1) {
                JsonResponse::send(['ok' => false, 'erro' => 'Mês/ano inválido']);
                return true;
            }

            $regModel = new RegularidadeObreiro();
            $ok = $regModel->definir(
                (string) ($body['obreiro_id'] ?? ''),
                $mes,
                $ano,
                $status,
                $body['observacao'] ?? null,
                $usuarioId
            );
            JsonResponse::send(['ok' => $ok, 'erro' => $ok ? null : 'Falha ao definir regularidade']);
            return true;
        }

        if ($requestUri === '/api/tesouraria/regularidade/definir-todos' && $method === 'POST') {
            $body = RequestBody::json();
            $obreiroModel = new Obreiro();
            $regModel = new RegularidadeObreiro();

            foreach ($obreiroModel->getAllAtivos() as $ob) {
                $regModel->definir($ob['id'], (int) ($body['mes'] ?? 0), (int) ($body['ano'] ?? 0), $body['status'] ?? 'regular', null, $usuarioId);
            }

            JsonResponse::send(['ok' => true]);
            return true;
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
            return true;
        }

        if ($requestUri === '/api/tesouraria/fechamento/saldo-inicial' && $method === 'POST') {
            $body = RequestBody::json();
            $fechModel = new FechamentoMensal();
            $ok = $fechModel->atualizarSaldoInicial((int) ($body['fechamento_id'] ?? 0), (float) ($body['novo_saldo'] ?? 0), $body['justificativa'] ?? '', $usuarioId);
            JsonResponse::send(['ok' => $ok]);
            return true;
        }

        if ($requestUri === '/api/tesouraria/fechamento/atualizar-saldo' && $method === 'POST') {
            // DEPRECATED: Use /saldo-inicial instead
            $body = RequestBody::json();
            $fechModel = new FechamentoMensal();
            $ok = $fechModel->atualizarSaldoInicial((int) ($body['fechamento_id'] ?? 0), (float) ($body['novo_saldo'] ?? 0), $body['justificativa'] ?? '', $usuarioId);
            JsonResponse::send(['ok' => $ok]);
            return true;
        }

        if (preg_match('~^/api/tesouraria/fechamento/(\d+)/lancamentos$~', $requestUri, $m) && $method === 'GET') {
            $fechModel = new FechamentoMensal();
            $fechamento = $fechModel->obterPorId((int) $m[1]);
            if (!$fechamento) {
                JsonResponse::send(['ok' => false]);
                return true;
            }

            $lancModel = new LancamentoFinanceiro();
            $lancamentos = $lancModel->obterPorMes($fechamento['mes_ref'], $fechamento['ano_ref']);
            JsonResponse::send(['ok' => true, 'lancamentos' => $lancamentos]);
            return true;
        }

        if (preg_match('~^/api/tesouraria/fechamento/(\d+)/auditoria$~', $requestUri, $m) && $method === 'GET') {
            $fechModel = new FechamentoMensal();
            $fechamento = $fechModel->obterComAuditoria((int) $m[1]);
            if (!$fechamento) {
                JsonResponse::send(['ok' => false]);
                return true;
            }
            JsonResponse::send(['ok' => true, 'auditoria' => $fechamento['auditoria']]);
            return true;
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
            return true;
        }

        return false;
    }
}
