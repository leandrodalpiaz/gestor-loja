<?php
require_once 'D:/Repos/gestor-loja/src/autoload.php';
use App\Config\Env;
use App\Config\Database;

Env::load('D:/Repos/gestor-loja/.env');

foreach ([
    'DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASS', 'DB_SCHEMA',
    'APP_LOJA_NUMERO', 'APP_DEFAULT_TENANT_ID', 'APP_DEFAULT_TENANT_NAME', 'APP_ALLOW_ENV_TENANT_FALLBACK'
] as $envKey) {
    if (!isset($_ENV[$envKey]) || $_ENV[$envKey] === '') {
        $val = getenv($envKey);
        if ($val !== false) {
            $_ENV[$envKey] = $val;
        }
    }
}

$_SESSION['tenant_id'] = $_ENV['APP_DEFAULT_TENANT_ID'] ?? 'a3b54432-8411-477d-bbab-d24c30c90c7b';

$db = Database::getConnection();

try {
    $db->beginTransaction();
    
    // Reset all obreiros to false/null
    $stmt = $db->prepare("UPDATE public.obreiros SET financeiro_joia_ativa = FALSE, financeiro_joia_tipo = NULL");
    $stmt->execute();
    
    $affected = $stmt->rowCount();
    
    $db->commit();
    echo "Successfully reset joia configurations for {$affected} members to default (inactive/none).\n";
} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo "Error resetting joia flags: " . $e->getMessage() . "\n";
}
