<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class BanqueteOperacao
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
            CREATE TABLE IF NOT EXISTS public.banquete_operacoes (
                id SERIAL PRIMARY KEY,
                sessao_id INT NOT NULL UNIQUE REFERENCES public.sessoes(id) ON DELETE CASCADE,
                status_operacional VARCHAR(30) NOT NULL DEFAULT 'planejamento',
                observacoes TEXT NULL,
                previsao_participantes INT NULL,
                fechado_em TIMESTAMP NULL,
                created_by INT NULL,
                updated_by INT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }

    public function obterPorSessao(int $sessaoId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM public.banquete_operacoes
            WHERE sessao_id = :sessao_id
            LIMIT 1
        ");
        $stmt->execute(['sessao_id' => $sessaoId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function salvar(int $sessaoId, array $dados, ?int $autorId = null): bool
    {
        $status = strtolower(trim((string) ($dados['status_operacional'] ?? 'planejamento')));
        if (!in_array($status, ['planejamento', 'preparacao', 'abastecimento', 'fechado'], true)) {
            $status = 'planejamento';
        }

        $observacoes = trim((string) ($dados['observacoes'] ?? ''));
        $previsao = isset($dados['previsao_participantes']) && $dados['previsao_participantes'] !== ''
            ? max(0, (int) $dados['previsao_participantes'])
            : null;

        $stmt = $this->db->prepare("
            INSERT INTO public.banquete_operacoes (
                sessao_id,
                status_operacional,
                observacoes,
                previsao_participantes,
                fechado_em,
                created_by,
                updated_by,
                updated_at
            ) VALUES (
                :sessao_id,
                :status_operacional,
                :observacoes,
                :previsao_participantes,
                :fechado_em,
                :created_by,
                :updated_by,
                CURRENT_TIMESTAMP
            )
            ON CONFLICT (sessao_id)
            DO UPDATE SET
                status_operacional = EXCLUDED.status_operacional,
                observacoes = EXCLUDED.observacoes,
                previsao_participantes = EXCLUDED.previsao_participantes,
                fechado_em = EXCLUDED.fechado_em,
                updated_by = EXCLUDED.updated_by,
                updated_at = CURRENT_TIMESTAMP
        ");

        return $stmt->execute([
            'sessao_id' => $sessaoId,
            'status_operacional' => $status,
            'observacoes' => $observacoes !== '' ? $observacoes : null,
            'previsao_participantes' => $previsao,
            'fechado_em' => $status === 'fechado' ? date('Y-m-d H:i:s') : null,
            'created_by' => $autorId,
            'updated_by' => $autorId,
        ]);
    }
}
