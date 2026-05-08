<?php

namespace App\Models;

use App\Config\Database;
use App\Core\Tenant\ResolvesStoreTenant;
use PDO;

class Comunicado
{
    use ResolvesStoreTenant;

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function listarRecentes(int $limite = 30): array
    {
        $limite = max(1, min($limite, 200));

        $stmt = $this->db->prepare("
            SELECT
                c.id,
                c.titulo,
                c.categoria,
                c.publicado_em,
                c.publicado,
                COALESCE(l.total_leituras, 0) AS total_leituras
            FROM comunicados c
            LEFT JOIN (
                SELECT comunicado_id, COUNT(*) AS total_leituras
                FROM comunicados_leituras
                GROUP BY comunicado_id
            ) l ON l.comunicado_id = c.id
            WHERE c.loja_id = :loja_id
              AND c.publicado = TRUE
            ORDER BY c.publicado_em DESC, c.id DESC
            LIMIT :limite
        ");

        $stmt->bindValue('loja_id', $this->currentStoreId(), PDO::PARAM_INT);
        $stmt->bindValue('limite', $limite, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM comunicados
            WHERE id = :id
              AND loja_id = :loja_id
            LIMIT 1
        ");
        $stmt->execute([
            'id' => $id,
            'loja_id' => $this->currentStoreId(),
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function marcarLeitura(int $comunicadoId, string $obreiroId): bool
    {
        $obreiroId = trim($obreiroId);
        if ($comunicadoId <= 0 || $obreiroId === '') {
            return false;
        }

        $stmt = $this->db->prepare("
            INSERT INTO comunicados_leituras (comunicado_id, obreiro_id, lido_em)
            SELECT :comunicado_id, :obreiro_id, NOW()
            WHERE EXISTS (
                SELECT 1 FROM comunicados c
                WHERE c.id = :comunicado_id
                  AND c.loja_id = :loja_id
                  AND c.publicado = TRUE
            )
            ON CONFLICT (comunicado_id, obreiro_id) DO NOTHING
        ");

        return $stmt->execute([
            'comunicado_id' => $comunicadoId,
            'obreiro_id' => $obreiroId,
            'loja_id' => $this->currentStoreId(),
        ]);
    }

    public function usuarioLeu(int $comunicadoId, string $obreiroId): bool
    {
        $obreiroId = trim($obreiroId);
        if ($comunicadoId <= 0 || $obreiroId === '') {
            return false;
        }

        $stmt = $this->db->prepare("
            SELECT 1
            FROM comunicados_leituras cl
            JOIN comunicados c ON c.id = cl.comunicado_id
            WHERE cl.comunicado_id = :comunicado_id
              AND cl.obreiro_id = :obreiro_id
              AND c.loja_id = :loja_id
            LIMIT 1
        ");
        $stmt->execute([
            'comunicado_id' => $comunicadoId,
            'obreiro_id' => $obreiroId,
            'loja_id' => $this->currentStoreId(),
        ]);

        return (bool) $stmt->fetchColumn();
    }

    public function criar(array $dados, ?string $autorId = null): ?int
    {
        $titulo = trim((string) ($dados['titulo'] ?? ''));
        $conteudo = trim((string) ($dados['conteudo'] ?? ''));
        $categoria = trim((string) ($dados['categoria'] ?? 'geral'));

        if ($titulo === '' || $conteudo === '') {
            return null;
        }

        $stmt = $this->db->prepare("
            INSERT INTO comunicados (
                loja_id,
                titulo,
                conteudo,
                categoria,
                publicado,
                publicado_em,
                criado_por,
                updated_at
            ) VALUES (
                :loja_id,
                :titulo,
                :conteudo,
                :categoria,
                TRUE,
                NOW(),
                :criado_por,
                NOW()
            )
            RETURNING id
        ");

        $stmt->execute([
            'loja_id' => $this->currentStoreId(),
            'titulo' => $titulo,
            'conteudo' => $conteudo,
            'categoria' => $categoria !== '' ? $categoria : 'geral',
            'criado_por' => $autorId,
        ]);

        $id = $stmt->fetchColumn();
        return $id !== false ? (int) $id : null;
    }

    private function currentStoreId(): int
    {
        return $this->resolveCurrentStoreId($this->db);
    }
}
