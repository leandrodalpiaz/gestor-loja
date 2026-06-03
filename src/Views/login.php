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

// Carrega as configurações institucionais da Loja se estiver resolvida
$configuracaoLoja = [];
if ($tenantResolved) {
    try {
        $configuracaoLoja = (new \App\Models\ConfiguracaoLoja())->obter();
    } catch (\Throwable $e) {
        // Fallback seguro
    }
}

// Fallback de dados para exibição institucional elegante
$lojaNomeExibicao = $tenantName ?: 'Gestor de Loja';
$lojaNumero = $configuracaoLoja['numero_loja'] ?? '270';
$lojaRito = $configuracaoLoja['rito'] ?? 'Escocês Antigo e Aceito (REAA)';
$lojaOriente = $configuracaoLoja['oriente'] ?? 'Arroio do Sal / RS';
$lojaFundacao = !empty($configuracaoLoja['data_fundacao']) ? date('d/m/Y', strtotime($configuracaoLoja['data_fundacao'])) : '20/12/2017';
$lojaPotencia = $configuracaoLoja['potencia_sigla'] ?? 'GLOJARS';
$lojaHistoria = $configuracaoLoja['historia_loja'] ?? '';
$lojaTemplo = $configuracaoLoja['nome_templo'] ?? '';
$lojaEndereco = $configuracaoLoja['templo_endereco'] ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Átrio - <?= htmlspecialchars($lojaNomeExibicao) ?></title>
    <meta name="theme-color" content="#050B14">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/assets/css/erp_design_system.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700;800;900&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        liturgical: {
                            bg: '#050B14',
                            surface: '#0B132B',
                            accent: '#1C2541',
                            gold: '#C9A227',
                            goldHover: '#E5B82B'
                        }
                    },
                    fontFamily: {
                        cinzel: ['Cinzel', 'serif'],
                        inter: ['Inter', 'sans-serif']
                    }
                }
            }
        }
    </script>
    
    <style>
        [x-cloak] { display: none !important; }
        
        /* Premium radial gradients background */
        .bg-premium-radial {
            background-color: #050B14;
            background-image: 
                radial-gradient(circle at 10% 15%, rgba(201, 162, 39, 0.07) 0%, transparent 40%),
                radial-gradient(circle at 85% 85%, rgba(28, 77, 138, 0.25) 0%, transparent 55%),
                radial-gradient(circle at 50% 50%, rgba(11, 19, 43, 0.5) 0%, #050B14 100%);
        }
        
        /* Glassmorphic elements */
        .glass-card {
            background: rgba(11, 19, 43, 0.45);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
        }
        
        .glass-card-gold {
            background: rgba(11, 19, 43, 0.65);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(201, 162, 39, 0.15);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5), 0 0 40px rgba(201, 162, 39, 0.03);
        }
        
        .glass-navbar {
            background: rgba(5, 11, 20, 0.75);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.03);
        }
        
        /* Subtle float animation for graphics */
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-8px); }
            100% { transform: translateY(0px); }
        }
        .animate-float {
            animation: float 6s ease-in-out infinite;
        }

        /* Glowing borders on focus */
        .focus-glow:focus {
            box-shadow: 0 0 15px rgba(201, 162, 39, 0.25);
            border-color: rgba(201, 162, 39, 0.4);
        }
    </style>
</head>
<body class="font-inter antialiased min-h-screen text-slate-300 bg-premium-radial overflow-x-hidden">

    <!-- Header Navigation -->
    <nav class="glass-navbar fixed top-0 left-0 right-0 z-50">
        <div class="max-w-7xl mx-auto px-6 h-20 flex justify-between items-center">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-white/5 overflow-hidden flex items-center justify-center p-1 border border-white/10 hover:border-liturgical-gold/40 transition-colors">
                    <?php if ($logoLogin): ?>
                        <img src="<?= htmlspecialchars($logoLogin) ?>" alt="Logo" class="max-w-full max-h-full object-contain">
                    <?php else: ?>
                        <img src="/assets/logo-renascenca.png" alt="Logo" class="max-w-full max-h-full object-contain">
                    <?php endif; ?>
                </div>
                <div>
                    <span class="font-cinzel text-lg font-bold tracking-widest text-white">ÁTRIO</span>
                    <?php if ($tenantResolved): ?>
                        <span class="hidden md:inline text-xs font-semibold tracking-wider text-liturgical-gold/80 uppercase ml-2 border-l border-white/15 pl-2"><?= htmlspecialchars($lojaNomeExibicao) ?></span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="flex items-center gap-6">
                <div class="hidden md:flex items-center gap-6">
                    <a href="#inicio" class="text-xs font-bold uppercase tracking-wider text-slate-400 hover:text-white transition-colors">Início</a>
                    <?php if (!empty($lojaHistoria)): ?>
                        <a href="#historia" class="text-xs font-bold uppercase tracking-wider text-slate-400 hover:text-white transition-colors">História</a>
                    <?php endif; ?>
                    <?php if (!empty($publicConteudos)): ?>
                        <a href="#mural" class="text-xs font-bold uppercase tracking-wider text-slate-400 hover:text-white transition-colors">Mural</a>
                    <?php endif; ?>
                    <?php if ($publicAdsEnabled && !empty($publicAds)): ?>
                        <a href="#apoio" class="text-xs font-bold uppercase tracking-wider text-slate-400 hover:text-white transition-colors">Apoio</a>
                    <?php endif; ?>
                </div>
                
                <a href="#login-box" class="px-4 py-2 bg-white/5 hover:bg-white/10 text-white rounded-xl font-bold text-xs uppercase tracking-wider border border-white/10 hover:border-white/20 transition-all">
                    Área do Obreiro
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Hero & Login Section -->
    <header id="inicio" class="relative max-w-7xl mx-auto px-6 pt-32 pb-16 md:pb-24 lg:min-h-screen lg:flex lg:items-center">
        <!-- Parchment design texture overlay -->
        <div class="absolute inset-0 opacity-[0.02] mix-blend-overlay pointer-events-none" style="background-image: url('data:image/svg+xml,%3Csvg viewBox=%220 0 200 200%22 xmlns=%22http://www.w3.org/2000/svg%22%3E%3Cfilter id=%22noiseFilter%22%3E%3CfeTurbulence type=%22fractalNoise%22 baseFrequency=%220.65%22 numOctaves=%223%22 stitchTiles=%22stitch%22/%3E%3C/filter%3E%3Crect width=%22100%25%22 height=%22100%25%22 filter=%22url(%23noiseFilter)%22/%3E%3C/svg%3E');"></div>

        <div class="w-full lg:grid lg:grid-cols-12 lg:gap-12 items-center">
            
            <!-- Left Info Panel -->
            <div class="lg:col-span-7 mb-12 lg:mb-0 space-y-8 text-center lg:text-left">
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-liturgical-gold/10 border border-liturgical-gold/20 text-liturgical-gold text-xs font-bold uppercase tracking-widest mx-auto lg:mx-0">
                    <svg class="w-3 h-3 animate-pulse" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2L2 22h20L12 2zm0 3.99L19.53 19H4.47L12 5.99zM11 16h2v2h-2v-2zm0-6h2v4h-2v-4z"/></svg>
                    Portal Público Oficial
                </div>
                
                <h1 class="font-cinzel text-4xl sm:text-5xl lg:text-6xl font-black tracking-wider text-white drop-shadow-lg leading-tight">
                    <?= htmlspecialchars($lojaNomeExibicao) ?>
                </h1>
                
                <p class="font-cinzel text-lg md:text-xl text-liturgical-gold/90 font-medium tracking-widest max-w-2xl mx-auto lg:mx-0">
                    Trabalho • Fraternidade • Busca da Verdade
                </p>
                
                <p class="text-base md:text-lg text-slate-400 font-light leading-relaxed max-w-2xl mx-auto lg:mx-0">
                    O Átrio é a entrada oficial da nossa Oficina. Um ambiente de comunicação pública para visitantes e um portal seguro para a administração e desenvolvimento de nossos Obreiros.
                </p>

                <!-- Small Info Badges -->
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 max-w-xl mx-auto lg:mx-0 pt-4">
                    <div class="glass-card p-4 rounded-2xl flex flex-col justify-center items-center text-center hover:border-liturgical-gold/25 transition-all">
                        <span class="text-[10px] text-slate-500 font-bold uppercase tracking-widest mb-1">Rito Praticado</span>
                        <span class="text-xs font-semibold text-white">REAA</span>
                    </div>
                    <div class="glass-card p-4 rounded-2xl flex flex-col justify-center items-center text-center hover:border-liturgical-gold/25 transition-all">
                        <span class="text-[10px] text-slate-500 font-bold uppercase tracking-widest mb-1">Oriente</span>
                        <span class="text-xs font-semibold text-white"><?= htmlspecialchars($lojaOriente) ?></span>
                    </div>
                    <div class="glass-card p-4 rounded-2xl flex flex-col justify-center items-center text-center hover:border-liturgical-gold/25 transition-all col-span-2 sm:col-span-1">
                        <span class="text-[10px] text-slate-500 font-bold uppercase tracking-widest mb-1">Fundada em</span>
                        <span class="text-xs font-semibold text-white"><?= htmlspecialchars($lojaFundacao) ?></span>
                    </div>
                </div>

                <div class="flex flex-wrap gap-4 justify-center lg:justify-start pt-2">
                    <?php if (!empty($lojaHistoria)): ?>
                        <a href="#historia" class="px-6 py-3.5 bg-white/5 hover:bg-white/10 text-white rounded-xl font-bold text-sm tracking-wide border border-white/10 transition-all flex items-center gap-2 group">
                            Nossa História
                            <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($publicConteudos)): ?>
                        <a href="#mural" class="px-6 py-3.5 text-slate-400 hover:text-white rounded-xl font-bold text-sm tracking-wide transition-colors">
                            Mural de Avisos
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Right Login Form Panel -->
            <div id="login-box" class="lg:col-span-5 flex justify-center lg:justify-end">
                <div class="w-full max-w-md glass-card-gold rounded-3xl p-8 md:p-10 relative overflow-hidden transition-all duration-500 hover:border-liturgical-gold/30">
                    
                    <!-- Decorative glowing aura behind form -->
                    <div class="absolute -top-24 -right-24 w-48 h-48 rounded-full bg-liturgical-gold/5 blur-3xl pointer-events-none"></div>
                    
                    <div class="text-center mb-8 relative z-10">
                        <div class="w-20 h-20 rounded-2xl bg-white/5 mx-auto mb-5 border border-white/10 flex items-center justify-center p-2 shadow-inner hover:scale-105 transition-transform duration-300">
                            <?php if ($logoLogin): ?>
                                <img src="<?= htmlspecialchars($logoLogin) ?>" alt="Logo" class="max-w-full max-h-full object-contain">
                            <?php else: ?>
                                <img src="/assets/logo-renascenca.png" alt="Logo" class="max-w-full max-h-full object-contain">
                            <?php endif; ?>
                        </div>
                        <h2 class="font-cinzel text-2xl font-bold tracking-wider text-white">Ingresso Restrito</h2>
                        <p class="text-xs text-slate-400 mt-2 font-medium tracking-widest uppercase">Identifique-se para adentrar ao Átrio</p>
                    </div>

                    <?php if (!$tenantResolved): ?>
                        <div class="mb-6 rounded-2xl border border-rose-500/20 bg-rose-500/5 p-4 text-xs font-medium text-rose-400 flex items-start gap-3 relative z-10">
                            <svg class="w-5 h-5 shrink-0 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            <span><?= htmlspecialchars($tenantUnavailableMessage ?: 'Loja não identificada pelo domínio.') ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($erroLogin)): ?>
                        <div class="mb-6 rounded-2xl border border-rose-500/20 bg-rose-500/5 p-4 text-xs font-semibold text-rose-400 flex items-start gap-3 relative z-10">
                            <svg class="w-5 h-5 shrink-0 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <span><?= htmlspecialchars((string) $erroLogin) ?></span>
                        </div>
                    <?php endif; ?>

                    <form action="/login" method="POST" class="space-y-6 relative z-10">
                        <div class="space-y-2">
                            <label for="matricula" class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1">C.I.M. (Código de Obreiro)</label>
                            <input id="matricula" name="matricula" type="text" required <?= !$tenantResolved ? 'disabled' : '' ?> 
                                class="w-full px-4 py-3.5 rounded-xl border border-white/10 bg-white/5 text-white text-sm focus:outline-none focus:ring-1 focus:ring-liturgical-gold focus:border-liturgical-gold transition-all duration-300 focus-glow" 
                                placeholder="Seu C.I.M. numérico">
                        </div>
                        
                        <div class="space-y-2">
                            <div class="flex justify-between items-center px-1">
                                <label for="password" class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest">Senha</label>
                            </div>
                            <input id="password" name="password" type="password" required <?= !$tenantResolved ? 'disabled' : '' ?> 
                                class="w-full px-4 py-3.5 rounded-xl border border-white/10 bg-white/5 text-white text-sm focus:outline-none focus:ring-1 focus:ring-liturgical-gold focus:border-liturgical-gold transition-all duration-300 focus-glow" 
                                placeholder="••••••••">
                        </div>
                        
                        <div class="pt-4">
                            <button type="submit" name="acao" value="login" <?= !$tenantResolved ? 'disabled' : '' ?> 
                                class="w-full py-4 bg-liturgical-gold hover:bg-liturgical-goldHover text-[#050B14] rounded-xl font-bold text-sm tracking-widest uppercase shadow-xl shadow-liturgical-gold/10 hover:shadow-liturgical-gold/25 transition-all duration-300 flex justify-center items-center gap-2 group">
                                Adentrar ao Templo
                                <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform text-[#050B14]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                            </button>
                        </div>
                        
                        <div class="pt-2 text-center">
                            <button type="submit" name="acao" value="solicitar" <?= !$tenantResolved ? 'disabled' : '' ?> 
                                class="text-xs font-semibold text-slate-400 hover:text-liturgical-gold hover:underline transition-colors tracking-wide">
                                Primeiro acesso ou recuperação?
                            </button>
                        </div>
                    </form>

                    <div class="mt-8 pt-6 border-t border-white/5 text-center flex items-center justify-center gap-2 relative z-10">
                        <span class="relative flex h-2 w-2">
                          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                          <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                        </span>
                        <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">
                            Ambiente Coberto e Protegido
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <?php if ($tenantResolved): ?>
        <!-- History Section -->
        <?php if (!empty($lojaHistoria)): ?>
            <section id="historia" class="relative py-20 bg-liturgical-surface/30 border-y border-white/5">
                <div class="max-w-4xl mx-auto px-6">
                    <div class="text-center mb-12">
                        <span class="text-liturgical-gold text-xs font-bold uppercase tracking-widest">Memória e Legado</span>
                        <h2 class="font-cinzel text-3xl md:text-4xl text-white font-bold tracking-wider mt-3">História da Oficina</h2>
                        <div class="w-16 h-0.5 bg-liturgical-gold/40 mx-auto mt-4"></div>
                    </div>
                    
                    <div class="glass-card rounded-3xl p-8 md:p-10 shadow-2xl relative">
                        <div class="absolute top-6 right-8 text-white/5 font-cinzel text-7xl font-bold select-none pointer-events-none">270</div>
                        
                        <!-- History Text Container with Read-More logic -->
                        <div id="historia-texto" class="prose prose-invert prose-slate max-w-none text-slate-300 leading-relaxed space-y-6 text-justify text-sm md:text-base font-light transition-all duration-500 line-clamp-[8] overflow-hidden">
                            <?= nl2br(htmlspecialchars($lojaHistoria)) ?>
                        </div>
                        
                        <div class="mt-8 flex justify-center border-t border-white/5 pt-6">
                            <button id="btn-ler-historia" onclick="toggleHistoria()" class="px-6 py-2.5 bg-white/5 hover:bg-white/10 border border-white/10 rounded-xl font-bold text-xs uppercase tracking-wider text-white transition-all flex items-center gap-2">
                                <span>Ler história completa</span>
                                <svg id="icone-ler-historia" class="w-4 h-4 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <!-- Notice Board (Mural) Section -->
        <?php if (!empty($publicConteudos)): ?>
            <section id="mural" class="py-20 max-w-7xl mx-auto px-6">
                <div class="text-center mb-12">
                    <span class="text-liturgical-gold text-xs font-bold uppercase tracking-widest">Comunicação e Notícias</span>
                    <h2 class="font-cinzel text-3xl md:text-4xl text-white font-bold tracking-wider mt-3">Mural da Oficina</h2>
                    <div class="w-16 h-0.5 bg-liturgical-gold/40 mx-auto mt-4"></div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <?php foreach ($publicConteudos as $item): ?>
                        <?php
                        $tipo = strtoupper(trim((string) ($item['tipo'] ?? '')));
                        $titulo = (string) ($item['titulo'] ?? '');
                        $resumo = (string) ($item['resumo'] ?? '');
                        $inicioEm = (string) ($item['inicio_em'] ?? '');
                        $linkUrl = (string) ($item['link_url'] ?? '');
                        
                        // Badge styling based on content type
                        $badgeColor = match(strtolower($tipo)) {
                            'agenda' => 'bg-amber-500/10 text-amber-400 border-amber-500/20',
                            'noticia' => 'bg-sky-500/10 text-sky-400 border-sky-500/20',
                            'comunicado' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
                            default => 'bg-liturgical-gold/10 text-liturgical-gold border-liturgical-gold/20'
                        };
                        ?>
                        <div class="glass-card hover:-translate-y-1 transition-all duration-300 rounded-3xl p-6 flex flex-col justify-between hover:border-white/10 group">
                            <div>
                                <div class="flex justify-between items-center mb-4">
                                    <span class="px-2.5 py-1 text-[9px] font-bold border rounded uppercase tracking-widest <?= $badgeColor ?>">
                                        <?= htmlspecialchars($tipo ?: 'INFO') ?>
                                    </span>
                                    <?php if ($inicioEm !== ''): ?>
                                        <span class="text-[10px] text-slate-500 font-bold uppercase tracking-wider">
                                            <?= htmlspecialchars($inicioEm) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <h3 class="text-lg font-bold text-white mb-3 leading-snug group-hover:text-liturgical-gold transition-colors"><?= htmlspecialchars($titulo) ?></h3>
                                <?php if ($resumo !== ''): ?>
                                    <p class="text-slate-400 text-sm font-light leading-relaxed line-clamp-3 mb-6"><?= htmlspecialchars($resumo) ?></p>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($linkUrl !== '' && $linkUrl !== '#'): ?>
                                <a href="<?= htmlspecialchars($linkUrl) ?>" target="_blank" rel="noopener noreferrer" 
                                   class="text-xs font-bold text-liturgical-gold hover:text-white uppercase tracking-wider flex items-center gap-1.5 transition-colors">
                                    Saber mais
                                    <svg class="w-3.5 h-3.5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <!-- Sponsorship/Ads Section -->
        <?php if ($publicAdsEnabled && !empty($publicAds)): ?>
            <section id="apoio" class="py-20 bg-liturgical-surface/20 border-t border-white/5">
                <div class="max-w-7xl mx-auto px-6">
                    <div class="text-center mb-12">
                        <span class="text-liturgical-gold text-xs font-bold uppercase tracking-widest">Colaboradores</span>
                        <h2 class="font-cinzel text-2xl md:text-3xl text-white font-bold tracking-wider mt-3">Apoio Institucional</h2>
                        <div class="w-16 h-0.5 bg-liturgical-gold/40 mx-auto mt-4"></div>
                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach ($publicAds as $ad): ?>
                            <a href="<?= htmlspecialchars((string) ($ad['link_url'] ?? '#')) ?>" target="_blank" rel="noopener noreferrer" 
                               class="glass-card hover:bg-white/5 transition-colors duration-300 rounded-2xl p-5 flex gap-4 items-center border border-white/5">
                                <div class="w-16 h-16 rounded-xl bg-white/5 shrink-0 overflow-hidden flex items-center justify-center border border-white/10 p-1">
                                    <?php if (!empty($ad['imagem_url'])): ?>
                                        <img src="<?= htmlspecialchars((string) $ad['imagem_url']) ?>" alt="Apoio" class="w-full h-full object-contain">
                                    <?php endif; ?>
                                </div>
                                <div class="space-y-1">
                                    <h3 class="text-sm font-bold text-white leading-tight line-clamp-1"><?= htmlspecialchars((string) ($ad['titulo'] ?? 'Apoio')) ?></h3>
                                    <p class="text-xs text-slate-500 font-light leading-snug line-clamp-2"><?= htmlspecialchars((string) ($ad['resumo'] ?? '')) ?></p>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Footer -->
    <footer class="bg-black/40 border-t border-white/5 py-12 relative z-10 text-center text-xs tracking-wider font-semibold text-slate-600">
        <div class="max-w-7xl mx-auto px-6 flex flex-col md:flex-row justify-between items-center gap-4">
            <p class="font-cinzel text-slate-400">
                &copy; <?= date('Y') ?> <?= htmlspecialchars($lojaNomeExibicao) ?>. Todos os direitos reservados.
            </p>
            <div class="flex items-center gap-1.5 justify-center md:justify-start">
                <svg class="w-4 h-4 text-slate-600" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
                <p class="uppercase text-[9px] font-bold text-slate-500">
                    O dever de segredo e discrição nos acompanha.
                </p>
            </div>
        </div>
    </footer>

    <script>
        // JavaScript for handling the read-more button on the history text
        function toggleHistoria() {
            const texto = document.getElementById('historia-texto');
            const botao = document.getElementById('btn-ler-historia');
            const icone = document.getElementById('icone-ler-historia');
            const spans = botao.getElementsByTagName('span');
            
            if (texto.classList.contains('line-clamp-[8]')) {
                texto.classList.remove('line-clamp-[8]');
                texto.classList.add('overflow-visible');
                spans[0].innerText = 'Recolher história';
                icone.style.transform = 'rotate(180deg)';
            } else {
                texto.classList.add('line-clamp-[8]');
                texto.classList.remove('overflow-visible');
                spans[0].innerText = 'Ler história completa';
                icone.style.transform = 'rotate(0deg)';
                // Scroll back to history header slightly above the box
                document.getElementById('historia').scrollIntoView({ behavior: 'smooth' });
            }
        }
    </script>
</body>
</html>
