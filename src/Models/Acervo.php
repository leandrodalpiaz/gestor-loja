<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class Acervo
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function listarTodos(): array
    {
        $sql = "SELECT * FROM acervo ORDER BY criado_em DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarPorId(int $id): ?array
    {
        $sql = "SELECT * FROM acervo WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function adicionar(array $dados): bool
    {
        $sql = "INSERT INTO acervo (
            titulo, autor, tipo, grau_restricao, arquivo_url, quantidade_disponivel,
            isbn, capa_url, grau_recomendado, nota_instrucao, curador_id
        ) VALUES (
            :titulo, :autor, :tipo, :grau_restricao, :arquivo_url, :quantidade_disponivel,
            :isbn, :capa_url, :grau_recomendado, :nota_instrucao, :curador_id
        )";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'titulo' => $dados['titulo'],
            'autor' => $dados['autor'],
            'tipo' => $dados['tipo'],
            'grau_restricao' => $dados['grau_restricao'],
            'arquivo_url' => $dados['arquivo_url'] ?? null,
            'quantidade_disponivel' => $dados['quantidade_disponivel'],
            'isbn' => $dados['isbn'] ?? null,
            'capa_url' => $dados['capa_url'] ?? null,
            'grau_recomendado' => $dados['grau_recomendado'] ?? 'Livre',
            'nota_instrucao' => $dados['nota_instrucao'] ?? null,
            'curador_id' => $dados['curador_id'] ?? null,
        ]);
    }

    public function atualizar(int $id, array $dados): bool
    {
        $sql = "UPDATE acervo SET
            titulo = :titulo,
            autor = :autor,
            tipo = :tipo,
            grau_restricao = :grau_restricao,
            arquivo_url = :arquivo_url,
            quantidade_disponivel = :quantidade_disponivel,
            isbn = :isbn,
            capa_url = :capa_url,
            grau_recomendado = :grau_recomendado,
            nota_instrucao = :nota_instrucao,
            curador_id = :curador_id
            WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'titulo' => $dados['titulo'],
            'autor' => $dados['autor'],
            'tipo' => $dados['tipo'],
            'grau_restricao' => $dados['grau_restricao'],
            'arquivo_url' => $dados['arquivo_url'] ?? null,
            'quantidade_disponivel' => $dados['quantidade_disponivel'],
            'isbn' => $dados['isbn'] ?? null,
            'capa_url' => $dados['capa_url'] ?? null,
            'grau_recomendado' => $dados['grau_recomendado'] ?? 'Livre',
            'nota_instrucao' => $dados['nota_instrucao'] ?? null,
            'curador_id' => $dados['curador_id'] ?? null,
            'id' => $id,
        ]);
    }

    public function deletar(int $id): bool
    {
        $sql = "DELETE FROM acervo WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }
}