<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class Emprestimo
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function solicitar(int $acervoId, int $obreiroId): bool
    {
        $sql = "INSERT INTO emprestimos (acervo_id, obreiro_id, data_emprestimo, data_devolucao_prevista, status) VALUES (:acervo_id, :obreiro_id, CURRENT_DATE, CURRENT_DATE + INTERVAL '14 days', 'pendente')";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'acervo_id' => $acervoId,
            'obreiro_id' => $obreiroId
        ]);
    }

    public function listarPorObreiro(int $obreiroId): array
    {
        $sql = "SELECT * FROM emprestimos WHERE obreiro_id = :obreiro_id ORDER BY data_emprestimo DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['obreiro_id' => $obreiroId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarPendentes(): array
    {
        $sql = "SELECT * FROM emprestimos WHERE status = 'pendente' OR status = 'atrasado' ORDER BY data_emprestimo DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function registrarDevolucao(int $id): bool
    {
        $sql = "UPDATE emprestimos SET data_devolucao_real = CURRENT_DATE, status = 'devolvido' WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }
}
