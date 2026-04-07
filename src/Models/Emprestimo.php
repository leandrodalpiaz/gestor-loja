<?php

namespace App\Models;

use App\Config\Database;
use PDO;
use Throwable;

class Emprestimo
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function listarPendentesPorObreiro(string $obreiroId): array
    {
        $sql = "SELECT
                    e.*,
                    a.titulo,
                    a.codigo_acervo
                FROM emprestimos e
                JOIN acervo a ON e.acervo_id = a.id
                WHERE e.obreiro_id = :obreiro_id
                  AND e.status IN ('pendente', 'atrasado', 'aprovado')
                ORDER BY e.data_emprestimo DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['obreiro_id' => $obreiroId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function solicitar(int $acervoId, string $obreiroId): bool
    {
        try {
            $this->db->beginTransaction();

            $stmtLivro = $this->db->prepare(
                "SELECT quantidade_disponivel
                 FROM acervo
                 WHERE id = :id
                   AND ativo = TRUE
                 FOR UPDATE"
            );
            $stmtLivro->execute(['id' => $acervoId]);
            $livro = $stmtLivro->fetch(PDO::FETCH_ASSOC);
            if (!$livro || (int) ($livro['quantidade_disponivel'] ?? 0) <= 0) {
                $this->db->rollBack();
                return false;
            }

            $sqlInsert = "INSERT INTO emprestimos (
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
                          )";
            $stmtInsert = $this->db->prepare($sqlInsert);
            $okInsert = $stmtInsert->execute([
                'acervo_id' => $acervoId,
                'obreiro_id' => $obreiroId,
            ]);
            if (!$okInsert) {
                $this->db->rollBack();
                return false;
            }

            $stmtUpdate = $this->db->prepare(
                "UPDATE acervo
                 SET quantidade_disponivel = quantidade_disponivel - 1,
                     atualizado_em = CURRENT_TIMESTAMP
                 WHERE id = :id"
            );
            $okUpdate = $stmtUpdate->execute(['id' => $acervoId]);
            if (!$okUpdate) {
                $this->db->rollBack();
                return false;
            }

            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('[emprestimo.solicitar] ' . $e->getMessage());
            return false;
        }
    }

    public function listarPorObreiro(string $obreiroId): array
    {
        $sql = "SELECT
                    e.*,
                    a.titulo,
                    a.codigo_acervo
                FROM emprestimos e
                JOIN acervo a ON a.id = e.acervo_id
                WHERE e.obreiro_id = :obreiro_id
                ORDER BY e.data_emprestimo DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['obreiro_id' => $obreiroId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarPendentes(): array
    {
        $sql = "SELECT
                    e.*,
                    a.titulo,
                    a.codigo_acervo,
                    COALESCE(o.nome_historico, o.nome) AS obreiro_nome
                FROM emprestimos e
                JOIN acervo a ON a.id = e.acervo_id
                JOIN obreiros o ON o.id = e.obreiro_id
                WHERE e.status IN ('pendente', 'atrasado')
                ORDER BY e.data_emprestimo DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function registrarDevolucao(int $id): bool
    {
        try {
            $this->db->beginTransaction();

            $stmtEmprestimo = $this->db->prepare(
                "SELECT id, acervo_id, status
                 FROM emprestimos
                 WHERE id = :id
                 FOR UPDATE"
            );
            $stmtEmprestimo->execute(['id' => $id]);
            $emprestimo = $stmtEmprestimo->fetch(PDO::FETCH_ASSOC);
            if (!$emprestimo) {
                $this->db->rollBack();
                return false;
            }

            $stmtUpdateEmprestimo = $this->db->prepare(
                "UPDATE emprestimos
                 SET data_devolucao_real = CURRENT_DATE,
                     status = 'devolvido'
                 WHERE id = :id"
            );
            $okEmp = $stmtUpdateEmprestimo->execute(['id' => $id]);
            if (!$okEmp) {
                $this->db->rollBack();
                return false;
            }

            if ((string) ($emprestimo['status'] ?? '') !== 'devolvido') {
                $stmtUpdateLivro = $this->db->prepare(
                    "UPDATE acervo
                     SET quantidade_disponivel = quantidade_disponivel + 1,
                         atualizado_em = CURRENT_TIMESTAMP
                     WHERE id = :id"
                );
                $okLivro = $stmtUpdateLivro->execute(['id' => (int) $emprestimo['acervo_id']]);
                if (!$okLivro) {
                    $this->db->rollBack();
                    return false;
                }
            }

            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('[emprestimo.registrarDevolucao] ' . $e->getMessage());
            return false;
        }
    }
}
