<?php

declare(strict_types=1);

session_start();

use App\Config\Env;
use App\Core\Auth\CurrentUser;
use App\Core\Authorization\Authorizer;
use App\Core\Authorization\PermissionMap;
use App\Models\Acervo;
use App\Models\Obreiro;
use App\Services\TelegramInitDataValidator;

require_once __DIR__ . '/../../../src/autoload.php';

Env::load(__DIR__ . '/../../../.env');

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Metodo nao permitido.']);
    exit;
}

$dados = json_decode(file_get_contents('php://input'), true) ?? [];
$normalizeRole = static function (?string $cargo): string {
    $cargo = strtolower(trim((string) $cargo));
    $cargo = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $cargo) ?: $cargo;
    $cargo = preg_replace('/[^a-z0-9_]+/', '', $cargo) ?? '';
    return $cargo;
};
$openTestAccess = filter_var($_ENV['APP_TEST_OPEN_ACCESS'] ?? 'false', FILTER_VALIDATE_BOOL);
$currentUser = new CurrentUser($_SESSION, $normalizeRole);
$permissionMap = new PermissionMap();
$authorizer = new Authorizer($currentUser, $permissionMap, $openTestAccess);

$authorized = isset($_SESSION['usuario_logado']) && $authorizer->hasPermission('biblioteca.manage');

if (!$authorized) {
    $initData = trim((string) ($dados['init_data'] ?? ''));
    $botToken = trim((string) ($_ENV['TELEGRAM_BOT_TOKEN'] ?? ''));
    $telegramUser = TelegramInitDataValidator::validate($initData, $botToken);

    if ($telegramUser) {
        $obreiro = (new Obreiro())->findByTelegramId((int) $telegramUser['id']);
        if ($obreiro) {
            $roles = array_values(array_unique(array_filter(array_map(
                $normalizeRole,
                $obreiro['cargos'] ?? [$obreiro['cargo_principal'] ?? $obreiro['cargo'] ?? '']
            ))));
            $permissions = $permissionMap->permissionsForRoles($roles);
            $authorized = in_array('*', $permissions, true) || in_array('biblioteca.manage', $permissions, true);
        }
    }
}

if (!$authorized) {
    http_response_code(403);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Acesso restrito a Biblioteca, Administrador ou Veneravel Mestre.']);
    exit;
}

$titulo = trim((string) ($dados['titulo'] ?? ''));
$autor = trim((string) ($dados['autor'] ?? ''));
if ($titulo === '' || $autor === '') {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Titulo e Autor sao obrigatorios.']);
    exit;
}

$tipoOriginal = trim((string) ($dados['tipo'] ?? 'Livro Fisico'));
$tipoMap = [
    'Livro Fisico' => 'Livro Fisico',
    'Livro Físico' => 'Livro Fisico',
    'Digital (PDF)' => 'Digital (PDF)',
    'Ritual' => 'Ritual',
];
$tipo = $tipoMap[$tipoOriginal] ?? 'Livro Fisico';

$qtd = max(1, (int) ($dados['quantidade_disponivel'] ?? 1));

$payload = [
    'titulo' => $titulo,
    'autor' => $autor,
    'resumo' => trim((string) ($dados['resumo'] ?? '')) ?: null,
    'tipo' => $tipo,
    'grau_restricao' => (int) ($dados['grau_restricao'] ?? 1),
    'arquivo_url' => trim((string) ($dados['arquivo_url'] ?? '')) ?: null,
    'quantidade_disponivel' => $qtd,
    'isbn' => trim((string) ($dados['isbn'] ?? '')) ?: null,
    'capa_url' => trim((string) ($dados['capa_url'] ?? '')) ?: null,
    'grau_recomendado' => trim((string) ($dados['grau_recomendado'] ?? 'Livre')) ?: 'Livre',
    'nota_instrucao' => trim((string) ($dados['nota_instrucao'] ?? '')) ?: null,
    'curador_id' => null,
];

try {
    $acervoModel = new Acervo();
    $ok = $acervoModel->adicionar($payload);

    if (!$ok) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Falha ao inserir livro no acervo.']);
        exit;
    }

    echo json_encode(['sucesso' => true], JSON_UNESCAPED_UNICODE);
} catch (\Throwable $e) {
    error_log('[api/biblioteca/cadastrar] ' . $e->getMessage());
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro interno ao salvar o livro.']);
}
