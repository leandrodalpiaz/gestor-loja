<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class TroncoSolidariedade
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Registra movimento no tronco
     */
    public function registrar(array $data): bool
    {
        $sql = "
            INSERT INTO tronco_solidariedade (tipo, valor, data_mov, descricao, sessao_ref, created_by)
            VALUES (:tipo, :valor, :data_mov, :descricao, :sessao_ref, :created_by)
        ";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'tipo' => $data['tipo'] ?? 'entrada',
            'valor' => $data['valor'] ?? 0,
            'data_mov' => $data['data_mov'] ?? date('Y-m-d'),
            'descricao' => $data['descricao'] ?? null,
            'sessao_ref' => $data['sessao_ref'] ?? null,
            'created_by' => $data['created_by'] ?? null,
        ]);
    }

    /**
     * Obtém movimento mensal
     */
    public function obterPorMes(int $mes, int $ano): array
    {
        $sql = "
            SELECT * FROM tronco_solidariedade
            WHERE EXTRACT(MONTH FROM data_mov) = :mes
              AND EXTRACT(YEAR FROM data_mov) = :ano
            ORDER BY data_mov DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['mes' => $mes, 'ano' => $ano]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Calcula saldo do tronco até uma data
     */
    public function obterSaldo(?string $ateData = null): float
    {
        if (!$ateData) {
            $ateData = date('Y-m-d');
        }

        $sql = "
            SELECT 
                COALESCE(SUM(CASE WHEN tipo = 'entrada' THEN valor ELSE -valor END), 0) as saldo
            FROM tronco_solidariedade
            WHERE data_mov <= :ate_data
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['ate_data' => $ateData]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (float) ($result['saldo'] ?? 0);
    }

    /**
     * Totais do tronco por mês
     */
    public function obterTotaisMes(int $mes, int $ano): array
    {
        $sql = "
            SELECT 
                tipo,
                SUM(valor) as total
            FROM tronco_solidariedade
            WHERE EXTRACT(MONTH FROM data_mov) = :mes
              AND EXTRACT(YEAR FROM data_mov) = :ano
            GROUP BY tipo
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['mes' => $mes, 'ano' => $ano]);
        
        $totais = ['entrada' => 0, 'saida' => 0];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $totais[$row['tipo']] = (float) $row['total'];
        }
        
        return $totais;
    }
}
