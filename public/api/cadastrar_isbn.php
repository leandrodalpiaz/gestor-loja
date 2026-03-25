<?php
// Oculta erros do PHP na tela para não quebrar a resposta JSON
ini_set('display_errors', 0);
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido']);
    exit;
}

// Recebe o ISBN
$data = json_decode(file_get_contents('php://input'), true);
$isbn = $data['isbn'] ?? null;

if (!$isbn) {
    http_response_code(400);
    echo json_encode(['error' => 'ISBN não informado']);
    exit;
}

// Buscar dados do livro na API Google Books
$googleUrl = 'https://www.googleapis.com/books/v1/volumes?q=isbn:' . urlencode($isbn);
$response = @file_get_contents($googleUrl);
$bookData = ($response !== false) ? json_decode($response, true) : null;

// Valores padrão caso NÃO encontre no Google
$titulo = 'Título não encontrado (Editar)';
$autor = 'Autor desconhecido';
$capa_url = null;

// Se encontrou no Google, atualiza os valores com os dados reais
if (!empty($bookData['items'][0]['volumeInfo'])) {
    $item = $bookData['items'][0]['volumeInfo'];
    $titulo = $item['title'] ?? $titulo;
    $autor = isset($item['authors']) ? implode(', ', $item['authors']) : $autor;
    $capa_url = $item['imageLinks']['thumbnail'] ?? null;
}

// Preenche todos os campos esperados pelo cadastro manual
$dados = [
    'titulo' => $titulo,
    'autor' => $autor,
    'isbn' => $isbn,
    'capa_url' => $capa_url,
    'tipo' => 'Livro Físico',
    'quantidade_disponivel' => 1,
    'grau_recomendado' => 'Livre',
    'nota_instrucao' => null
];

// Envia para o endpoint unificado de cadastro manual
$endpoint = __DIR__ . '/biblioteca/cadastrar.php';
$ch = curl_init($endpoint);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dados));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$result = curl_exec($ch);
curl_close($ch);

if ($result === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao comunicar com o endpoint de cadastro']);
    exit;
}

$response = json_decode($result, true);
if (!empty($response['sucesso'])) {
    echo json_encode(['success' => true, 'message' => 'Livro cadastrado!']);
} else {
    http_response_code(500);
    echo json_encode(['error' => $response['mensagem'] ?? 'Erro ao salvar livro']);
}