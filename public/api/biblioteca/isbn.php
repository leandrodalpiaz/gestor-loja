<?php

declare(strict_types=1);

session_start();

use App\Config\Database;
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
    echo json_encode(['ok' => false, 'erro' => 'Metodo nao permitido (use POST).']);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true) ?? [];
// Compatibilidade: alguns clientes enviam form-data (POST) em vez de JSON.
if (!empty($_POST) && is_array($_POST)) {
    $payload = array_merge($payload, $_POST);
}
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
    $initData = trim((string) ($payload['initData'] ?? $payload['init_data'] ?? ''));
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
    echo json_encode(['ok' => false, 'erro' => 'Acesso restrito a Biblioteca, Administrador ou Veneravel Mestre.']);
    exit;
}

$isbn = trim((string) ($payload['isbn'] ?? $payload['ISBN'] ?? ''));
if ($isbn === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'erro' => 'ISBN nao informado.']);
    exit;
}

$titulo = 'Titulo nao encontrado (editar)';
$autor = 'Autor desconhecido';
$capaUrl = null;
$resumo = null;

$url = 'https://www.googleapis.com/books/v1/volumes?q=isbn:' . urlencode($isbn);
$response = @file_get_contents($url);
$google = $response ? json_decode($response, true) : null;

if (is_array($google) && isset($google['items'][0]['volumeInfo'])) {
    $info = $google['items'][0]['volumeInfo'];
    $titulo = (string) ($info['title'] ?? $titulo);
    $autor = isset($info['authors']) && is_array($info['authors']) && !empty($info['authors'])
        ? implode(', ', $info['authors'])
        : $autor;
    $capaUrl = $info['imageLinks']['thumbnail'] ?? null;
    $resumo = $info['description'] ?? null;
}

try {
    $db = Database::getConnection();
    $stmt = $db->prepare('SELECT id, codigo_acervo, resumo, capa_url, titulo, autor FROM acervo WHERE isbn = :isbn AND ativo = TRUE LIMIT 1');
    $stmt->execute(['isbn' => $isbn]);
    $existente = $stmt->fetch();

    if ($existente) {
        echo json_encode([
            'ok' => true,
            'ja_cadastrado' => true,
            'acervo_id' => (int) $existente['id'],
            'codigo_acervo' => (string) ($existente['codigo_acervo'] ?? ''),
            'isbn' => $isbn,
            'titulo' => (string) ($existente['titulo'] ?? $titulo),
            'autor' => (string) ($existente['autor'] ?? $autor),
            'capa_url' => (string) ($existente['capa_url'] ?? $capaUrl),
            'resumo' => (string) ($existente['resumo'] ?? $resumo ?? ''),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $acervo = new Acervo();
    $ok = $acervo->adicionar([
        'titulo' => $titulo,
        'autor' => $autor,
        'resumo' => $resumo,
        'tipo' => 'Livro Fisico',
        'grau_restricao' => 1,
        'arquivo_url' => null,
        'quantidade_disponivel' => 1,
        'isbn' => $isbn,
        'capa_url' => $capaUrl,
        'grau_recomendado' => 'Livre',
        'nota_instrucao' => null,
        'curador_id' => null,
    ]);

    if (!$ok) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'erro' => 'Falha ao salvar livro no acervo.']);
        exit;
    }

    $stmt = $db->prepare('SELECT id, codigo_acervo, resumo FROM acervo WHERE isbn = :isbn ORDER BY id DESC LIMIT 1');
    $stmt->execute(['isbn' => $isbn]);
    $novo = $stmt->fetch();

    echo json_encode([
        'ok' => true,
        'ja_cadastrado' => false,
        'acervo_id' => $novo ? (int) $novo['id'] : null,
        'codigo_acervo' => $novo ? (string) ($novo['codigo_acervo'] ?? '') : '',
        'isbn' => $isbn,
        'titulo' => $titulo,
        'autor' => $autor,
        'capa_url' => $capaUrl,
        'resumo' => (string) ($novo['resumo'] ?? $resumo ?? ''),
    ], JSON_UNESCAPED_UNICODE);
} catch (\Throwable $e) {
    error_log('[api/biblioteca/isbn] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'erro' => 'Erro interno ao cadastrar livro por ISBN.']);
}
