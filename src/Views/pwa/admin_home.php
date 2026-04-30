<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

$links = is_array($links ?? null) ? $links : [];

$pwaPageTitle = 'Sistema';
$pwaShowBackButton = true;

ob_start();
?>

<div class="p-4 sm:p-6 space-y-4">
    <div class="rounded-2xl border border-erpBorder bg-erpSurface p-5">
        <h2 class="text-xl font-bold text-erpNavy">Atalhos do Sistema</h2>
        <p class="mt-1 text-sm text-erpMuted">
            Estes atalhos levam para os módulos de gestão completos (visão de desktop).
        </p>
    </div>

    <div class="space-y-3">
        <?php if (empty($links)): ?>
            <div class="rounded-2xl border border-erpBorder bg-erpSurface p-5 text-center">
                <p class="text-sm text-erpMuted">Nenhum atalho do sistema disponível.</p>
            </div>
        <?php else: ?>
            <?php foreach ($links as $item): ?>
                <?php
                $label = (string) ($item['label'] ?? 'Módulo');
                $href = (string) ($item['href'] ?? '/dashboard');
                $desc = (string) ($item['desc'] ?? '');
                ?>
                <a href="<?= htmlspecialchars($href) ?>" class="block rounded-2xl border border-erpBorder bg-erpSurface p-4 shadow-sm transition hover:border-erpNavy">
                    <div class="font-semibold text-erpNavy"><?= htmlspecialchars($label) ?></div>
                    <?php if ($desc !== ''): ?>
                        <p class="mt-1 text-sm text-erpMuted"><?= htmlspecialchars($desc) ?></p>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="text-center text-xs text-erpMuted">
        A migração completa dos painéis de gestão para o formato PWA será feita em fases futuras.
    </div>
</div>

<?php
$pwaContent = ob_get_clean();
require __DIR__ . '/shell.php';
?>

