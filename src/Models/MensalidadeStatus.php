<?php

namespace App\Models;

use App\Config\Database;
use App\Core\Tenant\ResolvesStoreTenant;
use PDO;

class MensalidadeStatus
{
    use ResolvesStoreTenant;

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Obtém status de mensalidade para um obreiro/mês
     */
    public function obter(string $obreiroId, int $mes, int $ano): ?array
    {
        $sql = "
            SELECT * FROM mensalidades_status
            WHERE obreiro_id = :obreiro_id 
              AND loja_id = :loja_id
              AND mes_ref = :mes 
              AND ano_ref = :ano
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'obreiro_id' => $obreiroId,
            'loja_id' => $this->obterLojaAtualId(),
            'mes' => $mes,
            'ano' => $ano,
        ]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Registra ou atualiza status de mensalidade
     */
    public function registrar(string $obreiroId, int $mes, int $ano, string $status, ?int $lancamentoId = null, ?string $nota = null): bool
    {
        $sql = "
            INSERT INTO mensalidades_status (loja_id, obreiro_id, mes_ref, ano_ref, status, lancamento_id, nota)
            VALUES (:loja_id, :obreiro_id, :mes, :ano, :status, :lancamento_id, :nota)
            ON CONFLICT (obreiro_id, mes_ref, ano_ref)
            DO UPDATE SET 
                loja_id = EXCLUDED.loja_id,
                status = EXCLUDED.status,
                lancamento_id = EXCLUDED.lancamento_id,
                nota = EXCLUDED.nota,
                atualizado_em = CURRENT_TIMESTAMP
        ";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'loja_id' => $this->obterLojaAtualId(),
            'obreiro_id' => $obreiroId,
            'mes' => $mes,
            'ano' => $ano,
            'status' => $status,
            'lancamento_id' => $lancamentoId,
            'nota' => $nota,
        ]);
    }

    /**
     * Obtém resumo de inadimplência para um mês
     */
    public function obterInadimplentes(int $mes, int $ano): array
    {
        $sql = "
            SELECT o.id, o.nome_historico, o.nome, o.email,
                   ms.status, ms.nota, ms.atualizado_em
            FROM obreiros o
            LEFT JOIN mensalidades_status ms ON o.id = ms.obreiro_id 
                AND ms.mes_ref = :mes AND ms.ano_ref = :ano
            WHERE o.ativo = true
              AND o.loja_id = :loja_id
              AND (ms.status = 'pendente' OR ms.id IS NULL)
            ORDER BY o.nome_historico ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'mes' => $mes,
            'ano' => $ano,
            'loja_id' => $this->obterLojaAtualId(),
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtém resumo de status: pago/pendente/isento
     */
    public function obterResumoMes(int $mes, int $ano): array
    {
        $sql = "
            SELECT 
                status,
                COUNT(*) as quantidade
            FROM mensalidades_status
            WHERE mes_ref = :mes AND ano_ref = :ano
              AND loja_id = :loja_id
            GROUP BY status
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'mes' => $mes,
            'ano' => $ano,
            'loja_id' => $this->obterLojaAtualId(),
        ]);
        
        $resumo = ['pago' => 0, 'pendente' => 0, 'isento' => 0, 'dispensado' => 0];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if (isset($resumo[$row['status']])) {
                $resumo[$row['status']] = (int) $row['quantidade'];
            }
        }
        
        return $resumo;
    }

    private function obterLojaAtualId(): int
    {
        return $this->resolveCurrentStoreId($this->db);
    }
}
