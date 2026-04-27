<?php

namespace App\Config;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $host = Env::get('DB_HOST', 'aws-0-sa-east-1.pooler.supabase.com');
            $port = Env::get('DB_PORT', '6543'); // Supabase pooling port
            $dbName = Env::get('DB_NAME', 'postgres');
            $user = Env::get('DB_USER', 'postgres');
            $password = Env::get('DB_PASS', '');

            $dsn = "pgsql:host={$host};port={$port};dbname={$dbName};options='--client_encoding=UTF8'";

            try {
                self::$instance = new PDO($dsn, $user, $password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    // Supabase pooler (transaction mode) pode invalidar prepared statements entre requests.
                    // Emular prepares no cliente evita erro "prepared statement does not exist" em produção.
                    PDO::ATTR_EMULATE_PREPARES => true,
                ]);
            } catch (PDOException $e) {
                // Registrar no log o motivo exato de o banco estar rejeitando
                error_log("FALHA CRÍTICA NO PDO: " . $e->getMessage() . " | DSN: " . $dsn);
                throw $e; // Propagar a exceção para o webhook capturar no try-catch dele
            }
        }

        return self::$instance;
    }
}