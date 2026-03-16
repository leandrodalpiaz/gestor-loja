<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class CategoriaFinanceira
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Obtém todas as categorias ativas
     */
    public function obterTodas(): array
    {
        $sql = "
            SELECT * FROM categorias_financeiras
            WHERE ativo = true
            ORDER BY principal DESC, tipo ASC, nome ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtém categorias por tipo
     */
    public function obterPorTipo(string $tipo): array
    {
        $sql = "
            SELECT * FROM categorias_financeiras
            WHERE tipo = :tipo AND ativo = true
            ORDER BY principal DESC, nome ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['tipo' => $tipo]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtém categoria por ID
     */
    public function obterPorId(int $id): ?array
    {
        $sql = "SELECT * FROM categorias_financeiras WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }
}
