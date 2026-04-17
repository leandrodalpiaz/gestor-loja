<?php

namespace App\Models;

use App\Config\Database;
use App\Core\Tenant\ResolvesStoreTenant;
use DateTimeImmutable;
use DateTimeZone;
use PDO;

class RelatorioSecretariaAnual
{
    use ResolvesStoreTenant;

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function montar(int $ano): array
    {
        $timezone = new DateTimeZone('America/Sao_Paulo');
        $inicio = new DateTimeImmutable(sprintf('%04d-01-01 00:00:00', $ano), $timezone);
        $fimExclusivo = $inicio->modify('+1 year');
        $configuracaoLoja = (new ConfiguracaoLoja())->obter();

        $visitantes = $this->obterVisitantesPeriodo($inicio, $fimExclusivo);
        $sessoesPorGrau = $this->obterSessoesPorGrau($inicio, $fimExclusivo);
        $quadro = $this->obterPanoramaQuadro($inicio, $fimExclusivo);
        $perfilQuadro = $this->obterPerfilQuadro($inicio, $fimExclusivo);
        $visitasExternas = $this->obterVisitasExternasPeriodo($inicio, $fimExclusivo);
        $congressos = $this->obterEventosPeriodo($inicio, $fimExclusivo, 'congressos');
        $palestras = $this->obterEventosPeriodo($inicio, $fimExclusivo, 'palestras');

        return [
            'ano' => $ano,
            'loja' => $configuracaoLoja,
            'periodo' => [
                'inicio' => $inicio->format('Y-m-d H:i:sP'),
                'fim_exclusivo' => $fimExclusivo->format('Y-m-d H:i:sP'),
            ],
            'visitantes' => $visitantes,
            'visitas_externas' => $visitasExternas,
            'congressos' => $congressos,
            'palestras' => $palestras,
            'sessoes_por_grau' => $sessoesPorGrau,
            'quadro' => $quadro,
            'perfil_quadro' => $perfilQuadro,
        ];
    }

    private function obterVisitantesPeriodo(DateTimeImmutable $inicio, DateTimeImmutable $fimExclusivo): array
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) AS total
            FROM public.balaustres b
            JOIN public.sessoes s ON s.id = b.sessao_id
            LEFT JOIN LATERAL jsonb_array_elements(
                COALESCE(b.dados_capturados -> 'palavra_bem_ordem' -> 'visitantes', '[]'::jsonb)
            ) AS visitante ON TRUE
            WHERE s.data_hora_inicio >= :inicio
              AND s.data_hora_inicio < :fim_exclusivo
              AND s.loja_id = :loja_id
              AND COALESCE(s.status, 'planejada') <> 'cancelada'
        ");
        $stmt->execute([
            'inicio' => $inicio->format('Y-m-d H:i:sP'),
            'fim_exclusivo' => $fimExclusivo->format('Y-m-d H:i:sP'),
            'loja_id' => $this->obterLojaAtualId(),
        ]);

        $total = (int) $stmt->fetchColumn();

        $stmtLojas = $this->db->prepare("
            SELECT
                NULLIF(TRIM(COALESCE(visitante ->> 'loja', '')), '') AS loja,
                COUNT(*) AS total
            FROM public.balaustres b
            JOIN public.sessoes s ON s.id = b.sessao_id
            LEFT JOIN LATERAL jsonb_array_elements(
                COALESCE(b.dados_capturados -> 'palavra_bem_ordem' -> 'visitantes', '[]'::jsonb)
            ) AS visitante ON TRUE
            WHERE s.data_hora_inicio >= :inicio
              AND s.data_hora_inicio < :fim_exclusivo
              AND s.loja_id = :loja_id
              AND COALESCE(s.status, 'planejada') <> 'cancelada'
              AND NULLIF(TRIM(COALESCE(visitante ->> 'loja', '')), '') IS NOT NULL
            GROUP BY loja
            ORDER BY total DESC, loja ASC
            LIMIT 8
        ");
        $stmtLojas->execute([
            'inicio' => $inicio->format('Y-m-d H:i:sP'),
            'fim_exclusivo' => $fimExclusivo->format('Y-m-d H:i:sP'),
            'loja_id' => $this->obterLojaAtualId(),
        ]);

        return [
            'total' => $total,
            'lojas_mais_frequentes' => $stmtLojas->fetchAll(PDO::FETCH_ASSOC),
            'fonte' => 'balaustres.palavra_bem_ordem.visitantes',
        ];
    }

    private function obterSessoesPorGrau(DateTimeImmutable $inicio, DateTimeImmutable $fimExclusivo): array
    {
        $stmt = $this->db->prepare("
            SELECT
                COALESCE(NULLIF(TRIM(grau_sessao), ''), 'Nao informado') AS grau_sessao,
                COUNT(*) AS total
            FROM public.sessoes
            WHERE data_hora_inicio >= :inicio
              AND data_hora_inicio < :fim_exclusivo
              AND loja_id = :loja_id
              AND COALESCE(status, 'planejada') <> 'cancelada'
            GROUP BY COALESCE(NULLIF(TRIM(grau_sessao), ''), 'Nao informado')
            ORDER BY total DESC, grau_sessao ASC
        ");
        $stmt->execute([
            'inicio' => $inicio->format('Y-m-d H:i:sP'),
            'fim_exclusivo' => $fimExclusivo->format('Y-m-d H:i:sP'),
            'loja_id' => $this->obterLojaAtualId(),
        ]);

        $itens = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $total = 0;
        foreach ($itens as $item) {
            $total += (int) ($item['total'] ?? 0);
        }

        return [
            'total' => $total,
            'itens' => $itens,
            'regra' => 'Conta sessoes do periodo com status diferente de cancelada.',
        ];
    }

    private function obterVisitasExternasPeriodo(DateTimeImmutable $inicio, DateTimeImmutable $fimExclusivo): array
    {
        if ($this->tabelaExiste('public', 'visitas_externas_sessao')) {
            return $this->obterVisitasExternasPeriodoEstruturado($inicio, $fimExclusivo);
        }

        return $this->obterVisitasExternasPeriodoLegacy($inicio, $fimExclusivo);
    }

    private function obterVisitasExternasPeriodoEstruturado(DateTimeImmutable $inicio, DateTimeImmutable $fimExclusivo): array
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) AS total
            FROM public.visitas_externas_sessao v
            JOIN public.sessoes s ON s.id = v.sessao_id
            WHERE s.data_hora_inicio >= :inicio
              AND s.data_hora_inicio < :fim_exclusivo
              AND s.loja_id = :loja_id
              AND COALESCE(s.status, 'planejada') <> 'cancelada'
        ");
        $stmt->execute([
            'inicio' => $inicio->format('Y-m-d H:i:sP'),
            'fim_exclusivo' => $fimExclusivo->format('Y-m-d H:i:sP'),
            'loja_id' => $this->obterLojaAtualId(),
        ]);

        $stmtLojas = $this->db->prepare("
            SELECT
                NULLIF(TRIM(COALESCE(v.loja_visitada, '')), '') AS loja,
                COUNT(*) AS total
            FROM public.visitas_externas_sessao v
            JOIN public.sessoes s ON s.id = v.sessao_id
            WHERE s.data_hora_inicio >= :inicio
              AND s.data_hora_inicio < :fim_exclusivo
              AND s.loja_id = :loja_id
              AND COALESCE(s.status, 'planejada') <> 'cancelada'
              AND NULLIF(TRIM(COALESCE(v.loja_visitada, '')), '') IS NOT NULL
            GROUP BY loja
            ORDER BY total DESC, loja ASC
            LIMIT 8
        ");
        $stmtLojas->execute([
            'inicio' => $inicio->format('Y-m-d H:i:sP'),
            'fim_exclusivo' => $fimExclusivo->format('Y-m-d H:i:sP'),
            'loja_id' => $this->obterLojaAtualId(),
        ]);

        return [
            'total' => (int) $stmt->fetchColumn(),
            'lojas_mais_visitadas' => $stmtLojas->fetchAll(PDO::FETCH_ASSOC),
            'fonte' => 'visitas_externas_sessao',
        ];
    }

    private function obterVisitasExternasPeriodoLegacy(DateTimeImmutable $inicio, DateTimeImmutable $fimExclusivo): array
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) AS total
            FROM public.balaustres b
            JOIN public.sessoes s ON s.id = b.sessao_id
            LEFT JOIN LATERAL jsonb_array_elements(
                COALESCE(b.dados_capturados -> 'saco_propostas' -> 'visitas_externas', '[]'::jsonb)
            ) AS visita ON TRUE
            WHERE s.data_hora_inicio >= :inicio
              AND s.data_hora_inicio < :fim_exclusivo
              AND s.loja_id = :loja_id
              AND COALESCE(s.status, 'planejada') <> 'cancelada'
        ");
        $stmt->execute([
            'inicio' => $inicio->format('Y-m-d H:i:sP'),
            'fim_exclusivo' => $fimExclusivo->format('Y-m-d H:i:sP'),
            'loja_id' => $this->obterLojaAtualId(),
        ]);

        $stmtLojas = $this->db->prepare("
            SELECT
                NULLIF(TRIM(COALESCE(visita ->> 'loja', '')), '') AS loja,
                COUNT(*) AS total
            FROM public.balaustres b
            JOIN public.sessoes s ON s.id = b.sessao_id
            LEFT JOIN LATERAL jsonb_array_elements(
                COALESCE(b.dados_capturados -> 'saco_propostas' -> 'visitas_externas', '[]'::jsonb)
            ) AS visita ON TRUE
            WHERE s.data_hora_inicio >= :inicio
              AND s.data_hora_inicio < :fim_exclusivo
              AND s.loja_id = :loja_id
              AND COALESCE(s.status, 'planejada') <> 'cancelada'
              AND NULLIF(TRIM(COALESCE(visita ->> 'loja', '')), '') IS NOT NULL
            GROUP BY loja
            ORDER BY total DESC, loja ASC
            LIMIT 8
        ");
        $stmtLojas->execute([
            'inicio' => $inicio->format('Y-m-d H:i:sP'),
            'fim_exclusivo' => $fimExclusivo->format('Y-m-d H:i:sP'),
            'loja_id' => $this->obterLojaAtualId(),
        ]);

        return [
            'total' => (int) $stmt->fetchColumn(),
            'lojas_mais_visitadas' => $stmtLojas->fetchAll(PDO::FETCH_ASSOC),
            'fonte' => 'balaustres.saco_propostas.visitas_externas',
        ];
    }

    private function obterEventosPeriodo(DateTimeImmutable $inicio, DateTimeImmutable $fimExclusivo, string $tipo): array
    {
        $tipo = $tipo === 'congressos' ? 'congressos' : 'palestras';

        if ($this->tabelaExiste('public', 'eventos_sessao')) {
            return $this->obterEventosPeriodoEstruturado($inicio, $fimExclusivo, $tipo);
        }

        return $this->obterEventosPeriodoLegacy($inicio, $fimExclusivo, $tipo);
    }

    private function obterEventosPeriodoEstruturado(DateTimeImmutable $inicio, DateTimeImmutable $fimExclusivo, string $tipo): array
    {
        $tipoEvento = $tipo === 'congressos' ? 'congresso' : 'palestra';
        $stmt = $this->db->prepare("
            SELECT COUNT(*) AS total
            FROM public.eventos_sessao e
            JOIN public.sessoes s ON s.id = e.sessao_id
            WHERE s.data_hora_inicio >= :inicio
              AND s.data_hora_inicio < :fim_exclusivo
              AND s.loja_id = :loja_id
              AND COALESCE(s.status, 'planejada') <> 'cancelada'
              AND e.tipo_evento = :tipo_evento
        ");
        $stmt->execute([
            'inicio' => $inicio->format('Y-m-d H:i:sP'),
            'fim_exclusivo' => $fimExclusivo->format('Y-m-d H:i:sP'),
            'loja_id' => $this->obterLojaAtualId(),
            'tipo_evento' => $tipoEvento,
        ]);

        return [
            'total' => (int) $stmt->fetchColumn(),
            'fonte' => 'eventos_sessao.' . $tipoEvento,
        ];
    }

    private function obterEventosPeriodoLegacy(DateTimeImmutable $inicio, DateTimeImmutable $fimExclusivo, string $tipo): array
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) AS total
            FROM public.balaustres b
            JOIN public.sessoes s ON s.id = b.sessao_id
            LEFT JOIN LATERAL jsonb_array_elements(
                COALESCE(b.dados_capturados -> 'eventos_realizados' -> :tipo, '[]'::jsonb)
            ) AS evento ON TRUE
            WHERE s.data_hora_inicio >= :inicio
              AND s.data_hora_inicio < :fim_exclusivo
              AND s.loja_id = :loja_id
              AND COALESCE(s.status, 'planejada') <> 'cancelada'
        ");
        $stmt->bindValue('tipo', $tipo);
        $stmt->bindValue('inicio', $inicio->format('Y-m-d H:i:sP'));
        $stmt->bindValue('fim_exclusivo', $fimExclusivo->format('Y-m-d H:i:sP'));
        $stmt->bindValue('loja_id', $this->obterLojaAtualId(), PDO::PARAM_INT);
        $stmt->execute();

        return [
            'total' => (int) $stmt->fetchColumn(),
            'fonte' => 'balaustres.eventos_realizados.' . $tipo,
        ];
    }

    private function obterPanoramaQuadro(DateTimeImmutable $inicio, DateTimeImmutable $fimExclusivo): array
    {
        $temBaseFormal = $this->colunaExiste('public', 'obreiros', 'situacao_quadro')
            && $this->colunaExiste('public', 'obreiros', 'data_filiacao')
            && $this->colunaExiste('public', 'obreiros', 'data_regularizacao')
            && $this->colunaExiste('public', 'obreiros', 'data_reintegracao')
            && $this->colunaExiste('public', 'obreiros', 'data_desligamento')
            && $this->colunaExiste('public', 'obreiros', 'data_oriente_eterno');

        if ($temBaseFormal) {
            return $this->obterPanoramaQuadroPorTrilhaFormal($inicio, $fimExclusivo);
        }

        return $this->obterPanoramaQuadroPorTimestamps($inicio, $fimExclusivo);
    }

    private function obterPanoramaQuadroPorTrilhaFormal(DateTimeImmutable $inicio, DateTimeImmutable $fimExclusivo): array
    {
        $sqlBase = "
            FROM public.obreiros
            WHERE COALESCE(
                    data_reintegracao,
                    data_regularizacao,
                    data_filiacao,
                    data_iniciacao,
                    created_at::date
                  ) < :limite
              AND loja_id = :loja_id
              AND (data_desligamento IS NULL OR data_desligamento >= :limite)
              AND (data_oriente_eterno IS NULL OR data_oriente_eterno >= :limite)
        ";

        $stmtInicio = $this->db->prepare("SELECT COUNT(*) " . $sqlBase);
        $stmtInicio->execute([
            'limite' => $inicio->format('Y-m-d'),
            'loja_id' => $this->obterLojaAtualId(),
        ]);
        $inicioAno = (int) $stmtInicio->fetchColumn();

        $stmtFim = $this->db->prepare("SELECT COUNT(*) " . $sqlBase);
        $stmtFim->execute([
            'limite' => $fimExclusivo->format('Y-m-d'),
            'loja_id' => $this->obterLojaAtualId(),
        ]);
        $fimAno = (int) $stmtFim->fetchColumn();

        $movStmt = $this->db->prepare("
            SELECT
                COUNT(*) FILTER (WHERE data_filiacao >= :inicio AND data_filiacao < :fim) AS filiacoes,
                COUNT(*) FILTER (WHERE data_regularizacao >= :inicio AND data_regularizacao < :fim) AS regularizacoes,
                COUNT(*) FILTER (WHERE data_reintegracao >= :inicio AND data_reintegracao < :fim) AS reintegracoes,
                COUNT(*) FILTER (WHERE data_suspensao >= :inicio AND data_suspensao < :fim) AS suspensoes,
                COUNT(*) FILTER (WHERE data_desligamento >= :inicio AND data_desligamento < :fim) AS desligamentos,
                COUNT(*) FILTER (WHERE data_oriente_eterno >= :inicio AND data_oriente_eterno < :fim) AS oriente_eterno
            FROM public.obreiros
            WHERE loja_id = :loja_id
        ");
        $movStmt->execute([
            'inicio' => $inicio->format('Y-m-d'),
            'fim' => $fimExclusivo->format('Y-m-d'),
            'loja_id' => $this->obterLojaAtualId(),
        ]);

        return [
            'inicio_ano' => $inicioAno,
            'fim_ano' => $fimAno,
            'movimentacao' => $movStmt->fetch(PDO::FETCH_ASSOC) ?: [],
            'observacao' => 'Indicador calculado pela trilha formal do quadro de obreiros.',
            'metodo' => 'trilha_formal',
        ];
    }

    private function obterPanoramaQuadroPorTimestamps(DateTimeImmutable $inicio, DateTimeImmutable $fimExclusivo): array
    {
        $temCreatedAt = $this->colunaExiste('public', 'obreiros', 'created_at');
        $temUpdatedAt = $this->colunaExiste('public', 'obreiros', 'updated_at');

        if (!$temCreatedAt || !$temUpdatedAt) {
            return [
                'inicio_ano' => null,
                'fim_ano' => null,
                'observacao' => 'Indicador indisponivel: tabela obreiros ainda sem trilha cadastral minima completa.',
                'metodo' => 'indisponivel',
            ];
        }

        $sqlBase = "
            FROM public.obreiros
            WHERE created_at < :limite
              AND loja_id = :loja_id
              AND (
                    ativo = TRUE
                 OR updated_at >= :referencia
              )
        ";

        $stmtInicio = $this->db->prepare("SELECT COUNT(*) " . $sqlBase);
        $stmtInicio->execute([
            'limite' => $inicio->format('Y-m-d H:i:sP'),
            'referencia' => $inicio->format('Y-m-d H:i:sP'),
            'loja_id' => $this->obterLojaAtualId(),
        ]);
        $inicioAno = (int) $stmtInicio->fetchColumn();

        $stmtFim = $this->db->prepare("SELECT COUNT(*) " . $sqlBase);
        $stmtFim->execute([
            'limite' => $fimExclusivo->format('Y-m-d H:i:sP'),
            'referencia' => $fimExclusivo->format('Y-m-d H:i:sP'),
            'loja_id' => $this->obterLojaAtualId(),
        ]);
        $fimAno = (int) $stmtFim->fetchColumn();

        return [
            'inicio_ano' => $inicioAno,
            'fim_ano' => $fimAno,
            'observacao' => 'Indicador estimado pela trilha cadastral atual dos obreiros (created_at, updated_at e ativo).',
            'metodo' => 'estimado_por_timestamps',
        ];
    }

    private function obterPerfilQuadro(DateTimeImmutable $inicio, DateTimeImmutable $fimExclusivo): array
    {
        $sqlBase = "
            FROM public.obreiros
            WHERE COALESCE(
                    data_reintegracao,
                    data_regularizacao,
                    data_filiacao,
                    data_iniciacao,
                    created_at::date
                  ) < :fim
              AND loja_id = :loja_id
              AND (data_desligamento IS NULL OR data_desligamento >= :inicio)
              AND (data_oriente_eterno IS NULL OR data_oriente_eterno >= :inicio)
        ";

        $stmtResumo = $this->db->prepare("
            SELECT
                COUNT(*) AS total,
                ROUND(AVG(
                    CASE
                        WHEN data_nascimento_civil IS NOT NULL
                        THEN EXTRACT(YEAR FROM AGE(:referencia::date, data_nascimento_civil))
                        ELSE NULL
                    END
                )::numeric, 1) AS idade_media
            " . $sqlBase
        );
        $stmtResumo->execute([
            'inicio' => $inicio->format('Y-m-d'),
            'fim' => $fimExclusivo->format('Y-m-d'),
            'referencia' => $fimExclusivo->modify('-1 day')->format('Y-m-d'),
            'loja_id' => $this->obterLojaAtualId(),
        ]);
        $resumo = $stmtResumo->fetch(PDO::FETCH_ASSOC) ?: [];

        $stmtEscolaridade = $this->db->prepare("
            SELECT
                COALESCE(NULLIF(TRIM(escolaridade), ''), 'nao_informado') AS categoria,
                COUNT(*) AS total
            " . $sqlBase . "
            GROUP BY categoria
            ORDER BY total DESC, categoria ASC
        ");
        $stmtEscolaridade->execute([
            'inicio' => $inicio->format('Y-m-d'),
            'fim' => $fimExclusivo->format('Y-m-d'),
            'loja_id' => $this->obterLojaAtualId(),
        ]);

        $stmtSituacao = $this->db->prepare("
            SELECT
                COALESCE(NULLIF(TRIM(situacao_quadro), ''), 'nao_informado') AS categoria,
                COUNT(*) AS total
            " . $sqlBase . "
            GROUP BY categoria
            ORDER BY total DESC, categoria ASC
        ");
        $stmtSituacao->execute([
            'inicio' => $inicio->format('Y-m-d'),
            'fim' => $fimExclusivo->format('Y-m-d'),
            'loja_id' => $this->obterLojaAtualId(),
        ]);

        $stmtGraus = $this->db->prepare("
            SELECT
                COALESCE(NULLIF(TRIM(grau), ''), 'Nao informado') AS categoria,
                COUNT(*) AS total
            " . $sqlBase . "
            GROUP BY categoria
            ORDER BY total DESC, categoria ASC
        ");
        $stmtGraus->execute([
            'inicio' => $inicio->format('Y-m-d'),
            'fim' => $fimExclusivo->format('Y-m-d'),
            'loja_id' => $this->obterLojaAtualId(),
        ]);

        $stmtCadastros = $this->db->prepare("
            SELECT
                id,
                COALESCE(NULLIF(TRIM(nome_historico), ''), nome) AS nome_exibicao,
                grau,
                situacao_quadro,
                escolaridade,
                profissao,
                data_filiacao,
                data_regularizacao,
                data_reintegracao,
                data_desligamento,
                data_oriente_eterno
            " . $sqlBase . "
            ORDER BY nome_exibicao ASC
            LIMIT 12
        ");
        $stmtCadastros->execute([
            'inicio' => $inicio->format('Y-m-d'),
            'fim' => $fimExclusivo->format('Y-m-d'),
            'loja_id' => $this->obterLojaAtualId(),
        ]);

        return [
            'total' => (int) ($resumo['total'] ?? 0),
            'idade_media' => isset($resumo['idade_media']) ? (float) $resumo['idade_media'] : null,
            'escolaridade' => $stmtEscolaridade->fetchAll(PDO::FETCH_ASSOC),
            'situacoes' => $stmtSituacao->fetchAll(PDO::FETCH_ASSOC),
            'graus' => $stmtGraus->fetchAll(PDO::FETCH_ASSOC),
            'amostra_cadastral' => $stmtCadastros->fetchAll(PDO::FETCH_ASSOC),
        ];
    }

    private function colunaExiste(string $schema, string $tabela, string $coluna): bool
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM information_schema.columns
            WHERE table_schema = :schema
              AND table_name = :tabela
              AND column_name = :coluna
        ");
        $stmt->execute([
            'schema' => $schema,
            'tabela' => $tabela,
            'coluna' => $coluna,
        ]);

        return (int) $stmt->fetchColumn() > 0;
    }

    private function tabelaExiste(string $schema, string $tabela): bool
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM information_schema.tables
            WHERE table_schema = :schema
              AND table_name = :tabela
        ");
        $stmt->execute([
            'schema' => $schema,
            'tabela' => $tabela,
        ]);

        return (int) $stmt->fetchColumn() > 0;
    }

    private function obterLojaAtualId(): int
    {
        return $this->resolveCurrentStoreId($this->db);
    }
}
