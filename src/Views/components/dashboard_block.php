<?php
$block = is_array($dashboardBlock ?? null) ? $dashboardBlock : [];
$title = trim((string) ($block['title'] ?? 'Bloco'));
$subtitle = trim((string) ($block['subtitle'] ?? ''));
$span = (string) ($block['span'] ?? 'half');
$containerClass = $span === 'full' ? 'md:col-span-2' : 'md:col-span-1';
?>
<section class="<?= $containerClass ?> rounded-erp-md rounded-xl border border-erp-border border-slate-200 bg-white px-5 py-4">
    <header class="mb-3">
        <h3 class="text-lg font-semibold text-erp-navy text-slate-900"><?= htmlspecialchars($title) ?></h3>
        <?php if ($subtitle !== ''): ?>
            <p class="mt-1 text-sm text-erp-muted text-slate-700"><?= htmlspecialchars($subtitle) ?></p>
        <?php endif; ?>
    </header>
    <?= $dashboardBlockContent ?? '' ?>
</section>
