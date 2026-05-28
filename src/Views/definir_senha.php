<?php
declare(strict_types=1);

$tenantSlug = trim((string) ($tenantSlug ?? $_SESSION['tenant_slug'] ?? ''));
$tenantName = trim((string) ($tenantName ?? $_SESSION['tenant_name'] ?? ''));
$logoLogin = \App\Core\Tenant\TenantAssetResolver::resolveLogo($tenantSlug);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Definir Nova Senha - <?= htmlspecialchars($tenantName ?: 'Gestor de Loja') ?></title>
    <meta name="theme-color" content="#0A1628">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/assets/css/erp_design_system.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .font-cinzel { font-family: 'Cinzel', serif; }
        .font-inter { font-family: 'Inter', sans-serif; }
        .glass-panel {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .bg-liturgical {
            background-color: #0A1628;
            background-image: radial-gradient(circle at 30% 20%, rgba(201, 162, 39, 0.12) 0%, transparent 60%),
                              radial-gradient(circle at 80% 80%, rgba(27, 58, 92, 0.5) 0%, transparent 50%);
        }
    </style>
</head>
<body class="font-inter antialiased min-h-screen flex items-center justify-center bg-liturgical relative overflow-hidden">

    <!-- Texture overlay for parchment/classic feel -->
    <div class="absolute inset-0 opacity-20 mix-blend-overlay pointer-events-none" style="background-image: url('data:image/svg+xml,%3Csvg viewBox=%220 0 200 200%22 xmlns=%22http://www.w3.org/2000/svg%22%3E%3Cfilter id=%22noiseFilter%22%3E%3CfeTurbulence type=%22fractalNoise%22 baseFrequency=%220.65%22 numOctaves=%223%22 stitchTiles=%22stitch%22/%3E%3C/filter%3E%3Crect width=%22100%25%22 height=%22100%25%22 filter=%22url(%23noiseFilter)%22/%3E%3C/svg%3E');"></div>

    <div class="relative z-10 w-full max-w-md mx-auto p-6">
        <div class="glass-panel rounded-2xl p-8 sm:p-10 shadow-[0_20px_50px_rgba(0,0,0,0.5)] border border-white/10">
            <div class="text-center mb-8">
                <div class="w-20 h-20 rounded-2xl overflow-hidden mx-auto mb-6 shadow-[0_10px_30px_rgba(0,0,0,0.3)] border border-white/10 bg-white/5 flex items-center justify-center p-2">
                    <?php if ($logoLogin): ?>
                        <img src="<?= htmlspecialchars($logoLogin) ?>" alt="Logo" class="max-w-full max-h-full object-contain">
                    <?php else: ?>
                        <img src="/assets/logo-renascenca.png" alt="Logo" class="max-w-full max-h-full object-contain">
                    <?php endif; ?>
                </div>
                <h2 class="font-cinzel text-2xl font-bold tracking-tight text-white mb-2">Primeiro Acesso</h2>
                <p class="text-xs text-slate-300 font-medium tracking-wide">Para sua segurança, defina uma nova senha definitiva para os acessos futuros.</p>
            </div>

            <?php if (isset($erroDefinirSenha)): ?>
                <div class="mb-6 rounded-xl border border-red-500/30 bg-red-950/40 p-4 text-xs font-semibold text-red-300 flex items-start gap-3">
                    <svg class="w-5 h-5 shrink-0 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span><?= htmlspecialchars((string) $erroDefinirSenha) ?></span>
                </div>
            <?php endif; ?>

            <form action="/definir-senha" method="POST" class="space-y-5">
                <div>
                    <label for="nova_senha" class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2 ml-1">Nova Senha</label>
                    <input id="nova_senha" name="nova_senha" type="password" required minlength="6"
                        class="w-full px-4 py-3 rounded-xl border border-white/10 bg-white/5 text-white text-sm focus:outline-none focus:ring-2 focus:ring-[#C9A227] focus:border-transparent transition-all placeholder-white/20" 
                        placeholder="Mínimo 6 caracteres">
                </div>
                <div>
                    <label for="confirmar_senha" class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2 ml-1">Confirmar Nova Senha</label>
                    <input id="confirmar_senha" name="confirmar_senha" type="password" required minlength="6"
                        class="w-full px-4 py-3 rounded-xl border border-white/10 bg-white/5 text-white text-sm focus:outline-none focus:ring-2 focus:ring-[#C9A227] focus:border-transparent transition-all placeholder-white/20" 
                        placeholder="Confirme sua nova senha">
                </div>
                
                <div class="pt-4">
                    <button type="submit" 
                        class="w-full py-4 bg-[#C9A227] hover:bg-[#b08e22] text-white rounded-xl font-bold text-sm tracking-wide shadow-lg shadow-[#C9A227]/20 hover:shadow-[#C9A227]/40 hover-lift flex justify-center items-center gap-2 group transition-all">
                        Salvar e Acessar
                        <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                    </button>
                </div>
            </form>

            <div class="mt-8 text-center">
                <a href="/logout" class="text-xs text-slate-400 hover:text-white transition-colors tracking-wide underline">
                    Voltar para o Login
                </a>
            </div>
        </div>
    </div>

</body>
</html>
