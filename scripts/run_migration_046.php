<?php

require_once __DIR__ . '/../src/autoload.php';

use App\Config\Database;
use App\Config\Env;

Env::load(__DIR__ . '/../.env');

$sqlFile = __DIR__ . '/../database/migrations/046_area_irmao_familiares_solicitacoes.sql';

if (!file_exists($sqlFile)) {
    fwrite(STDERR, "Arquivo SQL nao encontrado: {$sqlFile}\n");
    exit(1);
}

$sql = file_get_contents($sqlFile);
if ($sql === false || trim($sql) === '') {
    fwrite(STDERR, "Arquivo SQL vazio ou ilegivel.\n");
    exit(1);
}

try {
    $db = Database::getConnection();
    $db->beginTransaction();
    $db->exec($sql);
    $db->commit();
    echo "OK: migration 046 aplicada com sucesso.\n";
} catch (\Throwable $e) {
    if (isset($db) && $db instanceof \PDO && $db->inTransaction()) {
        $db->rollBack();
    }
    fwrite(STDERR, "ERRO ao aplicar migration 046: " . $e->getMessage() . "\n");
    exit(1);
}

