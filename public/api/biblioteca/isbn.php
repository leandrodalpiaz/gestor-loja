<?php
// Endpoint: POST { "isbn": "9788575225638" }
// Retorna JSON com título, autor e URL da capa.  
// Saída sempre em UTF-8.
declare(strict_types=1);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

// -------------------------------------------------
// 1. Só aceita POST com campo isbn
// -------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'erro' => 'Método não permitido (use POST)']);
    exit;
}
$payload = json_decode(file_get_contents('php://input'), true) ?? [];
$isbn     = trim((string) ($payload['isbn'] ?? ''));

if ($isbn === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'erro' => 'ISBN não informado']);
    exit;
}

// -------------------------------------------------
// 2. Consulta Google Books
// -------------------------------------------------
$url      = 'https://www.googleapis.com/books/v1/volumes?q=isbn:' . urlencode($isbn);
$response = @file_get_contents($url);
$dataGB   = $response ? json_decode($response, true) : null;

// Valores-fallback
$result = [
    'ok'         => true,
    'isbn'       => $isbn,
    'titulo'     => 'Título não encontrado (editar)',
    'autor'      => 'Autor desconhecido',
    'capa_url'   => null,
];

// Se encontrou, preenche
if ($dataGB && isset($dataGB['items'][0]['volumeInfo'])) {
    $info = $dataGB['items'][0]['volumeInfo'];
    $result['titulo']   = $info['title']              ?? $result['titulo'];
    $result['autor']    = $info['authors'][0]         ?? $result['autor'];
    $result['capa_url'] = $info['imageLinks']['thumbnail'] ?? null;
}

echo json_encode($result, JSON_UNESCAPED_UNICODE);