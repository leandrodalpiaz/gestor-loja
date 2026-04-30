<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class ConteudoPublico
{
    private PDO $db;

    public const TIPOS = [
        'agenda',
        'convite',
        'noticia',
        'ad',
    ];

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function listarParaAdmin(): array
    {
        try {
            $stmt = $this->db->query(
                "SELECT *
                 FROM conteudos_publicos
                 ORDER BY publicado DESC, prioridade DESC, created_at DESC"
            );

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            // In ambientes recem-instalados a migracao pode ainda nao ter sido aplicada.
            error_log('ConteudoPublico::listarParaAdmin - ' . $e->getMessage());
            return [];
        }
    }

    public function listarPublicos(?string $tipo = null, int $limit = 12): array
    {
        $limit = max(1, min(50, $limit));
        $params = [];
        $where = "publicado = TRUE";

        if ($tipo !== null && $tipo !== '') {
            $where .= " AND tipo = :tipo";
            $params['tipo'] = $tipo;
        } else {
            $where .= " AND tipo IN ('agenda','convite','noticia')";
        }

        $sql = "SELECT *
                FROM conteudos_publicos
                WHERE {$where}
                  AND (inicio_em IS NULL OR inicio_em <= CURRENT_DATE)
                  AND (fim_em IS NULL OR fim_em >= CURRENT_DATE)
                ORDER BY prioridade DESC, COALESCE(inicio_em, created_at::date) DESC, created_at DESC
                LIMIT {$limit}";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log('ConteudoPublico::listarPublicos - ' . $e->getMessage());
            return [];
        }
    }

    public function listarAdsPublicos(int $limit = 3): array
    {
        $limit = max(0, min(6, $limit));
        if ($limit === 0) {
            return [];
        }

        try {
            $stmt = $this->db->prepare(
                "SELECT *
                 FROM conteudos_publicos
                 WHERE publicado = TRUE
                   AND tipo = 'ad'
                   AND (inicio_em IS NULL OR inicio_em <= CURRENT_DATE)
                   AND (fim_em IS NULL OR fim_em >= CURRENT_DATE)
                 ORDER BY prioridade DESC, created_at DESC
                 LIMIT {$limit}"
            );
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log('ConteudoPublico::listarAdsPublicos - ' . $e->getMessage());
            return [];
        }
    }

    public function salvar(array $input): int
    {
        $id = (int) ($input['id'] ?? 0);
        $tipo = strtolower(trim((string) ($input['tipo'] ?? '')));
        $titulo = trim((string) ($input['titulo'] ?? ''));
        $resumo = trim((string) ($input['resumo'] ?? ''));
        $corpo = trim((string) ($input['corpo'] ?? ''));
        $linkUrl = trim((string) ($input['link_url'] ?? ''));
        $imagemUrl = trim((string) ($input['imagem_url'] ?? ''));
        $prioridade = (int) ($input['prioridade'] ?? 0);
        $publicado = !empty($input['publicado']);
        $inicioEm = trim((string) ($input['inicio_em'] ?? ''));
        $fimEm = trim((string) ($input['fim_em'] ?? ''));

        if ($titulo === '' || !in_array($tipo, self::TIPOS, true)) {
            throw new \InvalidArgumentException('Tipo e título são obrigatórios.');
        }

        $payload = [
            'tipo' => $tipo,
            'titulo' => $titulo,
            'resumo' => $resumo !== '' ? $resumo : null,
            'corpo' => $corpo !== '' ? $corpo : null,
            'link_url' => $linkUrl !== '' ? $linkUrl : null,
            'imagem_url' => $imagemUrl !== '' ? $imagemUrl : null,
            'prioridade' => $prioridade,
            'publicado' => $publicado,
            'inicio_em' => $inicioEm !== '' ? $inicioEm : null,
            'fim_em' => $fimEm !== '' ? $fimEm : null,
        ];

        if ($id > 0) {
            $payload['id'] = $id;
            $stmt = $this->db->prepare(
                "UPDATE conteudos_publicos
                 SET tipo = :tipo,
                     titulo = :titulo,
                     resumo = :resumo,
                     corpo = :corpo,
                     link_url = :link_url,
                     imagem_url = :imagem_url,
                     prioridade = :prioridade,
                     publicado = :publicado,
                     inicio_em = :inicio_em,
                     fim_em = :fim_em,
                     updated_at = NOW()
                 WHERE id = :id"
            );
            $stmt->execute($payload);
            return $id;
        }

        $stmt = $this->db->prepare(
            "INSERT INTO conteudos_publicos
                (tipo, titulo, resumo, corpo, link_url, imagem_url, prioridade, publicado, inicio_em, fim_em)
             VALUES
                (:tipo, :titulo, :resumo, :corpo, :link_url, :imagem_url, :prioridade, :publicado, :inicio_em, :fim_em)
             RETURNING id"
        );
        $stmt->execute($payload);

        return (int) $stmt->fetchColumn();
    }

    public function excluir(int $id): void
    {
        if ($id <= 0) {
            return;
        }

        $stmt = $this->db->prepare("DELETE FROM conteudos_publicos WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }
}


