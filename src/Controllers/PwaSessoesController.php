<?php

namespace App\Controllers;

use App\Config\FeatureFlags;
use App\Models\Presenca;
use App\Models\Sessao;

class PwaSessoesController
{
    public function index(): void
    {
        if (!FeatureFlags::pwaSessoes()) {
            http_response_code(404);
            echo 'Recurso indisponível.';
            return;
        }

        $sessaoModel = new Sessao();
        $presencaModel = new Presenca();

        $usuarioId = trim((string) ($_SESSION['usuario_id'] ?? ''));
        $sessoesList = $sessaoModel->listarFuturas(); // Traz até 50 futuras por padrão
        
        $sessoesFuturas = [];
        $respostas = [];

        foreach ($sessoesList as $s) {
            $sessaoDetalhada = $sessaoModel->findById((int) $s['id']);
            if ($sessaoDetalhada) {
                $sessoesFuturas[] = $sessaoDetalhada;
                if ($usuarioId !== '' && $usuarioId !== '0') {
                    $respostas[$s['id']] = $presencaModel->obterResposta((int) $s['id'], $usuarioId);
                } else {
                    $respostas[$s['id']] = null;
                }
            }
        }

        require __DIR__ . '/../Views/pwa/sessoes.php';
    }

    public function atualizar(): void
    {
        if (!FeatureFlags::pwaSessoes()) {
            http_response_code(404);
            echo 'Recurso indisponível.';
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /pwa/sessoes');
            exit;
        }

        $usuarioId = trim((string) ($_SESSION['usuario_id'] ?? ''));
        if ($usuarioId === '' || $usuarioId === '0') {
            $_SESSION['mensagem_erro'] = 'Ação indisponível no modo teste. Faça login como obreiro real.';
            header('Location: /pwa/sessoes');
            exit;
        }

        $sessaoId = (int) ($_POST['sessao_id'] ?? 0);
        $acao = trim((string) ($_POST['acao'] ?? ''));
        $observacao = trim((string) ($_POST['observacao'] ?? ''));

        if ($sessaoId <= 0 || $acao === '') {
            $_SESSION['mensagem_erro'] = 'Dados insuficientes para atualizar a confirmação.';
            header('Location: /pwa/sessoes');
            exit;
        }

        $presencaModel = new Presenca();
        $ok = false;

        try {
            $ok = match ($acao) {
                'confirmar' => $presencaModel->registrar($sessaoId, $usuarioId, 'confirmado', false),
                'confirmar_agape' => $presencaModel->registrar($sessaoId, $usuarioId, 'confirmado', true),
                'confirmar_sem_agape' => $presencaModel->registrar($sessaoId, $usuarioId, 'confirmado', false),
                'ausencia' => $presencaModel->registrar($sessaoId, $usuarioId, 'ausente', false, $observacao !== '' ? $observacao : null),
                'cancelar' => $presencaModel->cancelar($sessaoId, $usuarioId),
                default => false,
            };
        } catch (\Throwable $e) {
            error_log('Falha ao atualizar confirmacao PWA: ' . $e->getMessage());
            $ok = false;
        }

        $_SESSION[$ok ? 'mensagem_sucesso' : 'mensagem_erro'] = $ok
            ? 'Resposta registrada com sucesso.'
            : 'Não foi possível registrar sua resposta.';

        header('Location: /pwa/sessoes');
        exit;
    }
}

