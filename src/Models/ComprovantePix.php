<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class ComprovantePix
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Registra um comprovante recebido via Telegram
     */
    public function registrar(array $data): bool
    {
        $sql = "
            INSERT INTO comprovantes_pix (
                obreiro_id, telegram_user_id, nome_telegram, file_id,
                tipo_arquivo, nome_arquivo, descricao_usuario,
                valor_informado, mes_ref_informado, ano_ref_informado,
                status, criado_em
            ) VALUES (
                :obreiro_id, :telegram_user_id, :nome_telegram, :file_id,
                :tipo_arquivo, :nome_arquivo, :descricao_usuario,
                :valor_informado, :mes_ref_informado, :ano_ref_informado,
                :status, :criado_em
            )
        ";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'obreiro_id' => $data['obreiro_id'] ?? null,
            'telegram_user_id' => $data['telegram_id'] ?? 0,
            'nome_telegram' => $data['nome_telegram'] ?? $data['nome_arquivo'] ?? null,
            'file_id' => $data['telegram_file_id'] ?? $data['file_id'] ?? '',
            'tipo_arquivo' => $data['tipo_arquivo'] ?? 'desconhecido',
            'nome_arquivo' => $data['nome_arquivo'] ?? null,
            'descricao_usuario' => $data['descricao_usuario'] ?? null,
            'valor_informado' => $data['valor_informado'] ?? null,
            'mes_ref_informado' => $data['mes_ref_informado'] ?? null,
            'ano_ref_informado' => $data['ano_ref_informado'] ?? null,
            'status' => $data['status'] ?? 'pendente',
            'criado_em' => $data['data_envio'] ?? date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Lista comprovantes por status ou todos
     */
    public function obterTodos(?string $status = null): array
    {
        $sql = "
            SELECT cp.*, 
                   COALESCE(o.nome_historico, o.nome) as obreiro_nome
            FROM comprovantes_pix cp
            LEFT JOIN obreiros o ON cp.obreiro_id = o.id
        ";

        $params = [];
        if ($status !== null && $status !== '') {
            $sql .= " WHERE cp.status = :status";
            $params['status'] = $status;
        }

        $sql .= " ORDER BY cp.criado_em DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lista comprovantes pendentes
     */
    public function obterPendentes(): array
    {
        return $this->obterTodos('pendente');
    }

    /**
     * Aprova comprovante e cria lançamento
     */
    public function aprovar(int $id, array $validacao): bool
    {
        $sql = "
            UPDATE comprovantes_pix
            SET status = 'aprovado',
                valor_validado = :valor,
                mes_ref_validado = :mes,
                ano_ref_validado = :ano,
                validado_por = :validado_por,
                validado_em = CURRENT_TIMESTAMP
            WHERE id = :id
        ";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'valor' => $validacao['valor'] ?? 0,
            'mes' => $validacao['mes'] ?? date('n'),
            'ano' => $validacao['ano'] ?? date('Y'),
            'validado_por' => $validacao['validado_por'] ?? null,
        ]);
    }

    /**
     * Rejeita comprovante
     */
    public function rejeitar(int $id, string $motivo, ?string $rejeitadoPor): bool
    {
        $sql = "
            UPDATE comprovantes_pix
            SET status = 'rejeitado',
                motivo_rejeicao = :motivo,
                validado_por = :validado_por,
                validado_em = CURRENT_TIMESTAMP
            WHERE id = :id
        ";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'motivo' => $motivo,
            'validado_por' => $rejeitadoPor,
        ]);
    }

    /**
     * Busca comprovante por ID
     */
    public function obterPorId(int $id): ?array
    {
        $sql = "
            SELECT cp.*, 
                   COALESCE(o.nome_historico, o.nome) as obreiro_nome
            FROM comprovantes_pix cp
            LEFT JOIN obreiros o ON cp.obreiro_id = o.id
            WHERE cp.id = :id LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }
}
