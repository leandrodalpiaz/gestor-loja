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
        "á" => "a", "à" => "a", "â" => "a", "ã" => "a",
        "é" => "e", "ê" => "e",
        "í" => "i",
        "ó" => "o", "ô" => "o", "õ" => "o",
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

// ==========================================
// ROTEAMENTO PRINCIPAL
// ==========================================
switch ($requestUri) {
        // ─── Telas Antigas Restauradas ───────────────────────────────────────
        case "/obreiros":
            if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) { header("Location: /login"); exit; }
            $obreiroModel = new \App\Models\Obreiro();
            $obreiros = $obreiroModel->getAllAtivos();
            require_once __DIR__ . "/../src/Views/obreiros.php";
            break;

        case "/tesouraria/caixa":
            if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) { header("Location: /login"); exit; }
            require_once __DIR__ . "/../src/Views/tesouraria_caixa.php";
            break;

        case "/tesouraria/comprovantes":
            if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) { header("Location: /login"); exit; }
            require_once __DIR__ . "/../src/Views/tesouraria_comprovantes.php";
            break;

        case "/tesouraria/regularidade":
            if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) { header("Location: /login"); exit; }
            require_once __DIR__ . "/../src/Views/tesouraria_regularidade.php";
            break;

        case "/tesouraria/fechamento":
            if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) { header("Location: /login"); exit; }
            require_once __DIR__ . "/../src/Views/tesouraria_fechamento.php";
            break;

        case "/biblioteca/classificar":
            if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) {
                header("Location: /login");
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

        // --- BUSCANDO OS DADOS NO BANCO ANTES DE ABRIR A TELA ---
        $dadosEfemerides = $buildEfemeridesPreview();
        $registrosHoje = $dadosEfemerides['registrosHoje'];
        $registrosRecentes = $dadosEfemerides['registrosRecentes'];
        $mensagemBase = $dadosEfemerides['mensagemBase'];
        $mensagemPreview = $dadosEfemerides['mensagemPreview'];
        // --------------------------------------------------------

        require_once __DIR__ . "/../src/Views/efemerides_chanceler.php";
        break;

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
        header("Location: /chancelaria/efemerides?sucesso=enviado");
        exit;

    // ─── Tesouraria API ──────────────────────────────────────────────────
    case (preg_match('~^/api/tesouraria~', $requestUri) ? $requestUri : null):
        header('Content-Type: application/json; charset=utf-8');
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
        echo json_encode(['ok' => false, 'erro' => 'API não encontrada.']);
        exit;

    // ─── Biblioteca (Views) ──────────────────────────────────────────────
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
        (new \App\Controllers\BibliotecaController())->adicionar();
        break;

    case "/biblioteca/editar":
        if (!$openTestAccess && !isset($_SESSION["usuario_logado"])) {
            header("Location: /login");
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
    default:
        http_response_code(404);
        echo "404 - Página não encontrada.";
        break;
}