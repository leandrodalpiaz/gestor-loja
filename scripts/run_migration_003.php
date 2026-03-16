<?php

require_once __DIR__ . '/../src/autoload.php';

use App\Config\Database;
use App\Config\Env;

// Carrega variáveis do .env
Env::load(__DIR__ . '/../.env');

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "🚀 EXECUTANDO MIGRATION 003 - Tesoureiro Financeiro\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

try {
    // Conecta ao banco
    echo "📥 Conectando ao Supabase...\n";
    $db = Database::getConnection();
    echo "✅ Conectado com sucesso!\n\n";

    // Lê arquivo SQL
    $sqlFile = __DIR__ . '/../database/migrations/003_tesoureiro_financeiro.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("Arquivo não encontrado: {$sqlFile}");
    }

    echo "📖 Lendo arquivo SQL...\n";
    $sql = file_get_contents($sqlFile);
    echo "✅ Arquivo lido ({file_size = " . strlen($sql) . " bytes).\n\n";

    // Remove comentários e divide em comandos
    $commands = preg_split('/;[\s\n]+/', $sql);
    $commands = array_filter($commands, fn($cmd) => trim($cmd) !== '');

    echo "🔄 Executando {count($commands)} comandos SQL...\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

    $db->beginTransaction();

    $successCount = 0;
    $errorCount = 0;

    foreach ($commands as $index => $command) {
        $command = trim($command);
        if (empty($command)) {
            continue;
        }

        // Extrai nome da operação
        $name = 'SQL';
        if (stripos($command, 'CREATE TABLE') === 0) {
            preg_match('/CREATE TABLE IF NOT EXISTS (\w+)/i', $command, $matches);
            $name = $matches[1] ?? 'TABLE';
            echo "📋 Criando tabela: {$name}...";
        } elseif (stripos($command, 'INSERT INTO') === 0) {
            preg_match('/INSERT INTO (\w+)/i', $command, $matches);
            $name = $matches[1] ?? 'INSERT';
            echo "📝 Inserindo dados em: {$name}...";
        } elseif (stripos($command, 'CREATE INDEX') === 0) {
            preg_match('/CREATE INDEX IF NOT EXISTS (\w+)/i', $command, $matches);
            $name = $matches[1] ?? 'INDEX';
            echo "🔍 Criando índice: {$name}...";
        }

        try {
            $stmt = $db->prepare($command);
            $stmt->execute();
            echo " ✅\n";
            $successCount++;
        } catch (\PDOException $e) {
            echo " ❌ ERRO\n";
            echo "   └─ " . $e->getMessage() . "\n";
            $errorCount++;
        }
    }

    if ($errorCount === 0) {
        $db->commit();
        echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "✅ MIGRATION EXECUTADA COM SUCESSO!\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "📊 Resultado: {$successCount} comandos ✅ | 0 erros ❌\n\n";
        echo "📋 Tabelas criadas:\n";
        echo "   • categorias_financeiras (22 registros de seed)\n";
        echo "   • lancamentos_financeiros\n";
        echo "   • comprovantes_pix\n";
        echo "   • mensalidades_status\n";
        echo "   • regularidade_obreiro\n";
        echo "   • fechamento_mensal\n";
        echo "   • ajustes_saldo_auditoria\n";
        echo "   • tronco_solidariedade\n\n";
        echo "🎯 Próximos passos:\n";
        echo "   1. Tesoureiro acessa: /tesouraria/caixa\n";
        echo "   2. Obreiro envia PIX via Telegram Bot\n";
        echo "   3. Tesoureiro valida comprovante\n\n";
    } else {
        $db->rollBack();
        echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "❌ MIGRATION FALHOU!\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "📊 Resultado: {$successCount} comandos ✅ | {$errorCount} erros ❌\n";
        echo "   Transação foi revertida\n\n";
        exit(1);
    }

} catch (Exception $e) {
    echo "\n❌ ERRO FATAL:\n";
    echo "   {$e->getMessage()}\n\n";
    exit(1);
}
