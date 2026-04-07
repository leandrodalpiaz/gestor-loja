<?php

namespace App\Bot;

use App\Config\Env;

class CommandHandler
{
    private $telegram;
    private $obreiroModel;
    private $sessaoModel;
    private $presencaModel;

    private array $devIds = [8062119710];

    public function __construct($telegram, $obreiroModel, $sessaoModel, $presencaModel)
    {
        $this->telegram = $telegram;
        $this->obreiroModel = $obreiroModel;
        $this->sessaoModel = $sessaoModel;
        $this->presencaModel = $presencaModel;
    }

    private function getAppBaseUrl(): string
    {
        $base = trim((string) Env::get('APP_URL', ''));
        if ($base === '') {
            $base = 'http://localhost:8000';
        }

        return rtrim($base, '/');
    }

    private function buildAppUrl(string $path): string
    {
        $path = '/' . ltrim($path, '/');
        return $this->getAppBaseUrl() . $path;
    }

    private function getGroupChatId(): ?string
    {
        $candidates = [
            trim((string) Env::get('TELEGRAM_CHAT_ID_GROUP', '')),
            trim((string) Env::get('TELEGRAM_GRUPO_ID', '')),
            trim((string) Env::get('TELEGRAM_GROUP_ID', '')),
            trim((string) Env::get('TELEGRAM_CHAT_ID', '')),
        ];

        foreach ($candidates as $chatId) {
            if ($chatId !== '') {
                return $chatId;
            }
        }

        return null;
    }

    private function getObreiroRoles(?array $obreiro): array
    {
        if (!$obreiro) {
            return [];
        }

        if (!empty($obreiro['cargos']) && is_array($obreiro['cargos'])) {
            return array_values(array_unique(array_map(
                static fn ($role) => strtolower((string) $role),
                $obreiro['cargos']
            )));
        }

        $fallback = strtolower(trim((string) ($obreiro['cargo_principal'] ?? $obreiro['cargo'] ?? '')));
        return $fallback !== '' ? [$fallback] : [];
    }

    private function obreiroHasRole(?array $obreiro, string ...$roles): bool
    {
        $current = $this->getObreiroRoles($obreiro);
        foreach ($roles as $role) {
            if (in_array(strtolower($role), $current, true)) {
                return true;
            }
        }

        return false;
    }

    private function isDev(int $telegramId): bool
    {
        return in_array($telegramId, $this->devIds, true);
    }

    private function ensureChancelariaAccess($chatId, int $requesterTelegramId): bool
    {
        $obreiro = $this->obreiroModel->findByTelegramId($requesterTelegramId);
        if (!$this->isDev($requesterTelegramId) && (!$obreiro || !$this->obreiroHasRole($obreiro, 'chanceler', 'veneravel', 'admin'))) {
            $this->telegram->sendMessage($chatId, 'Acesso restrito ao Chanceler, Veneravel Mestre ou Administrador.');
            return false;
        }

        return true;
    }

    public function handlePainelAdmin($chatId, $requesterTelegramId)
    {
        $obreiro = $this->obreiroModel->findByTelegramId($requesterTelegramId);
        if (!$this->isDev($requesterTelegramId) && !$this->obreiroHasRole($obreiro, 'admin')) {
            $this->telegram->sendMessage($chatId, 'Acesso restrito aos Administradores do sistema.');
            return;
        }

        $mensagem = "*Painel do Administrador*\n\nSelecione o modulo que deseja acessar:";
        $teclado = [
            'inline_keyboard' => [
                [
                    ['text' => 'Chancelaria', 'callback_data' => 'admin_chancelaria'],
                    ['text' => 'Tesouraria', 'callback_data' => 'admin_tesouraria'],
                ],
                [
                    ['text' => 'Biblioteca', 'callback_data' => 'admin_biblioteca'],
                    ['text' => 'Secretaria', 'callback_data' => 'admin_secretaria'],
                ],
            ],
        ];

        $this->telegram->sendMessage($chatId, $mensagem, ['parse_mode' => 'Markdown', 'reply_markup' => $teclado]);
    }

    public function sendMenuPresenca($chatId)
    {
        $mensagem = "Bem-vindo ao assistente da Loja.";
        $teclado = [
            'inline_keyboard' => [
                [
                    ['text' => 'Confirmar Presenca', 'callback_data' => 'presenca_confirmar'],
                    ['text' => 'Informar Ausencia', 'callback_data' => 'presenca_ausencia'],
                ],
                [
                    ['text' => 'Ver Proxima Sessao', 'callback_data' => 'sessao_info'],
                ],
            ],
        ];

        $this->telegram->sendMessage($chatId, $mensagem, ['reply_markup' => $teclado]);
    }

    public function sendMenuPrincipal($chatId, $fromId)
    {
        $obreiro = $this->obreiroModel->findByTelegramId($fromId);
        $mensagem = "Bem-vindo ao painel da Loja, meu Irmao!";
        $teclado = ['inline_keyboard' => []];

        if ($this->obreiroHasRole($obreiro, 'admin', 'veneravel', 'secretario', 'chanceler', 'tesoureiro', 'bibliotecario', 'primeiro_vigilante', 'segundo_vigilante')) {
            $teclado['inline_keyboard'][] = [
                ['text' => 'Chancelaria', 'callback_data' => 'admin_chancelaria'],
                ['text' => 'Secretaria', 'callback_data' => 'secretaria_menu'],
            ];
            if ($this->obreiroHasRole($obreiro, 'admin', 'veneravel')) {
                $teclado['inline_keyboard'][] = [
                    ['text' => 'Painel do Veneravel Mestre', 'web_app' => ['url' => $this->buildAppUrl('/veneravel')]],
                ];
            }
            $teclado['inline_keyboard'][] = [
                ['text' => 'Tesouraria', 'callback_data' => 'tesouraria_menu'],
                ['text' => 'Biblioteca', 'callback_data' => 'biblioteca_menu'],
            ];
        } else {
            $teclado['inline_keyboard'][] = [
                ['text' => 'Confirmar Presenca', 'callback_data' => 'presenca_confirmar'],
                ['text' => 'Informar Ausencia', 'callback_data' => 'presenca_ausencia'],
            ];
            $teclado['inline_keyboard'][] = [
                ['text' => 'Ver Proxima Sessao', 'callback_data' => 'sessao_info'],
            ];
        }

        $this->telegram->sendMessage($chatId, $mensagem, ['reply_markup' => $teclado]);
    }

    public function handleHelp($chatId)
    {
        $mensagem = "<b>Ajuda do Gestor da Loja</b>\n\n";
        $mensagem .= "Comandos disponiveis:\n";
        $mensagem .= "/start - abre o menu principal\n";
        $mensagem .= "/chancelaria - painel da chancelaria\n";
        $mensagem .= "/tesouraria - painel da tesouraria\n";
        $mensagem .= "/biblioteca - painel da biblioteca\n";
        $mensagem .= "/painel - painel administrativo\n";

        $this->telegram->sendMessage($chatId, $mensagem, ['parse_mode' => 'HTML']);
    }

    public function handleChancelaria($chatId, $requesterTelegramId)
    {
        if (!$this->ensureChancelariaAccess($chatId, (int) $requesterTelegramId)) {
            return;
        }

        $mensagem = "*Painel da Chancelaria*\n\nSelecione uma opcao:";
        $teclado = [
            'inline_keyboard' => [
                [
                    ['text' => 'Emitir Certificado', 'web_app' => ['url' => $this->buildAppUrl('/chancelaria/certificado')]],
                ],
                [
                    ['text' => 'Certificado (Fallback)', 'url' => $this->buildAppUrl('/chancelaria/certificado')],
                ],
                [
                    ['text' => 'Neste Dia', 'callback_data' => 'chancelaria_neste_dia'],
                ],
                [
                    ['text' => 'Aniversarios Hoje', 'callback_data' => 'chancelaria_aniversarios'],
                    ['text' => 'Datas Maconicas', 'callback_data' => 'chancelaria_datas'],
                ],
                [
                    ['text' => 'Fatos Historicos', 'callback_data' => 'chancelaria_historico'],
                ],
                [
                    ['text' => 'Miniapp Historico', 'web_app' => ['url' => $this->buildAppUrl('/miniapp/historico')]],
                    ['text' => 'Miniapp Fallback', 'web_app' => ['url' => $this->buildAppUrl('/miniapp/fallback')]],
                ],
                [
                    ['text' => 'Miniapp Aniversario', 'web_app' => ['url' => $this->buildAppUrl('/miniapp/aniversario')]],
                    ['text' => 'Miniapp Data Maconica', 'web_app' => ['url' => $this->buildAppUrl('/miniapp/data-maconica')]],
                ],
            ],
        ];

        $this->telegram->sendMessage($chatId, $mensagem, ['parse_mode' => 'Markdown', 'reply_markup' => $teclado]);
    }

    public function handleTesouraria($chatId, $requesterTelegramId)
    {
        $obreiro = $this->obreiroModel->findByTelegramId($requesterTelegramId);
        if (!$this->isDev($requesterTelegramId) && (!$obreiro || !$this->obreiroHasRole($obreiro, 'tesoureiro', 'veneravel', 'admin'))) {
            $this->telegram->sendMessage($chatId, 'Acesso restrito ao Tesoureiro, Veneravel Mestre ou Administrador.');
            return;
        }

        $mensagem = "*Painel da Tesouraria*\n\nSelecione uma opcao:";
        $teclado = [
            'inline_keyboard' => [
                [
                    ['text' => 'Livro Caixa', 'callback_data' => 'tesouraria_caixa'],
                    ['text' => 'Comprovantes', 'callback_data' => 'tesouraria_comprovantes'],
                ],
                [
                    ['text' => 'Regularidade', 'callback_data' => 'tesouraria_regularidade'],
                    ['text' => 'Fechamento Mensal', 'callback_data' => 'tesouraria_fechamento'],
                ],
                [
                    ['text' => 'Validar Pix', 'callback_data' => 'tesouraria_validar_pix'],
                ],
                [
                    ['text' => 'Voltar', 'callback_data' => 'start_menu'],
                ],
            ],
        ];

        $this->telegram->sendMessage($chatId, $mensagem, ['parse_mode' => 'Markdown', 'reply_markup' => $teclado]);
    }

    public function handleBiblioteca($chatId, $requesterTelegramId)
    {
        $obreiro = $this->obreiroModel->findByTelegramId($requesterTelegramId);
        $isBibliotecario = $this->obreiroHasRole($obreiro, 'bibliotecario', 'admin', 'veneravel');
        $canClassificar = $this->obreiroHasRole($obreiro, 'primeiro_vigilante', 'segundo_vigilante', 'bibliotecario', 'admin', 'veneravel');
        $isDev = $this->isDev($requesterTelegramId);

        $mensagem = "<b>Biblioteca da Loja</b>\n\nSelecione uma opcao:";
        $botoes = [];

        $botoes[] = [
            ['text' => 'Meus Emprestimos', 'callback_data' => 'biblioteca_meus_emprestimos'],
            ['text' => 'Ver Acervo', 'callback_data' => 'biblioteca_acervo'],
        ];
        $botoes[] = [
            ['text' => 'Abrir Biblioteca Web', 'web_app' => ['url' => $this->buildAppUrl('/biblioteca')]],
        ];

        if ($isBibliotecario || $isDev) {
            $botoes[] = [
                ['text' => 'Cadastrar por ISBN', 'web_app' => ['url' => $this->buildAppUrl('/biblioteca/scanner')]],
                ['text' => 'Cadastrar Manual', 'web_app' => ['url' => $this->buildAppUrl('/biblioteca/novo')]],
            ];
            $botoes[] = [
                ['text' => 'Gerenciar Emprestimos', 'web_app' => ['url' => $this->buildAppUrl('/biblioteca/emprestimos')]],
            ];
        }
        if ($canClassificar || $isDev) {
            $botoes[] = [
                ['text' => 'Classificar Leituras', 'web_app' => ['url' => $this->buildAppUrl('/biblioteca')]],
            ];
        }

        $botoes[] = [
            ['text' => 'Voltar', 'callback_data' => 'start_menu'],
        ];

        $this->telegram->sendMessage($chatId, $mensagem, ['parse_mode' => 'HTML', 'reply_markup' => ['inline_keyboard' => $botoes]]);
    }

    public function handleBibliotecaMeusEmprestimos($chatId, $requesterTelegramId)
    {
        $obreiroModel = new \App\Models\Obreiro();
        $emprestimoModel = new \App\Models\Emprestimo();

        $obreiro = $obreiroModel->findByTelegramId($requesterTelegramId);
        if (!$obreiro) {
            $this->telegram->sendMessage($chatId, "Nao foi possivel identificar seu cadastro.");
            return;
        }

        $emprestimos = $emprestimoModel->listarPendentesPorObreiro($obreiro['id']);

        if (empty($emprestimos)) {
            $mensagem = "<b>Meus Emprestimos</b>\n\nVoce nao possui emprestimos ativos.";
        } else {
            $mensagem = "<b>Meus Emprestimos</b>\n\n";
            foreach ($emprestimos as $e) {
                $mensagem .= "- <b>" . htmlspecialchars($e['titulo']) . "</b> - Devolucao prevista: " . date('d/m/Y', strtotime($e['data_devolucao_prevista'])) . "\n";
            }
        }

        $this->telegram->sendMessage($chatId, $mensagem, [
            'parse_mode' => 'HTML',
            'reply_markup' => ['inline_keyboard' => [[['text' => 'Voltar', 'callback_data' => 'biblioteca_menu']]]],
        ]);
    }

    private function handleBibliotecaAcervo($chatId)
    {
        $acervoModel = new \App\Models\Acervo();
        $livros = $acervoModel->listarTodos();

        if (empty($livros)) {
            $mensagem = "<b>Acervo da Biblioteca</b>\n\nNenhum livro cadastrado.";
        } else {
            $mensagem = "<b>Acervo da Biblioteca</b>\n\n";
            foreach ($livros as $i => $livro) {
                $mensagem .= ($i + 1) . ". <b>" . htmlspecialchars($livro['titulo']) . "</b> - " . htmlspecialchars($livro['autor']);
                if (!empty($livro['grau_recomendado'])) {
                    $mensagem .= " (Grau: " . htmlspecialchars($livro['grau_recomendado']) . ")";
                }
                $mensagem .= "\n";
            }
        }

        $this->telegram->sendMessage($chatId, $mensagem, [
            'parse_mode' => 'HTML',
            'reply_markup' => ['inline_keyboard' => [[['text' => 'Voltar', 'callback_data' => 'biblioteca_menu']]]],
        ]);
    }

    private function handleBibliotecaCadastrar($chatId, $fromId = null)
    {
        $mensagem = "<b>Cadastrar Novo Livro</b>\n\nEscolha o metodo de cadastro:";
        $teclado = [
            'inline_keyboard' => [
                [
                    ['text' => 'Ler Codigo de Barras', 'web_app' => ['url' => $this->buildAppUrl('/biblioteca/scanner')]],
                ],
                [
                    ['text' => 'Preencher Manualmente', 'web_app' => ['url' => $this->buildAppUrl('/biblioteca/novo')]],
                ],
                [
                    ['text' => 'Voltar', 'callback_data' => 'biblioteca_menu'],
                ],
            ],
        ];

        $this->telegram->sendMessage($chatId, $mensagem, ['parse_mode' => 'HTML', 'reply_markup' => $teclado]);
    }

    private function handleBibliotecaGerenciar($chatId, $fromId = null)
    {
        $mensagem = "<b>Gerenciar Emprestimos</b>\n\nUse o painel web da biblioteca para aprovar devolucoes e acompanhar pendencias.";
        $this->telegram->sendMessage($chatId, $mensagem, [
            'parse_mode' => 'HTML',
            'reply_markup' => ['inline_keyboard' => [
                [['text' => 'Abrir painel', 'web_app' => ['url' => $this->buildAppUrl('/biblioteca/emprestimos')]],
                ['text' => 'Voltar', 'callback_data' => 'biblioteca_menu']]
            ]],
        ]);
    }

    private function handleAniversarios($chatId)
    {
        $obreiroModel = new \App\Models\Obreiro();
        $hoje = date('m-d');
        $aniversariantes = $obreiroModel->buscarPorAniversario($hoje);

        if (empty($aniversariantes)) {
            $msg = "Nao ha aniversariantes de vida hoje.";
        } else {
            $msg = "<b>Aniversariantes de Vida Hoje</b>\n\n";
            foreach ($aniversariantes as $o) {
                $msg .= "- {$o['nome']}\n";
            }
        }

        $this->telegram->sendMessage($chatId, $msg, ['parse_mode' => 'HTML']);
    }

    private function handleDatasMaconicas($chatId)
    {
        $obreiroModel = new \App\Models\Obreiro();
        $hoje = date('m-d');
        $maconicos = $obreiroModel->buscarPorDatasMaconicas($hoje);

        if (empty($maconicos)) {
            $msg = "Nao ha aniversarios maconicos hoje.";
        } else {
            $msg = "<b>Aniversarios Maconicos Hoje</b>\n\n";
            foreach ($maconicos as $o) {
                $msg .= "- {$o['nome']} ({$o['tipo']})\n";
            }
        }

        $this->telegram->sendMessage($chatId, $msg, ['parse_mode' => 'HTML']);
    }

    private function handleFatosHistoricos($chatId)
    {
        $efemerideModel = new \App\Models\EfemerideRegistro();
        $hoje = date('m-d');
        $fatos = $efemerideModel->buscarPorData($hoje);

        $fatosHistoricos = array_values(array_filter($fatos, static function (array $item): bool {
            $tipo = strtolower(trim((string) ($item['tipo'] ?? '')));
            return $tipo === 'historia' || $tipo === 'história' || $tipo === 'histÃ³ria';
        }));

        if (empty($fatosHistoricos)) {
            $msg = "Nao ha fatos historicos cadastrados para hoje.";
        } else {
            $msg = "<b>Fatos Historicos do Dia</b>\n\n";
            foreach ($fatosHistoricos as $f) {
                $texto = trim((string) ($f['mensagem_custom'] ?? ''));
                if ($texto === '') {
                    $texto = trim((string) ($f['nome'] ?? ''));
                }

                $dataEvento = '';
                if (!empty($f['data_evento'])) {
                    $timestamp = strtotime((string) $f['data_evento']);
                    $dataEvento = $timestamp ? date('d/m/Y', $timestamp) : (string) $f['data_evento'];
                }

                $linha = htmlspecialchars($texto !== '' ? $texto : 'Registro historico sem descricao.');
                if ($dataEvento !== '') {
                    $linha .= " ({$dataEvento})";
                }
                $msg .= "- {$linha}\n";
            }
        }

        $this->telegram->sendMessage($chatId, $msg, ['parse_mode' => 'HTML']);
    }

    private function handleNesteDia($chatId, int $requesterTelegramId)
    {
        if (!$this->ensureChancelariaAccess($chatId, $requesterTelegramId)) {
            return;
        }

        $composer = new \App\Services\EfemeridesComposer();
        $hoje = date('Y-m-d');
        $msg = $composer->gerarMensagemParaDia($hoje);

        $teclado = [
            'inline_keyboard' => [
                [
                    ['text' => 'Aprovar e Enviar p/ Grupo', 'callback_data' => 'chancelaria_aprovar_efemeride'],
                ],
                [
                    ['text' => 'Revisar Mensagem', 'web_app' => ['url' => $this->buildAppUrl('/chancelaria/efemerides?foco=mensagem')]],
                ],
                [
                    ['text' => 'Corrigir Dados', 'web_app' => ['url' => $this->buildAppUrl('/chancelaria/efemerides?foco=dados')]],
                ],
                [
                    ['text' => 'Voltar', 'callback_data' => 'admin_chancelaria'],
                ],
            ],
        ];

        $enviadoNoPrivado = $this->telegram->sendMessage(
            $requesterTelegramId,
            $msg,
            ['parse_mode' => 'HTML', 'reply_markup' => $teclado]
        );

        if ($enviadoNoPrivado) {
            $this->telegram->sendMessage(
                $chatId,
                "A previa de 'Neste Dia' foi enviada no seu privado para revisao.",
                ['parse_mode' => 'HTML']
            );
            return;
        }

        $this->telegram->sendMessage(
            $chatId,
            "Nao consegui entregar a previa no privado. Abra o chat com o bot e tente novamente.",
            ['parse_mode' => 'HTML']
        );
    }

    private function handleAprovarEfemeride($chatId, int $requesterTelegramId)
    {
        if (!$this->ensureChancelariaAccess($chatId, $requesterTelegramId)) {
            return;
        }

        $previaModel = new \App\Models\EfemeridePreviaDiaria();
        $hoje = date('Y-m-d');
        $previa = $previaModel->buscarPorData($hoje);

        $mensagem = trim((string) ($previa['mensagem'] ?? ''));
        if ($mensagem === '') {
            $composer = new \App\Services\EfemeridesComposer();
            $mensagem = trim($composer->gerarMensagemParaDia($hoje));
            if ($mensagem !== '') {
                $previaModel->salvarOuAtualizar($hoje, $mensagem, true);
            }
        }

        if ($mensagem !== '') {
            $grupoId = $this->getGroupChatId();
            if (!$grupoId) {
                $this->telegram->sendMessage($chatId, "Erro: o ID do grupo oficial nao esta configurado.", ['parse_mode' => 'HTML']);
                return;
            }

            $this->telegram->sendMessage($grupoId, $mensagem, ['parse_mode' => 'HTML']);
            $this->telegram->sendMessage($chatId, "Sucesso! A mensagem de efemerides foi enviada para o grupo oficial.", ['parse_mode' => 'HTML']);
            return;
        }

        $this->telegram->sendMessage($chatId, "Erro: nao foi possivel encontrar a mensagem de hoje para enviar.");
    }

    public function handle($update)
    {
        try {
            if (isset($update['message'])) {
                $message = $update['message'];
                $chatId = $message['chat']['id'];
                $text = $message['text'] ?? '';
                $fromId = $message['from']['id'];

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
                    $this->handleBiblioteca($chatId, $fromId);
                } else {
                    $this->telegram->sendMessage($chatId, "Comando nao reconhecido. Use /ajuda para ver as opcoes.");
                }
            } elseif (isset($update['callback_query'])) {
                $callback = $update['callback_query'];
                $chatId = $callback['message']['chat']['id'];
                $data = $callback['data'];
                $fromId = $callback['from']['id'];

                switch ($data) {
                    case 'admin_chancelaria':
                        $this->handleChancelaria($chatId, $fromId);
                        break;
                    case 'chancelaria_neste_dia':
                        $this->handleNesteDia($chatId, (int) $fromId);
                        break;
                    case 'chancelaria_aprovar_efemeride':
                        $this->handleAprovarEfemeride($chatId, (int) $fromId);
                        break;
                    case 'chancelaria_aniversarios':
                        if (!$this->ensureChancelariaAccess($chatId, (int) $fromId)) { break; }
                        $this->handleAniversarios($chatId);
                        break;
                    case 'chancelaria_datas':
                        if (!$this->ensureChancelariaAccess($chatId, (int) $fromId)) { break; }
                        $this->handleDatasMaconicas($chatId);
                        break;
                    case 'chancelaria_historico':
                        if (!$this->ensureChancelariaAccess($chatId, (int) $fromId)) { break; }
                        $this->handleFatosHistoricos($chatId);
                        break;

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
                        $this->handleBibliotecaCadastrar($chatId, $fromId);
                        break;
                    case 'biblioteca_gerenciar':
                        $this->handleBibliotecaGerenciar($chatId, $fromId);
                        break;

                    case 'admin_secretaria':
                    case 'secretaria_menu':
                        $this->handleSecretariaMenu($chatId, $fromId);
                        break;
                    case 'sec_agendas':
                        $this->handleSecAgendas($chatId);
                        break;

                    case 'presenca_confirmar':
                    case 'presenca_ausencia':
                    case 'sessao_info':
                        $this->sendMenuPresenca($chatId);
                        break;

                    case 'start_menu':
                        $this->sendMenuPrincipal($chatId, $fromId);
                        break;

                    default:
                        $this->telegram->sendMessage($chatId, "Acao nao reconhecida.");
                        break;
                }

                $this->telegram->answerCallbackQuery($callback['id']);
            } else {
                error_log('[handle] Update nao suportado: ' . json_encode($update));
            }

            error_log('[webhook] update processado com sucesso');
        } catch (\Throwable $e) {
            error_log('[webhook] erro ao processar update: ' . $e->getMessage());
        }
    }

    private function handleTesourariaCaixa($chatId)
    {
        $mes = date('n');
        $ano = date('Y');
        $lancModel = new \App\Models\LancamentoFinanceiro();
        $lancamentos = $lancModel->obterPorMes($mes, $ano);
        $totais = $lancModel->obterTotaisMes($mes, $ano);

        $msg = "<b>Livro Caixa - " . date('m/Y') . "</b>\n\n";
        $msg .= "<b>Entradas:</b> R$ " . number_format($totais['entrada'], 2, ',', '.') . "\n";
        $msg .= "<b>Saidas:</b> R$ " . number_format($totais['saida'], 2, ',', '.') . "\n";
        $msg .= "<b>Saldo:</b> R$ " . number_format($totais['entrada'] - $totais['saida'], 2, ',', '.') . "\n\n";

        if (empty($lancamentos)) {
            $msg .= "Nenhum lancamento registrado neste mes.";
        } else {
            foreach ($lancamentos as $l) {
                $tipo = $l['tipo'] === 'entrada' ? '+' : '-';
                $msg .= $tipo . " <b>" . $l['categoria_nome'] . "</b>: R$ " . number_format($l['valor'], 2, ',', '.') . " em " . date('d/m', strtotime($l['data_lancamento'])) . " - " . htmlspecialchars($l['descricao']) . "\n";
            }
        }

        $this->telegram->sendMessage($chatId, $msg, ['parse_mode' => 'HTML']);
    }

    private function handleTesourariaComprovantes($chatId)
    {
        $compModel = new \App\Models\ComprovantePix();
        $comprovantes = $compModel->obterTodos();

        if (empty($comprovantes)) {
            $msg = "Nenhum comprovante encontrado.";
        } else {
            $msg = "<b>Comprovantes Recebidos</b>\n\n";
            foreach ($comprovantes as $c) {
                $status = $c['status'] === 'aprovado' ? 'OK' : ($c['status'] === 'pendente' ? 'PENDENTE' : 'REJEITADO');
                $msg .= $status . " <b>" . htmlspecialchars($c['obreiro_nome']) . "</b> - R$ " . number_format($c['valor_informado'], 2, ',', '.') . " em " . date('d/m', strtotime($c['criado_em'])) . "\n";
            }
        }

        $this->telegram->sendMessage($chatId, $msg, ['parse_mode' => 'HTML']);
    }

    private function handleTesourariaRegularidade($chatId)
    {
        $mes = date('n');
        $ano = date('Y');
        $mensModel = new \App\Models\MensalidadeStatus();
        $inadimplentes = $mensModel->obterInadimplentes($mes, $ano);

        if (empty($inadimplentes)) {
            $msg = "Todos os obreiros estao regulares neste mes.";
        } else {
            $msg = "<b>Obreiros Inadimplentes - " . date('m/Y') . "</b>\n\n";
            foreach ($inadimplentes as $o) {
                $nome = htmlspecialchars($o['nome_historico'] ?: $o['nome']);
                $msg .= "- <b>$nome</b> - Status: " . htmlspecialchars($o['status'] ?? 'pendente') . "\n";
            }
        }

        $this->telegram->sendMessage($chatId, $msg, ['parse_mode' => 'HTML']);
    }

    private function handleTesourariaFechamento($chatId)
    {
        $mes = date('n');
        $ano = date('Y');
        $fechModel = new \App\Models\FechamentoMensal();
        $fechamento = $fechModel->obter($mes, $ano);

        if (!$fechamento) {
            $msg = "Nenhum fechamento registrado para este mes.";
        } else {
            $msg = "<b>Fechamento Mensal - " . date('m/Y') . "</b>\n\n";
            $msg .= "<b>Saldo Inicial:</b> R$ " . number_format($fechamento['saldo_inicial'], 2, ',', '.') . "\n";
            $msg .= "<b>Entradas:</b> R$ " . number_format($fechamento['total_entradas'], 2, ',', '.') . "\n";
            $msg .= "<b>Saidas:</b> R$ " . number_format($fechamento['total_saidas'], 2, ',', '.') . "\n";
            $msg .= "<b>Saldo Final:</b> R$ " . number_format($fechamento['saldo_final'], 2, ',', '.') . "\n";
        }

        $this->telegram->sendMessage($chatId, $msg, ['parse_mode' => 'HTML']);
    }

    private function handleTesourariaValidarPix($chatId)
    {
        $compModel = new \App\Models\ComprovantePix();
        $pendentes = $compModel->obterPendentes();

        if (empty($pendentes)) {
            $msg = "Nenhum comprovante Pix pendente de validacao.";
        } else {
            $msg = "<b>Comprovantes Pix Pendentes</b>\n\n";
            foreach ($pendentes as $c) {
                $msg .= "- <b>" . htmlspecialchars($c['obreiro_nome']) . "</b> - R$ " . number_format($c['valor_informado'], 2, ',', '.') . " em " . date('d/m', strtotime($c['criado_em'])) . "\n";
            }
        }

        $this->telegram->sendMessage($chatId, $msg, ['parse_mode' => 'HTML']);
    }

    public function handleSecretariaMenu($chatId, $fromId)
    {
        $obreiro = $this->obreiroModel->findByTelegramId($fromId);
        if (!$this->isDev($fromId) && (!$obreiro || !$this->obreiroHasRole($obreiro, 'secretario', 'admin', 'veneravel'))) {
            $this->telegram->sendMessage($chatId, 'Acesso restrito a Secretaria da Loja.');
            return;
        }

        $mensagem = "*Painel da Secretaria*\n\nSelecione uma opcao:";
        $botoes = [
            [
                ['text' => 'Painel Web da Secretaria', 'web_app' => ['url' => $this->buildAppUrl('/secretaria')]],
            ],
            [
                ['text' => 'Agendas e Sessoes', 'callback_data' => 'sec_agendas'],
            ],
        ];

        if ($this->isDev($fromId) || $this->obreiroHasRole($obreiro, 'admin', 'veneravel')) {
            $botoes[] = [
                ['text' => 'Painel do Veneravel Mestre', 'web_app' => ['url' => $this->buildAppUrl('/veneravel')]],
            ];
        }

        $botoes[] = [
            ['text' => 'Voltar', 'callback_data' => 'start_menu'],
        ];

        $teclado = [
            'inline_keyboard' => $botoes,
        ];

        $this->telegram->sendMessage($chatId, $mensagem, ['parse_mode' => 'Markdown', 'reply_markup' => $teclado]);
    }

    public function handleSecAgendas($chatId)
    {
        $this->telegram->sendMessage($chatId, "Use o painel web da Secretaria para operar sessoes, publicacoes e trabalhos da ordem do dia.");
    }
}
