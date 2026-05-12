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
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ";

        $this->db->exec($sql);
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
