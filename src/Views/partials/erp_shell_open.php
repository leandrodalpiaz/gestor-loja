<?php
if (empty($GLOBALS['gestor_loja_erp_head_rendered'])) {
    require __DIR__ . '/erp_head.php';
}

$getSidebarIcon = function(string $label): string {
    $l = mb_strtolower($label);
    if (str_contains($l, 'painel') || str_contains($l, 'principal') || str_contains($l, 'início')) {
        return '<path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />';
    }
    if (str_contains($l, 'sessões') || str_contains($l, 'reuniões') || str_contains($l, 'agenda') || str_contains($l, 'calendário')) {
        return '<path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />';
    }
    if (str_contains($l, 'obreiro') || str_contains($l, 'membro') || str_contains($l, 'quadro') || str_contains($l, 'nominata')) {
        return '<path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />';
    }
    if (str_contains($l, 'caixa') || str_contains($l, 'finanç') || str_contains($l, 'tesouraria') || str_contains($l, 'obrigaç') || str_contains($l, 'contas')) {
        return '<path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />';
    }
    if (str_contains($l, 'biblioteca') || str_contains($l, 'livro') || str_contains($l, 'acervo')) {
        return '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />';
    }
    if (str_contains($l, 'configura') || str_contains($l, 'parâmetro') || str_contains($l, 'sistema') || str_contains($l, 'cargos') || str_contains($l, 'acessos') || str_contains($l, 'auditoria')) {
        return '<path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z" />';
    }
    if (str_contains($l, 'convite') || str_contains($l, 'visitante')) {
        return '<path stroke-linecap="round" stroke-linejoin="round" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />';
    }
    if (str_contains($l, 'balaústre') || str_contains($l, 'ata') || str_contains($l, 'trabalho') || str_contains($l, 'votaç')) {
        return '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />';
    }
    return '<path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />';
};

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
$isHomeActive = $appShellActiveHref === '/dashboard' || $appShellActiveHref === '/';
?>
<body class="min-h-screen bg-erp-bg font-sans text-erp-text antialiased">
    <style>
        /* Shell — Layout & Depth */
        .erp-app-shell { min-height: 100vh; display: flex; }
        .erp-app-shell > aside {
            position: fixed;
            top: 0; left: 0; bottom: 0;
            width: 260px;
            background: linear-gradient(180deg, var(--erp-navy-deep, #0E2640) 0%, var(--erp-navy, #1B3A5C) 100%);
            border-right: 1px solid rgba(255,255,255,0.06);
            z-index: 40;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .erp-app-main { flex: 1; min-width: 0; margin-left: 260px; transition: margin-left 0.3s ease; }

        /* Sidebar — Navigation */
        aside nav a {
            position: relative;
            transition: all 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
            color: rgba(255,255,255,0.55);
        }
        aside nav a:hover {
            color: rgba(255,255,255,0.9);
            background: rgba(255,255,255,0.06);
            transform: translateX(4px);
        }
        aside nav a.active {
            color: #fff;
            background: linear-gradient(90deg, rgba(201,162,39,0.15) 0%, transparent 100%);
            border-left: 3px solid var(--erp-gold, #C9A227);
            box-shadow: inset 4px 0 12px rgba(201,162,39,0.08);
        }
        aside nav a.active svg { color: var(--erp-gold, #C9A227); }

        /* Sidebar section titles */
        aside nav h3 {
            color: rgba(255,255,255,0.3) !important;
        }

        /* Mobile */
        @media (max-width: 1023px) {
            .erp-app-shell > aside { transform: translateX(-100%); }
            .erp-app-shell > aside.open { transform: translateX(0); box-shadow: 20px 0 50px rgba(0,0,0,0.3); }
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
                <div class="px-5 pt-6 pb-4">
                    <?php
                        $sidebarLogo = $tenantLogo;
                        if ($sidebarLogo === '' || str_contains($sidebarLogo, 'placeholder')) {
                            $sidebarLogo = '/assets/logo-renascenca.png';
                        }
                    ?>
                    <div class="flex flex-col items-center text-center gap-2">
                        <img src="<?= htmlspecialchars($sidebarLogo) ?>" alt="Logo" class="h-[88px] w-[88px] object-contain drop-shadow-[0_2px_8px_rgba(0,0,0,0.4)]">
                        <div>
                            <div class="text-sm font-bold tracking-tight text-white leading-tight">
                                <?= htmlspecialchars($_SESSION['tenant_name'] ?? 'Gestor-Loja') ?>
                            </div>
                            <div class="text-[9px] font-bold uppercase tracking-[0.15em] text-[#C9A227] opacity-70 mt-0.5">Oficina Digital</div>
                        </div>
                    </div>
                </div>

                <nav class="flex-1 space-y-8 overflow-y-auto px-4 py-4 scrollbar-hide">
                    <!-- Atalho Fixo Home -->
                    <div>
                        <div class="space-y-1">
                            <a href="/dashboard" 
                               class="group flex items-center rounded-xl px-4 py-3 text-sm font-semibold transition-all <?= $isHomeActive ? 'active' : '' ?>">
                                <div class="mr-3 transition-transform group-hover:scale-110">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <?= $getSidebarIcon('painel') ?>
                                    </svg>
                                </div>
                                <span>Painel Principal</span>
                            </a>
                        </div>
                    </div>

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
                                                <?= $getSidebarIcon((string) ($item['label'] ?? '')) ?>
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
                    <div class="flex items-center gap-3 rounded-xl bg-white/5 p-3 border border-white/8">
                        <div class="h-9 w-9 rounded-lg bg-[#C9A227] text-[#0E2640] flex items-center justify-center text-xs font-bold">
                            <?= strtoupper(substr($appShellUserLabel, 0, 1)) ?>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-xs font-semibold text-white/80 truncate"><?= htmlspecialchars($appShellUserLabel) ?></div>
                            <a href="/logout" class="text-[10px] font-semibold text-white/35 hover:text-red-400 uppercase tracking-wider transition-colors">Deslogar</a>
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

            <header class="glass-surface sticky top-0 z-30 px-4 min-h-[4rem] pt-[max(env(safe-area-inset-top),0.5rem)] pb-2 flex items-center justify-between sm:px-8 border-b border-white/5">
                <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden p-2 rounded-lg hover:bg-erp-surface-2 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                
                <div class="flex-1"></div>

                <div class="flex items-center gap-6">
                    <a href="/pwa" class="text-[11px] font-bold uppercase tracking-widest text-erp-muted hover:text-erp-gold transition-colors">Acesso PWA</a>
                    <div class="h-8 w-[1px] bg-erp-border"></div>
                    <button class="text-erp-muted hover:text-erp-gold transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                    </button>
                </div>
            </header>

            <main class="max-w-[1200px] mx-auto px-4 py-10 sm:px-8 pb-32">
                <!-- Page Header & Actions Wrapper -->
                <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-6 mb-10 pb-6 border-b border-white/5">
                    <div class="flex-1 min-w-0">
                        <?php if ($appShellEyebrow !== ''): ?>
                            <span class="page-eyebrow mb-2">
                                <?= htmlspecialchars($appShellEyebrow) ?>
                            </span>
                        <?php endif; ?>
                        <h1 class="page-title tracking-tight"><?= htmlspecialchars($appShellTitle) ?></h1>
                        <?php if ($appShellDescription !== ''): ?>
                            <p class="page-description leading-relaxed"><?= htmlspecialchars($appShellDescription) ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Page Actions -->
                    <?php if ($appShellActions !== []): ?>
                        <div class="flex flex-wrap items-center gap-3 shrink-0">
                            <?php foreach ($appShellActions as $action): ?>
                                <a href="<?= htmlspecialchars((string) ($action['href'] ?? '#')) ?>" 
                                   class="btn depth-1 hover-lift <?= !empty($action['primary']) ? 'btn-primary' : 'btn-secondary' ?>">
                                    <?= htmlspecialchars((string) ($action['label'] ?? 'Abrir')) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Main content from other files will be injected here -->

