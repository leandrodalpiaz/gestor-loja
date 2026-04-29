<?php
$activity = is_array($dashboardActivity ?? null) ? $dashboardActivity : [];
?>
<?php if ($activity !== []): ?>
    <section class="rounded-erp-md border border-erp-border bg-white px-5 py-4">
        <h3 class="text-lg font-semibold text-erp-navy">Atividade recente</h3>
        <div class="mt-3 space-y-2">
            <?php foreach ($activity as $item): ?>
                <article class="rounded-md border border-slate-200 px-3 py-2">
                    <div class="text-sm text-slate-800"><?= htmlspecialchars((string) ($item['item'] ?? 'Atualização')) ?></div>
                    <?php if (!empty($item['meta'])): ?>
                        <div class="mt-1 text-xs text-slate-600"><?= htmlspecialchars((string) $item['meta']) ?></div>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>
