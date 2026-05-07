<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

$links = is_array($links ?? null) ? $links : [];

$pwaPageTitle = 'Gestao';
$pwaShowBackButton = true;
$pwaActiveTab = 'cargo';

ob_start();
?>

<div class="pwa-premium-page pwa-stack">
    <section class="pwa-hero p-5">
        <p class="pwa-eyebrow">Desktop + PWA</p>
        <h2 class="mt-3 text-2xl font-bold tracking-tight text-white">Modulos por cargo</h2>
        <p class="pwa-muted mt-2 text-sm leading-relaxed">
            Acesse os fluxos oficiais do PWA. Quando a acao ainda depende da tela completa, o card preserva o fluxo Desktop existente.
        </p>
    </section>

    <section class="space-y-3">
        <?php if (empty($links)): ?>
            <div class="rounded-2xl border border-white/10 bg-white/5 p-5 text-center">
                <p class="pwa-muted text-sm">Nenhum modulo de gestao disponivel para este usuario.</p>
            </div>
        <?php else: ?>
            <?php foreach ($links as $item): ?>
                <?php
                $label = (string) ($item['label'] ?? 'Modulo');
                $href = (string) ($item['href'] ?? '/pwa');
                $desc = (string) ($item['desc'] ?? '');
                ?>
                <a href="<?= htmlspecialchars($href) ?>" class="block rounded-2xl border border-white/10 bg-white/5 p-4 shadow-sm transition active:scale-[0.99]">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="text-base font-bold text-white"><?= htmlspecialchars($label) ?></div>
                            <?php if ($desc !== ''): ?>
                                <p class="pwa-muted mt-1 text-xs leading-relaxed"><?= htmlspecialchars($desc) ?></p>
                            <?php endif; ?>
                        </div>
                        <span class="shrink-0 rounded-full border border-emerald-300/30 bg-emerald-400/15 px-2.5 py-1 text-[0.6rem] font-bold tracking-[0.16em] text-emerald-100">
                            PWA
                        </span>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>
</div>

<?php
$pwaContent = ob_get_clean();
require __DIR__ . '/shell.php';
?>
