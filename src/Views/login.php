<?php
declare(strict_types=1);

// #############################################################################
// LÓGICA DE NEGÓCIO E HELPERS
// #############################################################################

$tenantSlug = trim((string) ($tenantSlug ?? $_SESSION['tenant_slug'] ?? ''));
$tenantName = trim((string) ($tenantName ?? $_SESSION['tenant_name'] ?? ''));
$tenantResolved = !empty($tenantResolved) && $tenantSlug !== '';
$tenantUnavailableMessage = trim((string) ($tenantUnavailableMessage ?? ''));
$logoLogin = \App\Core\Tenant\TenantAssetResolver::resolveLogo($tenantSlug);

// #############################################################################
// RENDERIZAÇÃO
// #############################################################################
?>
<!DOCTYPE html>
<html lang="pt-BR" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acesso Restrito - <?= htmlspecialchars($tenantName ?: 'Gestor de Loja') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style type="text/tailwindcss">
        @layer components {
            .form-input {
                @apply w-full px-4 py-3 rounded-md bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-blue-500 focus:outline-none focus:border-blue-500 transition-colors shadow-sm;
            }
            .btn {
                @apply w-full flex justify-center items-center px-4 py-3 rounded-md text-sm font-semibold shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 transition-all;
            }
            .btn-primary {
                @apply bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500 dark:focus:ring-offset-slate-900;
            }
            .btn-secondary {
                @apply bg-slate-200 text-slate-800 hover:bg-slate-300 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 focus:ring-slate-500 dark:focus:ring-offset-slate-900;
            }
            .alert {
                @apply p-4 rounded-md text-sm mb-4 border;
            }
            .alert-danger {
                @apply bg-red-50 text-red-800 border-red-200 dark:bg-red-900/30 dark:text-red-300 dark:border-red-900/50;
            }
            .alert-warning {
                @apply bg-amber-50 text-amber-800 border-amber-200 dark:bg-amber-900/30 dark:text-amber-300 dark:border-amber-900/50;
            }
        }
    </style>
</head>
<body class="h-full bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 font-['Inter'] antialiased">
    <div class="flex min-h-screen">
        <!-- Coluna da Esquerda (Branding) -->
        <div class="hidden lg:flex flex-1 flex-col justify-center items-center bg-blue-700 dark:bg-slate-900 text-white p-12 relative overflow-hidden border-r border-blue-800 dark:border-slate-800 shadow-inner">
            <div class="absolute inset-0 bg-gradient-to-br from-blue-800/80 to-blue-900/90 dark:from-slate-900/90 dark:to-black/90 mix-blend-multiply"></div>
            <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/stardust.png')] opacity-20 mix-blend-overlay"></div>
            <div class="z-10 text-center space-y-8 max-w-lg">
                <?php if ($logoLogin): ?>
                    <img src="<?= htmlspecialchars($logoLogin) ?>" alt="Brasão da Loja" class="h-32 w-32 mx-auto object-contain drop-shadow-xl">
                <?php else: ?>
                    <div class="h-32 w-32 mx-auto flex items-center justify-center rounded-full bg-white/5 border border-white/20 text-6xl font-serif shadow-2xl backdrop-blur-sm">∴</div>
                <?php endif; ?>
                
                <div class="space-y-4">
                    <h1 class="text-4xl sm:text-5xl font-extrabold tracking-tight text-white drop-shadow-md">
                        <?= htmlspecialchars($tenantName ?: 'Gestor de Loja') ?>
                    </h1>
                    <p class="text-lg sm:text-xl text-blue-100 dark:text-slate-300 font-medium">
                        Plataforma de gestão integrada para Lojas Maçônicas.
                    </p>
                </div>
                
                <div class="pt-8 border-t border-white/10 flex justify-center space-x-4 text-sm text-blue-200 dark:text-slate-400">
                    <span class="flex items-center"><svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg> Acesso Seguro</span>
                    <span class="flex items-center"><svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg> Gestão Centralizada</span>
                </div>
            </div>
        </div>

        <!-- Coluna da Direita (Formulário) -->
        <div class="flex flex-1 flex-col justify-center py-12 px-4 sm:px-6 lg:flex-none lg:w-[32rem] xl:w-[36rem] bg-white dark:bg-slate-900 border-l border-slate-200 dark:border-slate-800 shadow-2xl z-20">
            <div class="mx-auto w-full max-w-sm lg:w-full lg:max-w-md">
                
                <!-- Header Mobile -->
                <div class="lg:hidden flex flex-col items-center mb-10 text-center space-y-4">
                    <?php if ($logoLogin): ?>
                        <img src="<?= htmlspecialchars($logoLogin) ?>" alt="Brasão da Loja" class="h-20 w-20 object-contain drop-shadow">
                    <?php else: ?>
                        <div class="h-20 w-20 flex items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800 border-2 border-slate-200 dark:border-slate-700 text-3xl font-serif text-slate-700 dark:text-slate-300">∴</div>
                    <?php endif; ?>
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-white"><?= htmlspecialchars($tenantName ?: 'Gestor de Loja') ?></h1>
                </div>

                <div class="bg-white dark:bg-slate-800/50 p-6 sm:p-8 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700/50 backdrop-blur-xl">
                    <div class="mb-8 hidden lg:block">
                        <h2 class="text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white">Acesso Restrito</h2>
                        <p class="mt-2 text-sm text-slate-600 dark:text-slate-400 font-medium">
                            Área exclusiva para membros autorizados.
                        </p>
                    </div>

                    <div>
                        <?php if (!$tenantResolved): ?>
                            <div class="alert alert-warning flex items-start">
                                <svg class="w-5 h-5 mr-3 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                <div><?= htmlspecialchars($tenantUnavailableMessage ?: 'Loja não identificada. Verifique a configuração do ambiente.') ?></div>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($erroLogin)): ?>
                            <div class="alert alert-danger flex items-start">
                                <svg class="w-5 h-5 mr-3 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <div><?= htmlspecialchars($erroLogin) ?></div>
                            </div>
                        <?php endif; ?>

                        <form action="/login" method="POST" class="space-y-5">
                            <div>
                                <label for="matricula" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">CIM / Matrícula</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                    </div>
                                    <input id="matricula" name="matricula" type="text" required <?= !$tenantResolved ? 'disabled' : '' ?> class="form-input pl-10" placeholder="Digite seu CIM">
                                </div>
                            </div>

                            <div>
                                <label for="password" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Senha de Acesso</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                                    </div>
                                    <input id="password" name="password" type="password" required <?= !$tenantResolved ? 'disabled' : '' ?> class="form-input pl-10" placeholder="Digite sua senha">
                                </div>
                            </div>

                            <div class="space-y-4 pt-2">
                                <button type="submit" name="acao" value="login" <?= !$tenantResolved ? 'disabled' : '' ?> class="btn btn-primary disabled:opacity-50 disabled:cursor-not-allowed text-base font-bold">
                                    Entrar no Sistema
                                </button>
                                <button type="submit" name="acao" value="solicitar" <?= !$tenantResolved ? 'disabled' : '' ?> class="btn btn-secondary disabled:opacity-50 disabled:cursor-not-allowed">
                                    Solicitar Acesso
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="mt-8 flex items-start p-4 rounded-xl bg-slate-100 dark:bg-slate-800/80 text-sm text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700">
                    <svg class="w-5 h-5 mr-3 mt-0.5 shrink-0 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <p>Em caso de dúvida ou problema de acesso, procure a Secretaria da sua Loja para validação cadastral.</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
