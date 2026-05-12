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
    <title>Átrio - <?= htmlspecialchars($tenantName ?: 'Gestor de Loja') ?></title>
    <meta name="theme-color" content="#0A1628">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/assets/css/erp_design_system.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        [x-cloak] { display: none !important; }
        .font-cinzel { font-family: 'Cinzel', serif; }
        .font-inter { font-family: 'Inter', sans-serif; }
        .glass-panel {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .dark .glass-panel {
            background: rgba(10, 22, 40, 0.6);
            border: 1px solid rgba(201, 162, 39, 0.15);
        }
        .bg-liturgical {
            background-color: #0A1628;
            background-image: radial-gradient(circle at 20% 30%, rgba(201, 162, 39, 0.08) 0%, transparent 50%),
                              radial-gradient(circle at 80% 80%, rgba(27, 58, 92, 0.3) 0%, transparent 50%);
        }
        /* Custom scrollbar for the left panel */
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.2); border-radius: 10px; }
    </style>
</head>
<body class="font-inter antialiased min-h-screen flex flex-col lg:flex-row overflow-x-hidden bg-[#0A1628]">

    <!-- Left Panel: Immersive Theme & Public Content -->
    <div class="relative w-full lg:flex-1 bg-liturgical lg:h-screen lg:overflow-y-auto custom-scrollbar flex flex-col justify-between">
        
        <!-- Texture overlay for parchment/classic feel -->
        <div class="absolute inset-0 opacity-20 mix-blend-overlay pointer-events-none" style="background-image: url('data:image/svg+xml,%3Csvg viewBox=%220 0 200 200%22 xmlns=%22http://www.w3.org/2000/svg%22%3E%3Cfilter id=%22noiseFilter%22%3E%3CfeTurbulence type=%22fractalNoise%22 baseFrequency=%220.65%22 numOctaves=%223%22 stitchTiles=%22stitch%22/%3E%3C/filter%3E%3Crect width=%22100%25%22 height=%22100%25%22 filter=%22url(%23noiseFilter)%22/%3E%3C/svg%3E');"></div>
        
        <div class="relative z-10 px-8 py-12 md:px-16 md:py-20 flex-1 flex flex-col">
            <header class="mb-16">
                <h1 class="font-cinzel text-4xl md:text-5xl lg:text-6xl font-bold tracking-wider text-white drop-shadow-xl leading-tight text-center lg:text-left">
                    <?= htmlspecialchars($tenantName ?: 'Gestor de Loja') ?>
                </h1>
                <p class="text-base md:text-lg text-slate-300 mt-4 font-light tracking-wide text-center lg:text-left opacity-80">
                    Portal público e ambiente de trabalho restrito aos Obreiros da Oficina.
                </p>
            </header>

            <main class="w-full max-w-3xl mx-auto lg:mx-0 space-y-12">
                <?php if ($publicAdsEnabled && !empty($publicAds)): ?>
                <section>
                    <div class="flex items-center gap-4 mb-6">
                        <div class="h-6 w-1 bg-[#C9A227] rounded-full"></div>
                        <h2 class="font-cinzel text-xl text-[#C9A227] tracking-widest uppercase">Apoio Institucional</h2>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <?php foreach ($publicAds as $ad): ?>
                            <a href="<?= htmlspecialchars((string) ($ad['link_url'] ?? '#')) ?>" target="_blank" rel="noopener noreferrer" 
                               class="glass-panel hover:-translate-y-1 transition-transform duration-300 rounded-xl p-4 flex gap-4 items-center">
                                <div class="w-16 h-16 rounded-lg bg-white/10 shrink-0 overflow-hidden flex items-center justify-center">
                                    <?php if (!empty($ad['imagem_url'])): ?>
                                        <img src="<?= htmlspecialchars((string) $ad['imagem_url']) ?>" alt="Apoio" class="w-full h-full object-cover">
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <h3 class="text-sm font-semibold text-white"><?= htmlspecialchars((string) ($ad['titulo'] ?? 'Apoio')) ?></h3>
                                    <p class="text-xs text-slate-400 mt-1 line-clamp-2"><?= htmlspecialchars((string) ($ad['resumo'] ?? '')) ?></p>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endif; ?>

                <section>
                    <div class="flex items-center gap-4 mb-6">
                        <div class="h-6 w-1 bg-white rounded-full"></div>
                        <h2 class="font-cinzel text-xl text-white tracking-widest uppercase">Mural da Oficina</h2>
                    </div>
                    
                    <div class="grid grid-cols-1 gap-4">
                        <?php if (empty($publicConteudos)): ?>
                            <div class="py-12 text-center glass-panel rounded-xl border-dashed border-white/20">
                                <p class="text-slate-400 text-sm font-medium tracking-widest uppercase opacity-70">Nenhum comunicado no momento</p>
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
                                   class="glass-panel hover:bg-white/10 transition-colors duration-300 rounded-xl p-6 flex flex-col md:flex-row gap-6 items-start md:items-center">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-3 mb-3">
                                            <span class="px-2 py-1 text-[9px] font-bold text-[#C9A227] border border-[#C9A227]/30 rounded uppercase tracking-widest">
                                                <?= htmlspecialchars($tipo ?: 'INFO') ?>
                                            </span>
                                            <?php if ($inicioEm !== ''): ?>
                                                <span class="text-[10px] text-slate-400 tracking-widest">
                                                    <?= htmlspecialchars($inicioEm) ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <h3 class="text-lg font-bold text-white mb-2 leading-snug"><?= htmlspecialchars($titulo) ?></h3>
                                        <?php if ($resumo !== ''): ?>
                                            <p class="text-sm text-slate-300 line-clamp-2 leading-relaxed opacity-80"><?= htmlspecialchars($resumo) ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="shrink-0 text-[#C9A227] hidden md:block">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5l7 7-7 7"></path></svg>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>
            </main>
        </div>
        
        <footer class="relative z-10 p-8 text-center lg:text-left text-xs text-slate-500 font-medium tracking-wide">
            &copy; <?= date('Y') ?> Gestor de Loja. Protegido sob sigilo.
        </footer>
    </div>

    <!-- Right Panel: Login Form (Glassmorphism / Clean Premium) -->
    <aside class="w-full lg:w-[480px] shrink-0 bg-white dark:bg-[#0f1c2e] min-h-screen flex flex-col justify-center relative shadow-[-20px_0_40px_rgba(0,0,0,0.3)] z-20">
        
        <!-- Mobile background fallback -->
        <div class="lg:hidden absolute inset-0 bg-liturgical opacity-10 pointer-events-none"></div>

        <div class="relative z-10 p-8 sm:p-12 w-full max-w-md mx-auto">
            
            <div class="text-center mb-10">
                <div class="w-20 h-20 rounded-2xl overflow-hidden mx-auto mb-6 shadow-[0_10px_30px_rgba(0,0,0,0.1)] border border-slate-100 dark:border-slate-800 bg-white">
                    <?php if ($logoLogin): ?>
                        <img src="<?= htmlspecialchars($logoLogin) ?>" alt="Logo" class="w-full h-full object-contain p-2">
                    <?php else: ?>
                        <img src="/assets/logo-renascenca.png" alt="Logo" class="w-full h-full object-contain p-2">
                    <?php endif; ?>
                </div>
                <h2 class="font-cinzel text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Ingresso Restrito</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-2 font-medium tracking-wide">Identifique-se para adentrar aos trabalhos</p>
            </div>

            <?php if (!$tenantResolved): ?>
                <div class="mb-8 rounded-xl border border-red-200 bg-red-50 p-4 text-xs font-bold text-red-600 flex items-start gap-3">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    <span><?= htmlspecialchars($tenantUnavailableMessage ?: 'Loja não identificada pelo domínio.') ?></span>
                </div>
            <?php endif; ?>

            <?php if (isset($erroLogin)): ?>
                <div class="mb-8 rounded-xl border border-red-200 bg-red-50 p-4 text-xs font-bold text-red-600 flex items-start gap-3">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span><?= htmlspecialchars((string) $erroLogin) ?></span>
                </div>
            <?php endif; ?>

            <form action="/login" method="POST" class="space-y-5">
                <div>
                    <label for="matricula" class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">CIM / Matrícula</label>
                    <input id="matricula" name="matricula" type="text" required <?= !$tenantResolved ? 'disabled' : '' ?> 
                        class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-[#C9A227] focus:border-transparent transition-all" 
                        placeholder="Seu CIM numérico">
                </div>
                <div>
                    <label for="password" class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">Senha</label>
                    <input id="password" name="password" type="password" required <?= !$tenantResolved ? 'disabled' : '' ?> 
                        class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-[#C9A227] focus:border-transparent transition-all" 
                        placeholder="••••••••">
                </div>
                
                <div class="pt-6">
                    <button type="submit" name="acao" value="login" <?= !$tenantResolved ? 'disabled' : '' ?> 
                        class="w-full py-4 bg-[#1B3A5C] hover:bg-[#12273F] text-white rounded-xl font-bold text-sm tracking-wide shadow-lg shadow-[#1B3A5C]/30 transition-all flex justify-center items-center gap-2 group">
                        Entrar
                        <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                    </button>
                </div>
                
                <div class="pt-4 text-center">
                    <button type="submit" name="acao" value="solicitar" <?= !$tenantResolved ? 'disabled' : '' ?> 
                        class="text-xs font-semibold text-slate-500 hover:text-[#C9A227] transition-colors tracking-wide">
                        Primeiro acesso ou Esqueceu a senha?
                    </button>
                </div>
            </form>

            <div class="mt-12 text-center flex items-center justify-center gap-2">
                <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></div>
                <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">
                    Ambiente Coberto
                </p>
            </div>
        </div>
    </aside>

</body>
</html>
