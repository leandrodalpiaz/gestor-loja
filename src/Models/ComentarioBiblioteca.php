<?php

namespace App\Models;

use App\Config\Database;
use App\Core\Tenant\ResolvesStoreTenant;
use PDO;

class ComentarioBiblioteca
{
    use ResolvesStoreTenant;

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function listarPorLivro(int $acervoId): array
    {
        $sql = "SELECT
                    c.*,
                    COALESCE(o.nome_historico, o.nome) AS obreiro_nome
                FROM biblioteca_comentarios c
                JOIN acervo a ON a.id = c.acervo_id
                JOIN obreiros o ON o.id = c.obreiro_id
                WHERE c.acervo_id = :acervo_id
                  AND a.loja_id = :loja_id
                  AND c.ativo = TRUE
                ORDER BY c.criado_em DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'acervo_id' => $acervoId,
            'loja_id' => $this->buscarLojaAtualId(),
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function adicionar(int $acervoId, string $obreiroId, string $comentario): bool
    {
        $comentario = trim($comentario);
        if ($comentario === '' || !$this->acervoPertenceLojaAtual($acervoId)) {
            return false;
        }

        $stmt = $this->db->prepare(
            "INSERT INTO biblioteca_comentarios (acervo_id, obreiro_id, comentario, ativo)
             VALUES (:acervo_id, :obreiro_id, :comentario, TRUE)"
        );
        return $stmt->execute([
            'acervo_id' => $acervoId,
            'obreiro_id' => $obreiroId,
            'comentario' => $comentario,
        ]);
    }

    private function acervoPertenceLojaAtual(int $acervoId): bool
    {
        $stmt = $this->db->prepare(
            "SELECT 1
             FROM acervo
             WHERE id = :id
               AND loja_id = :loja_id
               AND ativo = TRUE
             LIMIT 1"
        );
        $stmt->execute([
            'id' => $acervoId,
            'loja_id' => $this->buscarLojaAtualId(),
        ]);

        return $stmt->fetchColumn() !== false;
    }

    private function buscarLojaAtualId(): int
    {
        return $this->resolveCurrentStoreId($this->db);
    }
}
