<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/autoload.php';

use App\Config\Env;

Env::load(__DIR__ . '/../.env');

$target = trim((string) ($_ENV['KEEPALIVE_TARGET_URL'] ?? ''));
if ($target === '') {
    $base = rtrim((string) ($_ENV['APP_URL'] ?? ''), '/');
    if ($base !== '') {
        $target = $base . '/health';
    }
}

if ($target === '') {
    fwrite(STDERR, "Defina KEEPALIVE_TARGET_URL ou APP_URL para keep-alive.\n");
    exit(1);
}

$options = [
    'http' => [
        'method' => 'GET',
        'timeout' => 60,
        'ignore_errors' => true,
        'header' => "User-Agent: gestor-loja-keepalive/1.0\r\n",
    ],
];

$context = stream_context_create($options);
$response = @file_get_contents($target, false, $context);
$statusCode = 0;

if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', (string) $http_response_header[0], $m)) {
    $statusCode = (int) $m[1];
}

// Durante rollout, tenta fallback para /login se /health ainda não existir no deploy ativo.
if (($response === false || $statusCode === 404) && preg_match('#/health$#', $target)) {
    $fallbackUrl = preg_replace('#/health$#', '/login', $target) ?: $target;
    $response = @file_get_contents($fallbackUrl, false, $context);
    $statusCode = 0;
    if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', (string) $http_response_header[0], $m)) {
        $statusCode = (int) $m[1];
    }
    $target = $fallbackUrl;
}

if ($response === false || $statusCode < 200 || $statusCode >= 400) {
    fwrite(STDERR, "Keep-alive falhou para {$target} (HTTP {$statusCode}).\n");
    exit(1);
}

echo "Keep-alive OK para {$target} (HTTP {$statusCode}).\n";
