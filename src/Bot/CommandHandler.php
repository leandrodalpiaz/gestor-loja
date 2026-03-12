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
        // Se a interação foi um clique de botão (Callback Query)
        if (isset($update['callback_query'])) {
            $this->handleCallback($update['callback_query']);
            return;
        }

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
            $mensagem .= "O que você deseja fazer agora?";

            // Criando o teclado de botões Inline
            $teclado = [
                'inline_keyboard' => [
                    [ // Linha 1
                        ['text' => '✅ Confirmar Presença', 'callback_data' => 'presenca_confirmar'],
                        ['text' => '❌ Informar Ausência', 'callback_data' => 'presenca_ausencia'],
                    ],
                    [ // Linha 2
                        ['text' => '📜 Ver Próxima Sessão', 'callback_data' => 'sessao_info']
                    ]
                ]
            ];

            $this->telegram->sendMessage($chatId, $mensagem, $teclado);
        } else {
            // Conta nova/não vinculada
            $mensagem = "Olá! Bem-vindo ao assistente da Loja Renascença.\n\n";
            $mensagem .= "Seu ID do Telegram é: <b>{$chatId}</b>\n\n";
            $mensagem .= "⚠️ <i>Ainda não encontrei o seu cadastro no meu banco de dados.</i> \n";
            $mensagem .= "Por favor, envie este número ({$chatId}) para o Irmão Secretário ou Chanceler para ele vincular você ao nosso quadro!";
            
            $this->telegram->sendMessage($chatId, $mensagem);
        }
    }

    private function handleCallback(array $callbackQuery): void
    {
        $chatId = $callbackQuery['message']['chat']['id'];
        $callbackData = $callbackQuery['data']; // ex: 'presenca_confirmar'
        $callbackId = $callbackQuery['id'];

        $obreiro = $this->obreiroModel->findByTelegramId($chatId);
        
        if (!$obreiro) {
            $this->telegram->sendMessage($chatId, "Usuário não autenticado.");
            return;
        }

        switch ($callbackData) {
            case 'presenca_confirmar':
                $mensagem = "✅ Irmão {$obreiro['nome']}, sua presença para a próxima sessão foi confirmada com sucesso!";
                // Futuramente: Chamar o Model para Salvar no Banco
                break;
            
            case 'presenca_ausencia':
                $mensagem = "❌ Entendido, Irmão. Sua ausência foi registrada. Desejamos que tudo esteja bem!";
                // Futuramente: Chamar o Model para Salvar no Banco
                break;

            case 'sessao_info':
                $mensagem = "📜 <b>Próxima Sessão:</b>\nData: (Exemplo) Quinta-feira às 20h\nGrau: Companheiro\nTraje: Maçônico";
                break;

            default:
                $mensagem = "Opção não reconhecida.";
                break;
        }

        $this->telegram->sendMessage($chatId, $mensagem);
        
        // As boas práticas da API do Telegram exigem responder ao CallbackQuery para tirar o ícone de "carregando" (reloginho) do botão
        // Como o TelegramClient ainda não tem "answerCallbackQuery" nativo, enviaremos apenas a mensagem e o frontend do Telegram aceita após alguns segundos.
        $this->telegram->sendMessage($chatId, $mensagem);
    }
}