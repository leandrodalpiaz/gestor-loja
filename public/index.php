<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

ini_set('default_charset', 'UTF-8');
if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
}

if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
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
use App\Core\Tenant\StoreTenantResolver;
use App\Models\AssistenteTelemetria;
use App\Models\Obreiro;
use App\Models\Cargo;
use App\Services\AssistenteFluxoService;

require_once __DIR__ . "/../src/autoload.php";

Env::load(__DIR__ . "/../.env");

// Garante o preenchimento de $_ENV em ambientes de produção (como o Render) onde as variáveis nativas estão disponíveis via getenv() mas $_ENV está vazio devido a diretiva variables_order do php.ini.
foreach ([
    'DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASS', 'DB_SCHEMA',
    'TELEGRAM_BOT_TOKEN', 'TELEGRAM_CHAT_ID_GROUP', 'TELEGRAM_CHAT_ID_CHANCELER',
    'APP_URL', 'APP_TIMEZONE', 'APP_ENV', 'APP_LOJA_NUMERO', 'APP_DEFAULT_TENANT_SLUG',
    'APP_DEFAULT_TENANT_ID', 'APP_DEFAULT_TENANT_NAME', 'APP_ALLOW_ENV_TENANT_FALLBACK',
    'APP_TEST_OPEN_ACCESS', 'APP_TEST_ALLOW_ALL_PANELS', 'SYSTEM_ADMIN_TELEGRAM_IDS'
] as $envKey) {
    if (!isset($_ENV[$envKey]) || $_ENV[$envKey] === '') {
        $val = getenv($envKey);
        if ($val !== false) {
            $_ENV[$envKey] = $val;
        }
    }
}

$requestUri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$method = $_SERVER["REQUEST_METHOD"];
if ($method === 'HEAD') {
    $method = 'GET';
}

$shouldNormalizeHtmlOutput = !str_starts_with($requestUri, '/api/')
    && !in_array($requestUri, ['/webhook', '/health'], true);
if ($shouldNormalizeHtmlOutput) {
    ob_start(static function (string $buffer): string {
        $isHtmlLike = str_contains($buffer, '<html') || str_contains($buffer, '<!DOCTYPE html');
        if (!$isHtmlLike) {
            return $buffer;
        }

        return strtr($buffer, [
            "\xC3\x83\xC2\xA1" => "\xC3\xA1",
            "\xC3\x83\xC2\xA2" => "\xC3\xA2",
            "\xC3\x83\xC2\xA3" => "\xC3\xA3",
            "\xC3\x83\xC2\xA0" => "\xC3\xA0",
            "\xC3\x83\xC2\xA9" => "\xC3\xA9",
            "\xC3\x83\xC2\xAA" => "\xC3\xAA",
            "\xC3\x83\xC2\xAD" => "\xC3\xAD",
            "\xC3\x83\xC2\xB3" => "\xC3\xB3",
            "\xC3\x83\xC2\xB4" => "\xC3\xB4",
            "\xC3\x83\xC2\xB5" => "\xC3\xB5",
            "\xC3\x83\xC2\xBA" => "\xC3\xBA",
            "\xC3\x83\xC2\xA7" => "\xC3\xA7",
            "\xC3\x83\xC2\x81" => "\xC3\x81",
            "\xC3\x83\xC2\x89" => "\xC3\x89",
            "\xC3\x83\xC2\x8D" => "\xC3\x8D",
            "\xC3\x83\xC2\x93" => "\xC3\x93",
            "\xC3\x83\xC2\x9A" => "\xC3\x9A",
            "\xC3\x83\xC2\x87" => "\xC3\x87",
            "\xC3\xA2\xC2\x80\xC2\xA2" => "\xE2\x80\xA2",
            "n\xC3\x82\xC2\xBA" => "n\xC2\xBA",
            "N\xC3\x82\xC2\xBA" => "N\xC2\xBA",
            "\xC3\x82\xC2\xB7" => "\xC2\xB7",
            "\xC3\x82\xC2\xBA" => "\xC2\xBA",
            "\xC3\x82\xC2\xAA" => "\xC2\xAA",
        ]);
    });
}
$openTestAccess = Env::bool("APP_TEST_OPEN_ACCESS", false);
// Segurança: por padrão NÃO liberar todos os painéis. Em produção isso deve ser false.
// Use APP_TEST_ALLOW_ALL_PANELS=true apenas para ambiente de teste.
$allowAllPanels = Env::bool("APP_TEST_ALLOW_ALL_PANELS", false);
$testLogin = trim((string) Env::get("APP_TEST_DEFAULT_LOGIN", ""));
$testPassword = (string) Env::get("APP_TEST_DEFAULT_PASSWORD", "");
$testRole = trim((string) Env::get("APP_TEST_DEFAULT_ROLE", "tesoureiro"));
$testDisplayName = trim((string) Env::get("APP_TEST_DEFAULT_NAME", "Modo Teste"));
$isTestSession = isset($_SESSION["usuario_id"]) && (string) $_SESSION["usuario_id"] === '0';
$bypassRoleChecks = $openTestAccess || $isTestSession || ($allowAllPanels && isset($_SESSION["usuario_logado"]));
$appEnv = strtolower(trim((string) ($_ENV['APP_ENV'] ?? '')));
if ($appEnv === '') {
    $appUrlHost = strtolower(trim((string) parse_url((string) ($_ENV['APP_URL'] ?? ''), PHP_URL_HOST)));
    $appEnv = in_array($appUrlHost, ['localhost', '127.0.0.1'], true) ? 'local' : 'production';
}
$isLocalEnvironment = in_array($appEnv, ['local', 'development', 'dev'], true);

// Guardrail: evita rodar ambientes não-prod apontando para schema de produção.
$dbSchema = strtolower(trim((string) ($_ENV['DB_SCHEMA'] ?? '')));
if ($dbSchema !== '' && $appEnv !== 'production' && $dbSchema === 'app_prod') {
    http_response_code(500);
    echo 'Configuração insegura: APP_ENV=' . htmlspecialchars($appEnv) . ' não pode usar DB_SCHEMA=app_prod.';
    exit;
}
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

$buildTenantSlug = static function (array $loja): string {
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

    return $slug !== '' ? $slug : trim((string) ($loja['id'] ?? ''));
};

$normalizeTenantToken = static function (?string $value): ?string {
    $value = trim((string) $value);
    if ($value === '') {
        return null;
    }

    $normalized = strtolower($value);
    $normalized = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $normalized) ?: $normalized;
    $normalized = preg_replace('/[^a-z0-9\-]+/', '-', $normalized) ?? '';
    $normalized = trim($normalized, '-');

    return $normalized !== '' ? $normalized : null;
};

$detectTenantFromRequest = static function () use ($normalizeTenantToken): ?string {
    $headerTenant = $_SERVER['HTTP_X_TENANT_SLUG'] ?? null;
    $queryTenant = $_GET['tenant'] ?? null;

    if ($headerTenant !== null || $queryTenant !== null) {
        return $normalizeTenantToken((string) ($headerTenant ?? $queryTenant));
    }

    $host = trim((string) ($_SERVER['HTTP_HOST'] ?? ''));
    $host = preg_replace('/:\d+$/', '', $host) ?? $host;
    $hostTenant = null;
    if ($host !== '' && !in_array($host, ['localhost', '127.0.0.1'], true)) {
        $appUrlHost = trim((string) parse_url((string) ($_ENV['APP_URL'] ?? ''), PHP_URL_HOST));
        $hasDefaultTenant = trim((string) ($_ENV['APP_DEFAULT_TENANT_ID'] ?? '')) !== ''
            || trim((string) ($_ENV['APP_DEFAULT_TENANT_SLUG'] ?? '')) !== ''
            || trim((string) ($_ENV['APP_LOJA_NUMERO'] ?? '')) !== '';

        // Render usa subdominios tecnicos por servico. Em instalacoes single-tenant,
        // esse host nao deve sobrescrever o tenant configurado por ambiente.
        if (!$hasDefaultTenant || strcasecmp($host, $appUrlHost) !== 0) {
            $parts = explode('.', $host);
            if (count($parts) >= 3) {
                $hostTenant = $parts[0] ?? null;
            }
        }
    }

    return $normalizeTenantToken((string) ($hostTenant ?? ''));
};

$tenantSessionInput = $_SESSION;
$requestTenantSlug = $detectTenantFromRequest();
if ($requestTenantSlug !== null) {
    $tenantSessionInput['tenant_slug'] = $requestTenantSlug;
}

// Em produção (Render), frequentemente o serviço é single-tenant e depende de variáveis de ambiente.
// Mantemos a flag para permitir desligar explicitamente, mas por padrão habilitamos o fallback por env
// para evitar 503 em health checks e webhooks quando o tenant não vem por sessão/host/header.
$allowEnvTenantFallback = filter_var($_ENV['APP_ALLOW_ENV_TENANT_FALLBACK'] ?? 'true', FILTER_VALIDATE_BOOL);
$tenantEnv = $_ENV;
if (!$allowEnvTenantFallback) {
    unset(
        $tenantEnv['APP_DEFAULT_TENANT_ID'],
        $tenantEnv['APP_DEFAULT_TENANT_SLUG'],
        $tenantEnv['APP_DEFAULT_TENANT_NAME'],
        $tenantEnv['APP_LOJA_NUMERO']
    );
}

$tenantContext = TenantContext::fromSessionAndEnv($tenantSessionInput, $tenantEnv);
$tenantUnresolvedMessage = 'Loja não identificada. Verifique a configuração do ambiente.';
$tenantResolved = false;
$tenantResolutionFailed = false;
$tenantResolutionError = null;

try {
    $tenantResolver = new StoreTenantResolver(Database::getConnection(), $tenantContext, $tenantEnv);
    $tenantLoja = $tenantResolver->resolveLoja();
    if (is_array($tenantLoja) && !empty($tenantLoja['id'])) {
        $tenantResolved = true;
        $_SESSION['tenant_id'] = (string) $tenantLoja['id'];
        $_SESSION['tenant_slug'] = $buildTenantSlug($tenantLoja);
        $_SESSION['tenant_name'] = trim((string) ($tenantLoja['nome'] ?? '')) !== ''
            ? (string) $tenantLoja['nome']
            : ('Loja ' . (string) ($tenantLoja['numero_loja'] ?? $tenantLoja['id']));
    }
} catch (\Throwable $e) {
    $tenantResolutionError = $e;
}

if (!$tenantResolved) {
    unset($_SESSION['tenant_id'], $_SESSION['tenant_slug'], $_SESSION['tenant_name']);
    $tenantResolutionFailed = true;

    if (!$isLocalEnvironment) {
        if (str_starts_with($requestUri, '/api/')) {
            header('Content-Type: application/json; charset=utf-8');
            JsonResponse::error($tenantUnresolvedMessage, 503);
        }

        http_response_code(503);
        echo $tenantUnresolvedMessage;
        if ($tenantResolutionError) {
            error_log('Falha ao resolver tenant: ' . $tenantResolutionError->getMessage());
        }
        exit;
    }

    $allowedWithoutTenant = in_array($requestUri, ['/login', '/health'], true);
    if (!$allowedWithoutTenant) {
        if (str_starts_with($requestUri, '/api/')) {
            header('Content-Type: application/json; charset=utf-8');
            JsonResponse::error($tenantUnresolvedMessage, 503);
        }
        http_response_code(503);
        echo $tenantUnresolvedMessage;
        if ($tenantResolutionError) {
            error_log('Falha ao resolver tenant em ambiente local: ' . $tenantResolutionError->getMessage());
        }
        exit;
    }
}

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

// Guardrail de Primeiro Acesso com Senha Provisória
if (!empty($_SESSION['exigir_nova_senha']) && !in_array($requestUri, ['/definir-senha', '/logout', '/health'], true)) {
    if (str_starts_with($requestUri, '/api/')) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Definição de nova senha obrigatória.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    header('Location: /definir-senha');
    exit;
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
    $historiaModel = new \App\Models\HistoriaMaconica();

    $timezone = new \DateTimeZone(trim((string) ($_ENV['APP_TIMEZONE'] ?? 'America/Sao_Paulo')));
    $dtHoje = new \DateTimeImmutable('today', $timezone);
    $diaHoje = (int) $dtHoje->format('d');
    $mesHoje = (int) $dtHoje->format('m');

    $registrosHoje = $registroModel->getRegistrosDoDia();
    
    // Injetar fatos de "Nossa História" no pool do dia para virarem cards
    try {
        $historiasHoje = $historiaModel->buscarPorDiaMes($diaHoje, $mesHoje, true);
        foreach ($historiasHoje as $hist) {
            $ano = $hist['ano_ref'] ?? $dtHoje->format('Y');
            $registrosHoje[] = [
                'id' => (int) ($hist['id'] ?? 0),
                'nome' => trim((string) ($hist['titulo'] ?? 'Nossa História')),
                'tipo' => 'História',
                'data_evento' => sprintf('%04d-%02d-%02d', $ano, $mesHoje, $diaHoje),
                'mensagem_custom' => trim((string) ($hist['texto'] ?? '')),
                'local' => trim((string) ($hist['fonte'] ?? '')),
                'vinculo' => 'Nossa História',
            ];
        }
    } catch (\Throwable $e) {
        error_log('Erro ao injetar historias no buildEfemeridesPreview: ' . $e->getMessage());
    }

    $registrosRecentes = $registroModel->getRecentes();
    
    // Sincronização: Incorporar overrides do texto dos cards na mensagem consolidada
    try {
        $previaCardModel = new \App\Models\EfemerideCardPrevia();
        $overrides = $previaCardModel->findByDate($dtHoje->format('Y-m-d'));
        $mapOverrides = [];
        foreach ($overrides as $ov) {
            $rid = (int) ($ov['registro_id'] ?? 0);
            if ($rid > 0 && !empty($ov['texto_custom_card'])) {
                $mapOverrides[$rid] = trim((string) $ov['texto_custom_card']);
            }
        }
        
        if (!empty($mapOverrides)) {
            foreach ($registrosHoje as &$regRef) {
                $rid = (int) ($regRef['id'] ?? 0);
                if ($rid > 0 && isset($mapOverrides[$rid])) {
                    // Atualiza a mensagem que será consumida pelo composer
                    $regRef['mensagem_custom'] = $mapOverrides[$rid];
                }
            }
            unset($regRef);
        }
    } catch (\Throwable $e) {
        error_log('Falha ao aplicar overrides no buildEfemeridesPreview: ' . $e->getMessage());
    }

    $mensagemBase = $composer->composeDailyPreview($registrosHoje);
    $mensagemPreview = $previaModel->garantirPreviaDoDia($mensagemBase);
    $cards = [];
    $hoje = $dtHoje->format('Y-m-d');
    $cards = (new \App\Services\EfemeridesCardService())->buildCardsForDate($hoje, $registrosHoje);
    $categoriasCards = array_values(array_unique(array_filter(array_map(static fn(array $c): string => (string) ($c['categoria'] ?? ''), $cards))));

    return [
        'registrosHoje' => $registrosHoje,
        'registrosRecentes' => $registrosRecentes,
        'mensagemBase' => $mensagemBase,
        'mensagemPreview' => $mensagemPreview,
        'cardsEnabled' => true,
        'cards' => $cards,
        'categoriasCards' => $categoriasCards,
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

    // "Admin tecnico" (fora do RBAC da loja) so existe via login tecnico (SYSTEM_ADMIN_WEB_*) ou via flag de Telegram.
    // Obreiros nunca devem herdar "admin" por cadastro/cargo, para evitar vinculo do admin a um membro.
    $isSystemAdmin = !empty($_SESSION['force_system_admin']) || !empty($usuario['is_system_admin']) || !empty($_SESSION['is_system_admin']);
    $_SESSION['is_system_admin'] = $isSystemAdmin;

    $slugsEfetivos = array_values(array_unique($slugs));

    if ($isSystemAdmin) {
        if (!in_array('admin', $slugsEfetivos, true)) {
            $slugsEfetivos[] = 'admin';
        }
    } else {
        $slugsEfetivos = array_values(array_filter(
            $slugsEfetivos,
            static fn (string $slug): bool => $slug !== 'admin'
        ));

        // Regra de acesso: todo obreiro autenticado herda o papel-base "obreiro".
        if (!in_array('obreiro', $slugsEfetivos, true)) {
            $slugsEfetivos[] = 'obreiro';
        }
    }

    $fallbackEfetivo = $fallback;
    if (!$isSystemAdmin && $fallbackEfetivo === 'admin') {
        $fallbackEfetivo = 'obreiro';
    }

    if ($slugsEfetivos === [] && $fallbackEfetivo !== '') {
        $slugsEfetivos = [$fallbackEfetivo];
    }

    $principal = Cargo::resolverCargoPrincipal($slugsEfetivos, $fallbackEfetivo);

    if (isset($_SESSION['usuario_logado']) && is_array($_SESSION['usuario_logado'])) {
        $_SESSION['usuario_logado']['cargo'] = $principal;
        $_SESSION['usuario_logado']['cargos'] = $slugsEfetivos;
    }

    $_SESSION['usuario_cargo'] = $principal;
    $_SESSION['usuario_cargos'] = $slugsEfetivos;
    $_SESSION['usuario_cargos_codigos'] = $codigos;

    return [$principal, $slugsEfetivos, $codigos];
};

$syncTenantSessionFromObreiro = static function (?array $usuario = null) use ($buildTenantSlug): void {
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

    $_SESSION['tenant_id'] = (string) $loja['id'];
    $_SESSION['tenant_slug'] = $buildTenantSlug($loja);
    $_SESSION['tenant_name'] = trim((string) ($loja['nome'] ?? '')) !== ''
        ? (string) $loja['nome']
        : 'Loja ' . (string) ($loja['numero_loja'] ?? $loja['id']);
};

$sessionHasRole = static function (string ...$roles) use (&$authorizer, $normalizeRole): bool {
    $roles = array_map($normalizeRole, $roles);
    return $authorizer->hasRole(...$roles);
};

$getSessionRoles = static function () use (&$authorizer): array {
    return $authorizer->roles();
};

$sessionHasPermission = static function (string $permission) use (&$authorizer): bool {
    return $authorizer->hasPermission($permission);
};

$requirePermission = static function (string $permission, string $message = 'Esta ação segue regras de responsabilidade da Loja.') use ($sessionHasPermission): void {
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

    $requirePermission('tesouraria.manage', "Esta ação é realizada pela Tesouraria.");
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
        $jsonError('Esta ação é realizada pela Tesouraria.', 403);
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

$bootstrapLoginDone = false;
if (!isset($_SESSION['usuario_logado'])) {
    $bootstrapInitData = trim((string) ($_GET['init_data'] ?? $_GET['initData'] ?? ''));
    if ($bootstrapInitData !== '') {
        $bootstrapObreiro = $resolveObreiroByInitData($bootstrapInitData);
        if ($bootstrapObreiro) {
            $loginTelegramObreiroInSession($bootstrapObreiro);
            $bootstrapLoginDone = true;
        }
    }
}

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

if ($bootstrapLoginDone) {
    $currentUser = new CurrentUser($_SESSION, $normalizeRole);
    $authorizer = new Authorizer($currentUser, $permissionMap, $bypassRoleChecks);
    $GLOBALS['gestor_loja_permission_map'] = $permissionMap;
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

        // Admin do sistema nao e cargo oficial, mas deve ter acesso total aos miniapps.
        // Mantemos isso via flag tecnica is_system_admin (sem "admin" aparecer como cargo).
        if (!empty($_SESSION['is_system_admin']) && isset($_SESSION['usuario_logado'])) {
            return is_array($_SESSION['usuario_logado']) ? $_SESSION['usuario_logado'] : [];
        }

        $allowedRoles = array_values(array_unique(array_filter(array_map($normalizeRole, $allowedRoles))));
        $sessionRoles = array_values(array_unique(array_filter(array_map(
            $normalizeRole,
            $_SESSION['usuario_cargos'] ?? [$_SESSION['usuario_cargo'] ?? '']
        ))));
        if (!in_array('obreiro', $sessionRoles, true)) {
            $sessionRoles[] = 'obreiro';
        }

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

        if (!empty($miniappObreiro['is_system_admin'])) {
            return $miniappObreiro;
        }

        $miniappRoles = array_values(array_unique(array_filter(array_map(
            $normalizeRole,
            $miniappObreiro['cargos'] ?? [$miniappObreiro['cargo_principal'] ?? $miniappObreiro['cargo'] ?? '']
        ))));
        if (!in_array('obreiro', $miniappRoles, true)) {
            $miniappRoles[] = 'obreiro';
        }

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
            echo 'Este miniapp está disponível conforme sua função ativa na Loja.';
            exit;
        }

        return $miniappObreiro;
    }
}

// ==========================================
// Endpoint para preparar previa de efemerides (Chanceler e Admin)
if ($requestUri === '/api/cron/preparar-previa') {
    if ($_SERVER['REQUEST_METHOD'] === 'HEAD') {
        exit;
    }
    if ($method !== 'GET') {
        http_response_code(405);
        exit;
    }
    $token = $_GET['token'] ?? '';
    $tokenEsperado = trim((string) (Env::get('CRON_EFEMERIDES_TOKEN') ?: Env::get('CRON_SECRET_TOKEN') ?: ''));
    if ($tokenEsperado === '') {
        $tokenEsperado = 'SUA_SENHA_SECRETA';
    }
    if ($token !== $tokenEsperado) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'erro', 'mensagem' => 'Token invalido'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    require_once __DIR__ . '/../src/Models/EfemerideRegistro.php';
    require_once __DIR__ . '/../src/Models/EfemeridePreviaDiaria.php';
    require_once __DIR__ . '/../src/Services/EfemeridesComposer.php';
    require_once __DIR__ . '/../src/Bot/TelegramClient.php';
    require_once __DIR__ . '/../src/Services/EfemeridesCardService.php';
    require_once __DIR__ . '/../src/Models/Obreiro.php';
    require_once __DIR__ . '/../src/Models/HistoriaMaconica.php';

    $registroModel = new \App\Models\EfemerideRegistro();
    $historiaModel = new \App\Models\HistoriaMaconica();
    $composer = new \App\Services\EfemeridesComposer();
    $previaModel = new \App\Models\EfemeridePreviaDiaria();

    $timezone = new \DateTimeZone('America/Sao_Paulo');
    $dtHoje = new \DateTimeImmutable('today', $timezone);
    $hoje = $dtHoje->format('Y-m-d');
    $diaHoje = (int) $dtHoje->format('d');
    $mesHoje = (int) $dtHoje->format('m');

    $registrosHoje = $registroModel->getRegistrosDoDia();
    try {
        $historiasHoje = $historiaModel->buscarPorDiaMes($diaHoje, $mesHoje, true);
        foreach ($historiasHoje as $hist) {
            $ano = $hist['ano_ref'] ?? $dtHoje->format('Y');
            $registrosHoje[] = [
                'id' => (int) ($hist['id'] ?? 0),
                'nome' => trim((string) ($hist['titulo'] ?? 'Nossa História')),
                'tipo' => 'História',
                'data_evento' => sprintf('%04d-%02d-%02d', $ano, $mesHoje, $diaHoje),
                'mensagem_custom' => trim((string) ($hist['texto'] ?? '')),
                'local' => trim((string) ($hist['fonte'] ?? '')),
                'vinculo' => 'Nossa História',
            ];
        }
    } catch (\Throwable $e) {
        error_log('public/index.php: erro ao buscar historias: ' . $e->getMessage());
    }

    $mensagemBase = $composer->composeDailyPreview($registrosHoje);
    $ok = $previaModel->prepararAutomaticaDoDia($mensagemBase);
    if (!$ok) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'erro', 'mensagem' => 'Falha ao preparar previa diaria no banco'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $targetChatIds = [];

    // 1. Chanceler do .env
    $envChanceler = trim((string) Env::get('TELEGRAM_CHAT_ID_CHANCELER'));
    if ($envChanceler !== '') {
        $targetChatIds[] = $envChanceler;
    }

    // 2. Chanceleres ativos do banco de dados
    try {
        $obreiroModel = new \App\Models\Obreiro();
        $obreiros = $obreiroModel->getAllAtivos();
        foreach ($obreiros as $ob) {
            $cargos = is_array($ob['cargos'] ?? null) ? $ob['cargos'] : [];
            if (in_array('chanceler', $cargos, true) || ($ob['cargo_principal'] ?? '') === 'chanceler') {
                $telId = trim((string) ($ob['telegram_id'] ?? ''));
                if ($telId !== '') {
                    $targetChatIds[] = $telId;
                }
            }
        }
    } catch (\Throwable $e) {
        error_log("api/cron/preparar-previa: erro ao buscar chanceleres: " . $e->getMessage());
    }

    // 3. Admins do .env
    $envAdmins = trim((string) Env::get('SYSTEM_ADMIN_TELEGRAM_IDS'));
    if ($envAdmins !== '') {
        $adminIds = preg_split('/\s*,\s*/', $envAdmins, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        foreach ($adminIds as $adminId) {
            $adminId = trim((string) $adminId);
            if ($adminId !== '') {
                $targetChatIds[] = $adminId;
            }
        }
    }

    $targetChatIds = array_values(array_unique(array_filter($targetChatIds)));
    $disparos = [];

    $telegramClient = new \App\Bot\TelegramClient();
    $cardService = new \App\Services\EfemeridesCardService();

    if (!empty($targetChatIds)) {
        foreach ($targetChatIds as $chatId) {
            $disparos[$chatId] = 'sucesso';

            if (empty($registrosHoje)) {
                $telegramClient->sendMessage($chatId, "Nenhuma efeméride para hoje.");
                continue;
            }

            foreach ($registrosHoje as $reg) {
                $textoReg = $composer->composeDailyPreview([$reg]);
                
                // Gerar cartão
                $cards = $cardService->buildCardsForDate($hoje, [$reg]);
                $card = !empty($cards) ? $cards[0] : null;
                $cardPath = $card['card_path'] ?? '';

                // Botões
                $source = ($reg['tipo'] === 'História') ? 'his' : 'reg';
                $id = $reg['id'];
                $keyboard = [
                    'inline_keyboard' => [
                        [
                            ['text' => '📄 Enviar Texto', 'callback_data' => "ef_tx_{$source}_{$id}"],
                            ['text' => '🖼️ Enviar Cartão', 'callback_data' => "ef_cd_{$source}_{$id}"],
                        ],
                        [
                            ['text' => '✨ Enviar Texto + Cartão', 'callback_data' => "ef_bo_{$source}_{$id}"],
                        ],
                        [
                            ['text' => '❌ Não enviar', 'callback_data' => "ef_no_{$source}_{$id}"],
                        ]
                    ]
                ];

                if ($cardPath !== '' && file_exists($cardPath)) {
                    $res = $telegramClient->sendPhoto($chatId, $cardPath, $textoReg, ['reply_markup' => $keyboard]);
                    if (!$res) {
                        $disparos[$chatId] = 'falha ao enviar foto';
                    }
                } else {
                    $res = $telegramClient->sendMessage($chatId, $textoReg, ['reply_markup' => $keyboard]);
                    if (!$res) {
                        $disparos[$chatId] = 'falha ao enviar texto';
                    }
                }
            }

            // Enviar dica final
            $dica = "💡 <b>Dica:</b> Para ajustes e correções (nomes, datas, parentescos ou motivos), faça a alteração diretamente no sistema no computador. Desta forma, a correção é salva e estará pronta no próximo envio.";
            $telegramClient->sendMessage($chatId, $dica, ['parse_mode' => 'HTML']);
        }
    }

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'status' => 'ok',
        'mensagem' => 'Previa de efemerides preparada com sucesso',
        'disparos' => $disparos
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ==========================================
// Endpoint para envio automatico de efemerides (Cron Job)
if ($requestUri === '/api/cron/efemerides-diarias') {
    if ($_SERVER['REQUEST_METHOD'] === 'HEAD') {
        exit;
    }
    if ($method !== 'GET') {
        http_response_code(405);
        exit;
    }
    $token = $_GET['token'] ?? '';
    $tokenEsperado = trim((string) (Env::get('CRON_EFEMERIDES_TOKEN') ?: Env::get('CRON_SECRET_TOKEN') ?: ''));
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
    require_once __DIR__ . '/../src/Services/EfemeridesCardService.php';

    $efemerideModel = new \App\Models\EfemerideRegistro();
    $registros = $efemerideModel->getRegistrosDoDia();
    $telegram = new \App\Bot\TelegramClient();
    $grupoId = $resolveTelegramGroupId();
    if (!$grupoId) {
        http_response_code(500);
        echo json_encode(['status' => 'erro', 'mensagem' => 'ID do grupo nao configurado']);
        exit;
    }

    // Geração de Cards Dinâmica para o Cron
    $service = new \App\Services\EfemeridesCardService();
    $hojeRef = date('Y-m-d');
    $listaCards = !empty($registros) ? $service->buildCardsForDate($hojeRef, $registros) : [];

    $contagemFotos = 0;
    if (!empty($listaCards)) {
        foreach ($listaCards as $c) {
            $absPath = $c['card_path'] ?? '';
            if ($absPath !== '' && file_exists($absPath)) {
                $desc = $c['titulo'] ?? $c['descricao'] ?? 'Efeméride';
                $telegram->sendPhoto($grupoId, $absPath, "🖼 *Card:* " . $desc);
                $contagemFotos++;
            }
        }
    }

    echo json_encode(['status' => 'ok', 'cards_enviados' => $contagemFotos]);
    exit;
}
// ROTEAMENTO PRINCIPAL
// ==========================================
if ($requestUri === '/chancelaria/certificado/gerar' && $method === 'POST') {
    $sessionAutorizada = isset($_SESSION['usuario_logado']) && $sessionHasRole('chanceler', 'veneravel', 'admin');
    $telegramObreiro = $sessionAutorizada ? null : $resolveAuthorizedTelegramObreiro('chanceler', 'veneravel', 'admin');
    if (!$sessionAutorizada && !$telegramObreiro) {
        http_response_code(403);
        echo "<div style='padding: 20px; color: red; font-family: sans-serif;'>Esta função pertence ao Chanceler.</div>";
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
            echo "Esta função pertence ao Chanceler.";
            exit;
        }
    } elseif (!$sessionHasRole('chanceler', 'veneravel', 'admin')) {
        http_response_code(403);
        echo "Esta função pertence ao Chanceler.";
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
    $contentPermissionService,
    $buildEfemeridesPreview
)) {
    return;
}

switch ($requestUri) {
    case "/api/assistente/interpretar":
        header('Content-Type: application/json; charset=utf-8');
        WebGuards::requireJsonLogin($openTestAccess, $_SESSION);

        if (!in_array($method, ['GET', 'POST'], true)) {
            JsonResponse::error('Metodo nao suportado.', 405);
        }

        $body = $method === 'POST' ? RequestBody::json() : [];
        $comando = trim((string) ($body['comando'] ?? $body['q'] ?? $_GET['q'] ?? ''));
        $assistenteService = new AssistenteFluxoService();
        $resultado = $assistenteService->interpretar($comando, $sessionHasPermission);

        $obreiroModel = new Obreiro();
        $resultado = $assistenteService->aplicarDesambiguacaoMensalidade(
            $resultado,
            $comando,
            static function (string $nomeBusca) use ($obreiroModel): array {
                $ativos = $obreiroModel->buscarAtivosPorNome($nomeBusca, 8);
                return array_map(static function (array $item): array {
                    return [
                        'id' => (string) ($item['id'] ?? ''),
                        'nome' => (string) ($item['nome_historico'] ?? $item['nome'] ?? ''),
                        'cim' => (string) ($item['cim'] ?? ''),
                    ];
                }, $ativos);
            }
        );

        try {
            (new AssistenteTelemetria())->registrar([
                'canal' => 'web',
                'comando_original' => $comando,
                'comando_normalizado' => preg_replace('/\s+/', ' ', strtolower(trim((string) iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $comando)))) ?: strtolower(trim($comando)),
                'intent' => (string) ($resultado['intent'] ?? 'desconhecida'),
                'confidence' => (float) ($resultado['confidence'] ?? 0),
                'allowed' => !empty($resultado['allowed']),
                'action_target' => (string) (($resultado['action']['target'] ?? null) ?: ''),
                'user_id' => (string) ($_SESSION['usuario_id'] ?? ''),
                'tenant_id' => (string) ($_SESSION['tenant_id'] ?? ''),
                'metadata' => [
                    'needs_disambiguation' => !empty($resultado['needs_disambiguation']),
                ],
            ]);
        } catch (\Throwable $e) {
            error_log('[assistente.telemetria] falha ao registrar evento web: ' . $e->getMessage());
        }

        error_log('[assistente.web] user=' . (string) ($_SESSION['usuario_id'] ?? 'anon')
            . ' intent=' . (string) ($resultado['intent'] ?? '-')
            . ' allowed=' . ((bool) ($resultado['allowed'] ?? false) ? '1' : '0'));

        JsonResponse::send(['ok' => true, 'resultado' => $resultado]);

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
        $tenantSlug = trim((string) ($_SESSION['tenant_slug'] ?? ''));
        $tenantName = trim((string) ($_SESSION['tenant_name'] ?? ''));
        $tenantResolved = !$tenantResolutionFailed && $tenantSlug !== '';
        $tenantUnavailableMessage = $tenantUnresolvedMessage;
        $publicAdsEnabled = filter_var($_ENV['PUBLIC_ADS_ENABLED'] ?? 'false', FILTER_VALIDATE_BOOL);

        if ($tenantResolved) {
            try {
                $conteudoModel = new \App\Models\ConteudoPublico();
                $publicConteudos = $conteudoModel->listarPublicos(null, 10);
                $publicAds = $publicAdsEnabled ? $conteudoModel->listarAdsPublicos(3) : [];
            } catch (\Throwable $e) {
                $publicConteudos = [];
                $publicAds = [];
            }
        }

        if ($publicConteudos === []) {
            $publicConteudos = [
                [
                    'tipo' => 'agenda',
                    'titulo' => 'Sessão Magna Branca - Programação Pública',
                    'resumo' => 'Encontro de caráter cultural e fraterno com participação da comunidade convidada.',
                    'inicio_em' => '15/05/2026',
                    'link_url' => 'https://www.glojars.org.br/',
                ],
                [
                    'tipo' => 'noticia',
                    'titulo' => 'Ações de solidariedade em destaque no trimestre',
                    'resumo' => 'Painel de impacto social com foco em arrecadações e apoio institucional.',
                    'inicio_em' => '10/05/2026',
                    'link_url' => 'https://www.gob.org.br/',
                ],
                [
                    'tipo' => 'comunicado',
                    'titulo' => 'Calendário de atividades públicas da Loja',
                    'resumo' => 'Programação oficial em atualização contínua para visitantes e convidados.',
                    'inicio_em' => '06/05/2026',
                    'link_url' => 'https://www.gorgs.org.br/',
                ],
                [
                    'tipo' => 'agenda',
                    'titulo' => 'Palestra aberta: ética, cidadania e fraternidade',
                    'resumo' => 'Espaço de diálogo com convidados para aproximação da Loja com a comunidade.',
                    'inicio_em' => '30/05/2026',
                    'link_url' => 'https://www.glojars.org.br/',
                ],
            ];
        }

        if ($publicAds === []) {
            $publicAdsEnabled = true;
            $publicAds = [
                [
                    'titulo' => 'Espaço reservado para publicidade institucional',
                    'resumo' => 'Sua marca pode apoiar projetos da Loja com presença discreta e qualificada.',
                    'link_url' => '#',
                    'imagem_url' => '/assets/portal/publicidade/banners/banner-reservado-01.svg',
                ],
                [
                    'titulo' => 'Apoio local de alto valor comunitário',
                    'resumo' => 'Cota de parceiro com exibição em card e banner, sem rastreadores.',
                    'link_url' => '#',
                    'imagem_url' => '/assets/portal/publicidade/cards/card-reservado-01.svg',
                ],
                [
                    'titulo' => 'Espaço premium para patrocinador',
                    'resumo' => 'Visibilidade institucional para negócios alinhados a valores de ética e serviço.',
                    'link_url' => '#',
                    'imagem_url' => '/assets/portal/publicidade/cards/card-reservado-01.svg',
                ],
            ];
        }

        if ($tenantResolutionFailed) {
            $erroLogin = $tenantUnresolvedMessage;
            require_once __DIR__ . "/../src/Views/login.php";
            break;
        }

        if ($method === "POST") {
            $matricula = $_POST["matricula"] ?? $_POST["cim"] ?? "";
            $password = $_POST["password"] ?? $_POST["senha"] ?? "";
            $acao = trim((string) ($_POST['acao'] ?? 'login'));

            if (empty($matricula) || empty($password)) {
                $erroLogin = "Informe o C.I.M. e a senha para acessar.";
            } else {
                // Login técnico (admin do sistema) - não é membro da Loja e não entra em nominata/listas.
                // Admin técnico (web): por padrão exige senha via env.
                // Aceita chaves WEB_* e, se ausentes, faz fallback para SYSTEM_ADMIN_* (compat/legacy).
                $systemAdminLogin = trim((string) (
                    $_ENV['SYSTEM_ADMIN_WEB_LOGIN']
                    ?? $_ENV['SYSTEM_ADMIN_LOGIN']
                    ?? 'adm'
                ));
                $systemAdminPassword = trim((string) (
                    $_ENV['SYSTEM_ADMIN_WEB_PASSWORD']
                    ?? $_ENV['SYSTEM_ADMIN_PASSWORD']
                    ?? ''
                ));
                if ($systemAdminPassword !== '' && $acao !== 'solicitar' && (string) $matricula === $systemAdminLogin && hash_equals($systemAdminPassword, (string) $password)) {
                    $_SESSION['force_system_admin'] = true;
                    $_SESSION['usuario_logado'] = [
                        'id' => 0,
                        'nome_historico' => 'adm',
                        'nome_completo' => 'Admin do sistema',
                        'cargo' => 'admin',
                        'cargos' => ['admin'],
                        'ativo' => true,
                    ];
                    $_SESSION['usuario_id'] = 0;
                    $_SESSION['usuario_nome'] = 'adm';
                    $syncSessionRoles($_SESSION['usuario_logado']);

                    header("Location: /dashboard");
                    exit;
                }

                $obreiroModel = new \App\Models\Obreiro();
                $gate = new AccountGate($obreiroModel);
                $estadoConta = $gate->byCim((string) $matricula);
                $estado = (string) ($estadoConta['state'] ?? 'inexistente');

                if ($acao === 'solicitar') {
                    $solicitacao = $obreiroModel->solicitarAcessoPorCim((string) $matricula, (string) $password);
                    if (!($solicitacao['ok'] ?? false)) {
                        $erroLogin = "Procure a Secretaria para regularizar seu registro.";
                    } else {
                        $erroLogin = "Solicitação de acesso registrada. Aguarde a aprovação do Secretário.";
                    }
                    require_once __DIR__ . "/../src/Views/login.php";
                    break;
                }

                if ($estado === 'inexistente') {
                    $erroLogin = "Procure a Secretaria para regularizar seu registro.";
                } elseif ($estado === 'pendente') {
                    $erroLogin = "Seu acesso está em análise. Aguarde a aprovação do Secretário.";
                } elseif ($estado === 'inativo') {
                    $erroLogin = "Seu registro está Afastado. Procure a Secretaria.";
                } else {
                    $usuario = $obreiroModel->autenticar((string) $matricula, (string) $password);
                    if (!$usuario) {
                        $erroLogin = "C.I.M. ou senha inválidos.";
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

                    if (!empty($usuario['primeiro_acesso_provisorio'])) {
                        $_SESSION['exigir_nova_senha'] = true;
                        header("Location: /definir-senha");
                        exit;
                    }

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

    case "/definir-senha":
        if (!isset($_SESSION['usuario_logado'])) {
            header("Location: /login");
            exit;
        }

        if (empty($_SESSION['exigir_nova_senha'])) {
            header("Location: /dashboard");
            exit;
        }

        $erroDefinirSenha = null;
        $tenantSlug = trim((string) ($_SESSION['tenant_slug'] ?? ''));
        $tenantName = trim((string) ($_SESSION['tenant_name'] ?? ''));

        if ($method === "POST") {
            $novaSenha = $_POST['nova_senha'] ?? '';
            $confirmarSenha = $_POST['confirmar_senha'] ?? '';

            if (strlen($novaSenha) < 6) {
                $erroDefinirSenha = "A nova senha deve possuir pelo menos 6 caracteres.";
            } elseif ($novaSenha !== $confirmarSenha) {
                $erroDefinirSenha = "As senhas não coincidem. Digite novamente.";
            } else {
                $obreiroModel = new \App\Models\Obreiro();
                $usuarioId = $_SESSION['usuario_logado']['id'] ?? '';
                
                if ($obreiroModel->atualizarSenha($usuarioId, $novaSenha)) {
                    unset($_SESSION['exigir_nova_senha']);
                    
                    $usuarioAtualizado = $obreiroModel->findById($usuarioId);
                    if ($usuarioAtualizado) {
                        $_SESSION["usuario_logado"] = $usuarioAtualizado;
                    }

                    $cargo = $normalizeRole((string) ($_SESSION['usuario_logado']['cargo_principal'] ?? $_SESSION['usuario_logado']['cargo'] ?? ''));
                    $cargosAtivos = array_values(array_unique(array_filter(array_map(
                        $normalizeRole,
                        $_SESSION['usuario_logado']['cargos'] ?? [$cargo]
                    ))));
                    $temAcessoPainel =
                        count(array_intersect($cargosAtivos, ["veneravel", "primeiro_vigilante", "segundo_vigilante", "tesoureiro", "chanceler", "admin", "bibliotecario", "mestre_banquetes", "hospitaleiro", "mestre_de_harmonia"])) > 0
                        || in_array($cargo, ["veneravel", "primeiro_vigilante", "segundo_vigilante", "secretario", "tesoureiro", "chanceler", "admin", "hospitaleiro", "mestre_de_harmonia"], true);

                    if ($temAcessoPainel) {
                        header("Location: /dashboard");
                    } else {
                        header("Location: /biblioteca");
                    }
                    exit;
                } else {
                    $erroDefinirSenha = "Falha ao gravar a nova senha. Tente novamente ou procure a Secretaria.";
                }
            }
        }

        require_once __DIR__ . "/../src/Views/definir_senha.php";
        break;

    case "/logout":
        session_destroy();
        header("Location: /login");
        exit;

    case "/sistema":
        WebGuards::requireLogin($openTestAccess, $_SESSION);
        if (empty($_SESSION['is_system_admin'])) {
            WebGuards::forbidHtml('Acesso restrito ao administrador técnico del sistema.');
        }
        $assistenteResumo = [
            'dias' => 14,
            'totais' => ['total' => 0, 'total_allowed' => 0, 'total_denied' => 0, 'total_unknown' => 0],
            'top_intents' => [],
            'top_comandos' => [],
            'erros_frequentes' => [],
            'top_telas' => [],
        ];
        try {
            $assistenteResumo = (new AssistenteTelemetria())->resumo(14);
        } catch (\Throwable $e) {
            error_log('[assistente.metricas] falha ao gerar resumo: ' . $e->getMessage());
        }
        require_once __DIR__ . "/../src/Views/sistema/index.php";
        break;
    default:
        http_response_code(404);
        echo "404 - Pagina nao encontrada.";
        break;
}
