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
$isTesoureiro = in_array('tesoureiro', $usuarioCargos, true);
$isPrimeiroVigilante = in_array('primeiro_vigilante', $usuarioCargos, true);
$isSegundoVigilante = in_array('segundo_vigilante', $usuarioCargos, true);
$isSecretario = in_array('secretario', $usuarioCargos, true);
$isVeneravel = in_array('veneravel', $usuarioCargos, true);
$isBibliotecario = in_array('bibliotecario', $usuarioCargos, true);
$isHospitaleiro = in_array('hospitaleiro', $usuarioCargos, true);
$adminLivre = $isAdmin || $showAllPanels;

$secaoGeral = [
    ['label' => 'Dashboard', 'href' => '/dashboard'],
];

$secoes = [];

if ($isChanceler || $isVeneravel || $adminLivre) {
    $secoes[] = [
        'titulo' => 'Chancelaria',
        'descricao' => 'Mensagens do dia, certificados e manutenção de efemérides.',
        'itens' => [
            ['label' => 'Revisar mensagem do dia', 'href' => '/chancelaria/efemerides?foco=mensagem'],
            ['label' => 'Corrigir dados das efemérides', 'href' => '/chancelaria/efemerides?foco=dados'],
            ['label' => 'Visão completa da Chancelaria', 'href' => '/chancelaria/efemerides'],
            ['label' => 'Emitir certificado', 'href' => '/chancelaria/certificado'],
        ],
    ];
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

if ($isHospitaleiro || $isVeneravel || $isTesoureiro || $adminLivre) {
    $secoes[] = [
        'titulo' => 'Hospitalaria',
        'descricao' => 'Ocorrencias assistenciais, acompanhamento e encaminhamentos ao Veneravel e Tesouraria.',
        'itens' => [
            ['label' => 'Painel de Assistencia', 'href' => '/assistencia'],
        ],
    ];
}

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
            ['label' => 'Livro-caixa', 'href' => '/tesouraria/caixa'],
            ['label' => 'Validação de comprovantes', 'href' => '/tesouraria/comprovantes'],
            ['label' => 'Regularidade', 'href' => '/tesouraria/regularidade'],
            ['label' => 'Fechamento mensal', 'href' => '/tesouraria/fechamento'],
        ],
    ];
}

if ($isBibliotecario || $isPrimeiroVigilante || $isSegundoVigilante || $isVeneravel || $adminLivre) {
    $secoes[] = [
        'titulo' => 'Biblioteca',
        'descricao' => 'Acervo, empréstimos, classificação de leituras e curadoria formativa.',
        'itens' => [
            ['label' => 'Painel da Biblioteca', 'href' => '/biblioteca'],
        ],
    ];
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
        ],
    ];
}

$atalhos = [];
foreach ($secoes as $secao) {
    foreach ($secao['itens'] as $item) {
        $atalhos[] = $item;
    }
}
$atalhos = array_slice($atalhos, 0, 6);

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
    <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
        <div class="flex items-center gap-3">
            <div class="flex h-11 w-11 items-center justify-center rounded-full border border-white/20 bg-white/10 text-xl text-ouro">∴</div>
            <div>
                <div class="font-serif text-lg font-bold tracking-wide">Loja Maçônica Renascença</div>
                <div class="text-xs text-slate-300">Dashboard por cargos e funções</div>
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

<div class="mx-auto flex max-w-7xl gap-8 px-4 py-8 sm:px-6 lg:px-8">
    <aside class="hidden w-80 shrink-0 md:block">
        <div class="sticky top-24 overflow-hidden rounded-3xl border border-white/60 bg-white/80 shadow-painel backdrop-blur">
            <div class="border-b border-slate-200 bg-[radial-gradient(circle_at_top_left,#f8e8b6,transparent_45%),linear-gradient(135deg,#ffffff,#f6f1e7)] px-6 py-5">
                <div class="text-xs font-semibold uppercase tracking-[0.22em] text-ardosia">Navegação</div>
                <div class="mt-2 font-serif text-2xl font-bold text-cobalto">Menus por cargo</div>
                <p class="mt-2 text-sm text-slate-600">Cada área aparece agrupada por responsabilidade. O administrador vê tudo.</p>
            </div>

            <nav class="space-y-6 px-5 py-5">
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
            <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-2xl">
                    <div class="text-xs font-semibold uppercase tracking-[0.24em] text-ouro/90">Centro de comando</div>
                    <h1 class="mt-3 font-serif text-3xl font-bold leading-tight sm:text-4xl">Dashboard geral com menus e submenus por cargo</h1>
                    <p class="mt-3 max-w-xl text-sm leading-6 text-slate-200">A navegação fica mais clara: cada função aparece dentro do seu bloco de responsabilidade, enquanto o administrador continua com acesso livre e completo.</p>
                </div>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-4">
                        <div class="text-xs uppercase tracking-[0.2em] text-slate-300">Perfil ativo</div>
                        <div class="mt-2 text-lg font-semibold"><?= htmlspecialchars(ucfirst($usuarioCargo !== '' ? $usuarioCargo : 'diretoria')) ?></div>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-4">
                        <div class="text-xs uppercase tracking-[0.2em] text-slate-300">Áreas visíveis</div>
                        <div class="mt-2 text-lg font-semibold"><?= count($secoes) ?></div>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-4">
                        <div class="text-xs uppercase tracking-[0.2em] text-slate-300">Acesso total</div>
                        <div class="mt-2 text-lg font-semibold"><?= $adminLivre ? 'Sim' : 'Não' ?></div>
                    </div>
                </div>
            </div>
        </section>

        <section class="mt-8 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-painel">
            <div class="border-b border-slate-200 bg-[linear-gradient(135deg,#fffdf7,#f4ede0)] px-6 py-6">
                <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Gestão atual</div>
                <h2 class="mt-2 font-serif text-2xl font-bold text-cobalto">Nominata da atual gestão</h2>
                <p class="mt-2 text-sm text-slate-600">Os cargos principais ficam em destaque. Onde ainda não houver atribuição cadastrada, o dashboard mostra <strong>A definir</strong>.</p>
            </div>

            <div class="grid gap-5 px-6 py-6 lg:grid-cols-[1.2fr_1fr]">
                <div class="grid gap-4 md:grid-cols-3">
                    <?php foreach ($cargosDestaque as $cargo): ?>
                        <?php $titular = trim((string) ($nominataMap[$cargo['codigo']] ?? '')); ?>
                        <article class="rounded-2xl border border-ouro/30 bg-[linear-gradient(180deg,#fffaf0,#f8f1df)] p-5">
                            <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Cargo principal</div>
                            <h3 class="mt-2 font-serif text-xl font-bold text-cobalto"><?= htmlspecialchars($cargo['label']) ?></h3>
                            <p class="mt-4 text-lg font-semibold text-slate-800"><?= htmlspecialchars($titular !== '' ? $titular : 'A definir') ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                    <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Demais cargos da gestão</div>
                    <div class="mt-4 space-y-3">
                        <?php foreach ($cargosGestao as $cargo): ?>
                            <?php $titular = trim((string) ($nominataMap[$cargo['codigo']] ?? '')); ?>
                            <div class="flex items-start justify-between gap-4 rounded-xl border border-slate-200 bg-white px-4 py-3">
                                <div class="text-sm font-medium text-slate-700"><?= htmlspecialchars($cargo['label']) ?></div>
                                <div class="text-sm font-semibold text-cobalto"><?= htmlspecialchars($titular !== '' ? $titular : 'A definir') ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>

        <section class="mt-8 grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
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
