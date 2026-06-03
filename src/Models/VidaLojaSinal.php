<?php

namespace App\Models;

use App\Config\Database;
use App\Core\Tenant\ResolvesStoreTenant;
use PDO;

class VidaLojaSinal
{
    use ResolvesStoreTenant;

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function criar(array $payload): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO public.vida_loja_sinais (
                loja_id,
                obreiro_id,
                tipo,
                status,
                nivel,
                detalhes,
                created_at,
                updated_at
            ) VALUES (
                :loja_id,
                :obreiro_id,
                :tipo,
                :status,
                :nivel,
                :detalhes,
                NOW(),
                NOW()
            )
        ");

        $status = trim((string) ($payload['status'] ?? 'aberto'));
        $nivel = trim((string) ($payload['nivel'] ?? 'baixo'));

        return $stmt->execute([
            'loja_id' => $this->obterLojaAtualId(),
            'obreiro_id' => $this->limparUuid($payload['obreiro_id'] ?? null),
            'tipo' => trim((string) ($payload['tipo'] ?? 'ausencias_consecutivas')),
            'status' => in_array($status, ['aberto', 'em_observacao', 'resolvido', 'arquivado'], true) ? $status : 'aberto',
            'nivel' => in_array($nivel, ['baixo', 'medio', 'alto'], true) ? $nivel : 'baixo',
            'detalhes' => $this->limparTexto($payload['detalhes'] ?? null),
        ]);
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT s.*, 
                   COALESCE(o.nome_historico, o.nome) AS obreiro_nome,
                   o.cim AS obreiro_cim,
                   COALESCE(res.nome_historico, res.nome) AS resolvedor_nome
            FROM public.vida_loja_sinais s
            JOIN public.obreiros o ON o.id = s.obreiro_id
            LEFT JOIN public.obreiros res ON res.id = s.resolved_by
            WHERE s.id = :id
              AND s.loja_id = :loja_id
            LIMIT 1
        ");
        $stmt->execute([
            'id' => $id,
            'loja_id' => $this->obterLojaAtualId(),
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function buscarAbertosPorLoja(): array
    {
        $stmt = $this->db->prepare("
            SELECT s.*, 
                   COALESCE(o.nome_historico, o.nome) AS obreiro_nome,
                   o.cim AS obreiro_cim,
                   o.grau AS obreiro_grau
            FROM public.vida_loja_sinais s
            JOIN public.obreiros o ON o.id = s.obreiro_id
            WHERE s.loja_id = :loja_id
              AND s.status IN ('aberto', 'em_observacao')
            ORDER BY 
                CASE s.nivel
                    WHEN 'alto' THEN 1
                    WHEN 'medio' THEN 2
                    ELSE 3
                END ASC,
                s.created_at DESC
        ");
        $stmt->execute(['loja_id' => $this->obterLojaAtualId()]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarPorObreiro(string $obreiroId): array
    {
        $stmt = $this->db->prepare("
            SELECT s.*,
                   COALESCE(res.nome_historico, res.nome) AS resolvedor_nome
            FROM public.vida_loja_sinais s
            LEFT JOIN public.obreiros res ON res.id = s.resolved_by
            WHERE s.obreiro_id = :obreiro_id
              AND s.loja_id = :loja_id
            ORDER BY s.created_at DESC
        ");
        $stmt->execute([
            'obreiro_id' => $this->limparUuid($obreiroId),
            'loja_id' => $this->obterLojaAtualId(),
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function sinalAbertoExiste(string $obreiroId, string $tipo): bool
    {
        $stmt = $this->db->prepare("
            SELECT 1
            FROM public.vida_loja_sinais
            WHERE loja_id = :loja_id
              AND obreiro_id = :obreiro_id
              AND tipo = :tipo
              AND status IN ('aberto', 'em_observacao')
            LIMIT 1
        ");
        $stmt->execute([
            'loja_id' => $this->obterLojaAtualId(),
            'obreiro_id' => $this->limparUuid($obreiroId),
            'tipo' => trim($tipo),
        ]);

        return (bool) $stmt->fetchColumn();
    }

    public function obterSinalAberto(string $obreiroId, string $tipo): ?array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM public.vida_loja_sinais
            WHERE loja_id = :loja_id
              AND obreiro_id = :obreiro_id
              AND tipo = :tipo
              AND status IN ('aberto', 'em_observacao')
            LIMIT 1
        ");
        $stmt->execute([
            'loja_id' => $this->obterLojaAtualId(),
            'obreiro_id' => $this->limparUuid($obreiroId),
            'tipo' => trim($tipo),
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function atualizarStatus(int $id, string $status, ?string $autorId = null, ?string $motivo = null): bool
    {
        $status = trim(strtolower($status));
        $validos = ['aberto', 'em_observacao', 'resolvido', 'arquivado'];
        if (!in_array($status, $validos, true)) {
            return false;
        }

        $autorUuid = $this->limparUuid($autorId);
        $sql = "
            UPDATE public.vida_loja_sinais
            SET status = :status,
                updated_at = NOW()
        ";

        $params = [
            'id' => $id,
            'status' => $status,
            'loja_id' => $this->obterLojaAtualId(),
        ];

        if (in_array($status, ['resolvido', 'arquivado'], true)) {
            $sql .= ", resolved_at = NOW(),
                       resolved_by = :resolved_by,
                       motivo_resolucao = :motivo_resolucao";
            $params['resolved_by'] = $autorUuid;
            $params['motivo_resolucao'] = $this->limparTexto($motivo);
        } else {
            // Se reabriu, zera a resolução
            $sql .= ", resolved_at = NULL,
                       resolved_by = NULL,
                       motivo_resolucao = NULL";
        }

        $sql .= " WHERE id = :id AND loja_id = :loja_id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    private function limparUuid(?string $uuid): ?string
    {
        if ($uuid === null || trim($uuid) === '') {
            return null;
        }
        $uuid = trim($uuid);
        // Garante formato UUID v4 compatível
        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $uuid) === 1) {
            return $uuid;
        }
        return null;
    }

    private function limparTexto(?string $texto): ?string
    {
        if ($texto === null) {
            return null;
        }
        $texto = trim($texto);
        return $texto !== '' ? $texto : null;
    }

    private function obterLojaAtualId(): int
    {
        return $this->resolveCurrentStoreId($this->db);
    }
}
