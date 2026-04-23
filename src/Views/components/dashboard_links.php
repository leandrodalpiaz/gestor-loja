<?php
$links = is_array($dashboardLinks ?? null) ? $dashboardLinks : [];
?>
<?php if ($links !== []): ?>
    <section class="rounded-erp-md border border-erp-border bg-white px-5 py-4">
        <h3 class="text-lg font-semibold text-erp-navy">Acessos secundarios</h3>
        <div class="mt-3 flex flex-wrap gap-2">
            <?php foreach ($links as $link): ?>
                <a href="<?= htmlspecialchars((string) ($link['href'] ?? '#')) ?>" class="inline-flex rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-50">
                    <?= htmlspecialchars((string) ($link['label'] ?? 'Abrir')) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>
