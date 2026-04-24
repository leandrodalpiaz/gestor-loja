<?php

namespace App\Models;

use App\Config\Database;
use App\Core\Tenant\ResolvesStoreTenant;
use PDO;

class LancamentoFinanceiro
{
    use ResolvesStoreTenant;

    private PDO $db;
    private ?bool $tabelaTemLojaId = null;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Cria um novo lançamento (entrada ou saída)
     */
    public function criar(array $data): bool
    {
        if ($this->tabelaPossuiColunaLojaId()) {
            $sql = "
                INSERT INTO lancamentos_financeiros (
                    loja_id, tipo, categoria_id, valor, data_lancamento,
                    descricao, obreiro_id, mes_ref, ano_ref, created_by
                ) VALUES (
                    :loja_id, :tipo, :categoria_id, :valor, :data_lancamento,
                    :descricao, :obreiro_id, :mes_ref, :ano_ref, :created_by
                )
            ";

            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                'loja_id' => $this->obterLojaAtualId(),
                'tipo' => $data['tipo'] ?? 'entrada',
                'categoria_id' => $data['categoria_id'] ?? null,
                'valor' => $data['valor'] ?? 0,
                'data_lancamento' => $data['data_lancamento'] ?? date('Y-m-d'),
                'descricao' => $data['descricao'] ?? null,
                'obreiro_id' => $data['obreiro_id'] ?? null,
                'mes_ref' => $data['mes_ref'] ?? date('n'),
                'ano_ref' => $data['ano_ref'] ?? date('Y'),
                'created_by' => $data['created_by'] ?? null,
            ]);
        }

        $sql = "
            INSERT INTO lancamentos_financeiros (
                tipo, categoria_id, valor, data_lancamento,
                descricao, obreiro_id, mes_ref, ano_ref, created_by
            ) VALUES (
                :tipo, :categoria_id, :valor, :data_lancamento,
                :descricao, :obreiro_id, :mes_ref, :ano_ref, :created_by
            )
        ";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'tipo' => $data['tipo'] ?? 'entrada',
            'categoria_id' => $data['categoria_id'] ?? null,
            'valor' => $data['valor'] ?? 0,
            'data_lancamento' => $data['data_lancamento'] ?? date('Y-m-d'),
            'descricao' => $data['descricao'] ?? null,
            'obreiro_id' => $data['obreiro_id'] ?? null,
            'mes_ref' => $data['mes_ref'] ?? date('n'),
            'ano_ref' => $data['ano_ref'] ?? date('Y'),
            'created_by' => $data['created_by'] ?? null,
        ]);
    }

    /**
     * Lista lançamentos de um mês
     */
    public function obterPorMes(int $mes, int $ano): array
    {
        $sql = "
            SELECT l.*, c.nome as categoria_nome, c.tipo as categoria_tipo,
                   COALESCE(o.nome_historico, o.nome) as obreiro_nome
            FROM lancamentos_financeiros l
            LEFT JOIN categorias_financeiras c ON l.categoria_id = c.id
            LEFT JOIN obreiros o ON l.obreiro_id = o.id
            WHERE l.mes_ref = :mes AND l.ano_ref = :ano
        ";

        $params = [
            'mes' => $mes,
            'ano' => $ano,
        ];

        if ($this->tabelaPossuiColunaLojaId()) {
            $sql .= " AND l.loja_id = :loja_id";
            $params['loja_id'] = $this->obterLojaAtualId();
        }

        $sql .= "
            ORDER BY l.data_lancamento DESC, l.id DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Totais de entrada/saída por mês
     */
    public function obterTotaisMes(int $mes, int $ano): array
    {
        $sql = "
            SELECT 
                tipo,
                SUM(valor) as total
            FROM lancamentos_financeiros
            WHERE mes_ref = :mes AND ano_ref = :ano
        ";

        $params = [
            'mes' => $mes,
            'ano' => $ano,
        ];

        if ($this->tabelaPossuiColunaLojaId()) {
            $sql .= " AND loja_id = :loja_id";
            $params['loja_id'] = $this->obterLojaAtualId();
        }

        $sql .= " GROUP BY tipo";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        $totais = ['entrada' => 0, 'saida' => 0];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $totais[$row['tipo']] = (float) $row['total'];
        }
        
        return $totais;
    }

    /**
     * Totais por categoria no mês
     */
    public function obterPorCategoriaMes(int $mes, int $ano): array
    {
        $sql = "
            SELECT 
                c.id, c.nome, c.tipo,
                SUM(l.valor) as total,
                COUNT(l.id) as quantidade
            FROM lancamentos_financeiros l
            JOIN categorias_financeiras c ON l.categoria_id = c.id
            WHERE l.mes_ref = :mes AND l.ano_ref = :ano
        ";

        $params = [
            'mes' => $mes,
            'ano' => $ano,
        ];

        if ($this->tabelaPossuiColunaLojaId()) {
            $sql .= " AND l.loja_id = :loja_id";
            $params['loja_id'] = $this->obterLojaAtualId();
        }

        $sql .= "
            GROUP BY c.id, c.nome, c.tipo
            ORDER BY c.tipo DESC, total DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Deleta um lançamento
     */
    public function deletar(int $id): bool
    {
        $sql = "DELETE FROM lancamentos_financeiros WHERE id = :id";
        $params = ['id' => $id];

        if ($this->tabelaPossuiColunaLojaId()) {
            $sql .= " AND loja_id = :loja_id";
            $params['loja_id'] = $this->obterLojaAtualId();
        }

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    private function obterLojaAtualId(): int
    {
        return $this->resolveCurrentStoreId($this->db);
    }

    private function tabelaPossuiColunaLojaId(): bool
    {
        if ($this->tabelaTemLojaId !== null) {
            return $this->tabelaTemLojaId;
        }

        $stmt = $this->db->prepare("
            SELECT 1
            FROM information_schema.columns
            WHERE table_schema = 'public'
              AND table_name = 'lancamentos_financeiros'
              AND column_name = 'loja_id'
            LIMIT 1
        ");
        $stmt->execute();

        $this->tabelaTemLojaId = (bool) $stmt->fetchColumn();
        return $this->tabelaTemLojaId;
    }
}
