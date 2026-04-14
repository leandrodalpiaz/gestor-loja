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

$mergeHistoricosFixos = static function (array $registros, array $filtros): array {
    $normalizarBusca = static function (string $texto): string {
        $texto = trim($texto);
        if ($texto === '') {
            return '';
        }

        $texto = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto) ?: $texto;
        $texto = strtolower($texto);

        return $texto;
    };

    $tipo = trim((string) ($filtros['tipo'] ?? ''));
    $termo = $normalizarBusca((string) ($filtros['termo'] ?? ''));
    $ativo = strtolower(trim((string) ($filtros['ativo'] ?? '1')));
    $dataIni = trim((string) ($filtros['data_ini'] ?? ''));
    $dataFim = trim((string) ($filtros['data_fim'] ?? ''));
    $vinculo = trim((string) ($filtros['vinculo'] ?? ''));
    $irmaoRef = trim((string) ($filtros['irmao_ref'] ?? ''));

    if ($tipo !== '' && $tipo !== 'História') {
        return $registros;
    }

    if ($ativo === '0' || $ativo === 'false' || $ativo === 'inativos') {
        return $registros;
    }

    if ($vinculo !== '' || $irmaoRef !== '') {
        return $registros;
    }

    $historicosFixos = \App\Services\HistoricoEventos::getFixosComoRegistros();
    $historicosFixos = array_values(array_filter($historicosFixos, static function (array $item) use ($termo, $dataIni, $dataFim, $normalizarBusca): bool {
        if ($termo !== '') {
            $alvo = $normalizarBusca(
                trim((string) ($item['nome'] ?? '')) . ' ' . trim((string) ($item['mensagem_custom'] ?? ''))
            );

            if (strpos($alvo, $termo) === false) {
                return false;
            }
        }

        $dataEvento = trim((string) ($item['data_evento'] ?? ''));
        if ($dataIni !== '' && $dataEvento < $dataIni) {
            return false;
        }

        if ($dataFim !== '' && $dataEvento > $dataFim) {
            return false;
        }

        return true;
    }));

    $registros = array_merge($registros, $historicosFixos);

    usort($registros, static function (array $a, array $b): int {
        $dataA = (string) ($a['data_evento'] ?? '');
        $dataB = (string) ($b['data_evento'] ?? '');
        $nomeA = (string) ($a['nome'] ?? '');
        $nomeB = (string) ($b['nome'] ?? '');
        $idA = (int) ($a['id'] ?? 0);
        $idB = (int) ($b['id'] ?? 0);

        $ordemData = strcmp(substr($dataA, 5, 5), substr($dataB, 5, 5));
        if ($ordemData !== 0) {
            return $ordemData;
        }

        $ordemNome = strcmp($nomeA, $nomeB);
        if ($ordemNome !== 0) {
            return $ordemNome;
        }

        return $idA <=> $idB;
    });

    return $registros;
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

$resolvePublicUserName = static function (array $usuario = []) use ($testDisplayName): string {
    $nomeHistorico = trim((string) ($usuario['nome_historico'] ?? ''));
    $nomeCompleto = trim((string) ($usuario['nome_completo'] ?? ''));
    $nomeHistoricoNormalizado = strtolower($nomeHistorico);

    if ($nomeHistorico !== '' && !in_array($nomeHistoricoNormalizado, ['admin', 'administrador'], true)) {
        return $nomeHistorico;
    }

    if ($nomeCompleto !== '' && stripos($nomeCompleto, 'acesso temporario') === false) {
        return $nomeCompleto;
    }

    if ($nomeHistorico !== '') {
        return $nomeHistorico;
    }

    return $testDisplayName !== '' ? $testDisplayName : 'Irmao';
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

$loginTelegramObreiroInSession = static function (array $obreiro) use ($syncSessionRoles, $normalizeRole, $resolvePublicUserName): void {
    $principal = $normalizeRole((string) ($obreiro['cargo_principal'] ?? $obreiro['cargo'] ?? ''));
    $cargos = array_values(array_unique(array_filter(array_map(
        $normalizeRole,
        $obreiro['cargos'] ?? [$principal]
    ))));

    $usuario = $obreiro;
    $usuario['cargo'] = $principal;
    $usuario['cargos'] = $cargos;

    $_SESSION['usuario_logado'] = $usuario;
    $_SESSION['usuario_id'] = $usuario['id'] ?? null;
    $_SESSION['usuario_nome'] = $resolvePublicUserName($usuario);

    $syncSessionRoles($usuario);
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
    $_SESSION["usuario_nome"] = $resolvePublicUserName($_SESSION["usuario_logado"]);
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
    $sessionAutorizada = isset($_SESSION['usuario_logado']) && $sessionHasRole('chanceler', 'veneravel', 'admin');
    $telegramObreiro = $sessionAutorizada ? null : $resolveAuthorizedTelegramObreiro('chanceler', 'veneravel', 'admin');
    if (!$sessionAutorizada && !$telegramObreiro) {
        http_response_code(403);
        echo "<div style='padding: 20px; color: red; font-family: sans-serif;'>Acesso restrito ao Chanceler, Veneravel Mestre ou Administrador.</div>";
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

        $telegramObreiro = $resolveAuthorizedTelegramObreiro('chanceler', 'veneravel', 'admin');
        if (!$telegramObreiro) {
            http_response_code(403);
            echo "Acesso restrito ao Chanceler, Veneravel Mestre ou Administrador.";
            exit;
        }
    } elseif (!$sessionHasRole('chanceler', 'veneravel', 'admin')) {
        http_response_code(403);
        echo "Acesso restrito ao Chanceler, Veneravel Mestre ou Administrador.";
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
        if (!$sessionHasRole('admin', 'secretario', 'veneravel')) {
            http_response_code(403);
            echo "Acesso restrito ao Administrador, Secretario ou Veneravel Mestre.";
            exit;
        }
        (new \App\Controllers\AdminController())->listarCargos();
        break;

    case "/admin/cargos/salvar":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) {
            header("Location: /login");
            exit;
        }
        if (!$sessionHasRole('admin', 'secretario', 'veneravel')) {
            http_response_code(403);
            echo "Acesso restrito ao Administrador, Secretario ou Veneravel Mestre.";
            exit;
        }
        (new \App\Controllers\AdminController())->salvarCargo();
        break;

    case "/admin/cargos/gestao/salvar":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) {
            header("Location: /login");
            exit;
        }
        if (!$sessionHasRole('admin', 'secretario', 'veneravel')) {
            http_response_code(403);
            echo "Acesso restrito ao Administrador, Secretario ou Veneravel Mestre.";
            exit;
        }
        (new \App\Controllers\AdminController())->salvarGestao();
        break;

    case "/admin/cargos/gestao/encerrar":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) {
            header("Location: /login");
            exit;
        }
        if (!$sessionHasRole('admin', 'secretario', 'veneravel')) {
            http_response_code(403);
            echo "Acesso restrito ao Administrador, Secretario ou Veneravel Mestre.";
            exit;
        }
        (new \App\Controllers\AdminController())->encerrarGestao();
        break;

    case "/admin/loja":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) {
            header("Location: /login");
            exit;
        }
        if (!$sessionHasRole('admin')) {
            http_response_code(403);
            echo "Acesso restrito ao Administrador.";
            exit;
        }
        (new \App\Controllers\AdminController())->configuracoesLoja();
        break;

    case "/admin/auditoria":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) {
            header("Location: /login");
            exit;
        }
        if (!$sessionHasRole('admin', 'veneravel')) {
            http_response_code(403);
            echo "Acesso restrito ao Administrador ou Veneravel Mestre.";
            exit;
        }
        (new \App\Controllers\AdminController())->auditoriaCritica();
        break;

    case "/admin/loja/salvar":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) {
            header("Location: /login");
            exit;
        }
        if (!$sessionHasRole('admin')) {
            http_response_code(403);
            echo "Acesso restrito ao Administrador.";
            exit;
        }
        (new \App\Controllers\AdminController())->salvarConfiguracoesLoja();
        break;

    case "/miniapp/admin":
        requireMiniappAuth(['admin', 'veneravel']);
        require_once __DIR__ . "/../src/Views/miniapp/admin.php";
        break;

    // Telas antigas restauradas
    case "/obreiros":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) { header("Location: /login"); exit; }
        if (!$sessionHasRole('secretario', 'primeiro_vigilante', 'segundo_vigilante', 'chanceler', 'veneravel', 'admin')) {
            http_response_code(403);
            echo "Acesso restrito a Secretaria, 1o Vigilante, 2o Vigilante, Chancelaria, Veneravel Mestre ou Administrador.";
            exit;
        }
        $obreiroModel = new \App\Models\Obreiro();
        $filtrosObreiros = [
            'busca' => trim((string) ($_GET['busca'] ?? '')),
            'situacao' => trim((string) ($_GET['situacao'] ?? '')),
            'grau' => trim((string) ($_GET['grau'] ?? '')),
            'alerta' => trim((string) ($_GET['alerta'] ?? $_GET['pendencia'] ?? '')),
            'cargo_codigo' => trim((string) ($_GET['cargo_codigo'] ?? '')),
            'ordenacao' => trim((string) ($_GET['ordenacao'] ?? 'nome')),
        ];
        $obreiros = $obreiroModel->listarParaSecretaria($filtrosObreiros);
        $resumoObreiros = $obreiroModel->obterResumoSecretaria($filtrosObreiros);
        $cargosFiltros = (new \App\Models\Cargo())->listarResumoCargos();
        require_once __DIR__ . "/../src/Views/obreiros.php";
        break;

    case "/primeiro-vigilante":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) { header("Location: /login"); exit; }
        if (!$sessionHasRole('primeiro_vigilante', 'veneravel', 'admin')) {
            http_response_code(403);
            echo "Acesso restrito ao 1o Vigilante, Veneravel Mestre ou Administrador.";
            exit;
        }
        (new \App\Controllers\PrimeiroVigilanteController())->index();
        break;

    case "/primeiro-vigilante/aprendiz":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) { header("Location: /login"); exit; }
        $aprendizId = trim((string) ($_GET['id'] ?? ''));
        $usuarioId = trim((string) ($_SESSION['usuario_id'] ?? ''));
        $podeVerQualquerAprendiz = $sessionHasRole('primeiro_vigilante', 'veneravel', 'admin');
        $podeVerProprio = $usuarioId !== '' && $usuarioId === $aprendizId;
        if (!$podeVerQualquerAprendiz && !$podeVerProprio) {
            http_response_code(403);
            echo "Acesso restrito ao 1o Vigilante, Veneravel Mestre, Administrador ou ao proprio Aprendiz.";
            exit;
        }
        (new \App\Controllers\PrimeiroVigilanteController())->aprendiz($aprendizId, !$podeVerQualquerAprendiz && $podeVerProprio);
        break;

    case "/primeiro-vigilante/trilha/atualizar":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) { header("Location: /login"); exit; }
        if (!$sessionHasRole('primeiro_vigilante', 'veneravel', 'admin')) {
            http_response_code(403);
            echo "Acesso restrito ao 1o Vigilante, Veneravel Mestre ou Administrador.";
            exit;
        }
        (new \App\Controllers\PrimeiroVigilanteController())->atualizarEtapa();
        break;

    case "/primeiro-vigilante/trilha/acao-rapida":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) { header("Location: /login"); exit; }
        if (!$sessionHasRole('primeiro_vigilante', 'veneravel', 'admin')) {
            http_response_code(403);
            echo "Acesso restrito ao 1o Vigilante, Veneravel Mestre ou Administrador.";
            exit;
        }
        (new \App\Controllers\PrimeiroVigilanteController())->acaoRapidaEtapa();
        break;

    case "/primeiro-vigilante/leitura/salvar":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) { header("Location: /login"); exit; }
        if (!$sessionHasRole('primeiro_vigilante', 'veneravel', 'admin')) {
            http_response_code(403);
            echo "Acesso restrito ao 1o Vigilante, Veneravel Mestre ou Administrador.";
            exit;
        }
        (new \App\Controllers\PrimeiroVigilanteController())->salvarLeituraSugerida();
        break;

    case "/primeiro-vigilante/certificado/solicitar":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) { header("Location: /login"); exit; }
        if (!$sessionHasRole('primeiro_vigilante', 'veneravel', 'admin')) {
            http_response_code(403);
            echo "Acesso restrito ao 1o Vigilante, Veneravel Mestre ou Administrador.";
            exit;
        }
        (new \App\Controllers\PrimeiroVigilanteController())->solicitarCertificado();
        break;

    case "/segundo-vigilante":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) { header("Location: /login"); exit; }
        if (!$sessionHasRole('segundo_vigilante', 'veneravel', 'admin')) {
            http_response_code(403);
            echo "Acesso restrito ao 2o Vigilante, Veneravel Mestre ou Administrador.";
            exit;
        }
        (new \App\Controllers\SegundoVigilanteController())->index();
        break;

    case "/segundo-vigilante/companheiro":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) { header("Location: /login"); exit; }
        $companheiroId = trim((string) ($_GET['id'] ?? ''));
        $usuarioId = trim((string) ($_SESSION['usuario_id'] ?? ''));
        $podeVerQualquerCompanheiro = $sessionHasRole('segundo_vigilante', 'veneravel', 'admin');
        $podeVerProprio = $usuarioId !== '' && $usuarioId === $companheiroId;
        if (!$podeVerQualquerCompanheiro && !$podeVerProprio) {
            http_response_code(403);
            echo "Acesso restrito ao 2o Vigilante, Veneravel Mestre, Administrador ou ao proprio Companheiro.";
            exit;
        }
        (new \App\Controllers\SegundoVigilanteController())->companheiro($companheiroId, !$podeVerQualquerCompanheiro && $podeVerProprio);
        break;

    case "/segundo-vigilante/trilha/atualizar":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) { header("Location: /login"); exit; }
        if (!$sessionHasRole('segundo_vigilante', 'veneravel', 'admin')) {
            http_response_code(403);
            echo "Acesso restrito ao 2o Vigilante, Veneravel Mestre ou Administrador.";
            exit;
        }
        (new \App\Controllers\SegundoVigilanteController())->atualizarEtapa();
        break;

    case "/segundo-vigilante/trilha/acao-rapida":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) { header("Location: /login"); exit; }
        if (!$sessionHasRole('segundo_vigilante', 'veneravel', 'admin')) {
            http_response_code(403);
            echo "Acesso restrito ao 2o Vigilante, Veneravel Mestre ou Administrador.";
            exit;
        }
        (new \App\Controllers\SegundoVigilanteController())->acaoRapidaEtapa();
        break;

    case "/segundo-vigilante/leitura/salvar":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) { header("Location: /login"); exit; }
        if (!$sessionHasRole('segundo_vigilante', 'veneravel', 'admin')) {
            http_response_code(403);
            echo "Acesso restrito ao 2o Vigilante, Veneravel Mestre ou Administrador.";
            exit;
        }
        (new \App\Controllers\SegundoVigilanteController())->salvarLeituraSugerida();
        break;

    case "/segundo-vigilante/certificado/solicitar":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) { header("Location: /login"); exit; }
        if (!$sessionHasRole('segundo_vigilante', 'veneravel', 'admin')) {
            http_response_code(403);
            echo "Acesso restrito ao 2o Vigilante, Veneravel Mestre ou Administrador.";
            exit;
        }
        (new \App\Controllers\SegundoVigilanteController())->solicitarCertificado();
        break;

    case "/segundo-vigilante/exaltacao/recomendar":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) { header("Location: /login"); exit; }
        if (!$sessionHasRole('segundo_vigilante', 'veneravel', 'admin')) {
            http_response_code(403);
            echo "Acesso restrito ao 2o Vigilante, Veneravel Mestre ou Administrador.";
            exit;
        }
        (new \App\Controllers\SegundoVigilanteController())->recomendarExaltacao();
        break;

    case "/meu-aprendizado":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) { header("Location: /login"); exit; }
        $usuarioId = trim((string) ($_SESSION['usuario_id'] ?? ''));
        if ($usuarioId === '') {
            http_response_code(403);
            echo "Nao foi possivel identificar o Aprendiz logado.";
            exit;
        }
        (new \App\Controllers\PrimeiroVigilanteController())->aprendiz($usuarioId, true);
        break;

    case "/meu-companheirismo":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) { header("Location: /login"); exit; }
        $usuarioId = trim((string) ($_SESSION['usuario_id'] ?? ''));
        if ($usuarioId === '') {
            http_response_code(403);
            echo "Nao foi possivel identificar o Companheiro logado.";
            exit;
        }
        (new \App\Controllers\SegundoVigilanteController())->companheiro($usuarioId, true);
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
            echo "Acesso restrito ao Secretario, Veneravel Mestre ou Administrador.";
            exit;
        }
        (new \App\Controllers\SecretariaController())->index();
        break;

    case "/secretaria/votacao":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) { header("Location: /login"); exit; }
        (new \App\Controllers\SecretariaController())->votacao();
        break;

    case "/secretaria/relatorio-anual":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) { header("Location: /login"); exit; }
        if (!$sessionHasRole('secretario', 'veneravel', 'admin')) {
            http_response_code(403);
            echo "Acesso restrito ao Secretario, Veneravel Mestre ou Administrador.";
            exit;
        }
        (new \App\Controllers\SecretariaController())->relatorioAnual();
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

    case "/secretaria/sessoes/publicar-rascunho":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) { header("Location: /login"); exit; }
        if (!$sessionHasRole('secretario', 'admin')) {
            http_response_code(403);
            echo "Acesso restrito ao Secretario ou Administrador.";
            exit;
        }
        (new \App\Controllers\SecretariaController())->publicarSessaoRascunho();
        break;

    case "/secretaria/sessoes/publicar":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) { header("Location: /login"); exit; }
        if (!$sessionHasRole('secretario', 'admin')) {
            http_response_code(403);
            echo "Acesso restrito ao Secretario ou Administrador.";
            exit;
        }
        (new \App\Controllers\SecretariaController())->publicarSessao();
        break;

    case "/secretaria/sessoes/cancelar-rascunho":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) { header("Location: /login"); exit; }
        if (!$sessionHasRole('secretario', 'admin')) {
            http_response_code(403);
            echo "Acesso restrito ao Secretario ou Administrador.";
            exit;
        }
        (new \App\Controllers\SecretariaController())->cancelarRascunhoSessao();
        break;

    case "/secretaria/sessoes/cancelar":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) { header("Location: /login"); exit; }
        if (!$sessionHasRole('secretario', 'admin')) {
            http_response_code(403);
            echo "Acesso restrito ao Secretario ou Administrador.";
            exit;
        }
        (new \App\Controllers\SecretariaController())->cancelarSessao();
        break;

    case "/secretaria/sessoes/reabrir":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) { header("Location: /login"); exit; }
        if (!$sessionHasRole('secretario', 'admin')) {
            http_response_code(403);
            echo "Acesso restrito ao Secretario ou Administrador.";
            exit;
        }
        (new \App\Controllers\SecretariaController())->reabrirSessao();
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

    case "/assistencia":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) { header("Location: /login"); exit; }
        if (!$sessionHasRole('hospitaleiro', 'secretario', 'tesoureiro', 'veneravel', 'admin')) {
            http_response_code(403);
            echo "Acesso restrito ao Mestre Hospitaleiro, Secretario, Tesoureiro, Veneravel Mestre ou Administrador.";
            exit;
        }
        (new \App\Controllers\HospitaleiroController())->index();
        break;

    case "/assistencia/ocorrencias/salvar":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) { header("Location: /login"); exit; }
        if (!$sessionHasRole('hospitaleiro', 'secretario', 'veneravel', 'admin')) {
            http_response_code(403);
            echo "Acesso restrito ao Mestre Hospitaleiro, Secretario, Veneravel Mestre ou Administrador.";
            exit;
        }
        (new \App\Controllers\HospitaleiroController())->salvarOcorrencia();
        break;

    case "/assistencia/ocorrencias/status":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) { header("Location: /login"); exit; }
        if (!$sessionHasRole('hospitaleiro', 'secretario', 'tesoureiro', 'veneravel', 'admin')) {
            http_response_code(403);
            echo "Acesso restrito ao Mestre Hospitaleiro, Secretario, Tesoureiro, Veneravel Mestre ou Administrador.";
            exit;
        }
        (new \App\Controllers\HospitaleiroController())->atualizarStatusOcorrencia();
        break;

    case "/assistencia/ocorrencias/visita":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) { header("Location: /login"); exit; }
        if (!$sessionHasRole('hospitaleiro', 'secretario', 'veneravel', 'admin')) {
            http_response_code(403);
            echo "Acesso restrito ao Mestre Hospitaleiro, Secretario, Veneravel Mestre ou Administrador.";
            exit;
        }
        (new \App\Controllers\HospitaleiroController())->registrarVisita();
        break;

    case "/mestre-harmonia":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) { header("Location: /login"); exit; }
        if (!$sessionHasRole('mestre_harmonia', 'veneravel', 'admin')) {
            http_response_code(403);
            echo "Acesso restrito ao Mestre de Harmonia, Veneravel Mestre ou Administrador.";
            exit;
        }
        (new \App\Controllers\MestreHarmoniaController())->index();
        break;

    case "/miniapp/mestre-harmonia":
        requireMiniappAuth(['mestre_harmonia', 'veneravel', 'admin']);
        require_once __DIR__ . "/../src/Views/miniapp/mestre_harmonia.php";
        break;

    case "/miniapp/tesouraria":
        requireMiniappAuth(['tesoureiro', 'veneravel', 'admin']);
        require_once __DIR__ . "/../src/Views/miniapp/tesouraria.php";
        break;

    case "/tesouraria/caixa":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) {
            $telegramObreiro = $resolveAuthorizedTelegramObreiro('tesoureiro', 'veneravel', 'admin');
            if (!$telegramObreiro) { header("Location: /login"); exit; }
            $loginTelegramObreiroInSession($telegramObreiro);
        }
        if (!$sessionHasRole('tesoureiro', 'veneravel', 'admin')) {
            http_response_code(403);
            echo "Acesso restrito ao Tesoureiro, Veneravel Mestre ou Administrador.";
            exit;
        }
        require_once __DIR__ . "/../src/Views/tesouraria_caixa.php";
        break;

    case "/tesouraria/sessoes":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) {
            $telegramObreiro = $resolveAuthorizedTelegramObreiro('tesoureiro', 'veneravel', 'admin');
            if (!$telegramObreiro) { header("Location: /login"); exit; }
            $loginTelegramObreiroInSession($telegramObreiro);
        }
        if (!$sessionHasRole('tesoureiro', 'veneravel', 'admin')) {
            http_response_code(403);
            echo "Acesso restrito ao Tesoureiro, Veneravel Mestre ou Administrador.";
            exit;
        }
        (new \App\Controllers\TesourariaSessaoController())->index();
        break;

    case "/tesouraria/comprovantes":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) {
            $telegramObreiro = $resolveAuthorizedTelegramObreiro('tesoureiro', 'veneravel', 'admin');
            if (!$telegramObreiro) { header("Location: /login"); exit; }
            $loginTelegramObreiroInSession($telegramObreiro);
        }
        if (!$sessionHasRole('tesoureiro', 'veneravel', 'admin')) {
            http_response_code(403);
            echo "Acesso restrito ao Tesoureiro, Veneravel Mestre ou Administrador.";
            exit;
        }
        $configuracaoLoja = (new \App\Models\ConfiguracaoLoja())->obter();
        $categoriasEntrada = (new \App\Models\CategoriaFinanceira())->obterPorTipo('entrada');
        require_once __DIR__ . "/../src/Views/tesouraria_comprovantes.php";
        break;

    case "/tesouraria/regularidade":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) {
            $telegramObreiro = $resolveAuthorizedTelegramObreiro('tesoureiro', 'veneravel', 'admin');
            if (!$telegramObreiro) { header("Location: /login"); exit; }
            $loginTelegramObreiroInSession($telegramObreiro);
        }
        if (!$sessionHasRole('tesoureiro', 'veneravel', 'admin')) {
            http_response_code(403);
            echo "Acesso restrito ao Tesoureiro, Veneravel Mestre ou Administrador.";
            exit;
        }
        require_once __DIR__ . "/../src/Views/tesouraria_regularidade.php";
        break;

    case "/tesouraria/fechamento":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) {
            $telegramObreiro = $resolveAuthorizedTelegramObreiro('tesoureiro', 'veneravel', 'admin');
            if (!$telegramObreiro) { header("Location: /login"); exit; }
            $loginTelegramObreiroInSession($telegramObreiro);
        }
        if (!$sessionHasRole('tesoureiro', 'veneravel', 'admin')) {
            http_response_code(403);
            echo "Acesso restrito ao Tesoureiro, Veneravel Mestre ou Administrador.";
            exit;
        }
        require_once __DIR__ . "/../src/Views/tesouraria_fechamento.php";
        break;

    case "/tesouraria/relatorio-gestao":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) {
            $telegramObreiro = $resolveAuthorizedTelegramObreiro('tesoureiro', 'veneravel', 'admin');
            if (!$telegramObreiro) { header("Location: /login"); exit; }
            $loginTelegramObreiroInSession($telegramObreiro);
        }
        if (!$sessionHasRole('tesoureiro', 'veneravel', 'admin')) {
            http_response_code(403);
            echo "Acesso restrito a Tesoureiro, Veneravel Mestre ou Administrador.";
            exit;
        }
        $gestaoModel = new \App\Models\Gestao();
        $gestoes = $gestaoModel->listar();
        $gestaoAtual = $gestaoModel->obterAberta();
        $gestaoIdSelecionada = (int) ($_GET['gestao_id'] ?? ($gestaoAtual['id'] ?? ($gestoes[0]['id'] ?? 0)));
        if ($gestaoIdSelecionada <= 0) {
            http_response_code(404);
            echo "Nenhuma gestao cadastrada para consolidar o relatorio financeiro.";
            exit;
        }
        $encerramentoInformado = trim((string) ($_GET['encerramento_em'] ?? ''));
        $relatorio = (new \App\Models\RelatorioTesourariaGestao())->montar($gestaoIdSelecionada, $encerramentoInformado !== '' ? $encerramentoInformado : null);
        require_once __DIR__ . "/../src/Views/tesouraria_relatorio_gestao.php";
        break;

    case "/tesouraria/obrigacoes":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) {
            $telegramObreiro = $resolveAuthorizedTelegramObreiro('tesoureiro', 'veneravel', 'admin');
            if (!$telegramObreiro) { header("Location: /login"); exit; }
            $loginTelegramObreiroInSession($telegramObreiro);
        }
        if (!$sessionHasRole('tesoureiro', 'veneravel', 'admin')) {
            http_response_code(403);
            echo "Acesso restrito ao Tesoureiro, Veneravel Mestre ou Administrador.";
            exit;
        }
        $obrigacaoModel = new \App\Models\ObrigacaoFinanceira();
        $categoriaModel = new \App\Models\CategoriaFinanceira();
        $configuracaoLoja = (new \App\Models\ConfiguracaoLoja())->obter();
        $obreirosPainel = $obrigacaoModel->listarResumoTesouraria([
            'busca' => trim((string) ($_GET['busca'] ?? '')),
            'somente_em_aberto' => !empty($_GET['somente_em_aberto']),
        ]);
        $obreirosCadastro = (new \App\Models\Obreiro())->getAllAtivos();
        $selectedObreiroId = trim((string) ($_GET['obreiro_id'] ?? ($obreirosPainel[0]['id'] ?? '')));
        $selectedObreiroNome = 'Selecione um obreiro';
        foreach ($obreirosCadastro as $obreiroCadastro) {
            if ((string) ($obreiroCadastro['id'] ?? '') === $selectedObreiroId) {
                $selectedObreiroNome = (string) ($obreiroCadastro['nome_historico'] ?? $obreiroCadastro['nome'] ?? 'Obreiro');
                break;
            }
        }
        $resumoObreiro = $selectedObreiroId !== '' ? $obrigacaoModel->obterResumoObreiro($selectedObreiroId) : [];
        $obrigacoesObreiro = $selectedObreiroId !== '' ? $obrigacaoModel->listarPorObreiro($selectedObreiroId) : [];
        $categoriasEntrada = $categoriaModel->obterPorTipo('entrada');
        require_once __DIR__ . "/../src/Views/tesouraria_obrigacoes.php";
        break;

    case "/tesouraria/obrigacoes/criar":
        if ($method !== 'POST') {
            http_response_code(405);
            exit;
        }
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) {
            $telegramObreiro = $resolveAuthorizedTelegramObreiro('tesoureiro', 'veneravel', 'admin');
            if (!$telegramObreiro) { header("Location: /login"); exit; }
            $loginTelegramObreiroInSession($telegramObreiro);
        }
        if (!$sessionHasRole('tesoureiro', 'veneravel', 'admin')) {
            http_response_code(403);
            echo "Acesso restrito ao Tesoureiro, Veneravel Mestre ou Administrador.";
            exit;
        }
        $ok = (new \App\Models\ObrigacaoFinanceira())->criar($_POST, $_SESSION['usuario_id'] ?? null);
        $destinoObreiro = trim((string) ($_POST['obreiro_id'] ?? ''));
        header("Location: /tesouraria/obrigacoes" . ($destinoObreiro !== '' ? '?obreiro_id=' . urlencode($destinoObreiro) : '') . ($destinoObreiro !== '' ? '&' : '?') . ($ok ? 'sucesso=1' : 'erro=1'));
        exit;

    case "/tesouraria/obrigacoes/parcela/quitar":
        if ($method !== 'POST') {
            http_response_code(405);
            exit;
        }
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) {
            $telegramObreiro = $resolveAuthorizedTelegramObreiro('tesoureiro', 'veneravel', 'admin');
            if (!$telegramObreiro) { header("Location: /login"); exit; }
            $loginTelegramObreiroInSession($telegramObreiro);
        }
        if (!$sessionHasRole('tesoureiro', 'veneravel', 'admin')) {
            http_response_code(403);
            echo "Acesso restrito ao Tesoureiro, Veneravel Mestre ou Administrador.";
            exit;
        }
        $parcelaId = (int) ($_POST['parcela_id'] ?? 0);
        $obreiroIdRetorno = trim((string) ($_POST['obreiro_id'] ?? ''));
        $ok = $parcelaId > 0 ? (new \App\Models\ObrigacaoFinanceira())->quitarParcela($parcelaId, $_POST, $_SESSION['usuario_id'] ?? null) : false;
        header("Location: /tesouraria/obrigacoes" . ($obreiroIdRetorno !== '' ? '?obreiro_id=' . urlencode($obreiroIdRetorno) : '') . ($obreiroIdRetorno !== '' ? '&' : '?') . ($ok ? 'sucesso=1' : 'erro=1'));
        exit;

    case "/tesouraria/obrigacoes/parcela/atualizar":
        if ($method !== 'POST') { http_response_code(405); exit; }
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) {
            $telegramObreiro = $resolveAuthorizedTelegramObreiro('tesoureiro', 'veneravel', 'admin');
            if (!$telegramObreiro) { header("Location: /login"); exit; }
            $loginTelegramObreiroInSession($telegramObreiro);
        }
        if (!$sessionHasRole('tesoureiro', 'veneravel', 'admin')) {
            http_response_code(403);
            echo "Acesso restrito ao Tesoureiro, Veneravel Mestre ou Administrador.";
            exit;
        }
        $parcelaId = (int) ($_POST['parcela_id'] ?? 0);
        $obreiroIdRetorno = trim((string) ($_POST['obreiro_id'] ?? ''));
        $ok = $parcelaId > 0 ? (new \App\Models\ObrigacaoFinanceira())->atualizarParcela($parcelaId, $_POST) : false;
        header("Location: /tesouraria/obrigacoes" . ($obreiroIdRetorno !== '' ? '?obreiro_id=' . urlencode($obreiroIdRetorno) : '') . ($obreiroIdRetorno !== '' ? '&' : '?') . ($ok ? 'sucesso=1' : 'erro=1'));
        exit;

    case "/tesouraria/obrigacoes/parcela/excluir":
        if ($method !== 'POST') { http_response_code(405); exit; }
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) {
            $telegramObreiro = $resolveAuthorizedTelegramObreiro('tesoureiro', 'veneravel', 'admin');
            if (!$telegramObreiro) { header("Location: /login"); exit; }
            $loginTelegramObreiroInSession($telegramObreiro);
        }
        if (!$sessionHasRole('tesoureiro', 'veneravel', 'admin')) {
            http_response_code(403);
            echo "Acesso restrito ao Tesoureiro, Veneravel Mestre ou Administrador.";
            exit;
        }
        $parcelaId = (int) ($_POST['parcela_id'] ?? 0);
        $obreiroIdRetorno = trim((string) ($_POST['obreiro_id'] ?? ''));
        $ok = $parcelaId > 0 ? (new \App\Models\ObrigacaoFinanceira())->excluirParcela($parcelaId) : false;
        header("Location: /tesouraria/obrigacoes" . ($obreiroIdRetorno !== '' ? '?obreiro_id=' . urlencode($obreiroIdRetorno) : '') . ($obreiroIdRetorno !== '' ? '&' : '?') . ($ok ? 'sucesso=1' : 'erro=1'));
        exit;

    case "/tesouraria/obrigacoes/parcela/recibo":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) {
            $telegramObreiro = $resolveAuthorizedTelegramObreiro('tesoureiro', 'veneravel', 'admin');
            if (!$telegramObreiro) { header("Location: /login"); exit; }
            $loginTelegramObreiroInSession($telegramObreiro);
        }
        if (!$sessionHasRole('tesoureiro', 'veneravel', 'admin')) {
            http_response_code(403);
            echo "Acesso restrito ao Tesoureiro, Veneravel Mestre ou Administrador.";
            exit;
        }
        $parcelaId = (int) ($_GET['id'] ?? 0);
        $parcelaRecibo = $parcelaId > 0 ? (new \App\Models\ObrigacaoFinanceira())->obterParcelaPorId($parcelaId) : null;
        if (!$parcelaRecibo || (string) ($parcelaRecibo['status'] ?? '') !== 'pago') {
            http_response_code(404);
            echo "Recibo indisponivel para esta parcela.";
            exit;
        }
        $configuracaoLoja = (new \App\Models\ConfiguracaoLoja())->obter();
        $tesoureiroNome = (string) ($_SESSION['usuario_nome'] ?? ($_SESSION['usuario_logado']['nome_historico'] ?? 'Tesoureiro'));
        require_once __DIR__ . "/../src/Views/tesouraria_recibo.php";
        exit;

    case "/tesouraria/obrigacoes/mensalidades/gerar":
        if ($method !== 'POST') { http_response_code(405); exit; }
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) {
            $telegramObreiro = $resolveAuthorizedTelegramObreiro('tesoureiro', 'veneravel', 'admin');
            if (!$telegramObreiro) { header("Location: /login"); exit; }
            $loginTelegramObreiroInSession($telegramObreiro);
        }
        if (!$sessionHasRole('tesoureiro', 'veneravel', 'admin')) {
            http_response_code(403);
            echo "Acesso restrito ao Tesoureiro, Veneravel Mestre ou Administrador.";
            exit;
        }
        $anoGeracao = max(2020, (int) ($_POST['ano_ref'] ?? date('Y')));
        $resultadoGeracao = (new \App\Models\ObrigacaoFinanceira())->gerarMensalidadesAno($anoGeracao, $_SESSION['usuario_id'] ?? null);
        $_SESSION['mensagem_sucesso'] = sprintf('Mensalidades %d: %d geradas, %d ignoradas e %d isentas.', $anoGeracao, $resultadoGeracao['geradas'], $resultadoGeracao['ignoradas'], $resultadoGeracao['isentas']);
        header("Location: /tesouraria/obrigacoes");
        exit;

    case "/tesouraria/obrigacoes/biblioteca/designar":
        if ($method !== 'POST') { http_response_code(405); exit; }
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) {
            $telegramObreiro = $resolveAuthorizedTelegramObreiro('tesoureiro', 'veneravel', 'admin');
            if (!$telegramObreiro) { header("Location: /login"); exit; }
            $loginTelegramObreiroInSession($telegramObreiro);
        }
        if (!$sessionHasRole('tesoureiro', 'veneravel', 'admin')) {
            http_response_code(403);
            echo "Acesso restrito ao Tesoureiro, Veneravel Mestre ou Administrador.";
            exit;
        }
        $obreirosBiblioteca = array_values(array_filter((array) ($_POST['obreiros_biblioteca'] ?? [])));
        $resultadoBiblioteca = (new \App\Models\ObrigacaoFinanceira())->designarBibliotecaMes(
            max(1, min(12, (int) ($_POST['mes_ref'] ?? date('n')))),
            max(2020, (int) ($_POST['ano_ref'] ?? date('Y'))),
            $obreirosBiblioteca,
            trim((string) ($_POST['observacao'] ?? '')),
            $_SESSION['usuario_id'] ?? null
        );
        $_SESSION['mensagem_sucesso'] = sprintf('Biblioteca: %d geradas, %d ignoradas e %d isentas.', $resultadoBiblioteca['geradas'], $resultadoBiblioteca['ignoradas'], $resultadoBiblioteca['isentas']);
        header("Location: /tesouraria/obrigacoes");
        exit;

    case "/tesouraria/obrigacoes/isencao/criar":
        if ($method !== 'POST') { http_response_code(405); exit; }
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) {
            $telegramObreiro = $resolveAuthorizedTelegramObreiro('tesoureiro', 'veneravel', 'admin');
            if (!$telegramObreiro) { header("Location: /login"); exit; }
            $loginTelegramObreiroInSession($telegramObreiro);
        }
        if (!$sessionHasRole('tesoureiro', 'veneravel', 'admin')) {
            http_response_code(403);
            echo "Acesso restrito ao Tesoureiro, Veneravel Mestre ou Administrador.";
            exit;
        }
        $ok = (new \App\Models\ObrigacaoFinanceira())->registrarIsencao($_POST, $_SESSION['usuario_id'] ?? null);
        $obreiroIdRetorno = trim((string) ($_POST['obreiro_id'] ?? ''));
        header("Location: /tesouraria/obrigacoes" . ($obreiroIdRetorno !== '' ? '?obreiro_id=' . urlencode($obreiroIdRetorno) : '') . ($obreiroIdRetorno !== '' ? '&' : '?') . ($ok ? 'sucesso=1' : 'erro=1'));
        exit;

    case "/financeiro/minhas-obrigacoes":
        $obreiroFinanceiro = $_SESSION['usuario_logado'] ?? null;
        if (!$openTestAccess && !$obreiroFinanceiro) {
            $initData = trim((string) ($_GET['init_data'] ?? ''));
            if ($initData !== '') {
                $obreiroFinanceiro = $resolveObreiroByInitData($initData);
                if ($obreiroFinanceiro) {
                    $loginTelegramObreiroInSession($obreiroFinanceiro);
                }
            }
        }
        if (!$obreiroFinanceiro) {
            header("Location: /login");
            exit;
        }
        $obreiroFinanceiroId = trim((string) ($obreiroFinanceiro['id'] ?? $_SESSION['usuario_id'] ?? ''));
        if ($obreiroFinanceiroId === '' || $obreiroFinanceiroId === '0') {
            http_response_code(403);
            echo "Nao foi possivel identificar o obreiro para consultar suas obrigacoes.";
            exit;
        }
        $obrigacaoModel = new \App\Models\ObrigacaoFinanceira();
        $resumoObreiro = $obrigacaoModel->obterResumoObreiro($obreiroFinanceiroId);
        $obrigacoesObreiro = $obrigacaoModel->listarPorObreiro($obreiroFinanceiroId);
        require_once __DIR__ . "/../src/Views/minhas_obrigacoes.php";
        break;

    case "/biblioteca/classificar":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) {
            header("Location: /login");
            exit;
        }
        if (!$sessionHasRole('primeiro_vigilante', 'segundo_vigilante', 'bibliotecario', 'admin', 'veneravel')) {
            http_response_code(403);
            echo "Acesso restrito para classificar leitura sugerida.";
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
        $dashboardMensagemSucesso = $_SESSION['mensagem_sucesso'] ?? null;
        $dashboardMensagemErro = $_SESSION['mensagem_erro'] ?? null;
        unset($_SESSION['mensagem_sucesso'], $_SESSION['mensagem_erro']);

        $dashboardConfiguracaoLoja = (new \App\Models\ConfiguracaoLoja())->obter();
        $dashboardLogoUrl = null;
        foreach ([
            '/assets/logo-renascenca.svg',
            '/assets/logo-renascenca.png',
            '/assets/logo-loja-renascenca.svg',
            '/assets/logo-loja-renascenca.png',
            '/assets/renascenca-logo.svg',
            '/assets/renascenca-logo.png',
        ] as $logoPath) {
            if (file_exists(__DIR__ . $logoPath)) {
                $dashboardLogoUrl = $logoPath;
                break;
            }
        }

        $dashboardUsuarioId = trim((string) ($_SESSION['usuario_id'] ?? ''));
        $dashboardObreiro = null;
        if ($dashboardUsuarioId !== '' && $dashboardUsuarioId !== '0') {
            try {
                $dashboardObreiro = (new \App\Models\Obreiro())->findById($dashboardUsuarioId);
            } catch (\Throwable $e) {
                error_log('Falha ao localizar obreiro do dashboard: ' . $e->getMessage());
            }
        }

        if ($method === 'POST' && ($_POST['dashboard_action'] ?? '') === 'sessao_confirmacao') {
            $sessaoId = (int) ($_POST['sessao_id'] ?? 0);
            $acao = trim((string) ($_POST['acao'] ?? ''));

            if ($sessaoId <= 0) {
                $_SESSION['mensagem_erro'] = 'Sessao invalida para atualizar a confirmacao.';
            } elseif (!$dashboardObreiro || $dashboardUsuarioId === '' || $dashboardUsuarioId === '0') {
                $_SESSION['mensagem_erro'] = 'A confirmacao direta no dashboard requer um obreiro real autenticado.';
            } else {
                try {
                    $presencaModel = new \App\Models\Presenca();
                    $ok = $acao === 'cancelar'
                        ? $presencaModel->cancelar($sessaoId, $dashboardUsuarioId)
                        : $presencaModel->registrar($sessaoId, $dashboardUsuarioId, 'confirmado', false);

                    if ($ok) {
                        $_SESSION['mensagem_sucesso'] = $acao === 'cancelar'
                            ? 'Confirmacao cancelada com sucesso.'
                            : 'Presenca confirmada com sucesso.';
                    } else {
                        $_SESSION['mensagem_erro'] = 'Nao foi possivel atualizar a confirmacao desta sessao.';
                    }
                } catch (\Throwable $e) {
                    $_SESSION['mensagem_erro'] = 'Falha ao atualizar a confirmacao da sessao.';
                    error_log('Falha no POST do dashboard: ' . $e->getMessage());
                }
            }

            header('Location: /dashboard#sessoes-loja');
            exit;
        }

        $dashboardSessoes = [];
        $dashboardOutrasLojas = [];
        try {
            $sessaoModel = new \App\Models\Sessao();
            $presencaModel = new \App\Models\Presenca();
            $sessoesFuturas = $sessaoModel->listarFuturas(4);

            foreach ($sessoesFuturas as $sessao) {
                $sessaoId = (int) ($sessao['id'] ?? 0);
                if ($sessaoId <= 0) {
                    continue;
                }

                $respostaUsuario = $dashboardUsuarioId !== '' && $dashboardUsuarioId !== '0'
                    ? $presencaModel->obterResposta($sessaoId, $dashboardUsuarioId)
                    : null;

                $rotaDetalheSessao = '/dashboard#sessoes-loja';
                if ($sessionHasRole('chanceler', 'veneravel', 'admin')) {
                    $rotaDetalheSessao = '/chanceler/sessao?sessao_id=' . urlencode((string) $sessaoId);
                } elseif ($sessionHasRole('secretario')) {
                    $rotaDetalheSessao = '/secretaria?sessao_resumo=' . urlencode((string) $sessaoId);
                } elseif ($sessionHasRole('tesoureiro')) {
                    $rotaDetalheSessao = '/tesouraria/sessoes';
                } elseif ($sessionHasRole('mestre_banquetes')) {
                    $rotaDetalheSessao = '/mestre-banquetes';
                }

                $dashboardSessoes[] = [
                    'id' => $sessaoId,
                    'titulo' => trim((string) ($sessao['titulo'] ?? '')) !== ''
                        ? (string) $sessao['titulo']
                        : trim((string) (($sessao['tipo_sessao'] ?? 'Sessao') . ' - ' . ($sessao['grau_sessao'] ?? ''))),
                    'data_hora_inicio' => (string) ($sessao['data_hora_inicio'] ?? ''),
                    'status' => trim((string) ($sessao['status'] ?? 'programada')) ?: 'programada',
                    'tipo_sessao' => (string) ($sessao['tipo_sessao'] ?? ''),
                    'grau_sessao' => (string) ($sessao['grau_sessao'] ?? ''),
                    'descricao_agape' => $sessaoModel->obterDescricaoAgape($sessao),
                    'total_confirmados' => $presencaModel->contarConfirmadosPorSessao($sessaoId),
                    'total_agape' => $presencaModel->contarParticipantesAgapePorSessao($sessaoId),
                    'resposta_usuario' => is_array($respostaUsuario) ? (string) ($respostaUsuario['status_confirmacao'] ?? '') : '',
                    'confirmado' => is_array($respostaUsuario) && (string) ($respostaUsuario['status_confirmacao'] ?? '') === 'confirmado',
                    'detalhe_href' => $rotaDetalheSessao,
                ];
            }
        } catch (\Throwable $e) {
            error_log('Falha ao montar sessoes do dashboard: ' . $e->getMessage());
            $dashboardSessoes = [];
        }

        $dashboardRecados = [];
        try {
            $dashboardRecados = (new \App\Models\PublicacaoSecretaria())->listarRecentes(3);
        } catch (\Throwable $e) {
            error_log('Falha ao carregar recados do dashboard: ' . $e->getMessage());
            $dashboardRecados = [];
        }

        $dashboardPalavraIrmao = '';
        try {
            $dashboardEfemerides = $buildEfemeridesPreview();
            $dashboardPalavraIrmao = trim((string) ($dashboardEfemerides['mensagemPreview'] ?? ''));
        } catch (\Throwable $e) {
            error_log('Falha ao carregar palavra do irmao no dashboard: ' . $e->getMessage());
            $dashboardPalavraIrmao = '';
        }

        require_once __DIR__ . "/../src/Views/dashboard.php";
        break;

    case "/veneravel":
    case "/veneravel/dashboard":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) {
            header("Location: /login");
            exit;
        }
        if (!$sessionHasRole('veneravel', 'admin')) {
            http_response_code(403);
            echo "Acesso restrito ao Veneravel Mestre ou Administrador.";
            exit;
        }
        (new \App\Controllers\VeneravelController())->index();
        break;

    case "/orador":
    case "/orador/dashboard":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) {
            header("Location: /login");
            exit;
        }
        if (!$sessionHasRole('orador', 'veneravel', 'admin')) {
            http_response_code(403);
            echo "Acesso restrito ao Orador, Veneravel Mestre ou Administrador.";
            exit;
        }
        (new \App\Controllers\OradorController())->index();
        break;

    case "/miniapp/orador":
        requireMiniappAuth(['orador', 'veneravel', 'admin']);
        require_once __DIR__ . '/../src/Views/miniapp/orador.php';
        break;

    case "/api/miniapp/orador/dashboard":
        $miniappUser = requireMiniappAuth(['orador', 'veneravel', 'admin']);
        $controller = new \App\Controllers\OradorController();
        $sessaoId = isset($_GET['sessao_id']) ? (int) $_GET['sessao_id'] : null;
        jsonResponse([
            'ok' => true,
            'dados' => $controller->montarPayloadMiniapp($sessaoId),
            'usuario' => [
                'id' => $miniappUser['id'] ?? null,
                'nome' => $miniappUser['nome_completo'] ?? null,
            ],
        ]);
        break;

    case "/mestre-banquetes":
    case "/mestre-banquetes/dashboard":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) {
            header("Location: /login");
            exit;
        }
        if (!$sessionHasRole('mestre_banquetes', 'veneravel', 'admin')) {
            http_response_code(403);
            echo "Acesso restrito ao Mestre de Banquetes, Veneravel Mestre ou Administrador.";
            exit;
        }
        (new \App\Controllers\MestreBanquetesController())->index();
        break;

    case "/mestre-banquetes/operacao/salvar":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) {
            header("Location: /login");
            exit;
        }
        if (!$sessionHasRole('mestre_banquetes', 'veneravel', 'admin')) {
            http_response_code(403);
            echo "Acesso restrito ao Mestre de Banquetes, Veneravel Mestre ou Administrador.";
            exit;
        }
        (new \App\Controllers\MestreBanquetesController())->salvarOperacao();
        break;

    case "/chanceler/sessao":
    case "/chanceler/sessao/dashboard":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) {
            header("Location: /login");
            exit;
        }
        if (!$sessionHasRole('chanceler', 'veneravel', 'admin')) {
            http_response_code(403);
            echo "Acesso restrito ao Chanceler, Veneravel Mestre ou Administrador.";
            exit;
        }
        (new \App\Controllers\ChancelerSessaoController())->index();
        break;

    case "/chanceler/sessao/presenca":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) {
            header("Location: /login");
            exit;
        }
        if (!$sessionHasRole('chanceler', 'veneravel', 'admin')) {
            http_response_code(403);
            echo "Acesso restrito ao Chanceler, Veneravel Mestre ou Administrador.";
            exit;
        }
        (new \App\Controllers\ChancelerSessaoController())->registrarPresenca();
        break;

    case "/veneravel/sessoes/publicar":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) {
            header("Location: /login");
            exit;
        }
        if (!$sessionHasRole('veneravel', 'admin')) {
            http_response_code(403);
            echo "Acesso restrito ao Veneravel Mestre ou Administrador.";
            exit;
        }
        (new \App\Controllers\VeneravelController())->publicarSessao();
        break;

    case "/veneravel/sessoes/cancelar":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) {
            header("Location: /login");
            exit;
        }
        if (!$sessionHasRole('veneravel', 'admin')) {
            http_response_code(403);
            echo "Acesso restrito ao Veneravel Mestre ou Administrador.";
            exit;
        }
        (new \App\Controllers\VeneravelController())->cancelarSessao();
        break;

    case "/veneravel/sessoes/reabrir":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) {
            header("Location: /login");
            exit;
        }
        if (!$sessionHasRole('veneravel', 'admin')) {
            http_response_code(403);
            echo "Acesso restrito ao Veneravel Mestre ou Administrador.";
            exit;
        }
        (new \App\Controllers\VeneravelController())->reabrirSessao();
        break;

    case "/veneravel/sessoes/realizar":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) {
            header("Location: /login");
            exit;
        }
        if (!$sessionHasRole('veneravel', 'admin')) {
            http_response_code(403);
            echo "Acesso restrito ao Veneravel Mestre ou Administrador.";
            exit;
        }
        (new \App\Controllers\VeneravelController())->realizarSessao();
        break;

    case "/veneravel/balaustres/abrir-votacao":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) {
            header("Location: /login");
            exit;
        }
        if (!$sessionHasRole('veneravel', 'admin')) {
            http_response_code(403);
            echo "Acesso restrito ao Veneravel Mestre ou Administrador.";
            exit;
        }
        (new \App\Controllers\VeneravelController())->abrirVotacaoBalaustre();
        break;

    case "/veneravel/balaustres/encerrar-votacao":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) {
            header("Location: /login");
            exit;
        }
        if (!$sessionHasRole('veneravel', 'admin')) {
            http_response_code(403);
            echo "Acesso restrito ao Veneravel Mestre ou Administrador.";
            exit;
        }
        (new \App\Controllers\VeneravelController())->encerrarVotacaoBalaustre();
        break;

    case "/chancelaria/efemerides/salvar-previa":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) {
            header("Location: /login");
            exit;
        }
        if (!$sessionHasRole('chanceler', 'veneravel', 'admin')) {
            http_response_code(403);
            echo "Acesso restrito ao Chanceler, Veneravel Mestre ou Administrador.";
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
        if (!$sessionHasRole('chanceler', 'veneravel', 'admin')) {
            http_response_code(403);
            echo "Acesso restrito ao Chanceler, Veneravel Mestre ou Administrador.";
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
        $chatPrivadoDestino = trim((string) ($_SESSION['usuario_logado']['telegram_id'] ?? ''));
        if ($chatPrivadoDestino === '') {
            $chatPrivadoDestino = trim((string) ($_ENV['TELEGRAM_CHAT_ID_CHANCELER'] ?? ''));
        }
        $ok = $telegramService->sendMessageToChat($chatPrivadoDestino, $mensagemPreview);
        $redirectEfemerides($ok
            ? ['sucesso' => 'previa_enviada']
            : ['erro' => 'falha_enviar_previa', 'detalhe' => $telegramService->getLastError()]);
        break;

    case "/chancelaria/efemerides/enviar-grupo":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) {
            header("Location: /login");
            exit;
        }
        if (!$sessionHasRole('chanceler', 'veneravel', 'admin')) {
            http_response_code(403);
            echo "Acesso restrito ao Chanceler, Veneravel Mestre ou Administrador.";
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
        $redirectEfemerides($ok
            ? ['sucesso' => 'enviado']
            : ['erro' => 'falha_enviar_grupo', 'detalhe' => $telegramService->getLastError()]);
        break;

    case "/chancelaria/efemerides/salvar":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) {
            header("Location: /login");
            exit;
        }
        if (!$sessionHasRole('chanceler', 'veneravel', 'admin')) {
            http_response_code(403);
            echo "Acesso restrito ao Chanceler, Veneravel Mestre ou Administrador.";
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
        if (!$sessionHasRole('chanceler', 'veneravel', 'admin')) {
            http_response_code(403);
            echo "Acesso restrito ao Chanceler, Veneravel Mestre ou Administrador.";
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
        if (!$sessionHasRole('chanceler', 'veneravel', 'admin')) {
            http_response_code(403);
            echo "Acesso restrito ao Chanceler, Veneravel Mestre ou Administrador.";
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
        if (!$sessionHasRole('chanceler', 'veneravel', 'admin')) {
            http_response_code(403);
            echo "Acesso restrito ao Chanceler, Veneravel Mestre ou Administrador.";
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
            'falha_enviar_previa' => 'Falha ao enviar a previa no privado.' . (!empty($_GET['detalhe']) ? ' Detalhe: ' . (string) $_GET['detalhe'] : ''),
            'falha_enviar_grupo' => 'Falha ao enviar no grupo oficial.' . (!empty($_GET['detalhe']) ? ' Detalhe: ' . (string) $_GET['detalhe'] : ''),
            'registro_invalido' => 'Preencha nome, tipo e data do evento corretamente.',
            'falha_salvar_registro' => 'Nao foi possivel salvar o registro.',
            'falha_atualizar_registro' => 'Nao foi possivel atualizar o registro.',
            'id_invalido' => 'Registro invalido para desativacao.',
            'falha_desativar' => 'Nao foi possivel desativar o registro.',
            default => null,
        };

        $dadosEfemerides = $buildEfemeridesPreview();
        $registrosHoje = $dadosEfemerides['registrosHoje'];
        $filtroIrmaoRef = trim((string) ($_GET['irmao_ref'] ?? ''));
        $filtroTermo = trim((string) ($_GET['termo'] ?? ''));
        $filtroTipo = trim((string) ($_GET['tipo'] ?? ''));
        $filtroVinculo = trim((string) ($_GET['vinculo'] ?? ''));
        $filtroAtivo = trim((string) ($_GET['ativo'] ?? '1'));
        $filtroDataIni = trim((string) ($_GET['data_ini'] ?? ''));
        $filtroDataFim = trim((string) ($_GET['data_fim'] ?? ''));
        $focoEfemeride = trim((string) ($_GET['foco'] ?? ''));
        $filtrosEfemeride = [
            'irmao_ref' => $filtroIrmaoRef,
            'termo' => $filtroTermo,
            'tipo' => $filtroTipo,
            'vinculo' => $filtroVinculo,
            'ativo' => $filtroAtivo,
            'data_ini' => $filtroDataIni,
            'data_fim' => $filtroDataFim,
        ];
        $registroModel = new \App\Models\EfemerideRegistro();
        $registrosRecentes = $registroModel->buscarComFiltros($filtrosEfemeride, 300);
        $registrosRecentes = $mergeHistoricosFixos($registrosRecentes, $filtrosEfemeride);
        $vinculosPadrao = $registroModel->getVinculosPadrao();
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
        $obreirosFiltro = (new \App\Models\Obreiro())->getAllAtivos();

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

    case "/miniapp/aprendizado":
        require_once __DIR__ . "/../src/Views/miniapp/aprendizado.php";
        break;

    case "/miniapp/primeiro-vigilante":
        require_once __DIR__ . "/../src/Views/miniapp/primeiro_vigilante.php";
        break;

    case "/miniapp/companheirismo":
        require_once __DIR__ . "/../src/Views/miniapp/companheirismo.php";
        break;

    case "/miniapp/segundo-vigilante":
        require_once __DIR__ . "/../src/Views/miniapp/segundo_vigilante.php";
        break;

    case "/miniapp/secretaria":
        require_once __DIR__ . "/../src/Views/miniapp/secretaria.php";
        break;

    case "/miniapp/hospitaleiro":
        require_once __DIR__ . "/../src/Views/miniapp/hospitaleiro.php";
        break;

    case "/miniapp/chanceler":
        require_once __DIR__ . "/../src/Views/miniapp/chanceler.php";
        break;

    case "/miniapp/mestre-banquetes":
        require_once __DIR__ . "/../src/Views/miniapp/mestre_banquetes.php";
        break;

    case "/miniapp/veneravel":
        require_once __DIR__ . "/../src/Views/miniapp/veneravel.php";
        break;

    case (preg_match('~^/api/miniapp~', $requestUri) ? $requestUri : null):
        header('Content-Type: application/json; charset=utf-8');

        $body = $getJsonBody();
        $initData = trim((string) ($body['initData'] ?? $body['init_data'] ?? $_GET['initData'] ?? $_GET['init_data'] ?? ''));
        $miniappObreiro = null;
        $miniappAllowedRoles = match (true) {
            str_starts_with($requestUri, '/api/miniapp/secretaria') => ['secretario', 'veneravel', 'admin'],
            str_starts_with($requestUri, '/api/miniapp/aprendizado') => ['primeiro_vigilante', 'veneravel', 'admin'],
            str_starts_with($requestUri, '/api/miniapp/primeiro-vigilante') => ['primeiro_vigilante', 'veneravel', 'admin'],
            str_starts_with($requestUri, '/api/miniapp/companheirismo') => ['segundo_vigilante', 'veneravel', 'admin'],
            str_starts_with($requestUri, '/api/miniapp/segundo-vigilante') => ['segundo_vigilante', 'veneravel', 'admin'],
            str_starts_with($requestUri, '/api/miniapp/mestre-banquetes') => ['mestre_banquetes', 'veneravel', 'admin'],
            str_starts_with($requestUri, '/api/miniapp/mestre-harmonia') => ['mestre_harmonia', 'veneravel', 'admin'],
            str_starts_with($requestUri, '/api/miniapp/tesouraria') => ['tesoureiro', 'veneravel', 'admin'],
            str_starts_with($requestUri, '/api/miniapp/biblioteca') => ['bibliotecario', 'primeiro_vigilante', 'segundo_vigilante', 'veneravel', 'admin'],
            str_starts_with($requestUri, '/api/miniapp/admin') => ['admin', 'veneravel'],
            str_starts_with($requestUri, '/api/miniapp/orador') => ['orador', 'veneravel', 'admin'],
            str_starts_with($requestUri, '/api/miniapp/veneravel') => ['veneravel', 'admin'],
            str_starts_with($requestUri, '/api/miniapp/hospitaleiro') => ['hospitaleiro', 'secretario', 'tesoureiro', 'veneravel', 'admin'],
            default => ['chanceler', 'veneravel', 'admin'],
        };
        $authorizedBySession = isset($_SESSION['usuario_logado']) && $sessionHasRole(...$miniappAllowedRoles);

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
            $temPermissaoMiniapp = false;
            foreach ($miniappAllowedRoles as $allowedRole) {
                if (in_array($allowedRole, $roles, true)) {
                    $temPermissaoMiniapp = true;
                    break;
                }
            }
            if (!$temPermissaoMiniapp) {
                http_response_code(403);
                echo json_encode(['ok' => false, 'erro' => 'Acesso restrito para este miniapp.']);
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

        if ($requestUri === '/api/miniapp/aprendizado' && $method === 'GET') {
            $roles = array_values(array_unique(array_map(
                static fn ($role) => strtolower((string) $role),
                $miniappObreiro['cargos'] ?? [$miniappObreiro['cargo_principal'] ?? $miniappObreiro['cargo'] ?? '']
            )));

            $aprendizId = trim((string) ($_GET['aprendiz_id'] ?? ''));
            $usuarioIdMiniapp = trim((string) ($miniappObreiro['id'] ?? ''));
            $podeConsultarOutros = in_array('primeiro_vigilante', $roles, true) || in_array('veneravel', $roles, true) || in_array('admin', $roles, true);
            $aprendizIdConsulta = $podeConsultarOutros && $aprendizId !== '' ? $aprendizId : $usuarioIdMiniapp;

            $controller = new \App\Controllers\PrimeiroVigilanteController();
            $payload = $controller->montarPayloadMiniapp($aprendizIdConsulta);
            if ($payload === null) {
                echo json_encode(['ok' => false, 'erro' => 'Aprendiz nao encontrado para acompanhamento.']);
                exit;
            }

            echo json_encode(['ok' => true, 'dados' => $payload]);
            exit;
        }

        if ($requestUri === '/api/miniapp/primeiro-vigilante/dashboard' && $method === 'GET') {
            $aprendizId = trim((string) ($_GET['aprendiz_id'] ?? ''));
            $controller = new \App\Controllers\PrimeiroVigilanteController();
            echo json_encode(['ok' => true, 'dados' => $controller->montarPayloadPainelMiniapp($aprendizId !== '' ? $aprendizId : null)]);
            exit;
        }

        if ($requestUri === '/api/miniapp/primeiro-vigilante/leitura/salvar' && $method === 'POST') {
            $controller = new \App\Controllers\PrimeiroVigilanteController();
            $autorId = trim((string) ($miniappObreiro['id'] ?? $_SESSION['usuario_id'] ?? ''));
            echo json_encode($controller->salvarLeituraSugeridaMiniapp(
                trim((string) ($body['aprendiz_id'] ?? '')),
                isset($body['acervo_id']) && (int) $body['acervo_id'] > 0 ? (int) $body['acervo_id'] : null,
                trim((string) ($body['observacao_leitura'] ?? '')) ?: null,
                $autorId !== '' ? $autorId : null
            ));
            exit;
        }

        if ($requestUri === '/api/miniapp/primeiro-vigilante/trilha/atualizar' && $method === 'POST') {
            $controller = new \App\Controllers\PrimeiroVigilanteController();
            $autorId = trim((string) ($miniappObreiro['id'] ?? $_SESSION['usuario_id'] ?? ''));
            echo json_encode($controller->atualizarEtapaMiniapp(
                trim((string) ($body['aprendiz_id'] ?? '')),
                (int) ($body['etapa_ordem'] ?? 0),
                trim((string) ($body['status'] ?? '')),
                trim((string) ($body['observacao_vigilante'] ?? '')) ?: null,
                $autorId !== '' ? $autorId : null
            ));
            exit;
        }

        if ($requestUri === '/api/miniapp/primeiro-vigilante/certificado/solicitar' && $method === 'POST') {
            $controller = new \App\Controllers\PrimeiroVigilanteController();
            $autorId = trim((string) ($miniappObreiro['id'] ?? $_SESSION['usuario_id'] ?? ''));
            echo json_encode($controller->solicitarCertificadoMiniapp(
                trim((string) ($body['aprendiz_id'] ?? '')),
                trim((string) ($body['observacao_certificado'] ?? '')) ?: null,
                $autorId !== '' ? $autorId : null
            ));
            exit;
        }

        if ($requestUri === '/api/miniapp/companheirismo' && $method === 'GET') {
            $roles = array_values(array_unique(array_map(
                static fn ($role) => strtolower((string) $role),
                $miniappObreiro['cargos'] ?? [$miniappObreiro['cargo_principal'] ?? $miniappObreiro['cargo'] ?? '']
            )));

            $companheiroId = trim((string) ($_GET['companheiro_id'] ?? ''));
            $usuarioIdMiniapp = trim((string) ($miniappObreiro['id'] ?? ''));
            $podeConsultarOutros = in_array('segundo_vigilante', $roles, true) || in_array('veneravel', $roles, true) || in_array('admin', $roles, true);
            $companheiroIdConsulta = $podeConsultarOutros && $companheiroId !== '' ? $companheiroId : $usuarioIdMiniapp;

            $controller = new \App\Controllers\SegundoVigilanteController();
            $payload = $controller->montarPayloadMiniapp($companheiroIdConsulta);
            if ($payload === null) {
                echo json_encode(['ok' => false, 'erro' => 'Companheiro nao encontrado para acompanhamento.']);
                exit;
            }

            echo json_encode(['ok' => true, 'dados' => $payload]);
            exit;
        }

        if ($requestUri === '/api/miniapp/segundo-vigilante/dashboard' && $method === 'GET') {
            $companheiroId = trim((string) ($_GET['companheiro_id'] ?? ''));
            $controller = new \App\Controllers\SegundoVigilanteController();
            echo json_encode(['ok' => true, 'dados' => $controller->montarPayloadPainelMiniapp($companheiroId !== '' ? $companheiroId : null)]);
            exit;
        }

        if ($requestUri === '/api/miniapp/segundo-vigilante/trilha/atualizar' && $method === 'POST') {
            $controller = new \App\Controllers\SegundoVigilanteController();
            $autorId = trim((string) ($miniappObreiro['id'] ?? $_SESSION['usuario_id'] ?? ''));
            echo json_encode($controller->atualizarEtapaMiniapp(
                trim((string) ($body['companheiro_id'] ?? '')),
                (int) ($body['etapa_ordem'] ?? 0),
                trim((string) ($body['status'] ?? '')),
                trim((string) ($body['observacao_vigilante'] ?? '')) ?: null,
                $autorId !== '' ? $autorId : null
            ));
            exit;
        }

        if ($requestUri === '/api/miniapp/segundo-vigilante/leitura/salvar' && $method === 'POST') {
            $controller = new \App\Controllers\SegundoVigilanteController();
            $autorId = trim((string) ($miniappObreiro['id'] ?? $_SESSION['usuario_id'] ?? ''));
            echo json_encode($controller->salvarLeituraSugeridaMiniapp(
                trim((string) ($body['companheiro_id'] ?? '')),
                isset($body['acervo_id']) && (int) $body['acervo_id'] > 0 ? (int) $body['acervo_id'] : null,
                trim((string) ($body['observacao_leitura'] ?? '')) ?: null,
                $autorId !== '' ? $autorId : null
            ));
            exit;
        }

        if ($requestUri === '/api/miniapp/segundo-vigilante/certificado/solicitar' && $method === 'POST') {
            $controller = new \App\Controllers\SegundoVigilanteController();
            $autorId = trim((string) ($miniappObreiro['id'] ?? $_SESSION['usuario_id'] ?? ''));
            echo json_encode($controller->solicitarCertificadoMiniapp(
                trim((string) ($body['companheiro_id'] ?? '')),
                trim((string) ($body['observacao_certificado'] ?? '')) ?: null,
                $autorId !== '' ? $autorId : null
            ));
            exit;
        }

        if ($requestUri === '/api/miniapp/segundo-vigilante/exaltacao/recomendar' && $method === 'POST') {
            $controller = new \App\Controllers\SegundoVigilanteController();
            $autorId = trim((string) ($miniappObreiro['id'] ?? $_SESSION['usuario_id'] ?? ''));
            echo json_encode($controller->recomendarExaltacaoMiniapp(
                trim((string) ($body['companheiro_id'] ?? '')),
                trim((string) ($body['observacao_exaltacao'] ?? '')) ?: null,
                $autorId !== '' ? $autorId : null
            ));
            exit;
        }

        if ($requestUri === '/api/miniapp/secretaria/dashboard' && $method === 'GET') {
            $sessaoId = (int) ($_GET['sessao_id'] ?? 0);
            $controller = new \App\Controllers\SecretariaController();
            echo json_encode(['ok' => true, 'dados' => $controller->montarPayloadMiniapp($sessaoId > 0 ? $sessaoId : null)]);
            exit;
        }

        if ($requestUri === '/api/miniapp/secretaria/sessao/salvar' && $method === 'POST') {
            $controller = new \App\Controllers\SecretariaController();
            $autorId = trim((string) ($miniappObreiro['id'] ?? $_SESSION['usuario_id'] ?? ''));
            $resultado = $controller->salvarSessaoMiniapp($body, $autorId !== '' ? $autorId : null);
            echo json_encode($resultado);
            exit;
        }

        if ($requestUri === '/api/miniapp/secretaria/sessao/publicar' && $method === 'POST') {
            $sessaoId = (int) ($body['sessao_id'] ?? 0);
            $autorId = trim((string) ($miniappObreiro['id'] ?? $_SESSION['usuario_id'] ?? ''));
            $ok = $sessaoId > 0
                ? (new \App\Models\Sessao())->marcarPublicada($sessaoId, $autorId !== '' ? $autorId : null, 'Publicacao realizada pela Secretaria no miniapp.')
                : false;
            echo json_encode(['ok' => $ok, 'erro' => $ok ? null : 'Nao foi possivel publicar a sessao.']);
            exit;
        }

        if ($requestUri === '/api/miniapp/secretaria/sessao/cancelar' && $method === 'POST') {
            $sessaoId = (int) ($body['sessao_id'] ?? 0);
            $autorId = trim((string) ($miniappObreiro['id'] ?? $_SESSION['usuario_id'] ?? ''));
            $ok = $sessaoId > 0
                ? (new \App\Models\Sessao())->cancelar($sessaoId, $autorId !== '' ? $autorId : null, 'Cancelamento realizado pela Secretaria no miniapp.')
                : false;
            echo json_encode(['ok' => $ok, 'erro' => $ok ? null : 'Nao foi possivel cancelar a sessao.']);
            exit;
        }

        if ($requestUri === '/api/miniapp/secretaria/sessao/reabrir' && $method === 'POST') {
            $sessaoId = (int) ($body['sessao_id'] ?? 0);
            $autorId = trim((string) ($miniappObreiro['id'] ?? $_SESSION['usuario_id'] ?? ''));
            $ok = $sessaoId > 0
                ? (new \App\Models\Sessao())->reabrir($sessaoId, $autorId !== '' ? $autorId : null, 'Reabertura realizada pela Secretaria no miniapp.')
                : false;
            echo json_encode(['ok' => $ok, 'erro' => $ok ? null : 'Nao foi possivel reabrir a sessao.']);
            exit;
        }

        if ($requestUri === '/api/miniapp/secretaria/trabalho/salvar' && $method === 'POST') {
            $controller = new \App\Controllers\SecretariaController();
            $autorId = trim((string) ($miniappObreiro['id'] ?? $_SESSION['usuario_id'] ?? ''));
            echo json_encode($controller->salvarTrabalhoMiniapp($body, $autorId !== '' ? $autorId : null));
            exit;
        }

        if ($requestUri === '/api/miniapp/secretaria/balaustre/salvar' && $method === 'POST') {
            $controller = new \App\Controllers\SecretariaController();
            $autorId = trim((string) ($miniappObreiro['id'] ?? $_SESSION['usuario_id'] ?? ''));
            echo json_encode($controller->salvarBalaustreMiniapp($body, $autorId !== '' ? $autorId : null));
            exit;
        }

        if ($requestUri === '/api/miniapp/secretaria/balaustre/apto' && $method === 'POST') {
            $balaustreId = (int) ($body['balaustre_id'] ?? 0);
            $autorId = trim((string) ($miniappObreiro['id'] ?? $_SESSION['usuario_id'] ?? ''));
            $ok = $balaustreId > 0 ? (new \App\Models\Balaustre())->marcarAptoVotacao($balaustreId, $autorId !== '' ? $autorId : null) : false;
            echo json_encode(['ok' => $ok, 'erro' => $ok ? null : 'Nao foi possivel marcar o balaustre como apto.']);
            exit;
        }

        if ($requestUri === '/api/miniapp/secretaria/balaustre/abrir-votacao' && $method === 'POST') {
            $balaustreId = (int) ($body['balaustre_id'] ?? 0);
            $autorId = trim((string) ($miniappObreiro['id'] ?? $_SESSION['usuario_id'] ?? ''));
            $resultado = $balaustreId > 0 ? (new \App\Models\Balaustre())->abrirVotacao($balaustreId, $autorId !== '' ? $autorId : null) : ['ok' => false, 'erro' => 'Balaustre invalido.'];
            echo json_encode($resultado);
            exit;
        }

        if ($requestUri === '/api/miniapp/secretaria/balaustre/encerrar-votacao' && $method === 'POST') {
            $balaustreId = (int) ($body['balaustre_id'] ?? 0);
            $resultado = $balaustreId > 0 ? (new \App\Models\Balaustre())->encerrarVotacaoPorBalaustre($balaustreId) : ['ok' => false, 'erro' => 'Balaustre invalido.'];
            echo json_encode($resultado);
            exit;
        }

        if ($requestUri === '/api/miniapp/chanceler/dashboard' && $method === 'GET') {
            $sessaoId = (int) ($_GET['sessao_id'] ?? 0);
            $controller = new \App\Controllers\ChancelerSessaoController();
            echo json_encode(['ok' => true, 'dados' => $controller->montarPayloadMiniapp($sessaoId > 0 ? $sessaoId : null)]);
            exit;
        }

        if ($requestUri === '/api/miniapp/chanceler/presenca' && $method === 'POST') {
            $controller = new \App\Controllers\ChancelerSessaoController();
            $sessaoId = (int) ($body['sessao_id'] ?? 0);
            $obreiroId = trim((string) ($body['obreiro_id'] ?? ''));
            $presente = filter_var($body['presente'] ?? false, FILTER_VALIDATE_BOOL);
            $autorId = trim((string) ($miniappObreiro['id'] ?? $_SESSION['usuario_id'] ?? ''));
            echo json_encode($controller->registrarPresencaMiniapp(
                $sessaoId,
                $obreiroId,
                $presente,
                $autorId !== '' ? $autorId : null
            ));
            exit;
        }

        if ($requestUri === '/api/miniapp/mestre-banquetes/dashboard' && $method === 'GET') {
            $sessaoId = (int) ($_GET['sessao_id'] ?? 0);
            $controller = new \App\Controllers\MestreBanquetesController();
            echo json_encode(['ok' => true, 'dados' => $controller->montarPayloadMiniapp($sessaoId > 0 ? $sessaoId : null)]);
            exit;
        }

        if ($requestUri === '/api/miniapp/mestre-banquetes/operacao/salvar' && $method === 'POST') {
            $controller = new \App\Controllers\MestreBanquetesController();
            $autorId = isset($miniappObreiro['id']) ? (int) $miniappObreiro['id'] : (isset($_SESSION['usuario_id']) ? (int) $_SESSION['usuario_id'] : null);
            echo json_encode($controller->salvarOperacaoMiniapp($body, $autorId));
            exit;
        }

        if ($requestUri === '/api/miniapp/mestre-harmonia/dashboard' && $method === 'GET') {
            $controller = new \App\Controllers\MestreHarmoniaController();
            $sessaoPath = trim((string) ($_GET['sessao_path'] ?? ''));
            echo json_encode(['ok' => true, 'dados' => $controller->montarPayloadMiniapp($sessaoPath !== '' ? $sessaoPath : null)]);
            exit;
        }

        if ($requestUri === '/api/miniapp/mestre-harmonia/operador' && $method === 'POST') {
            $controller = new \App\Controllers\MestreHarmoniaController();
            echo json_encode($controller->salvarOperadorMiniapp($body));
            exit;
        }

        if ($requestUri === '/api/miniapp/mestre-harmonia/controle' && $method === 'POST') {
            $controller = new \App\Controllers\MestreHarmoniaController();
            echo json_encode($controller->executarAcaoMiniapp(trim((string) ($body['acao'] ?? '')), $body));
            exit;
        }

        if ($requestUri === '/api/miniapp/tesouraria/dashboard' && $method === 'GET') {
            $controller = new \App\Controllers\TesourariaController();
            echo json_encode(['ok' => true, 'dados' => $controller->montarPayloadMiniapp()]);
            exit;
        }

        if ($requestUri === '/api/miniapp/tesouraria/comprovante/aprovar' && $method === 'POST') {
            $controller = new \App\Controllers\TesourariaController();
            $usuarioId = trim((string) ($miniappObreiro['id'] ?? $_SESSION['usuario_id'] ?? ''));
            echo json_encode($controller->aprovarComprovanteMiniapp($body, $usuarioId !== '' ? $usuarioId : null));
            exit;
        }

        if ($requestUri === '/api/miniapp/tesouraria/comprovante/rejeitar' && $method === 'POST') {
            $controller = new \App\Controllers\TesourariaController();
            $usuarioId = trim((string) ($miniappObreiro['id'] ?? $_SESSION['usuario_id'] ?? ''));
            echo json_encode($controller->rejeitarComprovanteMiniapp((int) ($body['id'] ?? 0), (string) ($body['motivo'] ?? ''), $usuarioId !== '' ? $usuarioId : null));
            exit;
        }

        if ($requestUri === '/api/miniapp/tesouraria/regularidade/definir' && $method === 'POST') {
            $controller = new \App\Controllers\TesourariaController();
            $usuarioId = trim((string) ($miniappObreiro['id'] ?? $_SESSION['usuario_id'] ?? ''));
            echo json_encode($controller->definirRegularidadeMiniapp(
                (string) ($body['obreiro_id'] ?? ''),
                (int) ($body['mes'] ?? date('n')),
                (int) ($body['ano'] ?? date('Y')),
                (string) ($body['status'] ?? 'regular'),
                $usuarioId !== '' ? $usuarioId : null
            ));
            exit;
        }

        if ($requestUri === '/api/miniapp/tesouraria/fechamento/fechar' && $method === 'POST') {
            $controller = new \App\Controllers\TesourariaController();
            $usuarioId = trim((string) ($miniappObreiro['id'] ?? $_SESSION['usuario_id'] ?? ''));
            echo json_encode($controller->fecharCompetenciaMiniapp((int) ($body['mes'] ?? date('n')), (int) ($body['ano'] ?? date('Y')), $usuarioId !== '' ? $usuarioId : null));
            exit;
        }

        if ($requestUri === '/api/miniapp/biblioteca/dashboard' && $method === 'GET') {
            $controller = new \App\Controllers\BibliotecaController();
            $acervoId = (int) ($_GET['acervo_id'] ?? 0);
            $obreiroId = trim((string) ($miniappObreiro['id'] ?? $_SESSION['usuario_id'] ?? ''));
            echo json_encode(['ok' => true, 'dados' => $controller->montarPayloadMiniapp($obreiroId !== '' ? $obreiroId : null, $acervoId > 0 ? $acervoId : null)]);
            exit;
        }

        if ($requestUri === '/api/miniapp/biblioteca/solicitar' && $method === 'POST') {
            $controller = new \App\Controllers\BibliotecaController();
            $obreiroId = trim((string) ($miniappObreiro['id'] ?? $_SESSION['usuario_id'] ?? ''));
            echo json_encode($controller->solicitarMiniapp((int) ($body['acervo_id'] ?? 0), $obreiroId));
            exit;
        }

        if ($requestUri === '/api/miniapp/biblioteca/comentar' && $method === 'POST') {
            $controller = new \App\Controllers\BibliotecaController();
            $obreiroId = trim((string) ($miniappObreiro['id'] ?? $_SESSION['usuario_id'] ?? ''));
            echo json_encode($controller->comentarMiniapp((int) ($body['acervo_id'] ?? 0), $obreiroId, (string) ($body['comentario'] ?? '')));
            exit;
        }

        if ($requestUri === '/api/miniapp/biblioteca/reagir' && $method === 'POST') {
            $controller = new \App\Controllers\BibliotecaController();
            $obreiroId = trim((string) ($miniappObreiro['id'] ?? $_SESSION['usuario_id'] ?? ''));
            echo json_encode($controller->reagirMiniapp((int) ($body['acervo_id'] ?? 0), $obreiroId, !empty($body['gostei'])));
            exit;
        }

        if ($requestUri === '/api/miniapp/admin/dashboard' && $method === 'GET') {
            $controller = new \App\Controllers\AdminController();
            echo json_encode(['ok' => true, 'dados' => $controller->montarPayloadMiniapp()]);
            exit;
        }

        if ($requestUri === '/api/miniapp/admin/gestao/abrir' && $method === 'POST') {
            $controller = new \App\Controllers\AdminController();
            echo json_encode($controller->abrirGestaoMiniapp((string) ($body['titulo'] ?? ''), (string) ($body['inicio_em'] ?? ''), (string) ($body['observacao'] ?? '')));
            exit;
        }

        if ($requestUri === '/api/miniapp/admin/gestao/encerrar' && $method === 'POST') {
            $controller = new \App\Controllers\AdminController();
            echo json_encode($controller->encerrarGestaoMiniapp((int) ($body['gestao_id'] ?? 0), (string) ($body['encerrada_em'] ?? '')));
            exit;
        }

        if ($requestUri === '/api/miniapp/admin/cargo/atribuir' && $method === 'POST') {
            $controller = new \App\Controllers\AdminController();
            echo json_encode($controller->atribuirCargoMiniapp(
                (string) ($body['cargo_codigo'] ?? ''),
                (string) ($body['obreiro_id'] ?? ''),
                isset($body['gestao_id']) ? (int) $body['gestao_id'] : null,
                (string) ($body['inicio_em'] ?? ''),
                (string) ($body['observacao'] ?? '')
            ));
            exit;
        }

        if ($requestUri === '/api/miniapp/admin/configuracao/salvar' && $method === 'POST') {
            $controller = new \App\Controllers\AdminController();
            echo json_encode($controller->salvarConfiguracaoMiniapp($body));
            exit;
        }

        if ($requestUri === '/api/miniapp/orador/dashboard' && $method === 'GET') {
            $sessaoId = (int) ($_GET['sessao_id'] ?? 0);
            $controller = new \App\Controllers\OradorController();
            echo json_encode(['ok' => true, 'dados' => $controller->montarPayloadMiniapp($sessaoId > 0 ? $sessaoId : null)]);
            exit;
        }

        if ($requestUri === '/api/miniapp/veneravel/dashboard' && $method === 'GET') {
            $sessaoId = (int) ($_GET['sessao_id'] ?? 0);
            $controller = new \App\Controllers\VeneravelController();
            echo json_encode(['ok' => true, 'dados' => $controller->montarPayloadMiniapp($sessaoId > 0 ? $sessaoId : null)]);
            exit;
        }

        if ($requestUri === '/api/miniapp/hospitaleiro/dashboard' && $method === 'GET') {
            $controller = new \App\Controllers\HospitaleiroController();
            echo json_encode(['ok' => true, 'dados' => $controller->montarPayloadMiniapp()]);
            exit;
        }

        if ($requestUri === '/api/miniapp/hospitaleiro/ocorrencias/salvar' && $method === 'POST') {
            $controller = new \App\Controllers\HospitaleiroController();
            $autorId = trim((string) ($miniappObreiro['id'] ?? $_SESSION['usuario_id'] ?? ''));
            echo json_encode($controller->salvarOcorrenciaMiniapp($body, $autorId !== '' ? $autorId : null));
            exit;
        }

        if ($requestUri === '/api/miniapp/hospitaleiro/ocorrencias/status' && $method === 'POST') {
            $controller = new \App\Controllers\HospitaleiroController();
            $autorId = trim((string) ($miniappObreiro['id'] ?? $_SESSION['usuario_id'] ?? ''));
            echo json_encode($controller->atualizarStatusMiniapp(
                (int) ($body['ocorrencia_id'] ?? 0),
                trim((string) ($body['status'] ?? '')),
                $autorId !== '' ? $autorId : null,
                trim((string) ($body['observacao_status'] ?? '')) ?: null
            ));
            exit;
        }

        if ($requestUri === '/api/miniapp/hospitaleiro/visita' && $method === 'POST') {
            $controller = new \App\Controllers\HospitaleiroController();
            $autorId = trim((string) ($miniappObreiro['id'] ?? $_SESSION['usuario_id'] ?? ''));
            echo json_encode($controller->registrarVisitaMiniapp(
                (int) ($body['ocorrencia_id'] ?? 0),
                $autorId !== '' ? $autorId : null,
                trim((string) ($body['observacao_visita'] ?? '')) ?: null,
                trim((string) ($body['data_proxima_acao'] ?? '')) ?: null
            ));
            exit;
        }

        if (preg_match('~^/api/miniapp/veneravel/sessao/(publicar|cancelar|reabrir|realizar)$~', $requestUri, $m) && $method === 'POST') {
            $controller = new \App\Controllers\VeneravelController();
            $sessaoId = (int) ($body['sessao_id'] ?? 0);
            $autorId = trim((string) ($miniappObreiro['id'] ?? $_SESSION['usuario_id'] ?? ''));
            echo json_encode($controller->executarAcaoSessaoMiniapp($m[1], $sessaoId, $autorId !== '' ? $autorId : null));
            exit;
        }

        if (preg_match('~^/api/miniapp/veneravel/balaustre/(abrir-votacao|encerrar-votacao)$~', $requestUri, $m) && $method === 'POST') {
            $controller = new \App\Controllers\VeneravelController();
            $balaustreId = (int) ($body['balaustre_id'] ?? 0);
            $autorId = trim((string) ($miniappObreiro['id'] ?? $_SESSION['usuario_id'] ?? ''));
            $acao = $m[1] === 'abrir-votacao' ? 'abrir' : 'encerrar';
            echo json_encode($controller->executarAcaoBalaustreMiniapp($acao, $balaustreId, $autorId !== '' ? $autorId : null));
            exit;
        }

        http_response_code(404);
        echo json_encode(['ok' => false, 'erro' => 'API miniapp nao encontrada.']);
        exit;

    case "/api/mestre-harmonia/scan":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'erro' => 'Nao autenticado.']);
            exit;
        }
        (new \App\Controllers\MestreHarmoniaController())->scan();
        exit;

    case "/api/mestre-harmonia/audio":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) {
            http_response_code(401);
            echo 'Nao autenticado.';
            exit;
        }
        (new \App\Controllers\MestreHarmoniaController())->audio();
        exit;

    case "/api/mestre-harmonia/operador":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'erro' => 'Nao autenticado.']);
            exit;
        }
        (new \App\Controllers\MestreHarmoniaController())->salvarOperador();
        exit;

    // Tesouraria API
    case (preg_match('~^/api/tesouraria~', $requestUri) ? $requestUri : null):
        header('Content-Type: application/json; charset=utf-8');

        if (!$openTestAccess && !isset($_SESSION['usuario_logado'])) {
            $telegramObreiro = $resolveAuthorizedTelegramObreiro('tesoureiro', 'veneravel', 'admin');
            if (!$telegramObreiro) {
                http_response_code(401);
                echo json_encode(['ok' => false, 'erro' => 'Nao autenticado.']);
                exit;
            }
            $loginTelegramObreiroInSession($telegramObreiro);
        }
        if (!$sessionHasRole('tesoureiro', 'veneravel', 'admin')) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'erro' => 'Acesso restrito ao Tesoureiro, Veneravel Mestre ou Administrador.']);
            exit;
        }

        $usuarioId = $_SESSION['usuario_id'] ?? 0;

        if ($requestUri === '/api/tesouraria/categorias' && $method === 'GET') {
            $tipo = trim((string) ($_GET['tipo'] ?? ''));
            $categoriaModel = new \App\Models\CategoriaFinanceira();
            $categorias = $tipo !== ''
                ? $categoriaModel->obterPorTipo($tipo)
                : $categoriaModel->obterTodas();
            echo json_encode(['ok' => true, 'categorias' => $categorias]);
            exit;
        }

        if ($requestUri === '/api/tesouraria/caixa' && $method === 'GET') {
            $mes = (int) ($_GET['mes'] ?? date('n'));
            $ano = (int) ($_GET['ano'] ?? date('Y'));
            $lancModel = new \App\Models\LancamentoFinanceiro();
            $lancamentos = $lancModel->obterPorMes($mes, $ano);
            $totais = $lancModel->obterTotaisMes($mes, $ano);
            $porCategoria = $lancModel->obterPorCategoriaMes($mes, $ano);

            echo json_encode([
                'ok' => true,
                'lancamentos' => $lancamentos,
                'totais' => $totais,
                'categorias' => $porCategoria,
            ]);
            exit;
        }

        if ($requestUri === '/api/tesouraria/lancamento/criar' && $method === 'POST') {
            $body = json_decode(file_get_contents('php://input'), true) ?? [];
            $lancModel = new \App\Models\LancamentoFinanceiro();
            $ok = $lancModel->criar([
                'tipo' => $body['tipo'] ?? 'entrada',
                'categoria_id' => (int) ($body['categoria_id'] ?? 0),
                'valor' => (float) ($body['valor'] ?? 0),
                'data_lancamento' => $body['data_lancamento'] ?? date('Y-m-d'),
                'descricao' => trim((string) ($body['descricao'] ?? '')) ?: null,
                'obreiro_id' => trim((string) ($body['obreiro_id'] ?? '')) ?: null,
                'mes_ref' => (int) ($body['mes_ref'] ?? date('n')),
                'ano_ref' => (int) ($body['ano_ref'] ?? date('Y')),
                'created_by' => $usuarioId,
            ]);

            echo json_encode(['ok' => $ok]);
            exit;
        }

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
            $obrigacaoModel = new \App\Models\ObrigacaoFinanceira();

            $comprovante = $comproModel->obterPorId((int) ($body['id'] ?? 0));
            if (!$comprovante) {
                echo json_encode(['ok' => false]);
                exit;
            }

            $validacao = [
                'valor' => (float) ($body['valor'] ?? 0),
                'mes' => (int) ($body['mes'] ?? date('n')),
                'ano' => (int) ($body['ano'] ?? date('Y')),
                'rotulo_pagamento' => trim((string) ($body['rotulo_pagamento'] ?? '')) ?: null,
                'categoria_id' => (int) ($body['categoria_id'] ?? 0) ?: null,
                'obrigacao_parcela_id' => (int) ($body['obrigacao_parcela_id'] ?? 0) ?: null,
                'validado_por' => $usuarioId,
            ];
            $comproModel->aprovar((int) ($body['id'] ?? 0), $validacao);

            if (!empty($validacao['obrigacao_parcela_id'])) {
                $obrigacaoModel->quitarParcela((int) $validacao['obrigacao_parcela_id'], [
                    'valor_pago' => $validacao['valor'],
                    'pago_em' => date('Y-m-d'),
                    'categoria_id' => $validacao['categoria_id'],
                    'descricao' => $validacao['rotulo_pagamento'] ?: ('Comprovante PIX #' . (int) $body['id']),
                    'observacao' => 'Baixa via comprovante PIX validado.',
                ], $usuarioId);
            } else {
                $lancData = [
                    'tipo' => 'entrada',
                    'categoria_id' => $validacao['categoria_id'] ?: 1,
                    'valor' => $validacao['valor'],
                    'data_lancamento' => date('Y-m-d'),
                    'descricao' => $validacao['rotulo_pagamento'] ?: 'Comprovante PIX validado',
                    'obreiro_id' => $comprovante['obreiro_id'],
                    'mes_ref' => $validacao['mes'],
                    'ano_ref' => $validacao['ano'],
                    'created_by' => $usuarioId,
                ];
                $lancModel->criar($lancData);
            }

            if ($comprovante['obreiro_id'] && (($validacao['categoria_id'] ?? null) === null || (int) $validacao['categoria_id'] === 1)) {
                $mensModel = new \App\Models\MensalidadeStatus();
                $mensModel->registrar($comprovante['obreiro_id'], $validacao['mes'], $validacao['ano'], 'pago');
            }

            echo json_encode(['ok' => true]);
            exit;
        }

        if ($requestUri === '/api/tesouraria/obrigacoes-abertas' && $method === 'GET') {
            $obreiroId = trim((string) ($_GET['obreiro_id'] ?? ''));
            if ($obreiroId === '') {
                echo json_encode(['ok' => true, 'parcelas' => []]);
                exit;
            }
            $parcelas = (new \App\Models\ObrigacaoFinanceira())->listarParcelasEmAbertoObreiro($obreiroId);
            echo json_encode(['ok' => true, 'parcelas' => $parcelas]);
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

    case "/miniapp/biblioteca":
        requireMiniappAuth(['bibliotecario', 'primeiro_vigilante', 'segundo_vigilante', 'veneravel', 'admin']);
        require_once __DIR__ . "/../src/Views/miniapp/biblioteca.php";
        break;

    case "/biblioteca/detalhes":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) {
            header("Location: /login");
            exit;
        }
        $id = (int) ($_GET['id'] ?? 0);
        (new \App\Controllers\BibliotecaController())->detalhes($id);
        break;

    case "/biblioteca/meus-emprestimos":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) {
            header("Location: /login");
            exit;
        }
        $obreiroId = trim((string) ($_SESSION['usuario_id'] ?? ''));
        (new \App\Controllers\BibliotecaController())->meusEmprestimos($obreiroId);
        break;

    case "/biblioteca/solicitar":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) {
            header("Location: /login");
            exit;
        }
        $acervoId = (int) ($_POST['acervo_id'] ?? 0);
        $obreiroId = trim((string) ($_SESSION['usuario_id'] ?? ''));
        (new \App\Controllers\BibliotecaController())->solicitar($acervoId, $obreiroId);
        break;

    case "/biblioteca/comentar":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) {
            header("Location: /login");
            exit;
        }
        $acervoId = (int) ($_POST['acervo_id'] ?? 0);
        $obreiroId = trim((string) ($_SESSION['usuario_id'] ?? ''));
        (new \App\Controllers\BibliotecaController())->comentar($acervoId, $obreiroId);
        break;

    case "/biblioteca/reagir":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) {
            header("Location: /login");
            exit;
        }
        $acervoId = (int) ($_POST['acervo_id'] ?? 0);
        $obreiroId = trim((string) ($_SESSION['usuario_id'] ?? ''));
        (new \App\Controllers\BibliotecaController())->reagir($acervoId, $obreiroId);
        break;

    case "/biblioteca/adicionar":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) {
            header("Location: /login");
            exit;
        }
        if (!$sessionHasRole('bibliotecario', 'admin', 'veneravel')) {
            http_response_code(403);
            echo "Acesso restrito ao Bibliotecario, Veneravel Mestre ou Administrador.";
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
            echo "Acesso restrito ao Bibliotecario, Veneravel Mestre ou Administrador.";
            exit;
        }
        $id = $method === 'POST'
            ? (int) ($_POST['id'] ?? 0)
            : (int) ($_GET['id'] ?? 0);
        (new \App\Controllers\BibliotecaController())->editar($id);
        break;

    case "/biblioteca/excluir":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) {
            header("Location: /login");
            exit;
        }
        if (!$sessionHasRole('bibliotecario', 'admin', 'veneravel')) {
            http_response_code(403);
            echo "Acesso restrito ao Bibliotecario, Veneravel Mestre ou Administrador.";
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
        if (!$sessionHasRole('bibliotecario', 'admin', 'veneravel')) {
            http_response_code(403);
            echo "Acesso restrito ao Bibliotecario, Veneravel Mestre ou Administrador.";
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
        if (!$sessionHasRole('bibliotecario', 'admin', 'veneravel')) {
            http_response_code(403);
            echo "Acesso restrito ao Bibliotecario, Veneravel Mestre ou Administrador.";
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
                        count(array_intersect($cargosAtivos, ["veneravel", "primeiro_vigilante", "segundo_vigilante", "tesoureiro", "chanceler", "admin", "bibliotecario", "mestre_banquetes", "hospitaleiro", "mestre_de_harmonia"])) > 0
                        || in_array($cargo, ["veneravel", "primeiro_vigilante", "segundo_vigilante", "secretario", "tesoureiro", "chanceler", "admin", "hospitaleiro", "mestre_de_harmonia"], true);

                    $_SESSION["usuario_logado"] = $usuario;
                    $_SESSION["usuario_id"] = $usuario["id"];
                    $_SESSION["usuario_nome"] = $resolvePublicUserName($usuario);
                    $syncSessionRoles($usuario);

                    if ($temAcessoPainel) {
                        header("Location: /dashboard");
                        exit;
                    }

                    header("Location: /biblioteca");
                    exit;
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
