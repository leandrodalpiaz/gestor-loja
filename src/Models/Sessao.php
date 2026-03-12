<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class Sessao
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Busca a próxima sessão futura em relação a data atual
     */
    public function getProximaSessao(): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM eventos
            WHERE data_hora > NOW()
            ORDER BY data_hora ASC
            LIMIT 1
        ");
        $stmt->execute();

        $result = $stmt->fetch();
        return $result ?: null;
    }
}