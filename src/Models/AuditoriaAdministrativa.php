<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class AuditoriaAdministrativa
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
            CREATE TABLE IF NOT EXISTS public.auditoria_administrativa (
                id SERIAL PRIMARY KEY,
                origem VARCHAR(50) NOT NULL,
                entidade VARCHAR(50) NOT NULL,
                entidade_id VARCHAR(80) NULL,
                acao VARCHAR(50) NOT NULL,
                resumo VARCHAR(255) NOT NULL,
                detalhes_json JSONB NULL,
                criado_por VARCHAR(80) NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }

    public function registrar(
        string $origem,
        string $entidade,
        ?string $entidadeId,
        string $acao,
        string $resumo,
        ?array $detalhes = null,
        ?string $criadoPor = null
    ): bool {
        $stmt = $this->db->prepare("
            INSERT INTO public.auditoria_administrativa (
                origem,
                entidade,
                entidade_id,
                acao,
                resumo,
                detalhes_json,
                criado_por
            ) VALUES (
                :origem,
                :entidade,
                :entidade_id,
                :acao,
                :resumo,
                :detalhes_json,
                :criado_por
            )
        ");

        return $stmt->execute([
            'origem' => $origem,
            'entidade' => $entidade,
            'entidade_id' => $entidadeId,
            'acao' => $acao,
            'resumo' => $resumo,
            'detalhes_json' => $detalhes ? json_encode($detalhes, JSON_UNESCAPED_UNICODE) : null,
            'criado_por' => $criadoPor,
        ]);
    }

    public function listarRecentes(int $limite = 40): array
    {
        $stmt = $this->db->prepare("
            SELECT aa.*,
                   COALESCE(o.nome_historico, o.nome) AS criado_por_nome
            FROM public.auditoria_administrativa aa
            LEFT JOIN public.obreiros o ON o.id::text = aa.criado_por
            ORDER BY aa.created_at DESC, aa.id DESC
            LIMIT :limite
        ");
        $stmt->bindValue(':limite', max(1, $limite), PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
