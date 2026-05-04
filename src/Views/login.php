<?php
declare(strict_types=1);

$tenantSlug = trim((string) ($tenantSlug ?? $_SESSION['tenant_slug'] ?? ''));
$tenantName = trim((string) ($tenantName ?? $_SESSION['tenant_name'] ?? ''));
$tenantResolved = !empty($tenantResolved) && $tenantSlug !== '';
$tenantUnavailableMessage = trim((string) ($tenantUnavailableMessage ?? ''));
$logoLogin = \App\Core\Tenant\TenantAssetResolver::resolveLogo($tenantSlug);

$publicConteudos = isset($publicConteudos) && is_array($publicConteudos) ? $publicConteudos : [];
$publicAds = isset($publicAds) && is_array($publicAds) ? $publicAds : [];
$publicAdsEnabled = (bool) ($publicAdsEnabled ?? false);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Portal Público - <?= htmlspecialchars($tenantName ?: 'Gestor de Loja') ?></title>
    <meta name="theme-color" content="#ffffff" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#121212" media="(prefers-color-scheme: dark)">
    <link rel="stylesheet" href="/assets/css/erp_design_system.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-erpSurface text-erpText font-sans antialiased min-h-screen flex flex-col items-center">

<div class="w-full max-w-6xl mx-auto px-4 py-8 flex-1 flex flex-col gap-8 md:gap-12">
    
    <!-- Hero Section -->
    <header class="flex flex-col md:flex-row items-center justify-between gap-6 pb-6 md:pb-10 border-b border-erpBorder/50">
        <div class="flex items-center gap-4 text-center md:text-left">
            <?php if ($logoLogin): ?>
                <img src="<?= htmlspecialchars($logoLogin) ?>" alt="Logo da Loja" class="w-16 h-16 md:w-20 md:h-20 object-contain rounded-2xl bg-erpSurface shadow-sm border border-erpBorder/50">
            <?php else: ?>
                <div class="w-16 h-16 md:w-20 md:h-20 bg-erpSurface2 rounded-2xl border border-erpBorder/50 flex items-center justify-center shadow-sm">
                    <span class="text-2xl text-erpMuted">🏛️</span>
                </div>
            <?php endif; ?>
            <div>
                <h1 class="text-2xl md:text-3xl font-bold tracking-tight"><?= htmlspecialchars($tenantName ?: 'Gestor de Loja') ?></h1>
                <p class="text-sm md:text-base text-erpMuted mt-1">Bem-vindo ao portal público e de acesso restrito.</p>
            </div>
        </div>
        <div class="hidden md:flex flex-col text-right">
            <span class="text-sm font-medium text-erpMuted uppercase tracking-wider">Acesso Seguro</span>
            <span class="text-xs text-erpMuted mt-1">Ambiente Criptografado</span>
        </div>
    </header>

    <main class="flex flex-col lg:flex-row gap-8 lg:gap-12 items-start w-full">
        
        <!-- Conteúdo Público (Notícias / Ads) -->
        <div class="w-full lg:flex-1 space-y-10">
            
            <?php if ($publicAdsEnabled && !empty($publicAds)): ?>
            <section>
                <div class="flex items-center justify-between mb-5">
                    <h2 class="text-xl font-bold tracking-tight">Apoio Institucional</h2>
                    <span class="text-xs font-semibold bg-erpBrand/10 text-erpBrand px-2.5 py-1 rounded-md uppercase tracking-wider">Patrocinadores</span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <?php foreach ($publicAds as $ad): ?>
                        <a href="<?= htmlspecialchars((string) ($ad['link_url'] ?? '#')) ?>" target="_blank" rel="noopener noreferrer" class="group relative bg-erpSurface border border-erpBorder/60 hover:border-erpBrand/40 rounded-2xl p-4 flex gap-4 transition-all duration-200 hover:shadow-md hover:-translate-y-0.5">
                            <?php if (!empty($ad['imagem_url'])): ?>
                                <img src="<?= htmlspecialchars((string) $ad['imagem_url']) ?>" alt="Sponsor" class="w-16 h-16 rounded-xl object-cover border border-erpBorder/50 shrink-0">
                            <?php else: ?>
                                <div class="w-16 h-16 rounded-xl bg-erpSurface2 border border-erpBorder/50 flex items-center justify-center shrink-0">
                                    <svg class="w-6 h-6 text-erpMuted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                                </div>
                            <?php endif; ?>
                            <div class="flex flex-col justify-center">
                                <h3 class="text-sm font-semibold text-erpText group-hover:text-erpBrand transition-colors line-clamp-1"><?= htmlspecialchars((string) ($ad['titulo'] ?? 'Apoio')) ?></h3>
                                <p class="text-xs text-erpMuted mt-1.5 line-clamp-2 leading-relaxed"><?= htmlspecialchars((string) ($ad['resumo'] ?? '')) ?></p>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <section>
                <div class="flex items-center justify-between mb-5">
                    <h2 class="text-xl font-bold tracking-tight">Mural e Acontecimentos</h2>
                    <span class="text-xs font-semibold bg-erpSurface2 px-2.5 py-1 rounded-md text-erpMuted uppercase tracking-wider">Notícias</span>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <?php if (empty($publicConteudos)): ?>
                        <div class="col-span-full py-10 text-center bg-erpSurface2/50 rounded-2xl border border-erpBorder border-dashed">
                            <p class="text-erpMuted text-sm font-medium">Nenhum evento ou comunicado publicado no momento.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($publicConteudos as $item): ?>
                            <?php
                            $tipo = strtoupper(trim((string) ($item['tipo'] ?? '')));
                            $titulo = (string) ($item['titulo'] ?? '');
                            $resumo = (string) ($item['resumo'] ?? '');
                            $inicioEm = (string) ($item['inicio_em'] ?? '');
                            $linkUrl = (string) ($item['link_url'] ?? '');
                            
                            $badgeColor = match(strtolower($tipo)) {
                                'evento', 'agenda' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
                                'noticia' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300 border-blue-200 dark:border-blue-800',
                                'comunicado' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 border-amber-200 dark:border-amber-800',
                                default => 'bg-erpSurface2 text-erpMuted border-erpBorder',
                            };
                            ?>
                            <a href="<?= htmlspecialchars($linkUrl !== '' ? $linkUrl : '#') ?>" class="group bg-erpSurface border border-erpBorder/80 hover:border-erpBrand/50 rounded-2xl p-6 flex flex-col transition-all duration-200 hover:shadow-lg hover:-translate-y-0.5">
                                <div class="flex items-center gap-2.5 mb-3.5">
                                    <?php if ($tipo !== ''): ?>
                                        <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded border <?= $badgeColor ?>">
                                            <?= htmlspecialchars($tipo) ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($inicioEm !== ''): ?>
                                        <span class="text-xs font-medium text-erpMuted flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                            <?= htmlspecialchars($inicioEm) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <h3 class="text-base font-semibold text-erpText group-hover:text-erpBrand transition-colors leading-snug"><?= htmlspecialchars($titulo) ?></h3>
                                <?php if ($resumo !== ''): ?>
                                    <p class="text-sm text-erpMuted mt-2.5 leading-relaxed flex-1 line-clamp-3"><?= htmlspecialchars($resumo) ?></p>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        </div>

        <!-- Área de Login (Formulário Lateral) -->
        <aside class="w-full lg:w-[420px] shrink-0">
            <div class="bg-erpSurface border border-erpBorder shadow-md rounded-[24px] p-6 md:p-8 sticky top-8">
                <div class="mb-8 text-center">
                    <div class="w-12 h-12 bg-erpBrand/10 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-6 h-6 text-erpBrand" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                    </div>
                    <h2 class="text-xl font-bold tracking-tight">Acesso Restrito</h2>
                    <p class="text-sm text-erpMuted mt-1.5">Identifique-se para acessar os módulos internos</p>
                </div>

                <?php if (!$tenantResolved): ?>
                    <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm font-medium text-amber-800 flex items-start gap-3">
                        <svg class="w-5 h-5 shrink-0 mt-0.5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        <span><?= htmlspecialchars($tenantUnavailableMessage ?: 'Loja não identificada. Verifique a configuração do ambiente.') ?></span>
                    </div>
                <?php endif; ?>

                <?php if (isset($erroLogin)): ?>
                    <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm font-medium text-rose-800 flex items-start gap-3">
                        <svg class="w-5 h-5 shrink-0 mt-0.5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span><?= htmlspecialchars((string) $erroLogin) ?></span>
                    </div>
                <?php endif; ?>

                <form action="/login" method="POST" class="space-y-5">
                    <div>
                        <label for="matricula" class="block text-xs font-semibold text-erpMuted uppercase tracking-wider mb-2">CIM / Matrícula</label>
                        <input id="matricula" name="matricula" type="text" required <?= !$tenantResolved ? 'disabled' : '' ?> 
                            class="w-full px-4 py-3.5 bg-erpSurface border border-erpBorder rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-erpBrand/20 focus:border-erpBrand transition-all shadow-sm" 
                            placeholder="Digite seu CIM numérico">
                    </div>
                    <div>
                        <label for="password" class="block text-xs font-semibold text-erpMuted uppercase tracking-wider mb-2">Senha</label>
                        <input id="password" name="password" type="password" required <?= !$tenantResolved ? 'disabled' : '' ?> 
                            class="w-full px-4 py-3.5 bg-erpSurface border border-erpBorder rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-erpBrand/20 focus:border-erpBrand transition-all shadow-sm" 
                            placeholder="Digite sua senha cadastrada">
                    </div>
                    
                    <div class="pt-4 flex flex-col gap-3">
                        <button type="submit" name="acao" value="login" <?= !$tenantResolved ? 'disabled' : '' ?> 
                            class="w-full flex items-center justify-center gap-2 bg-erpBrand hover:bg-erpBrand/90 text-white font-semibold py-3.5 px-4 rounded-xl transition-all duration-200 active:scale-[0.98] disabled:opacity-50 disabled:pointer-events-none shadow-sm">
                            Entrar no Sistema
                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                        </button>
                        <button type="submit" name="acao" value="solicitar" <?= !$tenantResolved ? 'disabled' : '' ?> 
                            class="w-full bg-erpSurface hover:bg-erpSurface2 text-erpText border border-erpBorder font-semibold py-3 px-4 rounded-xl transition-all duration-200 disabled:opacity-50 disabled:pointer-events-none text-sm">
                            Primeiro acesso ou esqueceu a senha?
                        </button>
                    </div>
                </form>

                <div class="mt-8 pt-6 border-t border-erpBorder/50 text-center">
                    <p class="text-[11px] text-erpMuted leading-relaxed">Em caso de dificuldade técnica, procure a Secretaria da sua Loja para validação cadastral ou redefinição de credenciais.</p>
                </div>
            </div>
        </aside>

    </main>
</div>

<script>
    // System Dark Mode auto-sync
    (function () {
        try {
            if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                document.documentElement.classList.add('dark');
            }
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
                if(e.matches) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            });
        } catch (e) {}
    })();
</script>
</body>
</html>
