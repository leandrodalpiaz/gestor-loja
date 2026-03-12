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
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } catch (PDOException $e) {
                // Return generic error or specific error based on environment
                die("Erro de conexão com o banco de dados: " . $e->getMessage());
            }
        }

        return self::$instance;
    }
}