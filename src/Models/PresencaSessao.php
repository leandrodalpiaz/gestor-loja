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
            INSERT INTO presencas_sessao (
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
            FROM sessoes s
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
            FROM presencas_sessao ps
            JOIN sessoes s ON s.id = ps.sessao_id
            JOIN obreiros o ON o.id = ps.obreiro_id
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
            FROM obreiros o
            LEFT JOIN presencas_sessao ps
              ON ps.sessao_id = :sessao_id
             AND ps.obreiro_id = o.id
            JOIN sessoes s
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

    public function listarPresencasAgapePorSessao(int $sessaoId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                ps.id,
                ps.sessao_id,
                ps.obreiro_id,
                ps.presente,
                ps.presente_agape,
                ps.observacao,
                COALESCE(o.nome_historico, o.nome) AS nome,
                o.cim,
                o.grau
            FROM presencas_sessao ps
            JOIN sessoes s ON s.id = ps.sessao_id
            JOIN obreiros o ON o.id = ps.obreiro_id
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

    public function salvarAgape(int $sessaoId, array $presentesAgapeObreiroIds): bool
    {
        // 1. Obter todos os obreiros que estiveram presentes na sessão (presente = true)
        $stmt = $this->db->prepare("
            SELECT obreiro_id 
            FROM presencas_sessao 
            WHERE sessao_id = :sessao_id AND presente = TRUE
        ");
        $stmt->execute(['sessao_id' => $sessaoId]);
        $obreirosSessao = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($obreirosSessao)) {
            // Nenhuma presença registrada na sessão ritualística ainda
            return false;
        }

        // Começa a transação se não estiver em uma existente
        $inTransaction = $this->db->inTransaction();
        if (!$inTransaction) {
            $this->db->beginTransaction();
        }
        try {
            // Primeiro, zera presente_agape para NULL para qualquer obreiro ausente na sessão ritualística
            $stmtReset = $this->db->prepare("
                UPDATE presencas_sessao
                SET presente_agape = NULL
                WHERE sessao_id = :sessao_id AND presente = FALSE
            ");
            $stmtReset->execute(['sessao_id' => $sessaoId]);

            // Agora, para cada obreiro que esteve presente na sessão ritualística, define true ou false
            $stmtUpdate = $this->db->prepare("
                UPDATE presencas_sessao
                SET presente_agape = :presente_agape,
                    updated_at = NOW()
                WHERE sessao_id = :sessao_id AND obreiro_id = :obreiro_id AND presente = TRUE
            ");

            foreach ($obreirosSessao as $obreiroId) {
                $ficouAgape = in_array($obreiroId, $presentesAgapeObreiroIds, true);
                $stmtUpdate->execute([
                    'presente_agape' => $ficouAgape ? 'true' : 'false',
                    'sessao_id' => $sessaoId,
                    'obreiro_id' => $obreiroId
                ]);
            }

            if (!$inTransaction) {
                $this->db->commit();
            }
            return true;
        } catch (\Throwable $e) {
            if (!$inTransaction) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    private function obterLojaAtualId(): int
    {
        return $this->resolveCurrentStoreId($this->db);
    }
}
