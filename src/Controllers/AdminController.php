<?php

namespace App\Controllers;

use App\Models\Cargo;
use App\Models\ConfiguracaoLoja;
use App\Models\Gestao;
use App\Models\Obreiro;

class AdminController
{
    public function listarCargos()
    {
        $obreiroModel = new Obreiro();
        $cargoModel = new Cargo();
        $gestaoModel = new Gestao();

        $obreiros = $obreiroModel->getAllAtivos();
        $gestoes = $gestaoModel->listar();
        $gestaoAtual = $gestaoModel->obterAberta();
        $cargosResumo = $cargoModel->listarResumoCargos($gestaoAtual ? (int) $gestaoAtual['id'] : null);
        $historico = $cargoModel->listarHistorico(120, null, $gestaoAtual ? (int) $gestaoAtual['id'] : null);

        require_once __DIR__ . '/../Views/admin/cargos.php';
    }

    public function salvarCargo()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $obreiroId = trim((string) ($_POST['obreiro_id'] ?? ''));
            $cargoCodigo = trim((string) ($_POST['cargo_codigo'] ?? ''));
            $observacao = trim((string) ($_POST['observacao'] ?? ''));
            $gestaoId = (int) ($_POST['gestao_id'] ?? 0);
            $inicioEm = trim((string) ($_POST['inicio_em'] ?? ''));

            if ($obreiroId !== '' && $cargoCodigo !== '') {
                try {
                    (new Cargo())->atribuirPorCodigo(
                        $cargoCodigo,
                        $obreiroId,
                        $observacao !== '' ? $observacao : null,
                        $gestaoId > 0 ? $gestaoId : null,
                        $inicioEm !== '' ? $inicioEm : null
                    );
                    $_SESSION['mensagem_sucesso'] = 'Titularidade atualizada com sucesso.';
                } catch (\Throwable $e) {
                    $_SESSION['mensagem_erro'] = 'Nao foi possivel atualizar o cargo: ' . $e->getMessage();
                }
            } else {
                $_SESSION['mensagem_erro'] = 'Selecione um cargo e um obreiro para concluir a troca.';
            }
        }

        header('Location: /admin/cargos');
        exit;
    }

    public function salvarGestao()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $titulo = trim((string) ($_POST['titulo'] ?? ''));
            $inicioEm = trim((string) ($_POST['inicio_em'] ?? ''));
            $observacao = trim((string) ($_POST['observacao'] ?? ''));

            if ($titulo !== '' && $inicioEm !== '') {
                try {
                    (new Gestao())->criar($titulo, $inicioEm, $observacao !== '' ? $observacao : null);
                    $_SESSION['mensagem_sucesso'] = 'Gestao aberta com sucesso.';
                } catch (\Throwable $e) {
                    $_SESSION['mensagem_erro'] = 'Nao foi possivel abrir a gestao: ' . $e->getMessage();
                }
            } else {
                $_SESSION['mensagem_erro'] = 'Informe titulo e data de inicio para abrir a gestao.';
            }
        }

        header('Location: /admin/cargos');
        exit;
    }

    public function encerrarGestao()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $gestaoId = (int) ($_POST['gestao_id'] ?? 0);
            $encerradaEm = trim((string) ($_POST['encerrada_em'] ?? ''));

            if ($gestaoId > 0) {
                try {
                    (new Gestao())->encerrar($gestaoId, $encerradaEm !== '' ? $encerradaEm : null);
                    $_SESSION['mensagem_sucesso'] = 'Gestao encerrada com sucesso.';
                } catch (\Throwable $e) {
                    $_SESSION['mensagem_erro'] = 'Nao foi possivel encerrar a gestao: ' . $e->getMessage();
                }
            } else {
                $_SESSION['mensagem_erro'] = 'Gestao invalida para encerramento.';
            }
        }

        header('Location: /admin/cargos');
        exit;
    }

    public function configuracoesLoja()
    {
        $configuracao = (new ConfiguracaoLoja())->obter();
        require_once __DIR__ . '/../Views/admin/configuracoes_loja.php';
    }

    public function salvarConfiguracoesLoja()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                (new ConfiguracaoLoja())->salvar($_POST);
                $_SESSION['mensagem_sucesso'] = 'Parametros gerais da Loja atualizados com sucesso.';
            } catch (\Throwable $e) {
                $_SESSION['mensagem_erro'] = 'Nao foi possivel salvar os parametros da Loja: ' . $e->getMessage();
            }
        }

        header('Location: /admin/loja');
        exit;
    }
}
