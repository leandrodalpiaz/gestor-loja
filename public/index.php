<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$_SESSION = $_SESSION ?? [];

use App\Config\Env;
use App\Config\Database;
use App\Core\Auth\CurrentUser;
use App\Core\Auth\AccountGate;
use App\Core\Http\AdminRoutes;
use App\Core\Http\AssistenciaRoutes;
use App\Core\Http\BibliotecaRoutes;
use App\Core\Http\ChancelariaRoutes;
use App\Core\Http\JsonResponse;
use App\Core\Http\MestreHarmoniaRoutes;
use App\Core\Http\MiniappApiRoutes;
use App\Core\Http\MiniappPageRoutes;
use App\Core\Http\ModuleGuards;
use App\Core\Http\ObreirosRoutes;
use App\Core\Http\PainelRoutes;
use App\Core\Http\RequestBody;
use App\Core\Http\SecretariaRoutes;
use App\Core\Http\TesourariaApiRoutes;
use App\Core\Http\TesourariaRoutes;
use App\Core\Http\VigilanciaRoutes;
use App\Core\Http\WebGuards;
use App\Core\Authorization\Authorizer;
use App\Core\Authorization\PermissionMap;
use App\Core\Tenant\TenantContext;
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

    // Aliases legados -> codigos canonicos (RBAC).
    // Importante para compatibilidade com cargos antigos (ex.: "Administrador").
    $aliases = [
        'administrador' => 'admin',
        'administracao' => 'admin',
        'adm' => 'admin',
        'veneravelmestre' => 'veneravel',
        'vm' => 'veneravel',
    ];

    return $aliases[$cargo] ?? $cargo;
};

$tenantContext = TenantContext::fromSessionAndEnv($_SESSION, $_ENV);
$_SESSION = array_merge($_SESSION, array_filter(
    $tenantContext->toSessionPayload(),
    static fn ($value) => $value !== null && $value !== ''
));

$currentUser = new CurrentUser($_SESSION, $normalizeRole);
$permissionMap = new PermissionMap();
$authorizer = new Authorizer($currentUser, $permissionMap, $bypassRoleChecks);
$GLOBALS['gestor_loja_normalize_role'] = $normalizeRole;
$GLOBALS['gestor_loja_permission_map'] = $permissionMap;

if (isset($_SESSION['usuario_logado']) && !in_array($requestUri, ['/login', '/logout', '/health'], true)) {
    $statusSessao = strtolower(trim((string) ($_SESSION['usuario_logado']['acesso_status'] ?? '')));
    if ($statusSessao === '') {
        $statusSessao = !empty($_SESSION['usuario_logado']['ativo']) ? 'ativo' : 'inativo';
    }

    if ($statusSessao !== 'ativo') {
        session_destroy();
        header('Location: /login');
        exit;
    }
}

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

    if ($tipo !== '' && $tipo !== 'HistÃ³ria') {
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

    // System admin (fora do RBAC da loja): ganha permissao total sem virar "cargo principal" da loja.
    $systemAdminTelegramIds = array_values(array_filter(array_map(
        static fn ($value) => trim($value),
        preg_split('/\s*,\s*/', (string) ($_ENV['SYSTEM_ADMIN_TELEGRAM_IDS'] ?? ''), -1, PREG_SPLIT_NO_EMPTY) ?: []
    )));
    $telegramIdSessao = (string) ($usuario['telegram_id'] ?? $_SESSION['usuario_logado']['telegram_id'] ?? '');
    $isSystemAdmin = $telegramIdSessao !== '' && in_array($telegramIdSessao, $systemAdminTelegramIds, true);
    $_SESSION['is_system_admin'] = $isSystemAdmin;

    $slugsEfetivos = $slugs;
    if ($isSystemAdmin && !in_array('admin', $slugsEfetivos, true)) {
        $slugsEfetivos[] = 'admin';
    }

    if (isset($_SESSION['usuario_logado']) && is_array($_SESSION['usuario_logado'])) {
        $_SESSION['usuario_logado']['cargo'] = $principal;
        $_SESSION['usuario_logado']['cargos'] = $slugsEfetivos;
    }

    $_SESSION['usuario_cargo'] = $principal;
    $_SESSION['usuario_cargos'] = $slugsEfetivos;
    $_SESSION['usuario_cargos_codigos'] = $codigos;

    return [$principal, $slugsEfetivos, $codigos];
};

$syncTenantSessionFromObreiro = static function (?array $usuario = null): void {
    $usuario = $usuario ?? ($_SESSION['usuario_logado'] ?? null);
    $lojaId = isset($usuario['loja_id']) ? (int) $usuario['loja_id'] : 0;

    if ($lojaId <= 0) {
        return;
    }

    try {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            "SELECT id, numero_loja, sigla, nome
             FROM public.lojas
             WHERE id = :id
             LIMIT 1"
        );
        $stmt->execute(['id' => $lojaId]);
        $loja = $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    } catch (\Throwable $e) {
        error_log('Falha ao sincronizar tenant da sessao: ' . $e->getMessage());
        return;
    }

    if (!$loja) {
        return;
    }

    $slugBase = trim((string) ($loja['sigla'] ?? ''));
    if ($slugBase === '') {
        $slugBase = trim((string) ($loja['numero_loja'] ?? ''));
    }
    if ($slugBase === '') {
        $slugBase = trim((string) ($loja['nome'] ?? ''));
    }

    $slug = strtolower($slugBase);
    $slug = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $slug) ?: $slug;
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
    $slug = trim($slug, '-');

    $_SESSION['tenant_id'] = (string) $loja['id'];
    $_SESSION['tenant_slug'] = $slug !== '' ? $slug : (string) $loja['id'];
    $_SESSION['tenant_name'] = trim((string) ($loja['nome'] ?? '')) !== ''
        ? (string) $loja['nome']
        : 'Loja ' . (string) ($loja['numero_loja'] ?? $loja['id']);
};

$sessionHasRole = static function (string ...$roles) use ($authorizer, $normalizeRole): bool {
    $roles = array_map($normalizeRole, $roles);
    return $authorizer->hasRole(...$roles);
};

$getSessionRoles = static function () use ($authorizer): array {
    return $authorizer->roles();
};

$sessionHasPermission = static function (string $permission) use ($authorizer): bool {
    return $authorizer->hasPermission($permission);
};

$requirePermission = static function (string $permission, string $message = 'Acesso restrito.') use ($sessionHasPermission): void {
    WebGuards::requirePermission($sessionHasPermission($permission), $message);
};

$requireLogin = static function () use ($openTestAccess): void {
    WebGuards::requireLogin($openTestAccess, $_SESSION);
};

$requireBibliotecaAccess = static function () use (
    $openTestAccess,
    $authorizer
): void {
    ModuleGuards::requireBibliotecaAccess($openTestAccess, $_SESSION, $authorizer);
};

$requireBibliotecaManageAccess = static function () use (
    $openTestAccess,
    $authorizer
): void {
    ModuleGuards::requireBibliotecaManageAccess($openTestAccess, $_SESSION, $authorizer);
};

$requireSecretariaAccess = static function () use (
    $openTestAccess,
    $authorizer
): void {
    ModuleGuards::requireSecretariaAccess($openTestAccess, $_SESSION, $authorizer);
};

$requireObreirosViewAccess = static function () use (
    $openTestAccess,
    $authorizer
): void {
    ModuleGuards::requireObreirosViewAccess($openTestAccess, $_SESSION, $authorizer);
};

$requireObreirosManageAccess = static function () use (
    $openTestAccess,
    $authorizer
): void {
    ModuleGuards::requireObreirosManageAccess($openTestAccess, $_SESSION, $authorizer);
};

$requireAssistenciaAccess = static function () use (
    $openTestAccess,
    $authorizer
): void {
    ModuleGuards::requireAssistenciaAccess($openTestAccess, $_SESSION, $authorizer);
};

$contentPermissionService = static function (): \App\Services\ConteudoPermissaoService {
    static $service = null;
    if ($service === null) {
        $service = new \App\Services\ConteudoPermissaoService();
    }
    return $service;
};

$canManageContentCategory = static function (string $categoria) use ($bypassRoleChecks, $getSessionRoles, $contentPermissionService): bool {
    if ($bypassRoleChecks) {
        return true;
    }

    return $contentPermissionService()->canManage($categoria, $getSessionRoles());
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

$loginTelegramObreiroInSession = static function (array $obreiro) use ($syncSessionRoles, $syncTenantSessionFromObreiro, $normalizeRole, $resolvePublicUserName): void {
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
    $syncTenantSessionFromObreiro($usuario);

    $syncSessionRoles($usuario);
};

$requireTesourariaAccess = static function () use (
    $openTestAccess,
    $resolveAuthorizedTelegramObreiro,
    $loginTelegramObreiroInSession,
    $requirePermission
): void {
    if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) {
        $telegramObreiro = $resolveAuthorizedTelegramObreiro('tesoureiro', 'veneravel', 'admin');
        if (!$telegramObreiro) {
            header("Location: /login");
            exit;
        }

        $loginTelegramObreiroInSession($telegramObreiro);
    }

    $requirePermission('tesouraria.manage', "Acesso restrito ao Tesoureiro, Veneravel Mestre ou Administrador.");
};

$requireTesourariaApiAccess = static function () use (
    $openTestAccess,
    $resolveAuthorizedTelegramObreiro,
    $loginTelegramObreiroInSession,
    $sessionHasPermission,
    &$jsonError
): void {
    if (!$openTestAccess && !isset($_SESSION['usuario_logado'])) {
        $telegramObreiro = $resolveAuthorizedTelegramObreiro('tesoureiro', 'veneravel', 'admin');
        if (!$telegramObreiro) {
            $jsonError('Nao autenticado.', 401);
        }

        $loginTelegramObreiroInSession($telegramObreiro);
    }

    if (!$sessionHasPermission('tesouraria.manage')) {
        $jsonError('Acesso restrito ao Tesoureiro, Veneravel Mestre ou Administrador.', 403);
    }
};

$getJsonBody = static function (): array {
    return RequestBody::json();
};

$jsonResponse = static function (array $payload, int $status = 200): void {
    JsonResponse::send($payload, $status);
};

$jsonError = static function (string $message, int $status = 400) use ($jsonResponse): void {
    $jsonResponse(['ok' => false, 'erro' => $message], $status);
};

$requireJsonLogin = static function () use ($openTestAccess, $jsonError): void {
    WebGuards::requireJsonLogin($openTestAccess, $_SESSION);
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

if (!function_exists('requireMiniappAuth')) {
    /**
     * Mantem compatibilidade com miniapps legados por cargo e permite evolucao por permission key.
     */
    function requireMiniappAuth(array $allowedRoles, ?string $requiredPermission = null): array
    {
        $normalizeRole = $GLOBALS['gestor_loja_normalize_role'] ?? static fn ($role) => strtolower(trim((string) $role));
        /** @var PermissionMap|null $permissionMap */
        $permissionMap = $GLOBALS['gestor_loja_permission_map'] ?? null;

        $allowedRoles = array_values(array_unique(array_filter(array_map($normalizeRole, $allowedRoles))));
        $sessionRoles = array_values(array_unique(array_filter(array_map(
            $normalizeRole,
            $_SESSION['usuario_cargos'] ?? [$_SESSION['usuario_cargo'] ?? '']
        ))));

        $hasRoleAccess = false;
        foreach ($allowedRoles as $allowedRole) {
            if (in_array($allowedRole, $sessionRoles, true)) {
                $hasRoleAccess = true;
                break;
            }
        }

        $hasPermissionAccess = false;
        if ($requiredPermission !== null && $permissionMap instanceof PermissionMap) {
            $sessionPermissions = $permissionMap->permissionsForRoles($sessionRoles);
            $hasPermissionAccess = in_array('*', $sessionPermissions, true) || in_array($requiredPermission, $sessionPermissions, true);
        }

        if (isset($_SESSION['usuario_logado']) && ($hasRoleAccess || $hasPermissionAccess)) {
            return is_array($_SESSION['usuario_logado']) ? $_SESSION['usuario_logado'] : [];
        }

        $initData = trim((string) ($_GET['init_data'] ?? $_GET['initData'] ?? $_POST['init_data'] ?? $_POST['initData'] ?? ''));
        if ($initData === '') {
            // Telegram WebApp nao envia init_data na URL automaticamente; fica disponivel em JS (tg.initData).
            // Para nao bloquear a abertura do miniapp, permitimos renderizar a pagina e deixamos a API exigir initData.
            return [];
        }

        $botToken = trim((string) ($_ENV['TELEGRAM_BOT_TOKEN'] ?? ''));
        $telegramUser = \App\Services\TelegramInitDataValidator::validate($initData, $botToken);
        if ($telegramUser === null || empty($telegramUser['id'])) {
            http_response_code(401);
            echo 'Nao autenticado no miniapp.';
            exit;
        }

        $miniappObreiro = (new \App\Models\Obreiro())->findByTelegramId((int) $telegramUser['id']);
        if (!$miniappObreiro) {
            http_response_code(401);
            echo 'Nao autenticado no miniapp.';
            exit;
        }

        $miniappRoles = array_values(array_unique(array_filter(array_map(
            $normalizeRole,
            $miniappObreiro['cargos'] ?? [$miniappObreiro['cargo_principal'] ?? $miniappObreiro['cargo'] ?? '']
        ))));

        $temPermissaoMiniapp = false;
        foreach ($allowedRoles as $allowedRole) {
            if (in_array($allowedRole, $miniappRoles, true)) {
                $temPermissaoMiniapp = true;
                break;
            }
        }

        if (!$temPermissaoMiniapp && $requiredPermission !== null && $permissionMap instanceof PermissionMap) {
            $miniappPermissions = $permissionMap->permissionsForRoles($miniappRoles);
            $temPermissaoMiniapp = in_array('*', $miniappPermissions, true) || in_array($requiredPermission, $miniappPermissions, true);
        }

        if (!$temPermissaoMiniapp) {
            http_response_code(403);
            echo 'Acesso restrito para este miniapp.';
            exit;
        }

        return $miniappObreiro;
    }
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

if (BibliotecaRoutes::dispatch($requestUri, $method, $openTestAccess, $_SESSION, $authorizer)) {
    return;
}

if (AdminRoutes::dispatch($requestUri, $openTestAccess, $_SESSION, $authorizer)) {
    return;
}

if (SecretariaRoutes::dispatch($requestUri, $openTestAccess, $_SESSION, $authorizer, $sessionHasRole)) {
    return;
}

if (ObreirosRoutes::dispatch($requestUri, $method, $openTestAccess, $_SESSION, $authorizer)) {
    return;
}

if (AssistenciaRoutes::dispatch($requestUri, $openTestAccess, $_SESSION, $authorizer)) {
    return;
}

if (TesourariaRoutes::dispatch(
    $requestUri,
    $method,
    $openTestAccess,
    $_SESSION,
    $authorizer,
    $requireTesourariaAccess,
    $resolveObreiroByInitData,
    $loginTelegramObreiroInSession,
    $requirePermission
)) {
    return;
}

if (TesourariaApiRoutes::dispatch(
    $requestUri,
    $method,
    $_SESSION,
    $requireTesourariaApiAccess
)) {
    return;
}

if (VigilanciaRoutes::dispatch($requestUri, $openTestAccess, $_SESSION, $sessionHasPermission)) {
    return;
}

if (MestreHarmoniaRoutes::dispatch($requestUri, $openTestAccess, $_SESSION, $sessionHasPermission, $requireJsonLogin)) {
    return;
}

if (MiniappPageRoutes::dispatch($requestUri)) {
    return;
}

if (PainelRoutes::dispatch(
    $requestUri,
    $method,
    $openTestAccess,
    $_SESSION,
    $authorizer,
    $sessionHasRole,
    $sessionHasPermission,
    $buildEfemeridesPreview,
    $canManageContentCategory
)) {
    return;
}

if (ChancelariaRoutes::dispatch(
    $requestUri,
    $method,
    $openTestAccess,
    $_SESSION,
    $sessionHasPermission,
    $appToday,
    $buildEfemeridesPreview,
    $redirectEfemerides,
    $contentPermissionService,
    $canManageContentCategory
)) {
    return;
}

if (MiniappApiRoutes::dispatch(
    $requestUri,
    $method,
    $_SESSION,
    $sessionHasRole,
    $sessionHasPermission,
    $resolveObreiroByInitData,
    $normalizeRole,
    $permissionMap,
    $contentPermissionService
)) {
    return;
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

    case "/login":
        if ($openTestAccess) {
            header("Location: /dashboard");
            exit;
        }

        $erroLogin = null;
        $publicConteudos = [];
        $publicAds = [];
        $publicAdsEnabled = filter_var($_ENV['PUBLIC_ADS_ENABLED'] ?? 'false', FILTER_VALIDATE_BOOL);

        try {
            $conteudoModel = new \App\Models\ConteudoPublico();
            $publicConteudos = $conteudoModel->listarPublicos(null, 10);
            $publicAds = $publicAdsEnabled ? $conteudoModel->listarAdsPublicos(3) : [];
        } catch (\Throwable $e) {
            $publicConteudos = [];
            $publicAds = [];
        }

        if ($method === "POST") {
            $matricula = $_POST["matricula"] ?? $_POST["cim"] ?? "";
            $password = $_POST["password"] ?? $_POST["senha"] ?? "";
            $acao = trim((string) ($_POST['acao'] ?? 'login'));

            if (empty($matricula) || empty($password)) {
                $erroLogin = "Informe CIM e senha para acessar.";
            } else {
                $obreiroModel = new \App\Models\Obreiro();
                $gate = new AccountGate($obreiroModel);
                $estadoConta = $gate->byCim((string) $matricula);
                $estado = (string) ($estadoConta['state'] ?? 'inexistente');

                if ($acao === 'solicitar') {
                    $solicitacao = $obreiroModel->solicitarAcessoPorCim((string) $matricula, (string) $password);
                    if (!($solicitacao['ok'] ?? false)) {
                        $erroLogin = "Procure o secretario para cadastro";
                    } else {
                        $erroLogin = "Solicitacao registrada. Aguarde aprovacao do secretario/admin.";
                    }
                    require_once __DIR__ . "/../src/Views/login.php";
                    break;
                }

                if ($estado === 'inexistente') {
                    $erroLogin = "Procure o secretario para cadastro";
                } elseif ($estado === 'pendente') {
                    $erroLogin = "Seu acesso esta pendente. Aguarde aprovacao do secretario/admin.";
                } elseif ($estado === 'inativo') {
                    $erroLogin = "Seu acesso esta inativo. Procure o secretario/admin.";
                } else {
                    $usuario = $obreiroModel->autenticar((string) $matricula, (string) $password);
                    if (!$usuario) {
                        $erroLogin = "Credenciais invalidas.";
                        require_once __DIR__ . "/../src/Views/login.php";
                        break;
                    }

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
                    $syncTenantSessionFromObreiro($usuario);
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
