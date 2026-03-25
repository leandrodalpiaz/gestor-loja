<?php
require_once __DIR__ . '/../../../src/bootstrap.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Método não permitido.']);
    exit;
}

$dados = json_decode(file_get_contents('php://input'), true);

if (empty($dados['titulo']) || empty($dados['autor'])) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Título e Autor são obrigatórios.']);
    exit;
}

try {
    require_once __DIR__ . '/../../../src/Models/Livro.php';
    $livroModel = new \App\Models\Livro();

    // Usa os mesmos nomes de campo do formulário web (adicionar.php)
    $livroModel->cadastrar([
        'titulo'               => $dados['titulo'],
        'autor'                => $dados['autor'],
        'isbn'                 => $dados['isbn'] ?? null,
        'capa_url'             => $dados['capa_url'] ?? null,
        'tipo'                 => $dados['tipo'] ?? 'Livro Físico',
        'quantidade_disponivel'=> (int)($dados['quantidade_disponivel'] ?? 1),
        'grau_recomendado'     => $dados['grau_recomendado'] ?? 'Livre',
        'nota_instrucao'       => $dados['nota_instrucao'] ?? null,
        // 'status' não é usado no método cadastrar, mas pode ser adicionado se necessário
    ]);

    echo json_encode(['sucesso' => true]);
} catch (\Exception $e) {
    error_log('[api/biblioteca/cadastrar] ' . $e->getMessage());
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro interno ao salvar o livro.']);
}
