<?php
// public/webhook.php

use App\Config\Env;
use App\Bot\TelegramClient;
use App\Bot\CommandHandler;

require_once __DIR__ . '/../src/autoload.php';

Env::load(__DIR__ . '/../.env');

$content = file_get_contents("php://input");
$update = json_decode($content, true);

$updateId = $update['update_id'] ?? 'n/a';
$hasMessage = isset($update['message']) ? '1' : '0';
$hasCallback = isset($update['callback_query']) ? '1' : '0';
error_log("[webhook] recebido update_id={$updateId} message={$hasMessage} callback={$hasCallback}");

if ($update) {
    try {
        $client = new TelegramClient();
        $handler = new CommandHandler($client);
        $handler->handle($update);
        error_log("[webhook] update processado com sucesso");
    } catch (\Throwable $e) {
        error_log("[webhook] erro ao processar update: " . $e->getMessage());
    }
}

http_response_code(200);
echo "OK";