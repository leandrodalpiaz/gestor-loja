<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class BanqueteOperacao
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->ensureTable();
    }

    private function ensureTable(): void
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS banquete_operacoes (
                id SERIAL PRIMARY KEY,
                sessao_id INT NOT NULL UNIQUE REFERENCES sessoes(id) ON DELETE CASCADE,
                status_operacional VARCHAR(30) NOT NULL DEFAULT 'planejamento',
                observacoes TEXT NULL,
                previsao_participantes INT NULL,
                fechado_em TIMESTAMP NULL,
                created_by INT NULL,
                updated_by INT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $this->db->exec("
            ALTER TABLE banquete_operacoes
                ADD COLUMN IF NOT EXISTS valor_unitario_previsto NUMERIC(12,2) NULL,
                ADD COLUMN IF NOT EXISTS custo_previsto NUMERIC(12,2) NULL,
                ADD COLUMN IF NOT EXISTS valor_arrecadado NUMERIC(12,2) NULL,
                ADD COLUMN IF NOT EXISTS custo_real NUMERIC(12,2) NULL,
                ADD COLUMN IF NOT EXISTS fornecedor VARCHAR(180) NULL,
                ADD COLUMN IF NOT EXISTS forma_pagamento VARCHAR(80) NULL,
                ADD COLUMN IF NOT EXISTS financeiro_status VARCHAR(30) NOT NULL DEFAULT 'planejado',
                ADD COLUMN IF NOT EXISTS financeiro_observacoes TEXT NULL,
                ADD COLUMN IF NOT EXISTS receita_lancamento_id INT NULL,
                ADD COLUMN IF NOT EXISTS despesa_lancamento_id INT NULL
        ");
    }

    public function obterPorSessao(int $sessaoId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM banquete_operacoes
            WHERE sessao_id = :sessao_id
            LIMIT 1
        ");
        $stmt->execute(['sessao_id' => $sessaoId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function salvar(int $sessaoId, array $dados, ?int $autorId = null): bool
    {
        $status = strtolower(trim((string) ($dados['status_operacional'] ?? 'planejamento')));
        if (!in_array($status, ['planejamento', 'preparacao', 'abastecimento', 'fechado'], true)) {
            $status = 'planejamento';
        }

        $observacoes = trim((string) ($dados['observacoes'] ?? ''));
        $previsao = isset($dados['previsao_participantes']) && $dados['previsao_participantes'] !== ''
            ? max(0, (int) $dados['previsao_participantes'])
            : null;
        $valorUnitario = $this->normalizarValor($dados['valor_unitario_previsto'] ?? null);
        $custoPrevisto = $this->normalizarValor($dados['custo_previsto'] ?? null);
        $valorArrecadado = $this->normalizarValor($dados['valor_arrecadado'] ?? null);
        $custoReal = $this->normalizarValor($dados['custo_real'] ?? null);
        $fornecedor = trim((string) ($dados['fornecedor'] ?? ''));
        $formaPagamento = trim((string) ($dados['forma_pagamento'] ?? ''));
        $financeiroObservacoes = trim((string) ($dados['financeiro_observacoes'] ?? ''));
        $financeiroStatus = strtolower(trim((string) ($dados['financeiro_status'] ?? 'planejado')));
        if (!in_array($financeiroStatus, ['planejado', 'a_receber', 'a_pagar', 'conciliado'], true)) {
            $financeiroStatus = 'planejado';
        }

        $operacaoAtual = $this->obterPorSessao($sessaoId);
        $receitaLancamentoId = !empty($operacaoAtual['receita_lancamento_id']) ? (int) $operacaoAtual['receita_lancamento_id'] : null;
        $despesaLancamentoId = !empty($operacaoAtual['despesa_lancamento_id']) ? (int) $operacaoAtual['despesa_lancamento_id'] : null;
        if (!empty($dados['registrar_financeiro'])) {
            $sessao = $this->obterSessao($sessaoId);
            $dataLancamento = substr((string) ($sessao['data_hora_inicio'] ?? date('Y-m-d')), 0, 10) ?: date('Y-m-d');
            $tituloSessao = trim((string) ($sessao['titulo'] ?? 'Sessão'));

            if ($receitaLancamentoId === null && $valorArrecadado !== null && $valorArrecadado > 0) {
                $receitaLancamentoId = $this->criarLancamentoBanquete(
                    'entrada',
                    $valorArrecadado,
                    $dataLancamento,
                    'Ágape - ' . $tituloSessao,
                    'AGAPE_RECEITA',
                    $autorId
                );
            }

            if ($despesaLancamentoId === null && $custoReal !== null && $custoReal > 0) {
                $despesaLancamentoId = $this->criarLancamentoBanquete(
                    'saida',
                    $custoReal,
                    $dataLancamento,
                    'Despesa do ágape - ' . $tituloSessao . ($fornecedor !== '' ? ' - ' . $fornecedor : ''),
                    'DESPESAS_AGAPE',
                    $autorId
                );
            }

            if (($valorArrecadado === null || $valorArrecadado <= 0) && ($custoReal === null || $custoReal <= 0)) {
                $financeiroStatus = 'planejado';
            } elseif (($valorArrecadado ?? 0) > 0 && ($custoReal ?? 0) > 0) {
                $financeiroStatus = 'conciliado';
            } elseif (($valorArrecadado ?? 0) > 0) {
                $financeiroStatus = 'a_pagar';
            } else {
                $financeiroStatus = 'a_receber';
            }
        }

        $stmt = $this->db->prepare("
            INSERT INTO banquete_operacoes (
                sessao_id,
                status_operacional,
                observacoes,
                previsao_participantes,
                valor_unitario_previsto,
                custo_previsto,
                valor_arrecadado,
                custo_real,
                fornecedor,
                forma_pagamento,
                financeiro_status,
                financeiro_observacoes,
                receita_lancamento_id,
                despesa_lancamento_id,
                fechado_em,
                created_by,
                updated_by,
                updated_at
            ) VALUES (
                :sessao_id,
                :status_operacional,
                :observacoes,
                :previsao_participantes,
                :valor_unitario_previsto,
                :custo_previsto,
                :valor_arrecadado,
                :custo_real,
                :fornecedor,
                :forma_pagamento,
                :financeiro_status,
                :financeiro_observacoes,
                :receita_lancamento_id,
                :despesa_lancamento_id,
                :fechado_em,
                :created_by,
                :updated_by,
                CURRENT_TIMESTAMP
            )
            ON CONFLICT (sessao_id)
            DO UPDATE SET
                status_operacional = EXCLUDED.status_operacional,
                observacoes = EXCLUDED.observacoes,
                previsao_participantes = EXCLUDED.previsao_participantes,
                valor_unitario_previsto = EXCLUDED.valor_unitario_previsto,
                custo_previsto = EXCLUDED.custo_previsto,
                valor_arrecadado = EXCLUDED.valor_arrecadado,
                custo_real = EXCLUDED.custo_real,
                fornecedor = EXCLUDED.fornecedor,
                forma_pagamento = EXCLUDED.forma_pagamento,
                financeiro_status = EXCLUDED.financeiro_status,
                financeiro_observacoes = EXCLUDED.financeiro_observacoes,
                receita_lancamento_id = COALESCE(EXCLUDED.receita_lancamento_id, banquete_operacoes.receita_lancamento_id),
                despesa_lancamento_id = COALESCE(EXCLUDED.despesa_lancamento_id, banquete_operacoes.despesa_lancamento_id),
                fechado_em = EXCLUDED.fechado_em,
                updated_by = EXCLUDED.updated_by,
                updated_at = CURRENT_TIMESTAMP
        ");

        return $stmt->execute([
            'sessao_id' => $sessaoId,
            'status_operacional' => $status,
            'observacoes' => $observacoes !== '' ? $observacoes : null,
            'previsao_participantes' => $previsao,
            'valor_unitario_previsto' => $valorUnitario,
            'custo_previsto' => $custoPrevisto,
            'valor_arrecadado' => $valorArrecadado,
            'custo_real' => $custoReal,
            'fornecedor' => $fornecedor !== '' ? $fornecedor : null,
            'forma_pagamento' => $formaPagamento !== '' ? $formaPagamento : null,
            'financeiro_status' => $financeiroStatus,
            'financeiro_observacoes' => $financeiroObservacoes !== '' ? $financeiroObservacoes : null,
            'receita_lancamento_id' => $receitaLancamentoId,
            'despesa_lancamento_id' => $despesaLancamentoId,
            'fechado_em' => $status === 'fechado' ? date('Y-m-d H:i:s') : null,
            'created_by' => $autorId,
            'updated_by' => $autorId,
        ]);
    }

    private function normalizarValor($valor): ?float
    {
        $texto = trim((string) $valor);
        if ($texto === '') {
            return null;
        }

        $texto = str_replace(['R$', ' '], '', $texto);
        if (str_contains($texto, ',') && str_contains($texto, '.')) {
            $texto = str_replace('.', '', $texto);
        }
        $texto = str_replace(',', '.', $texto);

        return is_numeric($texto) ? max(0, (float) $texto) : null;
    }

    private function obterSessao(int $sessaoId): ?array
    {
        $stmt = $this->db->prepare("SELECT id, titulo, data_hora_inicio FROM sessoes WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $sessaoId]);
        $sessao = $stmt->fetch(PDO::FETCH_ASSOC);
        return $sessao ?: null;
    }

    private function criarLancamentoBanquete(string $tipo, float $valor, string $dataLancamento, string $descricao, string $categoriaCodigo, ?int $autorId): ?int
    {
        $categoriaId = $this->obterCategoriaIdPorCodigo($categoriaCodigo);
        $model = new LancamentoFinanceiro();
        $ok = $model->criar([
            'tipo' => $tipo,
            'categoria_id' => $categoriaId,
            'valor' => $valor,
            'data_lancamento' => $dataLancamento,
            'descricao' => $descricao,
            'mes_ref' => (int) date('n', strtotime($dataLancamento)),
            'ano_ref' => (int) date('Y', strtotime($dataLancamento)),
            'created_by' => $autorId,
        ]);

        return $ok ? (int) $this->db->lastInsertId() : null;
    }

    private function obterCategoriaIdPorCodigo(string $codigo): ?int
    {
        $stmt = $this->db->prepare("SELECT id FROM categorias_financeiras WHERE UPPER(codigo) = :codigo LIMIT 1");
        $stmt->execute(['codigo' => strtoupper($codigo)]);
        $id = $stmt->fetchColumn();
        return $id !== false ? (int) $id : null;
    }
}
