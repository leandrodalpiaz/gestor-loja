<?php
session_start();

use App\Config\Env;
use App\Config\Database;

require_once __DIR__ . "/../src/autoload.php";

Env::load(__DIR__ . "/../.env");

$requestUri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$method = $_SERVER["REQUEST_METHOD"];

switch ($requestUri) {
    case "/":
    case "/index.php":
    case "/dashboard":
        if (!isset($_SESSION["usuario_logado"])) {
            header("Location: /login");
            exit;
        }
        require_once __DIR__ . "/../src/Views/dashboard.php";
        break;

    case "/login":
        if ($method === "POST") {
            $email = $_POST["email"] ?? "";
            $password = $_POST["password"] ?? "";
            
            // FIXME: Apenas um mock-up da regra de negócio de login para abrir a tela
            if (strpos($email, "@") !== false && $password != "") {
                $_SESSION["usuario_logado"] = true;
                header("Location: /dashboard");
                exit;
            } else {
                $erroLogin = "Credenciais inválidas. Verifique com a Secretaria.";
            }
        }
        require_once __DIR__ . "/../src/Views/login.php";
        break;

    case "/logout":
        session_destroy();
        header("Location: /login");
        exit;

    default:
        http_response_code(404);
        echo "404 - Página não encontrada.";
        break;
}

