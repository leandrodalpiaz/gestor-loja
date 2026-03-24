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

    public function handleBiblioteca($chatId, $requesterTelegramId) {
        $obreiro = $this->obreiroModel->findByTelegramId($requesterTelegramId);
        $cargo = strtolower(trim((string)($obreiro['cargo'] ?? '')));
        $isBibliotecario = in_array($cargo, ['bibliotecario', 'admin', 'veneravel']);
        $isDev = in_array($requesterTelegramId, $this->devIds);

        $mensagem = "📚 <b>Biblioteca da Loja</b>\n\nSelecione uma opção:";
        $botoes = [
            [
                ['text' => '📖 Meus Empréstimos', 'callback_data' => 'biblioteca_meus_emprestimos'],
                ['text' => '🔍 Ver Acervo', 'callback_data' => 'biblioteca_acervo']
            ],
            // Estes só aparecem para admin/bibliotecário/dev
            ($isBibliotecario || $isDev) ? [
                ['text' => '➕ Cadastrar Livro', 'callback_data' => 'biblioteca_cadastrar'],
                ['text' => '📋 Gerenciar Empréstimos', 'callback_data' => 'biblioteca_gerenciar']
            ] : null,
            [
                ['text' => '🔙 Voltar', 'callback_data' => 'start_menu']
            ]
        ];
        // Remove linhas nulas (caso não seja admin/bibliotecário/dev)
        $botoes = array_values(array_filter($botoes));
        $teclado = ['inline_keyboard' => $botoes];
        $this->telegram->sendMessage($chatId, $mensagem, ['parse_mode' => 'HTML', 'reply_markup' => $teclado]);
    }

    public function handleBibliotecaMeusEmprestimos($chatId, $requesterTelegramId) {
        require_once __DIR__ . '/../Models/Emprestimo.php';
        $obreiro = $this->obreiroModel->findByTelegramId($requesterTelegramId);
        if (!$obreiro) {
            $this->telegram->sendMessage($chatId, "⚠️ Você não está cadastrado no sistema.");
            return;
        }
        $emprestimoModel = new \App\Models\Emprestimo();
        $emprestimos = $emprestimoModel->obterPorObreiro($obreiro['id']);
        if (empty($emprestimos)) {
            $msg = "📖 Você não possui empréstimos pendentes.";
        } else {
            $msg = "📖 <b>Seus Empréstimos</b>\n\n";
            foreach ($emprestimos as $emp) {
                $msg .= "• <b>" . htmlspecialchars($emp['titulo']) . "</b> (Devolver até: " . date('d/m/Y', strtotime($emp['data_devolucao'])) . ")\n";
            }
        }
        $this->telegram->sendMessage($chatId, $msg, ['parse_mode' => 'HTML']);
    }

    private function handleBibliotecaAcervo($chatId) {
        require_once __DIR__ . '/../Models/Acervo.php';
        $acervoModel = new \App\Models\Acervo();
        $livros = $acervoModel->listarDisponiveis();
        if (empty($livros)) {
            $msg = "📚 Nenhum livro disponível no acervo no momento.";
        } else {
            $msg = "📚 <b>Acervo Disponível</b>\n\n";
            foreach ($livros as $livro) {
                $msg .= "• <b>" . htmlspecialchars($livro['titulo']) . "</b> - " . htmlspecialchars($livro['autor']) . "\n";
                if (!empty($livro['grau_recomendado'])) {
                    $msg .= "  🎓 " . htmlspecialchars($livro['grau_recomendado']) . "\n";
                }
            }
        }
        $this->telegram->sendMessage($chatId, $msg, ['parse_mode' => 'HTML']);
    }

    private function handleBibliotecaCadastrar($chatId) {
        $url = 'https://gestor-loja-web.onrender.com/biblioteca/adicionar';
        $msg = "Para cadastrar um novo livro, utilize o painel web:\n<a href=\"$url\">Adicionar Livro</a>";
        $this->telegram->sendMessage($chatId, $msg, ['parse_mode' => 'HTML']);
    }

    private function handleBibliotecaGerenciar($chatId) {
        $url = 'https://gestor-loja-web.onrender.com/biblioteca/emprestimos';
        $msg = "Para gerenciar os empréstimos, utilize o painel web:\n<a href=\"$url\">Gerenciar Empréstimos</a>";
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

                    // Biblioteca
                    case 'admin_biblioteca':
                    case 'biblioteca_menu':
                        $this->handleBiblioteca($chatId, $fromId);
                        break;
                    case 'biblioteca_meus_emprestimos':
                        $this->handleBibliotecaMeusEmprestimos($chatId, $fromId);
                        break;
                    case 'biblioteca_acervo':
                        $this->handleBibliotecaAcervo($chatId);
                        break;
                    case 'biblioteca_cadastrar':
                        $this->handleBibliotecaCadastrar($chatId);
                        break;
                    case 'biblioteca_gerenciar':
                        $this->handleBibliotecaGerenciar($chatId);
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

    // Métodos consolidados da Tesouraria (corrigidos para fora do método handle)
    private function handleTesourariaCaixa($chatId) {
        require_once __DIR__ . '/../Models/LancamentoFinanceiro.php';
        $mes = date('n');
        $ano = date('Y');
        $lancModel = new \App\Models\LancamentoFinanceiro();
        $lancamentos = $lancModel->obterPorMes($mes, $ano);
        $totais = $lancModel->obterTotaisMes($mes, $ano);

        $msg = "📒 <b>Livro Caixa - " . date('m/Y') . "</b>\n\n";
        $msg .= "<b>Entradas:</b> R$ " . number_format($totais['entrada'], 2, ',', '.') . "\n";
        $msg .= "<b>Saídas:</b> R$ " . number_format($totais['saida'], 2, ',', '.') . "\n";
        $msg .= "<b>Saldo:</b> R$ " . number_format($totais['entrada'] - $totais['saida'], 2, ',', '.') . "\n\n";
        if (empty($lancamentos)) {
            $msg .= "Nenhum lançamento registrado neste mês.";
        } else {
            foreach ($lancamentos as $l) {
                $tipo = $l['tipo'] === 'entrada' ? '➕' : '➖';
                $msg .= $tipo . " <b>" . $l['categoria_nome'] . "</b>: R$ " . number_format($l['valor'], 2, ',', '.') . " em " . date('d/m', strtotime($l['data_lancamento'])) . " - " . htmlspecialchars($l['descricao']) . "\n";
            }
        }
        $this->telegram->sendMessage($chatId, $msg, ['parse_mode' => 'HTML']);
    }

    private function handleTesourariaComprovantes($chatId) {
        require_once __DIR__ . '/../Models/ComprovantePix.php';
        $compModel = new \App\Models\ComprovantePix();
        $comprovantes = $compModel->obterTodos();
        if (empty($comprovantes)) {
            $msg = "📑 Nenhum comprovante encontrado.";
        } else {
            $msg = "📑 <b>Comprovantes Recebidos</b>\n\n";
            foreach ($comprovantes as $c) {
                $status = $c['status'] === 'aprovado' ? '✅' : ($c['status'] === 'pendente' ? '🕒' : '❌');
                $msg .= $status . " <b>" . htmlspecialchars($c['obreiro_nome']) . "</b> - R$ " . number_format($c['valor_informado'], 2, ',', '.') . " em " . date('d/m', strtotime($c['criado_em'])) . "\n";
            }
        }
        $this->telegram->sendMessage($chatId, $msg, ['parse_mode' => 'HTML']);
    }

    private function handleTesourariaRegularidade($chatId) {
        require_once __DIR__ . '/../Models/MensalidadeStatus.php';
        $mes = date('n');
        $ano = date('Y');
        $mensModel = new \App\Models\MensalidadeStatus();
        $inadimplentes = $mensModel->obterInadimplentes($mes, $ano);
        if (empty($inadimplentes)) {
            $msg = "🟢 Todos os obreiros estão regulares neste mês.";
        } else {
            $msg = "🟢 <b>Obreiros Inadimplentes - " . date('m/Y') . "</b>\n\n";
            foreach ($inadimplentes as $o) {
                $nome = htmlspecialchars($o['nome_historico'] ?: $o['nome']);
                $msg .= "❗ <b>$nome</b> - Status: " . htmlspecialchars($o['status'] ?? 'pendente') . "\n";
            }
        }
        $this->telegram->sendMessage($chatId, $msg, ['parse_mode' => 'HTML']);
    }

    private function handleTesourariaFechamento($chatId) {
        require_once __DIR__ . '/../Models/FechamentoMensal.php';
        $mes = date('n');
        $ano = date('Y');
        $fechModel = new \App\Models\FechamentoMensal();
        $fechamento = $fechModel->obter($mes, $ano);
        if (!$fechamento) {
            $msg = "📆 Nenhum fechamento registrado para este mês.";
        } else {
            $msg = "📆 <b>Fechamento Mensal - " . date('m/Y') . "</b>\n\n";
            $msg .= "<b>Saldo Inicial:</b> R$ " . number_format($fechamento['saldo_inicial'], 2, ',', '.') . "\n";
            $msg .= "<b>Entradas:</b> R$ " . number_format($fechamento['total_entradas'], 2, ',', '.') . "\n";
            $msg .= "<b>Saídas:</b> R$ " . number_format($fechamento['total_saidas'], 2, ',', '.') . "\n";
            $msg .= "<b>Saldo Final:</b> R$ " . number_format($fechamento['saldo_final'], 2, ',', '.') . "\n";
        }
        $this->telegram->sendMessage($chatId, $msg, ['parse_mode' => 'HTML']);
    }

    private function handleTesourariaValidarPix($chatId) {
        require_once __DIR__ . '/../Models/ComprovantePix.php';
        $compModel = new \App\Models\ComprovantePix();
        $pendentes = $compModel->obterPendentes();
        if (empty($pendentes)) {
            $msg = "💸 Nenhum comprovante Pix pendente de validação.";
        } else {
            $msg = "💸 <b>Comprovantes Pix Pendentes</b>\n\n";
            foreach ($pendentes as $c) {
                $msg .= "🕒 <b>" . htmlspecialchars($c['obreiro_nome']) . "</b> - R$ " . number_format($c['valor_informado'], 2, ',', '.') . " em " . date('d/m', strtotime($c['criado_em'])) . "\n";
            }
        }
        $this->telegram->sendMessage($chatId, $msg, ['parse_mode' => 'HTML']);
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