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
                    ['text' => '📚 Biblioteca', 'callback_data' => 'admin_biblioteca']
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
        $fechamento = $fechamentoModel->obter($mes, $ano);

        if ($fechamento && isset($fechamento['status']) && $fechamento['status'] === 'fechado') {
            $msg = "🔒 *Fechamento Mensal*\n\nO mês de " . str_pad($mes, 2, '0', STR_PAD_LEFT) . "/{$ano} já está fechado. Nenhuma alteração pode ser feita.";
        } else {
            $msg = "🔓 *Fechamento Mensal*\n\nO mês de " . str_pad($mes, 2, '0', STR_PAD_LEFT) . "/{$ano} está aberto.\n\nAcesse o painel web para realizar o fechamento quando todas as contas estiverem conciliadas.";
        }

        $this->telegram->sendMessage($chatId, $msg);
    }

    public function handleCallback($chatId, $callbackData, $fromId = null) {
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

        if ($callbackData === 'admin_chancelaria') {
            $this->handleChancelaria($chatId, $fromId);
            return;
        } elseif ($callbackData === 'admin_tesouraria') {
            $this->handleTesouraria($chatId, $fromId);
            return;
        } elseif ($callbackData === 'admin_biblioteca') {
            $this->handleBiblioteca($chatId, $fromId);
            return;
        }

        if ($callbackData === 'secretaria_menu') {
            $mensagem = "🏛️ *Painel da Secretaria*\nSelecione a rotina desejada:";
            $teclado = [
                'inline_keyboard' => [
                    [
                        ['text' => '📅 Agendas e Sessões', 'callback_data' => 'sec_agendas'],
                        ['text' => '📝 Atas e Votações', 'callback_data' => 'sec_atas']
                    ],
                    [
                        ['text' => '📜 Certificados', 'callback_data' => 'sec_certificados'],
                        ['text' => '📐 Peças de Arquitetura', 'callback_data' => 'sec_trabalhos']
                    ],
                    [
                        ['text' => '🔙 Voltar ao Menu', 'callback_data' => 'start_menu']
                    ]
                ]
            ];
            $this->telegram->sendMessage($chatId, $mensagem, $teclado);
            return;
        }

        if ($callbackData === 'sec_agendas') {
            $this->handleSecAgendas($chatId);
            return;
        }

        if ($callbackData === 'start_menu') {
            $this->sendMenuPrincipal($chatId, $fromId);
            return;
        }

        if ($callbackData === 'chancelaria_neste_dia') {
            require_once __DIR__ . '/../Models/EfemerideRegistro.php';
            $efemerideModel = new \App\Models\EfemerideRegistro();
            $registros = $efemerideModel->getRegistrosDoDia(date('Y-m-d'));

            require_once __DIR__ . '/../Services/EfemeridesComposer.php';
            $composer = new \App\Services\EfemeridesComposer();
            $mensagem = $composer->composeDailyPreview($registros);

            $this->telegram->sendMessage($chatId, $mensagem, ['parse_mode' => 'HTML']);
            return;
        }

        if ($callbackData === 'chancelaria_aprovar_efemeride') {
            $this->handleAprovarEfemeride($chatId);
            return;
        }

        switch ($callbackData) {
            case 'chancelaria_aniversarios':
                $this->handleAniversarios($chatId);
                return;
            case 'chancelaria_datas':
                $this->handleDatasMaconicas($chatId);
                return;
            case 'chancelaria_historico':
                $this->handleFatosHistoricos($chatId);
                return;
        }

        if ($callbackData === 'biblioteca_acervo') {
            $this->handleBibliotecaAcervo($chatId);
            return;
        } elseif ($callbackData === 'biblioteca_meus_emprestimos') {
            $this->handleBibliotecaMeusEmprestimos($chatId, $fromId);
            return;
        }
    }

    public function handle($update) {
        if (isset($update['message'])) {
            $chatId = $update['message']['chat']['id'];
            $text = $update['message']['text'] ?? '';
            $fromId = $update['message']['from']['id'] ?? null;

            if ($text === '/painel') {
                $this->handlePainelAdmin($chatId, $fromId);
            } elseif ($text === '/tesouraria') {
                $this->handleTesouraria($chatId, $fromId);
            } elseif ($text === '/ajuda') {
                $this->handleHelp($chatId);
            } elseif ($text === '/chancelaria') {
                $this->handleChancelaria($chatId, $fromId);
            } elseif ($text === '/biblioteca') {
                $this->handleBiblioteca($chatId, $fromId);
            } else {
                $this->sendMenuPrincipal($chatId, $fromId);
            }
        } elseif (isset($update['callback_query'])) {
            $chatId = $update['callback_query']['message']['chat']['id'];
            $callbackData = $update['callback_query']['data'];
            $fromId = $update['callback_query']['from']['id'] ?? null;

            $this->handleCallback($chatId, $callbackData, $fromId);
        }
    }

    private function handleSecAgendas($chatId) {
        $sessao = method_exists($this->sessaoModel, 'obterProximaSessao') ? $this->sessaoModel->obterProximaSessao() : null;

        if ($sessao) {
            $data = date('d/m/Y H:i', strtotime($sessao['data']));
            $grau = htmlspecialchars($sessao['grau'] ?? 'Indefinido');
            $tipo = htmlspecialchars($sessao['tipo'] ?? 'Indefinido');
            $pauta = htmlspecialchars($sessao['pauta'] ?? 'Não definida');

            $msg = "📅 <b>Próxima Sessão</b>\n\n";
            $msg .= "<b>Data:</b> {$data}\n";
            $msg .= "<b>Grau:</b> {$grau}\n";
            $msg .= "<b>Tipo:</b> {$tipo}\n";
            $msg .= "<b>Pauta:</b> {$pauta}";

            $teclado = [
                'inline_keyboard' => [
                    [
                        ['text' => '📢 Publicar Edital no Grupo', 'callback_data' => 'sec_publicar_edital']
                    ],
                    [
                        ['text' => '✏️ Gerenciar Agendas', 'web_app' => ['url' => 'https://gestor-loja-web.onrender.com/secretaria/agendas']]
                    ],
                    [
                        ['text' => '🔙 Voltar', 'callback_data' => 'secretaria_menu']
                    ]
                ]
            ];
            $this->telegram->sendMessage($chatId, $msg, ['parse_mode' => 'HTML', 'reply_markup' => $teclado]);
        } else {
            $msg = "📅 Nenhuma sessão futura agendada no momento.";
            $teclado = [
                'inline_keyboard' => [
                    [
                        ['text' => '➕ Nova Sessão', 'web_app' => ['url' => 'https://gestor-loja-web.onrender.com/secretaria/agendas']]
                    ],
                    [
                        ['text' => '🔙 Voltar', 'callback_data' => 'secretaria_menu']
                    ]
                ]
            ];
            $this->telegram->sendMessage($chatId, $msg, ['parse_mode' => 'HTML', 'reply_markup' => $teclado]);
        }
    }

    public function handleBiblioteca($chatId, $fromId) {
        $mensagem = "📚 Bem-vindo à Biblioteca!\n\nEscolha uma opção:";
        $teclado = [
            'inline_keyboard' => [
                [
                    ['text' => '📚 Ver Acervo', 'callback_data' => 'biblioteca_acervo'],
                    ['text' => '📖 Meus Empréstimos', 'callback_data' => 'biblioteca_meus_emprestimos'],
                    [
                        'text' => '📷 Cadastrar Livro',
                        'web_app' => ['url' => 'https://gestor-loja-web.onrender.com/scanner.php']
                    ]
                ]
            ]
        ];
        $this->telegram->sendMessage($chatId, $mensagem, $teclado);
    }

    public function handleBibliotecaAcervo($chatId) {
        require_once __DIR__ . '/../Models/Acervo.php';
        $acervoModel = new \App\Models\Acervo();
        $itens = $acervoModel->listarTodos();

        if (empty($itens)) {
            $msg = "Nenhum item cadastrado no acervo no momento.";
        } else {
            $msg = "📚 <b>Acervo da Biblioteca</b>\n\n";

            foreach ($itens as $item) {
                $disponivel = ($item['quantidade_disponivel'] > 0) ? "🟢 Disponível" : "🔴 Indisponível";
                $grau = !empty($item['grau_recomendado']) ? $item['grau_recomendado'] : 'Livre';
                $linkWeb = "https://gestor-loja-web.onrender.com/biblioteca";

                $msg .= "📖 <b>{$item['titulo']}</b>\n";
                $msg .= "👤 Autor: {$item['autor']}\n";
                $msg .= "🎓 Grau: {$grau}\n";
                $msg .= "📊 Status: {$disponivel}\n";
                $msg .= "🔗 <a href='{$linkWeb}'>Acessar no Sistema</a>\n";
                $msg .= "──────────────\n";
            }
        }

        $this->telegram->sendMessage($chatId, $msg, [
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true
        ]);
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
}