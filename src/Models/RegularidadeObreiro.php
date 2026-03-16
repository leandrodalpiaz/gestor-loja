<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class RegularidadeObreiro
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Define regularidade de um obreiro para um mês
     */
    public function definir(int $obreiroId, int $mes, int $ano, string $status, ?string $observacao, int $definidoPor): bool
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
    public function obter(int $obreiroId, int $mes, int $ano): ?array
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
            SELECT ro.*, 
                   COALESCE(o1.nome_historico, o1.nome) as obreiro_nome,
                   COALESCE(o2.nome_historico, o2.nome) as definido_por_nome
            FROM regularidade_obreiro ro
            LEFT JOIN obreiros o1 ON ro.obreiro_id = o1.id
            LEFT JOIN obreiros o2 ON ro.definido_por = o2.id
            WHERE ro.mes_ref = :mes AND ro.ano_ref = :ano
            ORDER BY o1.nome_historico ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['mes' => $mes, 'ano' => $ano]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
}
