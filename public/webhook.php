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
$chatType = (string) ($update['message']['chat']['type'] ?? $update['callback_query']['message']['chat']['type'] ?? 'unknown');
$chatId = (string) ($update['message']['chat']['id'] ?? $update['callback_query']['message']['chat']['id'] ?? 'n/a');
$userId = (string) ($update['message']['from']['id'] ?? $update['callback_query']['from']['id'] ?? 'n/a');
$command = trim((string) ($update['message']['text'] ?? $update['callback_query']['data'] ?? 'unknown'));
$appUrl = rtrim((string) Env::get('APP_URL', ''), '/');
$safeCommand = $command !== '' ? explode(' ', $command)[0] : 'unknown';
$safeAppUrl = $appUrl !== '' ? $appUrl : 'missing';
error_log("[webhook] recebido update_id={$updateId} message={$hasMessage} callback={$hasCallback} chat_type={$chatType} chat_id={$chatId} user_id={$userId} comando={$safeCommand} app_url={$safeAppUrl}");

if ($update) {
    try {
        $client = new TelegramClient();

        // Fallback: garante um tenant mínimo a partir de env quando não há sessão (webhook é stateless).
        // Isso evita falhas quando o bot precisa consultar sessões/relatórios antes de identificar o obreiro.
        if (
            !isset($_SESSION['tenant_id'])
            && !isset($_SESSION['tenant_slug'])
            && !isset($_SESSION['tenant_name'])
        ) {
            $fallback = TenantContext::fromSessionAndEnv($_SESSION ?? [], $_ENV);
            $_SESSION = array_merge($_SESSION ?? [], $fallback->toSessionPayload());
        }

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

        // Se falhar por tenant não resolvido, tenta responder com orientação em vez de "silêncio".
        try {
            $chatId = (string) ($update['message']['chat']['id'] ?? $update['callback_query']['message']['chat']['id'] ?? '');
            if ($chatId !== '') {
                $msg = 'Erro ao processar o bot: ' . $e->getMessage()
                    . "\n\nSe for \"Loja não identificada\", configure no Render:"
                    . "\n- APP_LOJA_NUMERO (ex.: 0001)"
                    . "\n- (opcional) APP_DEFAULT_TENANT_SLUG / APP_DEFAULT_TENANT_ID"
                    . "\nE mantenha APP_URL correto.";
                (new TelegramClient())->sendMessage((int) $chatId, $msg);
            }
        } catch (\Throwable $ignored) {
            // Evita loop de erros no webhook.
        }
    }
}

http_response_code(200);
echo "OK";
