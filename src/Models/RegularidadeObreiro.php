<?php

namespace App\Models;

use App\Config\Database;
use DateTimeImmutable;
use PDO;
use Throwable;

class RegularidadeObreiro
{
    private PDO $db;
    private ?array $parametrosFinanceiros = null;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Define regularidade de um obreiro para um mês
     */
    public function definir(string $obreiroId, int $mes, int $ano, string $status, ?string $observacao, ?string $definidoPor): bool
    {
        $sql = "
            INSERT INTO regularidade_obreiro (obreiro_id, mes_ref, ano_ref, status, observacao, definido_por)
            VALUES (:obreiro_id, :mes, :ano, :status, :observacao, :definido_por)
            ON CONFLICT (obreiro_id, mes_ref, ano_ref)
            DO UPDATE SET 
                status = EXCLUDED.status,
                observacao = EXCLUDED.observacao,
                definido_por = EXCLUDED.definido_por,
                definido_em = CURRENT_TIMESTAMP
        ";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'obreiro_id' => $obreiroId,
            'mes' => $mes,
            'ano' => $ano,
            'status' => $status,
            'observacao' => $observacao,
            'definido_por' => $definidoPor,
        ]);
    }

    /**
     * Obtém status de regularidade
     */
    public function obter(string $obreiroId, int $mes, int $ano): ?array
    {
        $sql = "
            SELECT ro.*, 
                   COALESCE(o.nome_historico, o.nome) as definido_por_nome
            FROM regularidade_obreiro ro
            LEFT JOIN obreiros o ON ro.definido_por = o.id
            WHERE ro.obreiro_id = :obreiro_id 
              AND ro.mes_ref = :mes 
              AND ro.ano_ref = :ano
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['obreiro_id' => $obreiroId, 'mes' => $mes, 'ano' => $ano]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Lista regularidade de todos os obreiros para um mês
     */
    public function obterPorMes(int $mes, int $ano): array
    {
        $sql = "
            SELECT 
                o1.id as obreiro_id,
                COALESCE(o1.nome_historico, o1.nome) as obreiro_nome,
                ro.status as status_manual,
                ro.observacao,
                ro.definido_em,
                COALESCE(o2.nome_historico, o2.nome) as definido_por_nome,
                ms.status as mensalidade_status
            FROM obreiros o1
            LEFT JOIN regularidade_obreiro ro 
                ON ro.obreiro_id = o1.id
               AND ro.mes_ref = :mes 
               AND ro.ano_ref = :ano
            LEFT JOIN obreiros o2 ON ro.definido_por = o2.id
            LEFT JOIN mensalidades_status ms
                ON ms.obreiro_id = o1.id
               AND ms.mes_ref = :mes
               AND ms.ano_ref = :ano
            WHERE o1.ativo = true
            ORDER BY COALESCE(o1.nome_historico, o1.nome) ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['mes' => $mes, 'ano' => $ano]);
        $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($registros as &$registro) {
            $registro['status'] = $this->resolverStatusLista($registro, $mes, $ano);
        }
        unset($registro);

        return $registros;
    }

    /**
     * Obtém resumo: quantos regular/irregular
     */
    public function obterResumoMes(int $mes, int $ano): array
    {
        $sql = "
            SELECT 
                status,
                COUNT(*) as quantidade
            FROM regularidade_obreiro
            WHERE mes_ref = :mes AND ano_ref = :ano
            GROUP BY status
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['mes' => $mes, 'ano' => $ano]);
        
        $resumo = ['regular' => 0, 'irregular' => 0];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if (isset($resumo[$row['status']])) {
                $resumo[$row['status']] = (int) $row['quantidade'];
            }
        }
        
        return $resumo;
    }

    private function resolverStatusLista(array $registro, int $mes, int $ano): string
    {
        $statusManual = strtolower(trim((string) ($registro['status_manual'] ?? '')));
        if (in_array($statusManual, ['regular', 'irregular'], true)) {
            return $statusManual;
        }

        if (!$this->competenciaPassouDoPrazo($mes, $ano)) {
            return 'regular';
        }

        $statusMensalidade = strtolower(trim((string) ($registro['mensalidade_status'] ?? '')));
        if (in_array($statusMensalidade, ['pago', 'isento', 'dispensado'], true)) {
            return 'regular';
        }

        return 'irregular';
    }

    private function competenciaPassouDoPrazo(int $mes, int $ano): bool
    {
        $hoje = new DateTimeImmutable('today');
        $limite = $this->calcularLimiteMensalidade($mes, $ano);
        return $hoje > $limite;
    }

    private function calcularLimiteMensalidade(int $mes, int $ano): DateTimeImmutable
    {
        $parametros = $this->obterParametrosFinanceiros();
        $regra = (string) ($parametros['mensalidade_regra_atraso'] ?? 'primeiro_dia_util_mes_seguinte');
        $diaSugerido = max(1, min(31, (int) ($parametros['mensalidade_dia_sugerido'] ?? 10)));

        if ($regra === 'dia_sugerido') {
            $ultimoDia = (int) (new DateTimeImmutable(sprintf('%04d-%02d-01', $ano, $mes)))->format('t');
            $dia = min($diaSugerido, $ultimoDia);
            return new DateTimeImmutable(sprintf('%04d-%02d-%02d', $ano, $mes, $dia));
        }

        $proximoMes = (new DateTimeImmutable(sprintf('%04d-%02d-01', $ano, $mes)))->modify('first day of next month');
        return $this->primeiroDiaUtil($proximoMes);
    }

    private function primeiroDiaUtil(DateTimeImmutable $data): DateTimeImmutable
    {
        $cursor = $data;
        while (in_array((int) $cursor->format('N'), [6, 7], true)) {
            $cursor = $cursor->modify('+1 day');
        }
        return $cursor;
    }

    private function obterParametrosFinanceiros(): array
    {
        if ($this->parametrosFinanceiros !== null) {
            return $this->parametrosFinanceiros;
        }

        try {
            $configuracao = (new ConfiguracaoLoja())->obter();
        } catch (Throwable $e) {
            $configuracao = [];
        }

        $this->parametrosFinanceiros = [
            'mensalidade_dia_sugerido' => (int) ($configuracao['mensalidade_dia_sugerido'] ?? 10),
            'mensalidade_regra_atraso' => (string) ($configuracao['mensalidade_regra_atraso'] ?? 'primeiro_dia_util_mes_seguinte'),
        ];

        return $this->parametrosFinanceiros;
    }
}
