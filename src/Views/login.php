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

$publicConteudos = isset($publicConteudos) && is_array($publicConteudos) ? $publicConteudos : [];
$publicAds = isset($publicAds) && is_array($publicAds) ? $publicAds : [];
$publicAdsEnabled = (bool) ($publicAdsEnabled ?? false);

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
    <link rel="stylesheet" href="/assets/css/erp_design_system.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style type="text/tailwindcss">
        @layer components {
            .form-input {
                @apply w-full px-4 py-3 rounded-lg bg-erp-surface border border-erp-border text-erp-text focus:ring-2 focus:ring-erp-info/40 focus:outline-none focus:border-erp-info transition-colors;
            }
            .btn {
                @apply w-full flex justify-center items-center px-4 py-3 rounded-lg text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors;
            }
            .btn-primary {
                @apply bg-erp-brand text-white hover:opacity-95 focus:ring-erp-info/50 focus:ring-offset-erp-bg;
            }
            .btn-secondary {
                @apply bg-erp-surface-2 text-erp-text border border-erp-border hover:opacity-95 focus:ring-erp-info/40 focus:ring-offset-erp-bg;
            }
            .alert {
                @apply p-4 rounded-lg text-sm mb-4 border;
            }
            .alert-danger {
                @apply bg-rose-50 text-rose-800 border-rose-200 dark:bg-rose-950/40 dark:text-rose-200 dark:border-rose-900/40;
            }
            .alert-warning {
                @apply bg-amber-50 text-amber-900 border-amber-200 dark:bg-amber-950/40 dark:text-amber-100 dark:border-amber-900/40;
            }
            .public-card {
                @apply rounded-2xl border border-erp-border bg-erp-surface px-4 py-4;
            }
        }
    </style>
</head>
<body class="h-full bg-erp-bg text-erp-text font-['Inter'] antialiased">
    <div class="min-h-screen">
        <header class="border-b border-erp-border bg-erp-surface">
            <div class="erp-container py-4 flex items-center justify-between gap-4">
                <div class="flex items-center gap-3 min-w-0">
                    <?php if ($logoLogin): ?>
                        <img src="<?= htmlspecialchars($logoLogin) ?>" alt="Brasão da Loja" class="h-10 w-10 object-contain">
                    <?php else: ?>
                        <div class="h-10 w-10 flex items-center justify-center rounded-full bg-erp-surface-2 border border-erp-border text-lg font-serif">∴</div>
                    <?php endif; ?>
                    <div class="min-w-0">
                        <div class="text-sm font-semibold text-erp-text truncate"><?= htmlspecialchars($tenantName ?: 'Gestor de Loja') ?></div>
                        <div class="text-xs text-erp-muted truncate">Acesso e informações públicas</div>
                    </div>
                </div>
                <div class="text-xs text-erp-muted whitespace-nowrap">Painel Administrativo</div>
            </div>
        </header>

        <main class="erp-container py-6 sm:py-10">
            <div class="grid gap-6 lg:grid-cols-[1.2fr_0.8fr] lg:items-start">
                <section class="order-2 lg:order-1 grid gap-4">
                    <div class="erp-card">
                        <div class="erp-card-header px-5 py-4">
                            <div class="text-sm font-semibold text-erp-text">Comunicados e agenda</div>
                            <div class="mt-1 text-xs text-erp-muted">Informações públicas exibidas em /login.</div>
                        </div>
                        <div class="p-5 grid gap-3">
                            <?php if ($publicAdsEnabled && !empty($publicAds)): ?>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <?php foreach ($publicAds as $ad): ?>
                                        <a href="<?= htmlspecialchars((string) ($ad['link_url'] ?? '#')) ?>" class="public-card hover:opacity-95 transition-opacity">
                                            <div class="flex gap-3">
                                                <?php if (!empty($ad['imagem_url'])): ?>
                                                    <img src="<?= htmlspecialchars((string) $ad['imagem_url']) ?>" alt="" class="h-12 w-12 rounded-lg border border-erp-border object-cover bg-erp-surface-2">
                                                <?php endif; ?>
                                                <div class="min-w-0">
                                                    <div class="text-sm font-semibold text-erp-text truncate"><?= htmlspecialchars((string) ($ad['titulo'] ?? 'Apoio')) ?></div>
                                                    <div class="mt-1 text-xs text-erp-muted overflow-hidden"><?= htmlspecialchars((string) ($ad['resumo'] ?? '')) ?></div>
                                                </div>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <div class="grid gap-3">
                                <?php foreach ($publicConteudos as $item): ?>
                                    <?php
                                    $tipo = strtoupper(trim((string) ($item['tipo'] ?? '')));
                                    $titulo = (string) ($item['titulo'] ?? '');
                                    $resumo = (string) ($item['resumo'] ?? '');
                                    $inicioEm = (string) ($item['inicio_em'] ?? '');
                                    $linkUrl = (string) ($item['link_url'] ?? '');
                                    ?>
                                    <a href="<?= htmlspecialchars($linkUrl !== '' ? $linkUrl : '#') ?>" class="public-card hover:opacity-95 transition-opacity">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <?php if ($tipo !== ''): ?>
                                                        <span class="inline-flex items-center rounded-full border border-erp-border bg-erp-surface-2 px-3 py-1 text-[0.7rem] font-semibold uppercase tracking-[0.18em] text-erp-muted">
                                                            <?= htmlspecialchars($tipo) ?>
                                                        </span>
                                                    <?php endif; ?>
                                                    <?php if ($inicioEm !== ''): ?>
                                                        <span class="text-xs text-erp-muted"><?= htmlspecialchars($inicioEm) ?></span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="mt-2 text-sm font-semibold text-erp-text break-words"><?= htmlspecialchars($titulo) ?></div>
                                                <?php if ($resumo !== ''): ?>
                                                    <div class="mt-1 text-xs leading-5 text-erp-muted break-words"><?= htmlspecialchars($resumo) ?></div>
                                                <?php endif; ?>
                                            </div>
                                            <svg class="w-4 h-4 text-erp-muted shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </section>

                <aside class="order-1 lg:order-2">
                    <div class="erp-card">
                        <div class="erp-card-header px-5 py-4">
                            <div class="text-sm font-semibold text-erp-text">Acesso restrito</div>
                            <div class="mt-1 text-xs text-erp-muted">Entre com seu CIM e senha cadastrada.</div>
                        </div>
                        <div class="p-5">
                            <?php if (!$tenantResolved): ?>
                                <div class="alert alert-warning flex items-start">
                                    <svg class="w-5 h-5 mr-3 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                    <div><?= htmlspecialchars($tenantUnavailableMessage ?: 'Loja não identificada. Verifique a configuração do ambiente.') ?></div>
                                </div>
                            <?php endif; ?>

                            <?php if (isset($erroLogin)): ?>
                                <div class="alert alert-danger flex items-start">
                                    <svg class="w-5 h-5 mr-3 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    <div><?= htmlspecialchars((string) $erroLogin) ?></div>
                                </div>
                            <?php endif; ?>

                            <form action="/login" method="POST" class="space-y-4">
                                <div>
                                    <label for="matricula" class="block text-xs font-semibold text-erp-muted mb-1.5 uppercase tracking-[0.18em]">CIM / Matrícula</label>
                                    <input id="matricula" name="matricula" type="text" required <?= !$tenantResolved ? 'disabled' : '' ?> class="form-input" placeholder="Digite seu CIM">
                                </div>

                                <div>
                                    <label for="password" class="block text-xs font-semibold text-erp-muted mb-1.5 uppercase tracking-[0.18em]">Senha</label>
                                    <input id="password" name="password" type="password" required <?= !$tenantResolved ? 'disabled' : '' ?> class="form-input" placeholder="Digite sua senha">
                                </div>

                                <div class="grid gap-3 pt-1">
                                    <button type="submit" name="acao" value="login" <?= !$tenantResolved ? 'disabled' : '' ?> class="btn btn-primary disabled:opacity-50 disabled:cursor-not-allowed">
                                        Entrar
                                    </button>
                                    <button type="submit" name="acao" value="solicitar" <?= !$tenantResolved ? 'disabled' : '' ?> class="btn btn-secondary disabled:opacity-50 disabled:cursor-not-allowed">
                                        Solicitar acesso
                                    </button>
                                </div>
                            </form>

                            <div class="mt-5 text-xs text-erp-muted leading-5">
                                Em caso de dúvida, procure a Secretaria da sua Loja para validação cadastral.
                            </div>
                        </div>
                    </div>
                </aside>
            </div>
        </main>
    </div>

    <script>
        (function () {
            try {
                if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                    document.documentElement.classList.add('dark');
                }
            } catch (e) {}
        })();
    </script>
</body>
</html>
