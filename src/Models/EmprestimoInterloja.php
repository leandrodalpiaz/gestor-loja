<?php

namespace App\Models;

use App\Config\Database;
use App\Core\Tenant\ResolvesStoreTenant;
use PDO;
use Throwable;

class EmprestimoInterloja
{
    use ResolvesStoreTenant;

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function solicitar(int $acervoId, int $lojaOrigemId, string $obreiroId, ?string $observacao = null): bool
    {
        try {
            $lojaDestinoId = $this->buscarLojaAtualId();
            if ($lojaOrigemId === $lojaDestinoId) {
                return false;
            }

            $stmtAcervo = $this->db->prepare("SELECT loja_id FROM acervo WHERE id = :id AND ativo = TRUE LIMIT 1");
            $stmtAcervo->execute(['id' => $acervoId]);
            $acervoLojaId = (int) ($stmtAcervo->fetchColumn() ?: 0);
            if ($acervoLojaId <= 0 || $acervoLojaId !== $lojaOrigemId) {
                return false;
            }

            $stmt = $this->db->prepare(
                "INSERT INTO emprestimos_interloja (
                    loja_origem_id,
                    loja_destino_id,
                    acervo_id,
                    obreiro_id,
                    status,
                    observacao
                 ) VALUES (
                    :loja_origem_id,
                    :loja_destino_id,
                    :acervo_id,
                    :obreiro_id,
                    'solicitado',
                    :observacao
                 )"
            );

            return (bool) $stmt->execute([
                'loja_origem_id' => $lojaOrigemId,
                'loja_destino_id' => $lojaDestinoId,
                'acervo_id' => $acervoId,
                'obreiro_id' => $obreiroId,
                'observacao' => $observacao,
            ]);
        } catch (Throwable $e) {
            error_log('[emprestimo_interloja.solicitar] ' . $e->getMessage());
            return false;
        }
    }

    private function buscarLojaAtualId(): int
    {
        return $this->resolveCurrentStoreId($this->db);
    }
}
