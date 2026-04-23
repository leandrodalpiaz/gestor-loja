<?php
if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
}

$erpPageTitle = 'Sistema - Painel técnico';
$appShellEyebrow = 'Sistema';
$appShellTitle = 'Painel do sistema';
$appShellDescription = 'Acesso técnico (não faz parte da nominata e não representa cargo da Loja).';
$appShellActiveHref = '/sistema';
$appShellActions = [
    ['label' => 'Voltar ao dashboard', 'href' => '/dashboard', 'primary' => true],
];
$appShellSidebarSections = [
    [
        'title' => 'Sistema',
        'items' => [
            ['label' => 'Painel do sistema', 'href' => '/sistema'],
            ['label' => 'Parâmetros da Loja', 'href' => '/admin/loja'],
            ['label' => 'Auditoria', 'href' => '/admin/auditoria'],
            ['label' => 'Dashboard', 'href' => '/dashboard'],
        ],
    ],
];

require __DIR__ . '/../partials/erp_head.php';
require __DIR__ . '/../partials/erp_shell_open.php';
?>

<div class="space-y-6">
    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Acesso técnico</div>
        <h2 class="mt-2 text-2xl font-semibold text-erp-navy">Admin invisível para a Loja</h2>
        <p class="mt-2 text-sm text-slate-700">
            Este painel existe apenas para suporte do sistema. Ele não aparece como cargo, não entra na nominata e não deve ser usado como perfil de membro.
        </p>

        <div class="mt-5 grid gap-3 md:grid-cols-2">
            <a href="/admin/loja" class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm font-semibold text-slate-800 hover:bg-white hover:border-cobalto">
                Parâmetros da Loja
                <div class="mt-1 text-xs font-normal text-slate-600">Configurações centrais e identidade institucional.</div>
            </a>
            <a href="/admin/auditoria" class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm font-semibold text-slate-800 hover:bg-white hover:border-cobalto">
                Auditoria
                <div class="mt-1 text-xs font-normal text-slate-600">Leitura consolidada de ações críticas.</div>
            </a>
        </div>
    </section>
</div>

<?php require __DIR__ . '/../partials/erp_shell_close.php'; ?>

