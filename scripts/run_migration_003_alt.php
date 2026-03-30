<?php

require_once __DIR__ . '/../src/autoload.php';

use App\Config\Database;
use App\Config\Env;

// Carrega variáveis do .env
Env::load(__DIR__ . '/../.env');

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "🚀 EXECUTANDO MIGRATION ALT 003 - Tesoureiro Financeiro\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

try {
    $appUrl = rtrim(trim((string) Env::get('APP_URL', 'http://localhost:8000')), '/');

    // Conecta ao banco
    echo "📥 Conectando ao Supabase...\n";
    $db = Database::getConnection();
    echo "✅ Conectado com sucesso!\n\n";

    // Lê arquivo SQL
    $sqlFile = __DIR__ . '/../database/migrations/003_alter_tesoureiro_financeiro.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("Arquivo não encontrado: {$sqlFile}");
    }

    echo "📖 Lendo arquivo SQL...\n";
    $sql = file_get_contents($sqlFile);
    echo "✅ Arquivo lido (" . strlen($sql) . " bytes).\n\n";

    // Remove comentários e divide em comandos
    $lines = explode("\n", $sql);
    $commands = [];
    $currentCommand = '';

    foreach ($lines as $line) {
        // Remove comentários
        $line = preg_replace('/--.*$/', '', $line);
        $line = trim($line);

        if (empty($line)) {
            continue;
        }

        $currentCommand .= ' ' . $line;

        if (str_ends_with($line, ';')) {
            $cmd = trim(str_replace(';', '', $currentCommand));
            if (!empty($cmd)) {
                $commands[] = $cmd;
            }
            $currentCommand = '';
        }
    }

    echo "🔄 Executando " . count($commands) . " comandos SQL...\n";
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
        if (stripos($command, 'ALTER TABLE') === 0) {
            preg_match('/ALTER TABLE.*?(\w+)/i', $command, $matches);
            $name = $matches[1] ?? 'ALTER';
            echo "🔧 Alterando tabela: {$name}...";
        } elseif (stripos($command, 'CREATE TABLE') === 0) {
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
            $result = $stmt->execute();
            echo " ✅\n";
            $successCount++;
        } catch (\PDOException $e) {
            // Alguns erros são aceitáveis (tipo já existe, coluna já existe)
            $msg = $e->getMessage();
            if (strpos($msg, 'already exists') !== false || 
                strpos($msg, 'duplicate key') !== false ||
                strpos($msg, 'already added') !== false ||
                strpos($msg, 'column') !== false && strpos($msg, 'already exists') !== false) {
                echo " ⚠️  (já existe)\n";
                $successCount++;
            } else {
                echo " ❌ ERRO\n";
                echo "   └─ " . $e->getMessage() . "\n";
                $errorCount++;
            }
        }
    }

    if ($errorCount === 0) {
        $db->commit();
        echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "✅ MIGRATION EXECUTADA COM SUCESSO!\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "📊 Resultado: {$successCount} comandos ✅ | 0 erros ❌\n\n";
        echo "📋 Estrutura garantida:\n";
        echo "   • categorias_financeiras (22 registros com seed)\n";
        echo "   • lancamentos_financeiros\n";
        echo "   • comprovantes_pix (com tipo_arquivo, nome_arquivo, descricao_usuario)\n";
        echo "   • mensalidades_status\n";
        echo "   • regularidade_obreiro\n";
        echo "   • fechamento_mensal\n";
        echo "   • ajustes_saldo_auditoria\n";
        echo "   • tronco_solidariedade\n\n";
        echo "✨ Sistema Tesouraria PRONTO PARA USAR!\n\n";
        echo "🎯 Próximos passos:\n";
        echo "   1. Acesse: {$appUrl}/tesouraria/caixa\n";
        echo "   2. Obreiro envia PIX foto via Telegram Bot\n";
        echo "   3. Tesoureiro valida na aba Comprovantes\n";
        echo "   4. Sistema cria lançamento automaticamente!\n\n";
    } else {
        $db->rollBack();
        echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "⚠️  MIGRATION COM ALGUNS ERROS\n";
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
