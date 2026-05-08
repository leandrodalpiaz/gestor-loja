<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class TrabalhoSubmissao
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    private function lojaId(): int
    {
        return (int) ($_SESSION['tenant_id'] ?? 0);
    }

    public function listarPorObreiro(string $obreiroId, int $limite = 80): array
    {
        $limite = max(1, min($limite, 200));
        $stmt = $this->db->prepare("
            SELECT *
            FROM public.trabalhos_submissoes
            WHERE loja_id = :loja_id
              AND obreiro_id = :obreiro_id
            ORDER BY created_at DESC
            LIMIT :limite
        ");
        $stmt->bindValue('loja_id', $this->lojaId(), PDO::PARAM_INT);
        $stmt->bindValue('obreiro_id', $obreiroId);
        $stmt->bindValue('limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function criar(array $dados, string $obreiroId, string $grauObreiro): bool
    {
        $titulo = trim((string) ($dados['titulo'] ?? ''));
        $tipo = trim((string) ($dados['tipo_trabalho'] ?? 'peca_arquitetura')) ?: 'peca_arquitetura';
        $sessaoId = (int) ($dados['sessao_id'] ?? 0);
        $pdfPath = trim((string) ($dados['arquivo_pdf_path'] ?? '')) ?: null;
        if ($titulo === '') {
            return false;
        }

        $grauNorm = strtolower(trim($grauObreiro));
        if (str_contains($grauNorm, 'aprendiz')) {
            $mentorTipo = 'primeiro_vigilante';
            $status = 'pendente_mentor';
        } elseif (str_contains($grauNorm, 'companheiro')) {
            $mentorTipo = 'segundo_vigilante';
            $status = 'pendente_mentor';
        } else {
            $mentorTipo = null;
            $status = 'pendente_secretaria';
        }

        $stmt = $this->db->prepare("
            INSERT INTO public.trabalhos_submissoes (
                loja_id, obreiro_id, grau_obreiro, tipo_trabalho, titulo, sessao_id, arquivo_pdf_path,
                status, mentor_tipo
            ) VALUES (
                :loja_id, :obreiro_id, :grau_obreiro, :tipo_trabalho, :titulo, :sessao_id, :arquivo_pdf_path,
                :status, :mentor_tipo
            )
        ");

        return (bool) $stmt->execute([
            'loja_id' => $this->lojaId(),
            'obreiro_id' => $obreiroId,
            'grau_obreiro' => $grauObreiro !== '' ? $grauObreiro : null,
            'tipo_trabalho' => $tipo,
            'titulo' => $titulo,
            'sessao_id' => $sessaoId > 0 ? $sessaoId : null,
            'arquivo_pdf_path' => $pdfPath,
            'status' => $status,
            'mentor_tipo' => $mentorTipo,
        ]);
    }

    public function listarPendentesMentor(string $mentorTipo, int $limite = 120): array
    {
        $limite = max(1, min($limite, 200));
        $stmt = $this->db->prepare("
            SELECT ts.*, COALESCE(o.nome_historico, o.nome) AS obreiro_nome
            FROM public.trabalhos_submissoes ts
            JOIN public.obreiros o ON o.id = ts.obreiro_id
            WHERE ts.loja_id = :loja_id
              AND ts.status = 'pendente_mentor'
              AND ts.mentor_tipo = :mentor_tipo
            ORDER BY ts.created_at ASC
            LIMIT :limite
        ");
        $stmt->bindValue('loja_id', $this->lojaId(), PDO::PARAM_INT);
        $stmt->bindValue('mentor_tipo', $mentorTipo);
        $stmt->bindValue('limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function decidirMentor(string $submissaoId, string $mentorId, string $decisao, ?string $observacao = null): bool
    {
        $decisao = $decisao === 'aprovar' ? 'aprovado' : 'rejeitado';
        $novoStatus = $decisao === 'aprovado' ? 'pendente_secretaria' : 'rejeitado';
        $stmt = $this->db->prepare("
            UPDATE public.trabalhos_submissoes
            SET mentor_decisao = :decisao,
                mentor_observacao = :observacao,
                mentor_decidido_por = :mentor_id,
                mentor_decidido_em = NOW(),
                status = :status,
                updated_at = NOW()
            WHERE id = :id
              AND loja_id = :loja_id
              AND status = 'pendente_mentor'
        ");
        return (bool) $stmt->execute([
            'decisao' => $decisao,
            'observacao' => $observacao !== null && trim($observacao) !== '' ? trim($observacao) : null,
            'mentor_id' => $mentorId,
            'status' => $novoStatus,
            'id' => $submissaoId,
            'loja_id' => $this->lojaId(),
        ]);
    }

    public function listarPendentesSecretaria(int $limite = 200): array
    {
        $limite = max(1, min($limite, 300));
        $stmt = $this->db->prepare("
            SELECT ts.*, COALESCE(o.nome_historico, o.nome) AS obreiro_nome
            FROM public.trabalhos_submissoes ts
            JOIN public.obreiros o ON o.id = ts.obreiro_id
            WHERE ts.loja_id = :loja_id
              AND ts.status = 'pendente_secretaria'
            ORDER BY ts.created_at ASC
            LIMIT :limite
        ");
        $stmt->bindValue('loja_id', $this->lojaId(), PDO::PARAM_INT);
        $stmt->bindValue('limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function arquivarSecretaria(string $submissaoId, string $secretarioId, array $payloadTrabalhoSessao): bool
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                SELECT *
                FROM public.trabalhos_submissoes
                WHERE id = :id
                  AND loja_id = :loja_id
                  AND status = 'pendente_secretaria'
                LIMIT 1
            ");
            $stmt->execute(['id' => $submissaoId, 'loja_id' => $this->lojaId()]);
            $sub = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
            if (!$sub) {
                $this->db->rollBack();
                return false;
            }

            $sessaoId = (int) ($payloadTrabalhoSessao['sessao_id'] ?? 0);
            if ($sessaoId <= 0) {
                $sessaoId = (int) ($sub['sessao_id'] ?? 0);
            }
            if ($sessaoId <= 0) {
                $this->db->rollBack();
                return false;
            }

            $ok = (new TrabalhoSessao())->criar([
                'sessao_id' => $sessaoId,
                'tipo_trabalho' => (string) ($payloadTrabalhoSessao['tipo_trabalho'] ?? ($sub['tipo_trabalho'] ?? 'peca_arquitetura')),
                'titulo' => (string) ($payloadTrabalhoSessao['titulo'] ?? ($sub['titulo'] ?? '')),
                'autor_id' => (string) ($payloadTrabalhoSessao['autor_id'] ?? ($sub['obreiro_id'] ?? '')) ?: null,
                'autor_nome_livre' => null,
                'arquivo_pdf_path' => (string) ($payloadTrabalhoSessao['arquivo_pdf_path'] ?? ($sub['arquivo_pdf_path'] ?? '')) ?: null,
                'status_envio_potencia' => (string) ($payloadTrabalhoSessao['status_envio_potencia'] ?? 'pendente'),
                'enviado_potencia_em' => null,
                'observacao' => (string) ($payloadTrabalhoSessao['observacao'] ?? null),
            ], $secretarioId);

            if (!$ok) {
                $this->db->rollBack();
                return false;
            }

            // Vincular o último trabalho criado (mesma sessão + título + autor) como referência.
            $refStmt = $this->db->prepare("
                SELECT id
                FROM trabalhos_sessao
                WHERE sessao_id = :sessao_id
                  AND titulo = :titulo
                  AND (autor_id = :autor_id OR (:autor_id IS NULL AND autor_id IS NULL))
                ORDER BY id DESC
                LIMIT 1
            ");
            $refStmt->execute([
                'sessao_id' => $sessaoId,
                'titulo' => (string) ($payloadTrabalhoSessao['titulo'] ?? ($sub['titulo'] ?? '')),
                'autor_id' => (string) ($payloadTrabalhoSessao['autor_id'] ?? ($sub['obreiro_id'] ?? '')) ?: null,
            ]);
            $trabalhoSessaoId = (int) ($refStmt->fetchColumn() ?: 0);

            $upd = $this->db->prepare("
                UPDATE public.trabalhos_submissoes
                SET status = 'arquivado',
                    arquivado_trabalho_sessao_id = :trabalho_id,
                    arquivado_por = :secretario_id,
                    arquivado_em = NOW(),
                    updated_at = NOW()
                WHERE id = :id
                  AND loja_id = :loja_id
            ");
            $upd->execute([
                'trabalho_id' => $trabalhoSessaoId > 0 ? $trabalhoSessaoId : null,
                'secretario_id' => $secretarioId,
                'id' => $submissaoId,
                'loja_id' => $this->lojaId(),
            ]);

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return false;
        }
    }
}
