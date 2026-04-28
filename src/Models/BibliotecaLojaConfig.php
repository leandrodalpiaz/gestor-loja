<?php

namespace App\Models;

use App\Config\Database;
use App\Core\Tenant\ResolvesStoreTenant;
use PDO;

class BibliotecaLojaConfig
{
    use ResolvesStoreTenant;

    private PDO $db;
    private ?bool $tabelaExiste = null;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    private function tabelaExiste(): bool
    {
        if ($this->tabelaExiste !== null) {
            return $this->tabelaExiste;
        }

        $stmt = $this->db->prepare(
            "SELECT 1
             FROM information_schema.tables
             WHERE table_schema = 'public'
               AND table_name = 'biblioteca_loja_config'
             LIMIT 1"
        );
        $stmt->execute();
        $this->tabelaExiste = (bool) $stmt->fetchColumn();
        return $this->tabelaExiste;
    }

    public function obterDaLojaAtual(): array
    {
        $padrao = [
            'compartilhar_acervo' => false,
            'permitir_emprestimo_cruzado' => false,
        ];

        if (!$this->tabelaExiste()) {
            return $padrao;
        }

        $stmt = $this->db->prepare(
            "SELECT compartilhar_acervo, permitir_emprestimo_cruzado
             FROM biblioteca_loja_config
             WHERE loja_id = :loja_id
             LIMIT 1"
        );
        $stmt->execute(['loja_id' => $this->buscarLojaAtualId()]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return $padrao;
        }

        return [
            'compartilhar_acervo' => !empty($row['compartilhar_acervo']),
            'permitir_emprestimo_cruzado' => !empty($row['permitir_emprestimo_cruzado']),
        ];
    }

    public function listarLojasCompartilhadas(): array
    {
        if (!$this->tabelaExiste()) {
            return [];
        }

        $sql = "SELECT l.id, l.numero_loja, l.sigla, l.nome, l.oriente
                FROM biblioteca_loja_config c
                JOIN lojas l ON l.id = c.loja_id
                WHERE c.compartilhar_acervo = TRUE
                  AND l.ativo = TRUE
                ORDER BY l.numero_loja ASC, l.nome ASC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function buscarLojaAtualId(): int
    {
        return $this->resolveCurrentStoreId($this->db);
    }
}

