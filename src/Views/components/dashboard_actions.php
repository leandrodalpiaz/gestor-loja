<?php
$actions = is_array($dashboardActions ?? null) ? $dashboardActions : [];
?>
<?php if ($actions !== []): ?>
    <section class="rounded-erp-md rounded-xl border border-erp-border border-slate-200 bg-white px-5 py-4">
        <div class="flex flex-col gap-2 md:flex-row md:flex-wrap">
            <?php foreach ($actions as $index => $action): ?>
                <?php
                $label = (string) ($action['label'] ?? 'Abrir');
                $href = (string) ($action['href'] ?? '#');
                $primary = (bool) ($action['primary'] ?? false);
                $isMain = $index === 0 || $primary;
                ?>
                <a href="<?= htmlspecialchars($href) ?>" class="inline-flex items-center justify-center rounded-md border px-3 py-2 text-sm font-semibold <?= $isMain ? 'border-erp-navy border-slate-900 bg-erp-navy bg-slate-900 text-white' : 'border-erp-border border-slate-300 bg-white text-erp-text text-slate-800 hover:bg-slate-100' ?>">
                    <?= htmlspecialchars($label) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>
