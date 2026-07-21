<?php

require_once __DIR__ . '/../src/autoload.php';

use App\Config\Database;
use App\Config\Env;

Env::load(__DIR__ . '/../.env');

$db = Database::getConnection();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$path = __DIR__ . '/../database/migrations/056_efemerides_registros_obreiro_id.sql';
if (!file_exists($path)) {
    fwrite(STDERR, "Arquivo de migration não encontrado: {$path}\n");
    exit(1);
}

$sql = (string) file_get_contents($path);
if (trim($sql) === '') {
    fwrite(STDERR, "Migration vazia: {$path}\n");
    exit(1);
}

try {
    $db->exec($sql);
    echo "OK: migration 056 aplicada com sucesso.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "ERRO: " . $e->getMessage() . "\n");
    exit(1);
}
