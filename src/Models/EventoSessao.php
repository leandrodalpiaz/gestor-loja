<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class EventoSessao
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function substituirPorSessao(int $sessaoId, array $eventos, ?string $autorId = null): bool
    {
        $delete = $this->db->prepare("DELETE FROM public.eventos_sessao WHERE sessao_id = :sessao_id");
        $okDelete = $delete->execute(['sessao_id' => $sessaoId]);
        if (!$okDelete) {
            return false;
        }

        if ($eventos === []) {
            return true;
        }

        $insert = $this->db->prepare("
            INSERT INTO public.eventos_sessao (
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
            ) VALUES (
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
            )
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
            ]);
            if (!$ok) {
                return false;
            }
        }

        return true;
    }
}
