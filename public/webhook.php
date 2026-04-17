<?php
// public/webhook.php

session_start();

use App\Config\Env;
use App\Bot\TelegramClient;
use App\Bot\CommandHandler;
use App\Core\Tenant\TenantContext;

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
        $obreiroModel = new \App\Models\Obreiro();
        $telegramId = (int) ($update['message']['from']['id'] ?? $update['callback_query']['from']['id'] ?? 0);
        if ($telegramId > 0) {
            $obreiroWebhook = $obreiroModel->findByTelegramId($telegramId);
            if (is_array($obreiroWebhook) && !empty($obreiroWebhook['loja_id'])) {
                $_SESSION = array_merge($_SESSION ?? [], (new TenantContext(
                    (string) $obreiroWebhook['loja_id'],
                    (string) $obreiroWebhook['loja_id'],
                    'Loja ' . (string) $obreiroWebhook['loja_id'],
                    true
                ))->toSessionPayload());
            }
        }
        $sessaoModel = new \App\Models\Sessao();
        $presencaModel = new \App\Models\Presenca();
        $handler = new CommandHandler($client, $obreiroModel, $sessaoModel, $presencaModel);
        $handler->handle($update);
        error_log("[webhook] update processado com sucesso");
    } catch (\Throwable $e) {
        error_log("[webhook] erro ao processar update: " . $e->getMessage());
    }
}

http_response_code(200);
echo "OK";
