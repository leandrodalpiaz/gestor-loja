<?php

namespace App\Controllers;

use App\Models\Cargo;
use App\Models\Obreiro;

class AdminController
{
    public function listarCargos()
    {
        $obreiroModel = new Obreiro();
        $cargoModel = new Cargo();

        $obreiros = $obreiroModel->getAllAtivos();
        $cargosResumo = $cargoModel->listarResumoCargos();
        $historico = $cargoModel->listarHistorico(80);

        require_once __DIR__ . '/../Views/admin/cargos.php';
    }

    public function salvarCargo()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $obreiroId = trim((string) ($_POST['obreiro_id'] ?? ''));
            $cargoCodigo = trim((string) ($_POST['cargo_codigo'] ?? ''));
            $observacao = trim((string) ($_POST['observacao'] ?? ''));

            if ($obreiroId !== '' && $cargoCodigo !== '') {
                try {
                    $cargoModel = new Cargo();
                    $cargoModel->atribuirPorCodigo($cargoCodigo, $obreiroId, $observacao !== '' ? $observacao : null);
                    $_SESSION['mensagem_sucesso'] = 'Titularidade atualizada com sucesso.';
                } catch (\Throwable $e) {
                    $_SESSION['mensagem_erro'] = 'Não foi possível atualizar o cargo: ' . $e->getMessage();
                }
            } else {
                $_SESSION['mensagem_erro'] = 'Selecione um cargo e um obreiro para concluir a troca.';
            }
        }

        header('Location: /admin/cargos');
        exit;
    }
}
