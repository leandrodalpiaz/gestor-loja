<?php

namespace App\Bot;

use App\Models\Obreiro;
use App\Models\Sessao;
use App\Models\Presenca;

class CommandHandler
{
    private TelegramClient $telegram;
    private Obreiro $obreiroModel;
    private Sessao $sessaoModel;
    private Presenca $presencaModel;

    public function __construct(TelegramClient $telegram)
    {
        $this->telegram = $telegram;
        $this->obreiroModel = new Obreiro();
        $this->sessaoModel = new Sessao();
        $this->presencaModel = new Presenca();
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
                $proxima = $this->sessaoModel->getProximaSessao();
                if ($proxima) {
                    $this->presencaModel->registrar($proxima['id'], $chatId, 'Confirmado');
                    $mensagem = "✅ Irmão {$obreiro['nome']}, sua presença para a sessão de <b>".date('d/m/Y', strtotime($proxima['data_hora']))."</b> foi confirmada com sucesso!";
                } else {
                    $mensagem = "❌ Nenhuma sessão futura está agendada no momento.";
                }
                break;

            case 'presenca_ausencia':
                $proxima = $this->sessaoModel->getProximaSessao();
                if ($proxima) {
                    $this->presencaModel->registrar($proxima['id'], $chatId, 'Ausente');
                    $mensagem = "❌ Entendido, Irmão {$obreiro['nome']}. Sua ausência para a sessão de <b>".date('d/m/Y', strtotime($proxima['data_hora']))."</b> foi devidamente justificada/registrada.";
                } else {
                    $mensagem = "❌ Nenhuma sessão futura está agendada no momento.";
                }
                break;

            case 'sessao_info':
                $proxima = $this->sessaoModel->getProximaSessao();
                if ($proxima) {
                    $data = date('d/m/Y à\s H:i', strtotime($proxima['data_hora']));
                    $mensagem = "📜 <b>Próxima Sessão:</b>\n\n";
                    $mensagem .= "<b>Título:</b> {$proxima['titulo']}\n";
                    $mensagem .= "<b>Data:</b> {$data}\n";
                    $mensagem .= "<b>Grau:</b> {$proxima['grau']}\n";
                    $mensagem .= "<b>Traje:</b> {$proxima['traje']}";
                } else {
                    $mensagem = "Nenhuma sessão futura programada no momento.";
                }
                break;

            default:
                $mensagem = "Opção não reconhecida.";
                break;
        }

        $this->telegram->sendMessage($chatId, $mensagem);

        // Avisar o Telegram oficial que nós recebemos o clique para remover o reloginho do botão e evitar duplicação de requests
        $this->telegram->answerCallbackQuery($callbackId);
    }
}