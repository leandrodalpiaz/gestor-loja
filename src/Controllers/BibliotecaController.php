<?php

namespace App\Controllers;

use App\Core\Http\WebGuards;
use App\Models\Acervo;
use App\Models\BibliotecaLojaConfig;
use App\Models\ComentarioBiblioteca;
use App\Models\Emprestimo;
use App\Models\EmprestimoInterloja;
use App\Models\ReacaoBiblioteca;
use App\Models\TrabalhoSessao;
use App\Models\Balaustre;
use App\Models\Obreiro;
use App\Services\LivroMetadataService;

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
        $bibliotecaRedeConfig = $redeConfig;
        $bibliotecaLojasRede = $lojasRede;
        require_once __DIR__ . '/../Views/biblioteca/index.php';
    }

    public function trabalhos(): void
    {
        // Área de pesquisa: trabalhos arquivados pela Secretaria (PDF quando disponível).
        $itens = (new TrabalhoSessao())->listarRecentes(120);

        $obreiroId = trim((string) ($_SESSION['usuario_id'] ?? ''));
        $grauObreiro = 'aprendiz';
        $isSystemAdmin = !empty($_SESSION['is_system_admin']) || !empty($_SESSION['force_system_admin']) || $obreiroId === '0';

        if ($obreiroId !== '' && !$isSystemAdmin) {
            $obreiro = (new Obreiro())->findById($obreiroId);
            $grauObreiro = strtolower((string) ($obreiro['grau'] ?? 'aprendiz'));
        } elseif ($isSystemAdmin) {
            $grauObreiro = 'mestre';
        }

        $ord = static function (string $g): int {
            $g = strtolower($g);
            return match (true) {
                str_contains($g, 'mestre') => 3,
                str_contains($g, 'companheiro') => 2,
                default => 1,
            };
        };

        $grauUserVal = $ord($grauObreiro);
        $itensFiltrados = [];
        foreach ($itens as $item) {
            $grauSessao = strtolower((string) ($item['grau_sessao'] ?? 'aprendiz'));
            if ($grauUserVal >= $ord($grauSessao)) {
                $itensFiltrados[] = $item;
            }
        }
        $itens = $itensFiltrados;

        require_once __DIR__ . '/../Views/biblioteca/trabalhos.php';
    }

    public function arquivoBalaustres(): void
    {
        // Área de pesquisa: balaústres aprovados.
        $db = \App\Config\Database::getConnection();
        $tenantId = (int) ($_SESSION['tenant_id'] ?? 0);
        $stmt = $db->prepare("
            SELECT b.id, b.numero_balaustre, b.status, b.updated_at, s.titulo AS sessao_titulo, s.data_hora_inicio, s.grau_sessao
            FROM balaustres b
            JOIN sessoes s ON s.id = b.sessao_id
            WHERE s.loja_id = :loja_id
              AND b.status = 'aprovado'
            ORDER BY s.data_hora_inicio DESC, b.id DESC
            LIMIT 200
        ");
        $stmt->execute(['loja_id' => $tenantId]);
        $itens = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        require_once __DIR__ . '/../Views/biblioteca/balaustres.php';
    }

    public function visualizarBalaustreAprovado(int $id, string $obreiroId): void
    {
        if ($id <= 0) {
            header('Location: /biblioteca/balaustres');
            exit;
        }

        $tenantId = (int) ($_SESSION['tenant_id'] ?? 0);
        $balaustreModel = new Balaustre();
        $balaustre = $balaustreModel->buscarPorId($id);
        if (!$balaustre) {
            header('Location: /biblioteca/balaustres');
            exit;
        }

        // Garantir que o balaústre é da loja atual e está aprovado.
        $db = \App\Config\Database::getConnection();
        $stmt = $db->prepare("
            SELECT b.*, s.grau_sessao, s.titulo AS sessao_titulo, s.data_hora_inicio
            FROM balaustres b
            JOIN sessoes s ON s.id = b.sessao_id
            WHERE b.id = :id
              AND s.loja_id = :loja_id
              AND b.status = 'aprovado'
            LIMIT 1
        ");
        $stmt->execute(['id' => $id, 'loja_id' => $tenantId]);
        $balaustre = $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
        if (!$balaustre) {
            header('Location: /biblioteca/balaustres');
            exit;
        }

        // Filtro simples por grau (sem categorias especiais neste primeiro passo).
        $obreiro = (new Obreiro())->findById($obreiroId);
        $grauObreiro = strtolower((string) ($obreiro['grau'] ?? ''));
        $grauBalaustre = strtolower((string) ($balaustre['grau_sessao'] ?? 'aprendiz'));

        $ord = static function (string $g): int {
            return match (true) {
                str_contains($g, 'mestre') => 3,
                str_contains($g, 'companheiro') => 2,
                default => 1,
            };
        };

        if ($ord($grauObreiro) < $ord($grauBalaustre)) {
            header('Location: /biblioteca/balaustres');
            exit;
        }

        require_once __DIR__ . '/../Views/biblioteca/balaustre_visualizar.php';
    }

    public function configurarRede(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /biblioteca');
            exit;
        }

        $compartilharAcervo = ($_POST['compartilhar_acervo'] ?? '') === '1';
        $permitirEmprestimoCruzado = ($_POST['permitir_emprestimo_cruzado'] ?? '') === '1';
        $ok = (new BibliotecaLojaConfig())->salvarDaLojaAtual($compartilharAcervo, $permitirEmprestimoCruzado);

        $_SESSION[$ok ? 'mensagem_sucesso' : 'mensagem_erro'] = $ok
            ? 'Configuração da rede de bibliotecas atualizada.'
            : 'Não foi possível salvar a configuração da rede de bibliotecas.';

        header('Location: /biblioteca');
        exit;
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
                'curador_id' => $this->currentUserIdOrNull(),
            ];
            $ok = $this->acervoModel->adicionar($dados);
            header('Location: /biblioteca' . ($ok ? '?sucesso=1' : '?erro=1'));
            exit;
        }
        require_once __DIR__ . '/../Views/biblioteca/adicionar.php';
    }

    public function buscarIsbn(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $isbn = trim((string) ($_GET['isbn'] ?? ''));
        if ($isbn === '') {
            http_response_code(422);
            echo json_encode(['ok' => false, 'erro' => 'Informe o ISBN.']);
            return;
        }

        $metadata = (new LivroMetadataService())->buscarPorIsbn($isbn);
        if (!$metadata) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'erro' => 'ISBN não encontrado.']);
            return;
        }

        echo json_encode(['ok' => true, 'dados' => $metadata], JSON_UNESCAPED_UNICODE);
    }

    public function importar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /biblioteca/adicionar');
            exit;
        }
        WebGuards::requireValidCsrf('/biblioteca/adicionar');

        $arquivo = $_FILES['arquivo_acervo'] ?? null;
        if (!is_array($arquivo) || (int) ($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $_SESSION['mensagem_erro'] = 'Envie um arquivo CSV para importar.';
            header('Location: /biblioteca/adicionar');
            exit;
        }

        $handle = fopen((string) ($arquivo['tmp_name'] ?? ''), 'rb');
        if (!$handle) {
            $_SESSION['mensagem_erro'] = 'Não foi possível ler o arquivo enviado.';
            header('Location: /biblioteca/adicionar');
            exit;
        }

        $primeiraLinha = fgets($handle);
        $delimitador = substr_count((string) $primeiraLinha, ';') > substr_count((string) $primeiraLinha, ',') ? ';' : ',';
        $header = str_getcsv((string) $primeiraLinha, $delimitador);
        if (!is_array($header)) {
            fclose($handle);
            $_SESSION['mensagem_erro'] = 'CSV sem cabeçalho válido.';
            header('Location: /biblioteca/adicionar');
            exit;
        }

        $normalizar = static function (string $valor): string {
            $valor = strtolower(trim($valor));
            $valor = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $valor) ?: $valor;
            return preg_replace('/[^a-z0-9]+/', '_', $valor) ?? '';
        };
        $chaves = array_map(static fn($coluna): string => $normalizar((string) $coluna), $header);

        $importados = 0;
        $ignorados = 0;
        while (($linha = fgetcsv($handle, 0, $delimitador)) !== false) {
            $row = [];
            foreach ($chaves as $idx => $chave) {
                $row[$chave] = trim((string) ($linha[$idx] ?? ''));
            }

            $dados = [
                'titulo' => $row['titulo'] ?? $row['title'] ?? '',
                'autor' => $row['autor'] ?? $row['author'] ?? '',
                'isbn' => $row['isbn'] ?? '',
                'resumo' => $row['resumo'] ?? $row['descricao'] ?? '',
                'tipo' => $row['tipo'] ?? 'Livro Fisico',
                'quantidade_disponivel' => $row['quantidade'] ?? $row['quantidade_disponivel'] ?? 1,
                'grau_recomendado' => $row['grau_recomendado'] ?? $row['grau'] ?? 'Livre',
                'nota_instrucao' => $row['nota_instrucao'] ?? $row['observacao'] ?? '',
                'capa_url' => $row['capa_url'] ?? '',
                'curador_id' => $this->currentUserIdOrNull(),
            ];

            if ($this->acervoModel->adicionar($dados)) {
                $importados++;
            } else {
                $ignorados++;
            }
        }
        fclose($handle);

        $_SESSION['mensagem_sucesso'] = sprintf('Importação concluída: %d livro(s) importado(s), %d linha(s) ignorada(s).', $importados, $ignorados);
        header('Location: /biblioteca');
        exit;
    }

    public function classificar(): void
    {
        $livroId = (int) ($_POST['livro_id'] ?? 0);
        $grau = (string) ($_POST['grau_recomendado'] ?? 'Livre');
        $nota = (string) ($_POST['nota_instrucao'] ?? '');
        $curadorId = $this->currentUserIdOrNull();

        if ($livroId > 0) {
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
                'curador_id' => $this->currentUserIdOrNull(),
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
        $pedidosInterloja = (new EmprestimoInterloja())->listarPorObreiroDaLojaAtual($obreiroId);
        require_once __DIR__ . '/../Views/biblioteca/meus_emprestimos.php';
    }

    public function emprestimos(): void
    {
        $emprestimos = $this->emprestimoModel->listarPendentes();
        $pedidosInterloja = (new EmprestimoInterloja())->listarRecebidasDaLojaAtual();
        require_once __DIR__ . '/../Views/biblioteca/emprestimos.php';
    }

    public function decidirInterloja(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /biblioteca/emprestimos');
            exit;
        }

        $pedidoId = (int) ($_POST['pedido_id'] ?? 0);
        $decisao = trim((string) ($_POST['decisao'] ?? ''));
        $ok = $pedidoId > 0 && (new EmprestimoInterloja())->decidir($pedidoId, $decisao, $this->currentUserIdOrNull());

        $_SESSION[$ok ? 'mensagem_sucesso' : 'mensagem_erro'] = $ok
            ? 'Pedido interloja atualizado.'
            : 'Não foi possível atualizar o pedido interloja.';

        header('Location: /biblioteca/emprestimos');
        exit;
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

        // Obter grau do obreiro logado para filtragem
        $isSystemAdmin = !empty($_SESSION['is_system_admin']) || !empty($_SESSION['force_system_admin']) || $obreiroId === '0' || $obreiroId === '';
        $grauLeitor = 1; // Padrão
        if ($obreiroId && !$isSystemAdmin) {
            $obreiro = (new Obreiro())->findById($obreiroId);
            $grauStr = strtolower((string) ($obreiro ? ($obreiro['grau'] ?? '') : ''));
            if (str_contains($grauStr, 'mestre')) {
                $grauLeitor = 3;
            } elseif (str_contains($grauStr, 'companheiro')) {
                $grauLeitor = 2;
            } else {
                $grauLeitor = 1;
            }
        } elseif ($isSystemAdmin) {
            $grauLeitor = 3;
        }

        $itensFiltrados = [];
        foreach ($itens as $item) {
            $tipo = (string) ($item['tipo'] ?? '');
            $grauRestricao = max(1, (int) ($item['grau_restricao'] ?? 1));
            $isBook = !in_array($tipo, ['Peca de Arquitetura', 'Trabalho de Instrucao'], true);
            
            if ($isBook) {
                $item['bloqueado'] = false;
                $itensFiltrados[] = $item;
            } else {
                if ($grauLeitor >= $grauRestricao) {
                    $item['bloqueado'] = false;
                    $itensFiltrados[] = $item;
                } else {
                    $item['bloqueado'] = true;
                    $item['arquivo_url'] = null;
                    $itensFiltrados[] = $item;
                }
            }
        }
        $itens = $itensFiltrados;

        $itemFoco = null;
        if ($acervoId !== null && $acervoId > 0) {
            $itemFoco = $this->acervoModel->buscarDetalhes($acervoId, $obreiroId, $scope === 'rede' ? $lojaId : null);
        }

        if ($itemFoco) {
            $tipoFoco = (string) ($itemFoco['tipo'] ?? '');
            $grauRestricaoFoco = max(1, (int) ($itemFoco['grau_restricao'] ?? 1));
            $isBookFoco = !in_array($tipoFoco, ['Peca de Arquitetura', 'Trabalho de Instrucao'], true);
            
            if (!$isBookFoco && $grauLeitor < $grauRestricaoFoco) {
                $itemFoco['bloqueado'] = true;
                $itemFoco['arquivo_url'] = null;
                $itemFoco['resumo'] = 'Acesso restrito ao grau ' . ($grauRestricaoFoco === 3 ? 'Mestre' : 'Companheiro') . '.';
                $itemFoco['pode_solicitar'] = false;
            } else {
                $itemFoco['bloqueado'] = false;
            }
        }

        $comentarios = $itemFoco ? $this->comentarioModel->listarPorLivro((int) ($itemFoco['id'] ?? 0)) : [];
        $meusEmprestimos = $obreiroId ? $this->emprestimoModel->listarPorObreiro($obreiroId) : [];
        $meusPedidosInterloja = $obreiroId ? (new EmprestimoInterloja())->listarPorObreiroDaLojaAtual($obreiroId) : [];
        $emprestimosPendentes = $this->emprestimoModel->listarPendentes();
        $pedidosInterlojaRecebidos = (new EmprestimoInterloja())->listarRecebidasDaLojaAtual();
        $permissoes = $this->resolverPermissoes();

        return [
            'permissoes' => $permissoes,
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
                    'nota_instrucao' => (string) ($item['nota_instrucao'] ?? ''),
                    'total_comentarios' => (int) ($item['total_comentarios'] ?? 0),
                    'total_gostei_sim' => (int) ($item['total_gostei_sim'] ?? 0),
                    'total_gostei_nao' => (int) ($item['total_gostei_nao'] ?? 0),
                    'tipo' => (string) ($item['tipo'] ?? ''),
                    'arquivo_url' => !empty($item['bloqueado']) ? null : (string) ($item['arquivo_url'] ?? ''),
                    'bloqueado' => !empty($item['bloqueado']),
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
                'pode_solicitar' => empty($itemFoco['bloqueado']) && ((int) ($itemFoco['quantidade_disponivel'] ?? 0)) > 0,
                'tipo' => (string) ($itemFoco['tipo'] ?? ''),
                'arquivo_url' => !empty($itemFoco['bloqueado']) ? null : (string) ($itemFoco['arquivo_url'] ?? ''),
                'bloqueado' => !empty($itemFoco['bloqueado']),
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
            'meus_pedidos_interloja' => $meusPedidosInterloja,
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
            'pedidos_interloja_recebidos' => $pedidosInterlojaRecebidos,
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

    public function devolverMiniapp(int $emprestimoId): array
    {
        $ok = $emprestimoId > 0 && $this->emprestimoModel->registrarDevolucao($emprestimoId);
        return ['ok' => $ok, 'erro' => $ok ? null : 'Não foi possível registrar a devolução.'];
    }

    public function decidirInterlojaMiniapp(int $pedidoId, string $decisao, ?string $autorId): array
    {
        $ok = $pedidoId > 0 && (new EmprestimoInterloja())->decidir($pedidoId, $decisao, $autorId);
        return ['ok' => $ok, 'erro' => $ok ? null : 'Não foi possível atualizar o pedido interloja.'];
    }

    public function classificarMiniapp(int $livroId, string $grau, string $nota, ?string $curadorId): array
    {
        $ok = $livroId > 0 && $this->acervoModel->atualizarClassificacao(
            $livroId,
            trim($grau) !== '' ? trim($grau) : 'Livre',
            trim($nota),
            $curadorId
        );
        return ['ok' => $ok, 'erro' => $ok ? null : 'Não foi possível atualizar a classificação.'];
    }

    public function buscarIsbnMiniapp(string $isbn): array
    {
        $isbn = trim($isbn);
        if ($isbn === '') {
            return ['ok' => false, 'erro' => 'Informe o ISBN para buscar.'];
        }
        $metadata = (new LivroMetadataService())->buscarPorIsbn($isbn);
        if (!$metadata) {
            return ['ok' => false, 'erro' => 'ISBN não encontrado nos servidores literários.'];
        }
        return ['ok' => true, 'dados' => $metadata];
    }

    // ─── CRUD de obras (Miniapp/Angular) ────────────────────────────────

    public function adicionarMiniapp(array $dados, ?string $autorId): array
    {
        $titulo = trim((string) ($dados['titulo'] ?? ''));
        if ($titulo === '') {
            return ['ok' => false, 'erro' => 'O título da obra é obrigatório.'];
        }

        $payload = [
            'titulo' => $titulo,
            'autor' => trim((string) ($dados['autor'] ?? '')),
            'resumo' => trim((string) ($dados['resumo'] ?? '')),
            'tipo' => trim((string) ($dados['tipo'] ?? 'Livro Físico')),
            'grau_restricao' => (int) ($dados['grau_restricao'] ?? 1),
            'arquivo_url' => trim((string) ($dados['arquivo_url'] ?? '')),
            'quantidade_disponivel' => (int) ($dados['quantidade_disponivel'] ?? 1),
            'isbn' => trim((string) ($dados['isbn'] ?? '')),
            'capa_url' => trim((string) ($dados['capa_url'] ?? '')),
            'grau_recomendado' => trim((string) ($dados['grau_recomendado'] ?? 'Livre')),
            'nota_instrucao' => trim((string) ($dados['nota_instrucao'] ?? '')),
            'curador_id' => $autorId,
        ];

        $id = $this->acervoModel->adicionar($payload);
        if ($id) {
            return ['ok' => true, 'id' => $id, 'dados' => $this->acervoModel->buscarPorId($id)];
        }
        return ['ok' => false, 'erro' => 'Não foi possível adicionar a obra.'];
    }

    public function editarMiniapp(int $id, array $dados): array
    {
        if ($id <= 0) {
            return ['ok' => false, 'erro' => 'ID da obra inválido.'];
        }

        $obra = $this->acervoModel->buscarPorId($id);
        if (!$obra) {
            return ['ok' => false, 'erro' => 'Obra não encontrada.'];
        }

        $payload = [
            'titulo' => trim((string) ($dados['titulo'] ?? $obra['titulo'])),
            'autor' => trim((string) ($dados['autor'] ?? $obra['autor'])),
            'resumo' => trim((string) ($dados['resumo'] ?? $obra['resumo'])),
            'tipo' => trim((string) ($dados['tipo'] ?? $obra['tipo'])),
            'grau_restricao' => (int) ($dados['grau_restricao'] ?? $obra['grau_restricao']),
            'arquivo_url' => trim((string) ($dados['arquivo_url'] ?? $obra['arquivo_url'])),
            'quantidade_disponivel' => (int) ($dados['quantidade_disponivel'] ?? $obra['quantidade_disponivel']),
            'isbn' => trim((string) ($dados['isbn'] ?? $obra['isbn'])),
            'capa_url' => trim((string) ($dados['capa_url'] ?? $obra['capa_url'])),
            'grau_recomendado' => trim((string) ($dados['grau_recomendado'] ?? $obra['grau_recomendado'])),
            'nota_instrucao' => trim((string) ($dados['nota_instrucao'] ?? $obra['nota_instrucao'])),
        ];

        $ok = $this->acervoModel->atualizar($id, $payload);
        if ($ok) {
            return ['ok' => true, 'dados' => $this->acervoModel->buscarPorId($id)];
        }
        return ['ok' => false, 'erro' => 'Não foi possível atualizar a obra.'];
    }

    public function excluirMiniapp(int $id): array
    {
        if ($id <= 0) {
            return ['ok' => false, 'erro' => 'ID da obra inválido.'];
        }

        $obra = $this->acervoModel->buscarPorId($id);
        if (!$obra) {
            return ['ok' => false, 'erro' => 'Obra não encontrada.'];
        }

        $ok = $this->acervoModel->deletar($id);
        return ['ok' => $ok, 'erro' => $ok ? null : 'Não foi possível excluir a obra.'];
    }

    private function resolverPermissoes(): array
    {
        if (!empty($_SESSION['is_system_admin']) || !empty($_SESSION['force_system_admin']) || (string) ($_SESSION['usuario_id'] ?? '') === '0') {
            return [
                'biblioteca.manage' => true,
                'biblioteca.classificar' => true,
            ];
        }

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

    private function currentUserIdOrNull(): ?string
    {
        $id = trim((string) ($_SESSION['usuario_id'] ?? ''));
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $id) === 1 ? $id : null;
    }
}
