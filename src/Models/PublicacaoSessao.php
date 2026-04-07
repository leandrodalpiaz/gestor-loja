<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class PublicacaoSessao
{
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
            INSERT INTO public.publicacoes_sessao (
                sessao_id,
                tipo_publicacao,
                canal,
                conteudo,
                publicado_por
            ) VALUES (
                :sessao_id,
                :tipo_publicacao,
                :canal,
                :conteudo,
                :publicado_por
            )
        ");

        return $stmt->execute([
            'sessao_id' => $sessaoId,
            'tipo_publicacao' => $tipoPublicacao,
            'canal' => $canal,
            'conteudo' => $conteudo,
            'publicado_por' => $publicadoPor,
        ]);
    }

    public function listarPorSessao(int $sessaoId): array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM public.publicacoes_sessao
            WHERE sessao_id = :sessao_id
            ORDER BY publicado_em DESC, id DESC
        ");
        $stmt->execute(['sessao_id' => $sessaoId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
