<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class Gestao
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function obterAberta(): ?array
    {
        $stmt = $this->db->query("
            SELECT *
            FROM public.gestoes
            WHERE status = 'aberta'
            ORDER BY inicio_em DESC, id DESC
            LIMIT 1
        ");

        $gestao = $stmt->fetch(PDO::FETCH_ASSOC);
        return $gestao ?: null;
    }

    public function listar(): array
    {
        $stmt = $this->db->query("
            SELECT *
            FROM public.gestoes
            ORDER BY
                CASE WHEN status = 'aberta' THEN 0 ELSE 1 END,
                inicio_em DESC,
                id DESC
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function criar(string $titulo, string $inicioEm, ?string $observacao = null): void
    {
        $aberta = $this->obterAberta();
        if ($aberta) {
            throw new \RuntimeException('Ja existe uma gestao aberta. Encerre a atual antes de abrir outra.');
        }

        $stmt = $this->db->prepare("
            INSERT INTO public.gestoes (titulo, inicio_em, status, observacao, created_at, updated_at)
            VALUES (:titulo, :inicio_em, 'aberta', :observacao, NOW(), NOW())
        ");
        $stmt->execute([
            'titulo' => trim($titulo),
            'inicio_em' => $inicioEm,
            'observacao' => $observacao !== null && trim($observacao) !== '' ? trim($observacao) : null,
        ]);
    }

    public function encerrar(int $gestaoId, ?string $encerradaEm = null): void
    {
        $stmt = $this->db->prepare("
            UPDATE public.gestoes
               SET status = 'encerrada',
                   encerrada_em = COALESCE(:encerrada_em, CURRENT_DATE),
                   updated_at = NOW()
             WHERE id = :id
        ");
        $stmt->execute([
            'id' => $gestaoId,
            'encerrada_em' => $encerradaEm !== null && trim($encerradaEm) !== '' ? $encerradaEm : null,
        ]);
    }
}
