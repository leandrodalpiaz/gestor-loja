<?php

namespace App\Bot;

use App\Models\Obreiro;

class CommandHandler
{
    private TelegramClient $telegram;
    private Obreiro $obreiroModel;

    public function __construct(TelegramClient $telegram)
    {
        $this->telegram = $telegram;
        $this->obreiroModel = new Obreiro();
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
        // Verifica no banco de dados se esse ID do Telegram já pertence a um Irmão
        $obreiro = $this->obreiroModel->findByTelegramId($chatId);

        if ($obreiro) {
            // Obreiro reconhecido no banco de dados!
            $grau = ucfirst($obreiro['grau']);
            $mensagem = "TFA, Meu Irmão <b>{$obreiro['nome']}</b>!\n";
            $mensagem .= "Seu cadastro foi reconhecido ({$grau}).\n\n";
            $mensagem .= "Utilize o menu para interagir com a Loja.";
            
            // Aqui futuramente colocaremos os botões In-Line do Telegram
        } else {
            // Conta nova/não vinculada
            $mensagem = "Olá! Bem-vindo ao assistente da Loja Renascença.\n\n";
            $mensagem .= "Seu ID do Telegram é: <b>{$chatId}</b>\n\n";
            $mensagem .= "⚠️ <i>Ainda não encontrei o seu cadastro no meu banco de dados.</i> \n";
            $mensagem .= "Por favor, envie este número ({$chatId}) para o Irmão Secretário ou Chanceler para ele vincular você ao nosso quadro!";
        }

        $this->telegram->sendMessage($chatId, $mensagem);
    }

    private function handleHelp($chatId): void
    {
        $mensagem = "Comandos disponíveis:\n/start - Iniciar Bot\n/ajuda - Menu de Ajuda";
        $this->telegram->sendMessage($chatId, $mensagem);
    }
}