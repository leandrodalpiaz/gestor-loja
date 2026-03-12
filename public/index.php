<?php
session_start();

use App\Config\Env;
use App\Config\Database;

require_once __DIR__ . "/../src/autoload.php";

Env::load(__DIR__ . "/../.env");

$requestUri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$method = $_SERVER["REQUEST_METHOD"];
$openTestAccess = filter_var($_ENV["APP_TEST_OPEN_ACCESS"] ?? "false", FILTER_VALIDATE_BOOL);

if ($openTestAccess && !isset($_SESSION["usuario_logado"])) {
    $_SESSION["usuario_logado"] = [
        "id" => 0,
        "nome_historico" => "Modo Teste",
        "nome_completo" => "Acesso temporario para homologacao",
        "cargo" => "suporte_tecnico",
        "ativo" => true,
    ];
    $_SESSION["usuario_id"] = 0;
    $_SESSION["usuario_nome"] = "Modo Teste";
    $_SESSION["usuario_cargo"] = "suporte_tecnico";
}

switch ($requestUri) {
    case "/":
    case "/index.php":
    case "/dashboard":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) {
            header("Location: /login");
            exit;
        }
        require_once __DIR__ . "/../src/Views/dashboard.php";
        break;

    case "/obreiros":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) {
            header("Location: /login");
            exit;
        }
        $obreiroModel = new \App\Models\Obreiro();
        $obreiros = $obreiroModel->getAllAtivos();
        require_once __DIR__ . "/../src/Views/obreiros.php";
        break;

    case "/obreiros/novo":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) {
            header("Location: /login");
            exit;
        }
        require_once __DIR__ . "/../src/Views/obreiro_form.php";
        break;

    case "/login":
        if ($openTestAccess) {
            header("Location: /dashboard");
            exit;
        }

        if ($method === "POST") {
            $matricula = $_POST["matricula"] ?? "";
            $password = $_POST["password"] ?? "";

            // Usuário universal apenas para facilitar testes
            if ($matricula === "teste" && $password === "teste") {
                $_SESSION["usuario_logado"] = [
                    "id" => 9999,
                    "nome_historico" => "Irmão Teste",
                    "cargo" => "veneravel",
                    "ativo" => true
                ];
                $_SESSION["usuario_id"] = 9999;
                $_SESSION["usuario_nome"] = "Irmão Teste";
                $_SESSION["usuario_cargo"] = "veneravel";
                header("Location: /dashboard");
                exit;
            }

            $obreiroModel = new \App\Models\Obreiro();
            $usuario = $obreiroModel->autenticar($matricula, $password);

            if ($usuario) {
                $cargo = strtolower($usuario["cargo"] ?? "");
                $cargo = strtr($cargo, [
                    "á" => "a",
                    "à" => "a",
                    "â" => "a",
                    "ã" => "a",
                    "é" => "e",
                    "ê" => "e",
                    "í" => "i",
                    "ó" => "o",
                    "ô" => "o",
                    "õ" => "o",
                    "ú" => "u",
                    "ç" => "c",
                ]);

                if (in_array($cargo, ["veneravel", "secretario", "tesoureiro", "chanceler"], true)) {
                    $_SESSION["usuario_logado"] = $usuario;
                    $_SESSION["usuario_id"] = $usuario["id"];
                    $_SESSION["usuario_nome"] = $usuario["nome_historico"] ?? $usuario["nome_completo"] ?? "Irmão";
                    $_SESSION["usuario_cargo"] = $cargo;
                    header("Location: /dashboard");
                    exit;
                } else {
                    $erroLogin = "Irmão, suas permissões são apenas para o uso do Bot via Telegram.";
                }
            } else {
                $erroLogin = "Matrícula ou palavra de passe incorretas.";
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

