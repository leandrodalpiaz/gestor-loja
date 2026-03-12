<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class Obreiro
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Busca um obreiro pelo ID do Telegram
     */
    public function findByTelegramId(int $telegramId): ?array
    {
        // Garante que o obreiro encontrado não apenas existe, mas está ATIVO no quadro da loja
        $stmt = $this->db->prepare("SELECT * FROM obreiros WHERE telegram_id = :telegram_id AND ativo = true LIMIT 1");
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Busca todos os obreiros ativos
     */
    public function getAllAtivos(): array
    {
        $stmt = $this->db->prepare("SELECT * FROM obreiros WHERE ativo = true ORDER BY nome ASC");
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Autenticação para o painel administrativo via matrícula e senha
     */
    public function autenticar(string $matricula, string $senha): ?array
    {
        // Certifica de puxar apenas se o membro for ativo para login
        $stmt = $this->db->prepare("SELECT * FROM obreiros WHERE matricula = :matricula AND ativo = true LIMIT 1");
        $stmt->execute(['matricula' => $matricula]);
        $usuario = $stmt->fetch();

        // O hash da senha deve estar salvo na coluna "senha_hash" no banco
        if ($usuario && password_verify($senha, $usuario['senha_hash'])) {
            return $usuario;
        }

        return null;
    }
}