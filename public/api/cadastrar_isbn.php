<?php
// Oculta erros do PHP na tela para não quebrar a resposta JSON
ini_set('display_errors', 0);
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido']);
    exit;
}

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
$bookData = json_decode($response, true);

if (empty($bookData['items'])) {
    http_response_code(404);
    echo json_encode(['error' => 'Livro não encontrado no Google Books']);
    exit;
}

$item = $bookData['items'][0]['volumeInfo'];
$titulo = $item['title'] ?? 'Título desconhecido';
$autor = isset($item['authors']) ? implode(', ', $item['authors']) : 'Autor desconhecido';
$capa_url = $item['imageLinks']['thumbnail'] ?? null;

// Carrega apenas o autoloader padrão do sistema
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Models\Acervo;

try {
    $acervo = new Acervo();
    $dados = [
        'titulo' => $titulo,
        'autor' => $autor,
        'tipo' => 'Livro',
        'grau_restricao' => 'Livre',
        'arquivo_url' => null,
        'quantidade_disponivel' => 1,
        'isbn' => $isbn,
        'capa_url' => $capa_url,
        'grau_recomendado' => 'Livre',
        'nota_instrucao' => null,
        'curador_id' => null
    ];

    $ok = $acervo->adicionar($dados);

    if ($ok) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Erro ao salvar livro no banco de dados']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno: ' . $e->getMessage()]);
}