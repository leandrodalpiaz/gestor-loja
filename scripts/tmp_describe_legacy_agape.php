<?php

require_once __DIR__ . '/../src/autoload.php';

use App\Config\Database;
use App\Config\Env;

Env::load(__DIR__ . '/../.env');

$pdo = Database::getConnection();
$targets = ['banquete', 'banquete_despesas', 'banquete_pix', 'banquete_presencas', 'eventos', 'presencas'];

foreach ($targets as $table) {
    echo '=== ', $table, ' ===', PHP_EOL;

    $stmt = $pdo->prepare(
        "SELECT column_name, data_type, is_nullable
           FROM information_schema.columns
          WHERE table_schema = 'public'
            AND table_name = :table
          ORDER BY ordinal_position"
    );
    $stmt->execute(['table' => $table]);

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        echo $row['column_name'], ' | ', $row['data_type'], ' | ', $row['is_nullable'], PHP_EOL;
    }

    $fkStmt = $pdo->prepare(
        "SELECT kcu.column_name, ccu.table_name AS foreign_table_name, ccu.column_name AS foreign_column_name
           FROM information_schema.table_constraints AS tc
           JOIN information_schema.key_column_usage AS kcu
             ON tc.constraint_name = kcu.constraint_name
            AND tc.table_schema = kcu.table_schema
           JOIN information_schema.constraint_column_usage AS ccu
             ON ccu.constraint_name = tc.constraint_name
            AND ccu.table_schema = tc.table_schema
          WHERE tc.constraint_type = 'FOREIGN KEY'
            AND tc.table_schema = 'public'
            AND tc.table_name = :table
          ORDER BY kcu.ordinal_position"
    );
    $fkStmt->execute(['table' => $table]);

    foreach ($fkStmt->fetchAll(PDO::FETCH_ASSOC) as $fk) {
        echo 'FK ', $fk['column_name'], ' -> ', $fk['foreign_table_name'], '.', $fk['foreign_column_name'], PHP_EOL;
    }
}

echo '=== TABLES LIKE BANQUETE ===', PHP_EOL;
$likeStmt = $pdo->query(
    "SELECT table_name
       FROM information_schema.tables
      WHERE table_schema = 'public'
        AND table_name ILIKE '%banquete%'
      ORDER BY table_name"
);
foreach ($likeStmt->fetchAll(PDO::FETCH_COLUMN) as $name) {
    echo $name, PHP_EOL;
}
