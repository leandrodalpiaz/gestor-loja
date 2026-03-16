
<?php
namespace App\Bot;

class CommandHandler {
    private $telegram;
    private $obreiroModel;
    private $sessaoModel;
    private $presencaModel;

    public function __construct($telegram, $obreiroModel, $sessaoModel, $presencaModel) {
        $this->telegram = $telegram;
        $this->obreiroModel = $obreiroModel;
        $this->sessaoModel = $sessaoModel;
        $this->presencaModel = $presencaModel;
    }

    public function sendMenuPresenca($chatId) {
        $mensagem = "Bem-vindo ao assistente da Loja.";
        $teclado = [
            'inline_keyboard' => [
                [
                    ['text' => '✅ Confirmar Presença', 'callback_data' => 'presenca_confirmar'],
                    ['text' => '❌ Informar Ausência', 'callback_data' => 'presenca_ausencia'],
                ],
                [
                    ['text' => '📜 Ver Próxima Sessão', 'callback_data' => 'sessao_info']
                ]
            ]
        ];
        $this->telegram->sendMessage($chatId, $mensagem, $teclado);
    }

    public function handleHelp($chatId) {
        $mensagem = "ℹ️ <b>Ajuda do Gestor da Loja</b>\n\n";
        $mensagem .= "Este bot auxilia na gestão da nossa Loja Maçônica.\n\n";
        $mensagem .= "<b>Comandos disponíveis:</b>\n";
        $mensagem .= "/start - Inicia a interação e valida seu cadastro\n";
        $mensagem .= "/chancelaria - Painel do Chanceler (cadastro de efemérides)\n";
        $mensagem .= "/ajuda - Exibe esta mensagem de ajuda\n\n";
        $mensagem .= "Para outras dúvidas, contate a Secretaria da Loja.";
        $this->telegram->sendMessage($chatId, $mensagem);
    }

    public function handleChancelaria($chatId, $requesterTelegramId) {
        $obreiro = $this->obreiroModel->findByTelegramId($requesterTelegramId);
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
                    [['text' => '🗓️ Neste dia (Hoje)',    'callback_data' => 'menu_hoje']],
                    [['text' => '🎂 Aniversários',       'callback_data' => 'menu_aniversarios']],
                    [['text' => '⚒️ Datas Maçônicas',     'callback_data' => 'menu_datas_maconicas']],
                    [['text' => '📜 Histórico da Ordem',  'callback_data' => 'menu_historico']],
                    [['text' => '💬 Mensagens Fallback',   'callback_data' => 'menu_fallback']],
                ],
            ]
        );
    }
}
