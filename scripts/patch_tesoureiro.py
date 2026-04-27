#!/usr/bin/env python3
"""
Script para aplicar patches críticos ao TesourariaApiRoutes
"""
import re

file_path = r'd:\Repos\gestor-loja\src\Core\Http\TesourariaApiRoutes.php'

with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# PATCH 1: Novo endpoint cancelar-comprovante (inserir após rejeitar)
cancelar_endpoint = '''
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
            } catch (\\Throwable $e) {
                $db->rollBack();
                error_log('[tesouraria] Erro ao cancelar comprovante: ' . $e->getMessage());
                JsonResponse::send(['ok' => false, 'erro' => 'Falha ao cancelar comprovante. Operação revertida.']);
            }
        }
'''

# Inserir após "rejeitar"
pattern_rejeitar = r"(if \(\$requestUri === '/api/tesouraria/comprovantes/rejeitar'.*?\n\s+JsonResponse::send\(\['ok' => \$ok\]\);\s+\})"
match = re.search(pattern_rejeitar, content, re.DOTALL)
if match:
    pos = match.end()
    content = content[:pos] + cancelar_endpoint + content[pos:]
    print("✓ Endpoint cancelar-comprovante adicionado")
else:
    print("✗ Não encontrou padrão rejeitar")

# Salvar
with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print(f"✓ Arquivo atualizado: {file_path}")
