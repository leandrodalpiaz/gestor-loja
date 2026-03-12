<?php
// public/index.php

use App\Config\Env;
use App\Config\Database;

require_once __DIR__ . '/../src/autoload.php';

// Load environment variables
Env::load(__DIR__ . '/../.env');

// Simple routing loop
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Lógica de Rotas do MVC Base
switch ($requestUri) {
    case '/':
    case '/index.php':
    case '/dashboard':
        require_once __DIR__ . '/../src/Views/dashboard.php';
        break;
    
    case '/login':
        require_once __DIR__ . '/../src/Views/login.php';
        break;

    default:
        http_response_code(404);
        echo "404 - Página não encontrada.";
        break;
}