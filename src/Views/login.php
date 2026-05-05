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
    <meta name="theme-color" content="#1B3A5C" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#0A1628" media="(prefers-color-scheme: dark)">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/assets/css/erp_design_system.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* Fallback de estabilidade e customizações Tailwind */
        [x-cloak] { display: none !important; }
        img { max-width: 100%; height: auto; }
        .glass-surface { backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); }
    </style>
</head>
<body class="bg-erp-bg text-erp-text font-sans antialiased min-h-screen flex flex-col overflow-x-hidden">

<!-- Premium Background Effect -->
<div class="fixed inset-0 z-[-1] overflow-hidden pointer-events-none">
    <div class="absolute -top-[10%] -left-[10%] w-[40%] h-[40%] rounded-full bg-[#1B3A5C]/8 blur-[120px]"></div>
    <div class="absolute top-[20%] -right-[10%] w-[30%] h-[30%] rounded-full bg-[#C9A227]/5 blur-[100px]"></div>
    <div class="absolute -bottom-[10%] left-[20%] w-[50%] h-[50%] rounded-full bg-[#4BACD4]/4 blur-[150px]"></div>
</div>

<div class="w-full max-w-7xl mx-auto px-6 py-12 flex-1 flex flex-col gap-12 md:gap-20">
    
    <!-- Hero Section -->
    <header class="flex flex-col md:flex-row items-center justify-between gap-8 pb-10 border-b border-erp-border/30">
        <div class="flex items-center gap-6 text-center md:text-left">
            <div class="relative group">
                <div class="absolute -inset-2 bg-gradient-to-r from-[#C9A227]/30 to-[#1B3A5C]/20 rounded-3xl blur opacity-30 group-hover:opacity-50 transition duration-500"></div>
                <?php if ($logoLogin): ?>
                    <img src="<?= htmlspecialchars($logoLogin) ?>" alt="Logo" class="relative w-20 h-20 md:w-24 md:h-24 object-contain rounded-2xl bg-white shadow-xl border border-erp-border/50 p-2">
                <?php else: ?>
                    <img src="/assets/logo-renascenca.png" alt="Logo Renascença" class="relative w-20 h-20 md:w-24 md:h-24 object-contain rounded-2xl bg-white shadow-xl border border-erp-border/50 p-2">
                <?php endif; ?>
            </div>
            <div>
                <h1 class="text-3xl md:text-5xl font-black tracking-tight text-erp-navy"><?= htmlspecialchars($tenantName ?: 'Gestor de Loja') ?></h1>
                <p class="text-base md:text-lg text-erp-muted mt-2 font-medium">Bem-vindo ao portal público e de acesso restrito.</p>
            </div>
        </div>
        <div class="hidden md:flex flex-col text-right">
            <div class="inline-flex items-center gap-2 bg-erp-navy/5 px-4 py-2 rounded-full border border-erp-navy/10">
                <div class="w-2 h-2 rounded-full bg-erp-success animate-pulse"></div>
                <span class="text-[10px] font-black text-erp-navy uppercase tracking-[0.2em]">Ambiente Seguro</span>
            </div>
            <span class="text-[9px] text-erp-muted mt-2 font-bold uppercase tracking-widest opacity-60">Protocolo de Criptografia Ativo</span>
        </div>
    </header>

    <main class="flex flex-col lg:flex-row gap-12 lg:gap-20 items-start w-full">
        
        <!-- Conteúdo Público (Notícias / Ads) -->
        <div class="w-full lg:flex-1 space-y-16">
            
            <?php if ($publicAdsEnabled && !empty($publicAds)): ?>
            <section>
                <div class="flex items-center gap-4 mb-8">
                    <div class="h-8 w-1 bg-erp-gold rounded-full"></div>
                    <h2 class="text-2xl font-black tracking-tight text-erp-navy">Apoio Institucional</h2>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <?php foreach ($publicAds as $ad): ?>
                        <a href="<?= htmlspecialchars((string) ($ad['link_url'] ?? '#')) ?>" target="_blank" rel="noopener noreferrer" 
                           class="group glass-surface hover-lift rounded-2xl p-5 flex gap-5">
                            <div class="w-20 h-20 rounded-xl bg-white border border-erp-border/50 shadow-sm shrink-0 overflow-hidden flex items-center justify-center">
                                <?php if (!empty($ad['imagem_url'])): ?>
                                    <img src="<?= htmlspecialchars((string) $ad['imagem_url']) ?>" alt="Sponsor" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <svg class="w-8 h-8 text-erp-muted opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                                <?php endif; ?>
                            </div>
                            <div class="flex flex-col justify-center">
                                <h3 class="text-sm font-bold text-erp-text group-hover:text-erp-brand-vibrant transition-colors"><?= htmlspecialchars((string) ($ad['titulo'] ?? 'Apoio')) ?></h3>
                                <p class="text-xs text-erp-muted mt-2 line-clamp-2 leading-relaxed"><?= htmlspecialchars((string) ($ad['resumo'] ?? '')) ?></p>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <section>
                <div class="flex items-center gap-4 mb-8">
                    <div class="h-8 w-1 bg-erp-navy rounded-full"></div>
                    <h2 class="text-2xl font-black tracking-tight text-erp-navy">Mural da Oficina</h2>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <?php if (empty($publicConteudos)): ?>
                        <div class="col-span-full py-16 text-center glass-surface rounded-3xl border-dashed">
                            <div class="text-4xl mb-4 opacity-20">📜</div>
                            <p class="text-erp-muted text-sm font-bold uppercase tracking-widest opacity-60">Nenhum comunicado disponível</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($publicConteudos as $item): ?>
                            <?php
                            $tipo = strtoupper(trim((string) ($item['tipo'] ?? '')));
                            $titulo = (string) ($item['titulo'] ?? '');
                            $resumo = (string) ($item['resumo'] ?? '');
                            $inicioEm = (string) ($item['inicio_em'] ?? '');
                            $linkUrl = (string) ($item['link_url'] ?? '');
                            ?>
                            <a href="<?= htmlspecialchars($linkUrl !== '' ? $linkUrl : '#') ?>" 
                               class="group glass-surface hover-lift rounded-3xl p-8 flex flex-col">
                                <div class="flex items-center justify-between mb-6">
                                    <span class="badge badge-primary bg-erp-navy/5 text-erp-navy border border-erp-navy/10">
                                        <?= htmlspecialchars($tipo ?: 'INFO') ?>
                                    </span>
                                    <?php if ($inicioEm !== ''): ?>
                                        <span class="text-[10px] font-bold text-erp-muted uppercase tracking-widest opacity-60">
                                            <?= htmlspecialchars($inicioEm) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <h3 class="text-lg font-bold text-erp-text group-hover:text-erp-brand-vibrant transition-colors leading-tight"><?= htmlspecialchars($titulo) ?></h3>
                                <?php if ($resumo !== ''): ?>
                                    <p class="text-sm text-erp-muted mt-4 leading-relaxed line-clamp-3 font-medium opacity-80"><?= htmlspecialchars($resumo) ?></p>
                                <?php endif; ?>
                                <div class="mt-6 pt-6 border-t border-erp-border/30 flex items-center text-xs font-bold text-erp-navy uppercase tracking-widest group-hover:gap-2 transition-all">
                                    Ler Detalhes <span class="ml-1">→</span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        </div>

        <!-- Área de Login (Formulário Lateral) -->
        <aside class="w-full lg:w-[460px] shrink-0">
            <div class="glass-surface depth-3 rounded-2xl p-8 md:p-10 sticky top-8 border border-[#C9A227]/15">
                <div class="mb-8 text-center">
                    <div class="w-14 h-14 rounded-xl overflow-hidden mx-auto mb-5 shadow-lg border border-[#C9A227]/20">
                        <?php if ($logoLogin): ?>
                            <img src="<?= htmlspecialchars($logoLogin) ?>" alt="Logo" class="w-full h-full object-contain bg-white p-1">
                        <?php else: ?>
                            <img src="/assets/logo-renascenca.png" alt="Logo" class="w-full h-full object-contain bg-white p-1">
                        <?php endif; ?>
                    </div>
                    <h2 class="text-xl font-extrabold tracking-tight text-erp-navy">Acesso de Obreiros</h2>
                    <p class="text-sm text-erp-muted mt-2 font-medium">Insira suas credenciais administrativas</p>
                </div>

                <?php if (!$tenantResolved): ?>
                    <div class="mb-8 rounded-2xl border border-warning/20 bg-warning/5 p-4 text-xs font-bold text-warning flex items-start gap-3">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        <span><?= htmlspecialchars($tenantUnavailableMessage ?: 'Loja não identificada.') ?></span>
                    </div>
                <?php endif; ?>

                <?php if (isset($erroLogin)): ?>
                    <div class="mb-8 rounded-2xl border border-danger/20 bg-danger/5 p-4 text-xs font-bold text-danger flex items-start gap-3 animate-shake">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span><?= htmlspecialchars((string) $erroLogin) ?></span>
                    </div>
                <?php endif; ?>

                <form action="/login" method="POST" class="space-y-6">
                    <div>
                        <label for="matricula" class="block text-[10px] font-bold text-erp-muted uppercase tracking-widest mb-3 ml-1">CIM / Matrícula</label>
                        <input id="matricula" name="matricula" type="text" required <?= !$tenantResolved ? 'disabled' : '' ?> 
                            class="form-input !bg-white/50 focus:!bg-white shadow-inner transition-all" 
                            placeholder="Seu CIM numérico">
                    </div>
                    <div>
                        <label for="password" class="block text-[10px] font-bold text-erp-muted uppercase tracking-widest mb-3 ml-1">Senha de Acesso</label>
                        <input id="password" name="password" type="password" required <?= !$tenantResolved ? 'disabled' : '' ?> 
                            class="form-input !bg-white/50 focus:!bg-white shadow-inner transition-all" 
                            placeholder="••••••••">
                    </div>
                    
                    <div class="pt-6 flex flex-col gap-4">
                        <button type="submit" name="acao" value="login" <?= !$tenantResolved ? 'disabled' : '' ?> 
                            class="btn btn-primary w-full py-4 text-base shadow-xl shadow-erp-navy/10 hover-lift">
                            Autenticar
                            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                        </button>
                        <button type="submit" name="acao" value="solicitar" <?= !$tenantResolved ? 'disabled' : '' ?> 
                            class="text-[11px] font-bold text-erp-muted hover:text-erp-navy transition-colors uppercase tracking-widest text-center py-2">
                            Primeiro acesso / Esqueci a senha
                        </button>
                    </div>
                </form>

                <div class="mt-12 pt-8 border-t border-erp-border/30 text-center">
                    <p class="text-[10px] font-medium text-erp-muted leading-relaxed opacity-60">
                        O uso deste sistema é restrito a obreiros regularizados. <br>
                        Seu acesso é monitorado e criptografado.
                    </p>
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
