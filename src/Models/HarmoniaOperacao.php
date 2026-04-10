<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class HarmoniaOperacao
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
            CREATE TABLE IF NOT EXISTS public.harmonia_operacoes (
                id SERIAL PRIMARY KEY,
                sessao_path TEXT NOT NULL UNIQUE,
                sessao_nome VARCHAR(255) NOT NULL,
                operador_nome VARCHAR(255) NULL,
                faixa_atual_id VARCHAR(80) NULL,
                faixa_atual_codigo VARCHAR(40) NULL,
                faixa_atual_titulo VARCHAR(255) NULL,
                status_player VARCHAR(20) NOT NULL DEFAULT 'parado',
                volume_percent INT NOT NULL DEFAULT 100,
                auto_proxima BOOLEAN NOT NULL DEFAULT FALSE,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }

    public function obterPorSessao(string $sessaoPath): ?array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM public.harmonia_operacoes
            WHERE sessao_path = :sessao_path
            LIMIT 1
        ");
        $stmt->execute(['sessao_path' => $sessaoPath]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function salvarEstado(string $sessaoPath, string $sessaoNome, array $dados): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO public.harmonia_operacoes (
                sessao_path,
                sessao_nome,
                operador_nome,
                faixa_atual_id,
                faixa_atual_codigo,
                faixa_atual_titulo,
                status_player,
                volume_percent,
                auto_proxima,
                updated_at
            ) VALUES (
                :sessao_path,
                :sessao_nome,
                :operador_nome,
                :faixa_atual_id,
                :faixa_atual_codigo,
                :faixa_atual_titulo,
                :status_player,
                :volume_percent,
                :auto_proxima,
                CURRENT_TIMESTAMP
            )
            ON CONFLICT (sessao_path)
            DO UPDATE SET
                sessao_nome = EXCLUDED.sessao_nome,
                operador_nome = EXCLUDED.operador_nome,
                faixa_atual_id = EXCLUDED.faixa_atual_id,
                faixa_atual_codigo = EXCLUDED.faixa_atual_codigo,
                faixa_atual_titulo = EXCLUDED.faixa_atual_titulo,
                status_player = EXCLUDED.status_player,
                volume_percent = EXCLUDED.volume_percent,
                auto_proxima = EXCLUDED.auto_proxima,
                updated_at = CURRENT_TIMESTAMP
        ");

        return $stmt->execute([
            'sessao_path' => $sessaoPath,
            'sessao_nome' => $sessaoNome,
            'operador_nome' => $dados['operador_nome'] ?? null,
            'faixa_atual_id' => $dados['faixa_atual_id'] ?? null,
            'faixa_atual_codigo' => $dados['faixa_atual_codigo'] ?? null,
            'faixa_atual_titulo' => $dados['faixa_atual_titulo'] ?? null,
            'status_player' => $dados['status_player'] ?? 'parado',
            'volume_percent' => $dados['volume_percent'] ?? 100,
            'auto_proxima' => !empty($dados['auto_proxima']),
        ]);
    }
}
