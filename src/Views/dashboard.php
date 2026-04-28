<?php
if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
}
?>
<?php
$usuarioNome = $_SESSION['usuario_nome'] ?? 'Irmão';
$usuarioCargo = (string) ($_SESSION['usuario_cargo'] ?? '');
$usuarioLogado = is_array($_SESSION['usuario_logado'] ?? null) ? $_SESSION['usuario_logado'] : [];
$usuarioNomeCompleto = trim((string) ($usuarioLogado['nome_completo'] ?? ''));
$usuarioNomeHistorico = trim((string) ($usuarioLogado['nome_historico'] ?? ''));
$usuarioNomeNormalizado = strtolower(trim((string) $usuarioNome));
if (in_array($usuarioNomeNormalizado, ['admin', 'administrador'], true)) {
    if ($usuarioNomeCompleto !== '' && stripos($usuarioNomeCompleto, 'acesso temporario') === false) {
        $usuarioNome = $usuarioNomeCompleto;
    } elseif ($usuarioNomeHistorico !== '' && !in_array(strtolower($usuarioNomeHistorico), ['admin', 'administrador'], true)) {
        $usuarioNome = $usuarioNomeHistorico;
    } else {
        $usuarioNome = 'Irmão';
    }
}
$usuarioCargos = $_SESSION['usuario_cargos'] ?? [$usuarioCargo];
$isSystemAdmin = (bool) ($_SESSION['is_system_admin'] ?? false);
$isTestSession = isset($_SESSION['usuario_id']) && (string) $_SESSION['usuario_id'] === '0';
$allowAllPanels = filter_var($_ENV['APP_TEST_ALLOW_ALL_PANELS'] ?? 'true', FILTER_VALIDATE_BOOL);
$showAllPanels = filter_var($_ENV['APP_TEST_OPEN_ACCESS'] ?? 'false', FILTER_VALIDATE_BOOL) || $isTestSession || $allowAllPanels;
$dashboardPermissions = is_array($dashboardPermissions ?? null) ? $dashboardPermissions : [];
$dashboardCan = static function (string $permission) use ($dashboardPermissions, $showAllPanels): bool {
    if ($showAllPanels) {
        return true;
    }

    return (bool) ($dashboardPermissions[$permission] ?? false);
};

$canAdminCargos = $dashboardCan('admin.cargos.view');
$canAdminLoja = $dashboardCan('admin.loja.view');
$canChancelaria = $dashboardCan('chancelaria.manage');
$canSecretaria = $dashboardCan('secretaria.manage');
$canOrador = $dashboardCan('orador.view');
$canBanquetes = $dashboardCan('mestre_banquetes.manage');
$canTesouraria = $dashboardCan('tesouraria.manage');
$canTesourariaPessoal = $dashboardCan('financeiro.self');
$canBibliotecaConsultar = $dashboardCan('biblioteca.self');
$canBibliotecaGerir = $dashboardCan('biblioteca.manage');
$canBibliotecaClassificar = $dashboardCan('biblioteca.classificar');
$canObreirosView = $dashboardCan('obreiros.view');
$canHospitaleiro = $dashboardCan('hospitaleiro.manage');
$canMestreHarmonia = $dashboardCan('mestre_harmonia.manage');
$canPrimeiroVigilante = $dashboardCan('vigilancia.primeiro.manage');
$canSegundoVigilante = $dashboardCan('vigilancia.segundo.manage');
$canVeneravel = $dashboardCan('veneravel.manage');
$adminLivre = $showAllPanels;
$dashboardMensagemSucesso = $dashboardMensagemSucesso ?? null;
$dashboardMensagemErro = $dashboardMensagemErro ?? null;
$dashboardConfiguracaoLoja = is_array($dashboardConfiguracaoLoja ?? null) ? $dashboardConfiguracaoLoja : [];
$dashboardLogoUrl = $dashboardLogoUrl ?? null;
if ($dashboardLogoUrl === null) {
    $dashboardLogoUrl = \App\Core\Tenant\TenantAssetResolver::resolveLogo((string) ($_SESSION['tenant_slug'] ?? ''));
}
$dashboardSessoes = is_array($dashboardSessoes ?? null) ? $dashboardSessoes : [];
$dashboardRecados = is_array($dashboardRecados ?? null) ? $dashboardRecados : [];
$dashboardPalavraIrmao = trim((string) ($dashboardPalavraIrmao ?? ''));
$dashboardOutrasLojas = is_array($dashboardOutrasLojas ?? null) ? $dashboardOutrasLojas : [];

$dashboardNomeLoja = trim((string) ($dashboardConfiguracaoLoja['nome_loja'] ?? ($_SESSION['tenant_name'] ?? 'Loja Maçonica')));
$dashboardNumeroLoja = trim((string) ($dashboardConfiguracaoLoja['numero_loja'] ?? ''));
if ($dashboardNumeroLoja !== '') {
    $dashboardNomeLoja .= ' nº ' . $dashboardNumeroLoja;
}
$dashboardDiaSessao = trim((string) ($dashboardConfiguracaoLoja['dia_semana_reuniao'] ?? ''));
$dashboardHorarioSessao = trim((string) ($dashboardConfiguracaoLoja['horario_reuniao'] ?? ''));
$dashboardRecadoPrincipal = $dashboardRecados[0] ?? null;
$dashboardRecadosSecundarios = array_slice($dashboardRecados, 1, 2);

$formatarDataHoraPainel = static function (?string $valor): string {
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
    ['label' => 'Painel', 'href' => '/dashboard'],
];

$secoes = [];
$atalhosPrioritarios = [];
$blocosPrioritarios = [];

if ($canChancelaria || $canVeneravel || $adminLivre) {
    $secoes[] = [
        'titulo' => 'Chancelaria',
        'descricao' => 'Mensagens do dia, certificados e manutenção de efemérides.',
        'itens' => [
            ['label' => 'Revisar mensagem do dia', 'href' => '/chancelaria/efemerides?foco=mensagem'],
            ['label' => 'Corrigir dados das efemérides', 'href' => '/chancelaria/efemerides?foco=dados'],
            ['label' => 'Visão completa da Chancelaria', 'href' => '/chancelaria/efemerides'],
            ['label' => 'Emitir certificado', 'href' => '/chancelaria/certificado'],
            ['label' => 'Sessão e check-in do Chanceler', 'href' => '/chanceler/sessao'],
        ],
    ];

    if ($canChancelaria && !$canVeneravel) {
        $atalhosPrioritarios = array_merge($atalhosPrioritarios, [
            ['label' => 'Mensagem do dia', 'href' => '/chancelaria/efemerides?foco=mensagem'],
            ['label' => 'Certificado', 'href' => '/chancelaria/certificado'],
            ['label' => 'Sessão do Chanceler', 'href' => '/chanceler/sessao'],
        ]);

        $blocosPrioritarios[] = [
            'perfil' => 'Chancelaria',
            'titulo' => 'Prioridades do Chanceler',
            'descricao' => 'Acesso direto ao que mais pesa no uso diário: efemérides, certificado e apoio de sessão.',
            'principal' => ['label' => 'Revisar mensagem do dia', 'href' => '/chancelaria/efemerides?foco=mensagem'],
            'secundarios' => [
                ['label' => 'Corrigir dados das efemérides', 'href' => '/chancelaria/efemerides?foco=dados'],
                ['label' => 'Emitir certificado', 'href' => '/chancelaria/certificado'],
                ['label' => 'Sessão e check-in', 'href' => '/chanceler/sessao'],
            ],
        ];
    }
}

if ($canSecretaria || $canVeneravel || $adminLivre) {
    $secoes[] = [
        'titulo' => 'Secretaria',
        'descricao' => 'Cadastros, sessões, balaústres e relatórios sob responsabilidade da Secretaria.',
        'itens' => [
            ['label' => 'Painel da Secretaria', 'href' => '/secretaria'],
            ['label' => 'Balaústres / votação', 'href' => '/secretaria/votacao'],
            ['label' => 'Relatório anual', 'href' => '/secretaria/relatorio-anual'],
            ['label' => 'Central de obreiros', 'href' => '/obreiros'],
            ['label' => 'Nominata oficial (cargos)', 'href' => '/admin/cargos'],
            ['label' => 'Convites de acesso', 'href' => '/admin/convites'],
            ['label' => 'Acessos e permissões', 'href' => '/admin/acessos'],
        ],
    ];
}

if ($canOrador || $canVeneravel || $adminLivre) {
    $secoes[] = [
        'titulo' => 'Orador',
        'descricao' => 'Leitura resumida da sessão, apoio ritual e nominata resumida de visitantes para agradecimento em Loja.',
        'itens' => [
            ['label' => 'Painel do Orador', 'href' => '/orador'],
        ],
    ];
}

if ($canBanquetes || $canVeneravel || $adminLivre) {
    $secoes[] = [
        'titulo' => 'Mestre de Banquetes',
        'descricao' => 'Leitura operacional dos confirmados com e sem ágape para planejamento do banquete.',
        'itens' => [
            ['label' => 'Painel do Mestre de Banquetes', 'href' => '/mestre-banquetes'],
        ],
    ];
}

if ($canHospitaleiro || $canVeneravel || $canTesouraria || $adminLivre) {
    $secoes[] = [
        'titulo' => 'Hospitalaria',
        'descricao' => 'Ocorrências assistenciais, acompanhamento e encaminhamentos ao Venerável e Tesouraria.',
        'itens' => [
            ['label' => 'Painel de Assistência', 'href' => '/assistencia'],
        ],
    ];
}

if ($canMestreHarmonia || $canVeneravel || $adminLivre) {
    $secoes[] = [
        'titulo' => 'Mestre de Harmonia',
        'descricao' => 'Player ritual em tela cheia, com etapas principais, transições e extras por sessão.',
        'itens' => [
            ['label' => 'Painel do Mestre de Harmonia', 'href' => '/mestre-harmonia'],
        ],
    ];
}

if ($canPrimeiroVigilante || $canVeneravel || $adminLivre) {
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

if ($canSegundoVigilante || $canVeneravel || $adminLivre) {
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

if ($canVeneravel || $adminLivre) {
    $secoes[] = [
        'titulo' => 'Venerável Mestre',
        'descricao' => 'Decisões de votação, acompanhamento de sessão e nominata oficial.',
        'itens' => [
            ['label' => 'Painel do Venerável', 'href' => '/veneravel'],
            ['label' => 'Abrir/encerrar votações', 'href' => '/secretaria/votacao'],
        ],
    ];
}

if ($canTesouraria || $canVeneravel || $adminLivre) {
    $secoes[] = [
        'titulo' => 'Tesouraria',
        'descricao' => 'Tesouraria, comprovantes, regularidade e fechamento mensal.',
        'itens' => [
            ['label' => 'Sessões e ágape pago', 'href' => '/tesouraria/sessoes'],
            ['label' => 'Caixa da Loja', 'href' => '/tesouraria/caixa'],
            ['label' => 'Obrigações financeiras', 'href' => '/tesouraria/obrigacoes'],
            ['label' => 'Validação de comprovantes', 'href' => '/tesouraria/comprovantes'],
            ['label' => 'Regularidade', 'href' => '/tesouraria/regularidade'],
            ['label' => 'Fechamento mensal', 'href' => '/tesouraria/fechamento'],
            ['label' => 'Relatório da gestão', 'href' => '/tesouraria/relatorio-gestao'],
        ],
    ];

    if ($canTesouraria && !$canVeneravel) {
        $atalhosPrioritarios = array_merge($atalhosPrioritarios, [
            ['label' => 'Comprovantes', 'href' => '/tesouraria/comprovantes'],
            ['label' => 'Caixa da Loja', 'href' => '/tesouraria/caixa'],
            ['label' => 'Regularidade', 'href' => '/tesouraria/regularidade'],
        ]);

        $blocosPrioritarios[] = [
            'perfil' => 'Tesouraria',
            'titulo' => 'Fila principal da Tesouraria',
            'descricao' => 'Atalhos para validar comprovantes, acompanhar o caixa e fechar pendências do mês.',
            'principal' => ['label' => 'Validar comprovantes', 'href' => '/tesouraria/comprovantes'],
            'secundarios' => [
                ['label' => 'Abrir livro-caixa', 'href' => '/tesouraria/caixa'],
                ['label' => 'Ver regularidade', 'href' => '/tesouraria/regularidade'],
                ['label' => 'Fechamento mensal', 'href' => '/tesouraria/fechamento'],
            ],
        ];
    }
}

if ($canTesourariaPessoal || $adminLivre) {
    $secoes[] = [
        'titulo' => 'Meu Tesouraria',
        'descricao' => 'Consulta pessoal de mensalidades, biblioteca, joias e demais obrigações cadastradas pela Tesouraria.',
        'itens' => [
            ['label' => 'Minhas obrigações financeiras', 'href' => '/financeiro/minhas-obrigacoes'],
        ],
    ];
}

if ($canBibliotecaConsultar || $canBibliotecaGerir || $canBibliotecaClassificar || $canVeneravel || $adminLivre) {
    $secoes[] = [
        'titulo' => 'Biblioteca',
        'descricao' => 'Acervo, empréstimos, classificação de leituras e curadoria formativa.',
        'itens' => [
            ['label' => 'Painel da Biblioteca', 'href' => '/biblioteca'],
        ],
    ];

    if ($canBibliotecaGerir && !$canPrimeiroVigilante && !$canSegundoVigilante && !$canVeneravel) {
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

if ($canSecretaria || $canChancelaria || $canVeneravel || $adminLivre) {
    $secoes[] = [
        'titulo' => 'Registro e Obreiros',
        'descricao' => 'Consulta e manutenção dos obreiros ativos da loja.',
        'itens' => [
            ['label' => 'Lista de obreiros', 'href' => '/obreiros'],
        ],
    ];
}

if (($canPrimeiroVigilante || $canSegundoVigilante) && !$canSecretaria && !$canChancelaria && !$canVeneravel && !$adminLivre) {
    $secoes[] = [
        'titulo' => 'Registro e Obreiros',
        'descricao' => $canPrimeiroVigilante && !$canSegundoVigilante
            ? 'Consulta dos Aprendizes ativos da loja.'
            : ($canSegundoVigilante && !$canPrimeiroVigilante
                ? 'Consulta dos Companheiros ativos da loja.'
                : 'Consulta dos Aprendizes e Companheiros ativos da loja.'),
        'itens' => [
            ['label' => 'Lista de obreiros', 'href' => '/obreiros'],
        ],
    ];
}

if ($canAdminCargos || $canAdminLoja || $adminLivre) {
    if ($isSystemAdmin) {
        $secoes[] = [
            'titulo' => 'Sistema',
            'descricao' => 'Painel técnico do sistema (oculto para membros da Loja).',
            'itens' => array_values(array_filter([
                ['label' => 'Painel do sistema', 'href' => '/sistema'],
                ($dashboardCan('admin.loja.view') || $adminLivre) ? ['label' => 'Parâmetros da Loja', 'href' => '/admin/loja'] : null,
                ($dashboardCan('admin.auditoria.view') || $adminLivre) ? ['label' => 'Auditoria', 'href' => '/admin/auditoria'] : null,
            ])),
        ];
    }
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
          if ($codigo === 'ADMINISTRADOR') {
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
// TODO(layout): consolidar classes visuais legadas no shell ERP em um lote dedicado,
// sem alterar regras de negócio nem contratos do payload atual.
$erpPageTitle = 'Painel - Gestor de Loja';
$appShellEyebrow = 'Painel';
$appShellTitle = $dashboardNomeLoja;
$appShellDescription = 'Abertura operacional da Loja com agenda, recados e acessos prioritários.';
$appShellActiveHref = '/dashboard';
$appShellUserLabel = $usuarioNome;
$appShellActions = [
    ['label' => 'Sair', 'href' => '/logout'],
];
$appShellSidebarSections = array_merge(
    [['title' => 'Geral', 'items' => $secaoGeral]],
    array_map(
        static fn(array $secao): array => [
            'title' => (string) ($secao['titulo'] ?? 'Secao'),
            'items' => $secao['itens'] ?? [],
        ],
        $secoes
    )
);
require __DIR__ . '/partials/erp_head.php';
?>
<?php require __DIR__ . '/partials/erp_shell_open.php'; ?>
<div class="space-y-8">
<?php if (false): ?>
<header class="sticky top-0 z-30 border-b border-slate-200/80 bg-cobalto text-white shadow-lg">
    <div class="mx-auto flex max-w-[1600px] items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
        <div class="flex items-center gap-3">
            <div class="flex h-11 w-11 items-center justify-center overflow-hidden rounded-full border border-white/20 bg-white/10 text-xl text-ouro">
                <?php if ($dashboardLogoUrl): ?>
                    <img src="<?= htmlspecialchars($dashboardLogoUrl) ?>" alt="Logotipo da Loja" class="h-full w-full object-cover">
                <?php else: ?>
                    <span>∴</span>
                <?php endif; ?>
            </div>
            <div>
                <div class="font-serif text-lg font-bold tracking-wide"><?= htmlspecialchars($dashboardNomeLoja) ?></div>
                <div class="text-xs text-slate-300">Acesso da Loja por ofícios e responsabilidades</div>
            </div>
        </div>

        <div class="hidden items-center gap-4 md:flex">
            <?php if ($canAdminCargos && $canAdminLoja && $dashboardCan('admin.loja.manage')): ?>
                <span class="hidden rounded-full bg-ouro px-3 py-1 text-xs font-semibold text-cobalto">Acesso técnico total</span>
            <?php elseif ($showAllPanels): ?>
                <span class="rounded-full bg-white/15 px-3 py-1 text-xs font-semibold text-white">Acesso ampliado nesta sessão</span>
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
                <div class="sticky top-24 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm xl:max-h-[calc(100vh-7rem)]">
            <div class="border-b border-slate-200 bg-[radial-gradient(circle_at_top_left,#f8e8b6,transparent_45%),linear-gradient(135deg,#ffffff,#f6f1e7)] px-6 py-5">
                <div class="text-xs font-semibold uppercase tracking-[0.22em] text-ardosia">Navegação</div>
                    <div class="mt-2 text-2xl font-semibold text-erp-navy">Menus por cargo</div>
                <p class="mt-2 text-sm text-slate-600">Cada área aparece agrupada por responsabilidade. O acesso técnico vê tudo.</p>
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
<?php endif; ?>
        <section class="overflow-hidden rounded-[2rem] border border-erp-border bg-white shadow-sm">
            <div class="grid gap-6 border-b border-erp-border bg-[linear-gradient(135deg,#ffffff_0%,#f5f7fb_52%,#f9f2dd_100%)] px-6 py-7 xl:grid-cols-12 xl:px-8">
                <div class="flex min-w-0 gap-5 xl:col-span-8 2xl:col-span-9">
                    <div class="hidden h-24 w-24 shrink-0 overflow-hidden rounded-2xl border border-erp-border bg-white shadow-sm sm:flex sm:items-center sm:justify-center">
                        <?php if ($dashboardLogoUrl): ?>
                            <img src="<?= htmlspecialchars((string) $dashboardLogoUrl) ?>" alt="Brasao da Loja" class="h-[5.5rem] w-[5.5rem] object-contain">
                        <?php else: ?>
                            <div class="text-3xl font-semibold text-erp-gold">&#9651;</div>
                        <?php endif; ?>
                    </div>
                    <div class="min-w-0">
                        <div class="text-sm font-semibold uppercase tracking-[0.24em] text-erp-gold">Centro de comando</div>
                        <h2 class="mt-3 text-4xl font-semibold leading-tight text-erp-navy 2xl:text-5xl"><?= htmlspecialchars($dashboardNomeLoja) ?></h2>
                        <p class="mt-4 max-w-4xl text-base leading-7 text-erp-muted">
                            Abertura administrativa com agenda da Loja, recados prioritários e acessos operacionais organizados por responsabilidade.
                        </p>
                        <div class="mt-5 flex flex-wrap gap-3">
                            <a href="/secretaria" class="rounded-erp-md border border-erp-border bg-white px-4 py-2.5 text-sm font-semibold text-erp-text hover:border-erp-navy hover:text-erp-navy">Abrir secretaria</a>
                            <a href="/admin/cargos" class="rounded-erp-md border border-erp-navy bg-erp-navy px-4 py-2.5 text-sm font-semibold text-white hover:opacity-95">Ir para nominata oficial</a>
                        </div>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-3 xl:col-span-4 xl:grid-cols-1 2xl:col-span-3 2xl:grid-cols-3">
                    <article class="rounded-2xl border border-erp-border bg-white/90 px-5 py-4 shadow-sm">
                        <div class="text-xs font-semibold uppercase tracking-[0.2em] text-erp-muted">Perfil ativo</div>
                        <div class="mt-2 text-xl font-semibold text-erp-navy"><?= htmlspecialchars($usuarioCargo !== '' ? $usuarioCargo : 'Operação geral') ?></div>
                        <p class="mt-1 text-sm leading-6 text-erp-muted">Leitura personalizada conforme as permissões desta sessão.</p>
                    </article>
                    <article class="rounded-2xl border border-erp-border bg-white/90 px-5 py-4 shadow-sm">
                        <div class="text-xs font-semibold uppercase tracking-[0.2em] text-erp-muted">Áreas visíveis</div>
                        <div class="mt-2 text-xl font-semibold text-erp-navy"><?= count($secoes) ?></div>
                        <p class="mt-1 text-sm leading-6 text-erp-muted">Blocos operacionais liberados nesta abertura administrativa.</p>
                    </article>
                    <article class="rounded-2xl border border-erp-border bg-white/90 px-5 py-4 shadow-sm">
                        <div class="text-xs font-semibold uppercase tracking-[0.2em] text-erp-muted">Próxima reunião</div>
                        <div class="mt-2 text-lg font-semibold text-erp-navy">
                            <?= htmlspecialchars(trim($dashboardDiaSessao . ($dashboardHorarioSessao !== '' ? ' · ' . $dashboardHorarioSessao : '')) ?: 'A definir') ?>
                        </div>
                        <p class="mt-1 text-sm leading-6 text-erp-muted">Referência institucional usada para orientar a agenda da Loja.</p>
                    </article>
                </div>
            </div>

            <div class="grid gap-4 px-6 py-6 md:grid-cols-2 2xl:grid-cols-4 xl:px-8">
                <article class="rounded-2xl border border-erp-border bg-white px-5 py-4 shadow-sm">
                    <div class="text-xs font-semibold uppercase tracking-[0.2em] text-erp-muted">Quadro de obreiros</div>
                    <?php /* TODO(dados): exibir total consolidado de obreiros quando o payload disponibilizar esse indicador. */ ?>
                    <div class="mt-2 text-3xl font-semibold text-erp-navy">Informação em atualização</div>
                    <p class="mt-1 text-sm text-erp-muted">Informação em atualização</p>
                </article>
                <article class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 shadow-sm">
                    <div class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-700">Regularidade financeira</div>
                    <?php /* TODO(dados): exibir consolidado financeiro real quando o payload fornecer os totais oficiais. */ ?>
                    <div class="mt-2 text-3xl font-semibold text-emerald-800">Dados em atualização</div>
                    <p class="mt-1 text-sm text-emerald-700">Dados em atualização</p>
                </article>
                <article class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 shadow-sm">
                    <div class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-700">Pendências financeiras</div>
                    <?php /* TODO(dados): exibir consolidado financeiro real quando o payload fornecer os totais oficiais. */ ?>
                    <div class="mt-2 text-3xl font-semibold text-amber-800">Dados em atualização</div>
                    <p class="mt-1 text-sm text-amber-700">Dados em atualização</p>
                </article>
                <article class="rounded-2xl border border-erp-border bg-white px-5 py-4 shadow-sm">
                    <div class="text-xs font-semibold uppercase tracking-[0.2em] text-erp-muted">Próxima sessão</div>
                    <div class="mt-2 text-xl font-semibold text-erp-navy">
                        <?= htmlspecialchars(isset($dashboardSessoes[0]['data_hora_inicio']) ? $formatarDataHoraPainel($dashboardSessoes[0]['data_hora_inicio']) : 'Agenda não publicada') ?>
                    </div>
                    <p class="mt-1 text-sm text-erp-muted">
                        <?= htmlspecialchars((string) ($dashboardSessoes[0]['titulo'] ?? 'A Secretaria ainda não publicou a próxima sessão.')) ?>
                    </p>
                </article>
            </div>
        </section>

        <?php if ($dashboardMensagemSucesso): ?>
            <div class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-800"><?= htmlspecialchars((string) $dashboardMensagemSucesso) ?></div>
        <?php endif; ?>
        <?php if ($dashboardMensagemErro): ?>
            <div class="mt-6 rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm font-medium text-rose-800"><?= htmlspecialchars((string) $dashboardMensagemErro) ?></div>
        <?php endif; ?>

        <section class="mt-8 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 bg-slate-50 px-6 py-5">
                <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Assistente IA (beta)</div>
                <h2 class="mt-2 text-2xl font-semibold text-erp-navy">Comando curto em linguagem natural</h2>
                <p class="mt-2 text-sm text-slate-600">Exemplos: "fechar mes", "mensalidade do leandro", "aniversario joao".</p>
            </div>
            <div class="px-6 py-5">
                <form id="assistente-ia-form" class="grid gap-3 md:grid-cols-[1fr_auto]">
                    <input id="assistente-ia-comando" type="text" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm" placeholder="Digite seu objetivo...">
                    <button type="submit" class="rounded-xl bg-cobalto px-5 py-3 text-sm font-semibold text-white hover:bg-[#163761]">Interpretar</button>
                </form>
                <div class="mt-3 flex flex-wrap gap-2">
                    <button type="button" data-assistente-exemplo="fechar mes" class="rounded-full border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:border-cobalto hover:text-cobalto">fechar mes</button>
                    <button type="button" data-assistente-exemplo="mensalidade do leandro" class="rounded-full border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:border-cobalto hover:text-cobalto">mensalidade do leandro</button>
                    <button type="button" data-assistente-exemplo="aniversario joao" class="rounded-full border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:border-cobalto hover:text-cobalto">aniversario joao</button>
                </div>
                <div id="assistente-ia-resposta" class="mt-4 hidden rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-700"></div>
            </div>
        </section>

        <section id="sessoes-loja" class="mt-8 grid gap-6 xl:grid-cols-12">
            <article class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm xl:col-span-8 2xl:col-span-9">
                <div class="border-b border-slate-200 bg-slate-50 px-6 py-6">
                    <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Sessões da Loja</div>
                    <h2 class="mt-2 text-2xl font-semibold text-erp-navy">Próximas sessões da Loja</h2>
                    <p class="mt-2 text-sm text-slate-600">Confirme sua presença, ajuste quando precisar e abra a sessão sem sair da página inicial.</p>
                </div>

                <div class="grid gap-4 px-6 py-6 xl:grid-cols-2">
                    <?php foreach ($dashboardSessoes as $sessaoCard): ?>
                        <?php $sessaoConfirmada = !empty($sessaoCard['confirmado']); ?>
                        <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <h3 class="text-xl font-semibold text-erp-navy"><?= htmlspecialchars((string) ($sessaoCard['titulo'] ?? 'Sessão')) ?></h3>
                                    <div class="mt-2 text-sm text-slate-600"><?= htmlspecialchars($formatarDataHoraPainel($sessaoCard['data_hora_inicio'] ?? null)) ?></div>
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
                            Ainda não há sessões programadas. Assim que a agenda for publicada, elas aparecerão aqui.
                        </div>
                    <?php endif; ?>
                </div>
            </article>

            <aside class="space-y-5 xl:col-span-4 2xl:col-span-3">
                <article class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 bg-slate-50 px-6 py-5">
                        <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Recado</div>
                        <h2 class="mt-2 text-2xl font-semibold text-erp-navy">Recados da Loja</h2>
                    </div>
                    <div class="space-y-4 px-6 py-5">
                        <?php if ($dashboardRecadoPrincipal): ?>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500"><?= htmlspecialchars((string) (($dashboardRecadoPrincipal['status_publicacao'] ?? '') !== '' ? $dashboardRecadoPrincipal['status_publicacao'] : 'Recado')) ?></div>
                                <div class="mt-2 text-base font-semibold text-slate-900"><?= htmlspecialchars((string) (($dashboardRecadoPrincipal['titulo'] ?? '') !== '' ? $dashboardRecadoPrincipal['titulo'] : 'Comunicado recente da Loja')) ?></div>
                                <p class="mt-2 text-sm leading-6 text-slate-600"><?= htmlspecialchars($dashboardResumirTexto((string) ($dashboardRecadoPrincipal['conteudo'] ?? ''), 220)) ?></p>
                            </div>
                        <?php else: ?>
                            <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-4 text-sm text-slate-600">Nenhum recado recente disponível.</div>
                        <?php endif; ?>

                        <?php foreach ($dashboardRecadosSecundarios as $recadoSecundario): ?>
                            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                                <div class="text-sm font-semibold text-slate-800"><?= htmlspecialchars((string) (($recadoSecundario['titulo'] ?? '') !== '' ? $recadoSecundario['titulo'] : 'Recado complementar')) ?></div>
                                <div class="mt-1 text-sm text-slate-600"><?= htmlspecialchars($dashboardResumirTexto((string) ($recadoSecundario['conteudo'] ?? ''), 120)) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </article>

                <article class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 bg-slate-50 px-6 py-5">
                        <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Palavra do irmão</div>
                        <h2 class="mt-2 text-2xl font-semibold text-erp-navy">Palavra do dia</h2>
                    </div>
                    <div class="px-6 py-5">
                        <?php if ($dashboardPalavraIrmao !== ''): ?>
                            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm leading-6 text-slate-700"><?= nl2br(htmlspecialchars($dashboardResumirTexto($dashboardPalavraIrmao, 520))) ?></div>
                            <a href="/chancelaria/efemerides" class="mt-4 inline-flex rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:border-cobalto hover:text-cobalto">Abrir efemérides completas</a>
                        <?php else: ?>
                            <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-4 text-sm text-slate-600">Nenhuma efeméride registrada para hoje.</div>
                        <?php endif; ?>
                    </div>
                </article>

                <article class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 bg-slate-50 px-6 py-5">
                        <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Gestão</div>
                        <h2 class="mt-2 text-2xl font-semibold text-erp-navy">Nominata oficial</h2>
                    </div>
                    <div class="px-6 py-5">
                        <p class="text-sm leading-6 text-slate-600">A nominata continua disponível como área de gestão, sem ocupar o espaço principal da abertura.</p>
                        <a href="/admin/cargos" class="mt-4 inline-flex rounded-2xl bg-cobalto px-4 py-3 text-sm font-semibold text-white hover:bg-[#163761]">Abrir nominata e gestões</a>
                    </div>
                </article>
            </aside>
        </section>

        <section class="mt-8 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 bg-slate-50 px-6 py-6">
                <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Sessões de outras Lojas</div>
                <h2 class="mt-2 text-2xl font-semibold text-erp-navy">Sessões de outras Lojas</h2>
                <p class="mt-2 text-sm text-slate-600">Aqui ficam os convites e compromissos de outras Lojas, sem tirar o foco da agenda principal da Loja atual.</p>
            </div>
            <div class="grid gap-4 px-6 py-6 md:grid-cols-2 xl:grid-cols-3">
                <?php if ($dashboardOutrasLojas !== []): ?>
                    <?php foreach ($dashboardOutrasLojas as $sessaoExterna): ?>
                        <article class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                            <div class="text-sm font-semibold text-slate-900"><?= htmlspecialchars((string) ($sessaoExterna['loja'] ?? 'Outra Loja')) ?></div>
                            <div class="mt-1 text-sm text-slate-600"><?= htmlspecialchars((string) ($sessaoExterna['titulo'] ?? 'Sessão')) ?></div>
                            <div class="mt-2 text-xs text-slate-500"><?= htmlspecialchars($formatarDataHoraPainel($sessaoExterna['data_hora_inicio'] ?? null)) ?></div>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-4 text-sm text-slate-600 md:col-span-2 xl:col-span-3">
                        Nenhuma sessão externa registrada no momento.
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <?php if ($blocosPrioritarios !== []): ?>
            <section class="mt-8 grid gap-4 xl:grid-cols-12">
                <?php foreach ($blocosPrioritarios as $bloco): ?>
                    <article class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm xl:col-span-4">
                        <div class="border-b border-slate-100 bg-slate-50 px-5 py-5 sm:px-6">
                            <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400"><?= htmlspecialchars($bloco['perfil']) ?></div>
                            <h2 class="mt-2 text-2xl font-semibold text-erp-navy"><?= htmlspecialchars($bloco['titulo']) ?></h2>
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

        <section class="mt-8">
            <div class="rounded-3xl border border-slate-200 bg-white px-6 py-6 shadow-sm">
                <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Perfis e rotinas</div>
                <h2 class="mt-2 text-2xl font-semibold text-erp-navy">Menus por cargo</h2>
                <p class="mt-2 text-sm text-slate-600">Cada seção agrupa as rotinas do respectivo cargo. Se você acumula permissões (admin/venerável), verá mais seções.</p>
            </div>

            <div class="mt-6 grid gap-6 xl:grid-cols-12">
            <?php foreach ($secoes as $secao): ?>
                    <article class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm xl:col-span-6 2xl:col-span-4">
                        <div class="border-b border-slate-100 bg-slate-50 px-6 py-5">
                            <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Cargo</div>
                            <h2 class="mt-2 text-2xl font-semibold text-erp-navy"><?= htmlspecialchars($secao['titulo']) ?></h2>
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
            </div>
        </section>

        <section class="mt-8 rounded-3xl border border-slate-200 bg-white px-6 py-6 shadow-sm">
            <div>
                <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Acesso rápido</div>
                <h2 class="mt-2 text-2xl font-semibold text-erp-navy">Atalhos do dia a dia</h2>
                <p class="mt-2 text-sm text-slate-600">Use estes atalhos para chegar mais rápido ao que você mais consulta.</p>
            </div>
            <div class="mt-5 flex flex-wrap gap-3">
                <?php foreach ($atalhos as $item): ?>
                    <a href="<?= htmlspecialchars($item['href']) ?>" class="rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-sm font-medium text-slate-700 hover:border-cobalto hover:bg-white hover:text-cobalto"><?= htmlspecialchars($item['label']) ?></a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php if (false): ?>
    </main>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/partials/erp_shell_close.php'; ?>

<script>
(() => {
    const form = document.getElementById('assistente-ia-form');
    const input = document.getElementById('assistente-ia-comando');
    const resposta = document.getElementById('assistente-ia-resposta');
    const exemplos = document.querySelectorAll('[data-assistente-exemplo]');

    if (!form || !input || !resposta) {
        return;
    }

    const renderizarResultado = (resultado) => {
        resposta.classList.remove('hidden');
        const message = String(resultado?.message || 'Sem resposta do assistente.');
        const action = resultado?.action && resultado.action.target ? resultado.action : null;

        let html = '<div class="font-medium text-slate-800">' + message + '</div>';
        if (action) {
            const label = String(action.label || 'Abrir agora');
            const target = String(action.target);
            html += '<a href="' + target + '" class="mt-3 inline-flex rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:border-cobalto hover:text-cobalto">' + label + '</a>';
        }

        resposta.innerHTML = html;
    };

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const comando = input.value.trim();
        if (!comando) {
            return;
        }

        resposta.classList.remove('hidden');
        resposta.textContent = 'Interpretando...';

        try {
            const res = await fetch('/api/assistente/interpretar', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ comando }),
            });
            const json = await res.json();
            if (!json || !json.ok || !json.resultado) {
                throw new Error('Resposta invalida do assistente.');
            }
            renderizarResultado(json.resultado);
        } catch (error) {
            resposta.classList.remove('hidden');
            resposta.textContent = 'Nao foi possivel interpretar o comando agora.';
        }
    });

    exemplos.forEach((btn) => {
        btn.addEventListener('click', () => {
            const valor = btn.getAttribute('data-assistente-exemplo') || '';
            input.value = valor;
            input.focus();
        });
    });
})();
</script>

