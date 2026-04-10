<?php

namespace App\Controllers;

use App\Models\ComprovantePix;
use App\Models\FechamentoMensal;
use App\Models\LancamentoFinanceiro;
use App\Models\ObrigacaoFinanceira;
use App\Models\Presenca;
use App\Models\RegularidadeObreiro;
use App\Models\Sessao;

class TesourariaController
{
    public function montarPayloadMiniapp(): array
    {
        $mes = (int) date('n');
        $ano = (int) date('Y');

        $lancamentos = new LancamentoFinanceiro();
        $comprovantes = new ComprovantePix();
        $regularidadeModel = new RegularidadeObreiro();
        $fechamentoModel = new FechamentoMensal();
        $obrigacoesModel = new ObrigacaoFinanceira();
        $sessaoModel = new Sessao();
        $presencaModel = new Presenca();

        $totais = $lancamentos->obterTotaisMes($mes, $ano);
        $listaComprovantes = $comprovantes->obterTodos();
        $regularidade = $regularidadeModel->obterPorMes($mes, $ano);
        $fechamento = $fechamentoModel->obter($mes, $ano);
        if (!$fechamento) {
            $mesPrev = $mes - 1;
            $anoPrev = $ano;
            if ($mesPrev < 1) {
                $mesPrev = 12;
                $anoPrev--;
            }
            $fechPrev = $fechamentoModel->obter($mesPrev, $anoPrev);
            $saldoSugerido = $fechPrev ? (float) $fechPrev['saldo_final'] : 0;
            $fechamentoModel->criar($mes, $ano, $saldoSugerido);
            $fechamentoModel->recalcularTotais($mes, $ano);
            $fechamento = $fechamentoModel->obter($mes, $ano);
        } else {
            $fechamentoModel->recalcularTotais($mes, $ano);
            $fechamento = $fechamentoModel->obter($mes, $ano);
        }

        $resumoObrigacoes = $obrigacoesModel->listarResumoTesouraria();
        $sessoes = $sessaoModel->listarFuturas(5);
        $sessoesFinanceiras = array_map(function (array $sessao) use ($sessaoModel, $presencaModel): array {
            $participantesAgape = !empty($sessao['id'])
                ? $presencaModel->listarParticipantesAgapePorSessao((int) $sessao['id'])
                : [];
            $estimativa = $this->calcularEstimativaArrecadacao($sessao, count($participantesAgape));

            return [
                'id' => (int) ($sessao['id'] ?? 0),
                'titulo' => (string) ($sessao['titulo'] ?? ''),
                'data_hora_inicio' => (string) ($sessao['data_hora_inicio'] ?? ''),
                'descricao_tipo' => (string) $sessaoModel->obterDescricaoTipoSessao($sessao),
                'descricao_agape' => (string) $sessaoModel->obterDescricaoAgape($sessao),
                'descricao_modelo' => (string) $sessaoModel->obterDescricaoModeloFinanceiroAgape($sessao),
                'confirmados_agape' => count($participantesAgape),
                'estimativa_arrecadacao' => $estimativa,
                'reflete_financeiro_oficial' => $this->refleteFinanceiroOficial($sessao),
            ];
        }, $sessoes);

        $resumoRegularidade = [
            'regular' => 0,
            'irregular' => 0,
        ];
        foreach ($regularidade as $item) {
            $status = (string) ($item['status'] ?? 'regular');
            if (isset($resumoRegularidade[$status])) {
                $resumoRegularidade[$status]++;
            }
        }

        $pendentesPix = array_values(array_filter($listaComprovantes, static fn (array $item): bool => (string) ($item['status'] ?? '') === 'pendente'));
        $obreirosCriticos = array_values(array_filter($resumoObrigacoes, static function (array $item): bool {
            return (int) ($item['parcelas_atrasadas'] ?? 0) > 0 || (float) ($item['saldo_em_aberto'] ?? 0) > 0;
        }));

        return [
            'mes_ref' => $mes,
            'ano_ref' => $ano,
            'caixa' => [
                'entradas' => (float) ($totais['entrada'] ?? 0),
                'saidas' => (float) ($totais['saida'] ?? 0),
                'saldo_liquido' => (float) (($totais['entrada'] ?? 0) - ($totais['saida'] ?? 0)),
            ],
            'comprovantes' => [
                'pendentes' => count($pendentesPix),
                'aprovados' => count(array_filter($listaComprovantes, static fn (array $item): bool => (string) ($item['status'] ?? '') === 'aprovado')),
                'rejeitados' => count(array_filter($listaComprovantes, static fn (array $item): bool => (string) ($item['status'] ?? '') === 'rejeitado')),
                'ultimos_pendentes' => array_map(static function (array $item): array {
                    return [
                        'id' => (int) ($item['id'] ?? 0),
                        'obreiro_nome' => (string) ($item['obreiro_nome'] ?? ('Telegram ' . ($item['telegram_user_id'] ?? ''))),
                        'valor_informado' => (float) ($item['valor_informado'] ?? 0),
                        'criado_em' => (string) ($item['criado_em'] ?? ''),
                    ];
                }, array_slice($pendentesPix, 0, 5)),
            ],
            'regularidade' => $resumoRegularidade,
            'fechamento' => [
                'id' => (int) ($fechamento['id'] ?? 0),
                'status' => (string) ($fechamento['status'] ?? 'aberto'),
                'saldo_inicial' => (float) ($fechamento['saldo_inicial'] ?? 0),
                'saldo_final' => (float) ($fechamento['saldo_final'] ?? 0),
                'total_entradas' => (float) ($fechamento['total_entradas'] ?? 0),
                'total_saidas' => (float) ($fechamento['total_saidas'] ?? 0),
            ],
            'obrigacoes' => [
                'obreiros_em_alerta' => count($obreirosCriticos),
                'parcelas_atrasadas' => array_sum(array_map(static fn (array $item): int => (int) ($item['parcelas_atrasadas'] ?? 0), $resumoObrigacoes)),
                'saldo_em_aberto' => array_sum(array_map(static fn (array $item): float => (float) ($item['saldo_em_aberto'] ?? 0), $resumoObrigacoes)),
                'top_alertas' => array_map(static function (array $item): array {
                    return [
                        'id' => (string) ($item['id'] ?? ''),
                        'nome' => (string) ($item['nome'] ?? 'Obreiro'),
                        'parcelas_atrasadas' => (int) ($item['parcelas_atrasadas'] ?? 0),
                        'saldo_em_aberto' => (float) ($item['saldo_em_aberto'] ?? 0),
                    ];
                }, array_slice($obreirosCriticos, 0, 5)),
            ],
            'sessoes_financeiras' => array_slice($sessoesFinanceiras, 0, 3),
            'atalhos' => [
                ['label' => 'Livro-caixa', 'dest' => '/tesouraria/caixa'],
                ['label' => 'Comprovantes', 'dest' => '/tesouraria/comprovantes'],
                ['label' => 'Regularidade', 'dest' => '/tesouraria/regularidade'],
                ['label' => 'Fechamento', 'dest' => '/tesouraria/fechamento'],
                ['label' => 'Obrigacoes', 'dest' => '/tesouraria/obrigacoes'],
                ['label' => 'Sessoes', 'dest' => '/tesouraria/sessoes'],
                ['label' => 'Relatorio', 'dest' => '/tesouraria/relatorio-gestao'],
            ],
        ];
    }

    private function calcularEstimativaArrecadacao(array $sessao, int $totalAgape): float
    {
        $modalidade = strtolower(trim((string) ($sessao['agape_modalidade'] ?? '')));
        $valorUnitario = (float) ($sessao['agape_valor'] ?? 0);

        if (!$this->refleteFinanceiroOficial($sessao) || $modalidade !== 'pago' || $valorUnitario <= 0 || $totalAgape <= 0) {
            return 0.0;
        }

        return round($valorUnitario * $totalAgape, 2);
    }

    private function refleteFinanceiroOficial(array $sessao): bool
    {
        $modalidade = strtolower(trim((string) ($sessao['agape_modalidade'] ?? 'nao_havera')));
        if ($modalidade === 'nao_havera') {
            return false;
        }

        $modelo = strtolower(trim((string) ($sessao['agape_modelo_financeiro'] ?? 'oficial_loja')));
        return in_array($modelo, ['oficial_loja', 'misto'], true);
    }
}
