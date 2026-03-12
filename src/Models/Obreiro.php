<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class Obreiro
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Busca um obreiro pelo ID do Telegram
     */
    public function findByTelegramId(int $telegramId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM obreiros WHERE telegram_id = :telegram_id LIMIT 1");
        $stmt->execute(['telegram_id' => $telegramId]);
        
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Busca todos os obreiros ativos
     */
    public function getAllAtivos(): array
    {
        $stmt = $this->db->prepare("SELECT * FROM obreiros WHERE ativo = true ORDER BY nome ASC");
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
}