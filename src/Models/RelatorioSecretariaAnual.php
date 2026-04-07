<?php

namespace App\Models;

use App\Config\Database;
use DateTimeImmutable;
use DateTimeZone;
use PDO;

class RelatorioSecretariaAnual
{
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

        $visitantes = $this->obterVisitantesPeriodo($inicio, $fimExclusivo);
        $sessoesPorGrau = $this->obterSessoesPorGrau($inicio, $fimExclusivo);
        $quadro = $this->obterPanoramaQuadro($inicio, $fimExclusivo);
        $visitasExternas = $this->obterVisitasExternasPeriodo($inicio, $fimExclusivo);
        $congressos = $this->obterEventosPeriodo($inicio, $fimExclusivo, 'congressos');
        $palestras = $this->obterEventosPeriodo($inicio, $fimExclusivo, 'palestras');

        return [
            'ano' => $ano,
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
              AND COALESCE(s.status, 'planejada') <> 'cancelada'
        ");
        $stmt->execute([
            'inicio' => $inicio->format('Y-m-d H:i:sP'),
            'fim_exclusivo' => $fimExclusivo->format('Y-m-d H:i:sP'),
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
              AND COALESCE(s.status, 'planejada') <> 'cancelada'
              AND NULLIF(TRIM(COALESCE(visitante ->> 'loja', '')), '') IS NOT NULL
            GROUP BY loja
            ORDER BY total DESC, loja ASC
            LIMIT 8
        ");
        $stmtLojas->execute([
            'inicio' => $inicio->format('Y-m-d H:i:sP'),
            'fim_exclusivo' => $fimExclusivo->format('Y-m-d H:i:sP'),
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
              AND COALESCE(status, 'planejada') <> 'cancelada'
            GROUP BY COALESCE(NULLIF(TRIM(grau_sessao), ''), 'Nao informado')
            ORDER BY total DESC, grau_sessao ASC
        ");
        $stmt->execute([
            'inicio' => $inicio->format('Y-m-d H:i:sP'),
            'fim_exclusivo' => $fimExclusivo->format('Y-m-d H:i:sP'),
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
        $stmt = $this->db->prepare("
            SELECT COUNT(*) AS total
            FROM public.balaustres b
            JOIN public.sessoes s ON s.id = b.sessao_id
            LEFT JOIN LATERAL jsonb_array_elements(
                COALESCE(b.dados_capturados -> 'saco_propostas' -> 'visitas_externas', '[]'::jsonb)
            ) AS visita ON TRUE
            WHERE s.data_hora_inicio >= :inicio
              AND s.data_hora_inicio < :fim_exclusivo
              AND COALESCE(s.status, 'planejada') <> 'cancelada'
        ");
        $stmt->execute([
            'inicio' => $inicio->format('Y-m-d H:i:sP'),
            'fim_exclusivo' => $fimExclusivo->format('Y-m-d H:i:sP'),
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
              AND COALESCE(s.status, 'planejada') <> 'cancelada'
              AND NULLIF(TRIM(COALESCE(visita ->> 'loja', '')), '') IS NOT NULL
            GROUP BY loja
            ORDER BY total DESC, loja ASC
            LIMIT 8
        ");
        $stmtLojas->execute([
            'inicio' => $inicio->format('Y-m-d H:i:sP'),
            'fim_exclusivo' => $fimExclusivo->format('Y-m-d H:i:sP'),
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
        $stmt = $this->db->prepare("
            SELECT COUNT(*) AS total
            FROM public.balaustres b
            JOIN public.sessoes s ON s.id = b.sessao_id
            LEFT JOIN LATERAL jsonb_array_elements(
                COALESCE(b.dados_capturados -> 'eventos_realizados' -> :tipo, '[]'::jsonb)
            ) AS evento ON TRUE
            WHERE s.data_hora_inicio >= :inicio
              AND s.data_hora_inicio < :fim_exclusivo
              AND COALESCE(s.status, 'planejada') <> 'cancelada'
        ");
        $stmt->bindValue('tipo', $tipo);
        $stmt->bindValue('inicio', $inicio->format('Y-m-d H:i:sP'));
        $stmt->bindValue('fim_exclusivo', $fimExclusivo->format('Y-m-d H:i:sP'));
        $stmt->execute();

        return [
            'total' => (int) $stmt->fetchColumn(),
            'fonte' => 'balaustres.eventos_realizados.' . $tipo,
        ];
    }

    private function obterPanoramaQuadro(DateTimeImmutable $inicio, DateTimeImmutable $fimExclusivo): array
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
              AND (
                    ativo = TRUE
                 OR updated_at >= :referencia
              )
        ";

        $stmtInicio = $this->db->prepare("SELECT COUNT(*) " . $sqlBase);
        $stmtInicio->execute([
            'limite' => $inicio->format('Y-m-d H:i:sP'),
            'referencia' => $inicio->format('Y-m-d H:i:sP'),
        ]);
        $inicioAno = (int) $stmtInicio->fetchColumn();

        $stmtFim = $this->db->prepare("SELECT COUNT(*) " . $sqlBase);
        $stmtFim->execute([
            'limite' => $fimExclusivo->format('Y-m-d H:i:sP'),
            'referencia' => $fimExclusivo->format('Y-m-d H:i:sP'),
        ]);
        $fimAno = (int) $stmtFim->fetchColumn();

        return [
            'inicio_ano' => $inicioAno,
            'fim_ano' => $fimAno,
            'observacao' => 'Indicador estimado pela trilha cadastral atual dos obreiros (created_at, updated_at e ativo).',
            'metodo' => 'estimado_por_timestamps',
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
}
