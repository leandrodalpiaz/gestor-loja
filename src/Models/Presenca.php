<?php

namespace App\Models;

use App\Config\Database;
use App\Core\Tenant\ResolvesStoreTenant;
use PDO;

class Presenca
{
    use ResolvesStoreTenant;

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Registra ou atualiza a confirmacao do obreiro para uma sessao.
     * Mantem o nome da classe por compatibilidade com o fluxo ja existente do bot.
     */
    public function registrar(
        int $sessaoId,
        string $obreiroId,
        string $status,
        bool $participaraAgape = false,
        ?string $observacao = null
    ): bool {
        $statusNormalizado = $this->normalizarStatus($status);

        $stmt = $this->db->prepare("
            INSERT INTO public.confirmacoes_sessao (
                sessao_id,
                obreiro_id,
                status_confirmacao,
                participara_agape,
                observacao,
                respondido_em,
                updated_at
            )
            SELECT
                :sessao_id,
                :obreiro_id,
                :status_confirmacao,
                :participara_agape,
                :observacao,
                NOW(),
                NOW()
            FROM public.sessoes s
            WHERE s.id = :sessao_id
              AND s.loja_id = :loja_id
            ON CONFLICT (sessao_id, obreiro_id)
            DO UPDATE SET
                status_confirmacao = EXCLUDED.status_confirmacao,
                participara_agape = EXCLUDED.participara_agape,
                observacao = EXCLUDED.observacao,
                respondido_em = NOW(),
                updated_at = NOW()
        ");

        return $stmt->execute([
            'sessao_id' => $sessaoId,
            'obreiro_id' => $obreiroId,
            'status_confirmacao' => $statusNormalizado,
            'participara_agape' => ($statusNormalizado === 'confirmado' ? $participaraAgape : false) ? 'true' : 'false',
            'observacao' => $observacao,
            'loja_id' => $this->obterLojaAtualId(),
        ]);
    }

    public function cancelar(int $sessaoId, string $obreiroId, ?string $observacao = null): bool
    {
        $stmt = $this->db->prepare("
            DELETE FROM public.confirmacoes_sessao
            WHERE sessao_id = :sessao_id
              AND obreiro_id = :obreiro_id
              AND EXISTS (
                  SELECT 1
                  FROM public.sessoes s
                  WHERE s.id = :sessao_id
                    AND s.loja_id = :loja_id
              )
        ");

        return $stmt->execute([
            'sessao_id' => $sessaoId,
            'obreiro_id' => $obreiroId,
            'loja_id' => $this->obterLojaAtualId(),
        ]);
    }

    public function obterResposta(int $sessaoId, string $obreiroId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM public.confirmacoes_sessao
            WHERE sessao_id = :sessao_id
              AND obreiro_id = :obreiro_id
              AND EXISTS (
                  SELECT 1
                  FROM public.sessoes s
                  WHERE s.id = :sessao_id
                    AND s.loja_id = :loja_id
              )
            LIMIT 1
        ");
        $stmt->execute([
            'sessao_id' => $sessaoId,
            'obreiro_id' => $obreiroId,
            'loja_id' => $this->obterLojaAtualId(),
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function listarConfirmadosPorSessao(int $sessaoId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                cs.id,
                cs.sessao_id,
                cs.obreiro_id,
                cs.status_confirmacao,
                cs.participara_agape,
                cs.observacao,
                cs.respondido_em,
                COALESCE(o.nome_historico, o.nome) AS nome,
                o.cim
            FROM public.confirmacoes_sessao cs
            JOIN public.sessoes s ON s.id = cs.sessao_id
            JOIN public.obreiros o ON o.id = cs.obreiro_id
            WHERE cs.sessao_id = :sessao_id
              AND s.loja_id = :loja_id
              AND cs.status_confirmacao = 'confirmado'
            ORDER BY nome ASC
        ");
        $stmt->execute([
            'sessao_id' => $sessaoId,
            'loja_id' => $this->obterLojaAtualId(),
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function contarAusentesPorSessao(int $sessaoId): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM public.confirmacoes_sessao cs
            JOIN public.sessoes s ON s.id = cs.sessao_id
            WHERE cs.sessao_id = :sessao_id
              AND s.loja_id = :loja_id
              AND status_confirmacao = 'ausente'
        ");
        $stmt->execute([
            'sessao_id' => $sessaoId,
            'loja_id' => $this->obterLojaAtualId(),
        ]);

        return (int) $stmt->fetchColumn();
    }

    public function listarParticipantesAgapePorSessao(int $sessaoId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                cs.id,
                cs.sessao_id,
                cs.obreiro_id,
                cs.observacao,
                cs.respondido_em,
                COALESCE(o.nome_historico, o.nome) AS nome,
                o.cim
            FROM public.confirmacoes_sessao cs
            JOIN public.sessoes s ON s.id = cs.sessao_id
            JOIN public.obreiros o ON o.id = cs.obreiro_id
            WHERE cs.sessao_id = :sessao_id
              AND s.loja_id = :loja_id
              AND cs.status_confirmacao = 'confirmado'
              AND cs.participara_agape = TRUE
            ORDER BY nome ASC
        ");
        $stmt->execute([
            'sessao_id' => $sessaoId,
            'loja_id' => $this->obterLojaAtualId(),
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function contarParticipantesAgapePorSessao(int $sessaoId): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM public.confirmacoes_sessao cs
            JOIN public.sessoes s ON s.id = cs.sessao_id
            WHERE cs.sessao_id = :sessao_id
              AND s.loja_id = :loja_id
              AND status_confirmacao = 'confirmado'
              AND participara_agape = TRUE
        ");
        $stmt->execute([
            'sessao_id' => $sessaoId,
            'loja_id' => $this->obterLojaAtualId(),
        ]);

        return (int) $stmt->fetchColumn();
    }

    public function contarConfirmadosPorSessao(int $sessaoId): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM public.confirmacoes_sessao cs
            JOIN public.sessoes s ON s.id = cs.sessao_id
            WHERE cs.sessao_id = :sessao_id
              AND s.loja_id = :loja_id
              AND status_confirmacao = 'confirmado'
        ");
        $stmt->execute([
            'sessao_id' => $sessaoId,
            'loja_id' => $this->obterLojaAtualId(),
        ]);

        return (int) $stmt->fetchColumn();
    }

    private function obterLojaAtualId(): int
    {
        return $this->resolveCurrentStoreId($this->db);
    }

    private function normalizarStatus(string $status): string
    {
        $status = strtolower(trim($status));

        return match ($status) {
            'confirmado', 'presente', 'sim' => 'confirmado',
            'ausente', 'nao', 'não', 'cancelado' => 'ausente',
            default => 'confirmado',
        };
    }
}
