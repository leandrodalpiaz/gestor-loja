<?php

namespace App\Models;

use App\Config\Database;
use App\Core\Tenant\ResolvesStoreTenant;
use PDO;

class PresencaSessao
{
    use ResolvesStoreTenant;

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function registrar(
        int $sessaoId,
        string $obreiroId,
        bool $presente = true,
        ?string $registradoPor = null,
        ?string $observacao = null
    ): bool {
        $stmt = $this->db->prepare("
            INSERT INTO public.presencas_sessao (
                sessao_id,
                obreiro_id,
                presente,
                observacao,
                registrado_por,
                registrado_em,
                updated_at
            )
            SELECT
                :sessao_id,
                :obreiro_id,
                :presente,
                :observacao,
                :registrado_por,
                NOW(),
                NOW()
            FROM public.sessoes s
            WHERE s.id = :sessao_id
              AND s.loja_id = :loja_id
            ON CONFLICT (sessao_id, obreiro_id)
            DO UPDATE SET
                presente = EXCLUDED.presente,
                observacao = EXCLUDED.observacao,
                registrado_por = EXCLUDED.registrado_por,
                registrado_em = NOW(),
                updated_at = NOW()
        ");

        return $stmt->execute([
            'sessao_id' => $sessaoId,
            'obreiro_id' => $obreiroId,
            'presente' => $presente ? 'true' : 'false',
            'observacao' => $observacao,
            'registrado_por' => $registradoPor,
            'loja_id' => $this->obterLojaAtualId(),
        ]);
    }

    public function listarPresentesPorSessao(int $sessaoId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                ps.id,
                ps.sessao_id,
                ps.obreiro_id,
                ps.presente,
                ps.observacao,
                ps.registrado_em,
                COALESCE(o.nome_historico, o.nome) AS nome,
                o.cim
            FROM public.presencas_sessao ps
            JOIN public.sessoes s ON s.id = ps.sessao_id
            JOIN public.obreiros o ON o.id = ps.obreiro_id
            WHERE ps.sessao_id = :sessao_id
              AND s.loja_id = :loja_id
              AND ps.presente = TRUE
            ORDER BY nome ASC
        ");
        $stmt->execute([
            'sessao_id' => $sessaoId,
            'loja_id' => $this->obterLojaAtualId(),
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarMapaPorSessao(int $sessaoId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                o.id,
                COALESCE(o.nome_historico, o.nome) AS nome,
                o.cim,
                o.grau,
                COALESCE(ps.presente, FALSE) AS presente,
                ps.observacao
            FROM public.obreiros o
            LEFT JOIN public.presencas_sessao ps
              ON ps.sessao_id = :sessao_id
             AND ps.obreiro_id = o.id
            JOIN public.sessoes s
              ON s.id = :sessao_id
            WHERE o.ativo = TRUE
              AND o.loja_id = s.loja_id
              AND s.loja_id = :loja_id
            ORDER BY nome ASC
        ");
        $stmt->execute([
            'sessao_id' => $sessaoId,
            'loja_id' => $this->obterLojaAtualId(),
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function obterLojaAtualId(): int
    {
        return $this->resolveCurrentStoreId($this->db);
    }
}
