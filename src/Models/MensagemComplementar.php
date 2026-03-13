<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class MensagemComplementar
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Sorteia uma mensagem do tipo informado evitando repetição imediata.
     * Equivalente ao _get_persistent_random_message() do Python.
     */
    public function sortear(string $tipo): string
    {
        $stmt = $this->db->prepare(
            'SELECT id, mensagem FROM mensagens_complementares
             WHERE tipo = :tipo AND ativo = true ORDER BY id'
        );
        $stmt->execute(['tipo' => $tipo]);
        $todas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($todas)) {
            return '';
        }

        $stmt2 = $this->db->prepare(
            'SELECT ids_usados FROM mensagens_rotacao_historico WHERE tipo = :tipo'
        );
        $stmt2->execute(['tipo' => $tipo]);
        $row = $stmt2->fetch(PDO::FETCH_ASSOC);

        $usados = [];
        if ($row && !empty($row['ids_usados'])) {
            $raw = trim((string) $row['ids_usados'], '{}');
            if ($raw !== '') {
                $usados = array_filter(
                    array_map('intval', explode(',', $raw)),
                    fn(int $v) => $v > 0
                );
            }
        }

        $todos_ids = array_column($todas, 'id');
        $disponiveis = array_values(array_diff($todos_ids, $usados));

        if (empty($disponiveis)) {
            $disponiveis = $todos_ids;
            $usados = [];
        }

        $id_sorteado = (int) $disponiveis[array_rand($disponiveis)];
        $usados[] = $id_sorteado;
        $ids_str = '{' . implode(',', array_map('intval', $usados)) . '}';

        $this->db->prepare(
            'INSERT INTO mensagens_rotacao_historico (tipo, ids_usados, updated_at)
             VALUES (:tipo, :ids, NOW())
             ON CONFLICT (tipo) DO UPDATE
             SET ids_usados = EXCLUDED.ids_usados, updated_at = NOW()'
        )->execute(['tipo' => $tipo, 'ids' => $ids_str]);

        foreach ($todas as $m) {
            if ((int) $m['id'] === $id_sorteado) {
                return (string) $m['mensagem'];
            }
        }

        return '';
    }

    /**
     * Lista todas as mensagens de um tipo (para CRUD no Mini App).
     */
    public function listarPorTipo(string $tipo): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM mensagens_complementares
             WHERE tipo = :tipo ORDER BY ativo DESC, id'
        );
        $stmt->execute(['tipo' => $tipo]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lista todos os tipos distintos com contagem.
     */
    public function listarTipos(): array
    {
        $stmt = $this->db->query(
            'SELECT tipo, COUNT(*) as total,
                    SUM(CASE WHEN ativo THEN 1 ELSE 0 END) as ativos
             FROM mensagens_complementares
             GROUP BY tipo ORDER BY tipo'
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM mensagens_complementares WHERE id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function criar(string $tipo, string $mensagem): bool
    {
        return $this->db->prepare(
            'INSERT INTO mensagens_complementares (tipo, mensagem) VALUES (:tipo, :mensagem)'
        )->execute(['tipo' => $tipo, 'mensagem' => trim($mensagem)]);
    }

    public function atualizar(int $id, string $mensagem): bool
    {
        return $this->db->prepare(
            'UPDATE mensagens_complementares SET mensagem = :mensagem WHERE id = :id'
        )->execute(['mensagem' => trim($mensagem), 'id' => $id]);
    }

    public function toggleAtivo(int $id): bool
    {
        return $this->db->prepare(
            'UPDATE mensagens_complementares SET ativo = NOT ativo WHERE id = :id'
        )->execute(['id' => $id]);
    }

    public function excluir(int $id): bool
    {
        return $this->db->prepare(
            'DELETE FROM mensagens_complementares WHERE id = :id'
        )->execute(['id' => $id]);
    }
}
