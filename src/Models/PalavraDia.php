<?php

namespace App\Models;

use App\Config\Database;
use App\Core\Tenant\ResolvesStoreTenant;
use PDO;

class PalavraDia
{
    use ResolvesStoreTenant;

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->ensureTable();
        $this->migrarLegado();
    }

    private function ensureTable(): void
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS palavras_dia (
                id SERIAL PRIMARY KEY,
                loja_id INTEGER NULL,
                titulo VARCHAR(255) NULL,
                mensagem TEXT NOT NULL,
                observacao TEXT NULL,
                ativo BOOLEAN NOT NULL DEFAULT true,
                created_by INT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }

    private function migrarLegado(): void
    {
        $stmt = $this->db->query("
            SELECT id, mensagem, ativo
            FROM mensagens_complementares
            WHERE tipo = 'fallback'
            ORDER BY id ASC
        ");

        $itens = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($itens as $item) {
            $mensagem = trim((string) ($item['mensagem'] ?? ''));
            if ($mensagem === '') {
                continue;
            }

            $check = $this->db->prepare("
                SELECT id
                FROM palavras_dia
                WHERE loja_id = :loja_id
                  AND mensagem = :mensagem
                LIMIT 1
            ");
            $check->execute([
                'loja_id' => $this->obterLojaAtualId(),
                'mensagem' => $mensagem,
            ]);
            if ($check->fetch(PDO::FETCH_ASSOC)) {
                continue;
            }

            $insert = $this->db->prepare("
                INSERT INTO palavras_dia (loja_id, titulo, mensagem, observacao, ativo, created_by)
                VALUES (:loja_id, NULL, :mensagem, 'Migrada do legado fallback', :ativo, NULL)
            ");
            $insert->execute([
                'loja_id' => $this->obterLojaAtualId(),
                'mensagem' => $mensagem,
                'ativo' => !empty($item['ativo']) ? 'true' : 'false',
            ]);
        }
    }

    public function listar(array $filtros = [], int $limit = 300): array
    {
        $limit = max(1, min($limit, 500));
        $where = ["loja_id = :loja_id"];
        $params = ['loja_id' => $this->obterLojaAtualId()];

        $termo = trim((string) ($filtros['termo'] ?? ''));
        if ($termo !== '') {
            $where[] = "(COALESCE(titulo, '') ILIKE :termo OR mensagem ILIKE :termo OR COALESCE(observacao, '') ILIKE :termo)";
            $params['termo'] = '%' . $termo . '%';
        }

        $ativo = strtolower(trim((string) ($filtros['ativo'] ?? '1')));
        if ($ativo === '1' || $ativo === 'true' || $ativo === 'ativos') {
            $where[] = "ativo = true";
        } elseif ($ativo === '0' || $ativo === 'false' || $ativo === 'inativos') {
            $where[] = "ativo = false";
        }

        $sql = "SELECT * FROM palavras_dia WHERE " . implode(' AND ', $where) . " ORDER BY id ASC LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarAtivas(): array
    {
        return $this->listar(['ativo' => '1'], 1000);
    }

    public function create(array $data, ?int $createdBy): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO palavras_dia (loja_id, titulo, mensagem, observacao, ativo, created_by)
            VALUES (:loja_id, :titulo, :mensagem, :observacao, :ativo, :created_by)
        ");

        return $stmt->execute([
            'loja_id' => $this->obterLojaAtualId(),
            'titulo' => trim((string) ($data['titulo'] ?? '')) ?: null,
            'mensagem' => trim((string) ($data['mensagem'] ?? '')),
            'observacao' => trim((string) ($data['observacao'] ?? '')) ?: null,
            'ativo' => array_key_exists('ativo', $data) ? (filter_var($data['ativo'], FILTER_VALIDATE_BOOL) ? 'true' : 'false') : 'true',
            'created_by' => $createdBy,
        ]);
    }

    public function atualizar(int $id, array $data): bool
    {
        $stmt = $this->db->prepare("
            UPDATE palavras_dia
            SET titulo = :titulo,
                mensagem = :mensagem,
                observacao = :observacao,
                ativo = :ativo,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
              AND loja_id = :loja_id
        ");

        return $stmt->execute([
            'id' => $id,
            'loja_id' => $this->obterLojaAtualId(),
            'titulo' => trim((string) ($data['titulo'] ?? '')) ?: null,
            'mensagem' => trim((string) ($data['mensagem'] ?? '')),
            'observacao' => trim((string) ($data['observacao'] ?? '')) ?: null,
            'ativo' => array_key_exists('ativo', $data) ? (filter_var($data['ativo'], FILTER_VALIDATE_BOOL) ? 'true' : 'false') : 'true',
        ]);
    }

    public function toggleAtivo(int $id): bool
    {
        $stmt = $this->db->prepare("
            UPDATE palavras_dia
            SET ativo = NOT ativo,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
              AND loja_id = :loja_id
        ");
        return $stmt->execute([
            'id' => $id,
            'loja_id' => $this->obterLojaAtualId(),
        ]);
    }

    public function excluir(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM palavras_dia WHERE id = :id AND loja_id = :loja_id");
        return $stmt->execute([
            'id' => $id,
            'loja_id' => $this->obterLojaAtualId(),
        ]);
    }

    private function obterLojaAtualId(): int
    {
        return $this->resolveCurrentStoreId($this->db);
    }
}
