<?php

namespace App\Controllers;

use App\Models\ConfiguracaoLoja;
use App\Models\Presenca;
use App\Models\Sessao;

class TesourariaSessaoController
{
    public function index(): void
    {
        $sessaoModel = new Sessao();
        $presencaModel = new Presenca();
        $configuracaoLoja = (new ConfiguracaoLoja())->obter();

        $proximaSessao = $sessaoModel->obterProximaSessao();
        $sessoes = $sessaoModel->listarFuturas(8);
        $participantesAgape = [];
        $estimativaArrecadacao = 0.0;

        if ($proximaSessao && !empty($proximaSessao['id'])) {
            $sessaoId = (int) $proximaSessao['id'];
            $participantesAgape = $presencaModel->listarParticipantesAgapePorSessao($sessaoId);
            $proximaSessao['reflete_financeiro_oficial'] = $this->refleteFinanceiroOficial($proximaSessao);
            $estimativaArrecadacao = $this->calcularEstimativaArrecadacao(
                $proximaSessao,
                count($participantesAgape)
            );
        }

        $sessoesFinanceiras = array_map(function (array $sessao) use ($sessaoModel): array {
            $sessao['descricao_tipo'] = $sessaoModel->obterDescricaoTipoSessao($sessao);
            $sessao['descricao_agape'] = $sessaoModel->obterDescricaoAgape($sessao);
            $sessao['descricao_modelo_financeiro_agape'] = $sessaoModel->obterDescricaoModeloFinanceiroAgape($sessao);
            $sessao['reflete_financeiro_oficial'] = $this->refleteFinanceiroOficial($sessao);
            $sessao['estimativa_arrecadacao'] = $this->calcularEstimativaArrecadacao(
                $sessao,
                (int) ($sessao['total_agape'] ?? 0)
            );

            return $sessao;
        }, $sessoes);

        require_once __DIR__ . '/../Views/tesouraria_sessao/index.php';
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
