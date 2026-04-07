<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class ReacaoBiblioteca
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function definir(int $acervoId, string $obreiroId, bool $gostei): bool
    {
        $sql = "INSERT INTO biblioteca_reacoes (acervo_id, obreiro_id, gostei, atualizado_em)
                VALUES (:acervo_id, :obreiro_id, :gostei, CURRENT_TIMESTAMP)
                ON CONFLICT (acervo_id, obreiro_id)
                DO UPDATE SET gostei = EXCLUDED.gostei, atualizado_em = CURRENT_TIMESTAMP";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'acervo_id' => $acervoId,
            'obreiro_id' => $obreiroId,
            'gostei' => $gostei,
        ]);
    }
}
