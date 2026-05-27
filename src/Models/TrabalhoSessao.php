<?php

namespace App\Models;

use App\Config\Database;
use App\Core\Tenant\ResolvesStoreTenant;
use PDO;

class TrabalhoSessao
{
    use ResolvesStoreTenant;

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function criar(array $data, ?string $autorRegistroId = null): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO trabalhos_sessao (
                sessao_id,
                tipo_trabalho,
                titulo,
                autor_id,
                autor_nome_livre,
                arquivo_pdf_path,
                status_envio_potencia,
                enviado_potencia_em,
                criado_por,
                observacao,
                updated_at
            ) SELECT
                :sessao_id,
                :tipo_trabalho,
                :titulo,
                :autor_id,
                :autor_nome_livre,
                :arquivo_pdf_path,
                :status_envio_potencia,
                :enviado_potencia_em,
                :criado_por,
                :observacao,
                NOW()
            FROM sessoes s
            WHERE s.id = :sessao_id
              AND s.loja_id = :loja_id
        ");

        $status = trim((string) ($data['status_envio_potencia'] ?? 'pendente'));
        $enviadoEm = $status === 'enviado' ? ($data['enviado_potencia_em'] ?? date('c')) : null;

        return $stmt->execute([
            'sessao_id' => (int) ($data['sessao_id'] ?? 0),
            'tipo_trabalho' => trim((string) ($data['tipo_trabalho'] ?? 'peca_arquitetura')) ?: 'peca_arquitetura',
            'titulo' => trim((string) ($data['titulo'] ?? '')),
            'autor_id' => trim((string) ($data['autor_id'] ?? '')) ?: null,
            'autor_nome_livre' => trim((string) ($data['autor_nome_livre'] ?? '')) ?: null,
            'arquivo_pdf_path' => trim((string) ($data['arquivo_pdf_path'] ?? '')) ?: null,
            'status_envio_potencia' => $status !== '' ? $status : 'pendente',
            'enviado_potencia_em' => $enviadoEm,
            'criado_por' => $autorRegistroId,
            'observacao' => trim((string) ($data['observacao'] ?? '')) ?: null,
            'loja_id' => $this->obterLojaAtualId(),
        ]);
    }

    public function listarRecentes(int $limite = 50): array
    {
        $limite = max(1, min($limite, 200));
        $stmt = $this->db->prepare("
            SELECT
                ts.*,
                s.titulo AS sessao_titulo,
                s.grau_sessao,
                s.data_hora_inicio,
                COALESCE(o.nome_historico, o.nome) AS autor_nome
            FROM trabalhos_sessao ts
            JOIN sessoes s ON s.id = ts.sessao_id
            LEFT JOIN obreiros o ON o.id = ts.autor_id
            WHERE s.loja_id = :loja_id
            ORDER BY ts.created_at DESC, ts.id DESC
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
