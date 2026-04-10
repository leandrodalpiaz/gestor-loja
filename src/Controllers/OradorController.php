<?php

namespace App\Controllers;

use App\Models\Balaustre;
use App\Models\Sessao;

class OradorController
{
    public function index(): void
    {
        $sessaoModel = new Sessao();
        $balaustreModel = new Balaustre();

        $proximaSessao = $sessaoModel->obterProximaSessao();
        $sessoes = $sessaoModel->listarFuturas(8);
        $visitantesResumo = [];

        if ($proximaSessao && !empty($proximaSessao['id'])) {
            $visitantesResumo = $balaustreModel->obterResumoVisitantesPorSessao((int) $proximaSessao['id']);
        }

        require_once __DIR__ . '/../Views/orador/index.php';
    }
}
