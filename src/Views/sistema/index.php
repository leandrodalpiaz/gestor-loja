<?php
if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
}

$erpPageTitle = 'Sistema - Painel tÃ©cnico';
$appShellEyebrow = 'Sistema';
$appShellTitle = 'Painel do sistema';
$appShellDescription = 'Acesso tÃ©cnico (nÃ£o faz parte da nominata e nÃ£o representa cargo da Loja).';
$appShellActiveHref = '/sistema';
$appShellActions = [
    ['label' => 'Voltar ao Painel', 'href' => '/dashboard', 'primary' => true],
];
$appShellSidebarSections = [
    [
        'title' => 'Sistema',
        'items' => [
            ['label' => 'Painel do sistema', 'href' => '/sistema'],
            ['label' => 'ParÃ¢metros da Loja', 'href' => '/admin/loja'],
            ['label' => 'Auditoria', 'href' => '/admin/auditoria'],
            ['label' => 'Painel', 'href' => '/dashboard'],
        ],
    ],
];

require __DIR__ . '/../partials/erp_head.php';
require __DIR__ . '/../partials/erp_shell_open.php';
?>

<div class="space-y-6">
    <?php
    $dashboard = [
        'title' => 'Administrador do sistema (painel tecnico)',
        'subtitle' => 'Perfil tecnico separado dos cargos oficiais da Loja.',
        'meta' => ['Nao integra nominata da Loja', 'Uso tecnico de sustentacao'],
        'actions' => [
            ['label' => 'Ajustes da Loja', 'href' => '/admin/loja'],
            ['label' => 'Parametros', 'href' => '/admin/loja'],
            ['label' => 'Integracoes', 'href' => '/admin/conteudo-publico'],
        ],
        'blocks' => [
            ['title' => 'Estado do sistema', 'subtitle' => 'Saude administrativa do ambiente.', 'span' => 'half', 'metrics' => [
                ['label' => 'Painel tecnico', 'value' => 'Regular'],
                ['label' => 'Escopo', 'value' => 'Infra/operacao'],
            ], 'list' => [['item' => 'Separacao semantica', 'meta' => 'Nao misturar com cargos da Loja', 'status' => 'Obrigatorio']]],
            ['title' => 'Logs e auditoria tecnica', 'subtitle' => 'Rastreamento de operacoes criticas.', 'span' => 'half', 'metrics' => [
                ['label' => 'Auditoria', 'value' => 'DisponÃ­vel'],
                ['label' => 'Integracoes', 'value' => 'Gerenciaveis'],
            ], 'list' => [['item' => 'Auditoria tecnica', 'meta' => 'Leitura consolidada', 'status' => 'Regular'], ['item' => 'Parametros da Loja', 'meta' => 'Configuracao central', 'status' => 'Regular']]],
        ],
        'alerts' => [['title' => 'Separacao obrigatoria', 'text' => 'Administrador do sistema nao representa cargo oficial da Loja.', 'tone' => 'warning']],
        'activity' => [['item' => 'Acesso tecnico habilitado', 'meta' => 'Uso restrito ao painel do sistema']],
        'links' => [['label' => 'Auditoria', 'href' => '/admin/auditoria'], ['label' => 'Conteudo/Integracoes', 'href' => '/admin/conteudo-publico']],
    ];
    $dashboardRenderers = [
        static function (array $block): void { $dashboardMetrics = $block['metrics'] ?? []; $dashboardListItems = $block['list'] ?? []; require __DIR__ . '/../components/dashboard_metrics.php'; echo '<div class="mt-3">'; require __DIR__ . '/../components/dashboard_list.php'; echo '</div>'; },
        static function (array $block): void { $dashboardMetrics = $block['metrics'] ?? []; $dashboardListItems = $block['list'] ?? []; require __DIR__ . '/../components/dashboard_metrics.php'; echo '<div class="mt-3">'; require __DIR__ . '/../components/dashboard_list.php'; echo '</div>'; },
    ];
    require __DIR__ . '/../layouts/dashboard.php';
    ?>

    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Acesso tÃ©cnico</div>
        <h2 class="mt-2 text-2xl font-semibold text-erp-navy">Admin invisÃ­vel para a Loja</h2>
        <p class="mt-2 text-sm text-slate-700">
            Este painel existe apenas para suporte do sistema. Ele nÃ£o aparece como cargo, nÃ£o entra na nominata e nÃ£o deve ser usado como perfil de membro.
        </p>

        <div class="mt-5 grid gap-3 md:grid-cols-2">
            <a href="/admin/loja" class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm font-semibold text-slate-800 hover:bg-white hover:border-cobalto">
                ParÃ¢metros da Loja
                <div class="mt-1 text-xs font-normal text-slate-600">ConfiguraÃ§Ãµes centrais e identidade institucional.</div>
            </a>
            <a href="/admin/auditoria" class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm font-semibold text-slate-800 hover:bg-white hover:border-cobalto">
                Auditoria
                <div class="mt-1 text-xs font-normal text-slate-600">Leitura consolidada de aÃ§Ãµes crÃ­ticas.</div>
            </a>
        </div>
    </section>
</div>

<?php require __DIR__ . '/../partials/erp_shell_close.php'; ?>

