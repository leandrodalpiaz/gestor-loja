<?php

namespace App\Models;

use App\Config\Database;
use App\Core\Tenant\ResolvesStoreTenant;
use PDO;

class VidaLojaAcompanhamento
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
            INSERT INTO public.vida_loja_acompanhamentos (
                loja_id,
                sinal_id,
                obreiro_id,
                data_contato,
                meio_contato,
                resultado,
                proximo_acompanhamento,
                nivel_sigilo,
                observacoes_sigilosas,
                responsavel_id,
                created_at,
                updated_at
            ) VALUES (
                :loja_id,
                :sinal_id,
                :obreiro_id,
                :data_contato,
                :meio_contato,
                :resultado,
                :proximo_acompanhamento,
                :nivel_sigilo,
                :observacoes_sigilosas,
                :responsavel_id,
                NOW(),
                NOW()
            )
        ");

        $sinalId = !empty($payload['sinal_id']) ? (int) $payload['sinal_id'] : null;
        $sigilo = trim((string) ($payload['nivel_sigilo'] ?? 'reservado'));
        $meio = trim((string) ($payload['meio_contato'] ?? 'whatsapp'));

        return $stmt->execute([
            'loja_id' => $this->obterLojaAtualId(),
            'sinal_id' => $sinalId,
            'obreiro_id' => $this->limparUuid($payload['obreiro_id'] ?? null),
            'data_contato' => trim((string) ($payload['data_contato'] ?? date('Y-m-d'))),
            'meio_contato' => in_array($meio, ['telefone', 'whatsapp', 'presencial', 'visita', 'outro'], true) ? $meio : 'whatsapp',
            'resultado' => trim((string) ($payload['resultado'] ?? '')),
            'proximo_acompanhamento' => $this->limparData($payload['proximo_acompanhamento'] ?? null),
            'nivel_sigilo' => in_array($sigilo, ['administrativo', 'reservado', 'sigiloso'], true) ? $sigilo : 'reservado',
            'observacoes_sigilosas' => $this->limparTexto($payload['observacoes_sigilosas'] ?? null),
            'responsavel_id' => $this->limparUuid($payload['responsavel_id'] ?? null),
        ]);
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT a.*, 
                   COALESCE(o.nome_historico, o.nome) AS obreiro_nome,
                   COALESCE(resp.nome_historico, resp.nome) AS responsavel_nome
            FROM public.vida_loja_acompanhamentos a
            JOIN public.obreiros o ON o.id = a.obreiro_id
            JOIN public.obreiros resp ON resp.id = a.responsavel_id
            WHERE a.id = :id
              AND a.loja_id = :loja_id
              AND a.deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->execute([
            'id' => $id,
            'loja_id' => $this->obterLojaAtualId(),
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function listarPorObreiro(string $obreiroId, bool $podeVerSigilosos, ?string $usuarioAutenticadoId = null): array
    {
        $stmt = $this->db->prepare("
            SELECT a.*,
                   COALESCE(resp.nome_historico, resp.nome) AS responsavel_nome
            FROM public.vida_loja_acompanhamentos a
            JOIN public.obreiros resp ON resp.id = a.responsavel_id
            WHERE a.obreiro_id = :obreiro_id
              AND a.loja_id = :loja_id
              AND a.deleted_at IS NULL
            ORDER BY a.data_contato DESC, a.created_at DESC
        ");
        $stmt->execute([
            'obreiro_id' => $this->limparUuid($obreiroId),
            'loja_id' => $this->obterLojaAtualId(),
        ]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->filtrarSigiloLista($rows, $podeVerSigilosos, $usuarioAutenticadoId);
    }

    public function listarPorLoja(bool $podeVerSigilosos, ?string $usuarioAutenticadoId = null, int $limite = 50): array
    {
        $limite = max(1, min($limite, 300));
        $stmt = $this->db->prepare("
            SELECT a.*,
                   COALESCE(o.nome_historico, o.nome) AS obreiro_nome,
                   o.cim AS obreiro_cim,
                   COALESCE(resp.nome_historico, resp.nome) AS responsavel_nome
            FROM public.vida_loja_acompanhamentos a
            JOIN public.obreiros o ON o.id = a.obreiro_id
            JOIN public.obreiros resp ON resp.id = a.responsavel_id
            WHERE a.loja_id = :loja_id
              AND a.deleted_at IS NULL
            ORDER BY a.data_contato DESC, a.created_at DESC
            LIMIT :limite
        ");
        $stmt->bindValue(':loja_id', $this->obterLojaAtualId(), PDO::PARAM_INT);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->filtrarSigiloLista($rows, $podeVerSigilosos, $usuarioAutenticadoId);
    }

    public function softDelete(int $id, ?string $autorId, string $motivo): bool
    {
        $stmt = $this->db->prepare("
            UPDATE public.vida_loja_acompanhamentos
            SET deleted_at = NOW(),
                deleted_by = :deleted_by,
                motivo_exclusao = :motivo_exclusao,
                updated_at = NOW()
            WHERE id = :id
              AND loja_id = :loja_id
              AND deleted_at IS NULL
        ");

        return $stmt->execute([
            'id' => $id,
            'deleted_by' => $this->limparUuid($autorId),
            'motivo_exclusao' => trim($motivo) !== '' ? trim($motivo) : 'Exclusão via painel assistencial',
            'loja_id' => $this->obterLojaAtualId(),
        ]);
    }

    private function filtrarSigiloLista(array $rows, bool $podeVerSigilosos, ?string $usuarioAutenticadoId = null): array
    {
        $usuarioAutenticadoId = $this->limparUuid($usuarioAutenticadoId);

        foreach ($rows as &$row) {
            $nivelSigilo = (string) ($row['nivel_sigilo'] ?? 'reservado');

            if ($nivelSigilo === 'administrativo') {
                continue;
            }

            // Para 'reservado' ou 'sigiloso', se não tiver permissão explícita de ver sigilo, censura as notas
            // No caso de 'sigiloso', mesmo que tenha a permissão, apenas o próprio autor (responsavel_id) ou o Venerável podem ler as observações sensíveis.
            // Aqui simplificamos: se $podeVerSigilosos é false, oculta.
            // Se o nível for 'sigiloso' e quem está visualizando não for o autor (responsavel_id) e nem for admin/veneravel (representado por $podeVerSigilosos), oculta.
            $podeVisualizar = $podeVerSigilosos;

            // Se for o próprio criador do acompanhamento, ele sempre pode visualizar as observações detalhadas
            if ($usuarioAutenticadoId !== null && $usuarioAutenticadoId === $row['responsavel_id']) {
                $podeVisualizar = true;
            }

            if (!$podeVisualizar) {
                $row['observacoes_sigilosas'] = '[Registro Confidencial da Hospitalaria]';
            }
        }
        unset($row);

        return $rows;
    }

    private function limparUuid(?string $uuid): ?string
    {
        if ($uuid === null || trim($uuid) === '') {
            return null;
        }
        $uuid = trim($uuid);
        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $uuid) === 1) {
            return $uuid;
        }
        return null;
    }

    private function limparData(?string $data): ?string
    {
        $data = trim((string) $data);
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $data) ? $data : null;
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
