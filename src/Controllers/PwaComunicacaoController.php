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

    public function novo(): void
    {
        if (!FeatureFlags::pwaComunicacao()) {
            http_response_code(404);
            echo 'Recurso indisponível.';
            return;
        }

        $mensagemSucesso = $_SESSION['mensagem_sucesso'] ?? null;
        $mensagemErro = $_SESSION['mensagem_erro'] ?? null;
        unset($_SESSION['mensagem_sucesso'], $_SESSION['mensagem_erro']);

        $erroDb = null;
        $payload = [
            'titulo' => '',
            'categoria' => 'geral',
            'conteudo' => '',
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $payload = [
                'titulo' => trim((string) ($_POST['titulo'] ?? '')),
                'categoria' => trim((string) ($_POST['categoria'] ?? 'geral')),
                'conteudo' => trim((string) ($_POST['conteudo'] ?? '')),
            ];

            try {
                $autorId = trim((string) ($_SESSION['usuario_id'] ?? ''));
                $id = (new Comunicado())->criar($payload, $autorId !== '' && $autorId !== '0' ? $autorId : null);
                if ($id) {
                    $_SESSION['mensagem_sucesso'] = 'Comunicado publicado com sucesso.';
                    header('Location: /pwa/comunicacao/ler?id=' . urlencode((string) $id));
                    exit;
                }

                $mensagemErro = 'Preencha título e conteúdo.';
            } catch (\Throwable $e) {
                $erroDb = 'Módulo ainda não inicializado no banco deste ambiente.';
                error_log('Falha ao criar comunicado: ' . $e->getMessage());
                $mensagemErro = 'Não foi possível publicar agora.';
            }
        }

        require __DIR__ . '/../Views/pwa/comunicacao_novo.php';
    }
}
