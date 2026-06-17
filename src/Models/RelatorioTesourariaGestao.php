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
    private array $schemaCache = [];

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
        $params = ['data_limite' => $dataLimite];
        $filtroLoja = $this->montarFiltroLoja('lancamentos_financeiros', 'l', $params);
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(CASE WHEN l.tipo = 'entrada' THEN l.valor ELSE -l.valor END), 0) AS saldo
            FROM lancamentos_financeiros l
            WHERE l.data_lancamento < :data_limite
              {$filtroLoja}
        ");
        $stmt->execute($params);
        return (float) $stmt->fetchColumn();
    }

    private function obterTotaisPeriodo(string $inicio, string $fim): array
    {
        $params = ['inicio' => $inicio, 'fim' => $fim];
        $filtroLoja = $this->montarFiltroLoja('lancamentos_financeiros', null, $params);
        $stmt = $this->db->prepare("
            SELECT
                COALESCE(SUM(CASE WHEN tipo = 'entrada' THEN valor ELSE 0 END), 0) AS entradas,
                COALESCE(SUM(CASE WHEN tipo = 'saida' THEN valor ELSE 0 END), 0) AS saidas
            FROM lancamentos_financeiros
            WHERE data_lancamento BETWEEN :inicio AND :fim
              {$filtroLoja}
        ");
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['entradas' => 0, 'saidas' => 0];
    }

    private function obterTotaisPorBloco(string $inicio, string $fim): array
    {
        $params = ['inicio' => $inicio, 'fim' => $fim];
        $filtroLoja = $this->montarFiltroLoja('lancamentos_financeiros', 'l', $params);
        $stmt = $this->db->prepare("
            SELECT COALESCE(c.bloco_relatorio, 'outros') AS bloco_relatorio, l.tipo, COALESCE(SUM(l.valor), 0) AS total
            FROM lancamentos_financeiros l
            JOIN categorias_financeiras c ON c.id = l.categoria_id
            WHERE l.data_lancamento BETWEEN :inicio AND :fim
              {$filtroLoja}
            GROUP BY COALESCE(c.bloco_relatorio, 'outros'), l.tipo
            ORDER BY bloco_relatorio ASC, l.tipo ASC
        ");
        $stmt->execute($params);

        $blocos = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $bloco = (string) ($row['bloco_relatorio'] ?? 'outros');
            $blocos[$bloco] ??= ['entrada' => 0.0, 'saida' => 0.0];
            $blocos[$bloco][(string) $row['tipo']] = (float) ($row['total'] ?? 0);
        }
        return $blocos;
    }

    private function obterTotaisPorCategoria(string $inicio, string $fim): array
    {
        $params = ['inicio' => $inicio, 'fim' => $fim];
        $filtroLoja = $this->montarFiltroLoja('lancamentos_financeiros', 'l', $params);
        $stmt = $this->db->prepare("
            SELECT
                CASE WHEN c.codigo IN ('INICIACAO', 'ELEVACAO', 'EXALTACAO', 'JOIAS') THEN 'Joias' ELSE c.nome END AS nome,
                CASE WHEN c.codigo IN ('INICIACAO', 'ELEVACAO', 'EXALTACAO', 'JOIAS') THEN 'JOIAS' ELSE c.codigo END AS codigo,
                c.bloco_relatorio,
                l.tipo,
                COUNT(l.id) AS quantidade,
                COALESCE(SUM(l.valor), 0) AS total
            FROM lancamentos_financeiros l
            JOIN categorias_financeiras c ON c.id = l.categoria_id
            WHERE l.data_lancamento BETWEEN :inicio AND :fim
              {$filtroLoja}
            GROUP BY 1, 2, c.bloco_relatorio, l.tipo
            ORDER BY c.bloco_relatorio ASC, total DESC, 1 ASC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function obterEntidadesAuxiliadas(string $inicio, string $fim): array
    {
        if (!$this->colunaExiste('lancamentos_financeiros', 'entidade_auxiliada') || !$this->colunaExiste('categorias_financeiras', 'exige_entidade_auxiliada')) {
            return [];
        }

        $params = ['inicio' => $inicio, 'fim' => $fim];
        $filtroLoja = $this->montarFiltroLoja('lancamentos_financeiros', 'l', $params);
        $stmt = $this->db->prepare("
            SELECT COALESCE(NULLIF(TRIM(l.entidade_auxiliada), ''), 'Não informada') AS entidade, COUNT(l.id) AS quantidade, COALESCE(SUM(l.valor), 0) AS total
            FROM lancamentos_financeiros l
            JOIN categorias_financeiras c ON c.id = l.categoria_id
            WHERE l.data_lancamento BETWEEN :inicio AND :fim
              {$filtroLoja}
              AND c.exige_entidade_auxiliada = TRUE
            GROUP BY entidade
            ORDER BY total DESC, entidade ASC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function obterLancamentosPeriodo(string $inicio, string $fim): array
    {
        $params = ['inicio' => $inicio, 'fim' => $fim];
        $filtroLoja = $this->montarFiltroLoja('lancamentos_financeiros', 'l', $params);
        $entidadeSelect = $this->colunaSelect('lancamentos_financeiros', 'entidade_auxiliada', 'l');
        $observacaoSelect = $this->colunaSelect('lancamentos_financeiros', 'observacao_gestao', 'l');
        $stmt = $this->db->prepare("
            SELECT
                l.id,
                l.data_lancamento,
                l.tipo,
                l.valor,
                l.descricao,
                {$entidadeSelect},
                {$observacaoSelect},
                c.nome AS categoria_nome,
                c.bloco_relatorio,
                COALESCE(o.nome_historico, o.nome) AS obreiro_nome
            FROM lancamentos_financeiros l
            JOIN categorias_financeiras c ON c.id = l.categoria_id
            LEFT JOIN obreiros o ON o.id = l.obreiro_id
            WHERE l.data_lancamento BETWEEN :inicio AND :fim
              {$filtroLoja}
            ORDER BY l.data_lancamento DESC, l.id DESC
            LIMIT 120
        ");
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function obterTotaisTronco(string $inicio, string $fim): array
    {
        if (!$this->tabelaExiste('tronco_solidariedade')) {
            return ['entradas' => 0, 'saidas' => 0];
        }

        $params = ['inicio' => $inicio, 'fim' => $fim];
        $filtroLoja = $this->montarFiltroLoja('tronco_solidariedade', null, $params);
        $stmt = $this->db->prepare("
            SELECT
                COALESCE(SUM(CASE WHEN tipo = 'entrada' THEN valor ELSE 0 END), 0) AS entradas,
                COALESCE(SUM(CASE WHEN tipo = 'saida' THEN valor ELSE 0 END), 0) AS saidas
            FROM tronco_solidariedade
            WHERE data_mov BETWEEN :inicio AND :fim
              {$filtroLoja}
        ");
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['entradas' => 0, 'saidas' => 0];
    }

    private function montarFiltroLoja(string $tabela, ?string $alias, array &$params): string
    {
        if (!$this->colunaExiste($tabela, 'loja_id')) {
            return '';
        }

        $params['loja_id'] = $this->obterLojaAtualId();
        return 'AND ' . ($alias ? $alias . '.' : '') . 'loja_id = :loja_id';
    }

    private function colunaSelect(string $tabela, string $coluna, string $alias): string
    {
        return $this->colunaExiste($tabela, $coluna) ? $alias . '.' . $coluna : "'' AS " . $coluna;
    }

    private function tabelaExiste(string $tabela): bool
    {
        $cacheKey = 'table:' . $tabela;
        if (array_key_exists($cacheKey, $this->schemaCache)) {
            return $this->schemaCache[$cacheKey];
        }

        $stmt = $this->db->prepare('SELECT to_regclass(:tabela) IS NOT NULL');
        $stmt->execute(['tabela' => $tabela]);
        return $this->schemaCache[$cacheKey] = (bool) $stmt->fetchColumn();
    }

    private function colunaExiste(string $tabela, string $coluna): bool
    {
        $cacheKey = 'column:' . $tabela . '.' . $coluna;
        if (array_key_exists($cacheKey, $this->schemaCache)) {
            return $this->schemaCache[$cacheKey];
        }

        try {
            $stmt = $this->db->prepare("
                SELECT 1 
                FROM pg_attribute 
                WHERE attrelid = :tabela::regclass 
                  AND attname = :coluna 
                  AND NOT attisdropped
            ");
            $stmt->execute(['tabela' => $tabela, 'coluna' => $coluna]);
            return $this->schemaCache[$cacheKey] = (bool) $stmt->fetchColumn();
        } catch (\PDOException $e) {
            return $this->schemaCache[$cacheKey] = false;
        }
    }

    private function obterLojaAtualId(): int
    {
        return $this->resolveCurrentStoreId($this->db);
    }
}
