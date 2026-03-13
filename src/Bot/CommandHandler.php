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
            case '/chancelaria':
                $this->handleChancelaria($chatId);
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
            // Conta nova/não vinculada ou membro inativo
            $mensagem = "Olá! Bem-vindo ao assistente da nossa Loja.\n\n";
            $mensagem .= "Seu ID do Telegram é: <b>{$chatId}</b>\n\n";
            $mensagem .= "⚠️ <i>Ainda não encontrei um cadastro de membro ATIVO no banco de dados vinculado a este Telegram.</i> \n";
            $mensagem .= "Por favor, encaminhe este número ({$chatId}) para o Irmão Secretário ou Chanceler para liberar o seu acesso!";

            $this->telegram->sendMessage($chatId, $mensagem);
        }
    }

    private function handleHelp(int $chatId): void
    {
        $mensagem = "ℹ️ <b>Ajuda do Gestor da Loja</b>\n\n";
        $mensagem .= "Este bot auxilia na gestão da nossa Loja Maçônica.\n\n";
        $mensagem .= "<b>Comandos disponíveis:</b>\n";
        $mensagem .= "/start - Inicia a interação e valida seu cadastro\n";
        $mensagem .= "/chancelaria - Painel do Chanceler (cadastro de efemérides)\n";
        $mensagem .= "/ajuda - Exibe esta mensagem de ajuda\n\n";
        $mensagem .= "Para outras dúvidas, contate a Secretaria da Loja.";

        $this->telegram->sendMessage($chatId, $mensagem);
    }

    // ---------------------------------------------------------------
    // Sessão do Chanceler
    // ---------------------------------------------------------------

    private function handleChancelaria(int $chatId): void
    {
        $obreiro = $this->obreiroModel->findByTelegramId($chatId);
        if (!$obreiro || strtolower(trim((string) ($obreiro['cargo'] ?? ''))) !== 'chanceler') {
            $this->telegram->sendMessage($chatId, '⛔ Acesso restrito ao Chanceler da Loja.');
            return;
        }

        $nome = $obreiro['nome_historico'] ?? $obreiro['nome'] ?? 'Chanceler';
        $this->telegram->sendMessage(
            $chatId,
            "🏛️ <b>Sessão do Chanceler</b>\n\nOlá, Irmão <b>{$nome}</b>.\nEscolha o tipo de efeméride a gerenciar:",
            [
                'inline_keyboard' => [
                    [['text' => '🎂 Aniversários',       'callback_data' => 'menu_aniversarios']],
                    [['text' => '⚒️ Datas Maçônicas',     'callback_data' => 'menu_datas_maconicas']],
                    [['text' => '📜 Histórico da Ordem',  'callback_data' => 'menu_historico']],
                    [['text' => '💬 Mensagens Fallback',   'callback_data' => 'menu_fallback']],
                ],
            ]
        );
    }

    private function sendMenuAniversarios(int $chatId): void
    {
        $base = rtrim((string) ($_ENV['APP_URL'] ?? ''), '/');
        $this->telegram->sendMessage(
            $chatId,
            "🎂 <b>Aniversários</b>\n\nSelecione o tipo de aniversariante para abrir a ficha de cadastro:",
            [
                'inline_keyboard' => [
                    [
                        ['text' => '👔 Irmão',    'web_app' => ['url' => "{$base}/miniapp/aniversario?tratamento=irmao"]],
                        ['text' => '👩 Cunhada',  'web_app' => ['url' => "{$base}/miniapp/aniversario?tratamento=cunhada"]],
                    ],
                    [
                        ['text' => '👧 Sobrinha', 'web_app' => ['url' => "{$base}/miniapp/aniversario?tratamento=sobrinha"]],
                        ['text' => '👦 Sobrinho', 'web_app' => ['url' => "{$base}/miniapp/aniversario?tratamento=sobrinho"]],
                    ],
                ],
            ]
        );
    }

    private function sendMenuDatasMaconicas(int $chatId): void
    {
        $base = rtrim((string) ($_ENV['APP_URL'] ?? ''), '/');
        $enc  = static fn(string $s) => rawurlencode($s);
        $this->telegram->sendMessage(
            $chatId,
            "⚒️ <b>Datas Maçônicas</b>\n\nSelecione o tipo de evento para abrir a ficha de cadastro:",
            [
                'inline_keyboard' => [
                    [
                        ['text' => '⚒️ Iniciação',  'web_app' => ['url' => "{$base}/miniapp/data-maconica?tipo=" . $enc('Iniciação')]],
                        ['text' => '📐 Elevação',   'web_app' => ['url' => "{$base}/miniapp/data-maconica?tipo=" . $enc('Elevação')]],
                    ],
                    [
                        ['text' => '👑 Exaltação',  'web_app' => ['url' => "{$base}/miniapp/data-maconica?tipo=" . $enc('Exaltação')]],
                        ['text' => '🔨 Instalação', 'web_app' => ['url' => "{$base}/miniapp/data-maconica?tipo=" . $enc('Instalação')]],
                    ],
                    [
                        ['text' => '🌙 Oriente Eterno', 'web_app' => ['url' => "{$base}/miniapp/data-maconica?tipo=" . $enc('Oriente Eterno')]],
                        ['text' => '🔗 Filiação',        'web_app' => ['url' => "{$base}/miniapp/data-maconica?tipo=" . $enc('Filiação')]],
                    ],
                    [
                        ['text' => '🌟 Posse Grão Mestre',   'web_app' => ['url' => "{$base}/miniapp/data-maconica?tipo=" . $enc('Posse Grão Mestre')]],
                        ['text' => '🏅 Membro Honorário',     'web_app' => ['url' => "{$base}/miniapp/data-maconica?tipo=" . $enc('Concessão de Membro Honorário')]],
                    ],
                ],
            ]
        );
    }

    private function sendMenuHistorico(int $chatId): void
    {
        $base = rtrim((string) ($_ENV['APP_URL'] ?? ''), '/');
        $this->telegram->sendMessage(
            $chatId,
            "📜 <b>Histórico da Ordem</b>\n\nAdicione datas históricas com texto explicativo. Esses registros disparam automaticamente no aniversário de cada data:",
            [
                'inline_keyboard' => [
                    [['text' => '➕ Novo registro histórico', 'web_app' => ['url' => "{$base}/miniapp/historico"]]],
                ],
            ]
        );
    }

    private function sendMenuFallback(int $chatId): void
    {
        $base = rtrim((string) ($_ENV['APP_URL'] ?? ''), '/');
        $this->telegram->sendMessage(
            $chatId,
            "💬 <b>Mensagens Fallback</b>\n\nEssas frases são enviadas nos dias sem nenhum evento cadastrado. Gerencie, ative ou desative cada mensagem:",
            [
                'inline_keyboard' => [
                    [['text' => '⚙️ Gerenciar mensagens fallback', 'web_app' => ['url' => "{$base}/miniapp/fallback"]]],
                ],
            ]
        );
    }


    // ---------------------------------------------------------------
    // Callbacks de botões inline
    // ---------------------------------------------------------------

    private function handleCallback(array $callbackQuery): void
    {
        $chatId       = $callbackQuery['message']['chat']['id'];
        $callbackData = $callbackQuery['data'];
        $callbackId   = $callbackQuery['id'];

        // Menus do chanceler tratados antes de verificar obreiro
        switch ($callbackData) {
            case 'menu_aniversarios':
                $this->sendMenuAniversarios($chatId);
                $this->telegram->answerCallbackQuery($callbackId);
                return;
            case 'menu_datas_maconicas':
                $this->sendMenuDatasMaconicas($chatId);
                $this->telegram->answerCallbackQuery($callbackId);
                return;
            case 'menu_historico':
                $this->sendMenuHistorico($chatId);
                $this->telegram->answerCallbackQuery($callbackId);
                return;
            case 'menu_fallback':
                $this->sendMenuFallback($chatId);
                $this->telegram->answerCallbackQuery($callbackId);
                return;
        }

        $obreiro = $this->obreiroModel->findByTelegramId($chatId);

        if (!$obreiro) {
            $this->telegram->sendMessage($chatId, 'Usuário não autenticado.');
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }

        $mensagem = '';

        switch ($callbackData) {
            case 'presenca_confirmar':
                $proxima = $this->sessaoModel->getProximaSessao();
                if ($proxima) {
                    $this->presencaModel->registrar($proxima['id'], $chatId, 'Confirmado');
                    $mensagem = "✅ Irmão {$obreiro['nome']}, sua presença para a sessão de <b>" . date('d/m/Y', strtotime($proxima['data_hora'])) . "</b> foi confirmada com sucesso!";
                } else {
                    $mensagem = '❌ Nenhuma sessão futura está agendada no momento.';
                }
                break;

            case 'presenca_ausencia':
                $proxima = $this->sessaoModel->getProximaSessao();
                if ($proxima) {
                    $this->presencaModel->registrar($proxima['id'], $chatId, 'Ausente');
                    $mensagem = "❌ Entendido, Irmão {$obreiro['nome']}. Sua ausência para a sessão de <b>" . date('d/m/Y', strtotime($proxima['data_hora'])) . "</b> foi devidamente registrada.";
                } else {
                    $mensagem = '❌ Nenhuma sessão futura está agendada no momento.';
                }
                break;

            case 'sessao_info':
                $proxima = $this->sessaoModel->getProximaSessao();
                if ($proxima) {
                    $data      = date("d/m/Y \\à\\s H:i", strtotime($proxima['data_hora']));
                    $mensagem  = "📜 <b>Próxima Sessão:</b>\n\n";
                    $mensagem .= "<b>Título:</b> {$proxima['titulo']}\n";
                    $mensagem .= "<b>Data:</b> {$data}\n";
                    $mensagem .= "<b>Grau:</b> {$proxima['grau']}\n";
                    $mensagem .= "<b>Traje:</b> {$proxima['traje']}";
                } else {
                    $mensagem = 'Nenhuma sessão futura programada no momento.';
                }
                break;

            default:
                $mensagem = 'Opção não reconhecida.';
                break;
        }

        if ($mensagem !== '') {
            $this->telegram->sendMessage($chatId, $mensagem);
        }

        $this->telegram->answerCallbackQuery($callbackId);
    }
}