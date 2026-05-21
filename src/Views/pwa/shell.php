<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

$usuarioNome = (string) ($_SESSION['usuario_nome'] ?? 'Irmão');
$usuarioCargo = strtolower(trim((string) ($_SESSION['usuario_cargo'] ?? '')));
$tenantSlug = (string) ($_SESSION['tenant_slug'] ?? '');
$logoUrl = \App\Core\Tenant\TenantAssetResolver::resolveLogo($tenantSlug);

$pwaPageTitle = (string) ($pwaPageTitle ?? 'Área do Irmão');
$pwaShowBackButton = (bool) ($pwaShowBackButton ?? false);
$pwaBackUrl = (string) ($pwaBackUrl ?? '/pwa');
$pwaActiveTab = (string) ($pwaActiveTab ?? 'inicio');

$cargoTabMap = [
    'secretario'        => ['href' => '/pwa/secretaria',         'label' => 'Secretaria'],
    'tesoureiro'        => ['href' => '/pwa/tesouraria',         'label' => 'Tesouraria'],
    'chanceler'         => ['href' => '/pwa/chancelaria',        'label' => 'Chancelaria'],
    'veneravel'         => ['href' => '/pwa/veneravel',          'label' => 'Venerável'],
    'orador'            => ['href' => '/pwa/orador',             'label' => 'Orador'],
    '1_vigilante'       => ['href' => '/pwa/primeiro-vigilante', 'label' => '1º Vig.'],
    '1 vigilante'       => ['href' => '/pwa/primeiro-vigilante', 'label' => '1º Vig.'],
    'primeiro_vigilante'=> ['href' => '/pwa/primeiro-vigilante', 'label' => '1º Vig.'],
    '2_vigilante'       => ['href' => '/pwa/segundo-vigilante',  'label' => '2º Vig.'],
    '2 vigilante'       => ['href' => '/pwa/segundo-vigilante',  'label' => '2º Vig.'],
    'segundo_vigilante' => ['href' => '/pwa/segundo-vigilante',  'label' => '2º Vig.'],
    'hospitaleiro'      => ['href' => '/pwa/hospitaleiro',       'label' => 'Hospitalar'],
    'mestre_banquetes'  => ['href' => '/pwa/mestre-banquetes',   'label' => 'Banquetes'],
    'mestre_harmonia'   => ['href' => '/pwa/mestre-harmonia',    'label' => 'Harmonia'],
    'admin'             => ['href' => '/pwa/admin',              'label' => 'Sistema'],
];
$cargoTab = $cargoTabMap[$usuarioCargo] ?? ['href' => '/pwa/obrigacoes', 'label' => 'Cargo'];

$pwaNavTabs = [
    [
        'id'        => 'inicio',
        'href'      => '/pwa',
        'label'     => 'Início',
        'icon'      => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />',
        'iconSolid' => '<path d="M11.47 3.84a.75.75 0 011.06 0l8.69 8.69a.75.75 0 101.06-1.06l-8.69-8.69a2.25 2.25 0 00-3.18 0l-8.69 8.69a.75.75 0 001.06 1.06l8.69-8.69z" /><path d="M12 5.43l8.25 8.25v6.2A2.12 2.12 0 0118.13 22h-3.38a.75.75 0 01-.75-.75v-4.5a.75.75 0 00-.75-.75h-2.5a.75.75 0 00-.75.75v4.5a.75.75 0 01-.75.75H5.88a2.12 2.12 0 01-2.13-2.12v-6.2L12 5.43z" />',
    ],
    [
        'id'        => 'cargo',
        'href'      => $cargoTab['href'],
        'label'     => $cargoTab['label'],
        'icon'      => '<path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />',
        'iconSolid' => '<path fill-rule="evenodd" d="M4.5 2.25A2.25 2.25 0 016.75 0h10.5a2.25 2.25 0 012.25 2.25V21h1.75a.75.75 0 010 1.5H2.75a.75.75 0 010-1.5H4.5V2.25zm5.25 4.5a.75.75 0 000 1.5h.75a.75.75 0 000-1.5h-.75zm0 4a.75.75 0 000 1.5h.75a.75.75 0 000-1.5h-.75zm4-4a.75.75 0 000 1.5h.75a.75.75 0 000-1.5h-.75zm0 4a.75.75 0 000 1.5h.75a.75.75 0 000-1.5h-.75zM10.5 21v-4.5h3V21h-3z" clip-rule="evenodd" />',
    ],
    [
        'id'        => 'biblioteca',
        'href'      => '/pwa/biblioteca',
        'label'     => 'Biblioteca',
        'icon'      => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />',
        'iconSolid' => '<path d="M11.25 4.53a8.25 8.25 0 00-6.38-.45A2.25 2.25 0 003.5 6.15v12.9a.75.75 0 001.02.7 6.75 6.75 0 015.66.25c.35.18.73.29 1.07.36V4.53z" /><path d="M12.75 20.36c.34-.07.72-.18 1.07-.36a6.75 6.75 0 015.66-.25.75.75 0 001.02-.7V6.15a2.25 2.25 0 00-1.37-2.07 8.25 8.25 0 00-6.38.45v15.83z" />',
    ],
    [
        'id'        => 'perfil',
        'href'      => '/pwa/perfil',
        'label'     => 'Perfil',
        'icon'      => '<path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />',
        'iconSolid' => '<path fill-rule="evenodd" d="M12 2.25a4.5 4.5 0 100 9 4.5 4.5 0 000-9zM4.5 20.12a7.5 7.5 0 0115 0 .63.63 0 01-.63.63H5.13a.63.63 0 01-.63-.63z" clip-rule="evenodd" />',
    ],
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, user-scalable=no">
    <meta name="theme-color" content="#020617">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Gestor Loja">
    <title><?= htmlspecialchars($pwaPageTitle) ?> — Gestor Loja</title>
    <link rel="manifest" href="/manifest.php">
    <link rel="stylesheet" href="/assets/css/tailwind.generated.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* ─── Reset & Base ─── */
        *, *::before, *::after { box-sizing: border-box; }
        body, html {
            height: 100%;
            overflow: hidden;
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
            background: #020617;
            -webkit-font-smoothing: antialiased;
        }

        /* ─── PWA Dark Design Tokens ─── */
        :root {
            --pwa-bg:          #020617;
            --pwa-surface:     rgba(255,255,255,0.055);
            --pwa-surface-2:   rgba(255,255,255,0.035);
            --pwa-border:      rgba(255,255,255,0.10);
            --pwa-border-soft: rgba(255,255,255,0.06);
            --pwa-text:        #f1f5f9;
            --pwa-muted:       #94a3b8;
            --pwa-gold:        #C9A227;
            --pwa-gold-glow:   rgba(201,162,39,0.28);
            --pwa-radius:      1.25rem;
            --pwa-radius-sm:   0.75rem;
        }

        /* ─── Layout Shell ─── */
        .app-container {
            display: flex;
            flex-direction: column;
            height: 100vh;
            height: 100dvh;
            background: var(--pwa-bg);
        }

        /* ─── Header ─── */
        .app-header {
            flex-shrink: 0;
            padding-top: env(safe-area-inset-top, 0px);
            background: rgba(2,6,23,0.92);
            backdrop-filter: blur(20px) saturate(1.4);
            -webkit-backdrop-filter: blur(20px) saturate(1.4);
            border-bottom: 1px solid var(--pwa-border);
            color: #fff;
            position: relative;
            z-index: 20;
        }
        .app-header-inner {
            display: flex;
            align-items: center;
            height: 52px;
            padding: 0 1rem;
            gap: 0.75rem;
        }
        .app-header-logo {
            width: 32px;
            height: 32px;
            border-radius: 10px;
            border: 1px solid rgba(255,255,255,0.14);
            background: rgba(255,255,255,0.08);
            padding: 4px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .app-header-title {
            flex: 1;
            min-width: 0;
            font-size: 0.9375rem;
            font-weight: 700;
            letter-spacing: -0.01em;
            color: #f8fafc;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .app-header-back {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            border: 1px solid var(--pwa-border);
            background: var(--pwa-surface);
            display: flex;
            align-items: center;
            justify-content: center;
            color: rgba(255,255,255,0.75);
            flex-shrink: 0;
            text-decoration: none;
            transition: background 0.15s;
        }
        .app-header-back:active { background: rgba(255,255,255,0.12); }

        /* ─── Scrollable Content ─── */
        .app-content {
            flex: 1;
            min-height: 0;
            overflow-x: hidden;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            overscroll-behavior-y: contain;
            background:
                radial-gradient(circle at 15% -5%, rgba(37,99,235,0.18) 0%, transparent 40%),
                radial-gradient(circle at 85% 12%, rgba(201,162,39,0.09) 0%, transparent 35%),
                linear-gradient(180deg, #020617 0%, #0c1525 50%, #020617 100%);
        }

        /* ─── Bottom Nav — Instagram style (compact 56px) ─── */
        .app-nav {
            flex-shrink: 0;
            padding-bottom: env(safe-area-inset-bottom, 0px);
            background: rgba(2,6,23,0.95);
            backdrop-filter: blur(20px) saturate(1.4);
            -webkit-backdrop-filter: blur(20px) saturate(1.4);
            border-top: 1px solid var(--pwa-border);
            position: relative;
            z-index: 20;
        }
        .app-nav-inner {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            height: 56px;
            padding: 0 0.25rem;
        }
        .nav-tab {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 3px;
            text-decoration: none;
            color: #475569;
            transition: color 0.15s;
            border-radius: 0;
            -webkit-tap-highlight-color: transparent;
            padding: 0 0.25rem;
        }
        .nav-tab:active { opacity: 0.7; }
        .nav-tab svg {
            width: 26px;
            height: 26px;
            transition: transform 0.15s;
        }
        .nav-tab:active svg { transform: scale(0.92); }
        .nav-tab-label {
            font-size: 0.6rem;
            font-weight: 600;
            letter-spacing: 0.01em;
            line-height: 1;
            white-space: nowrap;
        }
        .nav-tab-active {
            color: #f8fafc;
        }
        .nav-tab-active svg {
            filter: drop-shadow(0 0 6px rgba(201,162,39,0.5));
        }
        .nav-tab-dot {
            width: 4px;
            height: 4px;
            border-radius: 50%;
            background: var(--pwa-gold);
            position: absolute;
            bottom: 6px;
        }

        /* ─── Shared PWA Component Styles ─── */
        .pwa-scrollbar-none { scrollbar-width: none; }
        .pwa-scrollbar-none::-webkit-scrollbar { display: none; }

        /* Content padding for pages */
        .pwa-premium-page { padding: 1rem 1rem 1.5rem; }
        .pwa-stack > * + * { margin-top: 1.25rem; }

        /* Cards */
        .pwa-card {
            border: 1px solid var(--pwa-border);
            background: var(--pwa-surface);
            border-radius: var(--pwa-radius);
            box-shadow: 0 8px 24px rgba(2,6,23,0.28);
        }
        .pwa-card-sm {
            border: 1px solid var(--pwa-border);
            background: var(--pwa-surface);
            border-radius: var(--pwa-radius-sm);
        }

        /* Hero section */
        .pwa-hero {
            overflow: hidden;
            border: 1px solid var(--pwa-border);
            border-radius: 1.5rem;
            background: linear-gradient(135deg, #0f172a 0%, #1e2d45 50%, #0f172a 100%);
            color: #fff;
            box-shadow: 0 20px 50px rgba(2,6,23,0.45);
        }

        /* Typography helpers */
        .pwa-eyebrow {
            color: rgba(253,230,138,0.85);
            font-size: 0.65rem;
            font-weight: 700;
            letter-spacing: 0.22em;
            text-transform: uppercase;
        }
        .pwa-muted { color: var(--pwa-muted); }

        /* Glass surface */
        .pwa-glass {
            border: 1px solid var(--pwa-border);
            background: var(--pwa-surface);
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.07);
        }

        /* CTA button */
        .pwa-cta {
            background: #34d399;
            color: #020617;
            box-shadow: 0 0 22px rgba(52,211,153,0.35);
        }

        /* Status pills */
        .pwa-status-pill {
            border-radius: 999px;
            background: rgba(253,230,138,0.14);
            color: #fef3c7;
            font-size: 0.65rem;
            font-weight: 700;
            padding: 0.2rem 0.6rem;
        }

        /* Carousel */
        .pwa-carousel {
            display: flex;
            gap: 0.75rem;
            overflow-x: auto;
            padding: 0 1rem 0.5rem;
            scroll-padding-left: 1rem;
            scroll-snap-type: x mandatory;
            -webkit-overflow-scrolling: touch;
        }
        .pwa-carousel-card {
            min-width: 80%;
            border: 1px solid var(--pwa-border);
            border-radius: 1.25rem;
            background: var(--pwa-surface);
            box-shadow: 0 12px 30px rgba(2,6,23,0.25);
            scroll-snap-align: start;
            flex-shrink: 0;
        }

        /* Module/app icon grid */
        .pwa-module-card {
            aspect-ratio: 1 / 1;
            border: 1px solid var(--pwa-border);
            border-radius: 1.35rem;
            background: var(--pwa-surface);
            box-shadow: 0 8px 24px rgba(2,6,23,0.22);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: transform 0.15s, box-shadow 0.15s;
            -webkit-tap-highlight-color: transparent;
        }
        .pwa-module-card:active { transform: scale(0.94); }

        /* Form inputs — dark style */
        .pwa-input, .pwa-select, .pwa-textarea {
            width: 100%;
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--pwa-border);
            border-radius: var(--pwa-radius-sm);
            color: var(--pwa-text);
            padding: 0.625rem 0.875rem;
            font-size: 0.875rem;
            font-family: inherit;
            transition: border-color 0.15s, background 0.15s;
            -webkit-appearance: none;
            appearance: none;
        }
        .pwa-input::placeholder, .pwa-select::placeholder, .pwa-textarea::placeholder {
            color: var(--pwa-muted);
        }
        .pwa-input:focus, .pwa-select:focus, .pwa-textarea:focus {
            outline: none;
            border-color: var(--pwa-gold);
            background: rgba(255,255,255,0.07);
            box-shadow: 0 0 0 1px var(--pwa-gold);
        }
        .pwa-select option { background: #0f172a; color: var(--pwa-text); }
        .pwa-textarea { resize: vertical; min-height: 80px; }
        .pwa-label {
            display: block;
            font-size: 0.65rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--pwa-gold);
            margin-bottom: 0.35rem;
            opacity: 0.85;
        }
        .pwa-btn-primary {
            display: block;
            width: 100%;
            padding: 0.75rem 1rem;
            background: var(--pwa-gold);
            color: #0f172a;
            font-size: 0.875rem;
            font-weight: 700;
            border-radius: var(--pwa-radius-sm);
            border: none;
            cursor: pointer;
            font-family: inherit;
            transition: filter 0.15s, transform 0.1s;
        }
        .pwa-btn-primary:active { filter: brightness(0.9); transform: scale(0.98); }
        .pwa-btn-secondary {
            display: block;
            width: 100%;
            padding: 0.6875rem 1rem;
            background: var(--pwa-surface);
            color: var(--pwa-text);
            font-size: 0.875rem;
            font-weight: 600;
            border-radius: var(--pwa-radius-sm);
            border: 1px solid var(--pwa-border);
            cursor: pointer;
            font-family: inherit;
            text-align: center;
            text-decoration: none;
            transition: background 0.15s;
        }
        .pwa-btn-secondary:active { background: rgba(255,255,255,0.1); }

        /* Alerts */
        .pwa-alert-success {
            border: 1px solid rgba(52,211,153,0.3);
            background: rgba(52,211,153,0.12);
            border-radius: var(--pwa-radius-sm);
            color: #6ee7b7;
            font-size: 0.8125rem;
            font-weight: 500;
            padding: 0.75rem 1rem;
        }
        .pwa-alert-error {
            border: 1px solid rgba(248,113,113,0.3);
            background: rgba(248,113,113,0.1);
            border-radius: var(--pwa-radius-sm);
            color: #fca5a5;
            font-size: 0.8125rem;
            font-weight: 500;
            padding: 0.75rem 1rem;
        }

        /* Badge */
        .pwa-badge {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 0.18rem 0.55rem;
            font-size: 0.65rem;
            font-weight: 700;
            letter-spacing: 0.04em;
        }
        .pwa-badge-success { background: rgba(52,211,153,0.15); color: #6ee7b7; }
        .pwa-badge-warn    { background: rgba(253,230,138,0.15); color: #fde68a; }
        .pwa-badge-danger  { background: rgba(248,113,113,0.15); color: #fca5a5; }
        .pwa-badge-muted   { background: rgba(255,255,255,0.08); color: var(--pwa-muted); border: 1px solid var(--pwa-border); }

        /* Divider */
        .pwa-divider {
            height: 1px;
            background: var(--pwa-border);
            margin: 0;
        }

        /* List items */
        .pwa-list-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--pwa-border-soft);
        }
        .pwa-list-item:last-child { border-bottom: none; }
    </style>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-slate-950 text-slate-100 antialiased">
    <div class="app-container">
        <!-- ═══ HEADER ═══════════════════════════════════════════════ -->
        <header class="app-header">
            <div class="app-header-inner">
                <?php if ($pwaShowBackButton): ?>
                    <a href="<?= htmlspecialchars($pwaBackUrl) ?>" class="app-header-back" aria-label="Voltar">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                        </svg>
                    </a>
                <?php else: ?>
                    <div class="app-header-logo">
                        <?php if ($logoUrl): ?>
                            <img src="<?= htmlspecialchars($logoUrl) ?>" alt="Logo" style="width:100%;height:100%;object-fit:contain;">
                        <?php else: ?>
                            <span style="font-size:1rem;font-weight:900;color:#C9A227;line-height:1;">✦</span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <h1 class="app-header-title"><?= htmlspecialchars($pwaPageTitle) ?></h1>

                <a href="/pwa/perfil" aria-label="Perfil" style="
                    width:34px;height:34px;border-radius:50%;
                    background:rgba(201,162,39,0.15);
                    border:1.5px solid rgba(201,162,39,0.35);
                    display:flex;align-items:center;justify-content:center;
                    font-size:0.65rem;font-weight:800;color:#C9A227;
                    text-decoration:none;flex-shrink:0;letter-spacing:0;
                ">
                    <?php
                    $initials = '';
                    $parts = explode(' ', $usuarioNome);
                    if (count($parts) >= 2) {
                        $initials = mb_strtoupper(mb_substr($parts[0], 0, 1) . mb_substr(end($parts), 0, 1));
                    } else {
                        $initials = mb_strtoupper(mb_substr($usuarioNome, 0, 2));
                    }
                    echo htmlspecialchars($initials);
                    ?>
                </a>
            </div>
        </header>

        <!-- ═══ MAIN CONTENT ════════════════════════════════════════ -->
        <main class="app-content" id="pwa-main-content">
            <?= $pwaContent ?? '' ?>
        </main>

        <!-- ═══ BOTTOM NAV (always visible, Instagram-style) ════════ -->
        <nav class="app-nav" role="navigation" aria-label="Navegação principal">
            <div class="app-nav-inner">
                <?php foreach ($pwaNavTabs as $tab): ?>
                    <?php $isActive = $pwaActiveTab === $tab['id']; ?>
                    <a href="<?= htmlspecialchars($tab['href']) ?>"
                       class="nav-tab <?= $isActive ? 'nav-tab-active' : '' ?>"
                       aria-label="<?= htmlspecialchars($tab['label']) ?>"
                       <?= $isActive ? 'aria-current="page"' : '' ?>>
                        <svg xmlns="http://www.w3.org/2000/svg"
                             fill="<?= $isActive ? 'currentColor' : 'none' ?>"
                             viewBox="0 0 24 24"
                             stroke="<?= $isActive ? 'none' : 'currentColor' ?>"
                             stroke-width="1.8">
                            <?= $isActive ? ($tab['iconSolid'] ?? $tab['icon']) : $tab['icon'] ?>
                        </svg>
                        <span class="nav-tab-label"><?= htmlspecialchars($tab['label']) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </nav>
    </div>

    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js').catch(() => {
                console.warn('PWA service worker registration failed.');
            });
        }
    </script>
</body>
</html>
