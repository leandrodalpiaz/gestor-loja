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

    public function handlePainelAdmin($chatId, $requesterTelegramId) {
        if (!in_array($requesterTelegramId, $this->devIds)) {
            $this->telegram->sendMessage($chatId, '⛔ Acesso restrito aos Administradores do sistema.');
            return;
        }

        $mensagem = "👑 *Painel do Administrador*\n\nSelecione o módulo que deseja acessar para testes:";
        $teclado = [
            'inline_keyboard' => [
                [
                    ['text' => '🏛️ Chancelaria', 'callback_data' => 'admin_chancelaria'],
                    ['text' => '💰 Tesouraria', 'callback_data' => 'admin_tesouraria']
                ],
                [
                    ['text' => '📚 Biblioteca', 'callback_data' => 'admin_biblioteca'],
                    ['text' => '🏛️ Secretaria', 'callback_data' => 'admin_secretaria']
                ]
            ]
        ];
        $this->telegram->sendMessage($chatId, $mensagem, $teclado);
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

    public function sendMenuPrincipal($chatId, $fromId) {
        $obreiro = $this->obreiroModel->findByTelegramId($fromId);
        $cargo = strtolower(trim((string)($obreiro['cargo'] ?? 'comum')));

        $mensagem = "👋 Bem-vindo ao painel da Loja, meu Irmão!";
        $teclado = [
            'inline_keyboard' => []
        ];

        if (in_array($cargo, ['admin', 'veneravel', 'secretario'])) {
            $teclado['inline_keyboard'][] = [
                ['text' => '📅 Efemérides', 'callback_data' => 'chancelaria_neste_dia'],
                ['text' => '🏛️ Secretaria', 'callback_data' => 'secretaria_menu']
            ];
            $teclado['inline_keyboard'][] = [
                ['text' => '💰 Tesouraria', 'callback_data' => 'tesouraria_menu'],
                ['text' => '📚 Biblioteca', 'callback_data' => 'biblioteca_menu']
            ];
        } else {
            $teclado['inline_keyboard'][] = [
                ['text' => '✅ Confirmar Presença', 'callback_data' => 'presenca_confirmar'],
                ['text' => '❌ Informar Ausência', 'callback_data' => 'presenca_ausencia']
            ];
            $teclado['inline_keyboard'][] = [
                ['text' => '📜 Ver Próxima Sessão', 'callback_data' => 'sessao_info']
            ];
        }

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
        $this->telegram->sendMessage($chatId, $mensagem, ['parse_mode' => 'HTML']);
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
                    ['text' => '📜 Emitir Certificado', 'web_app' => ['url' => 'https://gestor-loja-web.onrender.com/chancelaria/certificado']]
                ],
                [
                    ['text' => '📅 Neste Dia', 'callback_data' => 'chancelaria_neste_dia']
                ],
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

    public function handleTesouraria($chatId, $requesterTelegramId) {
        $obreiro = $this->obreiroModel->findByTelegramId($requesterTelegramId);
        $cargo = strtolower(trim((string) ($obreiro['cargo'] ?? '')));
        if (!in_array($requesterTelegramId, $this->devIds) && (!$obreiro || $cargo !== 'tesoureiro')) {
            $this->telegram->sendMessage($chatId, '⛔ Acesso restrito ao Tesoureiro da Loja.');
            return;
        }
        $mensagem = "💰 *Painel da Tesouraria*\n\nSelecione uma opção:";
        $teclado = [
            'inline_keyboard' => [
                [
                    ['text' => '📒 Livro Caixa', 'callback_data' => 'tesouraria_caixa'],
                    ['text' => '📑 Comprovantes', 'callback_data' => 'tesouraria_comprovantes']
                ],
                [
                    ['text' => '🟢 Regularidade', 'callback_data' => 'tesouraria_regularidade'],
                    ['text' => '📆 Fechamento Mensal', 'callback_data' => 'tesouraria_fechamento']
                ],
                [
                    ['text' => '💸 Validar Pix', 'callback_data' => 'tesouraria_validar_pix']
                ],
                [
                    ['text' => '🔙 Voltar', 'callback_data' => 'start_menu']
                ]
            ]
        ];
        $this->telegram->sendMessage($chatId, $mensagem, $teclado);
    }

    public function handleBibliotecaMeusEmprestimos($chatId, $fromId) {
        require_once __DIR__ . '/../Models/Emprestimo.php';
        require_once __DIR__ . '/../Models/Obreiro.php';

        if (!$fromId) {
            $this->telegram->sendMessage($chatId, "Não foi possível identificar seu cadastro.");
            return;
        }
        $obreiroModel = new \App\Models\Obreiro();
        $obreiro = $obreiroModel->findByTelegramId($fromId);

        if (!$obreiro) {
            $this->telegram->sendMessage($chatId, "Não foi possível identificar seu cadastro.");
            return;
        }

        $emprestimoModel = new \App\Models\Emprestimo();
        $emprestimos = $emprestimoModel->listarPendentesPorObreiro($obreiro['id']);

        if (empty($emprestimos)) {
            $msg = "Você não possui empréstimos pendentes.";
        } else {
            $msg = "📖 <b>Seus Empréstimos Pendentes</b>\n\n";
            foreach ($emprestimos as $emp) {
                $msg .= "• <b>{$emp['titulo']}</b> (Devolver até: " . date('d/m/Y', strtotime($emp['data_devolucao'])) . ")\n";
            }
        }
        $this->telegram->sendMessage($chatId, $msg, ['parse_mode' => 'HTML']);
    }

    private function handleAniversarios($chatId) {
        require_once __DIR__ . '/../Models/Obreiro.php';
        $obreiroModel = new \App\Models\Obreiro();
        $hoje = date('m-d');
        $aniversariantes = $obreiroModel->buscarPorAniversario($hoje);

        if (empty($aniversariantes)) {
            $msg = "🎂 Não há aniversariantes de vida hoje.";
        } else {
            $msg = "🎂 <b>Aniversariantes de Vida Hoje</b>\n\n";
            foreach ($aniversariantes as $o) {
                $msg .= "• {$o['nome']} (" . date('d/m', strtotime('2000-' . $hoje)) . ")\n";
            }
        }
        $this->telegram->sendMessage($chatId, $msg, ['parse_mode' => 'HTML']);
    }

    private function handleDatasMaconicas($chatId) {
        require_once __DIR__ . '/../Models/Obreiro.php';
        $obreiroModel = new \App\Models\Obreiro();
        $hoje = date('m-d');
        $maconicos = $obreiroModel->buscarPorDatasMaconicas($hoje);

        if (empty($maconicos)) {
            $msg = "⚒️ Não há aniversários maçônicos hoje.";
        } else {
            $msg = "⚒️ <b>Aniversários Maçônicos Hoje</b>\n\n";
            foreach ($maconicos as $o) {
                $msg .= "• {$o['nome']} ({$o['tipo']})\n";
            }
        }
        $this->telegram->sendMessage($chatId, $msg, ['parse_mode' => 'HTML']);
    }

    private function handleFatosHistoricos($chatId) {
        require_once __DIR__ . '/../Models/EfemerideRegistro.php';
        $efemerideModel = new \App\Models\EfemerideRegistro();
        $hoje = date('m-d');
        $fatos = $efemerideModel->buscarPorData($hoje);

        if (empty($fatos)) {
            $msg = "📜 Não há fatos históricos cadastrados para hoje. Que tal registrar um novo?";
        } else {
            $msg = "📜 <b>Fatos Históricos do Dia</b>\n\n";
            foreach ($fatos as $f) {
                $msg .= "• {$f['descricao']} ({$f['ano']})\n";
            }
        }
        $this->telegram->sendMessage($chatId, $msg, ['parse_mode' => 'HTML']);
    }

    private function handleNesteDia($chatId) {
        require_once __DIR__ . '/../Models/EfemeridePreviaDiaria.php';
        $previaModel = new \App\Models\EfemeridePreviaDiaria();

        $hoje = date('Y-m-d');
        $previa = $previaModel->buscarPorData($hoje);

        if ($previa && !empty($previa['mensagem'])) {
            $msg = $previa['mensagem'];

            $teclado = [
                'inline_keyboard' => [
                    [
                        ['text' => '✅ Aprovar e Enviar p/ Grupo', 'callback_data' => 'chancelaria_aprovar_efemeride']
                    ],
                    [
                        ['text' => '✏️ Editar Texto', 'web_app' => ['url' => 'https://gestor-loja-web.onrender.com/chancelaria/efemerides']]
                    ],
                    [
                        ['text' => '🔙 Voltar', 'callback_data' => 'admin_chancelaria']
                    ]
                ]
            ];
            $this->telegram->sendMessage($chatId, $msg, ['parse_mode' => 'HTML', 'reply_markup' => $teclado]);
        } else {
            $msg = "📅 <b>Neste Dia</b>\n\nAinda não há uma prévia gerada para o dia de hoje.";
            $this->telegram->sendMessage($chatId, $msg, ['parse_mode' => 'HTML']);
        }
    }

    private function handleAprovarEfemeride($chatId) {
        require_once __DIR__ . '/../Models/EfemeridePreviaDiaria.php';
        $previaModel = new \App\Models\EfemeridePreviaDiaria();
        $hoje = date('Y-m-d');
        $previa = $previaModel->buscarPorData($hoje);

        if ($previa && !empty($previa['mensagem'])) {
            $grupoId = $_ENV['TELEGRAM_GROUP_ID'] ?? null;

            if (!$grupoId) {
                $this->telegram->sendMessage($chatId, "⚠️ <b>Erro:</b> O ID do grupo oficial não está configurado no arquivo .env (TELEGRAM_GROUP_ID).", ['parse_mode' => 'HTML']);
                return;
            }

            $this->telegram->sendMessage($grupoId, $previa['mensagem'], ['parse_mode' => 'HTML']);
            $this->telegram->sendMessage($chatId, "✅ <b>Sucesso!</b>\n\nA mensagem de Efemérides foi enviada para o grupo oficial da Loja.", ['parse_mode' => 'HTML']);
        } else {
            $this->telegram->sendMessage($chatId, "⚠️ Erro: Não foi possível encontrar a mensagem de hoje para enviar.");
        }
    }
    // Método central para processar updates do Telegram (restaurado consolidado)
    public function handle($update) {
        try {
            if (isset($update['message'])) {
                $message = $update['message'];
                $chatId = $message['chat']['id'];
                $text = $message['text'] ?? '';
                $fromId = $message['from']['id'];

                // Comandos principais
                if (strpos($text, '/start') === 0) {
                    $this->sendMenuPrincipal($chatId, $fromId);
                } elseif (strpos($text, '/painel') === 0) {
                    $this->handlePainelAdmin($chatId, $fromId);
                } elseif (strpos($text, '/ajuda') === 0 || strpos($text, '/help') === 0) {
                    $this->handleHelp($chatId);
                } elseif (strpos($text, '/chancelaria') === 0) {
                    $this->handleChancelaria($chatId, $fromId);
                } elseif (strpos($text, '/tesouraria') === 0) {
                    $this->handleTesouraria($chatId, $fromId);
                } elseif (strpos($text, '/biblioteca') === 0) {
                    $this->handleBibliotecaMeusEmprestimos($chatId, $fromId);
                } else {
                    $this->telegram->sendMessage($chatId, "Comando não reconhecido. Use /ajuda para ver opções.");
                }
            } elseif (isset($update['callback_query'])) {
                $callback = $update['callback_query'];
                $chatId = $callback['message']['chat']['id'];
                $data = $callback['data'];
                $fromId = $callback['from']['id'];

                switch ($data) {
                    // Chancelaria
                    case 'admin_chancelaria':
                        $this->handleChancelaria($chatId, $fromId);
                        break;
                    case 'chancelaria_neste_dia':
                        $this->handleNesteDia($chatId);
                        break;
                    case 'chancelaria_aprovar_efemeride':
                        $this->handleAprovarEfemeride($chatId);
                        break;
                    case 'chancelaria_aniversarios':
                        $this->handleAniversarios($chatId);
                        break;
                    case 'chancelaria_datas':
                        $this->handleDatasMaconicas($chatId);
                        break;
                    case 'chancelaria_historico':
                        $this->handleFatosHistoricos($chatId);
                        break;

                    // Tesouraria
                    case 'admin_tesouraria':
                    case 'tesouraria_menu':
                        $this->handleTesouraria($chatId, $fromId);
                        break;
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
                    case 'tesouraria_validar_pix':
                        $this->handleTesourariaValidarPix($chatId);
                        break;
    // Métodos consolidados da Tesouraria
    private function handleTesourariaCaixa($chatId) {
        $this->telegram->sendMessage($chatId, "📒 Função Livro Caixa em construção.");
    }

    private function handleTesourariaComprovantes($chatId) {
        $this->telegram->sendMessage($chatId, "📑 Função Comprovantes em construção.");
    }

    private function handleTesourariaRegularidade($chatId) {
        $this->telegram->sendMessage($chatId, "🟢 Função Regularidade em construção.");
    }

    private function handleTesourariaFechamento($chatId) {
        $this->telegram->sendMessage($chatId, "📆 Função Fechamento Mensal em construção.");
    }

    private function handleTesourariaValidarPix($chatId) {
        $this->telegram->sendMessage($chatId, "💸 Função Validar Pix em construção.");
    }

                    // Biblioteca
                    case 'admin_biblioteca':
                    case 'biblioteca_menu':
                        $this->handleBibliotecaMeusEmprestimos($chatId, $fromId);
                        break;

                    // Secretaria (NOVO)
                    case 'admin_secretaria':
                    case 'secretaria_menu':
                        $this->handleSecretariaMenu($chatId, $fromId);
                        break;
                    case 'sec_agendas':
                        $this->handleSecAgendas($chatId);
                        break;

                    // Presença
                    case 'presenca_confirmar':
                    case 'presenca_ausencia':
                    case 'sessao_info':
                        $this->sendMenuPresenca($chatId);
                        break;

                    // Voltar para o menu principal
                    case 'start_menu':
                        $this->sendMenuPrincipal($chatId, $fromId);
                        break;

                    default:
                        $this->telegram->sendMessage($chatId, "Ação não reconhecida.");
                        break;
                }
                $this->telegram->answerCallbackQuery($callback['id']);
            } else {
                error_log("[handle] Update não suportado: " . json_encode($update));
            }
            error_log("[webhook] update processado com sucesso");
        } catch (\Throwable $e) {
            error_log("[webhook] erro ao processar update: " . $e->getMessage());
        }
    }
    // Fluxo da Secretaria (NOVO)
    public function handleSecretariaMenu($chatId, $fromId) {
        $mensagem = "🏛️ *Painel da Secretaria*\n\nSelecione uma opção:";
        $teclado = [
            'inline_keyboard' => [
                [
                    ['text' => '📅 Agendas e Sessões', 'callback_data' => 'sec_agendas']
                ],
                [
                    ['text' => '🔙 Voltar', 'callback_data' => 'start_menu']
                ]
            ]
        ];
        $this->telegram->sendMessage($chatId, $mensagem, $teclado);
    }

    public function handleSecAgendas($chatId) {
        $this->telegram->sendMessage($chatId, "Agendas e Sessões em construção.");
    }
}