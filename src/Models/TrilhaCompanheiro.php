<?php

namespace App\Models;

use App\Config\Database;
use PDO;
use PDOException;

class TrilhaCompanheiro
{
    private PDO $db;
    private ?bool $trilhaDisponivelCache = null;

    private const ETAPAS = [
        1 => 'Entrega das impressões da elevação',
        2 => 'Passar a 1ª instrução',
        3 => 'Passar e receber o trabalho da 1ª instrução',
        4 => 'Passar e receber o trabalho da 2ª instrução',
        5 => 'Passar e receber o trabalho da 3ª instrução',
        6 => 'Registrar a docência',
        7 => 'Solicitar o certificado de conclusão da docência',
        8 => 'Indicar para exaltação ao grau de Mestre',
    ];

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public static function etapas(): array
    {
        return self::ETAPAS;
    }

    public function trilhaDisponivel(): bool
    {
        if ($this->trilhaDisponivelCache !== null) {
            return $this->trilhaDisponivelCache;
        }

        try {
            $stmt = $this->db->query("SELECT to_regclass('public.trilha_companheiro')");
            $valor = $stmt ? (string) $stmt->fetchColumn() : '';
            $this->trilhaDisponivelCache = $valor !== '';
            return $this->trilhaDisponivelCache;
        } catch (PDOException) {
            $this->trilhaDisponivelCache = false;
            return false;
        }
    }

    public function garantirTrilhaBaseParaCompanheiros(array $companheiroIds): void
    {
        if (!$this->trilhaDisponivel()) {
            return;
        }

        $companheiroIds = array_values(array_filter(array_unique(array_map(
            static fn ($id) => trim((string) $id),
            $companheiroIds
        ))));

        if ($companheiroIds === []) {
            return;
        }

        $stmt = $this->db->prepare("
            INSERT INTO public.trilha_companheiro (
                companheiro_id,
                etapa_ordem,
                titulo_etapa,
                status,
                created_at,
                updated_at
            )
            VALUES (
                :companheiro_id,
                :etapa_ordem,
                :titulo_etapa,
                'nao_iniciado',
                NOW(),
                NOW()
            )
            ON CONFLICT (companheiro_id, etapa_ordem) DO NOTHING
        ");

        foreach ($companheiroIds as $companheiroId) {
            foreach (self::ETAPAS as $ordem => $titulo) {
                $stmt->execute([
                    'companheiro_id' => $companheiroId,
                    'etapa_ordem' => $ordem,
                    'titulo_etapa' => $titulo,
                ]);
            }
        }
    }

    public function listarResumoAtualPorCompanheiroIds(array $companheiroIds): array
    {
        if (!$this->trilhaDisponivel()) {
            return [];
        }

        $companheiroIds = array_values(array_filter(array_unique(array_map(
            static fn ($id) => trim((string) $id),
            $companheiroIds
        ))));

        if ($companheiroIds === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($companheiroIds), '?'));
        $sql = "
            SELECT
                t.companheiro_id,
                t.etapa_ordem,
                t.titulo_etapa,
                t.status,
                t.data_disponibilizacao,
                t.data_entrega,
                t.data_revisao
            FROM public.trilha_companheiro t
            JOIN (
                SELECT
                    companheiro_id,
                    COALESCE(
                        MIN(etapa_ordem) FILTER (
                            WHERE status NOT IN ('concluido', 'certificado_solicitado', 'exaltacao_recomendada')
                        ),
                        MAX(etapa_ordem)
                    ) AS etapa_atual
                FROM public.trilha_companheiro
                WHERE companheiro_id IN ($placeholders)
                GROUP BY companheiro_id
            ) atual
              ON atual.companheiro_id = t.companheiro_id
             AND atual.etapa_atual = t.etapa_ordem
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($companheiroIds);

        $resultado = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $resultado[(string) $row['companheiro_id']] = $row;
        }

        return $resultado;
    }

    public function contarPorStatus(array $companheiroIds, string $status): int
    {
        if (!$this->trilhaDisponivel()) {
            return 0;
        }

        $companheiroIds = array_values(array_filter(array_unique(array_map(
            static fn ($id) => trim((string) $id),
            $companheiroIds
        ))));

        if ($companheiroIds === []) {
            return 0;
        }

        $placeholders = implode(', ', array_fill(0, count($companheiroIds), '?'));
        $sql = "
            SELECT COUNT(DISTINCT companheiro_id)
            FROM public.trilha_companheiro
            WHERE companheiro_id IN ($placeholders)
              AND status = ?
        ";

        $params = [...$companheiroIds, $status];
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    public function listarEtapasPorCompanheiroId(string $companheiroId): array
    {
        if (!$this->trilhaDisponivel()) {
            return [];
        }

        $companheiroId = trim($companheiroId);
        if ($companheiroId === '') {
            return [];
        }

        $stmt = $this->db->prepare("
            SELECT
                id,
                companheiro_id,
                etapa_ordem,
                titulo_etapa,
                status,
                data_disponibilizacao,
                data_entrega,
                data_revisao,
                observacao_vigilante,
                arquivo_entrega,
                revisado_por,
                created_at,
                updated_at
            FROM public.trilha_companheiro
            WHERE companheiro_id = :companheiro_id
            ORDER BY etapa_ordem ASC
        ");
        $stmt->execute(['companheiro_id' => $companheiroId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obterResumoDoCompanheiro(string $companheiroId): ?array
    {
        $etapas = $this->listarEtapasPorCompanheiroId($companheiroId);
        if ($etapas === []) {
            return null;
        }

        $etapaAtual = null;
        foreach ($etapas as $etapa) {
            if (!in_array((string) ($etapa['status'] ?? ''), ['concluido', 'certificado_solicitado', 'exaltacao_recomendada'], true)) {
                $etapaAtual = $etapa;
                break;
            }
        }
        if ($etapaAtual === null) {
            $etapaAtual = end($etapas) ?: null;
            if ($etapaAtual !== null) {
                reset($etapas);
            }
        }

        $concluidas = count(array_filter(
            $etapas,
            static fn (array $etapa): bool => in_array((string) ($etapa['status'] ?? ''), ['concluido', 'certificado_solicitado', 'exaltacao_recomendada'], true)
        ));

        return [
            'etapas' => $etapas,
            'etapa_atual' => $etapaAtual,
            'total_etapas' => count($etapas),
            'total_concluidas' => $concluidas,
            'percentual_conclusao' => count($etapas) > 0 ? (int) floor(($concluidas / count($etapas)) * 100) : 0,
        ];
    }

    public function atualizarEtapa(
        string $companheiroId,
        int $etapaOrdem,
        string $status,
        ?string $observacao = null,
        ?string $revisadoPor = null
    ): bool {
        if (!$this->trilhaDisponivel()) {
            return false;
        }

        $companheiroId = trim($companheiroId);
        $status = trim($status);
        $revisadoPor = trim((string) $revisadoPor) ?: null;

        if ($companheiroId === '' || $etapaOrdem < 1 || $etapaOrdem > 8) {
            return false;
        }

        $statusPermitidos = [
            'nao_iniciado',
            'disponibilizado',
            'aguardando_entrega',
            'recebido',
            'revisado',
            'concluido',
            'apto_para_certificado',
            'certificado_solicitado',
            'apto_para_exaltacao',
            'exaltacao_recomendada',
        ];
        if (!in_array($status, $statusPermitidos, true)) {
            return false;
        }

        $campos = [
            'status = :status',
            'observacao_vigilante = :observacao',
            'updated_at = NOW()',
        ];
        $params = [
            'companheiro_id' => $companheiroId,
            'etapa_ordem' => $etapaOrdem,
            'status' => $status,
            'observacao' => $observacao !== null && trim($observacao) !== '' ? trim($observacao) : null,
        ];

        if (in_array($status, ['disponibilizado', 'aguardando_entrega'], true)) {
            $campos[] = 'data_disponibilizacao = COALESCE(data_disponibilizacao, NOW())';
        }
        if ($status === 'recebido') {
            $campos[] = 'data_entrega = COALESCE(data_entrega, NOW())';
        }
        if (in_array($status, ['revisado', 'concluido', 'apto_para_certificado', 'certificado_solicitado', 'apto_para_exaltacao', 'exaltacao_recomendada'], true)) {
            $campos[] = 'data_revisao = COALESCE(data_revisao, NOW())';
            $campos[] = 'revisado_por = :revisado_por';
            $params['revisado_por'] = $revisadoPor;
        }

        $sql = "
            UPDATE public.trilha_companheiro
               SET " . implode(",\n                   ", $campos) . "
             WHERE companheiro_id = :companheiro_id
               AND etapa_ordem = :etapa_ordem
        ";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
}
