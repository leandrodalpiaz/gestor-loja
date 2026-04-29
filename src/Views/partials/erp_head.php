<?php
$erpPageTitle = $erpPageTitle ?? 'Gestor-Loja';
$tenantSlug = trim((string) ($_SESSION['tenant_slug'] ?? ''));
$tenantLogo = \App\Core\Tenant\TenantAssetResolver::resolveLogo($tenantSlug);
$appleTouchIcon = $tenantLogo !== '' ? $tenantLogo : '/assets/pwa/icon-192.png';
?>
<!DOCTYPE html>
<html lang="pt-BR" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($erpPageTitle) ?></title>
    <meta name="theme-color" content="#1E3A5F">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Gestor Loja">
    <link rel="manifest" href="/manifest.php">
    <link rel="apple-touch-icon" href="<?= htmlspecialchars($appleTouchIcon) ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/assets/css/erp_design_system.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'erp-navy': 'var(--erp-navy)',
                        'erp-gold': 'var(--erp-gold)',
                        'erp-accent': 'var(--erp-accent)',
                        'erp-brand': 'var(--erp-brand)',
                        'erp-accent-strong': 'var(--erp-accent-strong)',
                        'erp-bg': 'var(--erp-bg)',
                        'erp-surface': 'var(--erp-surface)',
                        'erp-surface-2': 'var(--erp-surface-2)',
                        'erp-border': 'var(--erp-border)',
                        'erp-text': 'var(--erp-text)',
                        'erp-muted': 'var(--erp-muted)',
                        'erp-success': 'var(--erp-success)',
                        'erp-warning': 'var(--erp-warning)',
                        'erp-danger': 'var(--erp-danger)',
                        'erp-info': 'var(--erp-info)',
                    }
                }
            }
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Fallback crítico: evita layout quebrado quando utilitários Tailwind não carregam. */
        html, body { margin: 0; padding: 0; }
        *, *::before, *::after { box-sizing: border-box; }
        img, video { max-width: 100%; height: auto; display: block; }
        body { font-family: Inter, system-ui, -apple-system, Segoe UI, sans-serif; }
    </style>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        (function () {
            try {
                if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                    document.documentElement.classList.add('dark');
                }
            } catch (e) {}

            if ('serviceWorker' in navigator) {
                window.addEventListener('load', function () {
                    navigator.serviceWorker.register('/sw.js').catch(function () {});
                });
            }
        })();
    </script>
</head>
