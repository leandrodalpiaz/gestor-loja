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
}
