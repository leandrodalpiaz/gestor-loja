<?php

namespace App\Controllers;

use App\Config\FeatureFlags;
use App\Models\Acervo;
use App\Models\BibliotecaLojaConfig;
use App\Models\ComentarioBiblioteca;
use App\Models\Emprestimo;
use App\Models\ReacaoBiblioteca;

class PwaBibliotecaController
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
        if (!FeatureFlags::pwaBiblioteca()) {
            http_response_code(404);
            echo 'Esta funcionalidade ainda não está habilitada nesta Loja.';
            return;
        }

        $redeConfig = (new BibliotecaLojaConfig())->obterDaLojaAtual();
        $redeHabilitada = !empty($redeConfig['compartilhar_acervo']);

        $scope = strtolower(trim((string) ($_GET['acervo'] ?? $_GET['scope'] ?? 'minha')));
        if (!$redeHabilitada) {
            $scope = 'minha';
        }

        $lojasRede = $redeHabilitada ? (new BibliotecaLojaConfig())->listarLojasCompartilhadas() : [];
        $lojasIds = null;
        if ($scope === 'rede') {
            $lojasIds = array_values(array_unique(array_map(
                static fn (array $l): int => (int) ($l['id'] ?? 0),
                $lojasRede
            )));
        }

        $itens = $this->acervoModel->listarTodos($lojasIds);

        $q = strtolower(trim((string) ($_GET['q'] ?? '')));
        if ($q !== '') {
            $itens = array_values(array_filter($itens, static function (array $item) use ($q): bool {
                $titulo = strtolower((string) ($item['titulo'] ?? ''));
                $autor = strtolower((string) ($item['autor'] ?? ''));
                $tipo = strtolower((string) ($item['tipo'] ?? ''));
                return str_contains($titulo, $q) || str_contains($autor, $q) || str_contains($tipo, $q);
            }));
        }

        $catalogScope = $scope;
        $bibliotecaRedeHabilitada = $redeHabilitada;
        $bibliotecaLojasRede = $lojasRede;

        require __DIR__ . '/../Views/pwa/biblioteca.php';
    }

    public function meusEmprestimos(): void
    {
        if (!FeatureFlags::pwaBiblioteca()) {
            http_response_code(404);
            echo 'Esta funcionalidade ainda não está habilitada nesta Loja.';
            return;
        }

        $usuarioId = trim((string) ($_SESSION['usuario_id'] ?? ''));
        if ($usuarioId === '' || $usuarioId === '0') {
            $_SESSION['mensagem_erro'] = 'Faça login como obreiro real para ver empréstimos.';
            header('Location: /pwa/biblioteca');
            exit;
        }

        $mensagemErro = $_SESSION['mensagem_erro'] ?? null;
        unset($_SESSION['mensagem_erro']);

        $emprestimos = [];
        try {
            $emprestimos = $this->emprestimoModel->listarPorObreiro($usuarioId);
        } catch (\Throwable $e) {
            error_log('Falha ao listar emprestimos PWA: ' . $e->getMessage());
            $mensagemErro = 'Não foi possível carregar seus empréstimos.';
        }

        require __DIR__ . '/../Views/pwa/biblioteca_meus_emprestimos.php';
    }

    public function detalhes(int $id): void
    {
        if (!FeatureFlags::pwaBiblioteca()) {
            http_response_code(404);
            echo 'Esta funcionalidade ainda não está habilitada nesta Loja.';
            return;
        }

        $obreiroId = trim((string) ($_SESSION['usuario_id'] ?? ''));
        $redeConfig = (new BibliotecaLojaConfig())->obterDaLojaAtual();
        $redeHabilitada = !empty($redeConfig['compartilhar_acervo']);
        $lojaId = $redeHabilitada ? (int) ($_GET['loja_id'] ?? 0) : 0;

        $item = $this->acervoModel->buscarDetalhes(
            $id,
            $obreiroId !== '' ? $obreiroId : null,
            $lojaId > 0 ? $lojaId : null
        );

        if (!$item) {
            http_response_code(404);
            echo 'Livro não encontrado.';
            exit;
        }

        $comentarios = $this->comentarioModel->listarPorLivro($id);
        $permissoes = $this->resolverPermissoes();

        require __DIR__ . '/../Views/pwa/biblioteca_detalhes.php';
    }

    public function adicionar(): void
    {
        if (!FeatureFlags::pwaBiblioteca()) {
            http_response_code(404);
            echo 'Esta funcionalidade ainda não está habilitada nesta Loja.';
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $isbn = trim((string) ($_POST['isbn'] ?? ''));
            if ($isbn !== '') {
                $dados = [
                    'isbn' => $isbn,
                    'quantidade_disponivel' => 1,
                    'curador_id' => $_SESSION['usuario_id'] ?? null,
                ];
                $ok = $this->acervoModel->adicionar($dados);
                header('Location: /pwa/biblioteca' . ($ok ? '?sucesso=1' : '?erro=1'));
                exit;
            }
            header('Location: /pwa/biblioteca/adicionar?erro=isbn_vazio');
            exit;
        }

        require __DIR__ . '/../Views/pwa/biblioteca_adicionar.php';
    }

    public function classificar(): void
    {
        $livroId = (int) ($_POST['livro_id'] ?? 0);
        $grau = (string) ($_POST['grau_recomendado'] ?? 'Livre');
        $nota = trim((string) ($_POST['nota_instrucao'] ?? ''));
        $curadorId = trim((string) ($_SESSION['usuario_id'] ?? ''));

        if ($livroId > 0 && $curadorId !== '') {
            $this->acervoModel->atualizarClassificacao($livroId, $grau, $nota, $curadorId);
        }
        
        header('Location: /pwa/biblioteca/detalhes?id=' . $livroId);
        exit;
    }

    public function solicitar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /pwa/biblioteca');
            exit;
        }

        $acervoId = (int) ($_POST['acervo_id'] ?? 0);
        $obreiroId = trim((string) ($_SESSION['usuario_id'] ?? ''));
        if ($acervoId <= 0 || $obreiroId === '' || $obreiroId === '0') {
            header('Location: ' . ($acervoId > 0 ? '/pwa/biblioteca/detalhes?id=' . $acervoId . '&erro=1' : '/pwa/biblioteca?erro=1'));
            exit;
        }

        $ok = $this->emprestimoModel->solicitar($acervoId, $obreiroId);
        header('Location: /pwa/biblioteca/detalhes?id=' . $acervoId . ($ok ? '&solicitado=1' : '&erro=1'));
        exit;
    }

    public function comentar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /pwa/biblioteca');
            exit;
        }

        $acervoId = (int) ($_POST['acervo_id'] ?? 0);
        $obreiroId = trim((string) ($_SESSION['usuario_id'] ?? ''));
        $comentario = trim((string) ($_POST['comentario'] ?? ''));
        $ok = $acervoId > 0 && $obreiroId !== '' && $obreiroId !== '0'
            ? $this->comentarioModel->adicionar($acervoId, $obreiroId, $comentario)
            : false;

        header('Location: /pwa/biblioteca/detalhes?id=' . $acervoId . ($ok ? '&comentado=1' : '&erro=1'));
        exit;
    }

    public function reagir(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /pwa/biblioteca');
            exit;
        }

        $acervoId = (int) ($_POST['acervo_id'] ?? 0);
        $obreiroId = trim((string) ($_SESSION['usuario_id'] ?? ''));
        $gosteiRaw = strtolower(trim((string) ($_POST['gostei'] ?? '')));
        $gostei = in_array($gosteiRaw, ['1', 'sim', 'true'], true);
        $ok = $acervoId > 0 && $obreiroId !== '' && $obreiroId !== '0'
            ? $this->reacaoModel->definir($acervoId, $obreiroId, $gostei)
            : false;

        header('Location: /pwa/biblioteca/detalhes?id=' . $acervoId . ($ok ? '&reagido=1' : '&erro=1'));
        exit;
    }

    public function emprestimos(): void
    {
        $mensagemSucesso = $_SESSION['mensagem_sucesso'] ?? null;
        $mensagemErro = $_SESSION['mensagem_erro'] ?? null;
        unset($_SESSION['mensagem_sucesso'], $_SESSION['mensagem_erro']);

        $emprestimos = $this->emprestimoModel->listarPendentes();
        require __DIR__ . '/../Views/pwa/biblioteca_emprestimos.php';
    }

    public function devolver(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /pwa/biblioteca/emprestimos');
            exit;
        }

        $id = (int) ($_POST['id'] ?? 0);
        $ok = $id > 0 && $this->emprestimoModel->registrarDevolucao($id);
        $_SESSION[$ok ? 'mensagem_sucesso' : 'mensagem_erro'] = $ok
            ? 'Devolução registrada com sucesso.'
            : 'Não foi possível registrar a devolução.';

        header('Location: /pwa/biblioteca/emprestimos');
        exit;
    }

    private function resolverPermissoes(): array
    {
        $permissionMap = $GLOBALS['gestor_loja_permission_map'] ?? null;
        if (!$permissionMap instanceof \App\Core\Authorization\PermissionMap) {
            return [];
        }

        $roles = array_values(array_unique(array_filter(array_map(
            static fn ($role) => strtolower(trim((string) $role)),
            $_SESSION['usuario_cargos'] ?? [$_SESSION['usuario_cargo'] ?? '']
        ))));
        $permissions = $permissionMap->permissionsForRoles($roles);
        $all = in_array('*', $permissions, true);

        return [
            'biblioteca.manage' => $all || in_array('biblioteca.manage', $permissions, true),
            'biblioteca.classificar' => $all || in_array('biblioteca.classificar', $permissions, true),
        ];
    }
}
