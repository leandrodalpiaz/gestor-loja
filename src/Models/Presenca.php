<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class Presenca
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Registra ou atualiza a presença do obreiro em uma sessão
     */
    public function registrar(string $sessaoId, int $obreiroId, string $status): bool
    {
        // O "ON CONFLICT" do PostgreSQL é perfeito aqui (Upsert)
        // Se a pessoa já confirmou antes, mas mudou de ideia pra "Ausente", ele atualiza em vez de dar erro
        $stmt = $this->db->prepare("
            INSERT INTO presencas (sessao_id, obreiro_id, status)
            VALUES (:sessao_id, :obreiro_id, :status)
            ON CONFLICT (sessao_id, obreiro_id) 
            DO UPDATE SET status = EXCLUDED.status, registrado_em = NOW()
        ");

        return $stmt->execute([
            'sessao_id' => $sessaoId,
            'obreiro_id' => $obreiroId,
            'status' => $status
        ]);
    }
}