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
        $mensagem .= "/tesouraria - Painel do Tesoureiro\n";
        $mensagem .= "/ajuda - Exibe esta mensagem de ajuda\n\n";
        $mensagem .= "Para outras dúvidas, contate a Secretaria da Loja.";
        $this->telegram->sendMessage($chatId, $mensagem);
    }

    // IDs de desenvolvedor com acesso total
    private $devIds = [8062119710]; // Seu Telegram ID para acesso total

    public function handleChancelaria($chatId, $requesterTelegramId) {
        $obreiro = $this->obreiroModel->findByTelegramId($requesterTelegramId);
        $cargo = strtolower(trim((string) ($obreiro['cargo'] ?? '')));
        if (!in_array($requesterTelegramId, $this->devIds) && (!$obreiro || $cargo !== 'chanceler')) {
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

    // Painel Tesouraria
    public function handleTesouraria($chatId, $requesterTelegramId) {
        $obreiro = $this->obreiroModel->findByTelegramId($requesterTelegramId);
        $cargo = strtolower(trim((string) ($obreiro['cargo'] ?? '')));
        if (!in_array($requesterTelegramId, $this->devIds) && (!$obreiro || $cargo !== 'tesoureiro')) {
            $this->telegram->sendMessage($chatId, '⛔ Acesso restrito ao Tesoureiro.');
            return;
        }
        $this->telegram->sendMessage(
            $chatId,
            "💰 <b>Painel Tesouraria</b>\nEscolha uma opção:",
            [
                'inline_keyboard' => [
                    [['text' => '📊 Livro-Caixa', 'callback_data' => 'tesouraria_caixa']],
                    [['text' => '🧾 Comprovantes', 'callback_data' => 'tesouraria_comprovantes']],
                    [['text' => '✅ Regularidade', 'callback_data' => 'tesouraria_regularidade']],
                    [['text' => '📅 Fechamento Mensal', 'callback_data' => 'tesouraria_fechamento']],
                ],
            ]
        );
    }

    // Roteamento de callbacks
    public function handleCallback($chatId, $callbackData) {
        switch ($callbackData) {
            // Tesouraria
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
            // Chancelaria
            case 'menu_hoje':
                $this->handleMenuHoje($chatId);
                break;
            case 'menu_aniversarios':
                $this->handleMenuAniversarios($chatId);
                break;
            case 'menu_datas_maconicas':
                $this->handleMenuDatasMaconicas($chatId);
                break;
            case 'menu_historico':
                $this->handleMenuHistorico($chatId);
                break;
            case 'menu_fallback':
                $this->handleMenuFallback($chatId);
                break;
            default:
                $this->telegram->sendMessage($chatId, '❓ Opção inválida ou não implementada.');
        }
    }

    // Consulta Livro-Caixa (Acesso Direto ao Model)
    public function handleTesourariaCaixa($chatId) {
        require_once __DIR__ . '/../Models/LancamentoFinanceiro.php';
        $model = new \App\Models\LancamentoFinanceiro();
        $mes = date('n');
        $ano = date('Y');

        try {
            // Tenta buscar os lançamentos. Se o nome do método no seu Model for diferente, ajuste aqui.
            $lancamentos = method_exists($model, 'listarPorMes') ? $model->listarPorMes($mes, $ano) : $model->listar(); 

            $mensagem = "📊 <b>Livro-Caixa (" . str_pad($mes, 2, '0', STR_PAD_LEFT) . "/$ano)</b>\n\n";
            $saldo = 0;

            if (empty($lancamentos)) {
                $mensagem .= "Nenhum lançamento encontrado neste mês.";
            } else {
                foreach ($lancamentos as $lanc) {
                    $valor = number_format($lanc['valor'] ?? 0, 2, ',', '.');
                    $tipo = (isset($lanc['tipo']) && $lanc['tipo'] == 'saida') ? '🔴' : '🟢';
                    $cat = $lanc['categoria_nome'] ?? 'Sem categoria';
                    $mensagem .= "{$tipo} {$lanc['data_lancamento']} - R$ {$valor} ({$cat})\n";
                    $saldo += ($tipo == '🔴') ? -$lanc['valor'] : $lanc['valor'];
                }
            }

            $mensagem .= "\n💰 Saldo Calculado: <b>R$ " . number_format($saldo, 2, ',', '.') . "</b>";
            $this->telegram->sendMessage($chatId, $mensagem);

        } catch (\Exception $e) {
            $this->telegram->sendMessage($chatId, '❌ Erro interno ao consultar o livro-caixa. Verifique o Model.');
        }
    }

    // Consulta Comprovantes (Acesso Direto ao Model)
    public function handleTesourariaComprovantes($chatId) {
        require_once __DIR__ . '/../Models/ComprovantePix.php';
        $model = new \App\Models\ComprovantePix();

        try {
            // Tenta buscar comprovantes pendentes. Ajuste o método se necessário.
            $comprovantes = method_exists($model, 'listarPendentes') ? $model->listarPendentes() : $model->listar();

            $mensagem = "🧾 <b>Comprovantes PIX Pendentes</b>\n\n";
            $encontrou = false;

            if (!empty($comprovantes)) {
                foreach ($comprovantes as $comp) {
                    if (($comp['status'] ?? '') === 'pendente') {
                        $valor = number_format($comp['valor'] ?? 0, 2, ',', '.');
                        $nome = $comp['nome_obreiro'] ?? 'Desconhecido';
                        $mensagem .= "⏳ {$nome} - R$ {$valor}\n";
                        $encontrou = true;
                    }
                }
            }

            if (!$encontrou) {
                $mensagem .= "✅ Nenhum comprovante pendente de validação.";
            } else {
                $mensagem .= "\n👉 Acesse o Painel Web para aprovar/rejeitar.";
            }

            $this->telegram->sendMessage($chatId, $mensagem);

        } catch (\Exception $e) {
            $this->telegram->sendMessage($chatId, '❌ Erro interno ao consultar comprovantes. Verifique o Model.');
        }
    }

    // Consulta Regularidade (Acesso Direto ao Model)
    public function handleTesourariaRegularidade($chatId) {
        require_once __DIR__ . '/../Models/RegularidadeObreiro.php';
        $model = new \App\Models\RegularidadeObreiro();
        $mes = date('n');
        $ano = date('Y');

        try {
            $regularidades = method_exists($model, 'listarPorMes') ? $model->listarPorMes($mes, $ano) : $model->listar();

            $mensagem = "✅ <b>Regularidade dos Obreiros (" . str_pad($mes, 2, '0', STR_PAD_LEFT) . "/$ano)</b>\n\n";

            if (empty($regularidades)) {
                $mensagem .= "Nenhum dado de regularidade encontrado para este mês.";
            } else {
                foreach ($regularidades as $reg) {
                    $statusStr = strtolower($reg['status'] ?? '');
                    $statusIcon = ($statusStr == 'regular' || $statusStr == 'pago') ? '🟢' : '🔴';
                    $nome = $reg['nome_obreiro'] ?? $reg['nome'] ?? 'Desconhecido';
                    $mensagem .= "{$statusIcon} {$nome}\n";
                }
            }

            $this->telegram->sendMessage($chatId, $mensagem);

        } catch (\Exception $e) {
            $this->telegram->sendMessage($chatId, '❌ Erro interno ao consultar regularidade. Verifique o Model.');
        }
    }

    // Consulta Fechamento Mensal (Acesso Direto ao Model)
    public function handleTesourariaFechamento($chatId) {
        require_once __DIR__ . '/../Models/FechamentoMensal.php';
        $model = new \App\Models\FechamentoMensal();
        $mes = date('n');
        $ano = date('Y');

        try {
            $fechamento = method_exists($model, 'obterPorMes') ? $model->obterPorMes($mes, $ano) : null;

            $mensagem = "📅 <b>Fechamento Mensal (" . str_pad($mes, 2, '0', STR_PAD_LEFT) . "/$ano)</b>\n\n";

            if (!$fechamento) {
                $mensagem .= "⚠️ O fechamento deste mês ainda não foi gerado ou iniciado no Painel Web.";
            } else {
                $saldoInicial = number_format($fechamento['saldo_inicial'] ?? 0, 2, ',', '.');
                $saldoFinal = number_format($fechamento['saldo_final'] ?? 0, 2, ',', '.');
                $status = ucfirst($fechamento['status'] ?? 'Aberto');

                $mensagem .= "Status: <b>{$status}</b>\n";
                $mensagem .= "Saldo Inicial: R$ {$saldoInicial}\n";
                $mensagem .= "Saldo Final: R$ {$saldoFinal}\n";
            }

            $this->telegram->sendMessage($chatId, $mensagem);

        } catch (\Exception $e) {
            $this->telegram->sendMessage($chatId, '❌ Erro interno ao consultar fechamento. Verifique o Model.');
        }
    }

    // Métodos do Chanceler (Mantidos intactos)
    public function handleMenuHoje($chatId) {
        require_once __DIR__ . '/../Models/EfemerideRegistro.php';
        require_once __DIR__ . '/../Services/EfemeridesComposer.php';
        $registroModel = new \App\Models\EfemerideRegistro();
        $composer = new \App\Services\EfemeridesComposer();
        $registrosHoje = $registroModel->getRegistrosDoDia();
        $mensagem = $composer->composeDailyPreview($registrosHoje);
        $this->telegram->sendMessage($chatId, $mensagem);
    }

    public function handleMenuAniversarios($chatId) {
        require_once __DIR__ . '/../Models/EfemerideRegistro.php';
        require_once __DIR__ . '/../Services/EfemeridesComposer.php';
        $registroModel = new \App\Models\EfemerideRegistro();
        $composer = new \App\Services\EfemeridesComposer();
        $registrosHoje = $registroModel->getRegistrosDoDia();
        $aniversarios = array_filter($registrosHoje, function($r) {
            return isset($r['tipo']) && strtolower($r['tipo']) === 'aniversário';
        });
        if (empty($aniversarios)) {
            $this->telegram->sendMessage($chatId, '🎂 Nenhum aniversário hoje.');
            return;
        }
        $mensagens = $composer->composeDailyPreview($aniversarios);
        $this->telegram->sendMessage($chatId, $mensagens);
    }

    public function handleMenuDatasMaconicas($chatId) {
        require_once __DIR__ . '/../Models/EfemerideRegistro.php';
        require_once __DIR__ . '/../Services/EfemeridesComposer.php';
        $registroModel = new \App\Models\EfemerideRegistro();
        $registrosHoje = $registroModel->getRegistrosDoDia();
        $datas = array_filter($registrosHoje, function($r) {
            return isset($r['tipo']) && in_array(strtolower($r['tipo']), ['iniciação','elevação','exaltação','instalação']);
        });
        if (empty($datas)) {
            $this->telegram->sendMessage($chatId, '⚒️ Nenhuma data maçônica hoje.');
            return;
        }
        $composer = new \App\Services\EfemeridesComposer();
        $mensagens = $composer->composeDailyPreview($datas);
        $this->telegram->sendMessage($chatId, $mensagens);
    }

    public function handleMenuHistorico($chatId) {
        require_once __DIR__ . '/../Models/EfemerideRegistro.php';
        require_once __DIR__ . '/../Services/EfemeridesComposer.php';
        $registroModel = new \App\Models\EfemerideRegistro();
        $registrosHoje = $registroModel->getRegistrosDoDia();
        $historicos = array_filter($registrosHoje, function($r) {
            return isset($r['tipo']) && strtolower($r['tipo']) === 'história';
        });
        if (empty($historicos)) {
            $this->telegram->sendMessage($chatId, '📜 Nenhum evento histórico hoje.');
            return;
        }
        $composer = new \App\Services\EfemeridesComposer();
        $mensagens = $composer->composeDailyPreview($historicos);
        $this->telegram->sendMessage($chatId, $mensagens);
    }

    public function handleMenuFallback($chatId) {
        require_once __DIR__ . '/../Models/MensagemComplementar.php';
        $comp = new \App\Models\MensagemComplementar();
        $mensagem = $comp->sortear('fallback');
        $this->telegram->sendMessage($chatId, "💬 Mensagem de reflexão:\n" . $mensagem);
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