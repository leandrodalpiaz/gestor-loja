<?php

/**
 * Aplica as migrations 030 a 035 (multi-tenant) em sequencia.
 * Cada migration e verificada antes de ser executada (idempotencia via information_schema).
 *
 * Uso: php scripts/run_migrations_030_035.php
 *      php scripts/run_migrations_030_035.php --dry-run   (apenas verifica estado)
 */

require_once __DIR__ . '/../src/autoload.php';

use App\Config\Database;
use App\Config\Env;

Env::load(__DIR__ . '/../.env');

$dryRun = in_array('--dry-run', $argv ?? [], true);

$migrations = [
    '030' => [
        'file'   => '030_configuracoes_loja_multitenant.sql',
        'checks' => [
            ['table' => 'configuracoes_loja', 'column' => 'loja_id'],
        ],
    ],
    '031' => [
        'file'   => '031_obreiros_multitenant.sql',
        'checks' => [
            ['table' => 'obreiros', 'column' => 'loja_id'],
        ],
    ],
    '032' => [
        'file'   => '032_sessoes_multitenant.sql',
        'checks' => [
            ['table' => 'sessoes', 'column' => 'loja_id'],
        ],
    ],
    '033' => [
        'file'   => '033_obrigacoes_financeiras_multitenant.sql',
        'checks' => [
            ['table' => 'obrigacoes_financeiras', 'column' => 'loja_id'],
        ],
    ],
    '034' => [
        'file'   => '034_financeiro_agregados_multitenant.sql',
        'checks' => [
            ['table' => 'obrigacoes_financeiras', 'column' => 'loja_id'],
        ],
    ],
    '035' => [
        'file'   => '035_assistencia_chancelaria_multitenant.sql',
        'checks' => [],
    ],
];

try {
    $db = Database::getConnection();
} catch (\Throwable $e) {
    fwrite(STDERR, "ERRO ao conectar ao banco: " . $e->getMessage() . "\n");
    exit(1);
}

$columnsPresentes = static function (PDO $db, array $checks): bool {
    foreach ($checks as $check) {
        $stmt = $db->prepare(
            "SELECT 1
             FROM information_schema.columns
             WHERE table_schema = 'public'
               AND table_name = :table
               AND column_name = :column
             LIMIT 1"
        );
        $stmt->execute([':table' => $check['table'], ':column' => $check['column']]);
        if (!$stmt->fetchColumn()) {
            return false;
        }
    }
    return true;
};

$ok = 0;
$skip = 0;
$fail = 0;

foreach ($migrations as $num => $config) {
    $sqlPath = __DIR__ . '/../database/migrations/' . $config['file'];

    echo "\n--- Migration {$num} ({$config['file']}) ---\n";

    if (!file_exists($sqlPath)) {
        fwrite(STDERR, "  ERRO: arquivo nao encontrado: {$sqlPath}\n");
        $fail++;
        continue;
    }

    // Verificar se ja foi aplicada (todas as colunas esperadas ja existem)
    if ($config['checks'] !== [] && $columnsPresentes($db, $config['checks'])) {
        echo "  SKIP: colunas ja existem no banco. Migration ja foi aplicada.\n";
        $skip++;
        continue;
    }

    if ($config['checks'] !== []) {
        foreach ($config['checks'] as $check) {
            echo "  Coluna '{$check['column']}' em '{$check['table']}': ausente — migration necessaria.\n";
        }
    }

    if ($dryRun) {
        echo "  DRY-RUN: migration seria executada.\n";
        continue;
    }

    $sql = file_get_contents($sqlPath);
    if ($sql === false || trim($sql) === '') {
        fwrite(STDERR, "  ERRO: arquivo SQL vazio ou ilegivel.\n");
        $fail++;
        continue;
    }

    try {
        $db->beginTransaction();
        $db->exec($sql);
        $db->commit();
        echo "  OK: migration {$num} aplicada com sucesso.\n";
        $ok++;
    } catch (\Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        fwrite(STDERR, "  ERRO ao aplicar migration {$num}: " . $e->getMessage() . "\n");
        $fail++;
        // Interromper aqui: migrations posteriores podem depender desta
        echo "\nAbortando: corrija o erro acima antes de continuar.\n";
        break;
    }
}

echo "\n=== Resumo ===\n";
echo "  Aplicadas: {$ok}\n";
echo "  Ignoradas (ja aplicadas): {$skip}\n";
echo "  Falhas: {$fail}\n";

exit($fail > 0 ? 1 : 0);
