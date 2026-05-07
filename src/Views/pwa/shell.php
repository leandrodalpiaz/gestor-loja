<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

$usuarioNome = (string) ($_SESSION['usuario_nome'] ?? 'Irmao');
$usuarioCargo = strtolower(trim((string) ($_SESSION['usuario_cargo'] ?? '')));
$tenantSlug = (string) ($_SESSION['tenant_slug'] ?? '');
$logoUrl = \App\Core\Tenant\TenantAssetResolver::resolveLogo($tenantSlug);

$pwaPageTitle = (string) ($pwaPageTitle ?? 'Acesso Rapido');
$pwaShowBackButton = (bool) ($pwaShowBackButton ?? false);
$pwaBackUrl = (string) ($pwaBackUrl ?? '/pwa');
$pwaActiveTab = (string) ($pwaActiveTab ?? 'inicio');

$cargoTabMap = [
    'secretario' => ['href' => '/pwa/secretaria', 'label' => 'Secretaria'],
    'tesoureiro' => ['href' => '/pwa/tesouraria', 'label' => 'Tesouraria'],
    'chanceler' => ['href' => '/pwa/chancelaria', 'label' => 'Chancelaria'],
    'veneravel' => ['href' => '/pwa/veneravel', 'label' => 'Veneravel'],
    'orador' => ['href' => '/pwa/orador', 'label' => 'Orador'],
    '1_vigilante' => ['href' => '/pwa/primeiro-vigilante', 'label' => '1o Vigilante'],
    '1 vigilante' => ['href' => '/pwa/primeiro-vigilante', 'label' => '1o Vigilante'],
    'primeiro_vigilante' => ['href' => '/pwa/primeiro-vigilante', 'label' => '1o Vigilante'],
    '2_vigilante' => ['href' => '/pwa/segundo-vigilante', 'label' => '2o Vigilante'],
    '2 vigilante' => ['href' => '/pwa/segundo-vigilante', 'label' => '2o Vigilante'],
    'segundo_vigilante' => ['href' => '/pwa/segundo-vigilante', 'label' => '2o Vigilante'],
    'hospitaleiro' => ['href' => '/pwa/hospitaleiro', 'label' => 'Hospitaleiro'],
    'mestre_banquetes' => ['href' => '/pwa/mestre-banquetes', 'label' => 'Banquetes'],
    'mestre_harmonia' => ['href' => '/pwa/mestre-harmonia', 'label' => 'Harmonia'],
    'admin' => ['href' => '/pwa/admin', 'label' => 'Sistema'],
];
$cargoTab = $cargoTabMap[$usuarioCargo] ?? ['href' => '/pwa/obrigacoes', 'label' => 'Obrigacoes'];

$pwaNavTabs = [
    [
        'id' => 'inicio',
        'href' => '/pwa',
        'label' => 'Inicio',
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />',
        'iconSolid' => '<path d="M11.47 3.84a.75.75 0 011.06 0l8.69 8.69a.75.75 0 101.06-1.06l-8.69-8.69a2.25 2.25 0 00-3.18 0l-8.69 8.69a.75.75 0 001.06 1.06l8.69-8.69z" /><path d="M12 5.43l8.25 8.25v6.2A2.12 2.12 0 0118.13 22h-3.38a.75.75 0 01-.75-.75v-4.5a.75.75 0 00-.75-.75h-2.5a.75.75 0 00-.75.75v4.5a.75.75 0 01-.75.75H5.88a2.12 2.12 0 01-2.13-2.12v-6.2L12 5.43z" />',
    ],
    [
        'id' => 'cargo',
        'href' => $cargoTab['href'],
        'label' => $cargoTab['label'],
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />',
        'iconSolid' => '<path fill-rule="evenodd" d="M4.5 2.25A2.25 2.25 0 016.75 0h10.5a2.25 2.25 0 012.25 2.25V21h1.75a.75.75 0 010 1.5H2.75a.75.75 0 010-1.5H4.5V2.25zm5.25 4.5a.75.75 0 000 1.5h.75a.75.75 0 000-1.5h-.75zm0 4a.75.75 0 000 1.5h.75a.75.75 0 000-1.5h-.75zm4-4a.75.75 0 000 1.5h.75a.75.75 0 000-1.5h-.75zm0 4a.75.75 0 000 1.5h.75a.75.75 0 000-1.5h-.75zM10.5 21v-4.5h3V21h-3z" clip-rule="evenodd" />',
    ],
    [
        'id' => 'biblioteca',
        'href' => '/pwa/biblioteca',
        'label' => 'Biblioteca',
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />',
        'iconSolid' => '<path d="M11.25 4.53a8.25 8.25 0 00-6.38-.45A2.25 2.25 0 003.5 6.15v12.9a.75.75 0 001.02.7 6.75 6.75 0 015.66.25c.35.18.73.29 1.07.36V4.53z" /><path d="M12.75 20.36c.34-.07.72-.18 1.07-.36a6.75 6.75 0 015.66-.25.75.75 0 001.02-.7V6.15a2.25 2.25 0 00-1.37-2.07 8.25 8.25 0 00-6.38.45v15.83z" />',
    ],
    [
        'id' => 'perfil',
        'href' => '/pwa/perfil',
        'label' => 'Perfil',
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />',
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
    <title><?= htmlspecialchars($pwaPageTitle) ?> - Gestor Loja</title>
    <link rel="manifest" href="/manifest.php">
    <link rel="stylesheet" href="/assets/css/tailwind.generated.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body, html {
            height: 100%;
            overflow: hidden;
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
            background: #020617;
        }
        .app-container {
            display: flex;
            flex-direction: column;
            height: 100vh;
            height: 100dvh;
            background: #020617;
        }
        .app-header {
            flex-shrink: 0;
            padding-top: env(safe-area-inset-top, 0px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.10);
            background: #020617;
            color: #fff;
            box-shadow: 0 10px 30px rgba(2, 6, 23, 0.45);
        }
        .app-content {
            flex-grow: 1;
            overflow-x: hidden;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            overscroll-behavior-y: contain;
            background:
                radial-gradient(circle at 14% -6%, rgba(37, 99, 235, 0.25), transparent 30rem),
                radial-gradient(circle at 85% 18%, rgba(201, 162, 39, 0.12), transparent 24rem),
                linear-gradient(180deg, #020617 0%, #0f172a 48%, #020617 100%);
        }
        .app-nav {
            flex-shrink: 0;
            padding-bottom: env(safe-area-inset-bottom, 0px);
            border-top: 1px solid rgba(255, 255, 255, 0.10);
            background: #020617;
            box-shadow: 0 -18px 40px rgba(2, 6, 23, 0.72);
        }
        .app-nav a {
            color: #64748b;
        }
        .pwa-tab-active {
            color: #f8fafc;
            border-radius: 1rem;
            background: rgba(255, 255, 255, 0.10);
        }
        .pwa-tab-active .pwa-tab-dot {
            opacity: 1;
        }
        .pwa-scrollbar-none {
            scrollbar-width: none;
        }
        .pwa-scrollbar-none::-webkit-scrollbar {
            display: none;
        }
        .pwa-premium-page {
            padding: 1rem 1rem 2rem;
        }
        .pwa-stack > * + * {
            margin-top: 1.5rem;
        }
        .pwa-card {
            border: 1px solid rgba(255, 255, 255, 0.10);
            background: rgba(255, 255, 255, 0.06);
            border-radius: 1.6rem;
            box-shadow: 0 18px 45px rgba(2, 6, 23, 0.32);
        }
        .pwa-hero {
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.10);
            border-radius: 1.9rem;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 48%, #0f172a 100%);
            color: #fff;
            box-shadow: 0 28px 70px rgba(2, 6, 23, 0.48);
        }
        .pwa-eyebrow {
            color: rgba(253, 230, 138, 0.84);
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 0.22em;
            text-transform: uppercase;
        }
        .pwa-glass {
            border: 1px solid rgba(255, 255, 255, 0.10);
            background: rgba(255, 255, 255, 0.09);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.08);
        }
        .pwa-muted {
            color: #94a3b8;
        }
        .pwa-cta {
            background: #34d399;
            color: #020617;
            box-shadow: 0 0 26px rgba(52, 211, 153, 0.38);
        }
        .pwa-carousel {
            display: flex;
            gap: 0.75rem;
            margin-left: -1rem;
            margin-right: -1rem;
            overflow-x: auto;
            padding: 0 1rem 0.5rem;
            scroll-padding-left: 1rem;
            scroll-snap-type: x mandatory;
            -webkit-overflow-scrolling: touch;
        }
        .pwa-carousel-card {
            min-width: 82%;
            border: 1px solid rgba(255, 255, 255, 0.10);
            border-radius: 1.4rem;
            background: rgba(255, 255, 255, 0.06);
            box-shadow: 0 18px 42px rgba(2, 6, 23, 0.28);
            scroll-snap-align: start;
        }
        .pwa-status-pill {
            border-radius: 999px;
            background: rgba(253, 230, 138, 0.16);
            color: #fef3c7;
        }
        .pwa-confirm-pill {
            border: 1px solid rgba(253, 230, 138, 0.30);
            background: rgba(253, 230, 138, 0.14);
            color: #fef3c7;
        }
        .pwa-module-card {
            aspect-ratio: 1 / 1;
            border: 1px solid rgba(255, 255, 255, 0.10);
            border-radius: 1.35rem;
            background: rgba(255, 255, 255, 0.06);
            box-shadow: 0 14px 36px rgba(2, 6, 23, 0.24);
        }
    </style>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-slate-950 text-slate-100 antialiased">
    <div class="app-container">
        <header class="app-header z-10 border-b border-white/10 bg-slate-950 text-white shadow-[0_10px_30px_rgba(2,6,23,0.45)]">
            <div class="mx-auto flex h-14 max-w-5xl items-center justify-between px-4">
                <div class="flex min-w-0 items-center gap-3">
                    <?php if ($pwaShowBackButton): ?>
                        <a href="<?= htmlspecialchars($pwaBackUrl) ?>" class="-ml-2 rounded-full border border-white/10 bg-white/5 p-2 text-white/80 transition active:scale-95">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                            </svg>
                        </a>
                    <?php else: ?>
                        <div class="h-9 w-9 shrink-0 rounded-xl border border-white/15 bg-white/10 p-1 shadow-inner shadow-white/10">
                            <?php if ($logoUrl): ?>
                                <img src="<?= htmlspecialchars($logoUrl) ?>" alt="Logo" class="h-full w-full object-contain">
                            <?php else: ?>
                                <span class="flex h-full w-full items-center justify-center text-base font-bold text-amber-300">*</span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <h1 class="truncate text-sm font-semibold tracking-wide text-slate-100"><?= htmlspecialchars($pwaPageTitle) ?></h1>
                </div>
                <button class="rounded-full border border-white/10 bg-white/5 p-2 text-white/60 transition-colors hover:text-white" aria-label="Notificacoes">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                </button>
            </div>
        </header>

        <main class="app-content">
            <?= $pwaContent ?? '' ?>
        </main>

        <nav class="app-nav border-t border-white/10 bg-slate-950 shadow-[0_-18px_40px_rgba(2,6,23,0.72)]">
            <div class="mx-auto grid max-w-5xl grid-cols-4 items-center justify-items-center px-2 py-2">
                <?php foreach ($pwaNavTabs as $tab): ?>
                    <?php $isActive = $pwaActiveTab === $tab['id']; ?>
                    <a href="<?= htmlspecialchars($tab['href']) ?>"
                       class="flex w-full flex-col items-center rounded-2xl px-1 py-2 transition-colors <?= $isActive ? 'pwa-tab-active bg-white/10' : 'text-slate-500 hover:text-slate-200' ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="<?= $isActive ? 'currentColor' : 'none' ?>" viewBox="0 0 24 24" stroke="<?= $isActive ? 'none' : 'currentColor' ?>" stroke-width="1.8">
                            <?= $isActive ? ($tab['iconSolid'] ?? $tab['icon']) : $tab['icon'] ?>
                        </svg>
                        <span class="mt-1 text-[0.62rem] font-semibold leading-tight tracking-tight"><?= htmlspecialchars($tab['label']) ?></span>
                        <div class="pwa-tab-dot mt-0.5 h-1 w-1 rounded-full bg-amber-300 <?= $isActive ? 'opacity-100' : 'opacity-0' ?>"></div>
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
