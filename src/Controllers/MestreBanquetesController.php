<?php

namespace App\Controllers;

use App\Models\Presenca;
use App\Models\Sessao;

class MestreBanquetesController
{
    public function index(): void
    {
        $sessaoModel = new Sessao();
        $presencaModel = new Presenca();

        $proximaSessao = $sessaoModel->obterProximaSessao();
        $sessoes = $sessaoModel->listarFuturas(8);
        $confirmados = [];
        $participantesAgape = [];
        $descricaoAgape = null;
        $descricaoModeloFinanceiroAgape = null;

        if ($proximaSessao && !empty($proximaSessao['id'])) {
            $sessaoId = (int) $proximaSessao['id'];
            $confirmados = $presencaModel->listarConfirmadosPorSessao($sessaoId);
            $participantesAgape = $presencaModel->listarParticipantesAgapePorSessao($sessaoId);
            $descricaoAgape = $sessaoModel->obterDescricaoAgape($proximaSessao);
            $descricaoModeloFinanceiroAgape = $sessaoModel->obterDescricaoModeloFinanceiroAgape($proximaSessao);
        }

        require_once __DIR__ . '/../Views/mestre_banquetes/index.php';
    }
}
