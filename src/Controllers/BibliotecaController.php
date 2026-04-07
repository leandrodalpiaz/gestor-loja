<?php

namespace App\Controllers;

use App\Models\Acervo;
use App\Models\ComentarioBiblioteca;
use App\Models\Emprestimo;
use App\Models\ReacaoBiblioteca;

class BibliotecaController
{
    private Acervo $acervoModel;
    private Emprestimo $emprestimoModel;
    private ComentarioBiblioteca $comentarioModel;
    private ReacaoBiblioteca $reacaoModel;

    public function __construct()
    {
        $this->acervoModel = new Acervo();
        $this->emprestimoModel = new Emprestimo();
        $this->comentarioModel = new ComentarioBiblioteca();
        $this->reacaoModel = new ReacaoBiblioteca();
    }

    public function index(): void
    {
        $itens = $this->acervoModel->listarTodos();
        require_once __DIR__ . '/../Views/biblioteca/index.php';
    }

    public function adicionar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $dados = [
                'titulo' => trim((string) ($_POST['titulo'] ?? '')),
                'autor' => trim((string) ($_POST['autor'] ?? '')),
                'resumo' => trim((string) ($_POST['resumo'] ?? '')),
                'tipo' => trim((string) ($_POST['tipo'] ?? '')),
                'grau_restricao' => (int) ($_POST['grau_restricao'] ?? 1),
                'arquivo_url' => trim((string) ($_POST['arquivo_url'] ?? '')),
                'quantidade_disponivel' => (int) ($_POST['quantidade_disponivel'] ?? 0),
                'isbn' => trim((string) ($_POST['isbn'] ?? '')),
                'capa_url' => trim((string) ($_POST['capa_url'] ?? '')),
                'grau_recomendado' => trim((string) ($_POST['grau_recomendado'] ?? 'Livre')),
                'nota_instrucao' => trim((string) ($_POST['nota_instrucao'] ?? '')),
                'curador_id' => $_SESSION['usuario_id'] ?? null,
            ];
            $ok = $this->acervoModel->adicionar($dados);
            header('Location: /biblioteca' . ($ok ? '?sucesso=1' : '?erro=1'));
            exit;
        }
        require_once __DIR__ . '/../Views/biblioteca/adicionar.php';
    }

    public function classificar(): void
    {
        $livroId = (int) ($_POST['livro_id'] ?? 0);
        $grau = (string) ($_POST['grau_recomendado'] ?? 'Livre');
        $nota = (string) ($_POST['nota_instrucao'] ?? '');
        $curadorId = trim((string) ($_SESSION['usuario_id'] ?? ''));

        if ($livroId > 0 && $curadorId !== '') {
            $this->acervoModel->atualizarClassificacao($livroId, $grau, $nota, $curadorId);
        }
        header('Location: /biblioteca');
        exit;
    }

    public function editar(int $id): void
    {
        if ($id <= 0) {
            header('Location: /biblioteca?erro=1');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $dados = [
                'titulo' => trim((string) ($_POST['titulo'] ?? '')),
                'autor' => trim((string) ($_POST['autor'] ?? '')),
                'resumo' => trim((string) ($_POST['resumo'] ?? '')),
                'tipo' => trim((string) ($_POST['tipo'] ?? '')),
                'grau_restricao' => (int) ($_POST['grau_restricao'] ?? 1),
                'arquivo_url' => trim((string) ($_POST['arquivo_url'] ?? '')),
                'quantidade_disponivel' => (int) ($_POST['quantidade_disponivel'] ?? 0),
                'isbn' => trim((string) ($_POST['isbn'] ?? '')),
                'capa_url' => trim((string) ($_POST['capa_url'] ?? '')),
                'grau_recomendado' => trim((string) ($_POST['grau_recomendado'] ?? 'Livre')),
                'nota_instrucao' => trim((string) ($_POST['nota_instrucao'] ?? '')),
                'curador_id' => $_SESSION['usuario_id'] ?? null,
            ];
            $ok = $this->acervoModel->atualizar($id, $dados);
            header('Location: /biblioteca' . ($ok ? '?editado=1' : '?erro=1'));
            exit;
        }

        $item = $this->acervoModel->buscarPorId($id);
        if (!$item) {
            header('Location: /biblioteca?erro=1');
            exit;
        }
        require_once __DIR__ . '/../Views/biblioteca/editar.php';
    }

    public function excluir(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->acervoModel->deletar($id);
            header('Location: /biblioteca');
            exit;
        }
    }

    public function detalhes(int $id): void
    {
        $obreiroId = trim((string) ($_SESSION['usuario_id'] ?? ''));
        $item = $this->acervoModel->buscarDetalhes($id, $obreiroId !== '' ? $obreiroId : null);
        if (!$item) {
            http_response_code(404);
            echo 'Livro nao encontrado.';
            exit;
        }

        $comentarios = $this->comentarioModel->listarPorLivro($id);
        require_once __DIR__ . '/../Views/biblioteca/detalhes.php';
    }

    public function solicitar(int $acervoId, string $obreiroId): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $acervoId <= 0 || trim($obreiroId) === '') {
            header('Location: /biblioteca?erro=1');
            exit;
        }

        $ok = $this->emprestimoModel->solicitar($acervoId, $obreiroId);
        header('Location: /biblioteca/detalhes?id=' . $acervoId . ($ok ? '&solicitado=1' : '&erro=1'));
        exit;
    }

    public function comentar(int $acervoId, string $obreiroId): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $acervoId <= 0 || trim($obreiroId) === '') {
            header('Location: /biblioteca?erro=1');
            exit;
        }

        $comentario = trim((string) ($_POST['comentario'] ?? ''));
        $ok = $this->comentarioModel->adicionar($acervoId, $obreiroId, $comentario);
        header('Location: /biblioteca/detalhes?id=' . $acervoId . ($ok ? '&comentado=1' : '&erro=1'));
        exit;
    }

    public function reagir(int $acervoId, string $obreiroId): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $acervoId <= 0 || trim($obreiroId) === '') {
            header('Location: /biblioteca?erro=1');
            exit;
        }

        $gosteiRaw = strtolower(trim((string) ($_POST['gostei'] ?? '')));
        $gostei = in_array($gosteiRaw, ['1', 'sim', 'true'], true);
        $ok = $this->reacaoModel->definir($acervoId, $obreiroId, $gostei);
        header('Location: /biblioteca/detalhes?id=' . $acervoId . ($ok ? '&reagido=1' : '&erro=1'));
        exit;
    }

    public function meusEmprestimos(string $obreiroId): void
    {
        if (trim($obreiroId) === '') {
            header('Location: /biblioteca?erro=1');
            exit;
        }
        $emprestimos = $this->emprestimoModel->listarPorObreiro($obreiroId);
        require_once __DIR__ . '/../Views/biblioteca/meus_emprestimos.php';
    }

    public function emprestimos(): void
    {
        $emprestimos = $this->emprestimoModel->listarPendentes();
        require_once __DIR__ . '/../Views/biblioteca/emprestimos.php';
    }

    public function devolver(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->emprestimoModel->registrarDevolucao($id);
            header('Location: /biblioteca/emprestimos');
            exit;
        }
    }
}
