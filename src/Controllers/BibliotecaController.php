<?php
namespace App\Controllers;

use App\Models\Acervo;
use App\Models\Emprestimo;

class BibliotecaController
{
    private Acervo $acervoModel;
    private Emprestimo $emprestimoModel;

    public function __construct()
    {
        $this->acervoModel = new Acervo();
        $this->emprestimoModel = new Emprestimo();
    }

    public function index()
    {
        $itens = $this->acervoModel->listarTodos();
        require_once __DIR__ . '/../Views/biblioteca/index.php';
    }

    public function adicionar()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $dados = [
                'titulo' => trim($_POST['titulo'] ?? ''),
                'autor' => trim($_POST['autor'] ?? ''),
                'tipo' => trim($_POST['tipo'] ?? ''),
                'grau_restricao' => (int) ($_POST['grau_restricao'] ?? 1),
                'arquivo_url' => trim($_POST['arquivo_url'] ?? ''),
                'quantidade_disponivel' => (int) ($_POST['quantidade_disponivel'] ?? 0),
                'isbn' => trim($_POST['isbn'] ?? ''),
                'capa_url' => trim($_POST['capa_url'] ?? ''),
                'grau_recomendado' => trim($_POST['grau_recomendado'] ?? 'Livre'),
                'nota_instrucao' => trim($_POST['nota_instrucao'] ?? ''),
            ];
            $this->acervoModel->adicionar($dados);
            header('Location: /biblioteca');
            exit;
        }
        require_once __DIR__ . '/../Views/biblioteca/adicionar.php';
    }

    public function classificar()
    {
        $livroId = $_POST['livro_id'] ?? null;
        $grau = $_POST['grau_recomendado'] ?? 'Livre';
        $nota = $_POST['nota_instrucao'] ?? '';
        $curadorId = $_SESSION['usuario_id'] ?? null;

        if ($livroId && $curadorId) {
            $this->acervoModel->atualizarClassificacao($livroId, $grau, $nota, $curadorId);
        }
        header("Location: /biblioteca");
        exit;
    }

    public function editar($id)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $dados = [
                'titulo' => trim($_POST['titulo'] ?? ''),
                'autor' => trim($_POST['autor'] ?? ''),
                'tipo' => trim($_POST['tipo'] ?? ''),
                'grau_restricao' => (int) ($_POST['grau_restricao'] ?? 1),
                'arquivo_url' => trim($_POST['arquivo_url'] ?? ''),
                'quantidade_disponivel' => (int) ($_POST['quantidade_disponivel'] ?? 0),
            ];
            $this->acervoModel->atualizar($id, $dados);
            header('Location: /biblioteca');
            exit;
        }
    }

    public function excluir($id)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->acervoModel->deletar($id);
            header('Location: /biblioteca');
            exit;
        }
    }

    public function emprestimos()
    {
        $emprestimos = $this->emprestimoModel->listarPendentes();
        require_once __DIR__ . '/../Views/biblioteca_emprestimos.php';
    }

    public function devolver($id)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->emprestimoModel->registrarDevolucao($id);
            header('Location: /biblioteca/emprestimos');
            exit;
        }
    }
}