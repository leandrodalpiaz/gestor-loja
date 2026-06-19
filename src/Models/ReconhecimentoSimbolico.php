<?php
declare(strict_types=1);

namespace App\Models;

use App\Config\Database;
use App\Core\Tenant\ResolvesStoreTenant;
use PDO;

class ReconhecimentoSimbolico
{
    use ResolvesStoreTenant;

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->ensureTable();
    }

    private function ensureTable(): void
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS public.reconhecimentos_simbolicos (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                loja_id INTEGER NOT NULL,
                obreiro_id UUID NOT NULL REFERENCES public.obreiros(id) ON DELETE CASCADE,
                orador_id UUID NOT NULL REFERENCES public.obreiros(id) ON DELETE CASCADE,
                tipo VARCHAR(50) NOT NULL, -- 'gratidao'|'constancia'|'servico_silencioso'|'marco_formativo'
                descricao TEXT NOT NULL,
                created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
                updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
            )
        ");

        $this->db->exec("
            CREATE INDEX IF NOT EXISTS idx_reconhecimentos_simbolicos_loja 
            ON public.reconhecimentos_simbolicos (loja_id)
        ");

        $this->db->exec("
            CREATE INDEX IF NOT EXISTS idx_reconhecimentos_simbolicos_obreiro 
            ON public.reconhecimentos_simbolicos (obreiro_id)
        ");
    }

    private function obterLojaAtualId(): int
    {
        return $this->resolveCurrentStoreId($this->db);
    }

    public function criar(array $dados, string $oradorId): bool
    {
        $obreiroId = trim((string) ($dados['obreiro_id'] ?? ''));
        $tipo = trim((string) ($dados['tipo'] ?? ''));
        $descricao = trim((string) ($dados['descricao'] ?? ''));

        if ($obreiroId === '' || $tipo === '' || $descricao === '') {
            return false;
        }

        $stmt = $this->db->prepare("
            INSERT INTO public.reconhecimentos_simbolicos (
                loja_id, obreiro_id, orador_id, tipo, descricao
            ) VALUES (
                :loja_id, :obreiro_id, :orador_id, :tipo, :descricao
            )
        ");

        return $stmt->execute([
            'loja_id' => $this->obterLojaAtualId(),
            'obreiro_id' => $obreiroId,
            'orador_id' => $oradorId,
            'tipo' => $tipo,
            'descricao' => $descricao,
        ]);
    }

    public function listarPorLoja(): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                r.*, 
                COALESCE(NULLIF(o.nome_historico, ''), o.nome) AS obreiro_nome,
                COALESCE(NULLIF(ora.nome_historico, ''), ora.nome) AS orador_nome
            FROM public.reconhecimentos_simbolicos r
            JOIN public.obreiros o ON o.id = r.obreiro_id
            JOIN public.obreiros ora ON ora.id = r.orador_id
            WHERE r.loja_id = :loja_id
            ORDER BY r.created_at DESC
        ");

        $stmt->execute([
            'loja_id' => $this->obterLojaAtualId(),
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function deletar(string $id, string $oradorId): bool
    {
        if (trim($id) === '') {
            return false;
        }

        $stmt = $this->db->prepare("
            DELETE FROM public.reconhecimentos_simbolicos
            WHERE id = :id 
              AND loja_id = :loja_id
        ");

        return $stmt->execute([
            'id' => $id,
            'loja_id' => $this->obterLojaAtualId(),
        ]);
    }
}
