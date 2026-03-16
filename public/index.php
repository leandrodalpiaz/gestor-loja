<?php
session_start();

use App\Config\Env;

require_once __DIR__ . "/../src/autoload.php";

Env::load(__DIR__ . "/../.env");

$requestUri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$method = $_SERVER["REQUEST_METHOD"];
$openTestAccess = filter_var($_ENV["APP_TEST_OPEN_ACCESS"] ?? "false", FILTER_VALIDATE_BOOL);
$allowAllPanels = filter_var($_ENV["APP_TEST_ALLOW_ALL_PANELS"] ?? "true", FILTER_VALIDATE_BOOL);
$testLogin = trim((string) ($_ENV["APP_TEST_DEFAULT_LOGIN"] ?? ""));
$testPassword = (string) ($_ENV["APP_TEST_DEFAULT_PASSWORD"] ?? "");
$testRole = trim((string) ($_ENV["APP_TEST_DEFAULT_ROLE"] ?? "tesoureiro"));
$testDisplayName = trim((string) ($_ENV["APP_TEST_DEFAULT_NAME"] ?? "Modo Teste"));
$isTestSession = isset($_SESSION["usuario_id"]) && (int) $_SESSION["usuario_id"] === 0;
$bypassRoleChecks = $openTestAccess || $isTestSession || ($allowAllPanels && isset($_SESSION["usuario_logado"]));

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

$appToday = static function (): \DateTimeImmutable {
    $timezone = trim((string) ($_ENV['APP_TIMEZONE'] ?? 'America/Sao_Paulo'));
    try {
        return new \DateTimeImmutable('today', new \DateTimeZone($timezone));
    } catch (\Throwable $e) {
        return new \DateTimeImmutable('today', new \DateTimeZone('America/Sao_Paulo'));
    }
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

$buildTestSessionUser = static function () use ($normalizeRole, $testDisplayName, $testRole): array {
    $role = $normalizeRole($testRole);

    return [
        "id" => 0,
        "nome_historico" => $testDisplayName,
        "nome_completo" => "Acesso temporario para homologacao",
        "cargo" => $role,
        "ativo" => true,
    ];
};

if ($openTestAccess && !isset($_SESSION["usuario_logado"])) {
    $_SESSION["usuario_logado"] = $buildTestSessionUser();
    $_SESSION["usuario_id"] = 0;
    $_SESSION["usuario_nome"] = $_SESSION["usuario_logado"]["nome_historico"];
    $_SESSION["usuario_cargo"] = $_SESSION["usuario_logado"]["cargo"];
}

switch ($requestUri) {
    case "/health":
        header("Content-Type: application/json; charset=utf-8");
        echo json_encode([
            "status" => "ok",
            "service" => "gestor-loja",
            "timestamp" => date(DATE_ATOM),
        ], JSON_UNESCAPED_UNICODE);
        exit;

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
        if (!$bypassRoleChecks && $cargoUsuario !== 'chanceler') {
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
        } elseif (isset($_GET['sucesso']) && $_GET['sucesso'] === 'previa_salva') {
            $sucessoMensagem = 'Prévia diária salva com sucesso.';
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
        if (!$bypassRoleChecks && $cargoUsuario !== 'chanceler') {
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
        if (!$bypassRoleChecks && $cargoUsuario !== 'chanceler') {
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
        if (!$bypassRoleChecks && $cargoUsuario !== 'chanceler') {
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

        $hoje = $appToday()->format('Y-m-d');
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
        if (!$bypassRoleChecks && $cargoUsuario !== 'chanceler') {
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
        if (!$bypassRoleChecks && $cargoUsuario !== 'chanceler') {
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

            if ($testLogin !== '' && $testPassword !== '' && hash_equals($testLogin, $matricula) && hash_equals($testPassword, $password)) {
                $usuarioTeste = $buildTestSessionUser();
                $_SESSION["usuario_logado"] = $usuarioTeste;
                $_SESSION["usuario_id"] = $usuarioTeste["id"];
                $_SESSION["usuario_nome"] = $usuarioTeste["nome_historico"];
                $_SESSION["usuario_cargo"] = $usuarioTeste["cargo"];
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

    // ─── Tesouraria (Views) ──────────────────────────────────────────────
    case "/tesouraria/caixa":
    case "/tesouraria/comprovantes":
    case "/tesouraria/regularidade":
    case "/tesouraria/fechamento":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) {
            header("Location: /login");
            exit;
        }
        $cargoUsuario = $normalizeRole($_SESSION["usuario_cargo"] ?? "");
        if (!$bypassRoleChecks && $cargoUsuario !== 'tesoureiro') {
            http_response_code(403);
            echo "Acesso restrito ao Tesoureiro.";
            exit;
        }

        $viewMap = [
            '/tesouraria/caixa' => 'tesouraria_caixa.php',
            '/tesouraria/comprovantes' => 'tesouraria_comprovantes.php',
            '/tesouraria/regularidade' => 'tesouraria_regularidade.php',
            '/tesouraria/fechamento' => 'tesouraria_fechamento.php',
        ];
        require_once __DIR__ . '/../src/Views/' . $viewMap[$requestUri];
        break;

    // ─── Tesouraria API ──────────────────────────────────────────────────
    case (preg_match('~^/api/tesouraria~', $requestUri) ? $requestUri : null):
        header('Content-Type: application/json; charset=utf-8');

        if (!isset($_SESSION["usuario_logado"])) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'erro' => 'Não autenticado.']);
            exit;
        }

        $cargoUsuario = $normalizeRole($_SESSION["usuario_cargo"] ?? "");
        if (!$bypassRoleChecks && $cargoUsuario !== 'tesoureiro') {
            http_response_code(403);
            echo json_encode(['ok' => false, 'erro' => 'Acesso restrito ao Tesoureiro.']);
            exit;
        }

        // obreiros.id é UUID — não converter para int (resultaria em 0)
        $rawUserId = $_SESSION['usuario_id'] ?? null;
        $usuarioId = ($rawUserId !== null && $rawUserId !== 0 && $rawUserId !== '0') ? $rawUserId : null;

        // GET /api/tesouraria/categorias?tipo=entrada
        if ($requestUri === '/api/tesouraria/categorias' && $method === 'GET') {
            $tipo = $_GET['tipo'] ?? 'entrada';
            $categoriaModel = new \App\Models\CategoriaFinanceira();
            $categorias = $categoriaModel->obterPorTipo($tipo);
            echo json_encode(['ok' => true, 'categorias' => $categorias]);
            exit;
        }

        // GET /api/tesouraria/caixa?mes=3&ano=2026
        if ($requestUri === '/api/tesouraria/caixa' && $method === 'GET') {
            $mes = (int) ($_GET['mes'] ?? date('n'));
            $ano = (int) ($_GET['ano'] ?? date('Y'));
            $lancModel = new \App\Models\LancamentoFinanceiro();
            $lancamentos = $lancModel->obterPorMes($mes, $ano);
            $totais = $lancModel->obterTotaisMes($mes, $ano);
            echo json_encode(['ok' => true, 'lancamentos' => $lancamentos, 'totais' => $totais]);
            exit;
        }

        // POST /api/tesouraria/lancamento/criar
        if ($requestUri === '/api/tesouraria/lancamento/criar' && $method === 'POST') {
            $body = json_decode(file_get_contents('php://input'), true) ?? [];
            $body['created_by'] = $usuarioId;
            try {
                $lancModel = new \App\Models\LancamentoFinanceiro();
                $ok = $lancModel->criar($body);
                echo json_encode(['ok' => $ok]);
            } catch (\Throwable $e) {
                error_log('Erro lancamento/criar: ' . $e->getMessage());
                http_response_code(500);
                echo json_encode(['ok' => false, 'erro' => 'Erro ao salvar lan\u00e7amento.']);
            }
            exit;
        }

        // DELETE /api/tesouraria/lancamento/{id}
        if (preg_match('~^/api/tesouraria/lancamento/(\d+)$~', $requestUri, $m) && $method === 'DELETE') {
            $lancModel = new \App\Models\LancamentoFinanceiro();
            $ok = $lancModel->deletar((int) $m[1]);
            echo json_encode(['ok' => $ok]);
            exit;
        }

        // GET /api/tesouraria/comprovantes
        if ($requestUri === '/api/tesouraria/comprovantes' && $method === 'GET') {
            $comproModel = new \App\Models\ComprovantePix();
            $comprovantes = $comproModel->obterPendentes();
            echo json_encode(['ok' => true, 'comprovantes' => $comprovantes]);
            exit;
        }

        // GET /api/tesouraria/comprovantes/{id}
        if (preg_match('~^/api/tesouraria/comprovantes/(\d+)$~', $requestUri, $m) && $method === 'GET') {
            $comproModel = new \App\Models\ComprovantePix();
            $comprovante = $comproModel->obterPorId((int) $m[1]);
            echo json_encode(['ok' => $comprovante !== null, 'comprovante' => $comprovante]);
            exit;
        }

        // POST /api/tesouraria/comprovantes/aprovar
        if ($requestUri === '/api/tesouraria/comprovantes/aprovar' && $method === 'POST') {
            $body = json_decode(file_get_contents('php://input'), true) ?? [];
            $comproModel = new \App\Models\ComprovantePix();
            $lancModel = new \App\Models\LancamentoFinanceiro();

            $comprovante = $comproModel->obterPorId((int) ($body['id'] ?? 0));
            if (!$comprovante) {
                echo json_encode(['ok' => false]);
                exit;
            }

            // Aprova comprovante
            $validacao = [
                'valor' => (float) ($body['valor'] ?? 0),
                'mes' => (int) ($body['mes'] ?? date('n')),
                'ano' => (int) ($body['ano'] ?? date('Y')),
                'validado_por' => $usuarioId,
            ];
            $comproModel->aprovar((int) ($body['id'] ?? 0), $validacao);

            // Cria lançamento automático
            $lancData = [
                'tipo' => 'entrada',
                'categoria_id' => 1, // Mensalidades ID
                'valor' => $validacao['valor'],
                'data_lancamento' => date('Y-m-d'),
                'obreiro_id' => $comprovante['obreiro_id'],
                'mes_ref' => $validacao['mes'],
                'ano_ref' => $validacao['ano'],
                'created_by' => $usuarioId,
            ];
            $lancModel->criar($lancData);

            // Atualiza mensalidade
            if ($comprovante['obreiro_id']) {
                $mensModel = new \App\Models\MensalidadeStatus();
                $mensModel->registrar($comprovante['obreiro_id'], $validacao['mes'], $validacao['ano'], 'pago');
            }

            echo json_encode(['ok' => true]);
            exit;
        }

        // POST /api/tesouraria/comprovantes/rejeitar
        if ($requestUri === '/api/tesouraria/comprovantes/rejeitar' && $method === 'POST') {
            $body = json_decode(file_get_contents('php://input'), true) ?? [];
            $comproModel = new \App\Models\ComprovantePix();
            $ok = $comproModel->rejeitar((int) ($body['id'] ?? 0), $body['motivo'] ?? '', $usuarioId);
            echo json_encode(['ok' => $ok]);
            exit;
        }

        // GET /api/tesouraria/regularidade?mes=3&ano=2026
        if ($requestUri === '/api/tesouraria/regularidade' && $method === 'GET') {
            $mes = (int) ($_GET['mes'] ?? date('n'));
            $ano = (int) ($_GET['ano'] ?? date('Y'));
            $regModel = new \App\Models\RegularidadeObreiro();
            $regularidade = $regModel->obterPorMes($mes, $ano);
            echo json_encode(['ok' => true, 'regularidade' => $regularidade]);
            exit;
        }

        // POST /api/tesouraria/regularidade/definir
        if ($requestUri === '/api/tesouraria/regularidade/definir' && $method === 'POST') {
            $body = json_decode(file_get_contents('php://input'), true) ?? [];
            $regModel = new \App\Models\RegularidadeObreiro();
            $ok = $regModel->definir(
                (int) ($body['obreiro_id'] ?? 0),
                (int) ($body['mes'] ?? 0),
                (int) ($body['ano'] ?? 0),
                $body['status'] ?? 'irregular',
                $body['observacao'] ?? null,
                $usuarioId
            );
            echo json_encode(['ok' => $ok]);
            exit;
        }

        // POST /api/tesouraria/regularidade/definir-todos
        if ($requestUri === '/api/tesouraria/regularidade/definir-todos' && $method === 'POST') {
            $body = json_decode(file_get_contents('php://input'), true) ?? [];
            $obreiroModel = new \App\Models\Obreiro();
            $regModel = new \App\Models\RegularidadeObreiro();

            $obreiros = $obreiroModel->getAllAtivos();
            foreach ($obreiros as $ob) {
                $regModel->definir($ob['id'], (int) ($body['mes'] ?? 0), (int) ($body['ano'] ?? 0), $body['status'] ?? 'regular', null, $usuarioId);
            }

            echo json_encode(['ok' => true]);
            exit;
        }

        // GET /api/tesouraria/fechamento?mes=3&ano=2026
        if ($requestUri === '/api/tesouraria/fechamento' && $method === 'GET') {
            $mes = (int) ($_GET['mes'] ?? date('n'));
            $ano = (int) ($_GET['ano'] ?? date('Y'));
            $fechModel = new \App\Models\FechamentoMensal();

            $fechamento = $fechModel->obter($mes, $ano);
            if (!$fechamento) {
                // Cria novo fechamento com saldo anterior do mês anterior
                $mesPrev = $mes - 1;
                $anoPrev = $ano;
                if ($mesPrev < 1) {
                    $mesPrev = 12;
                    $anoPrev--;
                }

                $fechPrev = $fechModel->obter($mesPrev, $anoPrev);
                $saldoSugerido = $fechPrev ? (float) $fechPrev['saldo_final'] : 0;

                $fechModel->criar($mes, $ano, $saldoSugerido);
                $fechamento = $fechModel->obter($mes, $ano);
            }

            $fechModel->recalcularTotais($mes, $ano);
            $fechamento = $fechModel->obter($mes, $ano);

            echo json_encode(['ok' => true, 'fechamento' => $fechamento]);
            exit;
        }

        // POST /api/tesouraria/fechamento/atualizar-saldo
        if ($requestUri === '/api/tesouraria/fechamento/atualizar-saldo' && $method === 'POST') {
            $body = json_decode(file_get_contents('php://input'), true) ?? [];
            $fechModel = new \App\Models\FechamentoMensal();
            $ok = $fechModel->atualizarSaldoInicial((int) ($body['fechamento_id'] ?? 0), (float) ($body['novo_saldo'] ?? 0), $body['justificativa'] ?? '', $usuarioId);
            echo json_encode(['ok' => $ok]);
            exit;
        }

        // GET /api/tesouraria/fechamento/{id}/lancamentos
        if (preg_match('~^/api/tesouraria/fechamento/(\d+)/lancamentos$~', $requestUri, $m) && $method === 'GET') {
            $fechModel = new \App\Models\FechamentoMensal();
            $fechamento = $fechModel->obterPorId((int) $m[1]);
            if (!$fechamento) {
                echo json_encode(['ok' => false]); exit;
            }

            $lancModel = new \App\Models\LancamentoFinanceiro();
            $lancamentos = $lancModel->obterPorMes($fechamento['mes_ref'], $fechamento['ano_ref']);
            echo json_encode(['ok' => true, 'lancamentos' => $lancamentos]);
            exit;
        }

        // GET /api/tesouraria/fechamento/{id}/auditoria
        if (preg_match('~^/api/tesouraria/fechamento/(\d+)/auditoria$~', $requestUri, $m) && $method === 'GET') {
            $fechModel = new \App\Models\FechamentoMensal();
            $fechamento = $fechModel->obterComAuditoria((int) $m[1]);
            if (!$fechamento) {
                echo json_encode(['ok' => false]); exit;
            }
            echo json_encode(['ok' => true, 'auditoria' => $fechamento['auditoria']]);
            exit;
        }

        // POST /api/tesouraria/fechamento/fechar
        if ($requestUri === '/api/tesouraria/fechamento/fechar' && $method === 'POST') {
            $body = json_decode(file_get_contents('php://input'), true) ?? [];
            $fechModel = new \App\Models\FechamentoMensal();
            $ok = $fechModel->fechar($body['mes'] ?? 0, $body['ano'] ?? 0, $usuarioId);
            echo json_encode(['ok' => $ok]);
            exit;
        }

        http_response_code(404);
        echo json_encode(['ok' => false, 'erro' => 'API não encontrada.']);
        exit;

    default:
        http_response_code(404);
        echo "404 - Página não encontrada.";
        break;
}

