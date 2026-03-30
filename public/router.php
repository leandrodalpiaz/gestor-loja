<?php

declare(strict_types=1);

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$file = __DIR__ . $path;

// Mantem comportamento do Apache/.htaccess:
// serve arquivos reais direto e roteia o restante para index.php.
if ($path !== '/' && is_file($file)) {
    return false;
}

require __DIR__ . '/index.php';
