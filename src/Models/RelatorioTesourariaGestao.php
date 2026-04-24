<?php

namespace App\Models;

use App\Config\Database;
use App\Core\Tenant\ResolvesStoreTenant;
use DateTimeImmutable;
use DateTimeZone;
use PDO;

class RelatorioTesourariaGestao
{
    use ResolvesStoreTenant;

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function montar(int $gestaoId, ?string $encerramentoInformado = null): array
    {
        $gestaoModel = new Gestao();
        $gestao = null;
        foreach ($gestaoModel->listar() as $item) {
            if ((int) ($item['id'] ?? 0) === $gestaoId) {
                $gestao = $item;
                break;
            }
        }

        if (!$gestao) {
            throw new \RuntimeException('Gestão não encontrada para o relatório financeiro.');
        }

        $timezone = new DateTimeZone('America/Sao_Paulo');
        $inicio = new DateTimeImmutable(((string) $gestao['inicio_em']) . ' 00:00:00', $timezone);
        $fimBase = trim((string) ($encerramentoInformado ?: ($gestao['encerrada_em'] ?? '')));
        $fim = $fimBase !== ''
            ? new DateTimeImmutable($fimBase . ' 23:59:59', $timezone)
            : new DateTimeImmutable('now', $timezone);

        if ($fim < $inicio) {
            $fim = $inicio->setTime(23, 59, 59);
        }

        $periodo = [
            'inicio' => $inicio->format('Y-m-d H:i:sP'),
            'fim' => $fim->format('Y-m-d H:i:sP'),
            'inicio_data' => $inicio->format('Y-m-d'),
            'fim_data' => $fim->format('Y-m-d'),
        ];

        $saldoInicial = $this->obterSaldoAte($inicio->modify('-1 day')->format('Y-m-d'));
        $totaisPeriodo = $this->obterTotaisPeriodo($periodo['inicio_data'], $periodo['fim_data']);
        $saldoFinal = $saldoInicial + (float) ($totaisPeriodo['entradas'] ?? 0) - (float) ($totaisPeriodo['saidas'] ?? 0);

        return [
            'gestao' => $gestao,
            'periodo' => $periodo,
            'saldo_inicial' => $saldoInicial,
            'saldo_final' => $saldoFinal,
            'totais' => $totaisPeriodo,
            'blocos' => $this->obterTotaisPorBloco($periodo['inicio_data'], $periodo['fim_data']),
            'categorias' => $this->obterTotaisPorCategoria($periodo['inicio_data'], $periodo['fim_data']),
            'entidades_auxiliadas' => $this->obterEntidadesAuxiliadas($periodo['inicio_data'], $periodo['fim_data']),
            'lancamentos' => $this->obterLancamentosPeriodo($periodo['inicio_data'], $periodo['fim_data']),
            'tronco' => $this->obterTotaisTronco($periodo['inicio_data'], $periodo['fim_data']),
        ];
    }

    private function obterSaldoAte(string $dataLimite): float
    {
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(CASE WHEN l.tipo = 'entrada' THEN l.valor ELSE -l.valor END), 0) AS saldo
            FROM public.lancamentos_financeiros l
            WHERE l.data_lancamento < :data_limite
              AND l.loja_id = :loja_id
        ");
        $stmt->execute([
            'data_limite' => $dataLimite,
            'loja_id' => $this->obterLojaAtualId(),
        ]);
        return (float) $stmt->fetchColumn();
    }

    private function obterTotaisPeriodo(string $inicio, string $fim): array
    {
        $stmt = $this->db->prepare("
            SELECT
                COALESCE(SUM(CASE WHEN tipo = 'entrada' THEN valor ELSE 0 END), 0) AS entradas,
                COALESCE(SUM(CASE WHEN tipo = 'saida' THEN valor ELSE 0 END), 0) AS saidas
            FROM public.lancamentos_financeiros
            WHERE data_lancamento BETWEEN :inicio AND :fim
              AND loja_id = :loja_id
        ");
        $stmt->execute([
            'inicio' => $inicio,
            'fim' => $fim,
            'loja_id' => $this->obterLojaAtualId(),
        ]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['entradas' => 0, 'saidas' => 0];
    }

    private function obterTotaisPorBloco(string $inicio, string $fim): array
    {
        $stmt = $this->db->prepare("
            SELECT
                COALESCE(c.bloco_relatorio, 'outros') AS bloco_relatorio,
                l.tipo,
                COALESCE(SUM(l.valor), 0) AS total
            FROM public.lancamentos_financeiros l
            JOIN public.categorias_financeiras c ON c.id = l.categoria_id
            WHERE l.data_lancamento BETWEEN :inicio AND :fim
              AND l.loja_id = :loja_id
            GROUP BY COALESCE(c.bloco_relatorio, 'outros'), l.tipo
            ORDER BY bloco_relatorio ASC, l.tipo ASC
        ");
        $stmt->execute([
            'inicio' => $inicio,
            'fim' => $fim,
            'loja_id' => $this->obterLojaAtualId(),
        ]);

        $blocos = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $bloco = (string) ($row['bloco_relatorio'] ?? 'outros');
            if (!isset($blocos[$bloco])) {
                $blocos[$bloco] = ['entrada' => 0.0, 'saida' => 0.0];
            }
            $blocos[$bloco][(string) $row['tipo']] = (float) ($row['total'] ?? 0);
        }

        return $blocos;
    }

    private function obterTotaisPorCategoria(string $inicio, string $fim): array
    {
        $stmt = $this->db->prepare("
            SELECT
                CASE
                    WHEN c.codigo IN ('INICIACAO', 'ELEVACAO', 'EXALTACAO', 'JOIAS') THEN 'Joias'
                    ELSE c.nome
                END AS nome,
                CASE
                    WHEN c.codigo IN ('INICIACAO', 'ELEVACAO', 'EXALTACAO', 'JOIAS') THEN 'JOIAS'
                    ELSE c.codigo
                END AS codigo,
                c.bloco_relatorio,
                l.tipo,
                COUNT(l.id) AS quantidade,
                COALESCE(SUM(l.valor), 0) AS total
            FROM public.lancamentos_financeiros l
            JOIN public.categorias_financeiras c ON c.id = l.categoria_id
            WHERE l.data_lancamento BETWEEN :inicio AND :fim
              AND l.loja_id = :loja_id
            GROUP BY 1, 2, c.bloco_relatorio, l.tipo
            ORDER BY c.bloco_relatorio ASC, total DESC, 1 ASC
        ");
        $stmt->execute([
            'inicio' => $inicio,
            'fim' => $fim,
            'loja_id' => $this->obterLojaAtualId(),
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function obterEntidadesAuxiliadas(string $inicio, string $fim): array
    {
        $stmt = $this->db->prepare("
            SELECT
                COALESCE(NULLIF(TRIM(l.entidade_auxiliada), ''), 'Não informada') AS entidade,
                COUNT(l.id) AS quantidade,
                COALESCE(SUM(l.valor), 0) AS total
            FROM public.lancamentos_financeiros l
            JOIN public.categorias_financeiras c ON c.id = l.categoria_id
            WHERE l.data_lancamento BETWEEN :inicio AND :fim
              AND l.loja_id = :loja_id
              AND c.exige_entidade_auxiliada = TRUE
            GROUP BY entidade
            ORDER BY total DESC, entidade ASC
        ");
        $stmt->execute([
            'inicio' => $inicio,
            'fim' => $fim,
            'loja_id' => $this->obterLojaAtualId(),
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function obterLancamentosPeriodo(string $inicio, string $fim): array
    {
        $stmt = $this->db->prepare("
            SELECT
                l.id,
                l.data_lancamento,
                l.tipo,
                l.valor,
                l.descricao,
                l.entidade_auxiliada,
                l.observacao_gestao,
                c.nome AS categoria_nome,
                c.bloco_relatorio,
                COALESCE(o.nome_historico, o.nome) AS obreiro_nome
            FROM public.lancamentos_financeiros l
            JOIN public.categorias_financeiras c ON c.id = l.categoria_id
            LEFT JOIN public.obreiros o ON o.id = l.obreiro_id
            WHERE l.data_lancamento BETWEEN :inicio AND :fim
              AND l.loja_id = :loja_id
            ORDER BY l.data_lancamento DESC, l.id DESC
            LIMIT 120
        ");
        $stmt->execute([
            'inicio' => $inicio,
            'fim' => $fim,
            'loja_id' => $this->obterLojaAtualId(),
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function obterTotaisTronco(string $inicio, string $fim): array
    {
        $stmt = $this->db->prepare("
            SELECT
                COALESCE(SUM(CASE WHEN tipo = 'entrada' THEN valor ELSE 0 END), 0) AS entradas,
                COALESCE(SUM(CASE WHEN tipo = 'saida' THEN valor ELSE 0 END), 0) AS saidas
            FROM public.tronco_solidariedade
            WHERE data_mov BETWEEN :inicio AND :fim
              AND loja_id = :loja_id
        ");
        $stmt->execute([
            'inicio' => $inicio,
            'fim' => $fim,
            'loja_id' => $this->obterLojaAtualId(),
        ]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['entradas' => 0, 'saidas' => 0];
    }

    private function obterLojaAtualId(): int
    {
        return $this->resolveCurrentStoreId($this->db);
    }
}
