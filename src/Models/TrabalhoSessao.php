<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class TrabalhoSessao
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function criar(array $data, ?string $autorRegistroId = null): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO public.trabalhos_sessao (
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
            ) VALUES (
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
            )
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
        ]);
    }

    public function listarRecentes(int $limite = 50): array
    {
        $limite = max(1, min($limite, 200));
        $stmt = $this->db->prepare("
            SELECT
                ts.*,
                s.titulo AS sessao_titulo,
                s.data_hora_inicio,
                COALESCE(o.nome_historico, o.nome) AS autor_nome
            FROM public.trabalhos_sessao ts
            JOIN public.sessoes s ON s.id = ts.sessao_id
            LEFT JOIN public.obreiros o ON o.id = ts.autor_id
            ORDER BY ts.created_at DESC, ts.id DESC
            LIMIT :limite
        ");
        $stmt->bindValue('limite', $limite, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
