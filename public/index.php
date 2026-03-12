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

    case "/cron/efemerides":
        // Proteção simples: require a secret token parameter to prevent malicious invocation
        $triggerToken = $_GET['token'] ?? '';
        $expectedToken = $_ENV['CRON_SECRET_TOKEN'] ?? 'renascenca-test';
        
        if ($triggerToken !== $expectedToken) {
            http_response_code(403);
            echo "Acesso negado.";
            exit;
        }

        $obreiroModel = new \App\Models\Obreiro();
        $telegram = new \App\Services\TelegramService();
        $aniversariantes = $obreiroModel->getEfemeridesDoDia();

        if (empty($aniversariantes)) {
            echo "Nenhuma efeméride hoje.";
            exit;
        }

        $mensagens = [];
        foreach ($aniversariantes as $ob) {
            $nome = $ob['nome_historico'] ?: $ob['nome'];
            $grau = $ob['grau'] ? " (" . ucfirst($ob['grau']) . ")" : "";
            
            if ($ob['is_aniversario_civil']) {
                $mensagens[] = "🎂 Hoje é o aniversário natalício do nosso Amado Ir. <b>{$nome}</b>{$grau}! Desejamos muita saúde, paz e prosperidade!";
            }
            if ($ob['is_aniversario_maconico']) {
                $mensagens[] = "🎉 Hoje nosso Amado Ir. <b>{$nome}</b>{$grau} comemora seu Aniversário de Iniciação Maçônica! Parabéns pela caminhada na Ordem!";
            }
        }

        if (!empty($mensagens)) {
            $textoBase = "🏛️ <b>A:.R:.L:.S:. Renascença</b> - Efemérides do Dia\n\n";
            $message = $textoBase . implode("\n\n", $mensagens);
            
            $success = $telegram->sendMessage($message);
            if ($success) {
                echo "Efemérides enviadas com sucesso no Telegram!";
            } else {
                echo "Falha ao enviar efemérides no Telegram. Verifique os logs e os tokens.";
            }
        } else {
            echo "Efemérides computadas, mas sem mensagens.";
        }
        exit;

    case "/schema":
        $db = \App\Config\Database::getConnection();
        
        // Roda a migration de chancelaria se a tabela antiga existir
        try {
            $db->exec('ALTER TABLE obreiros ADD COLUMN cargo VARCHAR(255), ADD COLUMN nome_historico VARCHAR(255), ADD COLUMN cpf VARCHAR(20), ADD COLUMN data_nascimento_civil DATE, ADD COLUMN data_iniciacao DATE, ADD COLUMN telefone VARCHAR(20), ADD COLUMN email VARCHAR(255), ADD COLUMN profissao VARCHAR(255), ADD COLUMN loja_origem VARCHAR(255), ADD COLUMN senha_hash VARCHAR(255);');
            echo "NOVAS COLUNAS ADICIONADAS COM SUCESSO! <br><br>";
        } catch (\PDOException $e) {
            echo "Info Migração: " . $e->getMessage() . " <br><br>";
        }

        $stmt = $db->query("SELECT column_name FROM information_schema.columns WHERE table_name = 'obreiros'");
        echo "COLUNAS DA TABELA OBREIROS: ";
        print_r($stmt->fetchAll(\PDO::FETCH_COLUMN));
        exit;

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

    case "/obreiros/salvar":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) {
            header("Location: /login");
            exit;
        }
        if ($method === "POST") {
            $obreiroModel = new \App\Models\Obreiro();
            try {
                $obreiroModel->create($_POST);
                header("Location: /obreiros?sucesso=1");
            } catch (\PDOException $e) {
                // Em caso de erro (ex: CIM duplicado), volta para o form
                echo "Erro ao salvar: " . htmlspecialchars($e->getMessage());
                echo "<br><a href='/obreiros/novo'>Voltar</a>";
            }
            exit;
        }
        break;

    case "/obreiros/editar":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) {
            header("Location: /login");
            exit;
        }
        $id = $_GET['id'] ?? '';
        $obreiroModel = new \App\Models\Obreiro();
        $obreiro = $obreiroModel->findById($id);
        
        if (!$obreiro) {
            header("Location: /obreiros");
            exit;
        }
        
        require_once __DIR__ . "/../src/Views/obreiro_editar.php";
        break;

    case "/obreiros/atualizar":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) {
            header("Location: /login");
            exit;
        }
        if ($method === "POST") {
            $obreiroModel = new \App\Models\Obreiro();
            try {
                $obreiroModel->update($_POST);
                header("Location: /obreiros/editar?id=" . urlencode($_POST['id']) . "&sucesso=1");
            } catch (\PDOException $e) {
                echo "Erro ao atualizar: " . htmlspecialchars($e->getMessage());
                echo "<br><a href='/obreiros/editar?id=" . urlencode($_POST['id']) . "'>Voltar</a>";
            }
            exit;
        }
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

