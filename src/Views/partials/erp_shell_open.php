<?php
$appShellEyebrow = $appShellEyebrow ?? '';
$appShellTitle = $appShellTitle ?? 'Painel';
$appShellDescription = $appShellDescription ?? '';
$appShellActions = is_array($appShellActions ?? null) ? $appShellActions : [];
$appShellSidebarSections = is_array($appShellSidebarSections ?? null) ? $appShellSidebarSections : [];
$appShellActiveHref = (string) ($appShellActiveHref ?? '');
$appShellUserLabel = (string) ($appShellUserLabel ?? ($_SESSION['usuario_nome'] ?? 'Operador'));
?>
<body class="min-h-screen bg-erp-app font-sans text-erp-text">
<div class="min-h-screen lg:grid lg:grid-cols-[320px_minmax(0,1fr)]">
    <aside class="hidden border-r border-erp-border bg-white lg:flex lg:flex-col">
        <div class="border-b border-erp-border px-7 py-7">
            <div class="text-sm font-semibold uppercase tracking-[0.24em] text-erp-gold"><?= htmlspecialchars($appShellEyebrow !== '' ? $appShellEyebrow : 'Gestor-Loja') ?></div>
            <div class="mt-3 text-3xl font-semibold text-erp-navy">Painel ERP</div>
            <div class="mt-3 text-base leading-7 text-erp-muted">Operacao centralizada, acesso rapido e leitura administrativa consistente.</div>
        </div>
        <div class="flex-1 overflow-y-auto px-5 py-6">
            <?php foreach ($appShellSidebarSections as $section): ?>
                <div class="mb-7">
                    <div class="px-3 text-xs font-semibold uppercase tracking-[0.22em] text-erp-muted"><?= htmlspecialchars((string) ($section['title'] ?? 'Secao')) ?></div>
                    <div class="mt-2 space-y-1">
                        <?php foreach (($section['items'] ?? []) as $item): ?>
                            <?php $isActive = (string) ($item['href'] ?? '') === $appShellActiveHref; ?>
                            <a href="<?= htmlspecialchars((string) ($item['href'] ?? '#')) ?>" class="flex items-center justify-between rounded-erp-md px-4 py-3.5 text-base <?= $isActive ? 'bg-erp-navy text-white' : 'text-erp-text hover:bg-slate-100' ?>">
                                <span><?= htmlspecialchars((string) ($item['label'] ?? 'Item')) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="border-t border-erp-border px-7 py-5 text-base text-erp-muted">
            <div class="font-semibold text-erp-text"><?= htmlspecialchars($appShellUserLabel) ?></div>
            <div class="mt-1">Ambiente administrativo da Loja.</div>
        </div>
    </aside>

    <div class="min-w-0">
        <header class="border-b border-erp-border bg-white">
            <div class="flex w-full flex-col gap-4 px-6 py-6 sm:px-8 xl:flex-row xl:items-end xl:justify-between 2xl:px-10">
                <div>
                    <?php if ($appShellEyebrow !== ''): ?>
                        <div class="text-sm font-semibold uppercase tracking-[0.24em] text-erp-gold"><?= htmlspecialchars($appShellEyebrow) ?></div>
                    <?php endif; ?>
                    <h1 class="mt-2 text-4xl font-semibold text-erp-navy"><?= htmlspecialchars($appShellTitle) ?></h1>
                    <?php if ($appShellDescription !== ''): ?>
                        <p class="mt-3 max-w-4xl text-base leading-7 text-erp-muted"><?= htmlspecialchars($appShellDescription) ?></p>
                    <?php endif; ?>
                </div>
                <?php if ($appShellActions !== []): ?>
                    <div class="flex flex-wrap gap-3">
                        <?php foreach ($appShellActions as $action): ?>
                            <a href="<?= htmlspecialchars((string) ($action['href'] ?? '#')) ?>" class="rounded-erp-md border <?= !empty($action['primary']) ? 'border-erp-navy bg-erp-navy text-white' : 'border-erp-border bg-white text-erp-text' ?> px-4 py-2.5 text-base font-semibold">
                                <?= htmlspecialchars((string) ($action['label'] ?? 'Abrir')) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </header>

        <main class="w-full px-6 py-7 sm:px-8 2xl:px-10">
