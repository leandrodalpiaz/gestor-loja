<?php

namespace App\Models;

use App\Config\Database;
use App\Core\Tenant\ResolvesStoreTenant;
use PDO;

class PublicacaoSessao
{
    use ResolvesStoreTenant;

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function registrar(
        int $sessaoId,
        string $tipoPublicacao,
        string $canal = 'erp',
        ?string $conteudo = null,
        ?string $publicadoPor = null
    ): bool {
        $stmt = $this->db->prepare("
            INSERT INTO publicacoes_sessao (
                sessao_id,
                tipo_publicacao,
                canal,
                conteudo,
                publicado_por
            ) SELECT
                :sessao_id,
                :tipo_publicacao,
                :canal,
                :conteudo,
                :publicado_por
            FROM sessoes s
            WHERE s.id = :sessao_id
              AND s.loja_id = :loja_id
        ");

        return $stmt->execute([
            'sessao_id' => $sessaoId,
            'tipo_publicacao' => $tipoPublicacao,
            'canal' => $canal,
            'conteudo' => $conteudo,
            'publicado_por' => $publicadoPor,
            'loja_id' => $this->obterLojaAtualId(),
        ]);
    }

    public function listarPorSessao(int $sessaoId): array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM publicacoes_sessao ps
            JOIN sessoes s ON s.id = ps.sessao_id
            WHERE ps.sessao_id = :sessao_id
              AND s.loja_id = :loja_id
            ORDER BY publicado_em DESC, id DESC
        ");
        $stmt->execute([
            'sessao_id' => $sessaoId,
            'loja_id' => $this->obterLojaAtualId(),
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function obterLojaAtualId(): int
    {
        return $this->resolveCurrentStoreId($this->db);
    }
}
