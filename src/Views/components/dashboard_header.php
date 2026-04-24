<?php
$header = is_array($dashboardHeader ?? null) ? $dashboardHeader : [];
$title = trim((string) ($header['title'] ?? 'Painel'));
$subtitle = trim((string) ($header['subtitle'] ?? ''));
$meta = is_array($header['meta'] ?? null) ? $header['meta'] : [];
?>
<section class="rounded-erp-md rounded-xl border border-erp-border border-slate-200 bg-white px-5 py-4">
    <div class="text-xs font-semibold uppercase tracking-[0.2em] text-erp-muted text-slate-500">Visao do cargo</div>
    <h2 class="mt-1 text-2xl font-semibold text-erp-navy text-slate-900"><?= htmlspecialchars($title) ?></h2>
    <?php if ($subtitle !== ''): ?>
        <p class="mt-1 text-sm text-erp-muted text-slate-700"><?= htmlspecialchars($subtitle) ?></p>
    <?php endif; ?>
    <?php if ($meta !== []): ?>
        <div class="mt-3 flex flex-wrap gap-2">
            <?php foreach ($meta as $metaItem): ?>
                <span class="inline-flex rounded-md border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs text-slate-700"><?= htmlspecialchars((string) $metaItem) ?></span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

