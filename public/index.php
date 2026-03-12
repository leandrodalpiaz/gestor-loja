<?php
session_start();

use App\Config\Env;
use App\Config\Database;

require_once __DIR__ . "/../src/autoload.php";

Env::load(__DIR__ . "/../.env");

$requestUri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$method = $_SERVER["REQUEST_METHOD"];
$openTestAccess = filter_var($_ENV["APP_TEST_OPEN_ACCESS"] ?? "false", FILTER_VALIDATE_BOOL);

$normalizeRole = static function (?string $cargo): string {
    $cargo = strtolower(trim((string) $cargo));
    return strtr($cargo, [
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
};

$buildEfemeridesPreview = static function (): array {
    $obreiroModel = new \App\Models\Obreiro();
    $registroModel = new \App\Models\EfemerideRegistro();
    $composer = new \App\Services\EfemeridesComposer();

    $aniversariantes = $obreiroModel->getEfemeridesDoDia();
    $registrosHoje = $registroModel->getRegistrosDoDia();
    $registrosRecentes = $registroModel->getRecentes();
    $mensagemPreview = $composer->composeDailyPreview($aniversariantes, $registrosHoje);

    return [
        'aniversariantes' => $aniversariantes,
        'registrosHoje' => $registrosHoje,
        'registrosRecentes' => $registrosRecentes,
        'mensagemPreview' => $mensagemPreview,
    ];
};

if ($openTestAccess && !isset($_SESSION["usuario_logado"])) {
    $_SESSION["usuario_logado"] = [
        "id" => 0,
        "nome_historico" => "Modo Teste",
        "nome_completo" => "Acesso temporario para homologacao",
        "cargo" => "chanceler",
        "ativo" => true,
    ];
    $_SESSION["usuario_id"] = 0;
    $_SESSION["usuario_nome"] = "Modo Teste";
    $_SESSION["usuario_cargo"] = "chanceler";
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

    case "/chancelaria/efemerides":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) {
            header("Location: /login");
            exit;
        }

        $cargoUsuario = $normalizeRole($_SESSION["usuario_cargo"] ?? "");
        if ($cargoUsuario !== 'chanceler') {
            http_response_code(403);
            echo "Acesso restrito ao Chanceler.";
            exit;
        }

        $previewData = $buildEfemeridesPreview();
        $registrosRecentes = $previewData['registrosRecentes'];
        $mensagemPreview = $previewData['mensagemPreview'];

        $sucessoMensagem = null;
        if (isset($_GET['sucesso']) && $_GET['sucesso'] === 'registro') {
            $sucessoMensagem = 'Registro salvo com sucesso.';
        } elseif (isset($_GET['sucesso']) && $_GET['sucesso'] === 'desativado') {
            $sucessoMensagem = 'Registro desativado com sucesso.';
        } elseif (isset($_GET['sucesso']) && $_GET['sucesso'] === 'previa_enviada') {
            $sucessoMensagem = 'Prévia enviada no Telegram privado do chanceler.';
        } elseif (isset($_GET['sucesso']) && $_GET['sucesso'] === 'grupo_enviado') {
            $sucessoMensagem = 'Mensagem enviada no grupo oficial com sucesso.';
        }

        $erroMensagem = $_GET['erro'] ?? null;

        require_once __DIR__ . "/../src/Views/efemerides_chanceler.php";
        break;

    case "/chancelaria/efemerides/enviar-previa":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) {
            header("Location: /login");
            exit;
        }

        $cargoUsuario = $normalizeRole($_SESSION["usuario_cargo"] ?? "");
        if ($cargoUsuario !== 'chanceler') {
            http_response_code(403);
            echo "Acesso restrito ao Chanceler.";
            exit;
        }

        if ($method !== 'POST') {
            http_response_code(405);
            echo "Método não permitido.";
            exit;
        }

        $previewData = $buildEfemeridesPreview();
        $mensagem = $previewData['mensagemPreview'];

        $telegram = new \App\Services\TelegramService();
        $ok = $telegram->sendMessageToReview($mensagem);

        if (!$ok) {
            header("Location: /chancelaria/efemerides?erro=" . urlencode('Falha ao enviar prévia no Telegram do chanceler.'));
            exit;
        }

        header("Location: /chancelaria/efemerides?sucesso=previa_enviada");
        exit;

    case "/chancelaria/efemerides/enviar-grupo":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) {
            header("Location: /login");
            exit;
        }

        $cargoUsuario = $normalizeRole($_SESSION["usuario_cargo"] ?? "");
        if ($cargoUsuario !== 'chanceler') {
            http_response_code(403);
            echo "Acesso restrito ao Chanceler.";
            exit;
        }

        if ($method !== 'POST') {
            http_response_code(405);
            echo "Método não permitido.";
            exit;
        }

        $previewData = $buildEfemeridesPreview();
        $mensagem = $previewData['mensagemPreview'];

        $telegram = new \App\Services\TelegramService();
        $ok = $telegram->sendMessageToGroup($mensagem);

        if (!$ok) {
            header("Location: /chancelaria/efemerides?erro=" . urlencode('Falha ao enviar mensagem no grupo.'));
            exit;
        }

        header("Location: /chancelaria/efemerides?sucesso=grupo_enviado");
        exit;

    case "/chancelaria/efemerides/salvar":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) {
            header("Location: /login");
            exit;
        }

        $cargoUsuario = $normalizeRole($_SESSION["usuario_cargo"] ?? "");
        if ($cargoUsuario !== 'chanceler') {
            http_response_code(403);
            echo "Acesso restrito ao Chanceler.";
            exit;
        }

        if ($method !== 'POST') {
            http_response_code(405);
            echo "Método não permitido.";
            exit;
        }

        $nome = trim((string) ($_POST['nome'] ?? ''));
        $tipo = trim((string) ($_POST['tipo'] ?? ''));
        $dataEvento = trim((string) ($_POST['data_evento'] ?? ''));

        if ($nome === '' || $tipo === '' || $dataEvento === '') {
            header("Location: /chancelaria/efemerides?erro=" . urlencode('Nome, tipo e data são obrigatórios.'));
            exit;
        }

        $registroModel = new \App\Models\EfemerideRegistro();
        $ok = $registroModel->create($_POST, (int) ($_SESSION['usuario_id'] ?? 0));

        if (!$ok) {
            header("Location: /chancelaria/efemerides?erro=" . urlencode('Não foi possível salvar o registro.'));
            exit;
        }

        header("Location: /chancelaria/efemerides?sucesso=registro");
        exit;

    case "/chancelaria/efemerides/desativar":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) {
            header("Location: /login");
            exit;
        }

        $cargoUsuario = $normalizeRole($_SESSION["usuario_cargo"] ?? "");
        if ($cargoUsuario !== 'chanceler') {
            http_response_code(403);
            echo "Acesso restrito ao Chanceler.";
            exit;
        }

        if ($method !== 'POST') {
            http_response_code(405);
            echo "Método não permitido.";
            exit;
        }

        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            header("Location: /chancelaria/efemerides?erro=" . urlencode('Registro inválido.'));
            exit;
        }

        $registroModel = new \App\Models\EfemerideRegistro();
        $ok = $registroModel->desativar($id);

        if (!$ok) {
            header("Location: /chancelaria/efemerides?erro=" . urlencode('Não foi possível desativar o registro.'));
            exit;
        }

        header("Location: /chancelaria/efemerides?sucesso=desativado");
        exit;

    case "/cron/efemerides":
        // Proteção simples: require a secret token parameter to prevent malicious invocation
        $triggerToken = $_GET['token'] ?? '';
        $expectedToken = $_ENV['CRON_SECRET_TOKEN'] ?? 'renascenca-test';
        
        if ($triggerToken !== $expectedToken) {
            http_response_code(403);
            echo "Acesso negado.";
            exit;
        }

        $previewData = $buildEfemeridesPreview();
        $message = $previewData['mensagemPreview'];

        if (trim($message) === '' || $message === 'Nenhuma efeméride para hoje.') {
            echo "Nenhuma efeméride hoje.";
            exit;
        }

        $telegram = new \App\Services\TelegramService();
        $success = $telegram->sendMessageToGroup($message);
        if ($success) {
            echo "Efemérides enviadas com sucesso no Telegram!";
        } else {
            echo "Falha ao enviar efemérides no Telegram. Verifique os logs e os tokens.";
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
                    "cargo" => "chanceler",
                    "ativo" => true
                ];
                $_SESSION["usuario_id"] = 9999;
                $_SESSION["usuario_nome"] = "Irmão Teste";
                $_SESSION["usuario_cargo"] = "chanceler";
                header("Location: /dashboard");
                exit;
            }

            $obreiroModel = new \App\Models\Obreiro();
            $usuario = $obreiroModel->autenticar($matricula, $password);

            if ($usuario) {
                $cargo = $normalizeRole($usuario["cargo"] ?? "");

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

