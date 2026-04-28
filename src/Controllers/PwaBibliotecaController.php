<?php

namespace App\Controllers;

use App\Config\FeatureFlags;
use App\Models\Acervo;
use App\Models\BibliotecaLojaConfig;

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
}

