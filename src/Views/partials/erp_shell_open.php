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
        /* Estabilização local do shell ERP com foco em profundidade e glassmorphism */
        .erp-app-shell { min-height: 100vh; display: flex; }
        .erp-app-shell > aside {
            position: fixed;
            top: 0; left: 0; bottom: 0;
            width: 280px;
            background: var(--erp-surface);
            border-right: 1px solid var(--erp-border);
            z-index: 40;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .erp-app-main { flex: 1; min-width: 0; margin-left: 280px; transition: margin-left 0.3s ease; }
        
        /* Sidebar Refinement */
        aside nav a { 
            position: relative;
            transition: all 0.2s ease;
            color: var(--erp-muted);
        }
        aside nav a:hover { color: var(--erp-text); background: var(--erp-surface-2); }
        aside nav a.active { 
            background: var(--erp-navy); 
            color: #fff; 
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.15);
        }
        aside nav a.active svg { color: var(--erp-gold); }

        /* Mobile Adjustments */
        @media (max-width: 1023px) {
            .erp-app-shell > aside { transform: translateX(-100%); }
            .erp-app-shell > aside.open { transform: translateX(0); box-shadow: 20px 0 50px rgba(0,0,0,0.2); }
            .erp-app-main { margin-left: 0; }
        }
    </style>
    <div x-data="{ sidebarOpen: false }" class="erp-app-shell bg-erp-bg">
        <!-- Sidebar -->
        <aside 
            class="depth-2"
            :class="{'open': sidebarOpen}"
        >
            <div class="flex h-full flex-col">
                <div class="px-6 py-8">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl border border-erp-border bg-white shadow-lg shadow-erp-navy/5 overflow-hidden p-1 group-hover:scale-105 transition-transform">
                            <?php if ($tenantLogo !== ''): ?>
                                <img src="<?= htmlspecialchars($tenantLogo) ?>" alt="Logo" class="h-full w-full object-contain">
                            <?php else: ?>
                                <div class="bg-gradient-to-br from-erp-navy to-erp-navy/80 text-white h-full w-full flex items-center justify-center font-black text-lg">
                                    <?= strtoupper(substr($_SESSION['tenant_name'] ?? 'GL', 0, 1)) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-black tracking-tight text-erp-navy leading-tight truncate">
                                <?= htmlspecialchars($_SESSION['tenant_name'] ?? 'Gestor-Loja') ?>
                            </div>
                            <div class="text-[9px] font-black uppercase tracking-[0.2em] text-erp-gold opacity-80">Oficina Digital</div>
                        </div>
                    </div>
                </div>

                <nav class="flex-1 space-y-8 overflow-y-auto px-4 py-4 scrollbar-hide">
                    <?php foreach ($appShellSidebarSections as $section): ?>
                        <div>
                            <h3 class="mb-3 px-4 text-[10px] font-bold uppercase tracking-[0.15em] text-erp-muted opacity-60">
                                <?= htmlspecialchars((string) ($section['title'] ?? 'Menu')) ?>
                            </h3>
                            <div class="space-y-1">
                                <?php foreach (($section['items'] ?? []) as $item): ?>
                                    <?php $isActive = (string) ($item['href'] ?? '') === $appShellActiveHref; ?>
                                    <a href="<?= htmlspecialchars((string) ($item['href'] ?? '#')) ?>" 
                                       class="group flex items-center rounded-xl px-4 py-3 text-sm font-semibold transition-all <?= $isActive ? 'active' : '' ?>">
                                        <div class="mr-3 transition-transform group-hover:scale-110">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        </div>
                                        <span><?= htmlspecialchars((string) ($item['label'] ?? 'Item')) ?></span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </nav>

                <div class="p-4">
                    <div class="flex items-center gap-3 rounded-2xl bg-erp-surface-2 p-3 border border-erp-border/50">
                        <div class="h-10 w-10 rounded-xl bg-erp-navy text-white flex items-center justify-center font-bold shadow-md">
                            <?= strtoupper(substr($appShellUserLabel, 0, 1)) ?>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-xs font-bold text-erp-text truncate"><?= htmlspecialchars($appShellUserLabel) ?></div>
                            <a href="/logout" class="text-[10px] font-bold text-erp-muted hover:text-erp-danger uppercase tracking-wider">Deslogar</a>
                        </div>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main content -->
        <div class="erp-app-main">
            <?php if (!empty($_SESSION['admin_mode'])): ?>
                <div class="bg-erp-danger text-white px-6 py-2 text-center text-xs font-bold tracking-wide uppercase z-50 sticky top-0">
                    ⚠️ Modo Suporte Ativo
                    <a href="/admin-suporte/sair" class="ml-4 underline hover:text-white/80">Sair</a>
                </div>
            <?php endif; ?>

            <header class="glass-surface sticky top-0 z-30 px-4 h-16 flex items-center justify-between sm:px-8">
                <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden p-2 rounded-lg hover:bg-erp-surface-2 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                
                <div class="flex-1"></div>

                <div class="flex items-center gap-6">
                    <a href="/pwa" class="text-[11px] font-bold uppercase tracking-widest text-erp-muted hover:text-erp-navy transition-colors">Acesso PWA</a>
                    <div class="h-8 w-[1px] bg-erp-border"></div>
                    <button class="text-erp-muted hover:text-erp-navy transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                    </button>
                </div>
            </header>

            <main class="max-w-[1200px] mx-auto px-4 py-10 sm:px-8 pb-32">
                <!-- Page Header -->
                <div class="mb-10">
                    <?php if ($appShellEyebrow !== ''): ?>
                        <div class="inline-block px-3 py-1 rounded-full bg-erp-navy/5 text-[10px] font-bold uppercase tracking-widest text-erp-navy mb-3">
                            <?= htmlspecialchars($appShellEyebrow) ?>
                        </div>
                    <?php endif; ?>
                    <h1 class="text-3xl font-black text-erp-navy tracking-tight sm:text-4xl"><?= htmlspecialchars($appShellTitle) ?></h1>
                    <?php if ($appShellDescription !== ''): ?>
                        <p class="mt-3 max-w-2xl text-base text-erp-muted leading-relaxed"><?= htmlspecialchars($appShellDescription) ?></p>
                    <?php endif; ?>
                </div>

                <!-- Page Actions -->
                <?php if ($appShellActions !== []): ?>
                    <div class="mb-10 flex flex-wrap gap-4">
                        <?php foreach ($appShellActions as $action): ?>
                            <a href="<?= htmlspecialchars((string) ($action['href'] ?? '#')) ?>" 
                               class="btn depth-1 hover-lift <?= !empty($action['primary']) ? 'btn-primary' : 'btn-secondary' ?>">
                                <?= htmlspecialchars((string) ($action['label'] ?? 'Abrir')) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Main content from other files will be injected here -->

