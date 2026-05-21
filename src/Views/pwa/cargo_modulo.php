<?php
declare(strict_types=1);

$module  = is_array($module ?? null) ? $module : [];
$title   = (string) ($module['title'] ?? 'Módulo');
$summary = (string) ($module['summary'] ?? '');
$primary = (string) ($module['primary'] ?? '/pwa');
$actions = is_array($module['actions'] ?? null) ? $module['actions'] : [];

$pwaPageTitle      = $title;
$pwaShowBackButton = true;
$pwaBackUrl        = '/pwa';
$pwaActiveTab      = 'cargo';

ob_start();
?>

<div class="pwa-premium-page">

    <!-- Hero do módulo -->
    <div class="pwa-hero" style="padding:1.5rem;margin-bottom:1.25rem;">
        <p class="pwa-eyebrow">Módulo PWA</p>
        <h2 style="font-size:1.375rem;font-weight:800;color:#f8fafc;margin:0.375rem 0 0;letter-spacing:-0.02em;">
            <?= htmlspecialchars($title) ?>
        </h2>
        <?php if ($summary !== ''): ?>
            <p class="pwa-muted" style="font-size:0.8125rem;margin:0.5rem 0 0;line-height:1.55;">
                <?= htmlspecialchars($summary) ?>
            </p>
        <?php endif; ?>
        <a href="<?= htmlspecialchars($primary) ?>" style="
            display:inline-flex;align-items:center;gap:0.5rem;
            margin-top:1.125rem;
            padding:0.75rem 1.375rem;
            background:rgba(201,162,39,0.25);
            border:1px solid rgba(201,162,39,0.45);
            border-radius:0.875rem;
            font-size:0.875rem;font-weight:700;
            color:#fde68a;text-decoration:none;
            box-shadow:0 0 20px rgba(201,162,39,0.15);
            transition:background 0.15s;
        ">
            Abrir fluxo principal
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
            </svg>
        </a>
    </div>

    <!-- Funcionalidades do cargo -->
    <?php if (!empty($actions)): ?>
    <div style="margin-bottom:1.25rem;">
        <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:0.75rem;">
            <p style="font-size:0.65rem;font-weight:700;letter-spacing:0.18em;text-transform:uppercase;color:#475569;margin:0;">
                Ações disponíveis
            </p>
            <div style="flex:1;height:1px;background:rgba(255,255,255,0.06);"></div>
        </div>

        <div style="display:flex;flex-direction:column;gap:0.5rem;">
            <?php foreach ($actions as $action): ?>
            <?php
            $label    = (string) ($action['label'] ?? 'Ação');
            $href     = (string) ($action['href'] ?? '#');
            $kind     = strtoupper((string) ($action['kind'] ?? 'pwa'));
            [$badgeBg, $badgeColor, $badgeText] = match ($kind) {
                'PWA'     => ['rgba(52,211,153,0.15)',  '#6ee7b7', 'PWA'],
                'API'     => ['rgba(56,189,248,0.15)',  '#7dd3fc', 'API'],
                'DESKTOP' => ['rgba(167,139,250,0.15)', '#c4b5fd', 'Desktop'],
                default   => ['rgba(201,162,39,0.15)',  '#fde68a', $kind],
            };
            $description = match ($kind) {
                'DESKTOP' => 'Fluxo completo via painel desktop.',
                'API'     => 'Endpoint operacional via API.',
                default   => 'Fluxo nativo do PWA.',
            };
            ?>
            <a href="<?= htmlspecialchars($href) ?>" style="
                display:flex;align-items:center;justify-content:space-between;gap:0.75rem;
                padding:0.875rem 1rem;
                background:rgba(255,255,255,0.05);
                border:1px solid rgba(255,255,255,0.09);
                border-radius:1rem;
                text-decoration:none;
                transition:background 0.12s;
                -webkit-tap-highlight-color:transparent;
            "
            onpointerdown="this.style.background='rgba(255,255,255,0.09)'"
            onpointerup="this.style.background='rgba(255,255,255,0.05)'"
            onpointercancel="this.style.background='rgba(255,255,255,0.05)'">
                <div style="min-width:0;">
                    <div style="font-size:0.9rem;font-weight:700;color:#f1f5f9;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                        <?= htmlspecialchars($label) ?>
                    </div>
                    <p style="font-size:0.72rem;color:#94a3b8;margin:0.1rem 0 0;font-weight:500;">
                        <?= $description ?>
                    </p>
                </div>
                <span style="
                    flex-shrink:0;
                    border-radius:999px;
                    padding:0.2rem 0.6rem;
                    font-size:0.6rem;font-weight:700;
                    letter-spacing:0.12em;
                    background:<?= $badgeBg ?>;color:<?= $badgeColor ?>;
                "><?= htmlspecialchars($badgeText) ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Nota de paridade -->
    <div style="
        border:1px solid rgba(201,162,39,0.18);
        background:rgba(201,162,39,0.07);
        border-radius:1rem;
        padding:0.875rem 1rem;
    ">
        <p style="font-size:0.75rem;font-weight:600;color:#fde68a;margin:0 0 0.25rem;">Em transição</p>
        <p style="font-size:0.72rem;color:rgba(253,230,138,0.65);margin:0;line-height:1.5;">
            Ações marcadas como Desktop preservam a regra de negócio existente enquanto o fluxo PWA nativo é consolidado.
        </p>
    </div>
</div>

<?php
$pwaContent = ob_get_clean();
require __DIR__ . '/shell.php';
?>
