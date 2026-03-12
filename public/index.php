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
            $matricula = $_POST["matricula"] ?? "";
            $password = $_POST["password"] ?? "";

            // FIXME: Acesso Temporário de Desenvolvimento (Backdoor de testes)
            if ($matricula === "admin" && $password === "admin") {
                $_SESSION["usuario_logado"] = true;
                $_SESSION["usuario_nome"] = "Administrador Master";
                $_SESSION["usuario_cargo"] = "suporte_tecnico";
                header("Location: /dashboard");
                exit;
            }

            // Implementação Real via Banco (Descomentar assim que a tabela Obreiros tiver senha_hash)
            /*
            $obreiroModel = new \App\Models\Obreiro();
            $usuario = $obreiroModel->autenticar($matricula, $password);

            if ($usuario) {
                if (in_array(strtolower($usuario["cargo"]), ["venerável", "secretário", "tesoureiro", "chanceler"])) {
                    $_SESSION["usuario_logado"] = true;
                    $_SESSION["usuario_id"] = $usuario["id"];
                    $_SESSION["usuario_nome"] = $usuario["nome"];
                    $_SESSION["usuario_cargo"] = strtolower($usuario["cargo"]);
                    header("Location: /dashboard");
                    exit;
                } else {
                    $erroLogin = "Irmão, suas permissões são apenas para o uso do Bot via Telegram.";
                }
            } else {
                $erroLogin = "Matrícula ou palavra de passe incorretas.";
            }
            */

            // Caso caia aqui antes de implementarmos o BD:
            if (!isset($erroLogin)) {
                $erroLogin = "Login inválido. Tente usar as credenciais provisórias.";
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

