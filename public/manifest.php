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
    $tenantSlug = 'renascenca';
}

require_once __DIR__ . '/../vendor/autoload.php';
\App\Config\Environment::load(__DIR__ . '/../');

try {
    $configuracaoLoja = (new \App\Models\ConfiguracaoLoja())->obter();
} catch (\Throwable $e) {
    $configuracaoLoja = [];
}

$nomeLoja = trim((string) ($configuracaoLoja['nome_loja'] ?? ''));
if ($nomeLoja === '') {
    $nomeLoja = 'Gestor Loja';
}

$shortName = mb_strimwidth($nomeLoja, 0, 12, '');
$themeColor = (string) ($configuracaoLoja['cor_primaria_light'] ?? '#1E3A8A');
$backgroundColor = (string) ($configuracaoLoja['cor_primaria_dark'] ?? '#0F172A');

$name = $nomeLoja;

$tenantBase = '/assets/tenants/' . rawurlencode($tenantSlug) . '/';
$logo192 = $tenantBase . 'logo-192.png';
$logo512 = $tenantBase . 'logo-512.png';
$logoPng = $tenantBase . 'logo.png';
$logoSvg = $tenantBase . 'logo.svg';

$defaultLogo = '/assets/logo-renascenca.png';

$icon192 = is_file(__DIR__ . $logo192)
    ? $logo192
    : (is_file(__DIR__ . $logoPng) ? $logoPng : $defaultLogo);
$icon512 = is_file(__DIR__ . $logo512)
    ? $logo512
    : (is_file(__DIR__ . $logoPng) ? $logoPng : $defaultLogo);
$iconSvg = is_file(__DIR__ . $logoSvg) ? $logoSvg : $defaultLogo;

echo json_encode([
    'name' => $name,
    'short_name' => $shortName,
    'start_url' => '/pwa',
    'scope' => '/',
    'display' => 'standalone',
    'orientation' => 'portrait',
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
