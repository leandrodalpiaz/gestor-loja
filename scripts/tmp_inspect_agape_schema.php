<?php

require_once __DIR__ . '/../src/autoload.php';

use App\Config\Database;
use App\Config\Env;
use PDO;
use Throwable;

Env::load(__DIR__ . '/../.env');

try {
    $pdo = Database::getConnection();
    echo "CONNECTED", PHP_EOL;

    $tables = $pdo->query(
        "SELECT table_name
           FROM information_schema.tables
          WHERE table_schema = 'public'
          ORDER BY table_name"
    )->fetchAll(PDO::FETCH_COLUMN);

    foreach ($tables as $name) {
        if (
            stripos($name, 'agape') !== false ||
            stripos($name, 'finance') !== false ||
            stripos($name, 'sessao') !== false ||
            stripos($name, 'obrig') !== false
        ) {
            echo $name, PHP_EOL;
        }
    }

    echo "--- COLUMNS ---", PHP_EOL;

    $sql = "
        SELECT table_name, column_name
          FROM information_schema.columns
         WHERE table_schema = 'public'
           AND (
                column_name ILIKE '%agape%'
             OR column_name ILIKE '%rateio%'
             OR column_name ILIKE '%custeio%'
             OR column_name ILIKE '%origem_%'
             OR column_name ILIKE '%sessao_id%'
           )
         ORDER BY table_name, ordinal_position
    ";

    foreach ($pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $row) {
        echo $row['table_name'], ' :: ', $row['column_name'], PHP_EOL;
    }
} catch (Throwable $e) {
    echo 'ERROR: ', $e->getMessage(), PHP_EOL;
    exit(1);
}
