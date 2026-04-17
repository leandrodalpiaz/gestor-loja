<?php

namespace App\Models;

use App\Config\Database;
use App\Core\Tenant\ResolvesStoreTenant;
use PDO;

class MensagemComplementar
{
    use ResolvesStoreTenant;

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function sortear(string $tipo): string
    {
        $lojaId = $this->obterLojaAtualId();

        $stmt = $this->db->prepare(
            'SELECT id, mensagem FROM mensagens_complementares
             WHERE loja_id = :loja_id AND tipo = :tipo AND ativo = true ORDER BY id'
        );
        $stmt->execute([
            'loja_id' => $lojaId,
            'tipo' => $tipo,
        ]);
        $todas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($todas)) {
            return '';
        }

        $stmt2 = $this->db->prepare(
            'SELECT ids_usados FROM mensagens_rotacao_historico
             WHERE loja_id = :loja_id AND tipo = :tipo'
        );
        $stmt2->execute([
            'loja_id' => $lojaId,
            'tipo' => $tipo,
        ]);
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

        $todosIds = array_map('intval', array_column($todas, 'id'));
        $disponiveis = array_values(array_diff($todosIds, $usados));

        if (empty($disponiveis)) {
            $disponiveis = $todosIds;
            $usados = [];
        }

        $idSorteado = (int) $disponiveis[array_rand($disponiveis)];
        $usados[] = $idSorteado;
        $idsStr = '{' . implode(',', array_map('intval', $usados)) . '}';

        $this->db->prepare(
            'INSERT INTO mensagens_rotacao_historico (loja_id, tipo, ids_usados, updated_at)
             VALUES (:loja_id, :tipo, :ids, NOW())
             ON CONFLICT (loja_id, tipo) DO UPDATE
             SET ids_usados = EXCLUDED.ids_usados, updated_at = NOW()'
        )->execute([
            'loja_id' => $lojaId,
            'tipo' => $tipo,
            'ids' => $idsStr,
        ]);

        foreach ($todas as $mensagem) {
            if ((int) $mensagem['id'] === $idSorteado) {
                return (string) $mensagem['mensagem'];
            }
        }

        return '';
    }

    public function listarPorTipo(string $tipo): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM mensagens_complementares
             WHERE loja_id = :loja_id AND tipo = :tipo
             ORDER BY ativo DESC, id'
        );
        $stmt->execute([
            'loja_id' => $this->obterLojaAtualId(),
            'tipo' => $tipo,
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarTipos(): array
    {
        $stmt = $this->db->prepare(
            'SELECT tipo, COUNT(*) as total,
                    SUM(CASE WHEN ativo THEN 1 ELSE 0 END) as ativos
             FROM mensagens_complementares
             WHERE loja_id = :loja_id
             GROUP BY tipo ORDER BY tipo'
        );
        $stmt->execute(['loja_id' => $this->obterLojaAtualId()]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM mensagens_complementares
             WHERE id = :id AND loja_id = :loja_id
             LIMIT 1'
        );
        $stmt->execute([
            'id' => $id,
            'loja_id' => $this->obterLojaAtualId(),
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function criar(string $tipo, string $mensagem): bool
    {
        return $this->db->prepare(
            'INSERT INTO mensagens_complementares (loja_id, tipo, mensagem)
             VALUES (:loja_id, :tipo, :mensagem)'
        )->execute([
            'loja_id' => $this->obterLojaAtualId(),
            'tipo' => $tipo,
            'mensagem' => trim($mensagem),
        ]);
    }

    public function atualizar(int $id, string $mensagem): bool
    {
        return $this->db->prepare(
            'UPDATE mensagens_complementares
             SET mensagem = :mensagem
             WHERE id = :id AND loja_id = :loja_id'
        )->execute([
            'mensagem' => trim($mensagem),
            'id' => $id,
            'loja_id' => $this->obterLojaAtualId(),
        ]);
    }

    public function toggleAtivo(int $id): bool
    {
        return $this->db->prepare(
            'UPDATE mensagens_complementares
             SET ativo = NOT ativo
             WHERE id = :id AND loja_id = :loja_id'
        )->execute([
            'id' => $id,
            'loja_id' => $this->obterLojaAtualId(),
        ]);
    }

    public function excluir(int $id): bool
    {
        return $this->db->prepare(
            'DELETE FROM mensagens_complementares
             WHERE id = :id AND loja_id = :loja_id'
        )->execute([
            'id' => $id,
            'loja_id' => $this->obterLojaAtualId(),
        ]);
    }

    private function obterLojaAtualId(): int
    {
        return $this->resolveCurrentStoreId($this->db);
    }
}
