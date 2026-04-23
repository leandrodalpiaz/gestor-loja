<?php
$items = is_array($dashboardMetrics ?? null) ? $dashboardMetrics : [];
?>
<?php if ($items !== []): ?>
    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <?php foreach ($items as $item): ?>
            <article class="rounded-md border border-slate-200 bg-slate-50 px-3 py-3">
                <div class="text-xs uppercase tracking-[0.16em] text-slate-600"><?= htmlspecialchars((string) ($item['label'] ?? 'Indicador')) ?></div>
                <div class="mt-1 text-2xl font-semibold text-erp-navy"><?= htmlspecialchars((string) ($item['value'] ?? '-')) ?></div>
                <?php if (!empty($item['hint'])): ?>
                    <div class="mt-1 text-xs text-slate-600"><?= htmlspecialchars((string) $item['hint']) ?></div>
                <?php endif; ?>
                <?php if (!empty($item['trend'])): ?>
                    <div class="mt-1 text-xs font-semibold text-slate-700"><?= htmlspecialchars((string) $item['trend']) ?></div>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
