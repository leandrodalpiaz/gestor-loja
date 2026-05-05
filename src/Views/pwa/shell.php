<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

$usuarioNome = (string) ($_SESSION['usuario_nome'] ?? 'Irmão');
$usuarioCargo = (string) ($_SESSION['usuario_cargo'] ?? '');
$tenantSlug = (string) ($_SESSION['tenant_slug'] ?? '');
$logoUrl = \App\Core\Tenant\TenantAssetResolver::resolveLogo($tenantSlug);

// Parâmetros da página (a serem definidos pela view que inclui o shell)
$pwaPageTitle = (string) ($pwaPageTitle ?? 'Acesso Rápido');
$pwaShowBackButton = (bool) ($pwaShowBackButton ?? false);
$pwaBackUrl = (string) ($pwaBackUrl ?? '/pwa');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title><?= htmlspecialchars($pwaPageTitle) ?> - Gestor Loja</title>
    <link rel="manifest" href="/manifest.php">
    <link rel="stylesheet" href="/assets/css/tailwind.generated.css">
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
        }
        .app-container {
            display: flex;
            flex-direction: column;
            height: 100vh; /* Full viewport height */
        }
        .app-header {
            flex-shrink: 0;
        }
        .app-content {
            flex-grow: 1;
            overflow-y: auto; /* Scroll only on content */
            -webkit-overflow-scrolling: touch;
        }
        .app-nav {
            flex-shrink: 0;
        }
    </style>
</head>
<body class="bg-erpBg text-erpText antialiased">
    <div class="app-container">
        <!-- Cabeçalho do App -->
        <header class="app-header bg-gradient-to-r from-erpNavyDeep to-erpNavy text-white shadow-lg z-10">
            <div class="mx-auto flex h-16 max-w-5xl items-center justify-between px-4">
                <div class="flex items-center gap-3">
                    <?php if ($pwaShowBackButton): ?>
                        <a href="<?= htmlspecialchars($pwaBackUrl) ?>" class="p-2 text-white/80 hover:text-white">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                            </svg>
                        </a>
                    <?php else: ?>
                        <div class="h-10 w-10 rounded-lg border border-white/20 bg-white/10 p-1.5">
                            <?php if ($logoUrl): ?>
                                <img src="<?= htmlspecialchars($logoUrl) ?>" alt="Logo" class="h-full w-full object-contain">
                            <?php else: ?>
                                <span class="flex h-full w-full items-center justify-center text-lg font-bold text-erpGold">∴</span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <h1 class="text-lg font-semibold tracking-tight"><?= htmlspecialchars($pwaPageTitle) ?></h1>
                </div>
                <div class="text-right">
                    <!-- Pode adicionar um botão de ação aqui se necessário -->
                </div>
            </div>
        </header>

        <!-- Conteúdo Principal -->
        <main class="app-content">
            <?= $pwaContent ?? '' ?>
        </main>

        <!-- Barra de Navegação Inferior -->
        <nav class="app-nav border-t border-erpBorder bg-erpSurface shadow-[0_-2px_8px_rgba(27,58,92,0.06)]">
            <div class="mx-auto grid max-w-5xl grid-cols-3 items-center justify-items-center px-2 py-1">
                <a href="/pwa" class="flex w-full flex-col items-center rounded-lg px-2 py-2 text-erpMuted hover:bg-erpBg hover:text-erpNavy">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    <span class="mt-1 text-xs font-medium">Início</span>
                </a>
                <a href="/dashboard" class="flex w-full flex-col items-center rounded-lg px-2 py-2 text-erpMuted hover:bg-erpBg hover:text-erpNavy">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                    </svg>
                    <span class="mt-1 text-xs font-medium">Painel</span>
                </a>
                <a href="/logout" class="flex w-full flex-col items-center rounded-lg px-2 py-2 text-erpMuted hover:bg-erpBg hover:text-erpNavy">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    <span class="mt-1 text-xs font-medium">Sair</span>
                </a>
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