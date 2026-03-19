<?php
namespace App\Controllers;

use App\Models\Obreiro;

class AdminController
{
    // Lista todos os cargos dos obreiros ativos
    public function listarCargos()
    {
        $obreiroModel = new Obreiro();
        $obreiros = $obreiroModel->getAllAtivos();
        require_once __DIR__ . '/../Views/admin/cargos.php';
    }

    // Salva alteração de cargo
    public function salvarCargo()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'] ?? null;
            $novoCargo = $_POST['cargo'] ?? null;
            if ($id && $novoCargo) {
                $obreiroModel = new Obreiro();
                $obreiroModel->atualizarCargo($id, $novoCargo);
                $_SESSION['mensagem_sucesso'] = 'Cargo atualizado com sucesso!';
            }
        }
        header('Location: /admin/cargos');
        exit;
    }
}
