<?php

namespace App\Models;

use App\Config\Database;
use App\Core\Tenant\ResolvesStoreTenant;
use PDO;

class EventoSessao
{
    use ResolvesStoreTenant;

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function substituirPorSessao(int $sessaoId, array $eventos, ?string $autorId = null): bool
    {
        $delete = $this->db->prepare("
            DELETE FROM eventos_sessao
            WHERE sessao_id = :sessao_id
              AND EXISTS (
                  SELECT 1
                  FROM sessoes s
                  WHERE s.id = :sessao_id
                    AND s.loja_id = :loja_id
              )
        ");
        $okDelete = $delete->execute([
            'sessao_id' => $sessaoId,
            'loja_id' => $this->obterLojaAtualId(),
        ]);
        if (!$okDelete) {
            return false;
        }

        if ($eventos === []) {
            return true;
        }

        $insert = $this->db->prepare("
            INSERT INTO eventos_sessao (
                sessao_id,
                tipo_evento,
                titulo,
                descricao,
                data_evento,
                local,
                promotor,
                loja_relacionada,
                oriente,
                observacao,
                created_by,
                updated_by,
                updated_at
            ) SELECT
                :sessao_id,
                :tipo_evento,
                :titulo,
                :descricao,
                :data_evento,
                :local,
                :promotor,
                :loja_relacionada,
                :oriente,
                :observacao,
                :created_by,
                :updated_by,
                NOW()
            FROM sessoes s
            WHERE s.id = :sessao_id
              AND s.loja_id = :loja_id
        ");

        foreach ($eventos as $evento) {
            $tipo = trim((string) ($evento['tipo_evento'] ?? ''));
            $titulo = trim((string) ($evento['titulo'] ?? ''));
            if ($tipo === '' || $titulo === '') {
                continue;
            }

            $ok = $insert->execute([
                'sessao_id' => $sessaoId,
                'tipo_evento' => $tipo,
                'titulo' => $titulo,
                'descricao' => trim((string) ($evento['descricao'] ?? '')) ?: null,
                'data_evento' => trim((string) ($evento['data_evento'] ?? '')) ?: null,
                'local' => trim((string) ($evento['local'] ?? '')) ?: null,
                'promotor' => trim((string) ($evento['promotor'] ?? '')) ?: null,
                'loja_relacionada' => trim((string) ($evento['loja_relacionada'] ?? '')) ?: null,
                'oriente' => trim((string) ($evento['oriente'] ?? '')) ?: null,
                'observacao' => trim((string) ($evento['observacao'] ?? '')) ?: null,
                'created_by' => $autorId,
                'updated_by' => $autorId,
                'loja_id' => $this->obterLojaAtualId(),
            ]);
            if (!$ok) {
                return false;
            }
        }

        return true;
    }

    private function obterLojaAtualId(): int
    {
        return $this->resolveCurrentStoreId($this->db);
    }
}
