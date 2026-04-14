<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class ConteudoCategoriaPermissao
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
            CREATE TABLE IF NOT EXISTS conteudo_categoria_permissoes (
                id SERIAL PRIMARY KEY,
                categoria VARCHAR(60) NOT NULL,
                cargo_slug VARCHAR(60) NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ");
        $this->db->exec("
            CREATE UNIQUE INDEX IF NOT EXISTS idx_conteudo_categoria_permissoes_unico
            ON conteudo_categoria_permissoes (categoria, cargo_slug)
        ");
    }

    public function listarPorCategoria(string $categoria): array
    {
        $stmt = $this->db->prepare("
            SELECT cargo_slug
            FROM conteudo_categoria_permissoes
            WHERE categoria = :categoria
            ORDER BY cargo_slug ASC
        ");
        $stmt->execute(['categoria' => trim($categoria)]);
        return array_values(array_map(
            static fn(array $row): string => (string) ($row['cargo_slug'] ?? ''),
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        ));
    }

    public function salvarPorCategoria(string $categoria, array $cargos): bool
    {
        $categoria = trim($categoria);
        $cargos = array_values(array_unique(array_filter(array_map(
            static fn($cargo): string => trim((string) $cargo),
            $cargos
        ))));

        $this->db->beginTransaction();
        try {
            $delete = $this->db->prepare("DELETE FROM conteudo_categoria_permissoes WHERE categoria = :categoria");
            $delete->execute(['categoria' => $categoria]);

            $insert = $this->db->prepare("
                INSERT INTO conteudo_categoria_permissoes (categoria, cargo_slug)
                VALUES (:categoria, :cargo_slug)
            ");
            foreach ($cargos as $cargo) {
                $insert->execute([
                    'categoria' => $categoria,
                    'cargo_slug' => $cargo,
                ]);
            }

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            return false;
        }
    }
}
