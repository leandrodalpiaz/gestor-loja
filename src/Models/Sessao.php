<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class Sessao
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT
                s.*,
                COALESCE(confirmados.total_confirmados, 0) AS total_confirmados,
                COALESCE(confirmados.total_ausentes, 0) AS total_ausentes,
                COALESCE(confirmados.total_agape, 0) AS total_agape,
                COALESCE(presentes.total_presentes, 0) AS total_presentes
            FROM public.sessoes s
            LEFT JOIN (
                SELECT
                    sessao_id,
                    COUNT(*) FILTER (WHERE status_confirmacao = 'confirmado') AS total_confirmados,
                    COUNT(*) FILTER (WHERE status_confirmacao = 'ausente') AS total_ausentes,
                    COUNT(*) FILTER (WHERE status_confirmacao = 'confirmado' AND participara_agape = TRUE) AS total_agape
                FROM public.confirmacoes_sessao
                GROUP BY sessao_id
            ) confirmados ON confirmados.sessao_id = s.id
            LEFT JOIN (
                SELECT
                    sessao_id,
                    COUNT(*) FILTER (WHERE presente = TRUE) AS total_presentes
                FROM public.presencas_sessao
                GROUP BY sessao_id
            ) presentes ON presentes.sessao_id = s.id
            WHERE s.id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        $sessao = $stmt->fetch(PDO::FETCH_ASSOC);

        return $sessao ?: null;
    }

    public function obterProximaSessao(): ?array
    {
        $stmt = $this->db->prepare("
            SELECT
                id,
                data_hora_inicio,
                data_hora_fim,
                tipo_sessao,
                grau_sessao,
                titulo,
                resumo_publico,
                status,
                agape_ativo,
                publicada_em
            FROM public.sessoes
            WHERE data_hora_inicio >= NOW()
              AND status IN ('planejada', 'publicada', 'alterada')
            ORDER BY data_hora_inicio ASC
            LIMIT 1
        ");
        $stmt->execute();
        $sessao = $stmt->fetch(PDO::FETCH_ASSOC);

        return $sessao ?: null;
    }

    public function listarFuturas(int $limite = 50): array
    {
        $limite = max(1, min($limite, 300));
        $stmt = $this->db->prepare("
            SELECT
                s.id,
                s.data_hora_inicio,
                s.data_hora_fim,
                s.tipo_sessao,
                s.grau_sessao,
                s.titulo,
                s.status,
                s.agape_ativo,
                COALESCE(confirmados.total_confirmados, 0) AS total_confirmados,
                COALESCE(confirmados.total_agape, 0) AS total_agape
            FROM public.sessoes s
            LEFT JOIN (
                SELECT
                    sessao_id,
                    COUNT(*) FILTER (WHERE status_confirmacao = 'confirmado') AS total_confirmados,
                    COUNT(*) FILTER (WHERE status_confirmacao = 'confirmado' AND participara_agape = TRUE) AS total_agape
                FROM public.confirmacoes_sessao
                GROUP BY sessao_id
            ) confirmados ON confirmados.sessao_id = s.id
            WHERE s.data_hora_inicio >= NOW()
              AND s.status IN ('planejada', 'publicada', 'alterada', 'cancelada')
            ORDER BY s.data_hora_inicio ASC
            LIMIT :limite
        ");
        $stmt->bindValue('limite', $limite, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function criar(array $data, ?string $autorId = null): ?int
    {
        $payload = $this->normalizarPayload($data);
        $sql = "
            INSERT INTO public.sessoes (
                data,
                grau,
                tipo,
                pauta,
                data_hora_inicio,
                data_hora_fim,
                tipo_sessao,
                grau_sessao,
                titulo,
                resumo_publico,
                observacao_interna,
                status,
                agape_ativo,
                criada_por,
                atualizada_por,
                updated_at
            ) VALUES (
                :data_legado,
                :grau_legado,
                :tipo_legado,
                :pauta_legado,
                :data_hora_inicio,
                :data_hora_fim,
                :tipo_sessao,
                :grau_sessao,
                :titulo,
                :resumo_publico,
                :observacao_interna,
                :status,
                :agape_ativo,
                :criada_por,
                :atualizada_por,
                NOW()
            )
            RETURNING id
        ";

        $stmt = $this->db->prepare($sql);
        $ok = $stmt->execute([
            'data_legado' => $payload['data_hora_inicio'],
            'grau_legado' => $payload['grau_sessao'],
            'tipo_legado' => $payload['tipo_sessao'],
            'pauta_legado' => $payload['resumo_publico'],
            'data_hora_inicio' => $payload['data_hora_inicio'],
            'data_hora_fim' => $payload['data_hora_fim'],
            'tipo_sessao' => $payload['tipo_sessao'],
            'grau_sessao' => $payload['grau_sessao'],
            'titulo' => $payload['titulo'],
            'resumo_publico' => $payload['resumo_publico'],
            'observacao_interna' => $payload['observacao_interna'],
            'status' => $payload['status'],
            'agape_ativo' => $payload['agape_ativo'],
            'criada_por' => $autorId,
            'atualizada_por' => $autorId,
        ]);

        if (!$ok) {
            return null;
        }

        $sessaoId = (int) $stmt->fetchColumn();
        $this->registrarHistorico($sessaoId, 'sessao_criada', null, $payload, $autorId, 'Criacao da sessao');

        return $sessaoId;
    }

    public function atualizar(int $id, array $data, ?string $autorId = null, ?string $observacao = null): bool
    {
        $anterior = $this->findById($id);
        if (!$anterior) {
            return false;
        }

        $payload = $this->normalizarPayload($data, $anterior);
        $status = $anterior['status'] === 'publicada' ? 'alterada' : ($payload['status'] ?? $anterior['status']);

        $stmt = $this->db->prepare("
            UPDATE public.sessoes
               SET data = :data_legado,
                   grau = :grau_legado,
                   tipo = :tipo_legado,
                   pauta = :pauta_legado,
                   data_hora_inicio = :data_hora_inicio,
                   data_hora_fim = :data_hora_fim,
                   tipo_sessao = :tipo_sessao,
                   grau_sessao = :grau_sessao,
                   titulo = :titulo,
                   resumo_publico = :resumo_publico,
                   observacao_interna = :observacao_interna,
                   status = :status,
                   agape_ativo = :agape_ativo,
                   atualizada_por = :atualizada_por,
                   updated_at = NOW()
             WHERE id = :id
        ");

        $ok = $stmt->execute([
            'id' => $id,
            'data_legado' => $payload['data_hora_inicio'],
            'grau_legado' => $payload['grau_sessao'],
            'tipo_legado' => $payload['tipo_sessao'],
            'pauta_legado' => $payload['resumo_publico'],
            'data_hora_inicio' => $payload['data_hora_inicio'],
            'data_hora_fim' => $payload['data_hora_fim'],
            'tipo_sessao' => $payload['tipo_sessao'],
            'grau_sessao' => $payload['grau_sessao'],
            'titulo' => $payload['titulo'],
            'resumo_publico' => $payload['resumo_publico'],
            'observacao_interna' => $payload['observacao_interna'],
            'status' => $status,
            'agape_ativo' => $payload['agape_ativo'],
            'atualizada_por' => $autorId,
        ]);

        if ($ok) {
            $payload['status'] = $status;
            $this->registrarHistorico($id, 'sessao_atualizada', $anterior, $payload, $autorId, $observacao);
        }

        return $ok;
    }

    public function cancelar(int $id, ?string $autorId = null, ?string $observacao = null): bool
    {
        $anterior = $this->findById($id);
        if (!$anterior) {
            return false;
        }

        $stmt = $this->db->prepare("
            UPDATE public.sessoes
               SET status = 'cancelada',
                   cancelada_em = NOW(),
                   atualizada_por = :atualizada_por,
                   updated_at = NOW()
             WHERE id = :id
        ");
        $ok = $stmt->execute([
            'id' => $id,
            'atualizada_por' => $autorId,
        ]);

        if ($ok) {
            $novo = $this->findById($id);
            $this->registrarHistorico($id, 'sessao_cancelada', $anterior, $novo, $autorId, $observacao);
        }

        return $ok;
    }

    public function reabrir(int $id, ?string $autorId = null, ?string $observacao = null): bool
    {
        $anterior = $this->findById($id);
        if (!$anterior) {
            return false;
        }

        $stmt = $this->db->prepare("
            UPDATE public.sessoes
               SET status = CASE WHEN publicada_em IS NULL THEN 'planejada' ELSE 'alterada' END,
                   cancelada_em = NULL,
                   atualizada_por = :atualizada_por,
                   updated_at = NOW()
             WHERE id = :id
        ");
        $ok = $stmt->execute([
            'id' => $id,
            'atualizada_por' => $autorId,
        ]);

        if ($ok) {
            $novo = $this->findById($id);
            $this->registrarHistorico($id, 'sessao_reaberta', $anterior, $novo, $autorId, $observacao);
        }

        return $ok;
    }

    public function marcarPublicada(int $id, ?string $autorId = null, ?string $observacao = null): bool
    {
        $anterior = $this->findById($id);
        if (!$anterior) {
            return false;
        }

        $stmt = $this->db->prepare("
            UPDATE public.sessoes
               SET status = 'publicada',
                   publicada_em = COALESCE(publicada_em, NOW()),
                   atualizada_por = :atualizada_por,
                   updated_at = NOW()
             WHERE id = :id
        ");
        $ok = $stmt->execute([
            'id' => $id,
            'atualizada_por' => $autorId,
        ]);

        if ($ok) {
            $novo = $this->findById($id);
            $this->registrarHistorico($id, 'sessao_publicada', $anterior, $novo, $autorId, $observacao);
        }

        return $ok;
    }

    public function marcarRealizada(int $id, ?string $autorId = null, ?string $observacao = null): bool
    {
        $anterior = $this->findById($id);
        if (!$anterior) {
            return false;
        }

        $stmt = $this->db->prepare("
            UPDATE public.sessoes
               SET status = 'realizada',
                   realizada_em = NOW(),
                   atualizada_por = :atualizada_por,
                   updated_at = NOW()
             WHERE id = :id
        ");
        $ok = $stmt->execute([
            'id' => $id,
            'atualizada_por' => $autorId,
        ]);

        if ($ok) {
            $novo = $this->findById($id);
            $this->registrarHistorico($id, 'sessao_realizada', $anterior, $novo, $autorId, $observacao);
        }

        return $ok;
    }

    public function obterResumoOperacional(int $id): ?array
    {
        $sessao = $this->findById($id);
        if (!$sessao) {
            return null;
        }

        return [
            'sessao' => $sessao,
            'total_confirmados' => (int) ($sessao['total_confirmados'] ?? 0),
            'total_ausentes' => (int) ($sessao['total_ausentes'] ?? 0),
            'total_agape' => (int) ($sessao['total_agape'] ?? 0),
            'total_presentes' => (int) ($sessao['total_presentes'] ?? 0),
        ];
    }

    private function registrarHistorico(
        int $sessaoId,
        string $acao,
        mixed $valorAnterior,
        mixed $valorNovo,
        ?string $autorId = null,
        ?string $observacao = null
    ): void {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO public.historico_sessao (
                    sessao_id,
                    acao,
                    valor_anterior,
                    valor_novo,
                    autor_id,
                    observacao
                ) VALUES (
                    :sessao_id,
                    :acao,
                    CAST(:valor_anterior AS jsonb),
                    CAST(:valor_novo AS jsonb),
                    :autor_id,
                    :observacao
                )
            ");
            $stmt->execute([
                'sessao_id' => $sessaoId,
                'acao' => $acao,
                'valor_anterior' => $valorAnterior !== null ? json_encode($valorAnterior, JSON_UNESCAPED_UNICODE) : null,
                'valor_novo' => $valorNovo !== null ? json_encode($valorNovo, JSON_UNESCAPED_UNICODE) : null,
                'autor_id' => $autorId,
                'observacao' => $observacao,
            ]);
        } catch (\Throwable $e) {
            error_log('Falha ao registrar historico de sessao: ' . $e->getMessage());
        }
    }

    private function normalizarPayload(array $data, ?array $fallback = null): array
    {
        $fallback = $fallback ?? [];

        return [
            'data_hora_inicio' => $data['data_hora_inicio'] ?? $data['data'] ?? $fallback['data_hora_inicio'] ?? null,
            'data_hora_fim' => $data['data_hora_fim'] ?? $fallback['data_hora_fim'] ?? null,
            'tipo_sessao' => trim((string) ($data['tipo_sessao'] ?? $data['tipo'] ?? $fallback['tipo_sessao'] ?? '')) ?: null,
            'grau_sessao' => trim((string) ($data['grau_sessao'] ?? $data['grau'] ?? $fallback['grau_sessao'] ?? '')) ?: null,
            'titulo' => trim((string) ($data['titulo'] ?? $fallback['titulo'] ?? '')) ?: null,
            'resumo_publico' => trim((string) ($data['resumo_publico'] ?? $data['pauta'] ?? $fallback['resumo_publico'] ?? '')) ?: null,
            'observacao_interna' => trim((string) ($data['observacao_interna'] ?? $fallback['observacao_interna'] ?? '')) ?: null,
            'status' => trim((string) ($data['status'] ?? $fallback['status'] ?? 'planejada')) ?: 'planejada',
            'agape_ativo' => filter_var($data['agape_ativo'] ?? $fallback['agape_ativo'] ?? false, FILTER_VALIDATE_BOOL),
        ];
    }
}
