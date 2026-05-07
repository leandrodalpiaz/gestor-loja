<?php
declare(strict_types=1);

$module = is_array($module ?? null) ? $module : [];
$title = (string) ($module['title'] ?? 'Modulo');
$summary = (string) ($module['summary'] ?? '');
$primary = (string) ($module['primary'] ?? '/pwa');
$actions = is_array($module['actions'] ?? null) ? $module['actions'] : [];

$pwaPageTitle = $title;
$pwaShowBackButton = true;
$pwaBackUrl = '/pwa/admin';
$pwaActiveTab = 'cargo';

ob_start();
?>

<div class="pwa-premium-page pwa-stack">
    <section class="pwa-hero p-5">
        <p class="pwa-eyebrow">Modulo PWA oficial</p>
        <h2 class="mt-3 text-2xl font-bold leading-tight tracking-tight text-white"><?= htmlspecialchars($title) ?></h2>
        <?php if ($summary !== ''): ?>
            <p class="pwa-muted mt-2 text-sm leading-relaxed"><?= htmlspecialchars($summary) ?></p>
        <?php endif; ?>
        <a href="<?= htmlspecialchars($primary) ?>" class="pwa-cta mt-5 inline-flex items-center justify-center rounded-2xl px-4 py-3 text-sm font-bold transition active:scale-95">
            Abrir fluxo principal
        </a>
    </section>

    <section>
        <div class="mb-3">
            <p class="pwa-eyebrow">Funcionalidades</p>
            <h3 class="mt-1 text-lg font-bold text-white">Acoes do cargo</h3>
        </div>

        <div class="space-y-3">
            <?php foreach ($actions as $action): ?>
                <?php
                $label = (string) ($action['label'] ?? 'Acao');
                $href = (string) ($action['href'] ?? '#');
                $kind = strtoupper((string) ($action['kind'] ?? 'pwa'));
                $kindClass = match ($kind) {
                    'PWA' => 'border-emerald-300/30 bg-emerald-400/15 text-emerald-100',
                    'API' => 'border-sky-300/30 bg-sky-400/15 text-sky-100',
                    default => 'border-amber-300/30 bg-amber-400/15 text-amber-100',
                };
                ?>
                <a href="<?= htmlspecialchars($href) ?>" class="block rounded-2xl border border-white/10 bg-white/5 p-4 shadow-sm transition active:scale-[0.99]">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="text-base font-bold text-white"><?= htmlspecialchars($label) ?></div>
                            <p class="pwa-muted mt-1 text-xs font-medium">
                                <?= $kind === 'DESKTOP' ? 'Fluxo completo reaproveitado do Desktop.' : ($kind === 'API' ? 'Endpoint operacional.' : 'Fluxo nativo do PWA.') ?>
                            </p>
                        </div>
                        <span class="shrink-0 rounded-full border px-2.5 py-1 text-[0.6rem] font-bold tracking-[0.16em] <?= $kindClass ?>">
                            <?= htmlspecialchars($kind) ?>
                        </span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="rounded-2xl border border-amber-300/20 bg-amber-300/10 p-4">
        <div class="text-sm font-semibold text-amber-100">Paridade em transicao</div>
        <p class="mt-1 text-xs leading-relaxed text-amber-100/80">
            A rota PWA deste cargo centraliza o acesso mobile oficial. Acoes marcadas como Desktop preservam a regra de negocio existente enquanto a tela PWA nativa completa e consolidada.
        </p>
    </section>
</div>

<?php
$pwaContent = ob_get_clean();
require __DIR__ . '/shell.php';
?>
