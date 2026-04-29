<?php

declare(strict_types=1);

if (!headers_sent()) {
    header('Content-Type: application/manifest+json; charset=UTF-8');
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

$tenantSlug = trim((string) ($_SESSION['tenant_slug'] ?? ''));
if ($tenantSlug === '') {
    $tenantSlug = 'loja-teste';
}

$name = 'Gestor Loja';
$shortName = 'Gestor';
$themeColor = '#1E3A5F';
$backgroundColor = '#F4F7FB';

$tenantBase = '/assets/tenants/' . rawurlencode($tenantSlug) . '/';
$logo192 = $tenantBase . 'logo-192.png';
$logo512 = $tenantBase . 'logo-512.png';
$logoPng = $tenantBase . 'logo.png';
$logoSvg = $tenantBase . 'logo.svg';

$icon192 = is_file(__DIR__ . $logo192)
    ? $logo192
    : (is_file(__DIR__ . $logoPng) ? $logoPng : '/assets/pwa/icon-192.png');
$icon512 = is_file(__DIR__ . $logo512)
    ? $logo512
    : (is_file(__DIR__ . $logoPng) ? $logoPng : '/assets/pwa/icon-512.png');
$iconSvg = is_file(__DIR__ . $logoSvg) ? $logoSvg : '/assets/placeholders/logo-loja.svg';

echo json_encode([
    'name' => $name,
    'short_name' => $shortName,
    'start_url' => '/pwa',
    'scope' => '/',
    'display' => 'standalone',
    'theme_color' => $themeColor,
    'background_color' => $backgroundColor,
    'icons' => [
        [
            'src' => $icon192,
            'sizes' => '192x192',
            'type' => str_ends_with($icon192, '.png') ? 'image/png' : 'image/svg+xml',
            'purpose' => 'any maskable',
        ],
        [
            'src' => $icon512,
            'sizes' => '512x512',
            'type' => str_ends_with($icon512, '.png') ? 'image/png' : 'image/svg+xml',
            'purpose' => 'any maskable',
        ],
        [
            'src' => $iconSvg,
            'sizes' => 'any',
            'type' => 'image/svg+xml',
            'purpose' => 'any maskable',
        ],
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

