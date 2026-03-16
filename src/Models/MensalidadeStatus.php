<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class MensalidadeStatus
{
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
              AND mes_ref = :mes 
              AND ano_ref = :ano
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['obreiro_id' => $obreiroId, 'mes' => $mes, 'ano' => $ano]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Registra ou atualiza status de mensalidade
     */
    public function registrar(string $obreiroId, int $mes, int $ano, string $status, ?int $lancamentoId = null, ?string $nota = null): bool
    {
        $sql = "
            INSERT INTO mensalidades_status (obreiro_id, mes_ref, ano_ref, status, lancamento_id, nota)
            VALUES (:obreiro_id, :mes, :ano, :status, :lancamento_id, :nota)
            ON CONFLICT (obreiro_id, mes_ref, ano_ref)
            DO UPDATE SET 
                status = EXCLUDED.status,
                lancamento_id = EXCLUDED.lancamento_id,
                nota = EXCLUDED.nota,
                atualizado_em = CURRENT_TIMESTAMP
        ";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
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
              AND (ms.status = 'pendente' OR ms.id IS NULL)
            ORDER BY o.nome_historico ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['mes' => $mes, 'ano' => $ano]);
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
            GROUP BY status
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['mes' => $mes, 'ano' => $ano]);
        
        $resumo = ['pago' => 0, 'pendente' => 0, 'isento' => 0, 'dispensado' => 0];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if (isset($resumo[$row['status']])) {
                $resumo[$row['status']] = (int) $row['quantidade'];
            }
        }
        
        return $resumo;
    }
}
