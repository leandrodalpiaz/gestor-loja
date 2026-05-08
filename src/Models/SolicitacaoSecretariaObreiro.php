<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class SolicitacaoSecretariaObreiro
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    private function lojaId(): string
    {
        return trim((string) ($_SESSION['tenant_id'] ?? ''));
    }

    public function listarPorObreiro(string $obreiroId): array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM public.solicitacoes_secretaria_obreiro
            WHERE loja_id = :loja_id
              AND obreiro_id = :obreiro_id
            ORDER BY created_at DESC
        ");
        $stmt->execute([
            'loja_id' => (int) $this->lojaId(),
            'obreiro_id' => $obreiroId,
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function criar(array $dados, string $obreiroId): bool
    {
        $tipo = trim((string) ($dados['tipo_solicitacao'] ?? ''));
        $justificativa = trim((string) ($dados['justificativa'] ?? ''));
        $valorSolicitado = trim((string) ($dados['valor_solicitado'] ?? ''));

        if ($tipo === '' || $justificativa === '') {
            return false;
        }

        $stmt = $this->db->prepare("
            INSERT INTO public.solicitacoes_secretaria_obreiro (
                loja_id, obreiro_id, tipo_solicitacao, valor_atual, valor_solicitado, justificativa, status
            ) VALUES (
                :loja_id, :obreiro_id, :tipo, NULL, :valor_solicitado, :justificativa, 'pendente'
            )
        ");

        return (bool) $stmt->execute([
            'loja_id' => (int) $this->lojaId(),
            'obreiro_id' => $obreiroId,
            'tipo' => $tipo,
            'valor_solicitado' => $valorSolicitado !== '' ? $valorSolicitado : null,
            'justificativa' => $justificativa,
        ]);
    }
}
