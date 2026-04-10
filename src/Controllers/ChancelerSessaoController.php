<?php

namespace App\Controllers;

use App\Models\Balaustre;
use App\Models\Obreiro;
use App\Models\Presenca;
use App\Models\PresencaSessao;
use App\Models\Sessao;

class ChancelerSessaoController
{
    public function index(): void
    {
        $sessaoModel = new Sessao();
        $presencaModel = new Presenca();
        $presencaSessaoModel = new PresencaSessao();
        $balaustreModel = new Balaustre();
        $obreiroModel = new Obreiro();

        $proximaSessao = $sessaoModel->obterProximaSessao();
        $sessoes = $sessaoModel->listarFuturas(8);
        $mapaPresencas = [];
        $confirmados = [];
        $visitantesResumo = [];
        $obreiros = $obreiroModel->getAllAtivos();

        if ($proximaSessao && !empty($proximaSessao['id'])) {
            $sessaoId = (int) $proximaSessao['id'];
            $mapaPresencas = $presencaSessaoModel->listarMapaPorSessao($sessaoId);
            $confirmados = $presencaModel->listarConfirmadosPorSessao($sessaoId);
            $visitantesResumo = $balaustreModel->obterResumoVisitantesPorSessao($sessaoId);
        }

        require_once __DIR__ . '/../Views/chanceler_sessao/index.php';
    }

    public function registrarPresenca(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /chanceler/sessao');
            exit;
        }

        $sessaoId = (int) ($_POST['sessao_id'] ?? 0);
        $obreiroId = trim((string) ($_POST['obreiro_id'] ?? ''));
        $presente = isset($_POST['presente']) && $_POST['presente'] === '1';
        $observacao = trim((string) ($_POST['observacao'] ?? ''));
        $autorId = trim((string) ($_SESSION['usuario_id'] ?? ''));

        if ($sessaoId <= 0 || $obreiroId === '') {
            $_SESSION['mensagem_erro'] = 'Dados insuficientes para registrar a presenca.';
            header('Location: /chanceler/sessao');
            exit;
        }

        $ok = (new PresencaSessao())->registrar(
            $sessaoId,
            $obreiroId,
            $presente,
            $autorId !== '' ? $autorId : null,
            $observacao !== '' ? $observacao : null
        );

        $_SESSION[$ok ? 'mensagem_sucesso' : 'mensagem_erro'] = $ok
            ? 'Presenca efetiva atualizada pelo Chanceler.'
            : 'Nao foi possivel atualizar a presenca efetiva.';

        header('Location: /chanceler/sessao');
        exit;
    }
}
