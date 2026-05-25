<?php

namespace App\Models;

use App\Config\Database;
use App\Core\Tenant\ResolvesStoreTenant;
use PDO;

class EfemeridesEnvioLog
{
    use ResolvesStoreTenant;

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->ensureTable();
    }

    private function ensureTable(): void
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS efemerides_envios_log (
                id SERIAL PRIMARY KEY,
                loja_id INTEGER NOT NULL,
                data_ref DATE NOT NULL,
                telegram_id BIGINT NOT NULL,
                modo VARCHAR(24) NOT NULL,
                action_hash VARCHAR(64) NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE (loja_id, data_ref, telegram_id, action_hash)
            )
        ");
    }

    public function tryRegister(string $dataRef, int $telegramId, string $modo, string $actionHash): bool
    {
        $lojaId = $this->resolveCurrentStoreId($this->db);
        $stmt = $this->db->prepare("
            INSERT INTO efemerides_envios_log (loja_id, data_ref, telegram_id, modo, action_hash)
            VALUES (:loja_id, :data_ref, :telegram_id, :modo, :action_hash)
            ON CONFLICT (loja_id, data_ref, telegram_id, action_hash) DO NOTHING
        ");
        $stmt->execute([
            'loja_id' => $lojaId,
            'data_ref' => $dataRef,
            'telegram_id' => $telegramId,
            'modo' => $modo,
            'action_hash' => $actionHash,
        ]);

        return $stmt->rowCount() > 0;
    }
}

