<?php

namespace App\Controllers;

use App\Config\FeatureFlags;
use App\Models\Comunicado;

class PwaComunicacaoController
{
    public function index(): void
    {
        if (!FeatureFlags::pwaComunicacao()) {
            http_response_code(404);
            echo 'Recurso indisponível.';
            return;
        }

        $comunicados = [];
        $erroDb = null;

        try {
            $comunicados = (new Comunicado())->listarRecentes(40);
        } catch (\Throwable $e) {
            $erroDb = 'Módulo ainda não inicializado no banco deste ambiente.';
            error_log('Falha ao listar comunicados: ' . $e->getMessage());
        }

        require __DIR__ . '/../Views/pwa/comunicacao.php';
    }

    public function ler(): void
    {
        if (!FeatureFlags::pwaComunicacao()) {
            http_response_code(404);
            echo 'Recurso indisponível.';
            return;
        }

        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            header('Location: /pwa/comunicacao');
            exit;
        }

        $model = new Comunicado();
        $comunicado = null;
        $erroDb = null;

        try {
            $comunicado = $model->findById($id);
            $usuarioId = trim((string) ($_SESSION['usuario_id'] ?? ''));
            if ($comunicado && $usuarioId !== '' && $usuarioId !== '0') {
                $model->marcarLeitura($id, $usuarioId);
            }
        } catch (\Throwable $e) {
            $erroDb = 'Módulo ainda não inicializado no banco deste ambiente.';
            error_log('Falha ao abrir comunicado: ' . $e->getMessage());
        }

        require __DIR__ . '/../Views/pwa/comunicacao_ler.php';
    }
}

