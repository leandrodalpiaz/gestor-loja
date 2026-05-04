<?php

namespace App\Controllers;

use App\Config\FeatureFlags;
use App\Models\Acervo;
use App\Models\BibliotecaLojaConfig;
use App\Models\Emprestimo;

class PwaBibliotecaController
{
    private Acervo $acervoModel;

    public function __construct()
    {
        $this->acervoModel = new Acervo();
    }

    public function index(): void
    {
        if (!FeatureFlags::pwaBiblioteca()) {
            http_response_code(404);
            echo 'Recurso indisponível.';
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
            echo 'Recurso indisponível.';
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
            $emprestimos = (new Emprestimo())->listarPendentesPorObreiro($usuarioId);
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
            echo 'Recurso indisponível.';
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

        $comentarios = (new \App\Models\ComentarioBiblioteca())->listarPorLivro($id);
        $permissoes = $this->resolverPermissoes();

        require __DIR__ . '/../Views/pwa/biblioteca_detalhes.php';
    }

    public function adicionar(): void
    {
        if (!FeatureFlags::pwaBiblioteca()) {
            http_response_code(404);
            echo 'Recurso indisponível.';
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
