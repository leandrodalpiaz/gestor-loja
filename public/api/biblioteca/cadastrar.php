<?php

declare(strict_types=1);

use App\Config\Env;
use App\Models\Acervo;

require_once __DIR__ . '/../../../src/autoload.php';

Env::load(__DIR__ . '/../../../.env');

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Metodo nao permitido.']);
    exit;
}

$dados = json_decode(file_get_contents('php://input'), true) ?? [];

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

    echo json_encode(['sucesso' => true]);
} catch (\Throwable $e) {
    error_log('[api/biblioteca/cadastrar] ' . $e->getMessage());
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro interno ao salvar o livro.']);
}
