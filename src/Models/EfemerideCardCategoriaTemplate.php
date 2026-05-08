<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class EfemerideCardCategoriaTemplate
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS efemerides_cards_categoria_template (
                id SERIAL PRIMARY KEY,
                categoria VARCHAR(120) NOT NULL UNIQUE,
                template_slug VARCHAR(120) NOT NULL,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }

    public function salvar(string $categoria, string $template): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO efemerides_cards_categoria_template (categoria, template_slug, updated_at)
            VALUES (:categoria, :template_slug, CURRENT_TIMESTAMP)
            ON CONFLICT (categoria) DO UPDATE SET
                template_slug = EXCLUDED.template_slug,
                updated_at = CURRENT_TIMESTAMP
        ");
        return $stmt->execute(['categoria' => $categoria, 'template_slug' => $template]);
    }

    public function mapa(): array
    {
        $rows = $this->db->query("SELECT categoria, template_slug FROM efemerides_cards_categoria_template")->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $map = [];
        foreach ($rows as $row) $map[(string)$row['categoria']] = (string)$row['template_slug'];
        return $map;
    }
}
