# Script para aplicar patches críticos ao TesourariaApiRoutes

## PATCH 1: Validações + Transação em /api/tesouraria/comprovantes/aprovar
Substituir o bloco inteiro de `aprovar` (linhas ~96-145) por:

`````php
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
        }
````php
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
        }
```

## PATCH 2: Adicionar endpoint cancelar-comprovante (após rejeitar, antes de regularidade)

`````php
        if (preg_match('~^/api/tesouraria/comprovantes/(\\d+)/cancelar$~', $requestUri, $m) && $method === 'POST') {
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
````php
        if (preg_match('~^/api/tesouraria/comprovantes/(\\d+)/cancelar$~', $requestUri, $m) && $method === 'POST') {
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
```

## PATCH 3: Adicionar validações em /api/tesouraria/lancamento/criar

Substituir por:

`````php
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
        }
````php
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
        }
```

## PATCH 4: Validações em /api/tesouraria/regularidade/definir

`````php
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
        }
````php
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
        }
```
