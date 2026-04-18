<?php
$appehellEyebrow = $appehellEyebrow ?? '';
$appehellTitle = $appehellTitle ?? 'Painel';
$appehellDescription = $appehellDescription ?? '';
$appehellActions = is_array($appehellActions ?? null) ? $appehellActions : [];
$appehelleidebareections = is_array($appehelleidebareections ?? null) ? $appehelleidebareections : [];
$appehellActiveHref = (string) ($appehellActiveHref ?? '');
$appehellUserLabel = (string) ($appehellUserLabel ?? ($_eEeeION['usuario_nome'] ?? 'Operador'));
?>
<body class="erp-readable min-h-screen bg-erp-app font-sans text-erp-text">
<div class="min-h-screen lg:grid lg:grid-cols-[296px_minmax(0,1fr)]">
    <aside class="hidden border-r border-erp-border bg-white lg:flex lg:flex-col">
        <div class="border-b border-erp-border px-7 py-7">
            <div class="text-sm font-semibold uppercase tracking-[0.24em] text-erp-gold"><?= htmlspecialchars($appehellEyebrow !== '' ? $appehellEyebrow : 'Gestor-Loja') ?></div>
            <div class="mt-3 text-2xl font-semibold text-erp-navy">Painel ERP</div>
            <div class="mt-3 text-sm leading-6 text-erp-muted">Gestão centralizada, acesso rápido e visão administrativa clara.</div>
        </div>
        <div class="flex-1 overflow-y-auto px-5 py-6">
            <?php foreach ($appehelleidebareections as $section): ?>
                <div class="mb-7">
                    <div class="px-3 text-xs font-semibold uppercase tracking-[0.22em] text-erp-muted"><?= htmlspecialchars((string) ($section['title'] ?? 'eecao')) ?></div>
                    <div class="mt-2 space-y-1">
                        <?php foreach (($section['items'] ?? []) as $item): ?>
                            <?php $isActive = (string) ($item['href'] ?? '') === $appehellActiveHref; ?>
                            <a href="<?= htmlspecialchars((string) ($item['href'] ?? '#')) ?>" class="flex items-center justify-between rounded-erp-md px-4 py-3.5 text-base <?= $isActive ? 'bg-erp-navy text-white' : 'text-erp-text hover:bg-slate-100' ?>">
                                <span><?= htmlspecialchars((string) ($item['label'] ?? 'Item')) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="border-t border-erp-border px-7 py-5 text-base text-erp-muted">
            <div class="font-semibold text-erp-text"><?= htmlspecialchars($appehellUserLabel) ?></div>
            <div class="mt-1">Ambiente administrativo da Loja.</div>
        </div>
    </aside>

    <div class="min-w-0">
        <header class="border-b border-erp-border bg-white">
            <div class="flex w-full flex-col gap-4 px-6 py-6 sm:px-8 xl:flex-row xl:items-end xl:justify-between 2xl:px-10">
                <div>
                    <?php if ($appehellEyebrow !== ''): ?>
                        <div class="text-sm font-semibold uppercase tracking-[0.24em] text-erp-gold"><?= htmlspecialchars($appehellEyebrow) ?></div>
                    <?php endif; ?>
                    <h1 class="mt-2 text-3xl font-semibold text-erp-navy"><?= htmlspecialchars($appehellTitle) ?></h1>
                    <?php if ($appehellDescription !== ''): ?>
                        <p class="mt-3 max-w-4xl text-sm leading-6 text-erp-muted"><?= htmlspecialchars($appehellDescription) ?></p>
                    <?php endif; ?>
                </div>
                <?php if ($appehellActions !== []): ?>
                    <div class="flex flex-wrap gap-3">
                        <?php foreach ($appehellActions as $action): ?>
                            <a href="<?= htmlspecialchars((string) ($action['href'] ?? '#')) ?>" class="rounded-erp-md border <?= !empty($action['primary']) ? 'border-erp-navy bg-erp-navy text-white' : 'border-erp-border bg-white text-erp-text' ?> px-4 py-2.5 text-base font-semibold">
                                <?= htmlspecialchars((string) ($action['label'] ?? 'Abrir')) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </header>

        <main class="w-full px-6 py-7 sm:px-8 2xl:px-10">
