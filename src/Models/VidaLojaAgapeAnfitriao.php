<?php

namespace App\Models;

use App\Config\Database;
use App\Core\Tenant\ResolvesStoreTenant;
use PDO;

class VidaLojaAgapeAnfitriao
{
    use ResolvesStoreTenant;

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Lista todos os anfitriões ativos designados para uma determinada sessão.
     */
    public function listarPorSessao(int $sessaoId): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                a.id,
                a.sessao_id,
                a.anfitriao_obreiro_id,
                a.acolhido_obreiro_id,
                a.visitante_nome,
                a.foco,
                a.observacao,
                a.created_at,
                COALESCE(o1.nome_historico, o1.nome) AS anfitriao_nome,
                COALESCE(o2.nome_historico, o2.nome) AS acolhido_nome
            FROM public.vida_loja_agape_anfitrioes a
            JOIN public.obreiros o1 ON o1.id = a.anfitriao_obreiro_id
            LEFT JOIN public.obreiros o2 ON o2.id = a.acolhido_obreiro_id
            WHERE a.sessao_id = :sessao_id
              AND a.loja_id = :loja_id
              AND a.deleted_at IS NULL
            ORDER BY a.created_at ASC
        ");
        $stmt->execute([
            'sessao_id' => $sessaoId,
            'loja_id' => $this->obterLojaAtualId(),
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Adiciona uma nova designação de anfitrião.
     */
    public function adicionar(int $sessaoId, array $dados, string $createdByUuid): bool
    {
        $anfitriaoObreiroId = trim((string) ($dados['anfitriao_obreiro_id'] ?? ''));
        $acolhidoObreiroId = trim((string) ($dados['acolhido_obreiro_id'] ?? ''));
        $visitanteNome = trim((string) ($dados['visitante_nome'] ?? ''));
        $foco = trim((string) ($dados['foco'] ?? 'integracao'));
        $observacao = trim((string) ($dados['observacao'] ?? ''));

        $acolhidoId = ($acolhidoObreiroId !== '') ? $acolhidoObreiroId : null;
        $visNome = ($visitanteNome !== '') ? $visitanteNome : null;
        $criadoPor = ($createdByUuid !== '') ? $createdByUuid : null;

        // Validação da check constraint no PHP para evitar erros desnecessários antes da query
        if ($acolhidoId === null && $visNome === null && $foco !== 'geral') {
            return false;
        }

        $stmt = $this->db->prepare("
            INSERT INTO public.vida_loja_agape_anfitrioes (
                loja_id,
                sessao_id,
                anfitriao_obreiro_id,
                acolhido_obreiro_id,
                visitante_nome,
                foco,
                observacao,
                created_by
            ) VALUES (
                :loja_id,
                :sessao_id,
                :anfitriao_obreiro_id,
                :acolhido_obreiro_id,
                :visitante_nome,
                :foco,
                :observacao,
                :created_by
            )
        ");

        return $stmt->execute([
            'loja_id' => $this->obterLojaAtualId(),
            'sessao_id' => $sessaoId,
            'anfitriao_obreiro_id' => $anfitriaoObreiroId,
            'acolhido_obreiro_id' => $acolhidoId,
            'visitante_nome' => $visNome,
            'foco' => $foco,
            'observacao' => $observacao !== '' ? $observacao : null,
            'created_by' => $criadoPor,
        ]);
    }

    /**
     * Realiza soft delete de uma designação de anfitrião.
     */
    public function remover(int $id, string $deletedByUuid): bool
    {
        $stmt = $this->db->prepare("
            UPDATE public.vida_loja_agape_anfitrioes
            SET deleted_at = NOW(),
                deleted_by = :deleted_by
            WHERE id = :id
              AND loja_id = :loja_id
              AND deleted_at IS NULL
        ");

        return $stmt->execute([
            'id' => $id,
            'deleted_by' => $deletedByUuid !== '' ? $deletedByUuid : null,
            'loja_id' => $this->obterLojaAtualId(),
        ]);
    }

    private function obterLojaAtualId(): int
    {
        return $this->resolveCurrentStoreId($this->db);
    }
}
