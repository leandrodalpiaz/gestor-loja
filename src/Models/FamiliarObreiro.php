<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class FamiliarObreiro
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

    private function mapCodVinculo(string $parentesco): ?int
    {
        return match ($parentesco) {
            'esposa', 'esposo' => 2,
            'filha' => 3,
            'filho' => 4,
            default => null,
        };
    }

    private function obterNomeObreiro(string $obreiroId): string
    {
        $stmt = $this->db->prepare("
            SELECT COALESCE(NULLIF(nome_historico, ''), nome) AS nome_ref
            FROM public.obreiros
            WHERE loja_id = :loja_id
              AND id = :id
            LIMIT 1
        ");
        $stmt->execute([
            'loja_id' => (int) $this->lojaId(),
            'id' => $obreiroId,
        ]);
        return trim((string) ($stmt->fetchColumn() ?: ''));
    }

    private function sincronizarEfemerideFamilia(string $obreiroId, string $nomeFamiliar, string $parentesco, ?string $dataNascimento): void
    {
        $cod = $this->mapCodVinculo($parentesco);
        $nomeIrmao = $this->obterNomeObreiro($obreiroId);
        if ($cod === null || $nomeIrmao === '' || $dataNascimento === null || trim($dataNascimento) === '') {
            return;
        }

        // Atualiza/insere registro "Familia" para consumo de efemérides.
        $existsStmt = $this->db->prepare("
            SELECT id
            FROM efemerides_registros
            WHERE loja_id = :loja_id
              AND ativo = true
              AND tipo = 'Familia'
              AND cod_vinculo = :cod
              AND lower(nome) = lower(:nome)
              AND lower(coalesce(parentesco, '')) = lower(:irmao_ref)
            ORDER BY id ASC
            LIMIT 1
        ");
        $existsStmt->execute([
            'loja_id' => (int) $this->lojaId(),
            'cod' => $cod,
            'nome' => $nomeFamiliar,
            'irmao_ref' => $nomeIrmao,
        ]);
        $efId = (int) ($existsStmt->fetchColumn() ?: 0);

        if ($efId > 0) {
            $upd = $this->db->prepare("
                UPDATE efemerides_registros
                SET data_evento = :data_evento,
                    vinculo = :vinculo,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
                  AND loja_id = :loja_id
            ");
            $upd->execute([
                'id' => $efId,
                'loja_id' => (int) $this->lojaId(),
                'data_evento' => $dataNascimento,
                'vinculo' => $parentesco,
            ]);
            return;
        }

        $ins = $this->db->prepare("
            INSERT INTO efemerides_registros (
                loja_id, nome, tipo, data_evento, cod_vinculo, vinculo, parentesco, local, mensagem_custom, ativo, created_by
            ) VALUES (
                :loja_id, :nome, 'Familia', :data_evento, :cod_vinculo, :vinculo, :parentesco, NULL, NULL, true, NULL
            )
        ");
        $ins->execute([
            'loja_id' => (int) $this->lojaId(),
            'nome' => $nomeFamiliar,
            'data_evento' => $dataNascimento,
            'cod_vinculo' => $cod,
            'vinculo' => $parentesco,
            'parentesco' => $nomeIrmao,
        ]);
    }

    public function listarPorObreiro(string $obreiroId): array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM public.familiares_obreiro
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
        $nome = trim((string) ($dados['nome_completo'] ?? ''));
        $parentesco = strtolower(trim((string) ($dados['parentesco'] ?? '')));
        // Enteados seguem a regra geral de filhos (DB guarda como filho/filha para manter o CHECK constraint).
        if ($parentesco === 'enteado') {
            $parentesco = 'filho';
        } elseif ($parentesco === 'enteada') {
            $parentesco = 'filha';
        }
        if ($nome === '' || $parentesco === '') {
            return false;
        }

        $stmt = $this->db->prepare("
            INSERT INTO public.familiares_obreiro (
                loja_id, obreiro_id, nome_completo, parentesco,
                data_nascimento, data_casamento, falecido, observacao_secretaria,
                status_revisao
            ) VALUES (
                :loja_id, :obreiro_id, :nome_completo, :parentesco,
                :data_nascimento, :data_casamento, :falecido, NULL,
                'pendente'
            )
        ");

        $ok = (bool) $stmt->execute([
            'loja_id' => (int) $this->lojaId(),
            'obreiro_id' => $obreiroId,
            'nome_completo' => $nome,
            'parentesco' => $parentesco,
            'data_nascimento' => ($dados['data_nascimento'] ?? null) ?: null,
            'data_casamento' => ($dados['data_casamento'] ?? null) ?: null,
            'falecido' => !empty($dados['falecido']),
        ]);

        if ($ok) {
            $this->sincronizarEfemerideFamilia(
                $obreiroId,
                $nome,
                $parentesco,
                (($dados['data_nascimento'] ?? null) ?: null)
            );
        }

        return $ok;
    }

    public function atualizar(string $familiarId, array $dados, string $obreiroId): bool
    {
        $nome = trim((string) ($dados['nome_completo'] ?? ''));
        $parentesco = strtolower(trim((string) ($dados['parentesco'] ?? '')));
        if ($parentesco === 'enteado') {
            $parentesco = 'filho';
        } elseif ($parentesco === 'enteada') {
            $parentesco = 'filha';
        }
        if ($familiarId === '' || $nome === '' || $parentesco === '') {
            return false;
        }

        $stmt = $this->db->prepare("
            UPDATE public.familiares_obreiro
            SET nome_completo = :nome_completo,
                parentesco = :parentesco,
                data_nascimento = :data_nascimento,
                data_casamento = :data_casamento,
                falecido = :falecido,
                status_revisao = 'pendente',
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
              AND loja_id = :loja_id
              AND obreiro_id = :obreiro_id
        ");

        $ok = (bool) $stmt->execute([
            'id' => $familiarId,
            'loja_id' => (int) $this->lojaId(),
            'obreiro_id' => $obreiroId,
            'nome_completo' => $nome,
            'parentesco' => $parentesco,
            'data_nascimento' => ($dados['data_nascimento'] ?? null) ?: null,
            'data_casamento' => ($dados['data_casamento'] ?? null) ?: null,
            'falecido' => !empty($dados['falecido']),
        ]);

        if ($ok) {
            $this->sincronizarEfemerideFamilia(
                $obreiroId,
                $nome,
                $parentesco,
                (($dados['data_nascimento'] ?? null) ?: null)
            );
        }

        return $ok;
    }
}
