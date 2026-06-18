<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class MensagemTrilha
{
    private PDO $db;
    private ?bool $tabelaDisponivel = null;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function obterPorEtapa(string $obreiroId, int $etapaOrdem): array
    {
        if (!$this->garantirTabela()) {
            return [];
        }

        $stmt = $this->db->prepare("
            SELECT
                m.*,
                o.nome AS autor_nome
            FROM mensagens_trilha m
            LEFT JOIN obreiros o ON o.id::text = m.autor_id
            WHERE m.obreiro_id = :obreiro_id
              AND m.etapa_ordem = :etapa_ordem
            ORDER BY m.created_at ASC
        ");
        $stmt->execute([
            'obreiro_id' => $obreiroId,
            'etapa_ordem' => $etapaOrdem,
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function enviar(string $obreiroId, int $grau, int $etapaOrdem, string $autorId, string $mensagem): bool
    {
        if (!$this->garantirTabela()) {
            return false;
        }

        $stmt = $this->db->prepare("
            INSERT INTO mensagens_trilha (
                obreiro_id,
                grau,
                etapa_ordem,
                autor_id,
                mensagem,
                created_at
            ) VALUES (
                :obreiro_id,
                :grau,
                :etapa_ordem,
                :autor_id,
                :mensagem,
                NOW()
            )
        ");
        return $stmt->execute([
            'obreiro_id' => $obreiroId,
            'grau' => $grau,
            'etapa_ordem' => $etapaOrdem,
            'autor_id' => $autorId,
            'mensagem' => trim($mensagem),
        ]);
    }

    private function garantirTabela(): bool
    {
        if ($this->tabelaDisponivel !== null) {
            return $this->tabelaDisponivel;
        }

        try {
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS mensagens_trilha (
                    id SERIAL PRIMARY KEY,
                    obreiro_id VARCHAR(50) NOT NULL,
                    grau INTEGER NOT NULL,
                    etapa_ordem INTEGER NOT NULL,
                    autor_id VARCHAR(50) NOT NULL,
                    mensagem TEXT NOT NULL,
                    created_at TIMESTAMP NOT NULL DEFAULT NOW()
                );
                CREATE INDEX IF NOT EXISTS idx_mensagens_trilha ON mensagens_trilha(obreiro_id, etapa_ordem);
            ");
            $this->tabelaDisponivel = true;
        } catch (\PDOException $e) {
            $this->tabelaDisponivel = false;
        }

        return $this->tabelaDisponivel;
    }
}
