<?php

namespace App\Models;

use App\Config\Database;
use App\Core\Tenant\ResolvesStoreTenant;
use DateTimeImmutable;
use DateTimeZone;
use PDO;

class TesourariaExecutive
{
    use ResolvesStoreTenant;

    private PDO $db;
    private ?bool $colunaLojaIdExiste = null;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function resumoMesAtual(): array
    {
        $tz = new DateTimeZone('America/Sao_Paulo');
        $hoje = new DateTimeImmutable('today', $tz);
        return $this->resumoMes((int) $hoje->format('n'), (int) $hoje->format('Y'));
    }

    public function resumoMes(int $mes, int $ano): array
    {
        $mes = max(1, min(12, $mes));
        $ano = max(2000, min(2100, $ano));

        $tz = new DateTimeZone('America/Sao_Paulo');
        $inicioMes = (new DateTimeImmutable(sprintf('%04d-%02d-01', $ano, $mes), $tz))->setTime(0, 0, 0);
        $fimMes = $inicioMes->modify('last day of this month')->setTime(23, 59, 59);
        $hoje = new DateTimeImmutable('today', $tz);
        $fimProjecao = $hoje < $fimMes ? $hoje->setTime(23, 59, 59) : $fimMes;

        $saldoInicial = $this->obterSaldoAte($inicioMes->modify('-1 day')->format('Y-m-d'));
        $totaisAteHoje = $this->obterTotaisPeriodo($inicioMes->format('Y-m-d'), $fimProjecao->format('Y-m-d'));
        $saldoAtual = $saldoInicial + (float) $totaisAteHoje['entradas'] - (float) $totaisAteHoje['saidas'];

        $diasDecorridos = max(1, (int) $fimProjecao->format('j'));
        $diasNoMes = (int) $fimMes->format('j');
        $netAteHoje = ((float) $totaisAteHoje['entradas']) - ((float) $totaisAteHoje['saidas']);
        $netProjetado = $diasNoMes > 0 ? ($netAteHoje / $diasDecorridos) * $diasNoMes : $netAteHoje;
        $saldoPrevistoFimMes = round($saldoInicial + $netProjetado, 2);

        return [
            'mes' => $mes,
            'ano' => $ano,
            'saldo_inicial' => round($saldoInicial, 2),
            'saldo_atual' => round($saldoAtual, 2),
            'fluxo_atual' => [
                'entradas' => round((float) $totaisAteHoje['entradas'], 2),
                'saidas' => round((float) $totaisAteHoje['saidas'], 2),
                'resultado' => round($netAteHoje, 2),
            ],
            // Projeção simples, explicitamente baseada na média do mês corrente.
            'previsao_fim_mes' => $saldoPrevistoFimMes,
        ];
    }

    public function serieComparativaTresAnos(int $anoBase): array
    {
        $anoBase = max(2000, min(2100, $anoBase));
        $anos = [$anoBase, $anoBase - 1, $anoBase - 2];
        $meses = range(1, 12);

        $params = [
            'ano_1' => $anos[0],
            'ano_2' => $anos[1],
            'ano_3' => $anos[2],
        ];
        $whereLoja = '';
        if ($this->tabelaLancamentosTemLojaId()) {
            $params['loja_id'] = $this->resolveCurrentStoreId($this->db);
            $whereLoja = 'AND loja_id = :loja_id';
        }

        $stmt = $this->db->prepare("
            SELECT ano_ref, mes_ref, tipo, COALESCE(SUM(valor), 0) AS total
            FROM lancamentos_financeiros
            WHERE ano_ref IN (:ano_1, :ano_2, :ano_3)
              $whereLoja
            GROUP BY ano_ref, mes_ref, tipo
        ");
        $stmt->execute($params);

        $map = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $ano = (int) ($row['ano_ref'] ?? 0);
            $mes = (int) ($row['mes_ref'] ?? 0);
            $tipo = (string) ($row['tipo'] ?? '');
            $total = (float) ($row['total'] ?? 0);
            if (!isset($map[$ano])) {
                $map[$ano] = [];
            }
            if (!isset($map[$ano][$mes])) {
                $map[$ano][$mes] = ['entrada' => 0.0, 'saida' => 0.0];
            }
            if ($tipo === 'entrada') {
                $map[$ano][$mes]['entrada'] += $total;
            } elseif ($tipo === 'saida') {
                $map[$ano][$mes]['saida'] += $total;
            }
        }

        $serie = [];
        foreach ($anos as $ano) {
            foreach ($meses as $mes) {
                $entrada = (float) (($map[$ano][$mes]['entrada'] ?? 0.0));
                $saida = (float) (($map[$ano][$mes]['saida'] ?? 0.0));
                $serie[] = [
                    'ano' => $ano,
                    'mes' => $mes,
                    'entradas' => round($entrada, 2),
                    'saidas' => round($saida, 2),
                    'resultado' => round($entrada - $saida, 2),
                ];
            }
        }

        return $serie;
    }

    public function somatoriosAnoPorGrupo(int $ano): array
    {
        $ano = max(2000, min(2100, $ano));
        $params = ['ano' => $ano];
        $whereLoja = '';
        if ($this->tabelaLancamentosTemLojaId()) {
            $params['loja_id'] = $this->resolveCurrentStoreId($this->db);
            $whereLoja = 'AND l.loja_id = :loja_id';
        }

        $stmt = $this->db->prepare("
            SELECT c.nome AS categoria_nome, c.codigo AS categoria_codigo, c.bloco_relatorio, l.tipo, COALESCE(SUM(l.valor), 0) AS total
            FROM lancamentos_financeiros l
            JOIN categorias_financeiras c ON c.id = l.categoria_id
            WHERE l.ano_ref = :ano
              $whereLoja
            GROUP BY c.nome, c.codigo, c.bloco_relatorio, l.tipo
        ");
        $stmt->execute($params);

        $grupos = [
            'despesas_fixas' => 0.0,
            'despesas_agape' => 0.0,
            'despesas_expediente' => 0.0,
            'despesas_cozinha_mercado' => 0.0,
            'entradas_fixas' => 0.0,
            'entradas_mensalidades' => 0.0,
            'entradas_joias' => 0.0,
        ];

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $tipo = (string) ($row['tipo'] ?? '');
            $codigo = strtoupper(trim((string) ($row['categoria_codigo'] ?? '')));
            $nome = strtolower(trim((string) ($row['categoria_nome'] ?? '')));
            $bloco = strtolower(trim((string) ($row['bloco_relatorio'] ?? '')));
            $total = (float) ($row['total'] ?? 0);

            if ($tipo === 'entrada') {
                if ($codigo === 'JOIAS' || in_array($codigo, ['INICIACAO', 'ELEVACAO', 'EXALTACAO'], true)) {
                    $grupos['entradas_joias'] += $total;
                    continue;
                }
                if (in_array($codigo, ['MENSALIDADES', 'MENSALIDADE'], true)) {
                    $grupos['entradas_mensalidades'] += $total;
                    continue;
                }
                if (str_contains($bloco, 'receitas') || str_contains($codigo, 'ALUGUEL')) {
                    $grupos['entradas_fixas'] += $total;
                    continue;
                }
                continue;
            }

            if ($tipo === 'saida') {
                if (str_contains($bloco, 'agapes') || str_contains($nome, 'ágape') || str_contains($nome, 'agape')) {
                    $grupos['despesas_agape'] += $total;
                    continue;
                }
                if (str_contains($nome, 'gráfica') || str_contains($nome, 'grafica') || str_contains($nome, 'papel') || str_contains($nome, 'expediente')) {
                    $grupos['despesas_expediente'] += $total;
                    continue;
                }
                if (str_contains($nome, 'cozinha') || str_contains($nome, 'mercado') || str_contains($nome, 'água') || str_contains($nome, 'agua') || str_contains($nome, 'refrigerante') || str_contains($nome, 'arroz')) {
                    $grupos['despesas_cozinha_mercado'] += $total;
                    continue;
                }
                if (str_contains($nome, 'aluguel') || str_contains($bloco, 'despesas')) {
                    $grupos['despesas_fixas'] += $total;
                }
            }
        }

        foreach ($grupos as $k => $v) {
            $grupos[$k] = round((float) $v, 2);
        }

        return $grupos;
    }

    private function obterSaldoAte(string $dataLimite): float
    {
        $params = ['data_limite' => $dataLimite];
        $whereLoja = '';
        if ($this->tabelaLancamentosTemLojaId()) {
            $params['loja_id'] = $this->resolveCurrentStoreId($this->db);
            $whereLoja = 'AND loja_id = :loja_id';
        }

        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(CASE WHEN tipo = 'entrada' THEN valor ELSE -valor END), 0) AS saldo
            FROM lancamentos_financeiros
            WHERE data_lancamento < :data_limite
              $whereLoja
        ");
        $stmt->execute($params);
        return (float) $stmt->fetchColumn();
    }

    private function obterTotaisPeriodo(string $inicio, string $fim): array
    {
        $params = [
            'inicio' => $inicio,
            'fim' => $fim,
        ];
        $whereLoja = '';
        if ($this->tabelaLancamentosTemLojaId()) {
            $params['loja_id'] = $this->resolveCurrentStoreId($this->db);
            $whereLoja = 'AND loja_id = :loja_id';
        }

        $stmt = $this->db->prepare("
            SELECT
                COALESCE(SUM(CASE WHEN tipo = 'entrada' THEN valor ELSE 0 END), 0) AS entradas,
                COALESCE(SUM(CASE WHEN tipo = 'saida' THEN valor ELSE 0 END), 0) AS saidas
            FROM lancamentos_financeiros
            WHERE data_lancamento BETWEEN :inicio AND :fim
              $whereLoja
        ");
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['entradas' => 0, 'saidas' => 0];
    }

    private function tabelaLancamentosTemLojaId(): bool
    {
        if ($this->colunaLojaIdExiste !== null) {
            return $this->colunaLojaIdExiste;
        }

        try {
            $stmt = $this->db->prepare("
                SELECT 1
                FROM information_schema.columns
                WHERE table_schema = 'public'
                  AND table_name = 'lancamentos_financeiros'
                  AND column_name = 'loja_id'
                LIMIT 1
            ");
            $stmt->execute();
            $this->colunaLojaIdExiste = (bool) $stmt->fetchColumn();
        } catch (\Throwable $e) {
            // Em caso de ambiente restrito/sem acesso ao catálogo, assume coluna ausente.
            $this->colunaLojaIdExiste = false;
        }

        return $this->colunaLojaIdExiste;
    }
}
