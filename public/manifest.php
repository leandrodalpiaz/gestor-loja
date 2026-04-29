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

$iconSvg = "/assets/tenants/{$tenantSlug}/logo.svg";
if (!is_file(__DIR__ . $iconSvg)) {
    $iconSvg = '/assets/placeholders/logo-loja.svg';
}

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
            'src' => '/assets/pwa/icon-192.png',
            'sizes' => '192x192',
            'type' => 'image/png',
            'purpose' => 'any maskable',
        ],
        [
            'src' => '/assets/pwa/icon-512.png',
            'sizes' => '512x512',
            'type' => 'image/png',
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

