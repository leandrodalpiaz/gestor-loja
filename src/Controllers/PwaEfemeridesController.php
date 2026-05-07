<?php

namespace App\Controllers;

use App\Models\EfemerideRegistro;

class PwaEfemeridesController
{
    public function index(): void
    {
        $model = new EfemerideRegistro();
        $registros = $model->buscarComFiltros([
            'termo' => trim((string) ($_GET['q'] ?? '')),
            'tipo' => trim((string) ($_GET['tipo'] ?? '')),
            'ativo' => $_GET['ativo'] ?? 'all',
        ], 300);
        $registroEditar = null;
        $editarId = (int) ($_GET['editar'] ?? 0);
        if ($editarId > 0) {
            $registroEditar = $model->findById($editarId);
        }

        $tipos = [
            'aniversario' => 'Aniversario',
            'data_maconica' => 'Data maconica',
            'historico' => 'Fato historico',
            'familia' => 'Familia',
            'outro' => 'Outro',
        ];
        $vinculos = $model->getVinculosPadrao();
        $mensagemSucesso = $_SESSION['mensagem_sucesso'] ?? null;
        $mensagemErro = $_SESSION['mensagem_erro'] ?? null;
        unset($_SESSION['mensagem_sucesso'], $_SESSION['mensagem_erro']);

        require __DIR__ . '/../Views/pwa/efemerides.php';
    }

    public function salvar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /pwa/chancelaria/efemerides');
            exit;
        }

        $id = (int) ($_POST['id'] ?? 0);
        $model = new EfemerideRegistro();
        $ok = $id > 0
            ? $model->atualizar($id, $_POST)
            : $model->create($_POST, $this->currentUserIntOrNull());

        $_SESSION[$ok ? 'mensagem_sucesso' : 'mensagem_erro'] = $ok
            ? 'Efemeride salva com sucesso.'
            : 'Nao foi possivel salvar a efemeride.';

        header('Location: /pwa/chancelaria/efemerides');
        exit;
    }

    public function desativar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /pwa/chancelaria/efemerides');
            exit;
        }

        $id = (int) ($_POST['id'] ?? 0);
        $ok = $id > 0 && (new EfemerideRegistro())->desativar($id);
        $_SESSION[$ok ? 'mensagem_sucesso' : 'mensagem_erro'] = $ok
            ? 'Efemeride desativada.'
            : 'Nao foi possivel desativar a efemeride.';

        header('Location: /pwa/chancelaria/efemerides');
        exit;
    }

    public function excluir(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /pwa/chancelaria/efemerides');
            exit;
        }

        $id = (int) ($_POST['id'] ?? 0);
        $ok = $id > 0 && (new EfemerideRegistro())->excluir($id);
        $_SESSION[$ok ? 'mensagem_sucesso' : 'mensagem_erro'] = $ok
            ? 'Efemeride excluida.'
            : 'Nao foi possivel excluir a efemeride.';

        header('Location: /pwa/chancelaria/efemerides');
        exit;
    }

    private function currentUserIntOrNull(): ?int
    {
        $id = trim((string) ($_SESSION['usuario_id'] ?? ''));
        return ctype_digit($id) ? (int) $id : null;
    }
}
