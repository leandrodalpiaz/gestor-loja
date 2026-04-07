<?php

namespace App\Controllers;

use App\Models\Balaustre;
use App\Models\Cargo;
use App\Models\Sessao;

class VeneravelController
{
    public function index(): void
    {
        $sessaoModel = new Sessao();
        $balaustreModel = new Balaustre();
        $cargoModel = new Cargo();

        $proximaSessao = $sessaoModel->obterProximaSessao();
        $sessoes = $sessaoModel->listarFuturas(8);
        $balaustresRecentes = $balaustreModel->listarRecentes(20);
        $nominata = $cargoModel->listarResumoCargos();

        $balaustresAptos = array_values(array_filter(
            $balaustresRecentes,
            static fn (array $item): bool => (string) ($item['status'] ?? '') === 'apto_votacao'
        ));
        $balaustresEmVotacao = array_values(array_filter(
            $balaustresRecentes,
            static fn (array $item): bool => (string) ($item['status'] ?? '') === 'em_votacao'
        ));
        $balaustresPendentesDecisao = array_values(array_filter(
            $balaustresRecentes,
            static fn (array $item): bool => in_array((string) ($item['status'] ?? ''), ['apto_votacao', 'em_votacao'], true)
        ));
        $sessoesPendentesAtencao = array_values(array_filter(
            $sessoes,
            static fn (array $item): bool =>
                in_array((string) ($item['status'] ?? ''), ['planejada', 'alterada', 'cancelada'], true)
                || ((int) ($item['total_confirmados'] ?? 0) < 5)
        ));

        $codigosNominataPrincipal = [
            'VENERAVEL',
            'PRIMEIRO_VIGILANTE',
            'SEGUNDO_VIGILANTE',
            'ORADOR',
            'SECRETARIO',
            'TESOUREIRO',
            'CHANCELER',
            'MESTRE_BANQUETES',
            'GUARDA_DA_LEI',
            'ARQUITETO',
            'MESTRE_DE_HARMONIA',
            'HOSPITALEIRO',
        ];
        $nominataPrincipal = array_values(array_filter(
            $nominata,
            static fn (array $item): bool => in_array((string) ($item['codigo'] ?? ''), $codigosNominataPrincipal, true)
        ));

        require_once __DIR__ . '/../Views/veneravel/index.php';
    }

    public function publicarSessao(): void
    {
        $this->executarAcaoSessao('publicar');
    }

    public function cancelarSessao(): void
    {
        $this->executarAcaoSessao('cancelar');
    }

    public function reabrirSessao(): void
    {
        $this->executarAcaoSessao('reabrir');
    }

    public function realizarSessao(): void
    {
        $this->executarAcaoSessao('realizar');
    }

    private function executarAcaoSessao(string $acao): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /veneravel');
            exit;
        }

        $sessaoId = (int) ($_POST['sessao_id'] ?? 0);
        if ($sessaoId <= 0) {
            $_SESSION['mensagem_erro'] = 'Sessao invalida para a acao solicitada.';
            header('Location: /veneravel');
            exit;
        }

        $sessaoModel = new Sessao();
        $autorId = (string) ($_SESSION['usuario_id'] ?? '');
        $autorId = $autorId !== '' ? $autorId : null;

        $ok = false;
        $mensagemSucesso = 'Acao concluida com sucesso.';
        $mensagemErro = 'Nao foi possivel concluir a acao na sessao.';

        switch ($acao) {
            case 'publicar':
                $ok = $sessaoModel->marcarPublicada($sessaoId, $autorId, 'Publicacao autorizada pelo Veneravel Mestre.');
                $mensagemSucesso = 'Sessao publicada com sucesso.';
                $mensagemErro = 'Nao foi possivel publicar a sessao.';
                break;
            case 'cancelar':
                $ok = $sessaoModel->cancelar($sessaoId, $autorId, 'Cancelamento determinado pelo Veneravel Mestre.');
                $mensagemSucesso = 'Sessao cancelada com sucesso.';
                $mensagemErro = 'Nao foi possivel cancelar a sessao.';
                break;
            case 'reabrir':
                $ok = $sessaoModel->reabrir($sessaoId, $autorId, 'Reabertura determinada pelo Veneravel Mestre.');
                $mensagemSucesso = 'Sessao reaberta com sucesso.';
                $mensagemErro = 'Nao foi possivel reabrir a sessao.';
                break;
            case 'realizar':
                $ok = $sessaoModel->marcarRealizada($sessaoId, $autorId, 'Sessao marcada como realizada pelo Veneravel Mestre.');
                $mensagemSucesso = 'Sessao marcada como realizada.';
                $mensagemErro = 'Nao foi possivel marcar a sessao como realizada.';
                break;
        }

        $_SESSION[$ok ? 'mensagem_sucesso' : 'mensagem_erro'] = $ok ? $mensagemSucesso : $mensagemErro;
        header('Location: /veneravel');
        exit;
    }
}
