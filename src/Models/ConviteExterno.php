<?php

namespace App\Models;

use App\Config\Database;
use App\Core\Tenant\ResolvesStoreTenant;
use PDO;

class ConviteExterno
{
    use ResolvesStoreTenant;

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function criar(array $data, array $arquivo = [], ?string $autorId = null): bool
    {
        $anexoBytes = null;
        $anexoMime = null;
        $anexoNome = null;
        if (($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK && is_uploaded_file((string) ($arquivo['tmp_name'] ?? ''))) {
            $conteudo = file_get_contents((string) $arquivo['tmp_name']);
            if ($conteudo !== false) {
                $anexoBytes = base64_encode($conteudo);
                $anexoMime = trim((string) ($arquivo['type'] ?? '')) ?: null;
                $anexoNome = trim((string) ($arquivo['name'] ?? '')) ?: null;
            }
        }

        $stmt = $this->db->prepare("
            INSERT INTO convites_externos (
                loja_id,
                source,
                external_source,
                external_id,
                integration_payload,
                tipo,
                titulo,
                loja_origem,
                potencia,
                grau,
                data_hora,
                cidade,
                local,
                prazo_confirmacao,
                contatos,
                valor,
                traje,
                descricao,
                texto_original,
                status,
                fixado,
                anexo_mime,
                anexo_nome,
                anexo_bytes,
                anexo_expires_at,
                updated_at
            ) VALUES (
                :loja_id,
                'external',
                :external_source,
                :external_id,
                CAST(:integration_payload AS jsonb),
                :tipo,
                :titulo,
                :loja_origem,
                :potencia,
                :grau,
                :data_hora,
                :cidade,
                :local,
                :prazo_confirmacao,
                :contatos,
                :valor,
                :traje,
                :descricao,
                :texto_original,
                :status,
                :fixado,
                :anexo_mime,
                :anexo_nome,
                :anexo_bytes,
                :anexo_expires_at,
                NOW()
            )
        ");

        $dataHora = trim((string) ($data['data_hora'] ?? ''));
        $prazo = trim((string) ($data['prazo_confirmacao'] ?? ''));
        $diasAnexo = max(1, (int) ($_ENV['APP_CONVITE_ANEXO_TTL_DIAS'] ?? 14));

        return $stmt->execute([
            'loja_id' => $this->obterLojaAtualId(),
            'external_source' => trim((string) ($data['external_source'] ?? 'manual')) ?: 'manual',
            'external_id' => trim((string) ($data['external_id'] ?? '')) ?: null,
            'integration_payload' => null,
            'tipo' => trim((string) ($data['tipo'] ?? 'outro')) ?: 'outro',
            'titulo' => trim((string) ($data['titulo'] ?? '')),
            'loja_origem' => trim((string) ($data['loja_origem'] ?? '')) ?: null,
            'potencia' => trim((string) ($data['potencia'] ?? '')) ?: null,
            'grau' => trim((string) ($data['grau'] ?? '')) ?: null,
            'data_hora' => $dataHora !== '' ? $dataHora : null,
            'cidade' => trim((string) ($data['cidade'] ?? '')) ?: null,
            'local' => trim((string) ($data['local'] ?? '')) ?: null,
            'prazo_confirmacao' => $prazo !== '' ? $prazo : null,
            'contatos' => trim((string) ($data['contatos'] ?? '')) ?: null,
            'valor' => trim((string) ($data['valor'] ?? '')) ?: null,
            'traje' => trim((string) ($data['traje'] ?? '')) ?: null,
            'descricao' => trim((string) ($data['descricao'] ?? '')) ?: null,
            'texto_original' => trim((string) ($data['texto_original'] ?? '')) ?: null,
            'status' => trim((string) ($data['status'] ?? 'rascunho')) ?: 'rascunho',
            'fixado' => !empty($data['fixado']) ? 'true' : 'false',
            'anexo_mime' => $anexoMime,
            'anexo_nome' => $anexoNome,
            'anexo_bytes' => $anexoBytes,
            'anexo_expires_at' => $anexoBytes !== null && $dataHora !== '' ? date('c', strtotime($dataHora . ' +' . $diasAnexo . ' days')) : null,
        ]);
    }

    public function listarRecentes(int $limite = 50): array
    {
        $limite = max(1, min($limite, 200));
        $stmt = $this->db->prepare("
            SELECT
                id,
                tipo,
                titulo,
                loja_origem,
                potencia,
                grau,
                data_hora,
                cidade,
                local,
                prazo_confirmacao,
                contatos,
                valor,
                traje,
                descricao,
                texto_original,
                status,
                fixado,
                anexo_mime,
                anexo_nome,
                anexo_expires_at,
                anexo_deleted_at,
                created_at,
                updated_at
            FROM convites_externos
            WHERE loja_id = :loja_id
            ORDER BY fixado DESC, data_hora ASC NULLS LAST, created_at DESC
            LIMIT :limite
        ");
        $stmt->bindValue('loja_id', $this->obterLojaAtualId(), PDO::PARAM_INT);
        $stmt->bindValue('limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($rows === []) {
            return [];
        }

        $ids = array_values(array_filter(array_map(static fn(array $r): int => (int) ($r['id'] ?? 0), $rows), static fn(int $v): bool => $v > 0));
        if ($ids === []) {
            return $rows;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sqlTotais = "
            SELECT convite_externo_id, COUNT(*) FILTER (WHERE status = 'confirmado') AS total_confirmados
            FROM presencas_convite_externo
            WHERE loja_id = ?
              AND convite_externo_id IN ($placeholders)
            GROUP BY convite_externo_id
        ";
        $stmtTotais = $this->db->prepare($sqlTotais);
        $params = array_merge([$this->obterLojaAtualId()], $ids);
        $stmtTotais->execute($params);
        $totais = [];
        foreach ($stmtTotais->fetchAll(PDO::FETCH_ASSOC) as $t) {
            $totais[(int) ($t['convite_externo_id'] ?? 0)] = (int) ($t['total_confirmados'] ?? 0);
        }

        foreach ($rows as &$row) {
            $row['total_confirmados'] = $totais[(int) ($row['id'] ?? 0)] ?? 0;
        }
        unset($row);

        return $rows;
    }

    public function definirPresenca(int $conviteId, string $obreiroId, string $status): bool
    {
        if (!in_array($status, ['pendente', 'confirmado', 'cancelado'], true)) {
            return false;
        }
        $stmt = $this->db->prepare("
            INSERT INTO presencas_convite_externo (loja_id, convite_externo_id, obreiro_id, status, updated_at)
            VALUES (:loja_id, :convite_id, :obreiro_id, :status, NOW())
            ON CONFLICT (convite_externo_id, obreiro_id)
            DO UPDATE SET status = EXCLUDED.status, updated_at = NOW()
        ");
        return $stmt->execute([
            'loja_id' => $this->obterLojaAtualId(),
            'convite_id' => $conviteId,
            'obreiro_id' => $obreiroId,
            'status' => $status,
        ]);
    }

    public function listarConfirmados(int $conviteId): array
    {
        $stmt = $this->db->prepare("
            SELECT o.nome, o.cim
            FROM presencas_convite_externo p
            JOIN obreiros o ON o.id = p.obreiro_id
            WHERE p.loja_id = :loja_id
              AND p.convite_externo_id = :convite_id
              AND p.status = 'confirmado'
            ORDER BY o.nome ASC
        ");
        $stmt->execute([
            'loja_id' => $this->obterLojaAtualId(),
            'convite_id' => $conviteId,
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function removerAnexo(int $id): bool
    {
        $stmt = $this->db->prepare("
            UPDATE convites_externos
               SET anexo_mime = NULL,
                   anexo_nome = NULL,
                   anexo_bytes = NULL,
                   anexo_deleted_at = NOW(),
                   updated_at = NOW()
             WHERE id = :id
               AND loja_id = :loja_id
        ");

        return $stmt->execute([
            'id' => $id,
            'loja_id' => $this->obterLojaAtualId(),
        ]);
    }

    private function obterLojaAtualId(): int
    {
        return $this->resolveCurrentStoreId($this->db);
    }
}
