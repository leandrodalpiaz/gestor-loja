<?php
if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Gestor de Loja</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        cobalto: '#0f2747',
                        ouro: '#c8a646',
                        pergaminho: '#f6f1e7',
                        marfim: '#fcfbf7',
                        ardosia: '#475569'
                    },
                    fontFamily: {
                        serif: ['"Playfair Display"', 'Georgia', 'serif'],
                        sans: ['"Inter"', 'system-ui', 'sans-serif'],
                    },
                    boxShadow: {
                        painel: '0 18px 50px rgba(15,39,71,0.08)'
                    }
                }
            }
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
</head>
<body class="min-h-screen bg-[linear-gradient(180deg,#f8f4ea_0%,#f3f4f6_45%,#eef2f7_100%)] font-sans text-slate-800" x-data="{ menuOpen: false }">
<?php
$usuarioNome = $_SESSION['usuario_nome'] ?? 'Irmão';
$usuarioCargo = (string) ($_SESSION['usuario_cargo'] ?? '');
$usuarioCargos = $_SESSION['usuario_cargos'] ?? [$usuarioCargo];
$isTestSession = isset($_SESSION['usuario_id']) && (string) $_SESSION['usuario_id'] === '0';
$allowAllPanels = filter_var($_ENV['APP_TEST_ALLOW_ALL_PANELS'] ?? 'true', FILTER_VALIDATE_BOOL);
$showAllPanels = filter_var($_ENV['APP_TEST_OPEN_ACCESS'] ?? 'false', FILTER_VALIDATE_BOOL) || $isTestSession || $allowAllPanels;

$isAdmin = in_array('admin', $usuarioCargos, true);
$isChanceler = in_array('chanceler', $usuarioCargos, true);
$isMestreBanquetes = in_array('mestre_banquetes', $usuarioCargos, true);
$isTesoureiro = in_array('tesoureiro', $usuarioCargos, true);
$isPrimeiroVigilante = in_array('primeiro_vigilante', $usuarioCargos, true);
$isSegundoVigilante = in_array('segundo_vigilante', $usuarioCargos, true);
$isSecretario = in_array('secretario', $usuarioCargos, true);
$isOrador = in_array('orador', $usuarioCargos, true);
$isVeneravel = in_array('veneravel', $usuarioCargos, true);
$isBibliotecario = in_array('bibliotecario', $usuarioCargos, true);
$isHospitaleiro = in_array('hospitaleiro', $usuarioCargos, true);
$isMestreHarmonia = in_array('mestre_de_harmonia', $usuarioCargos, true);
$adminLivre = $isAdmin || $showAllPanels;
$dashboardMensagemSucesso = $dashboardMensagemSucesso ?? null;
$dashboardMensagemErro = $dashboardMensagemErro ?? null;
$dashboardConfiguracaoLoja = is_array($dashboardConfiguracaoLoja ?? null) ? $dashboardConfiguracaoLoja : [];
$dashboardLogoUrl = $dashboardLogoUrl ?? null;
$dashboardSessoes = is_array($dashboardSessoes ?? null) ? $dashboardSessoes : [];
$dashboardRecados = is_array($dashboardRecados ?? null) ? $dashboardRecados : [];
$dashboardPalavraIrmao = trim((string) ($dashboardPalavraIrmao ?? ''));
$dashboardOutrasLojas = is_array($dashboardOutrasLojas ?? null) ? $dashboardOutrasLojas : [];

$dashboardNomeLoja = trim((string) ($dashboardConfiguracaoLoja['nome_loja'] ?? 'Loja Maçonica Renascença'));
$dashboardNumeroLoja = trim((string) ($dashboardConfiguracaoLoja['numero_loja'] ?? ''));
if ($dashboardNumeroLoja !== '') {
    $dashboardNomeLoja .= ' nº ' . $dashboardNumeroLoja;
}
$dashboardDiaReuniao = trim((string) ($dashboardConfiguracaoLoja['dia_semana_reuniao'] ?? ''));
$dashboardHorarioReuniao = trim((string) ($dashboardConfiguracaoLoja['horario_reuniao'] ?? ''));
$dashboardRecadoPrincipal = $dashboardRecados[0] ?? null;
$dashboardRecadosSecundarios = array_slice($dashboardRecados, 1, 2);

$formatarDataHoraDashboard = static function (?string $valor): string {
    $valor = trim((string) $valor);
    if ($valor === '') {
        return 'Data a definir';
    }

    try {
        return (new DateTimeImmutable($valor))
            ->setTimezone(new DateTimeZone('America/Sao_Paulo'))
            ->format('d/m/Y \à\s H:i');
    } catch (\Throwable $e) {
        return $valor;
    }
};

$dashboardResumirTexto = static function (?string $texto, int $limite = 180): string {
    $texto = trim(strip_tags((string) $texto));
    if ($texto === '') {
        return '';
    }

    if (function_exists('mb_strimwidth')) {
        return mb_strimwidth($texto, 0, $limite, '...');
    }

    return strlen($texto) > $limite ? substr($texto, 0, $limite - 3) . '...' : $texto;
};

$dashboardStatusClasses = static function (?string $status): string {
    return match (strtolower(trim((string) $status))) {
        'publicada', 'confirmada', 'confirmado', 'ativa', 'agendada', 'programada' => 'border-emerald-200 bg-emerald-50 text-emerald-800',
        'cancelada', 'cancelado', 'encerrada' => 'border-rose-200 bg-rose-50 text-rose-800',
        'rascunho', 'pendente' => 'border-amber-200 bg-amber-50 text-amber-800',
        default => 'border-slate-200 bg-slate-100 text-slate-700',
    };
};

$secaoGeral = [
    ['label' => 'Dashboard', 'href' => '/dashboard'],
];

$secoes = [];
$atalhosPrioritarios = [];
$blocosPrioritarios = [];

if ($isChanceler || $isVeneravel || $adminLivre) {
    $secoes[] = [
        'titulo' => 'Chancelaria',
        'descricao' => 'Mensagens do dia, certificados e manutenção de efemérides.',
        'itens' => [
            ['label' => 'Revisar mensagem do dia', 'href' => '/chancelaria/efemerides?foco=mensagem'],
            ['label' => 'Corrigir dados das efemérides', 'href' => '/chancelaria/efemerides?foco=dados'],
            ['label' => 'Visão completa da Chancelaria', 'href' => '/chancelaria/efemerides'],
            ['label' => 'Emitir certificado', 'href' => '/chancelaria/certificado'],
            ['label' => 'Sessao e check-in do Chanceler', 'href' => '/chanceler/sessao'],
        ],
    ];

    if ($isChanceler) {
        $atalhosPrioritarios = array_merge($atalhosPrioritarios, [
            ['label' => 'Mensagem do dia', 'href' => '/chancelaria/efemerides?foco=mensagem'],
            ['label' => 'Certificado', 'href' => '/chancelaria/certificado'],
            ['label' => 'Sessao do Chanceler', 'href' => '/chanceler/sessao'],
        ]);

        $blocosPrioritarios[] = [
            'perfil' => 'Chancelaria',
            'titulo' => 'Prioridades do Chanceler',
            'descricao' => 'Acesso direto ao que mais pesa no uso diario: efemerides, certificado e apoio de sessao.',
            'principal' => ['label' => 'Revisar mensagem do dia', 'href' => '/chancelaria/efemerides?foco=mensagem'],
            'secundarios' => [
                ['label' => 'Corrigir dados das efemerides', 'href' => '/chancelaria/efemerides?foco=dados'],
                ['label' => 'Emitir certificado', 'href' => '/chancelaria/certificado'],
                ['label' => 'Sessao e check-in', 'href' => '/chanceler/sessao'],
            ],
        ];
    }
}

if ($isSecretario || $isVeneravel || $adminLivre) {
    $secoes[] = [
        'titulo' => 'Secretaria',
        'descricao' => 'Sessões, votações e acompanhamento administrativo.',
        'itens' => [
            ['label' => 'Painel da Secretaria', 'href' => '/secretaria'],
            ['label' => 'Votação de balaustre', 'href' => '/secretaria/votacao'],
        ],
    ];
}

if ($isOrador || $isVeneravel || $adminLivre) {
    $secoes[] = [
        'titulo' => 'Orador',
        'descricao' => 'Leitura resumida da sessao, apoio ritual e nominata resumida de visitantes para agradecimento em Loja.',
        'itens' => [
            ['label' => 'Painel do Orador', 'href' => '/orador'],
        ],
    ];
}

if ($isMestreBanquetes || $isVeneravel || $adminLivre) {
    $secoes[] = [
        'titulo' => 'Mestre de Banquetes',
        'descricao' => 'Leitura operacional dos confirmados com e sem agape para planejamento do banquete.',
        'itens' => [
            ['label' => 'Painel do Mestre de Banquetes', 'href' => '/mestre-banquetes'],
        ],
    ];
}

if ($isHospitaleiro || $isVeneravel || $isTesoureiro || $adminLivre) {
    $secoes[] = [
        'titulo' => 'Hospitalaria',
        'descricao' => 'Ocorrencias assistenciais, acompanhamento e encaminhamentos ao Veneravel e Tesouraria.',
        'itens' => [
            ['label' => 'Painel de Assistencia', 'href' => '/assistencia'],
        ],
    ];
}

$secoes[] = [
    'titulo' => 'Mestre de Harmonia',
    'descricao' => 'Player ritual em tela cheia, com etapas principais, transicoes e extras por sessao.',
    'itens' => [
        ['label' => 'Painel do Mestre de Harmonia', 'href' => '/mestre-harmonia'],
    ],
];

if ($isPrimeiroVigilante || $isVeneravel || $adminLivre) {
    $secoes[] = [
        'titulo' => '1º Vigilante',
        'descricao' => 'Acompanhamento formativo dos Aprendizes, trilha de estudos e orientação de instruções.',
        'itens' => [
            ['label' => 'Painel do 1º Vigilante', 'href' => '/primeiro-vigilante'],
            ['label' => 'Lista de obreiros', 'href' => '/obreiros'],
            ['label' => 'Biblioteca e classificação', 'href' => '/biblioteca'],
        ],
    ];
}

if ($isSegundoVigilante || $isVeneravel || $adminLivre) {
    $secoes[] = [
        'titulo' => '2º Vigilante',
        'descricao' => 'Acompanhamento formativo dos Companheiros, trilha de estudos e orientação de instruções.',
        'itens' => [
            ['label' => 'Painel do 2º Vigilante', 'href' => '/segundo-vigilante'],
            ['label' => 'Lista de obreiros', 'href' => '/obreiros'],
            ['label' => 'Biblioteca e classificação', 'href' => '/biblioteca'],
        ],
    ];
}

if ($isVeneravel || $adminLivre) {
    $secoes[] = [
        'titulo' => 'Venerável Mestre',
        'descricao' => 'Decisões de votação, acompanhamento de sessão e nominata oficial.',
        'itens' => [
            ['label' => 'Dashboard do Venerável', 'href' => '/veneravel'],
            ['label' => 'Abrir/encerrar votações', 'href' => '/secretaria/votacao'],
        ],
    ];
}

if ($isTesoureiro || $isVeneravel || $adminLivre) {
    $secoes[] = [
        'titulo' => 'Tesouraria',
        'descricao' => 'Financeiro, comprovantes, regularidade e fechamento mensal.',
        'itens' => [
            ['label' => 'Sessões e ágape pago', 'href' => '/tesouraria/sessoes'],
            ['label' => 'Livro-caixa', 'href' => '/tesouraria/caixa'],
            ['label' => 'Obrigacoes financeiras', 'href' => '/tesouraria/obrigacoes'],
            ['label' => 'Validação de comprovantes', 'href' => '/tesouraria/comprovantes'],
            ['label' => 'Regularidade', 'href' => '/tesouraria/regularidade'],
            ['label' => 'Fechamento mensal', 'href' => '/tesouraria/fechamento'],
            ['label' => 'Relatório da gestão', 'href' => '/tesouraria/relatorio-gestao'],
        ],
    ];

    if ($isTesoureiro) {
        $atalhosPrioritarios = array_merge($atalhosPrioritarios, [
            ['label' => 'Comprovantes', 'href' => '/tesouraria/comprovantes'],
            ['label' => 'Livro-caixa', 'href' => '/tesouraria/caixa'],
            ['label' => 'Regularidade', 'href' => '/tesouraria/regularidade'],
        ]);

        $blocosPrioritarios[] = [
            'perfil' => 'Tesouraria',
            'titulo' => 'Fila principal da Tesouraria',
            'descricao' => 'Atalhos para validar comprovantes, acompanhar o caixa e fechar pendencias do mes.',
            'principal' => ['label' => 'Validar comprovantes', 'href' => '/tesouraria/comprovantes'],
            'secundarios' => [
                ['label' => 'Abrir livro-caixa', 'href' => '/tesouraria/caixa'],
                ['label' => 'Ver regularidade', 'href' => '/tesouraria/regularidade'],
                ['label' => 'Fechamento mensal', 'href' => '/tesouraria/fechamento'],
            ],
        ];
    }
}

$secoes[] = [
    'titulo' => 'Meu Financeiro',
    'descricao' => 'Consulta pessoal de mensalidades, biblioteca, joias e demais obrigacoes cadastradas pela Tesouraria.',
    'itens' => [
        ['label' => 'Minhas obrigacoes financeiras', 'href' => '/financeiro/minhas-obrigacoes'],
    ],
];

if ($isBibliotecario || $isPrimeiroVigilante || $isSegundoVigilante || $isVeneravel || $adminLivre) {
    $secoes[] = [
        'titulo' => 'Biblioteca',
        'descricao' => 'Acervo, empréstimos, classificação de leituras e curadoria formativa.',
        'itens' => [
            ['label' => 'Painel da Biblioteca', 'href' => '/biblioteca'],
        ],
    ];

    if ($isBibliotecario) {
        $atalhosPrioritarios = array_merge($atalhosPrioritarios, [
            ['label' => 'Emprestimos', 'href' => '/biblioteca/emprestimos'],
            ['label' => 'Painel da Biblioteca', 'href' => '/biblioteca'],
            ['label' => 'Novo titulo', 'href' => '/biblioteca/adicionar'],
        ]);

        $blocosPrioritarios[] = [
            'perfil' => 'Biblioteca',
            'titulo' => 'Fluxo principal da Biblioteca',
            'descricao' => 'Entrada rapida para emprestimos, catalogo e cadastro de novos titulos.',
            'principal' => ['label' => 'Gerenciar emprestimos', 'href' => '/biblioteca/emprestimos'],
            'secundarios' => [
                ['label' => 'Abrir painel da Biblioteca', 'href' => '/biblioteca'],
                ['label' => 'Cadastrar novo titulo', 'href' => '/biblioteca/adicionar'],
            ],
        ];
    }
}

if ($isSecretario || $isChanceler || $isVeneravel || $adminLivre) {
    $secoes[] = [
        'titulo' => 'Cadastro e Obreiros',
        'descricao' => 'Consulta e manutenção dos obreiros ativos da loja.',
        'itens' => [
            ['label' => 'Lista de obreiros', 'href' => '/obreiros'],
        ],
    ];
}

if (($isPrimeiroVigilante || $isSegundoVigilante) && !$isSecretario && !$isChanceler && !$isVeneravel && !$adminLivre) {
    $secoes[] = [
        'titulo' => 'Cadastro e Obreiros',
        'descricao' => $isPrimeiroVigilante && !$isSegundoVigilante
            ? 'Consulta dos Aprendizes ativos da loja.'
            : ($isSegundoVigilante && !$isPrimeiroVigilante
                ? 'Consulta dos Companheiros ativos da loja.'
                : 'Consulta dos Aprendizes e Companheiros ativos da loja.'),
        'itens' => [
            ['label' => 'Lista de obreiros', 'href' => '/obreiros'],
        ],
    ];
}

if ($isAdmin || $adminLivre) {
    $secoes[] = [
        'titulo' => 'Administração',
        'descricao' => 'Configurações centrais e liberação ampla para o administrador.',
        'itens' => [
            ['label' => 'Administração de cargos', 'href' => '/admin/cargos'],
            ['label' => 'Parâmetros da Loja', 'href' => '/admin/loja'],
        ],
    ];
}

if (($isSecretario || $isVeneravel) && !$isAdmin) {
    $secoes[] = [
        'titulo' => 'Nominata Oficial',
        'descricao' => 'Gestão da nominata, abertura de gestões e validação central dos cargos da loja.',
        'itens' => [
            ['label' => 'Nominata e gestões', 'href' => '/admin/cargos'],
        ],
    ];
}

$atalhos = [];
foreach ($secoes as $secao) {
    foreach ($secao['itens'] as $item) {
        $atalhos[] = $item;
    }
}
$atalhosMesclados = [];
foreach (array_merge($atalhosPrioritarios, $atalhos) as $item) {
    $chave = ($item['label'] ?? '') . '|' . ($item['href'] ?? '');
    if ($chave === '|' || isset($atalhosMesclados[$chave])) {
        continue;
    }

    $atalhosMesclados[$chave] = $item;
}
$atalhos = array_slice(array_values($atalhosMesclados), 0, 6);

$nominataMap = [];
try {
    $cargoModel = new \App\Models\Cargo();
    foreach ($cargoModel->listarResumoCargos() as $cargoResumo) {
        $codigo = strtoupper((string) ($cargoResumo['codigo'] ?? ''));
        if ($codigo === '') {
            continue;
        }
        $nominataMap[$codigo] = trim((string) ($cargoResumo['titular_nome'] ?? ''));
    }
} catch (\Throwable $e) {
    $nominataMap = [];
}

$cargosDestaque = [
    ['label' => 'Venerável Mestre', 'codigo' => 'VENERAVEL'],
    ['label' => '1º Vigilante', 'codigo' => 'PRIMEIRO_VIGILANTE'],
    ['label' => '2º Vigilante', 'codigo' => 'SEGUNDO_VIGILANTE'],
];

$cargosGestao = [
    ['label' => 'Orador', 'codigo' => 'ORADOR'],
    ['label' => 'Guarda da Lei', 'codigo' => 'GUARDA_DA_LEI'],
    ['label' => 'Secretário', 'codigo' => 'SECRETARIO'],
    ['label' => 'Tesoureiro', 'codigo' => 'TESOUREIRO'],
    ['label' => 'Mestre de Banquetes', 'codigo' => 'MESTRE_BANQUETES'],
    ['label' => 'Guarda do Templo', 'codigo' => 'GUARDA_DO_TEMPLO'],
    ['label' => 'Mestre de Cerimônias', 'codigo' => 'MESTRE_DE_CERIMONIAS'],
    ['label' => 'Chanceler', 'codigo' => 'CHANCELER'],
    ['label' => 'Mestre Hospitaleiro', 'codigo' => 'HOSPITALEIRO'],
    ['label' => '1º Diácono', 'codigo' => 'PRIMEIRO_DIACONO'],
];
?>

<header class="sticky top-0 z-30 border-b border-slate-200/80 bg-cobalto text-white shadow-lg">
    <div class="mx-auto flex max-w-[1600px] items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
        <div class="flex items-center gap-3">
            <div class="flex h-11 w-11 items-center justify-center overflow-hidden rounded-full border border-white/20 bg-white/10 text-xl text-ouro">
                <?php if ($dashboardLogoUrl): ?>
                    <img src="<?= htmlspecialchars($dashboardLogoUrl) ?>" alt="Logotipo da Loja Renascença" class="h-full w-full object-cover">
                <?php else: ?>
                    <span>∴</span>
                <?php endif; ?>
            </div>
            <div>
                <div class="font-serif text-lg font-bold tracking-wide"><?= htmlspecialchars($dashboardNomeLoja) ?></div>
                <div class="text-xs text-slate-300">Painel operacional por cargos e funções</div>
            </div>
        </div>

        <div class="hidden items-center gap-4 md:flex">
            <?php if ($isAdmin): ?>
                <span class="rounded-full bg-ouro px-3 py-1 text-xs font-semibold text-cobalto">Admin com acesso total</span>
            <?php elseif ($showAllPanels): ?>
                <span class="rounded-full bg-white/15 px-3 py-1 text-xs font-semibold text-white">Modo liberado para produção</span>
            <?php endif; ?>
            <span class="text-sm text-slate-200">Olá, <?= htmlspecialchars($usuarioNome) ?></span>
            <a href="/logout" class="rounded-md border border-white/15 px-3 py-2 text-sm text-slate-200 hover:bg-white/10 hover:text-white">Sair</a>
        </div>

        <button @click="menuOpen = !menuOpen" type="button" class="rounded-md border border-white/15 p-2 text-slate-200 md:hidden">
            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
    </div>

    <div x-show="menuOpen" x-transition class="border-t border-white/10 bg-[#102744] md:hidden" style="display: none;">
        <div class="space-y-4 px-4 py-4">
            <div>
                <div class="mb-2 text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Geral</div>
                <?php foreach ($secaoGeral as $item): ?>
                    <a href="<?= htmlspecialchars($item['href']) ?>" class="mb-2 block rounded-md bg-white/5 px-3 py-2 text-sm text-slate-100 hover:bg-white/10"><?= htmlspecialchars($item['label']) ?></a>
                <?php endforeach; ?>
            </div>
            <?php foreach ($secoes as $secao): ?>
                <div>
                    <div class="mb-2 text-xs font-semibold uppercase tracking-[0.18em] text-slate-400"><?= htmlspecialchars($secao['titulo']) ?></div>
                    <?php foreach ($secao['itens'] as $item): ?>
                        <a href="<?= htmlspecialchars($item['href']) ?>" class="mb-2 block rounded-md bg-white/5 px-3 py-2 text-sm text-slate-100 hover:bg-white/10"><?= htmlspecialchars($item['label']) ?></a>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
            <a href="/logout" class="block rounded-md border border-red-300/20 px-3 py-2 text-sm text-red-200 hover:bg-red-400/10">Sair</a>
        </div>
    </div>
</header>

<div class="mx-auto flex max-w-[1600px] gap-6 px-4 py-8 sm:px-6 xl:gap-8 xl:px-8">
    <aside class="hidden w-72 shrink-0 xl:block xl:w-[300px]">
        <div class="sticky top-24 overflow-hidden rounded-3xl border border-white/60 bg-white/80 shadow-painel backdrop-blur xl:max-h-[calc(100vh-7rem)]">
            <div class="border-b border-slate-200 bg-[radial-gradient(circle_at_top_left,#f8e8b6,transparent_45%),linear-gradient(135deg,#ffffff,#f6f1e7)] px-6 py-5">
                <div class="text-xs font-semibold uppercase tracking-[0.22em] text-ardosia">Navegação</div>
                <div class="mt-2 font-serif text-2xl font-bold text-cobalto">Menus por cargo</div>
                <p class="mt-2 text-sm text-slate-600">Cada área aparece agrupada por responsabilidade. O administrador vê tudo.</p>
            </div>

            <nav class="space-y-6 px-5 py-5 xl:overflow-y-auto xl:pr-3">
                <div>
                    <div class="mb-2 px-3 text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Geral</div>
                    <?php foreach ($secaoGeral as $item): ?>
                        <a href="<?= htmlspecialchars($item['href']) ?>" class="mb-2 flex items-center rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm font-medium text-slate-700 hover:border-cobalto hover:bg-white hover:text-cobalto"><?= htmlspecialchars($item['label']) ?></a>
                    <?php endforeach; ?>
                </div>

                <?php foreach ($secoes as $secao): ?>
                    <div>
                        <div class="px-3 text-xs font-semibold uppercase tracking-[0.2em] text-slate-400"><?= htmlspecialchars($secao['titulo']) ?></div>
                        <div class="px-3 pt-2 text-xs text-slate-500"><?= htmlspecialchars($secao['descricao']) ?></div>
                        <div class="mt-2 space-y-2">
                            <?php foreach ($secao['itens'] as $item): ?>
                                <a href="<?= htmlspecialchars($item['href']) ?>" class="flex items-center rounded-xl border border-slate-200 bg-white px-3 py-3 text-sm font-medium text-slate-700 hover:border-cobalto hover:text-cobalto">
                                    <?= htmlspecialchars($item['label']) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </nav>
        </div>
    </aside>

    <main class="min-w-0 flex-1">
        <section class="overflow-hidden rounded-3xl border border-white/70 bg-[radial-gradient(circle_at_top_right,#f7e2a3,transparent_30%),linear-gradient(135deg,#123153,#0f2747)] px-6 py-8 text-white shadow-painel sm:px-8">
            <div class="grid gap-6 xl:grid-cols-[minmax(0,1.7fr)_minmax(300px,0.9fr)] xl:items-end">
                <div class="max-w-3xl">
                    <div class="flex items-start gap-4">
                        <div class="hidden h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-white/15 bg-white/10 sm:flex">
                            <?php if ($dashboardLogoUrl): ?>
                                <img src="<?= htmlspecialchars($dashboardLogoUrl) ?>" alt="Logotipo da Loja Renascença" class="h-full w-full object-cover">
                            <?php else: ?>
                                <span class="text-2xl text-ouro">∴</span>
                            <?php endif; ?>
                        </div>
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-[0.24em] text-ouro/90">Centro de comando</div>
                            <h1 class="mt-3 font-serif text-3xl font-bold leading-tight sm:text-4xl">Abertura útil para sessões, recados e decisões do dia</h1>
                            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-200">O dashboard agora prioriza o que precisa de ação imediata: próximas sessões da Loja, comunicação útil e atalhos operacionais por cargo.</p>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 xl:grid-cols-1 2xl:grid-cols-3">
                    <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-4">
                        <div class="text-xs uppercase tracking-[0.2em] text-slate-300">Perfil ativo</div>
                        <div class="mt-2 text-lg font-semibold"><?= htmlspecialchars(ucfirst($usuarioCargo !== '' ? $usuarioCargo : 'diretoria')) ?></div>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-4">
                        <div class="text-xs uppercase tracking-[0.2em] text-slate-300">Áreas visíveis</div>
                        <div class="mt-2 text-lg font-semibold"><?= count($secoes) ?></div>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-4">
                        <div class="text-xs uppercase tracking-[0.2em] text-slate-300">Próxima reunião</div>
                        <div class="mt-2 text-sm font-semibold"><?= htmlspecialchars($dashboardDiaReuniao !== '' ? $dashboardDiaReuniao : 'Agenda variável') ?></div>
                        <div class="mt-1 text-xs text-slate-300"><?= htmlspecialchars($dashboardHorarioReuniao !== '' ? $dashboardHorarioReuniao : 'Horário a confirmar') ?></div>
                    </div>
                </div>
            </div>
        </section>

        <?php if ($dashboardMensagemSucesso): ?>
            <div class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-800"><?= htmlspecialchars((string) $dashboardMensagemSucesso) ?></div>
        <?php endif; ?>
        <?php if ($dashboardMensagemErro): ?>
            <div class="mt-6 rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm font-medium text-rose-800"><?= htmlspecialchars((string) $dashboardMensagemErro) ?></div>
        <?php endif; ?>

        <section id="sessoes-loja" class="mt-8 grid gap-5 xl:grid-cols-[minmax(0,1.5fr)_minmax(320px,0.9fr)]">
            <article class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-painel">
                <div class="border-b border-slate-200 bg-[linear-gradient(135deg,#fffdf7,#f4ede0)] px-6 py-6">
                    <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Sessões da Loja</div>
                    <h2 class="mt-2 font-serif text-2xl font-bold text-cobalto">Próximas sessões com ação direta</h2>
                    <p class="mt-2 text-sm text-slate-600">Confirme presença, cancele quando necessário e entre no contexto operacional sem sair da abertura do dashboard.</p>
                </div>

                <div class="grid gap-4 px-6 py-6 lg:grid-cols-2">
                    <?php foreach ($dashboardSessoes as $sessaoCard): ?>
                        <?php $sessaoConfirmada = !empty($sessaoCard['confirmado']); ?>
                        <article class="rounded-3xl border border-slate-200 bg-[linear-gradient(180deg,#ffffff,#fbfaf7)] p-5 shadow-sm">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <h3 class="font-serif text-xl font-bold text-cobalto"><?= htmlspecialchars((string) ($sessaoCard['titulo'] ?? 'Sessão')) ?></h3>
                                    <div class="mt-2 text-sm text-slate-600"><?= htmlspecialchars($formatarDataHoraDashboard($sessaoCard['data_hora_inicio'] ?? null)) ?></div>
                                </div>
                                <span class="inline-flex rounded-full border px-3 py-1 text-xs font-semibold <?= $dashboardStatusClasses($sessaoCard['status'] ?? null) ?>">
                                    <?= htmlspecialchars((string) ($sessaoCard['status'] ?? 'Programada')) ?>
                                </span>
                            </div>

                            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                    <div class="text-xs uppercase tracking-[0.18em] text-slate-500">Tipo e grau</div>
                                    <div class="mt-1 text-sm font-medium text-slate-800">
                                        <?= htmlspecialchars(trim((string) (($sessaoCard['tipo_sessao'] ?? 'Sessão') . ((string) ($sessaoCard['grau_sessao'] ?? '') !== '' ? ' · ' . $sessaoCard['grau_sessao'] : '')))) ?>
                                    </div>
                                </div>
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                    <div class="text-xs uppercase tracking-[0.18em] text-slate-500">Confirmados</div>
                                    <div class="mt-1 text-sm font-medium text-slate-800">
                                        <?= (int) ($sessaoCard['total_confirmados'] ?? 0) ?> irmão(s)
                                        <?php if ((int) ($sessaoCard['total_agape'] ?? 0) > 0): ?>
                                            · <?= (int) ($sessaoCard['total_agape'] ?? 0) ?> com ágape
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 rounded-2xl border <?= $sessaoConfirmada ? 'border-emerald-200 bg-emerald-50' : 'border-slate-200 bg-slate-50' ?> px-4 py-3">
                                <div class="text-xs uppercase tracking-[0.18em] <?= $sessaoConfirmada ? 'text-emerald-700' : 'text-slate-500' ?>">Sua resposta</div>
                                <div class="mt-1 text-sm font-semibold <?= $sessaoConfirmada ? 'text-emerald-800' : 'text-slate-700' ?>">
                                    <?= htmlspecialchars($sessaoConfirmada ? 'Presença confirmada' : 'Ainda sem confirmação registrada') ?>
                                </div>
                                <div class="mt-1 text-xs text-slate-500"><?= htmlspecialchars((string) ($sessaoCard['descricao_agape'] ?? '')) ?></div>
                            </div>

                            <div class="mt-4 grid gap-3">
                                <form method="POST" action="/dashboard#sessoes-loja" class="grid gap-3 sm:grid-cols-2">
                                    <input type="hidden" name="dashboard_action" value="sessao_confirmacao">
                                    <input type="hidden" name="sessao_id" value="<?= (int) ($sessaoCard['id'] ?? 0) ?>">
                                    <button type="submit" name="acao" value="confirmar" class="rounded-2xl bg-cobalto px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#163761]">Confirmar presença</button>
                                    <button type="submit" name="acao" value="cancelar" class="rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:border-rose-300 hover:text-rose-700">Cancelar confirmação</button>
                                </form>
                                <a href="<?= htmlspecialchars((string) ($sessaoCard['detalhe_href'] ?? '/dashboard#sessoes-loja')) ?>" class="flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700 transition hover:border-cobalto hover:bg-white hover:text-cobalto">
                                    <span>Abrir sessão</span>
                                    <span class="text-slate-400">Abrir</span>
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>

                    <?php if ($dashboardSessoes === []): ?>
                        <div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-5 py-6 text-sm text-slate-600 lg:col-span-2">
                            Nenhuma sessão futura encontrada no momento. Assim que a Secretaria publicar a agenda, ela aparecerá aqui com ação direta.
                        </div>
                    <?php endif; ?>
                </div>
            </article>

            <aside class="space-y-5">
                <article class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-painel">
                    <div class="border-b border-slate-200 bg-[linear-gradient(135deg,#ffffff,#f7f3ea)] px-6 py-5">
                        <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Recado</div>
                        <h2 class="mt-2 font-serif text-2xl font-bold text-cobalto">Comunicação da Loja</h2>
                    </div>
                    <div class="space-y-4 px-6 py-5">
                        <?php if ($dashboardRecadoPrincipal): ?>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500"><?= htmlspecialchars((string) (($dashboardRecadoPrincipal['status_publicacao'] ?? '') !== '' ? $dashboardRecadoPrincipal['status_publicacao'] : 'Recado')) ?></div>
                                <div class="mt-2 text-base font-semibold text-slate-900"><?= htmlspecialchars((string) (($dashboardRecadoPrincipal['titulo'] ?? '') !== '' ? $dashboardRecadoPrincipal['titulo'] : 'Comunicado recente da Secretaria')) ?></div>
                                <p class="mt-2 text-sm leading-6 text-slate-600"><?= htmlspecialchars($dashboardResumirTexto((string) ($dashboardRecadoPrincipal['conteudo'] ?? ''), 220)) ?></p>
                            </div>
                        <?php else: ?>
                            <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-4 text-sm text-slate-600">Ainda não há recados recentes publicados para destaque.</div>
                        <?php endif; ?>

                        <?php foreach ($dashboardRecadosSecundarios as $recadoSecundario): ?>
                            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                                <div class="text-sm font-semibold text-slate-800"><?= htmlspecialchars((string) (($recadoSecundario['titulo'] ?? '') !== '' ? $recadoSecundario['titulo'] : 'Recado complementar')) ?></div>
                                <div class="mt-1 text-sm text-slate-600"><?= htmlspecialchars($dashboardResumirTexto((string) ($recadoSecundario['conteudo'] ?? ''), 120)) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </article>

                <article class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-painel">
                    <div class="border-b border-slate-200 bg-[linear-gradient(135deg,#fffdf7,#f4ede0)] px-6 py-5">
                        <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Palavra do irmão</div>
                        <h2 class="mt-2 font-serif text-2xl font-bold text-cobalto">Mensagem do dia</h2>
                    </div>
                    <div class="px-6 py-5">
                        <?php if ($dashboardPalavraIrmao !== ''): ?>
                            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm leading-6 text-slate-700"><?= nl2br(htmlspecialchars($dashboardResumirTexto($dashboardPalavraIrmao, 520))) ?></div>
                            <a href="/chancelaria/efemerides" class="mt-4 inline-flex rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:border-cobalto hover:text-cobalto">Abrir efemérides completas</a>
                        <?php else: ?>
                            <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-4 text-sm text-slate-600">A mensagem diária ainda não está disponível. Quando a Chancelaria atualizar as efemérides, ela aparecerá aqui.</div>
                        <?php endif; ?>
                    </div>
                </article>

                <article class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-painel">
                    <div class="border-b border-slate-200 bg-[linear-gradient(135deg,#ffffff,#f7f3ea)] px-6 py-5">
                        <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Gestão</div>
                        <h2 class="mt-2 font-serif text-2xl font-bold text-cobalto">Nominata oficial</h2>
                    </div>
                    <div class="px-6 py-5">
                        <p class="text-sm leading-6 text-slate-600">A nominata continua disponível como área de gestão, sem ocupar o espaço principal da abertura.</p>
                        <a href="/admin/cargos" class="mt-4 inline-flex rounded-2xl bg-cobalto px-4 py-3 text-sm font-semibold text-white hover:bg-[#163761]">Abrir nominata e gestões</a>
                    </div>
                </article>
            </aside>
        </section>

        <section class="mt-8 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-painel">
            <div class="border-b border-slate-200 bg-[linear-gradient(135deg,#ffffff,#f7f3ea)] px-6 py-6">
                <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Sessões de outras Lojas</div>
                <h2 class="mt-2 font-serif text-2xl font-bold text-cobalto">Segunda faixa de agenda</h2>
                <p class="mt-2 text-sm text-slate-600">Estrutura reservada para compartilhar sessões de outras Lojas sem competir com a agenda principal da Renascença.</p>
            </div>
            <div class="grid gap-4 px-6 py-6 md:grid-cols-2 xl:grid-cols-3">
                <?php if ($dashboardOutrasLojas !== []): ?>
                    <?php foreach ($dashboardOutrasLojas as $sessaoExterna): ?>
                        <article class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                            <div class="text-sm font-semibold text-slate-900"><?= htmlspecialchars((string) ($sessaoExterna['loja'] ?? 'Outra Loja')) ?></div>
                            <div class="mt-1 text-sm text-slate-600"><?= htmlspecialchars((string) ($sessaoExterna['titulo'] ?? 'Sessão')) ?></div>
                            <div class="mt-2 text-xs text-slate-500"><?= htmlspecialchars($formatarDataHoraDashboard($sessaoExterna['data_hora_inicio'] ?? null)) ?></div>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-4 text-sm text-slate-600 md:col-span-2 xl:col-span-3">
                        A estrutura já está pronta, mas ainda não há fonte consolidada para sessões externas. Quando essa agenda entrar no sistema, ela aparecerá aqui em cards compactos.
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <?php if ($blocosPrioritarios !== []): ?>
            <section class="mt-8 grid gap-4 xl:grid-cols-2">
                <?php foreach ($blocosPrioritarios as $bloco): ?>
                    <article class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-painel">
                        <div class="border-b border-slate-100 bg-[linear-gradient(135deg,#ffffff,#f7f3ea)] px-5 py-5 sm:px-6">
                            <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400"><?= htmlspecialchars($bloco['perfil']) ?></div>
                            <h2 class="mt-2 font-serif text-2xl font-bold text-cobalto"><?= htmlspecialchars($bloco['titulo']) ?></h2>
                            <p class="mt-2 text-sm leading-6 text-slate-600"><?= htmlspecialchars($bloco['descricao']) ?></p>
                        </div>
                        <div class="space-y-3 px-5 py-5 sm:px-6">
                            <a href="<?= htmlspecialchars($bloco['principal']['href']) ?>" class="flex items-center justify-between rounded-2xl bg-cobalto px-4 py-4 text-sm font-semibold text-white transition hover:bg-[#163761]">
                                <span><?= htmlspecialchars($bloco['principal']['label']) ?></span>
                                <span class="text-white/80">Abrir</span>
                            </a>
                            <div class="grid gap-3">
                                <?php foreach ($bloco['secundarios'] as $item): ?>
                                    <a href="<?= htmlspecialchars($item['href']) ?>" class="flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700 transition hover:border-cobalto hover:bg-white hover:text-cobalto">
                                        <span><?= htmlspecialchars($item['label']) ?></span>
                                        <span class="text-slate-400">Abrir</span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>

        <section class="mt-8 grid gap-5 sm:grid-cols-2 2xl:grid-cols-3">
            <?php foreach ($secoes as $secao): ?>
                <article class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-painel">
                    <div class="border-b border-slate-100 bg-[linear-gradient(135deg,#ffffff,#f7f3ea)] px-6 py-5">
                        <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400"><?= htmlspecialchars($secao['titulo']) ?></div>
                        <h2 class="mt-2 font-serif text-2xl font-bold text-cobalto"><?= htmlspecialchars($secao['titulo']) ?></h2>
                        <p class="mt-2 text-sm leading-6 text-slate-600"><?= htmlspecialchars($secao['descricao']) ?></p>
                    </div>
                    <div class="space-y-3 px-6 py-5">
                        <?php foreach ($secao['itens'] as $item): ?>
                            <a href="<?= htmlspecialchars($item['href']) ?>" class="flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700 transition hover:border-cobalto hover:bg-white hover:text-cobalto">
                                <span><?= htmlspecialchars($item['label']) ?></span>
                                <span class="text-slate-400">Abrir</span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>

        <section class="mt-8 rounded-3xl border border-slate-200 bg-white px-6 py-6 shadow-painel">
            <div>
                <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Atalhos rápidos</div>
                <h2 class="mt-2 font-serif text-2xl font-bold text-cobalto">Ações mais usadas</h2>
                <p class="mt-2 text-sm text-slate-600">Esses atalhos ajudam a entrar direto no fluxo principal sem passar pelos menus laterais.</p>
            </div>
            <div class="mt-5 flex flex-wrap gap-3">
                <?php foreach ($atalhos as $item): ?>
                    <a href="<?= htmlspecialchars($item['href']) ?>" class="rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-sm font-medium text-slate-700 hover:border-cobalto hover:bg-white hover:text-cobalto"><?= htmlspecialchars($item['label']) ?></a>
                <?php endforeach; ?>
            </div>
        </section>
    </main>
</div>
</body>
</html>
