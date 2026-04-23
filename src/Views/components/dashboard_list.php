<?php
$listItems = is_array($dashboardListItems ?? null) ? $dashboardListItems : [];
?>
<?php if ($listItems === []): ?>
    <div class="rounded-md border border-dashed border-slate-300 px-3 py-3 text-sm text-slate-600">Sem itens no momento.</div>
<?php else: ?>
    <div class="space-y-2">
        <?php foreach ($listItems as $item): ?>
            <article class="rounded-md border border-slate-200 px-3 py-2">
                <div class="flex items-center justify-between gap-3">
                    <div class="text-sm font-medium text-slate-800"><?= htmlspecialchars((string) ($item['item'] ?? 'Item')) ?></div>
                    <?php if (!empty($item['status'])): ?>
                        <span class="inline-flex rounded-md border border-slate-200 bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-700"><?= htmlspecialchars((string) $item['status']) ?></span>
                    <?php endif; ?>
                </div>
                <?php if (!empty($item['meta'])): ?>
                    <div class="mt-1 text-xs text-slate-600"><?= htmlspecialchars((string) $item['meta']) ?></div>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
