<?php

namespace App\Models;

use App\Config\Database;
use App\Core\Tenant\ResolvesStoreTenant;
use PDO;

class Gestao
{
    use ResolvesStoreTenant;

    private PDO $db;
    private AuditoriaAdministrativa $auditoria;
    private ?bool $suportaLojaId = null;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->auditoria = new AuditoriaAdministrativa();
        $this->garantirEscopoLoja();
    }

    public function obterAberta(): ?array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM public.gestoes
            WHERE loja_id = :loja_id
              AND status = 'aberta'
            ORDER BY inicio_em DESC, id DESC
            LIMIT 1
        ");
        $stmt->execute(['loja_id' => $this->obterLojaAtualId()]);

        $gestao = $stmt->fetch(PDO::FETCH_ASSOC);
        return $gestao ?: null;
    }

    public function listar(): array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM public.gestoes
            WHERE loja_id = :loja_id
            ORDER BY
                CASE WHEN status = 'aberta' THEN 0 ELSE 1 END,
                inicio_em DESC,
                id DESC
        ");
        $stmt->execute(['loja_id' => $this->obterLojaAtualId()]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function criar(string $titulo, string $inicioEm, ?string $observacao = null): void
    {
        $aberta = $this->obterAberta();
        if ($aberta) {
            throw new \RuntimeException('Ja existe uma gestao aberta. Encerre a atual antes de abrir outra.');
        }

        $stmt = $this->db->prepare("
            INSERT INTO public.gestoes (loja_id, titulo, inicio_em, status, observacao, created_at, updated_at)
            VALUES (:loja_id, :titulo, :inicio_em, 'aberta', :observacao, NOW(), NOW())
        ");
        $stmt->execute([
            'loja_id' => $this->obterLojaAtualId(),
            'titulo' => trim($titulo),
            'inicio_em' => $inicioEm,
            'observacao' => $observacao !== null && trim($observacao) !== '' ? trim($observacao) : null,
        ]);

        $this->auditoria->registrar(
            'admin',
            'gestao',
            null,
            'abertura',
            'Gestao aberta',
            [
                'titulo' => trim($titulo),
                'inicio_em' => $inicioEm,
                'observacao' => $observacao,
                'loja_id' => $this->obterLojaAtualId(),
            ],
            isset($_SESSION['usuario_id']) ? (string) $_SESSION['usuario_id'] : null
        );
    }

    public function encerrar(int $gestaoId, ?string $encerradaEm = null): void
    {
        $stmt = $this->db->prepare("
            UPDATE public.gestoes
               SET status = 'encerrada',
                   encerrada_em = COALESCE(:encerrada_em, CURRENT_DATE),
                   updated_at = NOW()
             WHERE id = :id
               AND loja_id = :loja_id
        ");
        $stmt->execute([
            'id' => $gestaoId,
            'loja_id' => $this->obterLojaAtualId(),
            'encerrada_em' => $encerradaEm !== null && trim($encerradaEm) !== '' ? $encerradaEm : null,
        ]);

        $this->auditoria->registrar(
            'admin',
            'gestao',
            (string) $gestaoId,
            'encerramento',
            'Gestao encerrada',
            [
                'gestao_id' => $gestaoId,
                'encerrada_em' => $encerradaEm,
                'loja_id' => $this->obterLojaAtualId(),
            ],
            isset($_SESSION['usuario_id']) ? (string) $_SESSION['usuario_id'] : null
        );
    }

    private function obterLojaAtualId(): int
    {
        return $this->resolveCurrentStoreId($this->db);
    }

    private function garantirEscopoLoja(): void
    {
        if (!$this->suportaLojaId()) {
            throw new \RuntimeException('Tabela public.gestoes sem coluna loja_id. Execute a migration de isolamento por loja.');
        }
    }

    private function suportaLojaId(): bool
    {
        if ($this->suportaLojaId !== null) {
            return $this->suportaLojaId;
        }

        $stmt = $this->db->prepare("
            SELECT 1
            FROM information_schema.columns
            WHERE table_schema = 'public'
              AND table_name = 'gestoes'
              AND column_name = 'loja_id'
            LIMIT 1
        ");
        $stmt->execute();

        $this->suportaLojaId = (bool) $stmt->fetchColumn();
        return $this->suportaLojaId;
    }
}
