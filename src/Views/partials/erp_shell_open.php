<?php
if (empty($GLOBALS['gestor_loja_erp_head_rendered'])) {
    require __DIR__ . '/erp_head.php';
}

// Normaliza variaveis esperadas pelos templates ERP.
$appShellEyebrow = (string) ($appShellEyebrow ?? '');
$appShellTitle = (string) ($appShellTitle ?? 'Painel');
$appShellDescription = (string) ($appShellDescription ?? '');
$appShellActions = is_array($appShellActions ?? null) ? $appShellActions : [];
$appShellSidebarSections = is_array($appShellSidebarSections ?? null) ? $appShellSidebarSections : [];
$appShellActiveHref = (string) ($appShellActiveHref ?? '');
$appShellUserLabel = (string) ($appShellUserLabel ?? ($_SESSION['usuario_nome'] ?? 'Operador'));
$tenantSlug = trim((string) ($_SESSION['tenant_slug'] ?? ''));
$tenantLogo = \App\Core\Tenant\TenantAssetResolver::resolveLogo($tenantSlug);
$appShellBottomNavItems = [];
foreach ($appShellSidebarSections as $section) {
    foreach (($section['items'] ?? []) as $item) {
        $href = (string) ($item['href'] ?? '');
        $label = (string) ($item['label'] ?? '');
        if ($href === '' || $label === '' || $href === '/logout') {
            continue;
        }
        $appShellBottomNavItems[] = ['href' => $href, 'label' => $label];
        if (count($appShellBottomNavItems) >= 5) {
            break 2;
        }
    }
}
?>
<body class="min-h-screen bg-erp-bg font-sans text-erp-text antialiased">
    <style>
        /* Estabilização local do shell ERP (fallback sem Tailwind). */
        body { background: var(--erp-bg); color: var(--erp-text); }
        .erp-app-shell { min-height: 100vh; }
        .erp-app-shell > aside {
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            width: 280px;
            background: var(--erp-surface);
            border-right: 1px solid var(--erp-border);
            overflow: hidden;
            z-index: 30;
        }
        .erp-app-shell > aside > div { height: 100%; display: flex; flex-direction: column; }
        .erp-app-main { min-width: 0; min-height: 100vh; margin-left: 280px; }
        .erp-app-main > header {
            position: sticky;
            top: 0;
            z-index: 20;
            background: var(--erp-surface);
            border-bottom: 1px solid var(--erp-border);
        }
        .erp-app-main > main {
            padding: 24px;
            padding-bottom: 96px;
            width: 100%;
        }
        nav a, aside a { text-decoration: none; }
        aside svg { width: 20px; height: 20px; flex: 0 0 20px; }
        aside a { display: flex; align-items: center; gap: 10px; }
        aside a span { line-height: 1.25; }
        aside nav { padding: 12px 10px; overflow-y: auto; }
        aside nav h3 { margin: 0 0 8px 0; font-size: 11px; color: var(--erp-muted); text-transform: uppercase; letter-spacing: .08em; }
        aside nav > div { margin-bottom: 14px; }
        aside nav a { padding: 8px 10px; border-radius: 8px; color: var(--erp-text); }
        aside nav a:hover { background: var(--erp-surface-2); }
        aside nav a[class*="bg-erp-navy"] { background: var(--erp-brand); color: #fff; }
        header svg { width: 22px; height: 22px; }
        @media (max-width: 1023px) {
            .erp-app-shell > aside { transform: translateX(-100%); }
            .erp-app-main { margin-left: 0; }
            .erp-app-main > main { padding: 16px; padding-bottom: 96px; }
        }
    </style>
    <div x-data="{ sidebarOpen: false }" class="erp-app-shell min-h-screen">
        <!-- Sidebar -->
        <aside 
            class="fixed inset-y-0 left-0 z-30 w-[280px] transform border-r border-erp-border bg-erp-surface transition-transform duration-300 ease-in-out lg:translate-x-0"
            :class="{'translate-x-0': sidebarOpen, '-translate-x-full': !sidebarOpen}"
        >
            <div class="flex h-full flex-col">
                <div class="border-b border-erp-border px-6 py-5">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg border border-erp-border bg-erp-surface-2 overflow-hidden">
                            <?php if ($tenantLogo !== ''): ?>
                                <img src="<?= htmlspecialchars($tenantLogo) ?>" alt="Logo da Loja" class="h-9 w-9 object-contain" width="36" height="36" style="width:36px;height:36px;object-fit:contain;">
                            <?php else: ?>
                                <span class="text-sm font-semibold text-erp-navy">GL</span>
                            <?php endif; ?>
                        </div>
                        <div>
                            <div class="text-sm font-semibold text-erp-navy">Gestor-Loja</div>
                            <div class="text-xs text-erp-muted">Painel Administrativo</div>
                        </div>
                    </div>
                </div>
                <nav class="flex-1 overflow-y-auto px-4 py-4">
                    <?php foreach ($appShellSidebarSections as $section): ?>
                        <div class="mb-6">
                            <h3 class="px-3 text-xs font-semibold uppercase tracking-wider text-erp-muted"><?= htmlspecialchars((string) ($section['title'] ?? 'Seção')) ?></h3>
                            <div class="mt-2 space-y-1">
                                <?php foreach (($section['items'] ?? []) as $item): ?>
                                    <?php $isActive = (string) ($item['href'] ?? '') === $appShellActiveHref; ?>
                                    <a href="<?= htmlspecialchars((string) ($item['href'] ?? '#')) ?>" 
                                       class="flex items-center rounded-lg px-3 py-2.5 text-sm font-medium transition-colors <?= $isActive ? 'bg-erp-navy text-white' : 'text-erp-text hover:bg-erp-bg' ?>">
                                        <!-- Icon placeholder -->
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                        <span><?= htmlspecialchars((string) ($item['label'] ?? 'Item')) ?></span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </nav>
                <div class="border-t border-erp-border p-4">
                    <div class="flex items-center gap-3 rounded-lg bg-erp-bg p-3">
                        <div class="h-9 w-9 rounded-full bg-erp-navy text-white flex items-center justify-center text-sm font-bold">
                            <?= strtoupper(substr($appShellUserLabel, 0, 1)) ?>
                        </div>
                        <div>
                            <div class="text-sm font-semibold text-erp-text"><?= htmlspecialchars($appShellUserLabel) ?></div>
                            <a href="/logout" class="text-xs text-erp-muted hover:text-erp-danger">Sair</a>
                        </div>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main content -->
        <div class="erp-app-main min-w-0">
            <header class="sticky top-0 z-20 border-b border-erp-border bg-erp-surface/80 backdrop-blur-sm">
                <div class="flex h-16 items-center justify-between px-4 sm:px-6 lg:px-8">
                    <!-- Mobile sidebar toggle -->
                    <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                    </button>
                    
                    <div class="flex-1"></div>

                    <!-- Top right actions -->
                    <div class="flex items-center gap-4">
                        <a href="/pwa" class="text-sm font-medium text-erp-muted hover:text-erp-navy">Visão Mobile</a>
                        <!-- User menu can be added here -->
                    </div>
                </div>
            </header>

            <main class="px-4 py-6 pb-24 sm:px-6 lg:px-8 lg:pb-8">
                <!-- Page Header -->
                <div class="mb-6">
                    <?php if ($appShellEyebrow !== ''): ?>
                        <div class="text-sm font-semibold uppercase tracking-wider text-erp-accent"><?= htmlspecialchars($appShellEyebrow) ?></div>
                    <?php endif; ?>
                    <h1 class="mt-1 text-2xl font-bold text-erp-navy sm:text-3xl"><?= htmlspecialchars($appShellTitle) ?></h1>
                    <?php if ($appShellDescription !== ''): ?>
                        <p class="mt-2 max-w-2xl text-sm text-erp-muted"><?= htmlspecialchars($appShellDescription) ?></p>
                    <?php endif; ?>
                </div>

                <!-- Page Actions -->
                <?php if ($appShellActions !== []): ?>
                    <div class="mb-6 flex flex-wrap gap-3">
                        <?php foreach ($appShellActions as $action): ?>
                            <a href="<?= htmlspecialchars((string) ($action['href'] ?? '#')) ?>" 
                               class="inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold transition-colors shadow-sm
                               <?= !empty($action['primary']) ? 'bg-erp-navy text-white hover:bg-opacity-90' : 'bg-erp-surface text-erp-text border border-erp-border hover:bg-erp-bg' ?>">
                                <?= htmlspecialchars((string) ($action['label'] ?? 'Abrir')) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Main content from other files will be injected here -->

