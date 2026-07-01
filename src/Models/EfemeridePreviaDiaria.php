<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class EfemeridePreviaDiaria
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->ensureTable();
    }

    private function ensureTable(): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS efemerides_previas_diarias (
                id SERIAL PRIMARY KEY,
                data_ref DATE NOT NULL UNIQUE,
                mensagem TEXT NOT NULL,
                gerada_automaticamente BOOLEAN NOT NULL DEFAULT true,
                disparado_em TIMESTAMP NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ";

        $this->db->exec($sql);

        // Adiciona a coluna disparado_em se a tabela já existia sem ela
        try {
            $this->db->exec("ALTER TABLE efemerides_previas_diarias ADD COLUMN IF NOT EXISTS disparado_em TIMESTAMP NULL");
        } catch (\Throwable $e) {
            // Silencioso — a coluna já existe ou o SGBD não suporta ADD COLUMN IF NOT EXISTS
        }
    }

    public function buscarPorData(string $dataRef): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM efemerides_previas_diarias WHERE data_ref = :data_ref LIMIT 1");
        $stmt->execute(['data_ref' => $dataRef]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function salvarOuAtualizar(string $dataRef, string $mensagem, bool $geradaAutomaticamente): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO efemerides_previas_diarias (data_ref, mensagem, gerada_automaticamente, updated_at)
            VALUES (:data_ref, :mensagem, :gerada_automaticamente, CURRENT_TIMESTAMP)
            ON CONFLICT (data_ref)
            DO UPDATE SET
                mensagem = EXCLUDED.mensagem,
                gerada_automaticamente = EXCLUDED.gerada_automaticamente,
                updated_at = CURRENT_TIMESTAMP
        ");

        return $stmt->execute([
            'data_ref' => $dataRef,
            'mensagem' => $mensagem,
            'gerada_automaticamente' => $geradaAutomaticamente ? 1 : 0,
        ]);
    }

    public function garantirPreviaDoDia(string $mensagemBase): string
    {
        $hoje = $this->today()->format('Y-m-d');
        $existente = $this->buscarPorData($hoje);

        if ($existente) {
            // Se foi editada manualmente, preserva o conteúdo do chanceler.
            if (isset($existente['gerada_automaticamente']) && !$existente['gerada_automaticamente']) {
                return (string) ($existente['mensagem'] ?? '');
            }

            // Se a prévia é automática, mantém sincronizada com a base atual.
            $mensagemExistente = trim((string) ($existente['mensagem'] ?? ''));
            $mensagemCalculada = trim($mensagemBase);
            if ($mensagemExistente !== $mensagemCalculada) {
                $this->salvarOuAtualizar($hoje, $mensagemBase, true);
                return $mensagemBase;
            }

            return (string) ($existente['mensagem'] ?? '');
        }

        $this->salvarOuAtualizar($hoje, $mensagemBase, true);
        return $mensagemBase;
    }

    /**
     * Verifica se o disparo automático (Telegram) já foi feito hoje.
     */
    public function foiDisparadoHoje(): bool
    {
        $hoje = $this->today()->format('Y-m-d');
        $stmt = $this->db->prepare(
            "SELECT disparado_em FROM efemerides_previas_diarias WHERE data_ref = :data_ref AND disparado_em IS NOT NULL LIMIT 1"
        );
        $stmt->execute(['data_ref' => $hoje]);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * Marca que o disparo automático do dia já foi realizado.
     */
    public function marcarComoDisparado(): bool
    {
        $hoje = $this->today()->format('Y-m-d');
        $stmt = $this->db->prepare(
            "UPDATE efemerides_previas_diarias SET disparado_em = CURRENT_TIMESTAMP WHERE data_ref = :data_ref"
        );
        return $stmt->execute(['data_ref' => $hoje]);
    }

    public function prepararAutomaticaDoDia(string $mensagemBase): bool
    {
        $hoje = $this->today()->format('Y-m-d');
        $existente = $this->buscarPorData($hoje);

        if (!$existente) {
            return $this->salvarOuAtualizar($hoje, $mensagemBase, true);
        }

        // Se o chanceler editou manualmente, não sobrescreve pelo automático.
        if (isset($existente['gerada_automaticamente']) && !$existente['gerada_automaticamente']) {
            return true;
        }

        return $this->salvarOuAtualizar($hoje, $mensagemBase, true);
    }

    private function today(): \DateTimeImmutable
    {
        $timezone = trim((string) ($_ENV['APP_TIMEZONE'] ?? 'America/Sao_Paulo'));
        try {
            return new \DateTimeImmutable('today', new \DateTimeZone($timezone));
        } catch (\Throwable $e) {
            return new \DateTimeImmutable('today', new \DateTimeZone('America/Sao_Paulo'));
        }
    }
}
