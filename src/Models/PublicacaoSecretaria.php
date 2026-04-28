<?php

namespace App\Models;

use App\Config\Database;
use App\Core\Tenant\ResolvesStoreTenant;
use PDO;

class PublicacaoSecretaria
{
    use ResolvesStoreTenant;

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function criar(array $data, ?string $autorId = null): bool
    {
        $status = trim((string) ($data['status_publicacao'] ?? 'rascunho'));
        $publicadoEm = $status === 'publicado' ? ($data['publicado_em'] ?? date('c')) : null;

        $sessaoId = trim((string) ($data['sessao_id'] ?? ''));
        $lojaId = $this->obterLojaAtualId();
        if ($sessaoId !== '') {
            $stmt = $this->db->prepare("
                INSERT INTO publicacoes_secretaria (
                    sessao_id,
                    tipo_publicacao,
                    titulo,
                    origem,
                    conteudo,
                    canal_destino,
                    status_publicacao,
                    publicado_por,
                    publicado_em,
                    observacao,
                    updated_at
                ) SELECT
                    :sessao_id,
                    :tipo_publicacao,
                    :titulo,
                    :origem,
                    :conteudo,
                    :canal_destino,
                    :status_publicacao,
                    :publicado_por,
                    :publicado_em,
                    :observacao,
                    NOW()
                FROM sessoes s
                WHERE s.id = :sessao_id
                  AND s.loja_id = :loja_id
            ");
        } else {
            $stmt = $this->db->prepare("
                INSERT INTO publicacoes_secretaria (
                    sessao_id,
                    tipo_publicacao,
                    titulo,
                    origem,
                    conteudo,
                    canal_destino,
                    status_publicacao,
                    publicado_por,
                    publicado_em,
                    observacao,
                    updated_at
                ) VALUES (
                    NULL,
                    :tipo_publicacao,
                    :titulo,
                    :origem,
                    :conteudo,
                    :canal_destino,
                    :status_publicacao,
                    :publicado_por,
                    :publicado_em,
                    :observacao,
                    NOW()
                )
            ");
        }

        return $stmt->execute([
            'sessao_id' => $sessaoId !== '' ? (int) $sessaoId : null,
            'tipo_publicacao' => trim((string) ($data['tipo_publicacao'] ?? 'comunicado')) ?: 'comunicado',
            'titulo' => trim((string) ($data['titulo'] ?? '')),
            'origem' => trim((string) ($data['origem'] ?? '')) ?: null,
            'conteudo' => trim((string) ($data['conteudo'] ?? '')) ?: null,
            'canal_destino' => trim((string) ($data['canal_destino'] ?? 'grupo')) ?: 'grupo',
            'status_publicacao' => $status !== '' ? $status : 'rascunho',
            'publicado_por' => $autorId,
            'publicado_em' => $publicadoEm,
            'observacao' => trim((string) ($data['observacao'] ?? '')) ?: null,
            'loja_id' => $lojaId,
        ]);
    }

    public function listarRecentes(int $limite = 50): array
    {
        $limite = max(1, min($limite, 200));
        $stmt = $this->db->prepare("
            SELECT
                ps.*,
                s.titulo AS sessao_titulo
            FROM publicacoes_secretaria ps
            LEFT JOIN sessoes s ON s.id = ps.sessao_id
            WHERE (ps.sessao_id IS NULL OR s.loja_id = :loja_id)
            ORDER BY ps.created_at DESC, ps.id DESC
            LIMIT :limite
        ");
        $stmt->bindValue('loja_id', $this->obterLojaAtualId(), PDO::PARAM_INT);
        $stmt->bindValue('limite', $limite, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function obterLojaAtualId(): int
    {
        return $this->resolveCurrentStoreId($this->db);
    }
}
