<?php

namespace App\Models;

use App\Config\Database;
use App\Core\Tenant\ResolvesStoreTenant;
use PDO;

class TroncoSolidariedade
{
    use ResolvesStoreTenant;

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
            INSERT INTO tronco_solidariedade (loja_id, tipo, valor, data_mov, descricao, sessao_ref, created_by)
            VALUES (:loja_id, :tipo, :valor, :data_mov, :descricao, :sessao_ref, :created_by)
        ";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'loja_id' => $this->obterLojaAtualId(),
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
              AND loja_id = :loja_id
            ORDER BY data_mov DESC
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
              AND loja_id = :loja_id
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'ate_data' => $ateData,
            'loja_id' => $this->obterLojaAtualId(),
        ]);
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
              AND loja_id = :loja_id
            GROUP BY tipo
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'mes' => $mes,
            'ano' => $ano,
            'loja_id' => $this->obterLojaAtualId(),
        ]);
        
        $totais = ['entrada' => 0, 'saida' => 0];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $totais[$row['tipo']] = (float) $row['total'];
        }
        
        return $totais;
    }

    /**
     * Lista movimentações recentes do tronco
     */
    public function listarRecentes(int $limite = 50): array
    {
        $sql = "
            SELECT * FROM tronco_solidariedade
            WHERE loja_id = :loja_id
            ORDER BY data_mov DESC, id DESC
            LIMIT :limite
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':loja_id', $this->obterLojaAtualId(), PDO::PARAM_INT);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function obterLojaAtualId(): int
    {
        return $this->resolveCurrentStoreId($this->db);
    }
}
