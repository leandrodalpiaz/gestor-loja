<?php
$alerts = is_array($dashboardAlerts ?? null) ? $dashboardAlerts : [];
?>
<?php if ($alerts !== []): ?>
    <section class="rounded-erp-md border border-erp-border bg-white px-5 py-4">
        <h3 class="text-lg font-semibold text-erp-navy">Alertas e pendências</h3>
        <div class="mt-3 space-y-2">
            <?php foreach ($alerts as $alert): ?>
                <?php $tone = (string) ($alert['tone'] ?? 'warning'); ?>
                <?php
                $classes = match ($tone) {
                    'danger' => 'border-rose-200 bg-rose-50 text-rose-800',
                    'success' => 'border-emerald-200 bg-emerald-50 text-emerald-800',
                    default => 'border-amber-200 bg-amber-50 text-amber-900',
                };
                ?>
                <article class="rounded-md border px-3 py-2 <?= $classes ?>">
                    <div class="text-sm font-semibold"><?= htmlspecialchars((string) ($alert['title'] ?? 'Alerta')) ?></div>
                    <?php if (!empty($alert['text'])): ?>
                        <div class="mt-1 text-xs"><?= htmlspecialchars((string) $alert['text']) ?></div>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>
