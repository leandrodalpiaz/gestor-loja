<?php

namespace App\Bot;

class CommandHandler
{
    private TelegramClient $telegram;

    public function __construct(TelegramClient $telegram)
    {
        $this->telegram = $telegram;
    }

    public function handle(array $update): void
    {
        if (!isset($update['message'])) {
            return;
        }

        $chatId = $update['message']['chat']['id'] ?? null;
        $text = $update['message']['text'] ?? '';

        if (!$chatId) {
            return;
        }

        switch (trim($text)) {
            case '/start':
                $this->handleStart($chatId);
                break;
            case '/ajuda':
            case '/help':
                $this->handleHelp($chatId);
                break;
            default:
                // Comando não reconhecido ou lógica adicional
                break;
        }
    }

    private function handleStart($chatId): void
    {
        $mensagem = "Olá, Irmão! O Bot da Loja Renascença está online e estruturado em nossa nova arquitetura.\n\nSeu ID de registro é: <b>{$chatId}</b>.";
        $this->telegram->sendMessage($chatId, $mensagem);
    }

    private function handleHelp($chatId): void
    {
        $mensagem = "Comandos disponíveis:\n/start - Iniciar Bot\n/ajuda - Menu de Ajuda";
        $this->telegram->sendMessage($chatId, $mensagem);
    }
}