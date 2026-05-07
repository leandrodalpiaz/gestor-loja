<?php

namespace App\Controllers;

use App\Models\ComprovantePix;
use App\Models\FechamentoMensal;
use App\Models\LancamentoFinanceiro;
use App\Models\ObrigacaoFinanceira;
use App\Models\Obreiro;
use App\Models\RegularidadeObreiro;
use App\Models\Sessao;

class PwaTesourariaController
{
    public function index(): void
    {
        $mes = max(1, min(12, (int) ($_GET['mes'] ?? date('n'))));
        $ano = max(2020, (int) ($_GET['ano'] ?? date('Y')));

        $lancamentoModel = new LancamentoFinanceiro();
        $regularidadeModel = new RegularidadeObreiro();
        $obrigacaoModel = new ObrigacaoFinanceira();

        $totais = $lancamentoModel->obterTotaisMes($mes, $ano);
        $lancamentos = array_slice($lancamentoModel->obterPorMes($mes, $ano), 0, 8);
        $comprovantesPendentes = (new ComprovantePix())->obterPendentes();
        $regularidadeResumo = $regularidadeModel->obterResumoMes($mes, $ano);
        $regularidadeLista = array_slice($regularidadeModel->obterPorMes($mes, $ano), 0, 12);
        $fechamento = (new FechamentoMensal())->obter($mes, $ano);
        $obreirosPainel = array_slice($obrigacaoModel->listarResumoTesouraria([
            'busca' => '',
            'somente_em_aberto' => true,
        ]), 0, 12);
        $sessoesFinanceiras = array_slice((new Sessao())->listarFuturas(8), 0, 5);
        $obreirosAtivos = (new Obreiro())->getAllAtivos();

        require __DIR__ . '/../Views/pwa/tesouraria.php';
    }
}
