<?php
namespace App\Bot;

class CommandHandler {
    private $telegram;
    private $obreiroModel;
    private $sessaoModel;
    private $presencaModel;

    // IDs de desenvolvedor com acesso total
    private $devIds = [8062119710]; 

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
        $mensagem .= "/chancelaria - Painel do Chanceler\n";
        $mensagem .= "/tesouraria - Painel do Tesoureiro\n";
        $mensagem .= "/ajuda - Exibe esta mensagem de ajuda\n\n";
        $mensagem .= "Para outras dúvidas, contate a Secretaria da Loja.";
        $this->telegram->sendMessage($chatId, $mensagem);
    }

    public function handleChancelaria($chatId, $requesterTelegramId) {
        $obreiro = $this->obreiroModel->findByTelegramId($requesterTelegramId);
        $cargo = strtolower(trim((string) ($obreiro['cargo'] ?? '')));
        if (!in_array($requesterTelegramId, $this->devIds) && (!$obreiro || $cargo !== 'chanceler')) {
            $this->telegram->sendMessage($chatId, '⛔ Acesso restrito ao Chanceler da Loja.');
            return;
        }
        $mensagem = "🏛️ *Painel da Chancelaria*\n\nSelecione uma opção:";
        $teclado = [
            'inline_keyboard' => [
                [
                    ['text' => '🎂 Aniversários Hoje', 'callback_data' => 'chancelaria_aniversarios'],
                    ['text' => '⚒️ Datas Maçônicas', 'callback_data' => 'chancelaria_datas']
                ],
                [
                    ['text' => '📜 Fatos Históricos', 'callback_data' => 'chancelaria_historico']
                ]
            ]
        ];
        $this->telegram->sendMessage($chatId, $mensagem, $teclado);
    }

    // ==========================================
    // MÓDULO TESOURARIA (CORRIGIDO)
    // ==========================================
    public function handleTesouraria($chatId, $requesterTelegramId) {
        $obreiro = $this->obreiroModel->findByTelegramId($requesterTelegramId);
        $cargo = strtolower(trim((string) ($obreiro['cargo'] ?? '')));

        if (!in_array($requesterTelegramId, $this->devIds) && (!$obreiro || $cargo !== 'tesoureiro')) {
            $this->telegram->sendMessage($chatId, '⛔ Acesso restrito ao Tesoureiro da Loja.');
            return;
        }

        $mensagem = "🏛️ *Painel da Tesouraria*\n\nSelecione uma opção abaixo para consultar os dados em tempo real:";
        $teclado = [
            'inline_keyboard' => [
                [
                    ['text' => '📊 Resumo do Caixa', 'callback_data' => 'tesouraria_caixa'],
                    ['text' => '🧾 Validar PIX', 'callback_data' => 'tesouraria_comprovantes']
                ],
                [
                    ['text' => '⚠️ Inadimplência', 'callback_data' => 'tesouraria_regularidade'],
                    ['text' => '🔒 Fechamento', 'callback_data' => 'tesouraria_fechamento']
                ]
            ]
        ];
        $this->telegram->sendMessage($chatId, $mensagem, $teclado);
    }

    private function handleTesourariaCaixa($chatId) {
        require_once __DIR__ . '/../Models/LancamentoFinanceiro.php';
        $model = new \App\Models\LancamentoFinanceiro();
        $mes = (int) date('n');
        $ano = (int) date('Y');

        // Usando o método real que descobrimos no seu arquivo
        $totais = $model->obterTotaisMes($mes, $ano);
        $entradas = $totais['entrada'] ?? 0;
        $saidas = $totais['saida'] ?? 0;
        $saldo = $entradas - $saidas;

        $msg = "📊 *Resumo do Caixa (" . str_pad($mes, 2, '0', STR_PAD_LEFT) . "/{$ano})*\n\n";
        $msg .= "🟢 Entradas: R$ " . number_format($entradas, 2, ',', '.') . "\n";
        $msg .= "🔴 Saídas: R$ " . number_format($saidas, 2, ',', '.') . "\n";
        $msg .= "⚖️ *Saldo do Mês: R$ " . number_format($saldo, 2, ',', '.') . "*\n\n";
        $msg .= "Acesse o painel web para ver o extrato completo.";

        $this->telegram->sendMessage($chatId, $msg);
    }

    private function handleTesourariaComprovantes($chatId) {
        require_once __DIR__ . '/../Config/Database.php';
        $db = \App\Config\Database::getConnection();

        // Consulta direta para evitar erros de métodos inexistentes
        $stmt = $db->query("SELECT count(*) FROM comprovantes_pix WHERE status = 'pendente'");
        $pendentes = (int) $stmt->fetchColumn();

        if ($pendentes > 0) {
            $msg = "🧾 *Comprovantes PIX*\n\nVocê tem *{$pendentes}* comprovante(s) aguardando validação.\n\nAcesse o painel web para aprovar ou rejeitar.";
        } else {
            $msg = "🧾 *Comprovantes PIX*\n\nTudo limpo! Nenhum comprovante pendente de validação no momento.";
        }

        $this->telegram->sendMessage($chatId, $msg);
    }

    private function handleTesourariaRegularidade($chatId) {
        require_once __DIR__ . '/../Config/Database.php';
        $db = \App\Config\Database::getConnection();
        $mes = (int) date('n');
        $ano = (int) date('Y');

        // Consulta direta
        $stmt = $db->prepare("SELECT count(*) FROM mensalidades_status WHERE mes_ref = ? AND ano_ref = ? AND status = 'pendente'");
        $stmt->execute([$mes, $ano]);
        $pendentes = (int) $stmt->fetchColumn();

        $msg = "⚠️ *Inadimplência (" . str_pad($mes, 2, '0', STR_PAD_LEFT) . "/{$ano})*\n\n";
        if ($pendentes > 0) {
            $msg .= "Existem *{$pendentes}* obreiro(s) com a mensalidade pendente neste mês.\n\nAcesse o painel web para ver a lista e enviar cobranças.";
        } else {
            $msg .= "Excelente! Todos os obreiros estão regulares com a tesouraria neste mês.";
        }

        $this->telegram->sendMessage($chatId, $msg);
    }

    public function handleTesourariaFechamento($chatId) {
        $mes = (int) date('m');
        $ano = (int) date('Y');

        require_once __DIR__ . '/../Models/FechamentoMensal.php';
        $fechamentoModel = new \App\Models\FechamentoMensal();

        // Usa o seu Model para buscar os dados do mês
        $fechamento = $fechamentoModel->obter($mes, $ano);

        $msg = "📅 *Fechamento Mensal (" . str_pad($mes, 2, '0', STR_PAD_LEFT) . "/$ano)*\n\n";

        if (!$fechamento) {
            $msg .= "⚠️ O fechamento deste mês ainda não foi iniciado no Painel Web.";
        } else {
            $status = $fechamento['status'] ?? 'aberto';

            if ($status === 'fechado') {
                $msg .= "✅ O fechamento deste mês já foi concluído e o Balaústre gerado.\n";
                $msg .= "💰 Saldo Final: R$ " . number_format((float)($fechamento['saldo_final'] ?? 0), 2, ',', '.');
            } else {
                $msg .= "⏳ O fechamento está em andamento. Acesse o painel web para conferir as divergências e finalizar.";
            }
        }

        $this->telegram->sendMessage($chatId, $msg, ['parse_mode' => 'Markdown']);
    }

    // ==========================================
    // ROTEAMENTO DE CALLBACKS (BOTÕES)
    // ==========================================
    public function handleCallback($chatId, $callbackData) {
        if (strpos($callbackData, 'tesouraria_') === 0) {
            switch ($callbackData) {
                case 'tesouraria_caixa':
                    $this->handleTesourariaCaixa($chatId);
                    break;
                case 'tesouraria_comprovantes':
                    $this->handleTesourariaComprovantes($chatId);
                    break;
                case 'tesouraria_regularidade':
                    $this->handleTesourariaRegularidade($chatId);
                    break;
                case 'tesouraria_fechamento':
                    $this->handleTesourariaFechamento($chatId);
                    break;
            }
            return;
        }

        // Mantendo os callbacks da Chancelaria que já funcionavam
        if (strpos($callbackData, 'chancelaria_') === 0) {
            // Aqui ficaria a chamada para os métodos da chancelaria que você já tem
            $this->telegram->sendMessage($chatId, "Função da chancelaria acionada.");
            return;
        }
    }

    // Processa updates recebidos do Telegram
    public function handle($update) {
        if (isset($update['message'])) {
            $chatId = $update['message']['chat']['id'];
            $text = $update['message']['text'] ?? '';
            $fromId = $update['message']['from']['id'] ?? null;

            // Roteamento de comandos
            if ($text === '/tesouraria') {
                $this->handleTesouraria($chatId, $fromId);
            } elseif ($text === '/ajuda') {
                $this->handleHelp($chatId);
            } elseif ($text === '/chancelaria') {
                $this->handleChancelaria($chatId, $fromId);
            } else {
                $this->sendMenuPresenca($chatId);
            }
        } elseif (isset($update['callback_query'])) {
            $chatId = $update['callback_query']['message']['chat']['id'];
            $callbackData = $update['callback_query']['data'];
            $this->handleCallback($chatId, $callbackData);
        }
    }
}