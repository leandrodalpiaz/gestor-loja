<?php

namespace App\Models;

use App\Config\Database;
use PDO;
use PDOException;

class SegundoVigilanteAcompanhamento
{
    private PDO $db;
    private ?bool $estruturaDisponivel = null;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function obterPorCompanheiro(string $companheiroId): ?array
    {
        if (!$this->garantirEstrutura()) {
            return null;
        }

        $companheiroId = trim($companheiroId);
        if ($companheiroId === '') {
            return null;
        }

        $stmt = $this->db->prepare("
            SELECT
                sva.*,
                a.titulo AS leitura_titulo,
                a.autor AS leitura_autor
            FROM public.segundo_vigilante_acompanhamentos sva
            LEFT JOIN public.acervo a ON a.id = sva.leitura_acervo_id
            WHERE sva.companheiro_id = :companheiro_id
            LIMIT 1
        ");
        $stmt->execute(['companheiro_id' => $companheiroId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function salvarLeituraSugerida(string $companheiroId, ?int $acervoId, ?string $observacao, ?string $autorId = null): bool
    {
        if (!$this->garantirEstrutura()) {
            return false;
        }

        $companheiroId = trim($companheiroId);
        if ($companheiroId === '') {
            return false;
        }

        $leitura = null;
        if ($acervoId !== null && $acervoId > 0) {
            $leitura = (new Acervo())->buscarPorId($acervoId);
        }

        $stmt = $this->db->prepare("
            INSERT INTO public.segundo_vigilante_acompanhamentos (
                companheiro_id,
                leitura_acervo_id,
                leitura_titulo_snapshot,
                leitura_observacao,
                updated_by,
                created_at,
                updated_at
            ) VALUES (
                :companheiro_id,
                :leitura_acervo_id,
                :leitura_titulo_snapshot,
                :leitura_observacao,
                :updated_by,
                NOW(),
                NOW()
            )
            ON CONFLICT (companheiro_id) DO UPDATE SET
                leitura_acervo_id = EXCLUDED.leitura_acervo_id,
                leitura_titulo_snapshot = EXCLUDED.leitura_titulo_snapshot,
                leitura_observacao = EXCLUDED.leitura_observacao,
                updated_by = EXCLUDED.updated_by,
                updated_at = NOW()
        ");

        return $stmt->execute([
            'companheiro_id' => $companheiroId,
            'leitura_acervo_id' => ($acervoId !== null && $acervoId > 0) ? $acervoId : null,
            'leitura_titulo_snapshot' => $leitura['titulo'] ?? null,
            'leitura_observacao' => $this->nuloSeVazio($observacao),
            'updated_by' => $this->nuloSeVazio($autorId),
        ]);
    }

    public function solicitarCertificado(string $companheiroId, ?string $observacao, ?string $autorId = null): bool
    {
        if (!$this->garantirEstrutura()) {
            return false;
        }

        $companheiroId = trim($companheiroId);
        if ($companheiroId === '') {
            return false;
        }

        $stmt = $this->db->prepare("
            INSERT INTO public.segundo_vigilante_acompanhamentos (
                companheiro_id,
                certificado_status,
                certificado_observacao,
                certificado_solicitado_em,
                certificado_solicitado_por,
                updated_by,
                created_at,
                updated_at
            ) VALUES (
                :companheiro_id,
                'solicitado',
                :certificado_observacao,
                NOW(),
                :certificado_solicitado_por,
                :updated_by,
                NOW(),
                NOW()
            )
            ON CONFLICT (companheiro_id) DO UPDATE SET
                certificado_status = 'solicitado',
                certificado_observacao = EXCLUDED.certificado_observacao,
                certificado_solicitado_em = NOW(),
                certificado_solicitado_por = EXCLUDED.certificado_solicitado_por,
                updated_by = EXCLUDED.updated_by,
                updated_at = NOW()
        ");

        return $stmt->execute([
            'companheiro_id' => $companheiroId,
            'certificado_observacao' => $this->nuloSeVazio($observacao),
            'certificado_solicitado_por' => $this->nuloSeVazio($autorId),
            'updated_by' => $this->nuloSeVazio($autorId),
        ]);
    }

    public function recomendarExaltacao(string $companheiroId, ?string $observacao, ?string $autorId = null): bool
    {
        if (!$this->garantirEstrutura()) {
            return false;
        }

        $companheiroId = trim($companheiroId);
        if ($companheiroId === '') {
            return false;
        }

        $stmt = $this->db->prepare("
            INSERT INTO public.segundo_vigilante_acompanhamentos (
                companheiro_id,
                exaltacao_status,
                exaltacao_observacao,
                exaltacao_recomendada_em,
                exaltacao_recomendada_por,
                updated_by,
                created_at,
                updated_at
            ) VALUES (
                :companheiro_id,
                'recomendada',
                :exaltacao_observacao,
                NOW(),
                :exaltacao_recomendada_por,
                :updated_by,
                NOW(),
                NOW()
            )
            ON CONFLICT (companheiro_id) DO UPDATE SET
                exaltacao_status = 'recomendada',
                exaltacao_observacao = EXCLUDED.exaltacao_observacao,
                exaltacao_recomendada_em = NOW(),
                exaltacao_recomendada_por = EXCLUDED.exaltacao_recomendada_por,
                updated_by = EXCLUDED.updated_by,
                updated_at = NOW()
        ");

        return $stmt->execute([
            'companheiro_id' => $companheiroId,
            'exaltacao_observacao' => $this->nuloSeVazio($observacao),
            'exaltacao_recomendada_por' => $this->nuloSeVazio($autorId),
            'updated_by' => $this->nuloSeVazio($autorId),
        ]);
    }

    public function listarHistoricoFormativo(string $companheiroId): array
    {
        $historico = [];
        $trilha = new TrilhaCompanheiro();
        $etapas = $trilha->listarEtapasPorCompanheiroId($companheiroId);

        foreach ($etapas as $etapa) {
            if (!empty($etapa['data_disponibilizacao'])) {
                $historico[] = [
                    'momento' => (string) $etapa['data_disponibilizacao'],
                    'tipo' => 'etapa_disponibilizada',
                    'titulo' => 'Etapa ' . (int) ($etapa['etapa_ordem'] ?? 0) . ' disponibilizada',
                    'descricao' => (string) ($etapa['titulo_etapa'] ?? ''),
                ];
            }
            if (!empty($etapa['data_entrega'])) {
                $historico[] = [
                    'momento' => (string) $etapa['data_entrega'],
                    'tipo' => 'trabalho_recebido',
                    'titulo' => 'Trabalho recebido',
                    'descricao' => 'Etapa ' . (int) ($etapa['etapa_ordem'] ?? 0) . ' - ' . (string) ($etapa['titulo_etapa'] ?? ''),
                ];
            }
            if (!empty($etapa['data_revisao'])) {
                $historico[] = [
                    'momento' => (string) $etapa['data_revisao'],
                    'tipo' => 'etapa_revisada',
                    'titulo' => 'Etapa revisada',
                    'descricao' => (string) ($etapa['observacao_vigilante'] ?? ('Etapa ' . (int) ($etapa['etapa_ordem'] ?? 0))),
                ];
            }
        }

        $acompanhamento = $this->obterPorCompanheiro($companheiroId);
        if ($acompanhamento) {
            if (!empty($acompanhamento['updated_at']) && (!empty($acompanhamento['leitura_acervo_id']) || !empty($acompanhamento['leitura_observacao']))) {
                $historico[] = [
                    'momento' => (string) $acompanhamento['updated_at'],
                    'tipo' => 'leitura_sugerida',
                    'titulo' => 'Leitura sugerida',
                    'descricao' => trim((string) ($acompanhamento['leitura_titulo'] ?? $acompanhamento['leitura_titulo_snapshot'] ?? 'Leitura orientada')),
                ];
            }
            if (!empty($acompanhamento['certificado_solicitado_em'])) {
                $historico[] = [
                    'momento' => (string) $acompanhamento['certificado_solicitado_em'],
                    'tipo' => 'certificado_solicitado',
                    'titulo' => 'Certificado solicitado',
                    'descricao' => (string) ($acompanhamento['certificado_observacao'] ?? 'Solicitacao formal registrada pelo 2o Vigilante.'),
                ];
            }
            if (!empty($acompanhamento['exaltacao_recomendada_em'])) {
                $historico[] = [
                    'momento' => (string) $acompanhamento['exaltacao_recomendada_em'],
                    'tipo' => 'exaltacao_recomendada',
                    'titulo' => 'Exaltacao recomendada',
                    'descricao' => (string) ($acompanhamento['exaltacao_observacao'] ?? 'Encaminhamento para apreciacao da exaltacao.'),
                ];
            }
        }

        usort($historico, static function (array $a, array $b): int {
            return strcmp((string) ($b['momento'] ?? ''), (string) ($a['momento'] ?? ''));
        });

        return $historico;
    }

    private function garantirEstrutura(): bool
    {
        if ($this->estruturaDisponivel !== null) {
            return $this->estruturaDisponivel;
        }

        try {
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS public.segundo_vigilante_acompanhamentos (
                    id SERIAL PRIMARY KEY,
                    companheiro_id VARCHAR(50) NOT NULL UNIQUE,
                    leitura_acervo_id INTEGER NULL,
                    leitura_titulo_snapshot VARCHAR(255) NULL,
                    leitura_observacao TEXT NULL,
                    certificado_status VARCHAR(30) NOT NULL DEFAULT 'nao_solicitado',
                    certificado_observacao TEXT NULL,
                    certificado_solicitado_em TIMESTAMP NULL,
                    certificado_solicitado_por VARCHAR(50) NULL,
                    exaltacao_status VARCHAR(30) NOT NULL DEFAULT 'nao_recomendada',
                    exaltacao_observacao TEXT NULL,
                    exaltacao_recomendada_em TIMESTAMP NULL,
                    exaltacao_recomendada_por VARCHAR(50) NULL,
                    updated_by VARCHAR(50) NULL,
                    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
                    updated_at TIMESTAMP NOT NULL DEFAULT NOW()
                )
            ");
            $this->estruturaDisponivel = true;
        } catch (PDOException) {
            $this->estruturaDisponivel = false;
        }

        return $this->estruturaDisponivel;
    }

    private function nuloSeVazio(?string $valor): ?string
    {
        $valor = trim((string) $valor);
        return $valor !== '' ? $valor : null;
    }
}
