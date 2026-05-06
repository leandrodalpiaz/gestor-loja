<?php

namespace App\Models;

use App\Config\Database;
use App\Core\Tenant\ResolvesStoreTenant;
use PDO;
use Throwable;

class EmprestimoInterloja
{
    use ResolvesStoreTenant;

    private PDO $db;
    private ?bool $tabelaExiste = null;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function solicitar(int $acervoId, int $lojaOrigemId, string $obreiroId, ?string $observacao = null): bool
    {
        if (!$this->tabelaExiste()) {
            return false;
        }

        try {
            $lojaDestinoId = $this->buscarLojaAtualId();
            if ($lojaOrigemId === $lojaDestinoId) {
                return false;
            }

            $stmtAcervo = $this->db->prepare("SELECT loja_id FROM acervo WHERE id = :id AND ativo = TRUE LIMIT 1");
            $stmtAcervo->execute(['id' => $acervoId]);
            $acervoLojaId = (int) ($stmtAcervo->fetchColumn() ?: 0);
            if ($acervoLojaId <= 0 || $acervoLojaId !== $lojaOrigemId) {
                return false;
            }

            $stmt = $this->db->prepare(
                "INSERT INTO emprestimos_interloja (
                    loja_origem_id,
                    loja_destino_id,
                    acervo_id,
                    obreiro_id,
                    status,
                    observacao
                 ) VALUES (
                    :loja_origem_id,
                    :loja_destino_id,
                    :acervo_id,
                    :obreiro_id,
                    'solicitado',
                    :observacao
                 )"
            );

            return (bool) $stmt->execute([
                'loja_origem_id' => $lojaOrigemId,
                'loja_destino_id' => $lojaDestinoId,
                'acervo_id' => $acervoId,
                'obreiro_id' => $obreiroId,
                'observacao' => $observacao,
            ]);
        } catch (Throwable $e) {
            error_log('[emprestimo_interloja.solicitar] ' . $e->getMessage());
            return false;
        }
    }

    public function listarRecebidasDaLojaAtual(): array
    {
        if (!$this->tabelaExiste()) {
            return [];
        }

        $sql = "SELECT
                    ei.*,
                    a.titulo,
                    a.codigo_acervo,
                    a.quantidade_disponivel,
                    COALESCE(o.nome_historico, o.nome) AS obreiro_nome,
                    ld.nome AS loja_destino_nome,
                    ld.numero_loja AS loja_destino_numero,
                    ld.sigla AS loja_destino_sigla
                FROM emprestimos_interloja ei
                JOIN acervo a ON a.id = ei.acervo_id
                JOIN obreiros o ON o.id = ei.obreiro_id
                JOIN lojas ld ON ld.id = ei.loja_destino_id
                WHERE ei.loja_origem_id = :loja_id
                ORDER BY
                    CASE ei.status WHEN 'solicitado' THEN 0 WHEN 'aprovado' THEN 1 ELSE 2 END,
                    ei.solicitado_em DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['loja_id' => $this->buscarLojaAtualId()]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function listarPorObreiroDaLojaAtual(string $obreiroId): array
    {
        if (!$this->tabelaExiste() || !$this->isUuid($obreiroId)) {
            return [];
        }

        $sql = "SELECT
                    ei.*,
                    a.titulo,
                    a.codigo_acervo,
                    lo.nome AS loja_origem_nome,
                    lo.numero_loja AS loja_origem_numero,
                    lo.sigla AS loja_origem_sigla
                FROM emprestimos_interloja ei
                JOIN acervo a ON a.id = ei.acervo_id
                JOIN lojas lo ON lo.id = ei.loja_origem_id
                WHERE ei.loja_destino_id = :loja_id
                  AND ei.obreiro_id = :obreiro_id
                ORDER BY ei.solicitado_em DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'loja_id' => $this->buscarLojaAtualId(),
            'obreiro_id' => $obreiroId,
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function decidir(int $pedidoId, string $status, ?string $decididoPor = null): bool
    {
        if (!$this->tabelaExiste()) {
            return false;
        }

        if (!in_array($status, ['aprovado', 'negado'], true)) {
            return false;
        }
        if ($decididoPor !== null && !$this->isUuid($decididoPor)) {
            $decididoPor = null;
        }

        try {
            $this->db->beginTransaction();

            $stmtPedido = $this->db->prepare(
                "SELECT ei.id, ei.acervo_id, ei.obreiro_id, ei.status, a.quantidade_disponivel
                 FROM emprestimos_interloja ei
                 JOIN acervo a ON a.id = ei.acervo_id
                 WHERE ei.id = :id
                   AND ei.loja_origem_id = :loja_id
                 FOR UPDATE"
            );
            $stmtPedido->execute([
                'id' => $pedidoId,
                'loja_id' => $this->buscarLojaAtualId(),
            ]);
            $pedido = $stmtPedido->fetch(PDO::FETCH_ASSOC);
            if (!$pedido || (string) ($pedido['status'] ?? '') !== 'solicitado') {
                $this->db->rollBack();
                return false;
            }

            if ($status === 'aprovado') {
                if ((int) ($pedido['quantidade_disponivel'] ?? 0) <= 0) {
                    $this->db->rollBack();
                    return false;
                }

                $stmtEmprestimo = $this->db->prepare(
                    "INSERT INTO emprestimos (
                        acervo_id,
                        obreiro_id,
                        data_emprestimo,
                        data_devolucao_prevista,
                        status
                    ) VALUES (
                        :acervo_id,
                        :obreiro_id,
                        CURRENT_DATE,
                        CURRENT_DATE + INTERVAL '14 days',
                        'pendente'
                    )"
                );
                $okEmprestimo = $stmtEmprestimo->execute([
                    'acervo_id' => (int) $pedido['acervo_id'],
                    'obreiro_id' => (string) $pedido['obreiro_id'],
                ]);
                if (!$okEmprestimo) {
                    $this->db->rollBack();
                    return false;
                }

                $stmtAcervo = $this->db->prepare(
                    "UPDATE acervo
                     SET quantidade_disponivel = quantidade_disponivel - 1,
                         atualizado_em = CURRENT_TIMESTAMP
                     WHERE id = :id
                       AND loja_id = :loja_id"
                );
                $okAcervo = $stmtAcervo->execute([
                    'id' => (int) $pedido['acervo_id'],
                    'loja_id' => $this->buscarLojaAtualId(),
                ]);
                if (!$okAcervo) {
                    $this->db->rollBack();
                    return false;
                }
            }

            $stmtDecisao = $this->db->prepare(
                "UPDATE emprestimos_interloja
                 SET status = :status,
                     decidido_em = CURRENT_TIMESTAMP,
                     decidido_por = :decidido_por
                 WHERE id = :id
                   AND loja_origem_id = :loja_id"
            );
            $okDecisao = $stmtDecisao->execute([
                'status' => $status,
                'decidido_por' => $decididoPor,
                'id' => $pedidoId,
                'loja_id' => $this->buscarLojaAtualId(),
            ]);
            if (!$okDecisao) {
                $this->db->rollBack();
                return false;
            }

            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('[emprestimo_interloja.decidir] ' . $e->getMessage());
            return false;
        }
    }

    private function buscarLojaAtualId(): int
    {
        return $this->resolveCurrentStoreId($this->db);
    }

    private function tabelaExiste(): bool
    {
        if ($this->tabelaExiste !== null) {
            return $this->tabelaExiste;
        }

        $stmt = $this->db->prepare(
            "SELECT 1
             FROM information_schema.tables
             WHERE table_schema = 'public'
               AND table_name = 'emprestimos_interloja'
             LIMIT 1"
        );
        $stmt->execute();
        $this->tabelaExiste = (bool) $stmt->fetchColumn();
        return $this->tabelaExiste;
    }

    private function isUuid(string $value): bool
    {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', trim($value)) === 1;
    }
}
