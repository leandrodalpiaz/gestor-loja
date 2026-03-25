<?php
namespace App\Models;

use PDO;
use Exception;
use App\Config\Database;

class Livro
{
    // Valores válidos conforme formulário Mini App
    private const TIPOS = [
        'Livro Físico',
        'Digital (PDF)',
        'Ritual',
    ];
    private const GRAUS = [
        'Livre',
        'Aprendiz',
        'Companheiro',
        'Mestre',
    ];

    /**
     * Cadastra um novo livro no banco de dados
     * @param array $dados
     * @throws Exception
     */
    public function cadastrar(array $dados)
    {
        // Validação dos campos obrigatórios
        if (empty($dados['titulo']) || empty($dados['autor'])) {
            throw new Exception('Título e Autor são obrigatórios.');
        }
        // Validação do tipo de material
        $tipo = $dados['tipo'] ?? 'Livro Físico';
        if (!in_array($tipo, self::TIPOS, true)) {
            throw new Exception('Tipo de material inválido.');
        }
        // Validação do grau recomendado
        $grau = $dados['grau_recomendado'] ?? 'Livre';
        if ($grau && !in_array($grau, self::GRAUS, true)) {
            throw new Exception('Grau recomendado inválido.');
        }
        $quantidade = isset($dados['quantidade_disponivel']) ? (int)$dados['quantidade_disponivel'] : 1;
        if ($quantidade < 1) {
            throw new Exception('Quantidade deve ser pelo menos 1.');
        }

        $pdo = Database::getConnection();
        $sql = "INSERT INTO livros (isbn, titulo, autor, editora, capa_url, tipo_material, quantidade, grau_recomendado, nota_instrucao)
                VALUES (:isbn, :titulo, :autor, :editora, :capa_url, :tipo_material, :quantidade, :grau_recomendado, :nota_instrucao)";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':isbn', $dados['isbn'] ?? null);
        $stmt->bindValue(':titulo', $dados['titulo']);
        $stmt->bindValue(':autor', $dados['autor']);
        $stmt->bindValue(':editora', $dados['editora'] ?? null);
        $stmt->bindValue(':capa_url', $dados['capa_url'] ?? null);
        $stmt->bindValue(':tipo_material', $tipo);
        $stmt->bindValue(':quantidade', $quantidade, PDO::PARAM_INT);
        $stmt->bindValue(':grau_recomendado', $grau);
        $stmt->bindValue(':nota_instrucao', $dados['nota_instrucao'] ?? null);
        if (!$stmt->execute()) {
            throw new Exception('Erro ao inserir livro no banco de dados.');
        }
    }
}
