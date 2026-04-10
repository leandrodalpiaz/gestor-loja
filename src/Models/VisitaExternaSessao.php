<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class VisitaExternaSessao
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function substituirPorSessao(int $sessaoId, array $visitas, ?string $autorId = null): bool
    {
        $delete = $this->db->prepare("DELETE FROM public.visitas_externas_sessao WHERE sessao_id = :sessao_id");
        $okDelete = $delete->execute(['sessao_id' => $sessaoId]);
        if (!$okDelete) {
            return false;
        }

        if ($visitas === []) {
            return true;
        }

        $insert = $this->db->prepare("
            INSERT INTO public.visitas_externas_sessao (
                sessao_id,
                obreiro_id,
                obreiro_nome_livre,
                loja_visitada,
                potencia_obediencia,
                oriente,
                data_visita,
                observacao,
                origem_registro,
                created_by,
                updated_by,
                updated_at
            ) VALUES (
                :sessao_id,
                :obreiro_id,
                :obreiro_nome_livre,
                :loja_visitada,
                :potencia_obediencia,
                :oriente,
                :data_visita,
                :observacao,
                :origem_registro,
                :created_by,
                :updated_by,
                NOW()
            )
        ");

        foreach ($visitas as $visita) {
            $obreiroId = trim((string) ($visita['obreiro_id'] ?? ''));
            $obreiroNome = trim((string) ($visita['obreiro_nome'] ?? ''));
            $loja = trim((string) ($visita['loja'] ?? ''));
            $oriente = trim((string) ($visita['oriente'] ?? ''));
            $potencia = trim((string) ($visita['potencia_obediencia'] ?? ''));
            $dataVisita = trim((string) ($visita['data_visita'] ?? ''));
            $observacao = trim((string) ($visita['observacao'] ?? ''));

            if ($obreiroId === '' && $obreiroNome === '' && $loja === '' && $oriente === '' && $potencia === '' && $dataVisita === '' && $observacao === '') {
                continue;
            }

            $ok = $insert->execute([
                'sessao_id' => $sessaoId,
                'obreiro_id' => $obreiroId !== '' ? $obreiroId : null,
                'obreiro_nome_livre' => $obreiroNome !== '' ? $obreiroNome : null,
                'loja_visitada' => $loja !== '' ? $loja : null,
                'potencia_obediencia' => $potencia !== '' ? $potencia : null,
                'oriente' => $oriente !== '' ? $oriente : null,
                'data_visita' => $dataVisita !== '' ? $dataVisita : null,
                'observacao' => $observacao !== '' ? $observacao : null,
                'origem_registro' => 'saco_propostas',
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
