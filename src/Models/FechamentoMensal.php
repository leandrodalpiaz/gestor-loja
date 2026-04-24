<?php

namespace App\Models;

use App\Config\Database;
use App\Core\Tenant\ResolvesStoreTenant;
use PDO;

class FechamentoMensal
{
    use ResolvesStoreTenant;

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Obtém ou cria fechamento para um mês
     */
    public function obter(int $mes, int $ano): ?array
    {
        $sql = "
            SELECT * FROM fechamento_mensal
            WHERE mes_ref = :mes AND ano_ref = :ano
              AND loja_id = :loja_id
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'mes' => $mes,
            'ano' => $ano,
            'loja_id' => $this->obterLojaAtualId(),
        ]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Cria novo fechamento
     */
    public function criar(int $mes, int $ano, float $saldoInicial, ?string $observacoes = null, ?string $criadoPor = null): bool
    {
        $sql = "
            INSERT INTO fechamento_mensal (loja_id, mes_ref, ano_ref, saldo_inicial, saldo_final, criado_em, atualizado_em)
            VALUES (:loja_id, :mes, :ano, :saldo_inicial, :saldo_final, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
        ";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'loja_id' => $this->obterLojaAtualId(),
            'mes' => $mes,
            'ano' => $ano,
            'saldo_inicial' => $saldoInicial,
            'saldo_final' => $saldoInicial,
        ]);
    }

    /**
     * Atualiza saldo inicial com auditoria
     */
    public function atualizarSaldoInicial(int $fechamentoId, float $novoSaldo, string $justificativa, ?string $alteradoPor): bool
    {
        $fechamento = $this->obterPorId($fechamentoId);
        if (!$fechamento) {
            return false;
        }

        $saldoAnterior = (float) $fechamento['saldo_inicial'];

        // Transação para manter saldo e auditoria consistentes.
        $this->db->beginTransaction();
        try {
            // Atualiza saldo inicial e recalcula saldo final do período.
            $sql = "UPDATE fechamento_mensal SET saldo_inicial = :saldo, saldo_final = (total_entradas - total_saidas + :saldo), atualizado_em = CURRENT_TIMESTAMP WHERE id = :id AND loja_id = :loja_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'saldo' => $novoSaldo,
                'id' => $fechamentoId,
                'loja_id' => $this->obterLojaAtualId(),
            ]);

            // Registra trilha de auditoria do ajuste manual.
            $sqlAudit = "
                INSERT INTO ajustes_saldo_auditoria 
                (fechamento_id, campo_alterado, valor_anterior, valor_novo, justificativa, alterado_por)
                VALUES (:fechamento_id, :campo, :anterior, :novo, :justificativa, :alterado_por)
            ";
            $stmtAudit = $this->db->prepare($sqlAudit);
            $stmtAudit->execute([
                'fechamento_id' => $fechamentoId,
                'campo' => 'saldo_inicial',
                'anterior' => $saldoAnterior,
                'novo' => $novoSaldo,
                'justificativa' => $justificativa,
                'alterado_por' => $alteradoPor,
            ]);

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            return false;
        }
    }

    /**
     * Calcula totalização e atualiza o fechamento
     */
    public function recalcularTotais(int $mes, int $ano): bool
    {
        // Obtém totais do mês para recalcular o fechamento consolidado.
        $lancModel = new LancamentoFinanceiro();
        $totais = $lancModel->obterTotaisMes($mes, $ano);

        $fechamento = $this->obter($mes, $ano);
        if (!$fechamento) {
            return false;
        }

        $saldoInicial = (float) ($fechamento['saldo_inicial'] ?? 0);
        $saldoFinal = $saldoInicial + (float) $totais['entrada'] - (float) $totais['saida'];

        $sql = "
            UPDATE fechamento_mensal
            SET total_entradas = :entradas,
                total_saidas = :saidas,
                saldo_final = :saldo_final,
                atualizado_em = CURRENT_TIMESTAMP
            WHERE mes_ref = :mes AND ano_ref = :ano AND loja_id = :loja_id
        ";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'entradas' => $totais['entrada'],
            'saidas' => $totais['saida'],
            'saldo_final' => $saldoFinal,
            'mes' => $mes,
            'ano' => $ano,
            'loja_id' => $this->obterLojaAtualId(),
        ]);
    }

    /**
     * Obtém resumo do fechamento com auditoria
     */
    public function obterComAuditoria(int $fechamentoId): ?array
    {
        $sqlFechamento = "
            SELECT fm.*, 
                   COALESCE(o.nome_historico, o.nome) as fechado_por_nome
            FROM fechamento_mensal fm
            LEFT JOIN obreiros o ON fm.fechado_por = o.id
            WHERE fm.id = :id
              AND fm.loja_id = :loja_id
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sqlFechamento);
        $stmt->execute([
            'id' => $fechamentoId,
            'loja_id' => $this->obterLojaAtualId(),
        ]);
        $fechamento = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$fechamento) {
            return null;
        }

        // Obtém auditoria
        $sqlAudit = "
            SELECT asa.*, 
                   COALESCE(o.nome_historico, o.nome) as alterado_por_nome
            FROM ajustes_saldo_auditoria asa
            LEFT JOIN obreiros o ON asa.alterado_por = o.id
            WHERE asa.fechamento_id = :fechamento_id
            ORDER BY asa.alterado_em DESC
        ";

        $stmtAudit = $this->db->prepare($sqlAudit);
        $stmtAudit->execute(['fechamento_id' => $fechamentoId]);
        $fechamento['auditoria'] = $stmtAudit->fetchAll(PDO::FETCH_ASSOC);

        return $fechamento;
    }

    /**
     * Busca por ID
     */
    public function obterPorId(int $id): ?array
    {
        $sql = "SELECT * FROM fechamento_mensal WHERE id = :id AND loja_id = :loja_id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'id' => $id,
            'loja_id' => $this->obterLojaAtualId(),
        ]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Fecha período fiscal
     */
    public function fechar(int $mes, int $ano, ?string $fechadoPor): bool
    {
        $sql = "
            UPDATE fechamento_mensal
            SET status = 'fechado',
                fechado_por = :fechado_por,
                fechado_em = CURRENT_TIMESTAMP
            WHERE mes_ref = :mes AND ano_ref = :ano AND loja_id = :loja_id
        ";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'mes' => $mes,
            'ano' => $ano,
            'fechado_por' => $fechadoPor,
            'loja_id' => $this->obterLojaAtualId(),
        ]);
    }

    private function obterLojaAtualId(): int
    {
        return $this->resolveCurrentStoreId($this->db);
    }
}
