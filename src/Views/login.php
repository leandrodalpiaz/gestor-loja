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
    <style>
        body {
            font-family: 'Inter', sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        .form-input {
            @apply w-full px-4 py-3 rounded-md bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:ring-2 focus:ring-blue-500 focus:outline-none focus:border-blue-500 transition;
        }
        .btn {
            @apply w-full flex justify-center items-center px-4 py-3 rounded-md text-sm font-semibold shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 transition;
        }
        .btn-primary {
            @apply bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500;
        }
        .btn-secondary {
            @apply bg-gray-200 text-gray-800 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600 focus:ring-gray-500;
        }
        .alert {
            @apply p-4 rounded-md text-sm;
        }
        .alert-danger {
            @apply bg-red-50 text-red-700 dark:bg-red-900/20 dark:text-red-300;
        }
        .alert-warning {
            @apply bg-yellow-50 text-yellow-700 dark:bg-yellow-900/20 dark:text-yellow-300;
        }
    </style>
</head>
<body class="h-full bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100">
    <div class="flex min-h-full">
        <!-- Coluna da Esquerda (Branding) -->
        <div class="hidden lg:flex flex-1 flex-col justify-center items-center bg-blue-700 dark:bg-blue-900 text-white p-12 relative overflow-hidden">
            <div class="absolute inset-0 bg-black/20 mix-blend-multiply"></div>
            <div class="z-10 text-center">
                <?php if ($logoLogin): ?>
                    <img src="<?= htmlspecialchars($logoLogin) ?>" alt="Brasão da Loja" class="h-24 w-24 mx-auto mb-6 object-contain">
                <?php else: ?>
                    <div class="h-24 w-24 mx-auto mb-6 flex items-center justify-center rounded-full bg-white/10 border border-white/20 text-4xl font-serif">∴</div>
                <?php endif; ?>
                <h1 class="text-3xl font-bold tracking-tight">
                    <?= htmlspecialchars($tenantName ?: 'Gestor de Loja') ?>
                </h1>
                <p class="mt-4 text-lg text-blue-200 max-w-md mx-auto">
                    Plataforma de gestão integrada para Lojas Maçônicas.
                </p>
            </div>
        </div>

        <!-- Coluna da Direita (Formulário) -->
        <div class="flex flex-1 flex-col justify-center py-12 px-4 sm:px-6 lg:flex-none lg:px-20 xl:px-24">
            <div class="mx-auto w-full max-w-sm lg:w-96">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Acesso Restrito</h2>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        Área exclusiva para membros autorizados.
                    </p>
                </div>

                <div class="mt-8">
                    <?php if (!$tenantResolved): ?>
                        <div class="alert alert-warning">
                            <?= htmlspecialchars($tenantUnavailableMessage ?: 'Loja não identificada. Verifique a configuração do ambiente.') ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($erroLogin)): ?>
                        <div class="alert alert-danger mt-4">
                            <?= htmlspecialchars($erroLogin) ?>
                        </div>
                    <?php endif; ?>

                    <form action="/login" method="POST" class="mt-6 space-y-6">
                        <div>
                            <label for="matricula" class="block text-sm font-medium text-gray-700 dark:text-gray-300">CIM / Matrícula</label>
                            <div class="mt-1">
                                <input id="matricula" name="matricula" type="text" required <?= !$tenantResolved ? 'disabled' : '' ?> class="form-input" placeholder="Digite seu CIM">
                            </div>
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Senha de Acesso</label>
                            <div class="mt-1">
                                <input id="password" name="password" type="password" required <?= !$tenantResolved ? 'disabled' : '' ?> class="form-input" placeholder="Digite sua senha">
                            </div>
                        </div>

                        <div class="space-y-3">
                            <button type="submit" name="acao" value="login" <?= !$tenantResolved ? 'disabled' : '' ?> class="btn btn-primary disabled:opacity-50">
                                Entrar no Sistema
                            </button>
                            <button type="submit" name="acao" value="solicitar" <?= !$tenantResolved ? 'disabled' : '' ?> class="btn btn-secondary disabled:opacity-50">
                                Solicitar Acesso
                            </button>
                        </div>
                    </form>

                    <div class="mt-8 p-4 rounded-md bg-gray-100 dark:bg-gray-800 text-sm text-gray-600 dark:text-gray-400">
                        Em caso de dúvida ou problema de acesso, procure a Secretaria da sua Loja para validação cadastral.
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
