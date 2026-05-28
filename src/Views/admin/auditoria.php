<?php
$itens = $itens ?? [];

$erpPageTitle = 'Auditoria administrativa';
$appShellEyebrow = 'Sistema';
$appShellTitle = 'Auditoria administrativa';
$appShellDescription = 'Leitura consolidada de alterações críticas da administração.';
$appShellActiveHref = '/admin/auditoria';
$appShellActions = [
    ['label' => 'Voltar ao painel', 'href' => '/dashboard', 'primary' => true],
];

$appShellSidebarSections = [
    [
        'title' => 'Secretaria',
        'items' => [
            ['label' => 'Nominata Oficial', 'href' => '/admin/cargos'],
            ['label' => 'Central de Obreiros', 'href' => '/obreiros'],
            ['label' => 'Convites de acesso', 'href' => '/admin/convites'],
            ['label' => 'Acessos', 'href' => '/admin/acessos'],
            ['label' => 'Sessões', 'href' => '/secretaria'],
            ['label' => 'Balaústres / votação', 'href' => '/secretaria/votacao'],
            ['label' => 'Relatório Anual', 'href' => '/secretaria/relatorio-anual'],
            ['label' => 'Painel', 'href' => '/dashboard'],
        ],
    ],
];

if (!empty($_SESSION['is_system_admin'])) {
    $appShellSidebarSections[] = [
        'title' => 'Sistema',
        'items' => [
            ['label' => 'Painel do sistema', 'href' => '/sistema'],
            ['label' => 'Parâmetros da Loja', 'href' => '/admin/loja'],
        ],
    ];
}

require __DIR__ . '/../partials/erp_head.php';
require __DIR__ . '/../partials/erp_shell_open.php';
?>

<div class="space-y-4">
    <?php if ($itens !== []): ?>
        <?php foreach ($itens as $item): ?>
            <article class="card depth-1 p-5">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="text-xs uppercase tracking-[0.2em] text-erp-gold font-bold"><?= htmlspecialchars((string) ($item['origem'] ?? 'admin')) ?> · <?= htmlspecialchars((string) ($item['entidade'] ?? 'evento')) ?></div>
                        <h2 class="mt-2 text-lg font-black text-white leading-snug"><?= htmlspecialchars((string) ($item['resumo'] ?? 'Registro administrativo')) ?></h2>
                    </div>
                    <div class="text-right text-xs text-slate-400 font-medium"><?= htmlspecialchars((string) ($item['created_at'] ?? '')) ?></div>
                </div>
                <div class="mt-3 text-sm text-slate-300">
                    Ação: <strong class="text-white"><?= htmlspecialchars((string) ($item['acao'] ?? '-')) ?></strong>
                    <?php if (!empty($item['criado_por_nome'])): ?>
                        · Por <strong class="text-white"><?= htmlspecialchars((string) $item['criado_por_nome']) ?></strong>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="card depth-1 p-6 text-sm text-slate-400">Nenhum registro de auditoria administrativa encontrado.</div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../partials/erp_shell_close.php'; ?>
