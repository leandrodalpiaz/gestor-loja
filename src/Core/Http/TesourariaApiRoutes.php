<?php

namespace App\Core\Http;

use App\Config\Database;
use App\Models\CategoriaFinanceira;
use App\Models\ComprovantePix;
use App\Models\ConfiguracaoLoja;
use App\Models\FechamentoMensal;
use App\Models\Gestao;
use App\Models\LancamentoFinanceiro;
use App\Models\MensalidadeStatus;
use App\Models\ObrigacaoFinanceira;
use App\Models\Obreiro;
use App\Models\Presenca;
use App\Models\RelatorioTesourariaGestao;
use App\Models\RegularidadeObreiro;
use App\Models\Sessao;
use App\Models\TesourariaExecutive;
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

            // Calcula previsões do período
            $obrigModel = new ObrigacaoFinanceira();
            $previsoes = $obrigModel->obterPrevisoesMes($mes, $ano);
            $previsaoEntradas = $previsoes['entrada'] ?? 0.0;
            $previsaoSaidas = $previsoes['saida'] ?? 0.0;

            // Calcula saldo inicial e atual do caixa
            $execModel = new TesourariaExecutive();
            $resumo = $execModel->resumoMes($mes, $ano);
            $saldoInicial = $resumo['saldo_inicial'] ?? 0.0;
            $saldoAtual = $resumo['saldo_atual'] ?? 0.0;
            $saldoPrevisto = $saldoAtual + $previsaoEntradas - $previsaoSaidas;

            JsonResponse::send([
                'ok' => true,
                'lancamentos' => $lancamentos,
                'totais' => $totais,
                'categorias' => $porCategoria,
                'previsao_entradas' => $previsaoEntradas,
                'previsao_saidas' => $previsaoSaidas,
                'saldo_previsto' => $saldoPrevisto,
                'saldo_atual' => $saldoAtual,
                'saldo_inicial' => $saldoInicial,
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

        if ($requestUri === '/api/tesouraria/comprovantes/enviar' && $method === 'POST') {
            $body = RequestBody::json();
            $obreiroId = trim((string) ($body['obreiro_id'] ?? ''));
            $valor = (float) ($body['valor'] ?? 0);
            $mes = (int) ($body['mes_ref'] ?? date('n'));
            $ano = (int) ($body['ano_ref'] ?? date('Y'));
            $descricao = trim((string) ($body['descricao'] ?? ''));
            $comprovanteUrl = trim((string) ($body['comprovante_url'] ?? ''));

            if ($obreiroId === '' || !preg_match('/^[0-9a-fA-F-]{36}$/', $obreiroId)) {
                JsonResponse::send(['ok' => false, 'erro' => 'Obreiro vinculado inválido.']);
                return true;
            }

            if ($valor <= 0) {
                JsonResponse::send(['ok' => false, 'erro' => 'Informe um valor válido maior que zero.']);
                return true;
            }

            if ($comprovanteUrl === '') {
                JsonResponse::send(['ok' => false, 'erro' => 'Informe o caminho do comprovante.']);
                return true;
            }

            $nomeArquivo = basename(str_replace('\\', '/', $comprovanteUrl));
            $ext = strtolower(pathinfo($nomeArquivo, PATHINFO_EXTENSION));
            $mimeMap = [
                'pdf' => 'application/pdf',
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'webp' => 'image/webp',
            ];
            $tipoArquivo = $mimeMap[$ext] ?? 'desconhecido';

            $comprovanteModel = new ComprovantePix();
            $ok = $comprovanteModel->registrar([
                'obreiro_id' => $obreiroId,
                'valor_informado' => $valor,
                'mes_ref_informado' => $mes,
                'ano_ref_informado' => $ano,
                'descricao_usuario' => $descricao,
                'nome_arquivo' => $nomeArquivo,
                'tipo_arquivo' => $tipoArquivo,
                'status' => 'pendente',
            ]);

            JsonResponse::send([
                'ok' => $ok,
                'erro' => $ok ? null : 'Erro ao registrar comprovante no banco.',
            ]);
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

        if ($requestUri === '/api/tesouraria/sessoes' && $method === 'GET') {
            $sessaoModel = new Sessao();
            $presencaModel = new Presenca();
            $configuracao = (new ConfiguracaoLoja())->obter();
            $itens = [];

            foreach ($sessaoModel->listarFuturas(8) as $sessao) {
                $participantes = !empty($sessao['id'])
                    ? $presencaModel->listarParticipantesAgapePorSessao((int) $sessao['id'])
                    : [];
                $modalidade = strtolower(trim((string) ($sessao['agape_modalidade'] ?? 'nao_havera')));
                $modelo = strtolower(trim((string) ($sessao['agape_modelo_financeiro'] ?? 'oficial_loja')));
                $reflete = $modalidade !== 'nao_havera' && in_array($modelo, ['oficial_loja', 'misto'], true);
                $valor = (float) ($sessao['agape_valor'] ?? 0);

                $itens[] = [
                    'id' => (int) ($sessao['id'] ?? 0),
                    'titulo' => (string) ($sessao['titulo'] ?? ''),
                    'data_hora_inicio' => (string) ($sessao['data_hora_inicio'] ?? ''),
                    'descricao_tipo' => $sessaoModel->obterDescricaoTipoSessao($sessao),
                    'descricao_agape' => $sessaoModel->obterDescricaoAgape($sessao),
                    'descricao_modelo' => $sessaoModel->obterDescricaoModeloFinanceiroAgape($sessao),
                    'reflete_financeiro_oficial' => $reflete,
                    'confirmados_agape' => count($participantes),
                    'estimativa_arrecadacao' => $reflete && $modalidade === 'pago'
                        ? round($valor * count($participantes), 2)
                        : 0,
                    'participantes' => array_map(static fn(array $item): array => [
                        'nome' => (string) ($item['nome'] ?? 'Obreiro'),
                        'cim' => (string) ($item['cim'] ?? ''),
                    ], $participantes),
                ];
            }

            JsonResponse::send([
                'ok' => true,
                'loja' => [
                    'nome' => (string) ($configuracao['nome_loja'] ?? ''),
                    'numero' => (string) ($configuracao['numero_loja'] ?? ''),
                ],
                'sessoes' => $itens,
            ]);
            return true;
        }

        if ($requestUri === '/api/tesouraria/relatorio-gestao' && $method === 'GET') {
            $gestoes = (new Gestao())->listar();
            if ($gestoes === []) {
                JsonResponse::send(['ok' => true, 'gestoes' => [], 'relatorio' => null]);
                return true;
            }

            $gestaoId = (int) ($_GET['gestao_id'] ?? ($gestoes[0]['id'] ?? 0));
            $encerramento = trim((string) ($_GET['encerramento_em'] ?? '')) ?: null;

            try {
                $relatorio = (new RelatorioTesourariaGestao())->montar($gestaoId, $encerramento);
                JsonResponse::send(['ok' => true, 'gestoes' => $gestoes, 'relatorio' => $relatorio]);
            } catch (\Throwable $e) {
                error_log('[tesouraria] Erro ao montar relatorio de gestao: ' . $e->getMessage());
                JsonResponse::send(['ok' => false, 'erro' => 'Não foi possível montar o relatório financeiro.']);
            }
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

            // Obter fechamento do mês anterior para comparação
            $mesPrev = $mes - 1;
            $anoPrev = $ano;
            if ($mesPrev < 1) {
                $mesPrev = 12;
                $anoPrev--;
            }
            $fechamentoAnterior = $fechModel->obter($mesPrev, $anoPrev);

            $dadosAux = $fechModel->obterDadosAuxiliaresRelatorio($mes, $ano);

            JsonResponse::send([
                'ok' => true,
                'fechamento' => $fechamento,
                'anterior' => $fechamentoAnterior,
                'tronco' => $dadosAux['tronco'],
                'inadimplencia' => $dadosAux['inadimplencia'],
                'creditos_a_receber' => $dadosAux['creditos_a_receber']
            ]);
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

        if ($requestUri === '/api/tesouraria/graficos' && $method === 'GET') {
            $ano = (int) ($_GET['ano'] ?? date('Y'));
            if ($ano < 2000 || $ano > (int) date('Y') + 1) {
                $ano = (int) date('Y');
            }

            $lancModel = new LancamentoFinanceiro();
            $dados = $lancModel->obterDadosGraficosAnual($ano);

            JsonResponse::send([
                'ok' => true,
                'ano' => $ano,
                'dados' => $dados
            ]);
            return true;
        }

        if (str_starts_with($requestUri, '/api/tesouraria/obreiro/detalhe-financeiro') && $method === 'GET') {
            $obreiroId = trim((string) ($_GET['obreiro_id'] ?? ''));
            if ($obreiroId === '') {
                JsonResponse::send(['ok' => false, 'erro' => 'ID do obreiro inválido']);
                return true;
            }
            $obrigModel = new ObrigacaoFinanceira();
            $detalhe = $obrigModel->obterDetalheFinanceiroObreiro($obreiroId);
            if (!$detalhe) {
                JsonResponse::send(['ok' => false, 'erro' => 'Obreiro não encontrado']);
                return true;
            }
            JsonResponse::send(['ok' => true, 'detalhe' => $detalhe]);
            return true;
        }

        if ($requestUri === '/api/tesouraria/auto-gerar' && $method === 'POST') {
            $body = RequestBody::json();
            $mes = (int) ($body['mes'] ?? date('n'));
            $ano = (int) ($body['ano'] ?? date('Y'));

            if ($mes < 1 || $mes > 12 || $ano < 2000) {
                JsonResponse::send(['ok' => false, 'erro' => 'Mês ou ano inválido']);
                return true;
            }

            $obrigModel = new ObrigacaoFinanceira();
            
            // Gerar mensalidades para o ano se ainda não criadas
            $resultadoMensalidades = $obrigModel->gerarMensalidadesAno($ano, $usuarioId);
            
            // Programar biblioteca do ano
            $resultadoBiblioteca = $obrigModel->programarBibliotecaRenascencaAno($ano, $usuarioId);

            JsonResponse::send([
                'ok' => true,
                'mensalidades' => $resultadoMensalidades,
                'biblioteca' => $resultadoBiblioteca
            ]);
            return true;
        }

        if ($requestUri === '/api/tesouraria/obreiros-financeiro' && $method === 'GET') {
            $obreiroModel = new Obreiro();
            $obreiros = $obreiroModel->getAllAtivos();
            
            // Format numbers to clean float representation for JSON
            foreach ($obreiros as &$ob) {
                $ob['financeiro_joia_valor'] = isset($ob['financeiro_joia_valor']) ? (float) $ob['financeiro_joia_valor'] : null;
                $ob['financeiro_biblioteca_valor'] = isset($ob['financeiro_biblioteca_valor']) ? (float) $ob['financeiro_biblioteca_valor'] : null;
                $ob['financeiro_mensalidade_valor'] = isset($ob['financeiro_mensalidade_valor']) ? (float) $ob['financeiro_mensalidade_valor'] : null;
            }
            unset($ob);

            JsonResponse::send(['ok' => true, 'obreiros' => $obreiros]);
            return true;
        }

        if ($requestUri === '/api/tesouraria/obreiros-financeiro/salvar' && $method === 'POST') {
            $body = RequestBody::json();
            $obreiroId = trim((string) ($body['obreiro_id'] ?? ''));
            $joiaValor = isset($body['joia_valor']) && $body['joia_valor'] !== '' ? (float) $body['joia_valor'] : null;
            $joiaFormato = trim((string) ($body['joia_formato'] ?? 'a_vista'));
            $bibliotecaValor = isset($body['biblioteca_valor']) && $body['biblioteca_valor'] !== '' ? (float) $body['biblioteca_valor'] : null;
            $bibliotecaFormato = trim((string) ($body['biblioteca_formato'] ?? 'mensal'));
            $dataElevacao = isset($body['data_elevacao']) && $body['data_elevacao'] !== '' ? trim((string) $body['data_elevacao']) : null;
            $dataExaltacao = isset($body['data_exaltacao']) && $body['data_exaltacao'] !== '' ? trim((string) $body['data_exaltacao']) : null;
            $joiaAtiva = !empty($body['joia_ativa']);
            $joiaTipo = isset($body['joia_tipo']) && $body['joia_tipo'] !== '' ? trim((string) $body['joia_tipo']) : null;
            $dataIniciacao = isset($body['data_iniciacao']) && $body['data_iniciacao'] !== '' ? trim((string) $body['data_iniciacao']) : null;
            $bibliotecaMes = isset($body['biblioteca_mes']) && $body['biblioteca_mes'] !== '' && $body['biblioteca_mes'] !== 'null' ? (int) $body['biblioteca_mes'] : null;
            $mensalidadeValor = isset($body['mensalidade_valor']) && $body['mensalidade_valor'] !== '' ? (float) $body['mensalidade_valor'] : null;
            $mensalidadeFormato = trim((string) ($body['mensalidade_formato'] ?? 'mensal'));

            if ($obreiroId === '') {
                JsonResponse::send(['ok' => false, 'erro' => 'ID do obreiro inválido']);
                return true;
            }

            $obreiroModel = new Obreiro();
            $ok = $obreiroModel->atualizarConfiguracaoFinanceira(
                $obreiroId,
                $joiaValor,
                $joiaFormato,
                $bibliotecaValor,
                $bibliotecaFormato,
                $dataElevacao,
                $dataExaltacao,
                $joiaAtiva,
                $joiaTipo,
                $dataIniciacao,
                $bibliotecaMes,
                $mensalidadeValor,
                $mensalidadeFormato
            );

            JsonResponse::send(['ok' => $ok, 'erro' => $ok ? null : 'Falha ao salvar configurações financeiras']);
            return true;
        }

        return false;
    }
}
