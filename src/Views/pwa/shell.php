<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

$usuarioNome = (string) ($_SESSION['usuario_nome'] ?? 'Irmão');
$usuarioCargo = strtolower(trim((string) ($_SESSION['usuario_cargo'] ?? '')));
$tenantSlug = (string) ($_SESSION['tenant_slug'] ?? '');
$logoUrl = \App\Core\Tenant\TenantAssetResolver::resolveLogo($tenantSlug);

// Parâmetros da página (a serem definidos pela view que inclui o shell)
$pwaPageTitle = (string) ($pwaPageTitle ?? 'Acesso Rápido');
$pwaShowBackButton = (bool) ($pwaShowBackButton ?? false);
$pwaBackUrl = (string) ($pwaBackUrl ?? '/pwa');
$pwaActiveTab = (string) ($pwaActiveTab ?? 'inicio');

// ─── Aba dinâmica "Cargo" ───────────────────────────────────────────────
// Mapeia o cargo do obreiro para a rota e label mais relevantes no PWA.
// Obreiros sem cargo oficial veem "Obrigações" (financeiro pessoal).
$cargoTabMap = [
    'secretario'       => ['href' => '/pwa/sessoes',      'label' => 'Secretaria'],
    'tesoureiro'       => ['href' => '/pwa/comprovantes', 'label' => 'Tesouraria'],
    'chanceler'        => ['href' => '/pwa/comunicacao',   'label' => 'Chancelaria'],
    'veneravel'        => ['href' => '/dashboard',         'label' => 'Venerável'],
    'orador'           => ['href' => '/pwa/comunicacao',   'label' => 'Orador'],
    '1_vigilante'      => ['href' => '/pwa/sessoes',       'label' => '1º Vigilante'],
    '1 vigilante'      => ['href' => '/pwa/sessoes',       'label' => '1º Vigilante'],
    'primeiro_vigilante' => ['href' => '/pwa/sessoes',     'label' => '1º Vigilante'],
    '2_vigilante'      => ['href' => '/pwa/sessoes',       'label' => '2º Vigilante'],
    '2 vigilante'      => ['href' => '/pwa/sessoes',       'label' => '2º Vigilante'],
    'segundo_vigilante' => ['href' => '/pwa/sessoes',      'label' => '2º Vigilante'],
    'hospitaleiro'     => ['href' => '/dashboard',         'label' => 'Hospitaleiro'],
    'mestre_banquetes' => ['href' => '/dashboard',         'label' => 'Banquetes'],
    'mestre_harmonia'  => ['href' => '/dashboard',         'label' => 'Harmonia'],
    'admin'            => ['href' => '/pwa/admin',         'label' => 'Sistema'],
];
$cargoTab = $cargoTabMap[$usuarioCargo] ?? ['href' => '/pwa/obrigacoes', 'label' => 'Obrigações'];

// ─── Definição das 4 abas da bottom nav ─────────────────────────────────
$pwaNavTabs = [
    [
        'id'    => 'inicio',
        'href'  => '/pwa',
        'label' => 'Início',
        'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />',
    ],
    [
        'id'    => 'cargo',
        'href'  => $cargoTab['href'],
        'label' => $cargoTab['label'],
        'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />',
    ],
    [
        'id'    => 'biblioteca',
        'href'  => '/pwa/biblioteca',
        'label' => 'Biblioteca',
        'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />',
    ],
    [
        'id'    => 'perfil',
        'href'  => '/pwa/perfil',
        'label' => 'Perfil',
        'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />',
    ],
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, user-scalable=no">
    <meta name="theme-color" content="#0E2640">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title><?= htmlspecialchars($pwaPageTitle) ?> - Gestor Loja</title>
    <link rel="manifest" href="/manifest.php">
    <link rel="stylesheet" href="/assets/css/tailwind.generated.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        erpNavy: '#1B3A5C',
                        erpNavyDeep: '#0E2640',
                        erpGold: '#C9A227',
                        erpBg: '#F6F8FB',
                        erpSurface: '#FFFFFF',
                        erpBorder: '#D1DAE6',
                        erpText: '#1A2B3D',
                        erpMuted: '#6B7F94',
                    }
                }
            }
        }
    </script>
    <style>
        /* Garante que o app ocupe a tela inteira, especialmente em iOS */
        body, html {
            height: 100%;
            overflow: hidden;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }
        .app-container {
            display: flex;
            flex-direction: column;
            height: 100vh;
            height: 100dvh; /* Dynamic viewport height — respeita barras do navegador mobile */
        }
        .app-header {
            flex-shrink: 0;
            padding-top: env(safe-area-inset-top, 0px);
        }
        .app-content {
            flex-grow: 1;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            overscroll-behavior-y: contain;
        }
        .app-nav {
            flex-shrink: 0;
            padding-bottom: env(safe-area-inset-bottom, 0px);
        }
        /* Bottom nav active state */
        .pwa-tab-active {
            color: #1B3A5C;
        }
        .pwa-tab-active .pwa-tab-dot {
            opacity: 1;
        }
    </style>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-erpBg text-erpText antialiased">
    <div class="app-container">
        <!-- Cabeçalho do App -->
        <header class="app-header bg-gradient-to-r from-erpNavyDeep to-erpNavy text-white shadow-lg z-10">
            <div class="mx-auto flex h-14 max-w-5xl items-center justify-between px-4">
                <div class="flex items-center gap-3">
                    <?php if ($pwaShowBackButton): ?>
                        <a href="<?= htmlspecialchars($pwaBackUrl) ?>" class="p-2 -ml-2 text-white/80 hover:text-white active:scale-95 transition-transform">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                            </svg>
                        </a>
                    <?php else: ?>
                        <div class="h-9 w-9 rounded-lg border border-white/20 bg-white/10 p-1">
                            <?php if ($logoUrl): ?>
                                <img src="<?= htmlspecialchars($logoUrl) ?>" alt="Logo" class="h-full w-full object-contain">
                            <?php else: ?>
                                <span class="flex h-full w-full items-center justify-center text-base font-bold text-erpGold">∴</span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <h1 class="text-base font-semibold tracking-tight"><?= htmlspecialchars($pwaPageTitle) ?></h1>
                </div>
                <div class="flex items-center gap-2">
                    <button class="p-2 text-white/60 hover:text-white transition-colors" aria-label="Notificações">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                    </button>
                </div>
            </div>
        </header>

        <!-- Conteúdo Principal -->
        <main class="app-content">
            <?= $pwaContent ?? '' ?>
        </main>

        <!-- Barra de Navegação Inferior — 4 abas contextuais -->
        <nav class="app-nav border-t border-erpBorder bg-erpSurface shadow-[0_-4px_12px_rgba(27,58,92,0.08)]">
            <div class="mx-auto grid max-w-5xl grid-cols-4 items-center justify-items-center px-1 py-1">
                <?php foreach ($pwaNavTabs as $tab): ?>
                    <?php $isActive = $pwaActiveTab === $tab['id']; ?>
                    <a href="<?= htmlspecialchars($tab['href']) ?>"
                       class="flex w-full flex-col items-center rounded-xl px-1 py-2 transition-colors <?= $isActive ? 'pwa-tab-active' : 'text-erpMuted hover:text-erpNavy' ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="<?= $isActive ? '2.5' : '1.8' ?>">
                            <?= $tab['icon'] ?>
                        </svg>
                        <span class="mt-0.5 text-[0.65rem] font-semibold leading-tight tracking-tight"><?= htmlspecialchars($tab['label']) ?></span>
                        <div class="pwa-tab-dot mt-0.5 h-1 w-1 rounded-full bg-erpGold <?= $isActive ? 'opacity-100' : 'opacity-0' ?>"></div>
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