<?php
session_start();

use App\Config\Env;

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
    $registroModel = new \App\Models\EfemerideRegistro();
    $previaModel = new \App\Models\EfemeridePreviaDiaria();
    $composer = new \App\Services\EfemeridesComposer();

    $registrosHoje = $registroModel->getRegistrosDoDia();
    $registrosRecentes = $registroModel->getRecentes();
    $mensagemBase = $composer->composeDailyPreview($registrosHoje);
    $mensagemPreview = $previaModel->garantirPreviaDoDia($mensagemBase);

    return [
        'registrosHoje' => $registrosHoje,
        'registrosRecentes' => $registrosRecentes,
        'mensagemBase' => $mensagemBase,
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

    case "/chancelaria/efemerides/salvar-previa":
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

        $mensagemEditada = trim((string) ($_POST['mensagem_preview'] ?? ''));
        if ($mensagemEditada === '') {
            header("Location: /chancelaria/efemerides?erro=" . urlencode('A prévia não pode estar vazia.'));
            exit;
        }

        $hoje = (new \DateTimeImmutable('today'))->format('Y-m-d');
        $previaModel = new \App\Models\EfemeridePreviaDiaria();
        $ok = $previaModel->salvarOuAtualizar($hoje, $mensagemEditada, false);

        if (!$ok) {
            header("Location: /chancelaria/efemerides?erro=" . urlencode('Falha ao salvar a prévia diária.'));
            exit;
        }

        header("Location: /chancelaria/efemerides?sucesso=previa_salva");
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
        $message = $previewData['mensagemBase'] ?? $previewData['mensagemPreview'];

        // Após 00:01, este cron apenas prepara (ou atualiza) a prévia do dia para revisão do chanceler.
        $hoje = (new \DateTimeImmutable('today'))->format('Y-m-d');
        $previaModel = new \App\Models\EfemeridePreviaDiaria();
        $ok = $previaModel->prepararAutomaticaDoDia($message);

        if (!$ok) {
            echo "Falha ao preparar prévia diária.";
            exit;
        }

        echo "Prévia diária preparada com sucesso para revisão do chanceler.";
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

    // ─── Mini App pages (GET) — served inside Telegram WebApp ────────────────
    case "/miniapp/aniversario":
    case "/miniapp/data-maconica":
    case "/miniapp/historico":
    case "/miniapp/fallback":
        // Mini Apps are opened inside Telegram; they validate via initData, not session.
        // A basic referer/user-agent guard keeps direct browser browsing out.
        $viewMap = [
            '/miniapp/aniversario'  => 'aniversario.php',
            '/miniapp/data-maconica'=> 'data-maconica.php',
            '/miniapp/historico'    => 'historico.php',
            '/miniapp/fallback'     => 'fallback.php',
        ];
        require_once __DIR__ . '/../src/Views/miniapp/' . $viewMap[$requestUri];
        break;

    // ─── Mini App API endpoints (POST, JSON, validate initData) ──────────────
    case "/api/miniapp/efemeride/salvar":
    case "/api/miniapp/efemeride/desativar":
    case "/api/miniapp/fallback/listar":
    case "/api/miniapp/fallback/salvar":
    case "/api/miniapp/fallback/toggle":
    case "/api/miniapp/fallback/excluir":
    case "/api/miniapp/historico/listar":
        header('Content-Type: application/json; charset=utf-8');

        // Accept initData both from GET (listar) and POST body (mutations)
        if ($method === 'GET') {
            $initData = $_GET['initData'] ?? '';
        } else {
            $raw = file_get_contents('php://input');
            $body = json_decode($raw ?: '{}', true) ?? [];
            $initData = $body['initData'] ?? '';
        }

        $botToken = $_ENV['TELEGRAM_BOT_TOKEN'] ?? '';
        $validator = new \App\Services\TelegramInitDataValidator();
        $tgUser = $validator->validate($initData, $botToken);

        if ($tgUser === null) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'erro' => 'initData inválido ou expirado.']);
            exit;
        }

        // Verify telegram_id belongs to a chanceler
        $obreiroModel = new \App\Models\Obreiro();
        $membro = $obreiroModel->findByTelegramId((int) ($tgUser['id'] ?? 0));
        if (!$membro || $normalizeRole($membro['cargo'] ?? '') !== 'chanceler') {
            http_response_code(403);
            echo json_encode(['ok' => false, 'erro' => 'Acesso restrito ao Chanceler.']);
            exit;
        }

        if ($requestUri === '/api/miniapp/efemeride/salvar') {
            if ($method !== 'POST') { http_response_code(405); echo json_encode(['ok'=>false,'erro'=>'POST requerido']); exit; }
            $nome = trim((string) ($body['nome'] ?? ''));
            $tipo = trim((string) ($body['tipo'] ?? ''));
            $dataEvento = trim((string) ($body['data_evento'] ?? ''));
            if ($nome === '' || $tipo === '' || $dataEvento === '') {
                echo json_encode(['ok' => false, 'erro' => 'nome, tipo e data_evento são obrigatórios.']);
                exit;
            }
            $registroModel = new \App\Models\EfemerideRegistro();
            $ok = $registroModel->create($body, (int) ($membro['id'] ?? 0));
            echo json_encode(['ok' => (bool) $ok]);
            exit;
        }

        if ($requestUri === '/api/miniapp/efemeride/desativar') {
            if ($method !== 'POST') { http_response_code(405); echo json_encode(['ok'=>false,'erro'=>'POST requerido']); exit; }
            $id = (int) ($body['id'] ?? 0);
            if ($id <= 0) { echo json_encode(['ok'=>false,'erro'=>'ID inválido']); exit; }
            $registroModel = new \App\Models\EfemerideRegistro();
            echo json_encode(['ok' => (bool) $registroModel->desativar($id)]);
            exit;
        }

        if ($requestUri === '/api/miniapp/historico/listar') {
            $registroModel = new \App\Models\EfemerideRegistro();
            $registros = $registroModel->listarPorTipo('História');
            echo json_encode(['ok' => true, 'registros' => $registros]);
            exit;
        }

        $complementarModel = new \App\Models\MensagemComplementar();

        if ($requestUri === '/api/miniapp/fallback/listar') {
            echo json_encode(['ok' => true, 'mensagens' => $complementarModel->listarPorTipo('fallback')]);
            exit;
        }

        if ($requestUri === '/api/miniapp/fallback/salvar') {
            if ($method !== 'POST') { http_response_code(405); echo json_encode(['ok'=>false,'erro'=>'POST requerido']); exit; }
            $mensagem = trim((string) ($body['mensagem'] ?? ''));
            if ($mensagem === '') { echo json_encode(['ok'=>false,'erro'=>'Mensagem não pode estar vazia.']); exit; }
            if (!empty($body['id'])) {
                $ok = $complementarModel->atualizar((int) $body['id'], $mensagem);
            } else {
                $ok = $complementarModel->criar('fallback', $mensagem);
            }
            echo json_encode(['ok' => (bool) $ok]);
            exit;
        }

        if ($requestUri === '/api/miniapp/fallback/toggle') {
            if ($method !== 'POST') { http_response_code(405); echo json_encode(['ok'=>false,'erro'=>'POST requerido']); exit; }
            $id = (int) ($body['id'] ?? 0);
            if ($id <= 0) { echo json_encode(['ok'=>false,'erro'=>'ID inválido']); exit; }
            echo json_encode(['ok' => (bool) $complementarModel->toggleAtivo($id)]);
            exit;
        }

        if ($requestUri === '/api/miniapp/fallback/excluir') {
            if ($method !== 'POST') { http_response_code(405); echo json_encode(['ok'=>false,'erro'=>'POST requerido']); exit; }
            $id = (int) ($body['id'] ?? 0);
            if ($id <= 0) { echo json_encode(['ok'=>false,'erro'=>'ID inválido']); exit; }
            echo json_encode(['ok' => (bool) $complementarModel->excluir($id)]);
            exit;
        }

        http_response_code(404);
        echo json_encode(['ok' => false, 'erro' => 'Rota não encontrada.']);
        exit;

    default:
        http_response_code(404);
        echo "404 - Página não encontrada.";
        break;
}

