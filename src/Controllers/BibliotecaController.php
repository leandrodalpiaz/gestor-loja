<?php

namespace App\Controllers;

use App\Models\Acervo;
use App\Models\BibliotecaLojaConfig;
use App\Models\ComentarioBiblioteca;
use App\Models\Emprestimo;
use App\Models\EmprestimoInterloja;
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
        $bibliotecaPermissions = $this->resolverPermissoes();
        $catalogScope = $scope;
        $bibliotecaRedeHabilitada = $redeHabilitada;
        $bibliotecaLojasRede = $lojasRede;
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
        $podeSolicitar = !empty($_SESSION['usuario_logado']) || !empty($_SESSION['usuario_id']);
        require_once __DIR__ . '/../Views/biblioteca/detalhes.php';
    }

    public function solicitar(int $acervoId, string $obreiroId): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $acervoId <= 0 || trim($obreiroId) === '') {
            header('Location: /biblioteca?erro=1');
            exit;
        }

        $lojaOrigemId = (int) ($_POST['loja_id'] ?? 0);
        $scope = strtolower(trim((string) ($_POST['scope'] ?? '')));
        $redeConfig = (new BibliotecaLojaConfig())->obterDaLojaAtual();
        $redeHabilitada = !empty($redeConfig['compartilhar_acervo']);
        $emprestimoCruzado = $redeHabilitada && !empty($redeConfig['permitir_emprestimo_cruzado']);
        $solicitacaoRede = $scope === 'rede' || $lojaOrigemId > 0;

        if ($solicitacaoRede) {
            if ($lojaOrigemId <= 0) {
                header('Location: /biblioteca/detalhes?id=' . $acervoId . '&erro=1');
                exit;
            }
            if (!$emprestimoCruzado) {
                header('Location: /biblioteca/detalhes?id=' . $acervoId . '&loja_id=' . $lojaOrigemId . '&erro=1');
                exit;
            }

            $ok = (new EmprestimoInterloja())->solicitar($acervoId, $lojaOrigemId, $obreiroId);
            header('Location: /biblioteca/detalhes?id=' . $acervoId . '&loja_id=' . $lojaOrigemId . ($ok ? '&pedido=1' : '&erro=1'));
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

    public function montarPayloadMiniapp(
        ?string $obreiroId,
        ?int $acervoId = null,
        string $scope = 'minha',
        ?int $lojaId = null
    ): array
    {
        $redeConfig = (new BibliotecaLojaConfig())->obterDaLojaAtual();
        $redeHabilitada = !empty($redeConfig['compartilhar_acervo']);
        $emprestimoCruzado = $redeHabilitada && !empty($redeConfig['permitir_emprestimo_cruzado']);

        $scope = strtolower(trim($scope));
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
        $itemFoco = null;
        if ($acervoId !== null && $acervoId > 0) {
            $itemFoco = $this->acervoModel->buscarDetalhes($acervoId, $obreiroId, $scope === 'rede' ? $lojaId : null);
        }
        if (!$itemFoco && $itens !== []) {
            $primeiroId = (int) ($itens[0]['id'] ?? 0);
            if ($primeiroId > 0) {
                $itemFoco = $this->acervoModel->buscarDetalhes($primeiroId, $obreiroId, $scope === 'rede' ? (int) ($itens[0]['loja_id'] ?? 0) : null);
            }
        }

        $comentarios = $itemFoco ? $this->comentarioModel->listarPorLivro((int) ($itemFoco['id'] ?? 0)) : [];
        $meusEmprestimos = $obreiroId ? $this->emprestimoModel->listarPorObreiro($obreiroId) : [];
        $emprestimosPendentes = $this->emprestimoModel->listarPendentes();

        return [
            'rede' => [
                'habilitada' => $redeHabilitada,
                'emprestimo_cruzado' => $emprestimoCruzado,
                'scope' => $scope,
                'lojas' => array_map(static function (array $l): array {
                    return [
                        'id' => (int) ($l['id'] ?? 0),
                        'numero_loja' => (string) ($l['numero_loja'] ?? ''),
                        'sigla' => (string) ($l['sigla'] ?? ''),
                        'nome' => (string) ($l['nome'] ?? ''),
                    ];
                }, $lojasRede),
            ],
            'acervo' => array_map(static function (array $item): array {
                return [
                    'id' => (int) ($item['id'] ?? 0),
                    'loja_id' => (int) ($item['loja_id'] ?? 0),
                    'loja_nome' => (string) ($item['loja_nome'] ?? ''),
                    'loja_sigla' => (string) ($item['loja_sigla'] ?? ''),
                    'numero_loja' => (string) ($item['numero_loja'] ?? ''),
                    'codigo_acervo' => (string) ($item['codigo_acervo'] ?? ''),
                    'titulo' => (string) ($item['titulo'] ?? ''),
                    'autor' => (string) ($item['autor'] ?? ''),
                    'capa_url' => (string) ($item['capa_url'] ?? ''),
                    'quantidade_disponivel' => (int) ($item['quantidade_disponivel'] ?? 0),
                    'disponivel' => !empty($item['disponivel']),
                    'grau_recomendado' => (string) ($item['grau_recomendado'] ?? 'Livre'),
                    'total_comentarios' => (int) ($item['total_comentarios'] ?? 0),
                    'total_gostei_sim' => (int) ($item['total_gostei_sim'] ?? 0),
                    'total_gostei_nao' => (int) ($item['total_gostei_nao'] ?? 0),
                ];
            }, $itens),
            'item_foco' => $itemFoco ? [
                'id' => (int) ($itemFoco['id'] ?? 0),
                'loja_id' => (int) ($itemFoco['loja_id'] ?? 0),
                'loja_nome' => (string) ($itemFoco['loja_nome'] ?? ''),
                'loja_sigla' => (string) ($itemFoco['loja_sigla'] ?? ''),
                'numero_loja' => (string) ($itemFoco['numero_loja'] ?? ''),
                'codigo_acervo' => (string) ($itemFoco['codigo_acervo'] ?? ''),
                'titulo' => (string) ($itemFoco['titulo'] ?? ''),
                'autor' => (string) ($itemFoco['autor'] ?? ''),
                'resumo' => (string) ($itemFoco['resumo'] ?? ''),
                'isbn' => (string) ($itemFoco['isbn'] ?? ''),
                'capa_url' => (string) ($itemFoco['capa_url'] ?? ''),
                'quantidade_disponivel' => (int) ($itemFoco['quantidade_disponivel'] ?? 0),
                'grau_recomendado' => (string) ($itemFoco['grau_recomendado'] ?? 'Livre'),
                'nota_instrucao' => (string) ($itemFoco['nota_instrucao'] ?? ''),
                'total_comentarios' => (int) ($itemFoco['total_comentarios'] ?? 0),
                'total_gostei_sim' => (int) ($itemFoco['total_gostei_sim'] ?? 0),
                'total_gostei_nao' => (int) ($itemFoco['total_gostei_nao'] ?? 0),
                'pode_solicitar' => ((int) ($itemFoco['quantidade_disponivel'] ?? 0)) > 0,
            ] : null,
            'comentarios' => array_map(static function (array $comentario): array {
                return [
                    'obreiro_nome' => (string) ($comentario['obreiro_nome'] ?? 'Irmao'),
                    'comentario' => (string) ($comentario['comentario'] ?? ''),
                    'criado_em' => (string) ($comentario['criado_em'] ?? ''),
                ];
            }, $comentarios),
            'meus_emprestimos' => array_map(static function (array $emp): array {
                return [
                    'id' => (int) ($emp['id'] ?? 0),
                    'codigo_acervo' => (string) ($emp['codigo_acervo'] ?? ''),
                    'titulo' => (string) ($emp['titulo'] ?? ''),
                    'data_emprestimo' => (string) ($emp['data_emprestimo'] ?? ''),
                    'data_devolucao_prevista' => (string) ($emp['data_devolucao_prevista'] ?? ''),
                    'status' => (string) ($emp['status'] ?? ''),
                ];
            }, $meusEmprestimos),
            'emprestimos_pendentes' => array_map(static function (array $emp): array {
                return [
                    'id' => (int) ($emp['id'] ?? 0),
                    'codigo_acervo' => (string) ($emp['codigo_acervo'] ?? ''),
                    'titulo' => (string) ($emp['titulo'] ?? ''),
                    'obreiro_nome' => (string) ($emp['obreiro_nome'] ?? ''),
                    'data_devolucao_prevista' => (string) ($emp['data_devolucao_prevista'] ?? ''),
                    'status' => (string) ($emp['status'] ?? ''),
                ];
            }, $emprestimosPendentes),
        ];
    }

    public function solicitarMiniapp(int $acervoId, string $obreiroId, ?int $lojaOrigemId = null, string $scope = 'minha'): array
    {
        if ($acervoId <= 0 || trim($obreiroId) === '') {
            return ['ok' => false, 'erro' => 'Livro ou obreiro inválido para solicitação.'];
        }

        $scope = strtolower(trim($scope));
        $lojaOrigemId = $lojaOrigemId !== null ? (int) $lojaOrigemId : 0;
        $redeConfig = (new BibliotecaLojaConfig())->obterDaLojaAtual();
        $redeHabilitada = !empty($redeConfig['compartilhar_acervo']);
        $emprestimoCruzado = $redeHabilitada && !empty($redeConfig['permitir_emprestimo_cruzado']);
        $solicitacaoRede = $scope === 'rede' || $lojaOrigemId > 0;

        if ($solicitacaoRede) {
            if ($lojaOrigemId <= 0) {
                return ['ok' => false, 'erro' => 'Loja de origem inválida para solicitação na rede.'];
            }
            if (!$emprestimoCruzado) {
                return ['ok' => false, 'erro' => 'Empréstimo entre lojas está desativado.'];
            }

            $ok = (new EmprestimoInterloja())->solicitar($acervoId, $lojaOrigemId, $obreiroId);
            return [
                'ok' => $ok,
                'erro' => $ok ? null : 'Não foi possível registrar o pedido entre lojas.',
            ];
        }

        $ok = $this->emprestimoModel->solicitar($acervoId, $obreiroId);
        return ['ok' => $ok, 'erro' => $ok ? null : 'Não foi possível solicitar o empréstimo.'];
    }

    public function comentarMiniapp(int $acervoId, string $obreiroId, string $comentario): array
    {
        $comentario = trim($comentario);
        if ($acervoId <= 0 || trim($obreiroId) === '' || $comentario === '') {
            return ['ok' => false, 'erro' => 'Comentário inválido para publicação.'];
        }

        $ok = $this->comentarioModel->adicionar($acervoId, $obreiroId, $comentario);
        return ['ok' => $ok, 'erro' => $ok ? null : 'Não foi possível publicar o comentário.'];
    }

    public function reagirMiniapp(int $acervoId, string $obreiroId, bool $gostei): array
    {
        if ($acervoId <= 0 || trim($obreiroId) === '') {
            return ['ok' => false, 'erro' => 'Reação inválida para este item.'];
        }

        $ok = $this->reacaoModel->definir($acervoId, $obreiroId, $gostei);
        return ['ok' => $ok, 'erro' => $ok ? null : 'Não foi possível registrar a reação.'];
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
