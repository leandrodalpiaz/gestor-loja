<?php

namespace App\Models;

use App\Config\Database;
use App\Core\Tenant\ResolvesStoreTenant;
use App\Services\LivroMetadataService;
use PDO;

class Acervo
{
    use ResolvesStoreTenant;

    private PDO $db;
    private LivroMetadataService $metadataService;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->metadataService = new LivroMetadataService();
    }

    public function listarTodos(?array $lojasIds = null): array
    {
        $lojaId = $this->buscarLojaAtualId();
        $lojasIds = $lojasIds !== null ? array_values(array_unique(array_filter(array_map('intval', $lojasIds)))) : null;
        if ($lojasIds === []) {
            $lojasIds = [$lojaId];
        }
        if ($lojasIds === null) {
            $lojasIds = [$lojaId];
        }

        $placeholders = [];
        $params = [];
        foreach ($lojasIds as $idx => $id) {
            $key = 'loja_' . $idx;
            $placeholders[] = ':' . $key;
            $params[$key] = $id;
        }
        $inClause = implode(', ', $placeholders);
        $sql = "SELECT
                    a.*,
                    l.nome AS loja_nome,
                    l.numero_loja,
                    l.sigla AS loja_sigla,
                    COALESCE(c.total_comentarios, 0) AS total_comentarios,
                    COALESCE(r.total_gostei_sim, 0) AS total_gostei_sim,
                    COALESCE(r.total_gostei_nao, 0) AS total_gostei_nao,
                    CASE
                        WHEN COALESCE(a.quantidade_disponivel, 0) > 0 THEN TRUE
                        ELSE FALSE
                    END AS disponivel
                FROM acervo a
                JOIN lojas l ON l.id = a.loja_id
                LEFT JOIN (
                    SELECT acervo_id, COUNT(*) AS total_comentarios
                    FROM biblioteca_comentarios
                    WHERE ativo = TRUE
                    GROUP BY acervo_id
                ) c ON c.acervo_id = a.id
                LEFT JOIN (
                    SELECT
                        acervo_id,
                        SUM(CASE WHEN gostei = TRUE THEN 1 ELSE 0 END) AS total_gostei_sim,
                        SUM(CASE WHEN gostei = FALSE THEN 1 ELSE 0 END) AS total_gostei_nao
                    FROM biblioteca_reacoes
                    GROUP BY acervo_id
                ) r ON r.acervo_id = a.id
                WHERE a.ativo = TRUE
                  AND a.loja_id IN ($inClause)
                ORDER BY a.criado_em DESC, a.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarPorId(int $id): ?array
    {
        $sql = "SELECT * FROM acervo WHERE id = :id AND loja_id = :loja_id AND ativo = TRUE LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'id' => $id,
            'loja_id' => $this->buscarLojaAtualId(),
        ]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function existePorNotaInstrucao(string $notaInstrucao): bool
    {
        $notaInstrucao = trim($notaInstrucao);
        if ($notaInstrucao === '') {
            return false;
        }
        $sql = "SELECT COUNT(*) FROM acervo WHERE nota_instrucao = :nota_instrucao AND loja_id = :loja_id AND ativo = TRUE";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'nota_instrucao' => $notaInstrucao,
            'loja_id' => $this->buscarLojaAtualId(),
        ]);
        return ((int) $stmt->fetchColumn()) > 0;
    }

    public function buscarDetalhes(int $id, ?string $obreiroId = null, ?int $lojaId = null): ?array
    {
        $lojaId = $lojaId ?? $this->buscarLojaAtualId();
        $sql = "SELECT
                    a.*,
                    l.nome AS loja_nome,
                    l.numero_loja,
                    l.sigla AS loja_sigla,
                    COALESCE(c.total_comentarios, 0) AS total_comentarios,
                    COALESCE(r.total_gostei_sim, 0) AS total_gostei_sim,
                    COALESCE(r.total_gostei_nao, 0) AS total_gostei_nao,
                    ru.gostei AS minha_reacao,
                    CASE
                        WHEN COALESCE(a.quantidade_disponivel, 0) > 0 THEN TRUE
                        ELSE FALSE
                    END AS disponivel
                FROM acervo a
                JOIN lojas l ON l.id = a.loja_id
                LEFT JOIN (
                    SELECT acervo_id, COUNT(*) AS total_comentarios
                    FROM biblioteca_comentarios
                    WHERE ativo = TRUE
                    GROUP BY acervo_id
                ) c ON c.acervo_id = a.id
                LEFT JOIN (
                    SELECT
                        acervo_id,
                        SUM(CASE WHEN gostei = TRUE THEN 1 ELSE 0 END) AS total_gostei_sim,
                        SUM(CASE WHEN gostei = FALSE THEN 1 ELSE 0 END) AS total_gostei_nao
                    FROM biblioteca_reacoes
                    GROUP BY acervo_id
                ) r ON r.acervo_id = a.id
                LEFT JOIN biblioteca_reacoes ru
                    ON ru.acervo_id = a.id
                    AND ru.obreiro_id::text = :obreiro_id
                WHERE a.id = :id
                  AND a.loja_id = :loja_id
                  AND a.ativo = TRUE
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'id' => $id,
            'loja_id' => $lojaId,
            'obreiro_id' => $obreiroId ?? '',
        ]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function adicionar(array $dados): bool
    {
        $isbn = trim((string) ($dados['isbn'] ?? ''));
        $metadata = $isbn !== '' ? $this->metadataService->buscarPorIsbn($isbn) : null;

        $titulo = trim((string) ($dados['titulo'] ?? ''));
        $autor = trim((string) ($dados['autor'] ?? ''));
        $capaUrl = trim((string) ($dados['capa_url'] ?? ''));
        $resumo = trim((string) ($dados['resumo'] ?? ''));

        if ($titulo === '' && is_array($metadata)) {
            $titulo = (string) ($metadata['titulo'] ?? '');
        }
        if ($autor === '' && is_array($metadata)) {
            $autor = (string) ($metadata['autor'] ?? '');
        }
        if ($capaUrl === '' && is_array($metadata)) {
            $capaUrl = (string) ($metadata['capa_url'] ?? '');
        }
        if ($resumo === '' && is_array($metadata)) {
            $resumo = (string) ($metadata['resumo'] ?? '');
        }

        if ($titulo === '' || $autor === '') {
            return false;
        }

        $grauRecomendado = $this->normalizarGrau((string) ($dados['grau_recomendado'] ?? 'Livre'));
        $grauRestricao = isset($dados['grau_restricao']) ? max(1, (int) $dados['grau_restricao']) : match ($grauRecomendado) {
            'Mestre' => 3,
            'Companheiro' => 2,
            default => 1,
        };

        $sql = "INSERT INTO acervo (
                    codigo_acervo,
                    loja_id,
                    titulo,
                    autor,
                    resumo,
                    tipo,
                    grau_restricao,
                    arquivo_url,
                    quantidade_disponivel,
                    isbn,
                    capa_url,
                    grau_recomendado,
                    nota_instrucao,
                    curador_id,
                    ativo
                ) VALUES (
                    :codigo_acervo,
                    :loja_id,
                    :titulo,
                    :autor,
                    :resumo,
                    :tipo,
                    :grau_restricao,
                    :arquivo_url,
                    :quantidade_disponivel,
                    :isbn,
                    :capa_url,
                    :grau_recomendado,
                    :nota_instrucao,
                    :curador_id,
                    TRUE
                )";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'codigo_acervo' => $dados['codigo_acervo'] ?? $this->gerarCodigoAcervo(),
            'loja_id' => $dados['loja_id'] ?? $this->buscarLojaAtualId(),
            'titulo' => $titulo,
            'autor' => $autor,
            'resumo' => $resumo !== '' ? $resumo : null,
            'tipo' => $this->normalizarTipo((string) ($dados['tipo'] ?? 'Livro Fisico')),
            'grau_restricao' => $grauRestricao,
            'arquivo_url' => $this->nuloSeVazio((string) ($dados['arquivo_url'] ?? '')),
            'quantidade_disponivel' => max(0, (int) ($dados['quantidade_disponivel'] ?? 0)),
            'isbn' => $isbn !== '' ? $isbn : null,
            'capa_url' => $capaUrl !== '' ? $capaUrl : null,
            'grau_recomendado' => $grauRecomendado,
            'nota_instrucao' => $this->nuloSeVazio((string) ($dados['nota_instrucao'] ?? '')),
            'curador_id' => $dados['curador_id'] ?? null,
        ]);
    }

    public function atualizar(int $id, array $dados): bool
    {
        $grauRecomendado = $this->normalizarGrau((string) ($dados['grau_recomendado'] ?? 'Livre'));
        $grauRestricao = isset($dados['grau_restricao']) ? max(1, (int) $dados['grau_restricao']) : match ($grauRecomendado) {
            'Mestre' => 3,
            'Companheiro' => 2,
            default => 1,
        };

        $sql = "UPDATE acervo SET
                    titulo = :titulo,
                    autor = :autor,
                    resumo = :resumo,
                    tipo = :tipo,
                    grau_restricao = :grau_restricao,
                    arquivo_url = :arquivo_url,
                    quantidade_disponivel = :quantidade_disponivel,
                    isbn = :isbn,
                    capa_url = :capa_url,
                    grau_recomendado = :grau_recomendado,
                    nota_instrucao = :nota_instrucao,
                    curador_id = :curador_id,
                    atualizado_em = CURRENT_TIMESTAMP
                WHERE id = :id
                  AND loja_id = :loja_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'titulo' => trim((string) ($dados['titulo'] ?? '')),
            'autor' => trim((string) ($dados['autor'] ?? '')),
            'resumo' => $this->nuloSeVazio((string) ($dados['resumo'] ?? '')),
            'tipo' => $this->normalizarTipo((string) ($dados['tipo'] ?? 'Livro Fisico')),
            'grau_restricao' => $grauRestricao,
            'arquivo_url' => $this->nuloSeVazio((string) ($dados['arquivo_url'] ?? '')),
            'quantidade_disponivel' => max(0, (int) ($dados['quantidade_disponivel'] ?? 0)),
            'isbn' => $this->nuloSeVazio((string) ($dados['isbn'] ?? '')),
            'capa_url' => $this->nuloSeVazio((string) ($dados['capa_url'] ?? '')),
            'grau_recomendado' => $grauRecomendado,
            'nota_instrucao' => $this->nuloSeVazio((string) ($dados['nota_instrucao'] ?? '')),
            'curador_id' => $dados['curador_id'] ?? null,
            'id' => $id,
            'loja_id' => $this->buscarLojaAtualId(),
        ]);
    }

    public function atualizarClassificacao($id, $grau, $nota, ?string $curadorId): bool
    {
        $grauNorm = $this->normalizarGrau((string) $grau);
        $grauRestricao = match ($grauNorm) {
            'Mestre' => 3,
            'Companheiro' => 2,
            default => 1,
        };

        $stmt = $this->db->prepare(
            "UPDATE acervo
             SET grau_recomendado = :grau,
                 grau_restricao = :grau_restricao,
                 nota_instrucao = :nota,
                 curador_id = :curador,
                 atualizado_em = CURRENT_TIMESTAMP
             WHERE id = :id
               AND loja_id = :loja_id"
        );
        return $stmt->execute([
            'grau' => $grauNorm,
            'grau_restricao' => $grauRestricao,
            'nota' => $this->nuloSeVazio((string) $nota),
            'curador' => $curadorId,
            'id' => $id,
            'loja_id' => $this->buscarLojaAtualId(),
        ]);
    }

    public function deletar(int $id): bool
    {
        $sql = "UPDATE acervo
                SET ativo = FALSE, atualizado_em = CURRENT_TIMESTAMP
                WHERE id = :id
                  AND loja_id = :loja_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'loja_id' => $this->buscarLojaAtualId(),
        ]);
    }

    private function buscarLojaAtualId(): int
    {
        return $this->resolveCurrentStoreId($this->db);
    }

    private function gerarCodigoAcervo(): string
    {
        $numero = preg_replace('/\D+/', '', (string) ($_ENV['APP_LOJA_NUMERO'] ?? '270')) ?: '270';
        $siglaRaw = trim((string) ($_ENV['APP_LOJA_SIGLA'] ?? 'R'));
        $sigla = strtoupper(substr($siglaRaw !== '' ? $siglaRaw : 'R', 0, 1));
        $prefixo = $numero . '-' . $sigla . '-';

        $stmt = $this->db->prepare(
            "SELECT codigo_acervo
             FROM acervo
             WHERE codigo_acervo LIKE :prefixo
               AND loja_id = :loja_id
             ORDER BY id DESC
             LIMIT 1"
        );
        $stmt->execute([
            'prefixo' => $prefixo . '%',
            'loja_id' => $this->buscarLojaAtualId(),
        ]);
        $ultimo = (string) ($stmt->fetchColumn() ?: '');

        $sequencial = 0;
        if ($ultimo !== '' && preg_match('/-(\d+)$/', $ultimo, $m)) {
            $sequencial = (int) $m[1];
        }
        $sequencial++;

        return $prefixo . str_pad((string) $sequencial, 5, '0', STR_PAD_LEFT);
    }

    private function normalizarTipo(string $tipo): string
    {
        $tipo = trim($tipo);
        $map = [
            'Livro Fisico' => 'Livro Fisico',
            'Livro Físico' => 'Livro Fisico',
            'fisico' => 'Livro Fisico',
            'Digital (PDF)' => 'Digital (PDF)',
            'digital' => 'Digital (PDF)',
            'Ritual' => 'Ritual',
            'ritual' => 'Ritual',
            'Peca de Arquitetura' => 'Peca de Arquitetura',
            'Peça de Arquitetura' => 'Peca de Arquitetura',
            'peca_de_arquitetura' => 'Peca de Arquitetura',
            'Trabalho de Instrucao' => 'Trabalho de Instrucao',
            'Trabalho de Instrução' => 'Trabalho de Instrucao',
            'trabalho_de_instrucao' => 'Trabalho de Instrucao',
        ];

        return $map[$tipo] ?? 'Livro Fisico';
    }

    private function normalizarGrau(string $grau): string
    {
        $grau = trim($grau);
        $validos = ['Livre', 'Aprendiz', 'Companheiro', 'Mestre'];
        return in_array($grau, $validos, true) ? $grau : 'Livre';
    }

    private function nuloSeVazio(string $valor): ?string
    {
        $valor = trim($valor);
        return $valor !== '' ? $valor : null;
    }
}
