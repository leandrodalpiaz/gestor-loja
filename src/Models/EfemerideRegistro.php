<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class EfemerideRegistro
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->ensureTable();
    }

    private function ensureTable(): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS efemerides_registros (
                id SERIAL PRIMARY KEY,
                nome VARCHAR(255) NOT NULL,
                tipo VARCHAR(100) NOT NULL,
                data_evento DATE NOT NULL,
                cod_vinculo INT NULL,
                vinculo VARCHAR(255) NULL,
                parentesco VARCHAR(255) NULL,
                local VARCHAR(255) NULL,
                mensagem_custom TEXT NULL,
                ativo BOOLEAN NOT NULL DEFAULT true,
                created_by INT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ";

        $this->db->exec($sql);
    }

    public function getRegistrosDoDia(): array
    {
        $sql = "
            SELECT *
            FROM efemerides_registros
            WHERE ativo = true
              AND EXTRACT(MONTH FROM data_evento) = EXTRACT(MONTH FROM CURRENT_DATE)
              AND EXTRACT(DAY FROM data_evento) = EXTRACT(DAY FROM CURRENT_DATE)
            ORDER BY tipo, nome
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRecentes(int $limit = 80): array
    {
        $limit = max(1, min($limit, 300));
        $sql = "
            SELECT *
            FROM efemerides_registros
            ORDER BY data_evento DESC, id DESC
            LIMIT :limit
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data, ?int $createdBy): bool
    {
        $sql = "
            INSERT INTO efemerides_registros (
                nome,
                tipo,
                data_evento,
                cod_vinculo,
                vinculo,
                parentesco,
                local,
                mensagem_custom,
                ativo,
                created_by
            ) VALUES (
                :nome,
                :tipo,
                :data_evento,
                :cod_vinculo,
                :vinculo,
                :parentesco,
                :local,
                :mensagem_custom,
                true,
                :created_by
            )
        ";

        $stmt = $this->db->prepare($sql);

        $codVinculo = isset($data['cod_vinculo']) && $data['cod_vinculo'] !== '' ? (int) $data['cod_vinculo'] : null;

        return $stmt->execute([
            'nome' => trim((string) ($data['nome'] ?? '')),
            'tipo' => trim((string) ($data['tipo'] ?? '')),
            'data_evento' => $data['data_evento'] ?? null,
            'cod_vinculo' => $codVinculo,
            'vinculo' => trim((string) ($data['vinculo'] ?? '')) ?: null,
            'parentesco' => trim((string) ($data['parentesco'] ?? '')) ?: null,
            'local' => trim((string) ($data['local'] ?? '')) ?: null,
            'mensagem_custom' => trim((string) ($data['mensagem_custom'] ?? '')) ?: null,
            'created_by' => $createdBy,
        ]);
    }

    public function desativar(int $id): bool
    {
        $sql = "
            UPDATE efemerides_registros
            SET ativo = false,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }
}
