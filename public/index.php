<?php
session_start();

use App\Config\Env;
use App\Models\Cargo;

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
$isTestSession = isset($_SESSION["usuario_id"]) && (string) $_SESSION["usuario_id"] === '0';
$bypassRoleChecks = $openTestAccess || $isTestSession || ($allowAllPanels && isset($_SESSION["usuario_logado"]));
$resolveTelegramGroupId = static function (): string {
    $candidates = [
        trim((string) ($_ENV['TELEGRAM_CHAT_ID_GROUP'] ?? '')),
        trim((string) ($_ENV['TELEGRAM_GRUPO_ID'] ?? '')),
        trim((string) ($_ENV['TELEGRAM_GROUP_ID'] ?? '')),
        trim((string) ($_ENV['TELEGRAM_CHAT_ID'] ?? '')),
    ];

    foreach ($candidates as $candidate) {
        if ($candidate !== '') {
            return $candidate;
        }
    }

    return '';
};

$normalizeRole = static function (?string $cargo): string {
    $cargo = strtolower(trim((string) $cargo));
    $cargo = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $cargo) ?: $cargo;
    $cargo = preg_replace('/[^a-z0-9_]+/', '', $cargo) ?? '';
    return $cargo;
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

$redirectEfemerides = static function (array $params = []): void {
    $params = array_filter(
        $params,
        static fn ($value) => $value !== null && $value !== ''
    );
    $query = http_build_query($params);
    $url = '/chancelaria/efemerides' . ($query !== '' ? '?' . $query : '');
    header('Location: ' . $url);
    exit;
};

$buildTestSessionUser = static function () use ($normalizeRole, $testDisplayName, $testRole): array {
    $role = $normalizeRole($testRole);

    return [
        "id" => 0,
        "nome_historico" => $testDisplayName,
        "nome_completo" => "Acesso temporario para homologacao",
        "cargo" => $role,
        "cargos" => [$role],
        "ativo" => true,
    ];
};

$syncSessionRoles = static function (?array $usuario = null) use ($normalizeRole): array {
    $usuario = $usuario ?? ($_SESSION['usuario_logado'] ?? null);
    $fallback = $normalizeRole($usuario['cargo'] ?? $_SESSION['usuario_cargo'] ?? '');

    $slugs = [];
    $codigos = [];
    $usuarioId = (string) ($usuario['id'] ?? $_SESSION['usuario_id'] ?? '');

    if ($usuarioId !== '' && $usuarioId !== '0') {
        try {
            $cargoModel = new Cargo();
            $codigos = $cargoModel->getCodigosAtivosDoObreiro($usuarioId);
            $slugs = Cargo::slugsFromCodigos($codigos);
        } catch (\Throwable $e) {
            error_log('Falha ao sincronizar cargos da sessao: ' . $e->getMessage());
        }
    }

    if ($slugs === [] && $fallback !== '') {
        $slugs = [$fallback];
    }

    $principal = Cargo::resolverCargoPrincipal($slugs, $fallback);

    if (isset($_SESSION['usuario_logado']) && is_array($_SESSION['usuario_logado'])) {
        $_SESSION['usuario_logado']['cargo'] = $principal;
        $_SESSION['usuario_logado']['cargos'] = $slugs;
    }

    $_SESSION['usuario_cargo'] = $principal;
    $_SESSION['usuario_cargos'] = $slugs;
    $_SESSION['usuario_cargos_codigos'] = $codigos;

    return [$principal, $slugs, $codigos];
};

$sessionHasRole = static function (string ...$roles) use ($normalizeRole, $bypassRoleChecks): bool {
    if ($bypassRoleChecks) {
        return true;
    }

    $sessionRoles = array_values(array_unique(array_filter(array_map(
        $normalizeRole,
        $_SESSION['usuario_cargos'] ?? [$_SESSION['usuario_cargo'] ?? '']
    ))));

    foreach ($roles as $role) {
        if (in_array($normalizeRole($role), $sessionRoles, true)) {
            return true;
        }
    }

    return false;
};

$resolveAuthorizedTelegramObreiro = static function (string ...$roles) use ($normalizeRole): ?array {
    $initData = trim((string) ($_POST['init_data'] ?? $_GET['init_data'] ?? ''));
    if ($initData === '') {
        return null;
    }

    $botToken = trim((string) ($_ENV['TELEGRAM_BOT_TOKEN'] ?? ''));
    $telegramUser = \App\Services\TelegramInitDataValidator::validate($initData, $botToken);
    if ($telegramUser === null || empty($telegramUser['id'])) {
        return null;
    }

    try {
        $obreiro = (new \App\Models\Obreiro())->findByTelegramId((int) $telegramUser['id']);
    } catch (\Throwable $e) {
        error_log('Falha ao resolver obreiro por initData: ' . $e->getMessage());
        return null;
    }

    if (!$obreiro) {
        return null;
    }

    $cargos = array_values(array_unique(array_filter(array_map(
        $normalizeRole,
        $obreiro['cargos'] ?? [$obreiro['cargo_principal'] ?? $obreiro['cargo'] ?? '']
    ))));

    foreach ($roles as $role) {
        if (in_array($normalizeRole($role), $cargos, true)) {
            return $obreiro;
        }
    }

    return null;
};

$getJsonBody = static function (): array {
    static $parsed = null;
    if ($parsed !== null) {
        return $parsed;
    }

    $raw = file_get_contents('php://input');
    if (!is_string($raw) || trim($raw) === '') {
        $parsed = [];
        return $parsed;
    }

    $decoded = json_decode($raw, true);
    $parsed = is_array($decoded) ? $decoded : [];
    return $parsed;
};

$resolveObreiroByInitData = static function (?string $initData = null): ?array {
    $initData = trim((string) $initData);
    if ($initData === '') {
        return null;
    }

    $botToken = trim((string) ($_ENV['TELEGRAM_BOT_TOKEN'] ?? ''));
    $telegramUser = \App\Services\TelegramInitDataValidator::validate($initData, $botToken);
    if ($telegramUser === null || empty($telegramUser['id'])) {
        return null;
    }

    try {
        return (new \App\Models\Obreiro())->findByTelegramId((int) $telegramUser['id']);
    } catch (\Throwable $e) {
        error_log('Falha ao resolver miniapp por initData: ' . $e->getMessage());
        return null;
    }
};

if ($openTestAccess && !isset($_SESSION["usuario_logado"])) {
    $_SESSION["usuario_logado"] = $buildTestSessionUser();
    $_SESSION["usuario_id"] = 0;
    $_SESSION["usuario_nome"] = $_SESSION["usuario_logado"]["nome_historico"];
    $_SESSION["usuario_cargo"] = $_SESSION["usuario_logado"]["cargo"];
    $_SESSION["usuario_cargos"] = $_SESSION["usuario_logado"]["cargos"];
    $_SESSION["usuario_cargos_codigos"] = [];
}

if (isset($_SESSION['usuario_logado']) && !$openTestAccess && !$isTestSession) {
    $syncSessionRoles();
}

// ==========================================
// Endpoint para envio automatico de efemerides (Cron Job)
if ($requestUri === '/api/cron/efemerides-diarias' && $method === 'GET') {
    $token = $_GET['token'] ?? '';
    $tokenEsperado = trim((string) ($_ENV['CRON_EFEMERIDES_TOKEN'] ?? $_ENV['CRON_SECRET_TOKEN'] ?? ''));
    if ($tokenEsperado === '') {
        $tokenEsperado = 'SUA_SENHA_SECRETA';
    }
    if ($token !== $tokenEsperado) {
        http_response_code(403);
        echo json_encode(['status' => 'erro', 'mensagem' => 'Token invalido']);
        exit;
    }

    require_once __DIR__ . '/../src/Models/EfemerideRegistro.php';
    require_once __DIR__ . '/../src/Services/EfemeridesComposer.php';
    require_once __DIR__ . '/../src/Bot/TelegramClient.php';

    $efemerideModel = new \App\Models\EfemerideRegistro();
    $registros = $efemerideModel->getRegistrosDoDia();
    $composer = new \App\Services\EfemeridesComposer();
    $mensagem = $composer->composeDailyPreview($registros);

    $telegram = new \App\Bot\TelegramClient();
    $grupoId = $resolveTelegramGroupId();
    if (!$grupoId) {
        http_response_code(500);
        echo json_encode(['status' => 'erro', 'mensagem' => 'ID do grupo nao configurado']);
        exit;
    }
    $telegram->sendMessage($grupoId, $mensagem, ['parse_mode' => 'HTML']);
    echo json_encode(['status' => 'ok']);
    exit;
}
// ROTEAMENTO PRINCIPAL
// ==========================================
if ($requestUri === '/chancelaria/certificado/gerar' && $method === 'POST') {
    $sessionAutorizada = isset($_SESSION['usuario_logado']) && $sessionHasRole('chanceler', 'admin');
    $telegramObreiro = $sessionAutorizada ? null : $resolveAuthorizedTelegramObreiro('chanceler', 'admin');
    if (!$sessionAutorizada && !$telegramObreiro) {
        http_response_code(403);
        echo "<div style='padding: 20px; color: red; font-family: sans-serif;'>Acesso restrito ao Chanceler ou Administrador.</div>";
        exit;
    }

    require_once __DIR__ . '/../src/Services/CertificadoGenerator.php';

    $nome = $_POST['nome_visitante'] ?? '';
    $loja = $_POST['loja_visitante'] ?? '';
    $oriente = $_POST['oriente'] ?? '';
    $tipoSessao = $_POST['tipo_sessao'] ?? '';
    $grauSessao = $_POST['grau_sessao'] ?? '';
    $dataSessao = $_POST['data_sessao'] ?? '';
    $chatId = $_POST['chat_id'] ?? '';

    try {
        $generator = new \App\Services\CertificadoGenerator();
        $caminhoImagem = $generator->gerar($nome, $loja, $oriente, $tipoSessao, $grauSessao, $dataSessao);

        if (!empty($chatId)) {
            require_once __DIR__ . '/../src/Bot/TelegramClient.php';
            $telegram = new \App\Bot\TelegramClient();
            $telegram->sendPhoto($chatId, $caminhoImagem, "Certificado gerado com sucesso!\n\nAgora e so encaminhar para o Irmao {$nome}.");
        }

        echo "<script src='https://telegram.org/js/telegram-web-app.js'></script>
        <script>
            (function () {
                const tg = window.Telegram && window.Telegram.WebApp ? window.Telegram.WebApp : null;
                if (tg && typeof tg.showAlert === 'function') {
                    tg.showAlert('Certificado gerado e enviado no seu chat!', function() {
                        if (typeof tg.close === 'function') {
                            tg.close();
                        }
                    });
                    return;
                }
                alert('Certificado gerado com sucesso!');
                window.history.back();
            })();
        </script>";
        exit;

    } catch (Exception $e) {
        echo "<div style='padding: 20px; color: red; font-family: sans-serif;'>Erro ao gerar certificado: " . $e->getMessage() . "</div>";
        exit;
    }
}

if ($requestUri === '/chancelaria/certificado' && $method === 'GET') {
    if (!$openTestAccess && !isset($_SESSION['usuario_logado'])) {
        $initData = trim((string) ($_GET['init_data'] ?? ''));
        if ($initData === '') {
            require_once __DIR__ . '/../src/Views/chancelaria_certificado.php';
            exit;
        }

        $telegramObreiro = $resolveAuthorizedTelegramObreiro('chanceler', 'admin');
        if (!$telegramObreiro) {
            http_response_code(403);
            echo "Acesso restrito ao Chanceler ou Administrador.";
            exit;
        }
    } elseif (!$sessionHasRole('chanceler', 'admin')) {
        http_response_code(403);
        echo "Acesso restrito ao Chanceler ou Administrador.";
        exit;
    }

    require_once __DIR__ . '/../src/Views/chancelaria_certificado.php';
    exit;
}

switch ($requestUri) {
    // Gestao de Cargos (Admin)
    case "/admin/cargos":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) {
            header("Location: /login");
            exit;
        }
        if (!$sessionHasRole('admin')) {
            http_response_code(403);
            echo "Acesso restrito ao Administrador.";
            exit;
        }
        (new \App\Controllers\AdminController())->listarCargos();
        break;

    case "/admin/cargos/salvar":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) {
            header("Location: /login");
            exit;
        }
        if (!$sessionHasRole('admin')) {
            http_response_code(403);
            echo "Acesso restrito ao Administrador.";
            exit;
        }
        (new \App\Controllers\AdminController())->salvarCargo();
        break;

    // Telas antigas restauradas
    case "/obreiros":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) { header("Location: /login"); exit; }
        if (!$sessionHasRole('secretario', 'chanceler', 'admin')) {
            http_response_code(403);
            echo "Acesso restrito a Secretaria, Chancelaria ou Administrador.";
            exit;
        }
        $obreiroModel = new \App\Models\Obreiro();
        $obreiros = $obreiroModel->getAllAtivos();
        require_once __DIR__ . "/../src/Views/obreiros.php";
        break;

    case "/obreiros/novo":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) { header("Location: /login"); exit; }
        if (!$sessionHasRole('secretario', 'admin')) {
            http_response_code(403);
            echo "Acesso restrito ao Secretario ou Administrador.";
            exit;
        }
        require_once __DIR__ . "/../src/Views/obreiro_form.php";
        break;

    case "/obreiros/salvar":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) { header("Location: /login"); exit; }
        if (!$sessionHasRole('secretario', 'admin')) {
            http_response_code(403);
            echo "Acesso restrito ao Secretario ou Administrador.";
            exit;
        }
        if ($method !== 'POST') {
            header("Location: /obreiros/novo");
            exit;
        }

        $obreiroModel = new \App\Models\Obreiro();
        $ok = $obreiroModel->create($_POST);
        if ($ok) {
            header("Location: /obreiros?sucesso=1");
        } else {
            header("Location: /obreiros/novo?erro=1");
        }
        exit;

    case "/obreiros/editar":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) { header("Location: /login"); exit; }
        if (!$sessionHasRole('secretario', 'admin')) {
            http_response_code(403);
            echo "Acesso restrito ao Secretario ou Administrador.";
            exit;
        }
        $id = (string) ($_GET['id'] ?? '');
        if ($id === '') {
            header("Location: /obreiros");
            exit;
        }

        $obreiroModel = new \App\Models\Obreiro();
        $obreiro = $obreiroModel->findById($id);
        if (!$obreiro) {
            http_response_code(404);
            echo "Obreiro nao encontrado.";
            exit;
        }
        require_once __DIR__ . "/../src/Views/obreiro_editar.php";
        break;

    case "/obreiros/atualizar":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) { header("Location: /login"); exit; }
        if (!$sessionHasRole('secretario', 'admin')) {
            http_response_code(403);
            echo "Acesso restrito ao Secretario ou Administrador.";
            exit;
        }
        if ($method !== 'POST') {
            header("Location: /obreiros");
            exit;
        }

        $obreiroId = (string) ($_POST['id'] ?? '');
        if ($obreiroId === '') {
            header("Location: /obreiros?erro=1");
            exit;
        }

        $obreiroModel = new \App\Models\Obreiro();
        $ok = $obreiroModel->update($_POST);
        if ($ok) {
            header("Location: /obreiros/editar?id=" . urlencode($obreiroId) . "&sucesso=1");
        } else {
            header("Location: /obreiros/editar?id=" . urlencode($obreiroId) . "&erro=1");
        }
        exit;

    case "/secretaria":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) { header("Location: /login"); exit; }
        if (!$sessionHasRole('secretario', 'veneravel', 'admin')) {
            http_response_code(403);
            echo "Acesso restrito ao Secretario, Veneravel ou Administrador.";
            exit;
        }
        (new \App\Controllers\SecretariaController())->index();
        break;

    case "/secretaria/votacao":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) { header("Location: /login"); exit; }
        (new \App\Controllers\SecretariaController())->votacao();
        break;

    case "/secretaria/sessoes/salvar":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) { header("Location: /login"); exit; }
        if (!$sessionHasRole('secretario', 'admin')) {
            http_response_code(403);
            echo "Acesso restrito ao Secretario ou Administrador.";
            exit;
        }
        (new \App\Controllers\SecretariaController())->salvarSessao();
        break;

    case "/secretaria/trabalhos/salvar":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) { header("Location: /login"); exit; }
        if (!$sessionHasRole('secretario', 'admin')) {
            http_response_code(403);
            echo "Acesso restrito ao Secretario ou Administrador.";
            exit;
        }
        (new \App\Controllers\SecretariaController())->salvarTrabalho();
        break;

    case "/secretaria/publicacoes/salvar":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) { header("Location: /login"); exit; }
        if (!$sessionHasRole('secretario', 'admin')) {
            http_response_code(403);
            echo "Acesso restrito ao Secretario ou Administrador.";
            exit;
        }
        (new \App\Controllers\SecretariaController())->salvarPublicacao();
        break;

    case "/secretaria/balaustres/salvar":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) { header("Location: /login"); exit; }
        if (!$sessionHasRole('secretario', 'admin')) {
            http_response_code(403);
            echo "Acesso restrito ao Secretario ou Administrador.";
            exit;
        }
        (new \App\Controllers\SecretariaController())->salvarBalaustre();
        break;

    case "/secretaria/balaustres/apto":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) { header("Location: /login"); exit; }
        if (!$sessionHasRole('secretario', 'admin')) {
            http_response_code(403);
            echo "Acesso restrito ao Secretario ou Administrador.";
            exit;
        }
        (new \App\Controllers\SecretariaController())->marcarBalaustreApto();
        break;

    case "/secretaria/balaustres/abrir-votacao":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) { header("Location: /login"); exit; }
        if (!$sessionHasRole('veneravel', 'admin')) {
            http_response_code(403);
            echo "Acesso restrito ao Veneravel Mestre ou Administrador.";
            exit;
        }
        (new \App\Controllers\SecretariaController())->abrirVotacaoBalaustre();
        break;

    case "/secretaria/balaustres/votar":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) { header("Location: /login"); exit; }
        (new \App\Controllers\SecretariaController())->votarBalaustre();
        break;

    case "/secretaria/balaustres/encerrar-votacao":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) { header("Location: /login"); exit; }
        if (!$sessionHasRole('veneravel', 'admin')) {
            http_response_code(403);
            echo "Acesso restrito ao Veneravel Mestre ou Administrador.";
            exit;
        }
        (new \App\Controllers\SecretariaController())->encerrarVotacaoBalaustre();
        break;

    case "/tesouraria/caixa":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) { header("Location: /login"); exit; }
        if (!$sessionHasRole('tesoureiro', 'admin')) {
            http_response_code(403);
            echo "Acesso restrito ao Tesoureiro ou Administrador.";
            exit;
        }
        require_once __DIR__ . "/../src/Views/tesouraria_caixa.php";
        break;

    case "/tesouraria/comprovantes":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) { header("Location: /login"); exit; }
        if (!$sessionHasRole('tesoureiro', 'admin')) {
            http_response_code(403);
            echo "Acesso restrito ao Tesoureiro ou Administrador.";
            exit;
        }
        require_once __DIR__ . "/../src/Views/tesouraria_comprovantes.php";
        break;

    case "/tesouraria/regularidade":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) { header("Location: /login"); exit; }
        if (!$sessionHasRole('tesoureiro', 'admin')) {
            http_response_code(403);
            echo "Acesso restrito ao Tesoureiro ou Administrador.";
            exit;
        }
        require_once __DIR__ . "/../src/Views/tesouraria_regularidade.php";
        break;

    case "/tesouraria/fechamento":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) { header("Location: /login"); exit; }
        if (!$sessionHasRole('tesoureiro', 'admin')) {
            http_response_code(403);
            echo "Acesso restrito ao Tesoureiro ou Administrador.";
            exit;
        }
        require_once __DIR__ . "/../src/Views/tesouraria_fechamento.php";
        break;

    case "/biblioteca/classificar":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) {
            header("Location: /login");
            exit;
        }
        if (!$sessionHasRole('bibliotecario', 'admin', 'veneravel')) {
            http_response_code(403);
            echo "Acesso restrito ao Bibliotecario, Veneravel ou Administrador.";
            exit;
        }
        $bibliotecaController = new \App\Controllers\BibliotecaController();
        $bibliotecaController->classificar();
        break;

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

    case "/chancelaria/efemerides/salvar-previa":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) {
            header("Location: /login");
            exit;
        }
        if (!$sessionHasRole('chanceler', 'admin')) {
            http_response_code(403);
            echo "Acesso restrito ao Chanceler ou Administrador.";
            exit;
        }
        if ($method !== 'POST') {
            $redirectEfemerides();
        }

        $mensagemPreview = trim((string) ($_POST['mensagem_preview'] ?? ''));
        if ($mensagemPreview === '') {
            $redirectEfemerides(['erro' => 'previa_vazia']);
        }

        $previaModel = new \App\Models\EfemeridePreviaDiaria();
        $ok = $previaModel->salvarOuAtualizar(
            $appToday()->format('Y-m-d'),
            $mensagemPreview,
            false
        );

        $redirectEfemerides($ok ? ['sucesso' => 'previa_salva'] : ['erro' => 'falha_salvar_previa']);
        break;

    case "/chancelaria/efemerides/enviar-previa":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) {
            header("Location: /login");
            exit;
        }
        if (!$sessionHasRole('chanceler', 'admin')) {
            http_response_code(403);
            echo "Acesso restrito ao Chanceler ou Administrador.";
            exit;
        }
        if ($method !== 'POST') {
            $redirectEfemerides();
        }

        $dadosEfemerides = $buildEfemeridesPreview();
        $mensagemPreview = trim((string) ($dadosEfemerides['mensagemPreview'] ?? ''));
        if ($mensagemPreview === '') {
            $redirectEfemerides(['erro' => 'previa_vazia']);
        }

        $telegramService = new \App\Services\TelegramService();
        $ok = $telegramService->sendMessageToReview($mensagemPreview);
        $redirectEfemerides($ok ? ['sucesso' => 'previa_enviada'] : ['erro' => 'falha_enviar_previa']);
        break;

    case "/chancelaria/efemerides/enviar-grupo":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) {
            header("Location: /login");
            exit;
        }
        if (!$sessionHasRole('chanceler', 'admin')) {
            http_response_code(403);
            echo "Acesso restrito ao Chanceler ou Administrador.";
            exit;
        }
        if ($method !== 'POST') {
            $redirectEfemerides();
        }

        $dadosEfemerides = $buildEfemeridesPreview();
        $mensagemPreview = trim((string) ($dadosEfemerides['mensagemPreview'] ?? ''));
        if ($mensagemPreview === '') {
            $redirectEfemerides(['erro' => 'previa_vazia']);
        }

        $telegramService = new \App\Services\TelegramService();
        $ok = $telegramService->sendMessageToGroup($mensagemPreview);
        $redirectEfemerides($ok ? ['sucesso' => 'enviado'] : ['erro' => 'falha_enviar_grupo']);
        break;

    case "/chancelaria/efemerides/salvar":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) {
            header("Location: /login");
            exit;
        }
        if (!$sessionHasRole('chanceler', 'admin')) {
            http_response_code(403);
            echo "Acesso restrito ao Chanceler ou Administrador.";
            exit;
        }
        if ($method !== 'POST') {
            $redirectEfemerides();
        }

        $nome = trim((string) ($_POST['nome'] ?? ''));
        $tipo = trim((string) ($_POST['tipo'] ?? ''));
        $dataEvento = trim((string) ($_POST['data_evento'] ?? ''));
        $dataValida = \DateTimeImmutable::createFromFormat('Y-m-d', $dataEvento) !== false;
        if ($nome === '' || $tipo === '' || $dataEvento === '' || !$dataValida) {
            $redirectEfemerides(['erro' => 'registro_invalido']);
        }

        $registroModel = new \App\Models\EfemerideRegistro();
        $createdBy = isset($_SESSION['usuario_id']) ? (int) $_SESSION['usuario_id'] : null;
        $ok = $registroModel->create($_POST, $createdBy);
        $redirectEfemerides($ok ? ['sucesso' => 'registro_salvo'] : ['erro' => 'falha_salvar_registro']);
        break;

    case "/chancelaria/efemerides/atualizar":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) {
            header("Location: /login");
            exit;
        }
        if (!$sessionHasRole('chanceler', 'admin')) {
            http_response_code(403);
            echo "Acesso restrito ao Chanceler ou Administrador.";
            exit;
        }
        if ($method !== 'POST') {
            $redirectEfemerides();
        }

        $registroId = (int) ($_POST['registro_id'] ?? 0);
        $nome = trim((string) ($_POST['nome'] ?? ''));
        $tipo = trim((string) ($_POST['tipo'] ?? ''));
        $dataEvento = trim((string) ($_POST['data_evento'] ?? ''));
        $dataValida = \DateTimeImmutable::createFromFormat('Y-m-d', $dataEvento) !== false;

        if ($registroId <= 0 || $nome === '' || $tipo === '' || $dataEvento === '' || !$dataValida) {
            $redirectEfemerides(['erro' => 'registro_invalido']);
        }

        $registroModel = new \App\Models\EfemerideRegistro();
        $ok = $registroModel->atualizar($registroId, $_POST);
        $redirectEfemerides($ok ? ['sucesso' => 'registro_atualizado'] : ['erro' => 'falha_atualizar_registro']);
        break;

    case "/chancelaria/efemerides/desativar":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) {
            header("Location: /login");
            exit;
        }
        if (!$sessionHasRole('chanceler', 'admin')) {
            http_response_code(403);
            echo "Acesso restrito ao Chanceler ou Administrador.";
            exit;
        }
        if ($method !== 'POST') {
            $redirectEfemerides();
        }

        $registroId = (int) ($_POST['id'] ?? 0);
        if ($registroId <= 0) {
            $redirectEfemerides(['erro' => 'id_invalido']);
        }

        $registroModel = new \App\Models\EfemerideRegistro();
        $ok = $registroModel->desativar($registroId);
        $redirectEfemerides($ok ? ['sucesso' => 'registro_desativado'] : ['erro' => 'falha_desativar']);
        break;

    case "/chancelaria/efemerides":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) {
            header("Location: /login");
            exit;
        }
        if (!$sessionHasRole('chanceler', 'admin')) {
            http_response_code(403);
            echo "Acesso restrito ao Chanceler ou Administrador.";
            exit;
        }

        $sucessoMensagem = match ((string) ($_GET['sucesso'] ?? '')) {
            'previa_salva' => 'Previa salva com sucesso.',
            'previa_enviada' => 'Previa enviada no privado do Chanceler.',
            'enviado' => 'Mensagem enviada ao grupo oficial.',
            'registro_salvo' => 'Registro salvo com sucesso.',
            'registro_atualizado' => 'Registro atualizado com sucesso.',
            'registro_desativado' => 'Registro desativado com sucesso.',
            default => null,
        };

        $erroMensagem = match ((string) ($_GET['erro'] ?? '')) {
            'previa_vazia' => 'A mensagem da previa nao pode ficar vazia.',
            'falha_salvar_previa' => 'Nao foi possivel salvar a previa.',
            'falha_enviar_previa' => 'Falha ao enviar a previa no privado. Verifique TELEGRAM_CHAT_ID_CHANCELER.',
            'falha_enviar_grupo' => 'Falha ao enviar no grupo oficial. Verifique TELEGRAM_CHAT_ID_GROUP.',
            'registro_invalido' => 'Preencha nome, tipo e data do evento corretamente.',
            'falha_salvar_registro' => 'Nao foi possivel salvar o registro.',
            'falha_atualizar_registro' => 'Nao foi possivel atualizar o registro.',
            'id_invalido' => 'Registro invalido para desativacao.',
            'falha_desativar' => 'Nao foi possivel desativar o registro.',
            default => null,
        };

        $dadosEfemerides = $buildEfemeridesPreview();
        $registrosHoje = $dadosEfemerides['registrosHoje'];
        $filtroTermo = trim((string) ($_GET['termo'] ?? ''));
        $filtroTipo = trim((string) ($_GET['tipo'] ?? ''));
        $filtroAtivo = trim((string) ($_GET['ativo'] ?? '1'));
        $filtroDataIni = trim((string) ($_GET['data_ini'] ?? ''));
        $filtroDataFim = trim((string) ($_GET['data_fim'] ?? ''));
        $filtrosEfemeride = [
            'termo' => $filtroTermo,
            'tipo' => $filtroTipo,
            'ativo' => $filtroAtivo,
            'data_ini' => $filtroDataIni,
            'data_fim' => $filtroDataFim,
        ];
        $registroModel = new \App\Models\EfemerideRegistro();
        $registrosRecentes = $registroModel->buscarComFiltros($filtrosEfemeride, 300);
        $tiposEfemeride = [
            'Aniversário',
            'Iniciação',
            'Elevação',
            'Exaltação',
            'Instalação',
            'Oriente Eterno',
            'História',
            'Posse Grão Mestre',
            'Concessão de Membro Honorário',
            'Filiação',
        ];
        $mensagemBase = $dadosEfemerides['mensagemBase'];
        $mensagemPreview = $dadosEfemerides['mensagemPreview'];

        require_once __DIR__ . "/../src/Views/efemerides_chanceler.php";
        break;

    case "/miniapp/aniversario":
        require_once __DIR__ . "/../src/Views/miniapp/aniversario.php";
        break;

    case "/miniapp/data-maconica":
        require_once __DIR__ . "/../src/Views/miniapp/data-maconica.php";
        break;

    case "/miniapp/historico":
        require_once __DIR__ . "/../src/Views/miniapp/historico.php";
        break;

    case "/miniapp/fallback":
        require_once __DIR__ . "/../src/Views/miniapp/fallback.php";
        break;

    case (preg_match('~^/api/miniapp~', $requestUri) ? $requestUri : null):
        header('Content-Type: application/json; charset=utf-8');

        $body = $getJsonBody();
        $initData = trim((string) ($body['initData'] ?? $body['init_data'] ?? $_GET['initData'] ?? $_GET['init_data'] ?? ''));
        $miniappObreiro = null;
        $authorizedBySession = isset($_SESSION['usuario_logado']) && $sessionHasRole('chanceler', 'admin');

        if ($authorizedBySession) {
            $miniappObreiro = $_SESSION['usuario_logado'];
        } else {
            $miniappObreiro = $resolveObreiroByInitData($initData);
            if (!$miniappObreiro) {
                http_response_code(401);
                echo json_encode(['ok' => false, 'erro' => 'Nao autenticado no miniapp.']);
                exit;
            }

            $roles = array_values(array_unique(array_filter(array_map(
                $normalizeRole,
                $miniappObreiro['cargos'] ?? [$miniappObreiro['cargo_principal'] ?? $miniappObreiro['cargo'] ?? '']
            ))));
            if (!in_array('chanceler', $roles, true) && !in_array('admin', $roles, true)) {
                http_response_code(403);
                echo json_encode(['ok' => false, 'erro' => 'Acesso restrito ao Chanceler ou Administrador.']);
                exit;
            }
        }

        $efemerideModel = new \App\Models\EfemerideRegistro();
        $mensagensModel = new \App\Models\MensagemComplementar();

        if ($requestUri === '/api/miniapp/efemeride/salvar' && $method === 'POST') {
            $id = (int) ($body['id'] ?? 0);
            $nome = trim((string) ($body['nome'] ?? ''));
            $tipo = trim((string) ($body['tipo'] ?? ''));
            $dataEvento = trim((string) ($body['data_evento'] ?? ''));
            $dataValida = \DateTimeImmutable::createFromFormat('Y-m-d', $dataEvento) !== false;
            if ($nome === '' || $tipo === '' || $dataEvento === '' || !$dataValida) {
                echo json_encode(['ok' => false, 'erro' => 'Dados invalidos para salvar efemeride.']);
                exit;
            }

            if ($id > 0) {
                $ok = $efemerideModel->atualizar($id, $body);
            } else {
                $createdBy = (int) ($miniappObreiro['id'] ?? ($_SESSION['usuario_id'] ?? 0));
                $ok = $efemerideModel->create($body, $createdBy > 0 ? $createdBy : null);
            }

            echo json_encode(['ok' => $ok]);
            exit;
        }

        if ($requestUri === '/api/miniapp/efemeride/desativar' && $method === 'POST') {
            $id = (int) ($body['id'] ?? 0);
            $ok = $id > 0 ? $efemerideModel->desativar($id) : false;
            echo json_encode(['ok' => $ok]);
            exit;
        }

        if ($requestUri === '/api/miniapp/historico/listar' && $method === 'GET') {
            $registros = $efemerideModel->buscarComFiltros(['tipo' => 'História', 'ativo' => 'all'], 300);
            if ($registros === []) {
                $registros = $efemerideModel->buscarComFiltros(['tipo' => 'Historia', 'ativo' => 'all'], 300);
            }
            echo json_encode(['ok' => true, 'registros' => $registros]);
            exit;
        }

        if ($requestUri === '/api/miniapp/fallback/listar' && $method === 'GET') {
            $mensagens = $mensagensModel->listarPorTipo('fallback');
            echo json_encode(['ok' => true, 'mensagens' => $mensagens]);
            exit;
        }

        if ($requestUri === '/api/miniapp/fallback/salvar' && $method === 'POST') {
            $id = (int) ($body['id'] ?? 0);
            $mensagem = trim((string) ($body['mensagem'] ?? ''));
            if ($mensagem === '') {
                echo json_encode(['ok' => false, 'erro' => 'Mensagem vazia.']);
                exit;
            }

            $ok = $id > 0
                ? $mensagensModel->atualizar($id, $mensagem)
                : $mensagensModel->criar('fallback', $mensagem);
            echo json_encode(['ok' => $ok]);
            exit;
        }

        if ($requestUri === '/api/miniapp/fallback/toggle' && $method === 'POST') {
            $id = (int) ($body['id'] ?? 0);
            $ok = $id > 0 ? $mensagensModel->toggleAtivo($id) : false;
            echo json_encode(['ok' => $ok]);
            exit;
        }

        if ($requestUri === '/api/miniapp/fallback/excluir' && $method === 'POST') {
            $id = (int) ($body['id'] ?? 0);
            $ok = $id > 0 ? $mensagensModel->excluir($id) : false;
            echo json_encode(['ok' => $ok]);
            exit;
        }

        http_response_code(404);
        echo json_encode(['ok' => false, 'erro' => 'API miniapp nao encontrada.']);
        exit;

    // Tesouraria API
    case (preg_match('~^/api/tesouraria~', $requestUri) ? $requestUri : null):
        header('Content-Type: application/json; charset=utf-8');

        if (!$openTestAccess && !isset($_SESSION['usuario_logado'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'erro' => 'Nao autenticado.']);
            exit;
        }
        if (!$sessionHasRole('tesoureiro', 'admin')) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'erro' => 'Acesso restrito ao Tesoureiro ou Administrador.']);
            exit;
        }

        $usuarioId = $_SESSION['usuario_id'] ?? 0;

        if (preg_match('~^/api/tesouraria/lancamento/(\d+)$~', $requestUri, $m) && $method === 'DELETE') {
            $lancModel = new \App\Models\LancamentoFinanceiro();
            $ok = $lancModel->deletar((int) $m[1]);
            echo json_encode(['ok' => $ok]);
            exit;
        }

        if ($requestUri === '/api/tesouraria/comprovantes' && $method === 'GET') {
            $status = $_GET['status'] ?? null;
            $status = in_array($status, ['pendente', 'aprovado', 'rejeitado'], true) ? $status : null;
            $comproModel = new \App\Models\ComprovantePix();
            $comprovantes = $comproModel->obterTodos($status);
            echo json_encode(['ok' => true, 'comprovantes' => $comprovantes]);
            exit;
        }

        if (preg_match('~^/api/tesouraria/comprovantes/(\d+)$~', $requestUri, $m) && $method === 'GET') {
            $comproModel = new \App\Models\ComprovantePix();
            $comprovante = $comproModel->obterPorId((int) $m[1]);
            echo json_encode(['ok' => $comprovante !== null, 'comprovante' => $comprovante]);
            exit;
        }

        if ($requestUri === '/api/tesouraria/comprovantes/aprovar' && $method === 'POST') {
            $body = json_decode(file_get_contents('php://input'), true) ?? [];
            $comproModel = new \App\Models\ComprovantePix();
            $lancModel = new \App\Models\LancamentoFinanceiro();

            $comprovante = $comproModel->obterPorId((int) ($body['id'] ?? 0));
            if (!$comprovante) {
                echo json_encode(['ok' => false]);
                exit;
            }

            $validacao = [
                'valor' => (float) ($body['valor'] ?? 0),
                'mes' => (int) ($body['mes'] ?? date('n')),
                'ano' => (int) ($body['ano'] ?? date('Y')),
                'validado_por' => $usuarioId,
            ];
            $comproModel->aprovar((int) ($body['id'] ?? 0), $validacao);

            $lancData = [
                'tipo' => 'entrada',
                'categoria_id' => 1,
                'valor' => $validacao['valor'],
                'data_lancamento' => date('Y-m-d'),
                'obreiro_id' => $comprovante['obreiro_id'],
                'mes_ref' => $validacao['mes'],
                'ano_ref' => $validacao['ano'],
                'created_by' => $usuarioId,
            ];
            $lancModel->criar($lancData);

            if ($comprovante['obreiro_id']) {
                $mensModel = new \App\Models\MensalidadeStatus();
                $mensModel->registrar($comprovante['obreiro_id'], $validacao['mes'], $validacao['ano'], 'pago');
            }

            echo json_encode(['ok' => true]);
            exit;
        }

        if ($requestUri === '/api/tesouraria/comprovantes/rejeitar' && $method === 'POST') {
            $body = json_decode(file_get_contents('php://input'), true) ?? [];
            $comproModel = new \App\Models\ComprovantePix();
            $ok = $comproModel->rejeitar((int) ($body['id'] ?? 0), $body['motivo'] ?? '', $usuarioId);
            echo json_encode(['ok' => $ok]);
            exit;
        }

        if ($requestUri === '/api/tesouraria/regularidade' && $method === 'GET') {
            $mes = (int) ($_GET['mes'] ?? date('n'));
            $ano = (int) ($_GET['ano'] ?? date('Y'));
            $regModel = new \App\Models\RegularidadeObreiro();
            $regularidade = $regModel->obterPorMes($mes, $ano);
            echo json_encode(['ok' => true, 'regularidade' => $regularidade]);
            exit;
        }

        if ($requestUri === '/api/tesouraria/regularidade/definir' && $method === 'POST') {
            $body = json_decode(file_get_contents('php://input'), true) ?? [];
            $regModel = new \App\Models\RegularidadeObreiro();
            $ok = $regModel->definir(
                (string) ($body['obreiro_id'] ?? ''),
                (int) ($body['mes'] ?? 0),
                (int) ($body['ano'] ?? 0),
                $body['status'] ?? 'irregular',
                $body['observacao'] ?? null,
                $usuarioId
            );
            echo json_encode(['ok' => $ok]);
            exit;
        }

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

        if ($requestUri === '/api/tesouraria/fechamento' && $method === 'GET') {
            $mes = (int) ($_GET['mes'] ?? date('n'));
            $ano = (int) ($_GET['ano'] ?? date('Y'));
            $fechModel = new \App\Models\FechamentoMensal();

            $fechamento = $fechModel->obter($mes, $ano);
            if (!$fechamento) {
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

        if ($requestUri === '/api/tesouraria/fechamento/atualizar-saldo' && $method === 'POST') {
            $body = json_decode(file_get_contents('php://input'), true) ?? [];
            $fechModel = new \App\Models\FechamentoMensal();
            $ok = $fechModel->atualizarSaldoInicial((int) ($body['fechamento_id'] ?? 0), (float) ($body['novo_saldo'] ?? 0), $body['justificativa'] ?? '', $usuarioId);
            echo json_encode(['ok' => $ok]);
            exit;
        }

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

        if (preg_match('~^/api/tesouraria/fechamento/(\d+)/auditoria$~', $requestUri, $m) && $method === 'GET') {
            $fechModel = new \App\Models\FechamentoMensal();
            $fechamento = $fechModel->obterComAuditoria((int) $m[1]);
            if (!$fechamento) {
                echo json_encode(['ok' => false]); exit;
            }
            echo json_encode(['ok' => true, 'auditoria' => $fechamento['auditoria']]);
            exit;
        }

        if ($requestUri === '/api/tesouraria/fechamento/fechar' && $method === 'POST') {
            $body = json_decode(file_get_contents('php://input'), true) ?? [];
            $fechModel = new \App\Models\FechamentoMensal();

            $mes = (int) ($body['mes'] ?? 0);
            $ano = (int) ($body['ano'] ?? 0);
            $fechamentoId = (int) ($body['fechamento_id'] ?? 0);

            if (($mes <= 0 || $ano <= 0) && $fechamentoId > 0) {
                $fechamento = $fechModel->obterPorId($fechamentoId);
                if ($fechamento) {
                    $mes = (int) $fechamento['mes_ref'];
                    $ano = (int) $fechamento['ano_ref'];
                }
            }

            $ok = ($mes > 0 && $ano > 0) ? $fechModel->fechar($mes, $ano, $usuarioId) : false;
            echo json_encode(['ok' => $ok]);
            exit;
        }

        http_response_code(404);
        echo json_encode(['ok' => false, 'erro' => 'API nao encontrada.']);
        exit;

    // Biblioteca (Views)
    case "/biblioteca":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) {
            header("Location: /login");
            exit;
        }
        (new \App\Controllers\BibliotecaController())->index();
        break;

    case "/biblioteca/adicionar":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) {
            header("Location: /login");
            exit;
        }
        if (!$sessionHasRole('bibliotecario', 'admin', 'veneravel')) {
            http_response_code(403);
            echo "Acesso restrito ao Bibliotecario, Veneravel ou Administrador.";
            exit;
        }
        (new \App\Controllers\BibliotecaController())->adicionar();
        break;

    case "/biblioteca/editar":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) {
            header("Location: /login");
            exit;
        }
        if (!$sessionHasRole('bibliotecario', 'admin', 'veneravel')) {
            http_response_code(403);
            echo "Acesso restrito ao Bibliotecario, Veneravel ou Administrador.";
            exit;
        }
        $id = (int) ($_POST['id'] ?? 0);
        (new \App\Controllers\BibliotecaController())->editar($id);
        break;

    case "/biblioteca/excluir":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) {
            header("Location: /login");
            exit;
        }
        if (!$sessionHasRole('bibliotecario', 'admin', 'veneravel')) {
            http_response_code(403);
            echo "Acesso restrito ao Bibliotecario, Veneravel ou Administrador.";
            exit;
        }
        $id = (int) ($_POST['id'] ?? 0);
        (new \App\Controllers\BibliotecaController())->excluir($id);
        break;

    case "/biblioteca/emprestimos":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) {
            header("Location: /login");
            exit;
        }
        (new \App\Controllers\BibliotecaController())->emprestimos();
        break;

    case "/biblioteca/novo":
        require_once __DIR__ . '/tg/novo.php';
        break;

    case "/biblioteca/scanner":
        require_once __DIR__ . '/tg/scanner.php';
        break;

    case "/biblioteca/devolver":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) {
            header("Location: /login");
            exit;
        }
        $id = (int) ($_POST['id'] ?? 0);
        (new \App\Controllers\BibliotecaController())->devolver($id);
        break;

    case "/login":
        if ($openTestAccess) {
            header("Location: /dashboard");
            exit;
        }

        $erroLogin = null;
        if ($method === "POST") {
            $matricula = $_POST["matricula"] ?? $_POST["cim"] ?? "";
            $password = $_POST["password"] ?? $_POST["senha"] ?? "";

            if (empty($matricula) || empty($password)) {
                $erroLogin = "Informe CIM e senha para acessar.";
            } else {
                $obreiroModel = new \App\Models\Obreiro();
                $usuario = $obreiroModel->autenticar($matricula, $password);

                if (!$usuario) {
                    $erroLogin = "Credenciais invalidas ou usuario inativo.";
                } else {
                    $cargo = $normalizeRole((string) ($usuario['cargo_principal'] ?? $usuario['cargo'] ?? ''));
                    $cargosAtivos = array_values(array_unique(array_filter(array_map(
                        $normalizeRole,
                        $usuario['cargos'] ?? [$cargo]
                    ))));
                    $temAcessoPainel =
                        count(array_intersect($cargosAtivos, ["veneravel", "tesoureiro", "chanceler", "admin", "bibliotecario", "mestre_banquetes"])) > 0
                        || in_array($cargo, ["veneravel", "secretario", "tesoureiro", "chanceler", "admin"], true);

                    if ($temAcessoPainel) {
                        $_SESSION["usuario_logado"] = $usuario;
                        $_SESSION["usuario_id"] = $usuario["id"];
                        $_SESSION["usuario_nome"] = $usuario["nome_historico"] ?? $usuario["nome_completo"] ?? "Irmao";
                        $syncSessionRoles($usuario);
                        header("Location: /dashboard");
                        exit;
                    }

                    $erroLogin = "Seu perfil nao possui permissao para acessar o painel.";
                }
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
        echo "404 - Pagina nao encontrada.";
        break;
}
