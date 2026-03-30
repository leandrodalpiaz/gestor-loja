<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/autoload.php';

use App\Config\Env;

Env::load(__DIR__ . '/../.env');

$token = trim((string) Env::get('TELEGRAM_BOT_TOKEN', ''));
if ($token === '') {
    fwrite(STDERR, "Defina TELEGRAM_BOT_TOKEN no .env\n");
    exit(1);
}

$action = strtolower(trim((string) ($argv[1] ?? 'status')));
$baseUrl = rtrim(trim((string) Env::get('APP_URL', '')), '/');
$urlArg = trim((string) ($argv[2] ?? ''));

switch ($action) {
    case 'status':
        $response = telegramCall($token, 'getWebhookInfo');
        printJson($response);
        exit(empty($response['ok']) ? 1 : 0);

    case 'set':
        $webhookUrl = $urlArg !== '' ? $urlArg : ($baseUrl !== '' ? $baseUrl . '/webhook.php' : '');
        if ($webhookUrl === '') {
            fwrite(STDERR, "Informe URL no comando ou defina APP_URL no .env\n");
            exit(1);
        }
        $response = telegramCall($token, 'setWebhook', ['url' => $webhookUrl]);
        printJson($response);
        exit(empty($response['ok']) ? 1 : 0);

    case 'delete':
        $response = telegramCall($token, 'deleteWebhook', ['drop_pending_updates' => 'false']);
        printJson($response);
        exit(empty($response['ok']) ? 1 : 0);

    default:
        fwrite(STDERR, "Uso:\n");
        fwrite(STDERR, "  php scripts/telegram_webhook.php status\n");
        fwrite(STDERR, "  php scripts/telegram_webhook.php set [https://url-publica/webhook.php]\n");
        fwrite(STDERR, "  php scripts/telegram_webhook.php delete\n");
        exit(1);
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
            'timeout' => 30,
        ],
    ];

    $context = stream_context_create($opts);
    $raw = @file_get_contents($url, false, $context);
    if ($raw === false) {
        return ['ok' => false, 'error' => 'Falha ao chamar API do Telegram.'];
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return ['ok' => false, 'error' => 'Resposta invalida da API do Telegram.', 'raw' => $raw];
    }

    return $decoded;
}

function printJson(array $payload): void
{
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
}
