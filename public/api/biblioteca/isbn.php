<?php

declare(strict_types=1);

use App\Config\Database;
use App\Config\Env;
use App\Models\Acervo;

require_once __DIR__ . '/../../../src/autoload.php';

Env::load(__DIR__ . '/../../../.env');

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'erro' => 'Metodo nao permitido (use POST).']);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true) ?? [];
$isbn = trim((string) ($payload['isbn'] ?? ''));
if ($isbn === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'erro' => 'ISBN nao informado.']);
    exit;
}

$titulo = 'Titulo nao encontrado (editar)';
$autor = 'Autor desconhecido';
$capaUrl = null;

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
}

try {
    $db = Database::getConnection();
    $stmt = $db->prepare('SELECT id FROM acervo WHERE isbn = :isbn LIMIT 1');
    $stmt->execute(['isbn' => $isbn]);
    $existente = $stmt->fetch();

    if ($existente) {
        echo json_encode([
            'ok' => true,
            'ja_cadastrado' => true,
            'acervo_id' => (int) $existente['id'],
            'isbn' => $isbn,
            'titulo' => $titulo,
            'autor' => $autor,
            'capa_url' => $capaUrl,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $acervo = new Acervo();
    $ok = $acervo->adicionar([
        'titulo' => $titulo,
        'autor' => $autor,
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

    $stmt = $db->prepare('SELECT id FROM acervo WHERE isbn = :isbn ORDER BY id DESC LIMIT 1');
    $stmt->execute(['isbn' => $isbn]);
    $novo = $stmt->fetch();

    echo json_encode([
        'ok' => true,
        'ja_cadastrado' => false,
        'acervo_id' => $novo ? (int) $novo['id'] : null,
        'isbn' => $isbn,
        'titulo' => $titulo,
        'autor' => $autor,
        'capa_url' => $capaUrl,
    ], JSON_UNESCAPED_UNICODE);
} catch (\Throwable $e) {
    error_log('[api/biblioteca/isbn] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'erro' => 'Erro interno ao cadastrar livro por ISBN.']);
}
