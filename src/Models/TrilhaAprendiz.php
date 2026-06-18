<?php

namespace App\Models;

use App\Config\Database;
use PDO;
use PDOException;

class TrilhaAprendiz
{
    private PDO $db;
    private ?bool $trilhaDisponivelCache = null;

    private const ETAPAS = [
        1 => 'Entrega das impressões de iniciação',
        2 => 'Passar o complemento à iniciação',
        3 => 'Passar a 1ª instrução',
        4 => 'Receber o trabalho da 1ª instrução',
        5 => 'Passar a 2ª instrução',
        6 => 'Receber o trabalho da 2ª instrução',
        7 => 'Passar a 3ª instrução',
        8 => 'Receber o trabalho da 3ª instrução',
        9 => 'Passar a 4ª instrução',
        10 => 'Receber o trabalho da 4ª instrução',
        11 => 'Passar a 5ª instrução',
        12 => 'Receber o trabalho da 5ª instrução',
        13 => 'Solicitar o certificado de conclusão da docência maçônica',
    ];

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public static function etapas(): array
    {
        return self::ETAPAS;
    }

    public static function isEtapaOral(int $etapaOrdem): bool
    {
        return in_array($etapaOrdem, [2, 3, 5, 7, 9, 11, 13], true);
    }

    public function garantirTrilhaBaseParaAprendizes(array $aprendizIds): void
    {
        if (!$this->trilhaDisponivel()) {
            return;
        }

        $aprendizIds = array_values(array_filter(array_unique(array_map(
            static fn ($id) => trim((string) $id),
            $aprendizIds
        ))));

        if ($aprendizIds === []) {
            return;
        }

        $stmt = $this->db->prepare("
            INSERT INTO trilha_aprendiz (
                aprendiz_id,
                etapa_ordem,
                titulo_etapa,
                status,
                created_at,
                updated_at
            )
            VALUES (
                :aprendiz_id,
                :etapa_ordem,
                :titulo_etapa,
                'nao_iniciado',
                NOW(),
                NOW()
            )
            ON CONFLICT (aprendiz_id, etapa_ordem) DO NOTHING
        ");

        foreach ($aprendizIds as $aprendizId) {
            foreach (self::ETAPAS as $ordem => $titulo) {
                $stmt->execute([
                    'aprendiz_id' => $aprendizId,
                    'etapa_ordem' => $ordem,
                    'titulo_etapa' => $titulo,
                ]);
            }
        }
    }

    public function listarResumoAtualPorAprendizIds(array $aprendizIds): array
    {
        if (!$this->trilhaDisponivel()) {
            return [];
        }

        $aprendizIds = array_values(array_filter(array_unique(array_map(
            static fn ($id) => trim((string) $id),
            $aprendizIds
        ))));

        if ($aprendizIds === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($aprendizIds), '?'));
        $sql = "
            SELECT
                t.aprendiz_id,
                t.etapa_ordem,
                t.titulo_etapa,
                t.status,
                t.data_disponibilizacao,
                t.data_entrega,
                t.data_revisao
            FROM trilha_aprendiz t
            JOIN (
                SELECT
                    aprendiz_id,
                    COALESCE(
                        MIN(etapa_ordem) FILTER (
                            WHERE status NOT IN ('concluido', 'certificado_solicitado')
                        ),
                        MAX(etapa_ordem)
                    ) AS etapa_atual
                FROM trilha_aprendiz
                WHERE aprendiz_id IN ($placeholders)
                GROUP BY aprendiz_id
            ) atual
              ON atual.aprendiz_id = t.aprendiz_id
             AND atual.etapa_atual = t.etapa_ordem
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($aprendizIds);

        $resultado = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $resultado[(string) $row['aprendiz_id']] = $row;
        }

        return $resultado;
    }

    public function contarPorStatus(array $aprendizIds, string $status): int
    {
        if (!$this->trilhaDisponivel()) {
            return 0;
        }

        $aprendizIds = array_values(array_filter(array_unique(array_map(
            static fn ($id) => trim((string) $id),
            $aprendizIds
        ))));

        if ($aprendizIds === []) {
            return 0;
        }

        $placeholders = implode(', ', array_fill(0, count($aprendizIds), '?'));
        $sql = "
            SELECT COUNT(DISTINCT aprendiz_id)
            FROM trilha_aprendiz
            WHERE aprendiz_id IN ($placeholders)
              AND status = ?
        ";

        $params = [...$aprendizIds, $status];
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    public function listarEtapasPorAprendizId(string $aprendizId): array
    {
        if (!$this->trilhaDisponivel()) {
            return [];
        }

        $aprendizId = trim($aprendizId);
        if ($aprendizId === '') {
            return [];
        }

        $stmt = $this->db->prepare("
            SELECT
                id,
                aprendiz_id,
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
            FROM trilha_aprendiz
            WHERE aprendiz_id = :aprendiz_id
            ORDER BY etapa_ordem ASC
        ");
        $stmt->execute(['aprendiz_id' => $aprendizId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obterResumoDoAprendiz(string $aprendizId): ?array
    {
        $etapas = $this->listarEtapasPorAprendizId($aprendizId);
        if ($etapas === []) {
            return null;
        }

        $etapaAtual = null;
        foreach ($etapas as $etapa) {
            if (!in_array((string) ($etapa['status'] ?? ''), ['concluido', 'certificado_solicitado'], true)) {
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
            static fn (array $etapa): bool => in_array((string) ($etapa['status'] ?? ''), ['concluido', 'certificado_solicitado'], true)
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
        string $aprendizId,
        int $etapaOrdem,
        string $status,
        ?string $observacao = null,
        ?string $revisadoPor = null,
        ?string $arquivoEntrega = null,
        bool $publicarBiblioteca = false
    ): bool {
        if (!$this->trilhaDisponivel()) {
            return false;
        }

        $aprendizId = trim($aprendizId);
        $status = trim($status);
        $revisadoPor = trim((string) $revisadoPor) ?: null;

        if ($aprendizId === '' || $etapaOrdem < 1 || $etapaOrdem > 13) {
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
            'aprendiz_id' => $aprendizId,
            'etapa_ordem' => $etapaOrdem,
            'status' => $status,
            'observacao' => $observacao !== null && trim($observacao) !== '' ? trim($observacao) : null,
        ];

        if ($arquivoEntrega !== null) {
            $campos[] = 'arquivo_entrega = :arquivo_entrega';
            $params['arquivo_entrega'] = $arquivoEntrega !== '' ? trim($arquivoEntrega) : null;
        }

        if (in_array($status, ['disponibilizado', 'aguardando_entrega'], true)) {
            $campos[] = 'data_disponibilizacao = COALESCE(data_disponibilizacao, NOW())';
        }
        if ($status === 'recebido') {
            $campos[] = 'data_entrega = COALESCE(data_entrega, NOW())';
        }
        if (in_array($status, ['revisado', 'concluido', 'apto_para_certificado', 'certificado_solicitado'], true)) {
            $campos[] = 'data_revisao = COALESCE(data_revisao, NOW())';
            $campos[] = 'revisado_por = :revisado_por';
            $params['revisado_por'] = $revisadoPor;
        }

        $sql = "
            UPDATE trilha_aprendiz
               SET " . implode(",\n                   ", $campos) . "
             WHERE aprendiz_id = :aprendiz_id
               AND etapa_ordem = :etapa_ordem
        ";

        $stmt = $this->db->prepare($sql);
        $sucesso = $stmt->execute($params);

        if ($sucesso && $status === 'concluido' && $publicarBiblioteca) {
            $stmtObreiro = $this->db->prepare("SELECT nome, loja_id FROM obreiros WHERE id = :id LIMIT 1");
            $stmtObreiro->execute(['id' => $aprendizId]);
            $obreiro = $stmtObreiro->fetch(PDO::FETCH_ASSOC);
            $nomeObreiro = $obreiro ? $obreiro['nome'] : 'Obreiro';
            $lojaId = $obreiro ? (int) $obreiro['loja_id'] : null;

            $stmtEtapa = $this->db->prepare("SELECT titulo_etapa, arquivo_entrega FROM trilha_aprendiz WHERE aprendiz_id = :aprendiz_id AND etapa_ordem = :etapa_ordem LIMIT 1");
            $stmtEtapa->execute([
                'aprendiz_id' => $aprendizId,
                'etapa_ordem' => $etapaOrdem
            ]);
            $etapaDb = $stmtEtapa->fetch(PDO::FETCH_ASSOC);
            $tituloEtapa = $etapaDb ? $etapaDb['titulo_etapa'] : "Etapa {$etapaOrdem}";
            $arquivoFinal = $arquivoEntrega ?? ($etapaDb ? $etapaDb['arquivo_entrega'] : null);

            if ($arquivoFinal !== null && trim($arquivoFinal) !== '') {
                $notaInstrucao = "trilha_aprendiz:{$aprendizId}:{$etapaOrdem}";
                $acervoModel = new \App\Models\Acervo();
                if (!$acervoModel->existePorNotaInstrucao($notaInstrucao)) {
                    $acervoModel->adicionar([
                        'titulo' => "Trabalho: " . $tituloEtapa,
                        'autor' => $nomeObreiro,
                        'resumo' => "Trabalho de instrução do Grau de Aprendiz - " . $tituloEtapa,
                        'tipo' => 'Trabalho de Instrucao',
                        'grau_restricao' => 1,
                        'grau_recomendado' => 'Aprendiz',
                        'arquivo_url' => $arquivoFinal,
                        'nota_instrucao' => $notaInstrucao,
                        'loja_id' => $lojaId,
                    ]);
                }
            }
        }

        return $sucesso;
    }

    public function trilhaDisponivel(): bool
    {
        if ($this->trilhaDisponivelCache !== null) {
            return $this->trilhaDisponivelCache;
        }

        try {
            $stmt = $this->db->query("SELECT to_regclass('trilha_aprendiz')");
            $valor = $stmt ? (string) $stmt->fetchColumn() : '';
            $this->trilhaDisponivelCache = $valor !== '';
            return $this->trilhaDisponivelCache;
        } catch (PDOException) {
            $this->trilhaDisponivelCache = false;
            return false;
        }
    }
}
