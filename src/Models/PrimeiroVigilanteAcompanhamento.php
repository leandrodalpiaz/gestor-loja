<?php

namespace App\Models;

use App\Config\Database;
use PDO;
use PDOException;

class PrimeiroVigilanteAcompanhamento
{
    private PDO $db;
    private ?bool $estruturaDisponivel = null;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function obterPorAprendiz(string $aprendizId): ?array
    {
        if (!$this->garantirEstrutura()) {
            return null;
        }

        $aprendizId = trim($aprendizId);
        if ($aprendizId === '') {
            return null;
        }

        $stmt = $this->db->prepare("
            SELECT
                pva.*,
                a.titulo AS leitura_titulo,
                a.autor AS leitura_autor
            FROM primeiro_vigilante_acompanhamentos pva
            LEFT JOIN acervo a ON a.id = pva.leitura_acervo_id
            WHERE pva.aprendiz_id = :aprendiz_id
            LIMIT 1
        ");
        $stmt->execute(['aprendiz_id' => $aprendizId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function salvarLeituraSugerida(string $aprendizId, ?int $acervoId, ?string $observacao, ?string $autorId = null): bool
    {
        if (!$this->garantirEstrutura()) {
            return false;
        }

        $aprendizId = trim($aprendizId);
        if ($aprendizId === '') {
            return false;
        }

        $leitura = null;
        if ($acervoId !== null && $acervoId > 0) {
            $leitura = (new Acervo())->buscarPorId($acervoId);
        }

        $stmt = $this->db->prepare("
            INSERT INTO primeiro_vigilante_acompanhamentos (
                aprendiz_id,
                leitura_acervo_id,
                leitura_titulo_snapshot,
                leitura_observacao,
                updated_by,
                created_at,
                updated_at
            ) VALUES (
                :aprendiz_id,
                :leitura_acervo_id,
                :leitura_titulo_snapshot,
                :leitura_observacao,
                :updated_by,
                NOW(),
                NOW()
            )
            ON CONFLICT (aprendiz_id) DO UPDATE SET
                leitura_acervo_id = EXCLUDED.leitura_acervo_id,
                leitura_titulo_snapshot = EXCLUDED.leitura_titulo_snapshot,
                leitura_observacao = EXCLUDED.leitura_observacao,
                updated_by = EXCLUDED.updated_by,
                updated_at = NOW()
        ");

        return $stmt->execute([
            'aprendiz_id' => $aprendizId,
            'leitura_acervo_id' => ($acervoId !== null && $acervoId > 0) ? $acervoId : null,
            'leitura_titulo_snapshot' => $leitura['titulo'] ?? null,
            'leitura_observacao' => $this->nuloSeVazio($observacao),
            'updated_by' => $this->nuloSeVazio($autorId),
        ]);
    }

    public function solicitarCertificado(string $aprendizId, ?string $observacao, ?string $autorId = null): bool
    {
        if (!$this->garantirEstrutura()) {
            return false;
        }

        $aprendizId = trim($aprendizId);
        if ($aprendizId === '') {
            return false;
        }

        $stmt = $this->db->prepare("
            INSERT INTO primeiro_vigilante_acompanhamentos (
                aprendiz_id,
                certificado_status,
                certificado_observacao,
                certificado_solicitado_em,
                certificado_solicitado_por,
                updated_by,
                created_at,
                updated_at
            ) VALUES (
                :aprendiz_id,
                'solicitado',
                :certificado_observacao,
                NOW(),
                :certificado_solicitado_por,
                :updated_by,
                NOW(),
                NOW()
            )
            ON CONFLICT (aprendiz_id) DO UPDATE SET
                certificado_status = 'solicitado',
                certificado_observacao = EXCLUDED.certificado_observacao,
                certificado_solicitado_em = NOW(),
                certificado_solicitado_por = EXCLUDED.certificado_solicitado_por,
                updated_by = EXCLUDED.updated_by,
                updated_at = NOW()
        ");

        return $stmt->execute([
            'aprendiz_id' => $aprendizId,
            'certificado_observacao' => $this->nuloSeVazio($observacao),
            'certificado_solicitado_por' => $this->nuloSeVazio($autorId),
            'updated_by' => $this->nuloSeVazio($autorId),
        ]);
    }

    public function recomendarElevacao(string $aprendizId, ?string $observacao, ?string $autorId = null): bool
    {
        if (!$this->garantirEstrutura()) {
            return false;
        }

        $aprendizId = trim($aprendizId);
        if ($aprendizId === '') {
            return false;
        }

        $stmt = $this->db->prepare("
            INSERT INTO primeiro_vigilante_acompanhamentos (
                aprendiz_id,
                elevacao_status,
                elevacao_observacao,
                elevacao_autorizada_em,
                elevacao_autorizada_por,
                updated_by,
                created_at,
                updated_at
            ) VALUES (
                :aprendiz_id,
                'recomendada',
                :elevacao_observacao,
                NOW(),
                :elevacao_autorizada_por,
                :updated_by,
                NOW(),
                NOW()
            )
            ON CONFLICT (aprendiz_id) DO UPDATE SET
                elevacao_status = 'recomendada',
                elevacao_observacao = EXCLUDED.elevacao_observacao,
                elevacao_autorizada_em = NOW(),
                elevacao_autorizada_por = EXCLUDED.elevacao_autorizada_por,
                updated_by = EXCLUDED.updated_by,
                updated_at = NOW()
        ");

        return $stmt->execute([
            'aprendiz_id' => $aprendizId,
            'elevacao_observacao' => $this->nuloSeVazio($observacao),
            'elevacao_autorizada_por' => $this->nuloSeVazio($autorId),
            'updated_by' => $this->nuloSeVazio($autorId),
        ]);
    }

    public function listarHistoricoFormativo(string $aprendizId): array
    {
        $historico = [];
        $trilha = new TrilhaAprendiz();
        $etapas = $trilha->listarEtapasPorAprendizId($aprendizId);

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

        $acompanhamento = $this->obterPorAprendiz($aprendizId);
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
                    'descricao' => (string) ($acompanhamento['certificado_observacao'] ?? 'Solicitação formal registrada pelo 1º Vigilante.'),
                ];
            }
            if (!empty($acompanhamento['elevacao_autorizada_em'])) {
                $historico[] = [
                    'momento' => (string) $acompanhamento['elevacao_autorizada_em'],
                    'tipo' => 'elevacao_recomendada',
                    'titulo' => 'Elevação recomendada',
                    'descricao' => (string) ($acompanhamento['elevacao_observacao'] ?? 'Obreiro indicado para Elevação pelo 1º Vigilante.'),
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
                CREATE TABLE IF NOT EXISTS primeiro_vigilante_acompanhamentos (
                    id SERIAL PRIMARY KEY,
                    aprendiz_id VARCHAR(50) NOT NULL UNIQUE,
                    leitura_acervo_id INTEGER NULL,
                    leitura_titulo_snapshot VARCHAR(255) NULL,
                    leitura_observacao TEXT NULL,
                    certificado_status VARCHAR(30) NOT NULL DEFAULT 'nao_solicitado',
                    certificado_observacao TEXT NULL,
                    certificado_solicitado_em TIMESTAMP NULL,
                    certificado_solicitado_por VARCHAR(50) NULL,
                    elevacao_status VARCHAR(30) NOT NULL DEFAULT 'nao_indicada',
                    elevacao_observacao TEXT NULL,
                    elevacao_autorizada_em TIMESTAMP NULL,
                    elevacao_autorizada_por VARCHAR(50) NULL,
                    updated_by VARCHAR(50) NULL,
                    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
                    updated_at TIMESTAMP NOT NULL DEFAULT NOW()
                );
                ALTER TABLE primeiro_vigilante_acompanhamentos ADD COLUMN IF NOT EXISTS elevacao_status VARCHAR(30) NOT NULL DEFAULT 'nao_indicada';
                ALTER TABLE primeiro_vigilante_acompanhamentos ADD COLUMN IF NOT EXISTS elevacao_observacao TEXT NULL;
                ALTER TABLE primeiro_vigilante_acompanhamentos ADD COLUMN IF NOT EXISTS elevacao_autorizada_em TIMESTAMP NULL;
                ALTER TABLE primeiro_vigilante_acompanhamentos ADD COLUMN IF NOT EXISTS elevacao_autorizada_por VARCHAR(50) NULL;
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
