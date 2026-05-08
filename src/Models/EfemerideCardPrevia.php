<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class EfemerideCardPrevia
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->ensureTable();
    }

    private function ensureTable(): void
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS efemerides_cards_previas (
                id SERIAL PRIMARY KEY,
                data_ref DATE NOT NULL,
                registro_id INTEGER NOT NULL,
                template_slug VARCHAR(120) NOT NULL,
                ocultar_idade BOOLEAN NOT NULL DEFAULT true,
                texto_custom_card TEXT NULL,
                card_hash VARCHAR(128) NOT NULL,
                card_path TEXT NULL,
                aprovado BOOLEAN NOT NULL DEFAULT false,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE (data_ref, registro_id)
            )
        ");
    }

    public function upsert(string $dataRef, int $registroId, array $data): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO efemerides_cards_previas (data_ref, registro_id, template_slug, ocultar_idade, texto_custom_card, card_hash, card_path, aprovado, updated_at)
            VALUES (:data_ref, :registro_id, :template_slug, (:ocultar_idade_int = 1), :texto_custom_card, :card_hash, :card_path, (:aprovado_int = 1), CURRENT_TIMESTAMP)
            ON CONFLICT (data_ref, registro_id) DO UPDATE SET
                template_slug = EXCLUDED.template_slug,
                ocultar_idade = EXCLUDED.ocultar_idade,
                texto_custom_card = EXCLUDED.texto_custom_card,
                card_hash = EXCLUDED.card_hash,
                card_path = EXCLUDED.card_path,
                aprovado = EXCLUDED.aprovado,
                updated_at = CURRENT_TIMESTAMP
        ");
        return $stmt->execute([
            'data_ref' => $dataRef,
            'registro_id' => $registroId,
            'template_slug' => (string) ($data['template_slug'] ?? 'card_oficial_convite.png'),
            'ocultar_idade_int' => !empty($data['ocultar_idade']) ? 1 : 0,
            'texto_custom_card' => $data['texto_custom_card'] ?? null,
            'card_hash' => (string) ($data['card_hash'] ?? ''),
            'card_path' => $data['card_path'] ?? null,
            'aprovado_int' => !empty($data['aprovado']) ? 1 : 0,
        ]);
    }

    public function findByDate(string $dataRef): array
    {
        $stmt = $this->db->prepare("SELECT * FROM efemerides_cards_previas WHERE data_ref = :data_ref ORDER BY id ASC");
        $stmt->execute(['data_ref' => $dataRef]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findOne(string $dataRef, int $registroId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM efemerides_cards_previas WHERE data_ref = :data_ref AND registro_id = :registro_id LIMIT 1");
        $stmt->execute(['data_ref' => $dataRef, 'registro_id' => $registroId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function atualizarPreferencias(string $dataRef, int $registroId, bool $ocultarIdade, ?string $textoCustom): bool
    {
        $stmt = $this->db->prepare("
            UPDATE efemerides_cards_previas
            SET ocultar_idade = :ocultar_idade,
                texto_custom_card = :texto_custom_card,
                updated_at = CURRENT_TIMESTAMP
            WHERE data_ref = :data_ref
              AND registro_id = :registro_id
        ");
        return $stmt->execute([
            'data_ref' => $dataRef,
            'registro_id' => $registroId,
            'ocultar_idade' => $ocultarIdade,
            'texto_custom_card' => $textoCustom,
        ]);
    }
}
