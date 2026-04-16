<?php

namespace App\Models;

use App\Config\Database;
use App\Core\Tenant\ResolvesStoreTenant;
use PDO;

class ReacaoBiblioteca
{
    use ResolvesStoreTenant;

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function definir(int $acervoId, string $obreiroId, bool $gostei): bool
    {
        if (!$this->acervoPertenceLojaAtual($acervoId)) {
            return false;
        }

        $sql = "INSERT INTO biblioteca_reacoes (acervo_id, obreiro_id, gostei, atualizado_em)
                VALUES (:acervo_id, :obreiro_id, :gostei, CURRENT_TIMESTAMP)
                ON CONFLICT (acervo_id, obreiro_id)
                DO UPDATE SET gostei = EXCLUDED.gostei, atualizado_em = CURRENT_TIMESTAMP";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'acervo_id' => $acervoId,
            'obreiro_id' => $obreiroId,
            'gostei' => $gostei,
        ]);
    }

    private function acervoPertenceLojaAtual(int $acervoId): bool
    {
        $stmt = $this->db->prepare(
            "SELECT 1
             FROM acervo
             WHERE id = :id
               AND loja_id = :loja_id
               AND ativo = TRUE
             LIMIT 1"
        );
        $stmt->execute([
            'id' => $acervoId,
            'loja_id' => $this->buscarLojaAtualId(),
        ]);

        return $stmt->fetchColumn() !== false;
    }

    private function buscarLojaAtualId(): int
    {
        return $this->resolveCurrentStoreId($this->db);
    }
}
