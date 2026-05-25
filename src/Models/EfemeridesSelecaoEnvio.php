<?php

namespace App\Models;

use App\Config\Database;
use App\Core\Tenant\ResolvesStoreTenant;
use PDO;

class EfemeridesSelecaoEnvio
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
            CREATE TABLE IF NOT EXISTS efemerides_envios_selecao (
                id SERIAL PRIMARY KEY,
                loja_id INTEGER NOT NULL,
                data_ref DATE NOT NULL,
                telegram_id BIGINT NOT NULL,
                selected_registro_ids TEXT NOT NULL DEFAULT '[]',
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE (loja_id, data_ref, telegram_id)
            )
        ");
    }

    /**
     * @return array<int,int>
     */
    public function getSelectedIds(string $dataRef, int $telegramId): array
    {
        $lojaId = $this->resolveCurrentStoreId($this->db);
        $stmt = $this->db->prepare("
            SELECT selected_registro_ids
            FROM efemerides_envios_selecao
            WHERE loja_id = :loja_id AND data_ref = :data_ref AND telegram_id = :telegram_id
            LIMIT 1
        ");
        $stmt->execute([
            'loja_id' => $lojaId,
            'data_ref' => $dataRef,
            'telegram_id' => $telegramId,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $raw = is_array($row) ? (string) ($row['selected_registro_ids'] ?? '[]') : '[]';
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }

        $ids = [];
        foreach ($decoded as $v) {
            $iv = (int) $v;
            if ($iv > 0) {
                $ids[] = $iv;
            }
        }

        $ids = array_values(array_unique($ids));
        sort($ids);
        return $ids;
    }

    /**
     * @return array<int,int>
     */
    public function toggle(string $dataRef, int $telegramId, int $registroId): array
    {
        $ids = $this->getSelectedIds($dataRef, $telegramId);
        if (in_array($registroId, $ids, true)) {
            $ids = array_values(array_diff($ids, [$registroId]));
        } else {
            $ids[] = $registroId;
            $ids = array_values(array_unique($ids));
            sort($ids);
        }

        $this->setSelectedIds($dataRef, $telegramId, $ids);
        return $ids;
    }

    /**
     * @param array<int,int> $ids
     */
    public function setSelectedIds(string $dataRef, int $telegramId, array $ids): void
    {
        $lojaId = $this->resolveCurrentStoreId($this->db);
        $payload = json_encode(array_values(array_map('intval', $ids)), JSON_UNESCAPED_UNICODE) ?: '[]';
        $stmt = $this->db->prepare("
            INSERT INTO efemerides_envios_selecao (loja_id, data_ref, telegram_id, selected_registro_ids, updated_at)
            VALUES (:loja_id, :data_ref, :telegram_id, :ids, CURRENT_TIMESTAMP)
            ON CONFLICT (loja_id, data_ref, telegram_id) DO UPDATE
            SET selected_registro_ids = EXCLUDED.selected_registro_ids,
                updated_at = CURRENT_TIMESTAMP
        ");
        $stmt->execute([
            'loja_id' => $lojaId,
            'data_ref' => $dataRef,
            'telegram_id' => $telegramId,
            'ids' => $payload,
        ]);
    }

    public function clear(string $dataRef, int $telegramId): void
    {
        $this->setSelectedIds($dataRef, $telegramId, []);
    }
}

