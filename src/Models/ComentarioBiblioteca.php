<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class ComentarioBiblioteca
{
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
                JOIN obreiros o ON o.id = c.obreiro_id
                WHERE c.acervo_id = :acervo_id
                  AND c.ativo = TRUE
                ORDER BY c.criado_em DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['acervo_id' => $acervoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function adicionar(int $acervoId, string $obreiroId, string $comentario): bool
    {
        $comentario = trim($comentario);
        if ($comentario === '') {
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
}
