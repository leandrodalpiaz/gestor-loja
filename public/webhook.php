<?php
// public/webhook.php

use App\Config\Env;
use App\Bot\TelegramClient;
use App\Bot\CommandHandler;

require_once __DIR__ . '/../src/autoload.php';

// Carrega o .env a partir da raiz do projeto
Env::load(__DIR__ . '/../.env');

// Aditional DB Connection instantiation logic if needed inside commands later
// $db = \App\Config\Database::getConnection();

// Lógica principal do webhook
$content = file_get_contents("php://input");
$update = json_decode($content, true);

if ($update) {
    $client = new TelegramClient();
    $handler = new CommandHandler($client);
    $handler->handle($update);
}

// Responde ao servidor do Telegram que recebemos os dados com sucesso
http_response_code(200);
echo "OK";