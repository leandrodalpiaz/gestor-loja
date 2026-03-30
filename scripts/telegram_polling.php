<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/autoload.php';

use App\Bot\CommandHandler;
use App\Bot\TelegramClient;
use App\Config\Env;

Env::load(__DIR__ . '/../.env');

$token = trim((string) Env::get('TELEGRAM_BOT_TOKEN', ''));
if ($token === '') {
    fwrite(STDERR, "Defina TELEGRAM_BOT_TOKEN no .env\n");
    exit(1);
}

$once = false;
$timeout = 25;
$sleepMs = 300;
$resetOffset = false;
$disableWebhook = true;
$offsetFile = __DIR__ . '/tmp/telegram_offset.txt';

foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--once') {
        $once = true;
        continue;
    }
    if ($arg === '--reset-offset') {
        $resetOffset = true;
        continue;
    }
    if ($arg === '--no-delete-webhook') {
        $disableWebhook = false;
        continue;
    }
    if (str_starts_with($arg, '--timeout=')) {
        $timeout = max(1, min(50, (int) substr($arg, 10)));
        continue;
    }
    if (str_starts_with($arg, '--sleep-ms=')) {
        $sleepMs = max(0, (int) substr($arg, 11));
        continue;
    }
    if (str_starts_with($arg, '--offset-file=')) {
        $customOffsetFile = trim((string) substr($arg, 14));
        if ($customOffsetFile !== '') {
            $offsetFile = $customOffsetFile;
        }
    }
}

$offsetDir = dirname($offsetFile);
if (!is_dir($offsetDir)) {
    @mkdir($offsetDir, 0777, true);
}

if ($resetOffset && file_exists($offsetFile)) {
    @unlink($offsetFile);
}

$offset = readOffset($offsetFile);

if ($disableWebhook) {
    $deleteResp = telegramCall($token, 'deleteWebhook', ['drop_pending_updates' => 'false']);
    if (!($deleteResp['ok'] ?? false)) {
        error_log('[polling] aviso: nao foi possivel remover webhook antes do polling.');
    }
}

$client = new TelegramClient();
$obreiroModel = new \App\Models\Obreiro();
$sessaoModel = new \App\Models\Sessao();
$presencaModel = new \App\Models\Presenca();
$handler = new CommandHandler($client, $obreiroModel, $sessaoModel, $presencaModel);

$running = true;

if (function_exists('pcntl_async_signals') && function_exists('pcntl_signal')) {
    pcntl_async_signals(true);
    pcntl_signal(SIGINT, static function () use (&$running): void {
        $running = false;
    });
    pcntl_signal(SIGTERM, static function () use (&$running): void {
        $running = false;
    });
}

echo "[polling] iniciado timeout={$timeout}s offset_inicial={$offset}\n";

while ($running) {
    $params = [
        'timeout' => (string) $timeout,
        'allowed_updates' => json_encode(['message', 'callback_query'], JSON_UNESCAPED_UNICODE),
    ];
    if ($offset > 0) {
        $params['offset'] = (string) $offset;
    }

    $response = telegramCall($token, 'getUpdates', $params);
    if (!($response['ok'] ?? false)) {
        $description = (string) ($response['description'] ?? 'erro desconhecido');
        error_log('[polling] erro getUpdates: ' . $description);
        if ($once) {
            break;
        }
        sleep(1);
        continue;
    }

    $updates = $response['result'] ?? [];
    if (!is_array($updates) || empty($updates)) {
        if ($once) {
            break;
        }
        continue;
    }

    foreach ($updates as $update) {
        if (!is_array($update)) {
            continue;
        }

        $updateId = (int) ($update['update_id'] ?? 0);

        try {
            $handler->handle($update);
        } catch (\Throwable $e) {
            error_log('[polling] erro ao processar update: ' . $e->getMessage());
        }

        if ($updateId >= $offset) {
            $offset = $updateId + 1;
        }
    }

    saveOffset($offsetFile, $offset);

    if ($once) {
        break;
    }

    if ($sleepMs > 0) {
        usleep($sleepMs * 1000);
    }
}

echo "[polling] encerrado offset_final={$offset}\n";
exit(0);

function readOffset(string $offsetFile): int
{
    if (!file_exists($offsetFile)) {
        return 0;
    }

    $raw = trim((string) @file_get_contents($offsetFile));
    if ($raw === '' || !is_numeric($raw)) {
        return 0;
    }

    return max(0, (int) $raw);
}

function saveOffset(string $offsetFile, int $offset): void
{
    @file_put_contents($offsetFile, (string) $offset, LOCK_EX);
}

function telegramCall(string $token, string $method, array $params = []): array
{
    $url = "https://api.telegram.org/bot{$token}/{$method}";
    $opts = [
        'http' => [
            'method' => empty($params) ? 'GET' : 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => empty($params) ? '' : http_build_query($params),
            'ignore_errors' => true,
            'timeout' => 70,
        ],
    ];

    $context = stream_context_create($opts);
    $raw = @file_get_contents($url, false, $context);
    if ($raw === false) {
        return ['ok' => false, 'description' => 'falha de rede na API do Telegram'];
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return ['ok' => false, 'description' => 'resposta invalida da API do Telegram'];
    }

    return $decoded;
}
