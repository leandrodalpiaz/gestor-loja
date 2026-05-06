<?php

namespace App\Controllers;

use App\Config\FeatureFlags;
use App\Models\Comunicado;
use App\Models\Presenca;
use App\Models\Sessao;

class PwaHomeController
{
    public function index(): void
    {
        $links = [
            'sessoes' => FeatureFlags::pwaSessoes(),
            'biblioteca' => FeatureFlags::pwaBiblioteca(),
            'comunicacao' => FeatureFlags::pwaComunicacao(),
        ];

        // ─── Próxima Sessão (Hero Card) ─────────────────────────────────
        $proximaSessao = null;
        $proximaSessaoResposta = null;
        if (FeatureFlags::pwaSessoes()) {
            try {
                $sessaoModel = new Sessao();
                $proximaSessao = $sessaoModel->obterProximaSessao();
                if ($proximaSessao) {
                    $usuarioId = trim((string) ($_SESSION['usuario_id'] ?? ''));
                    if ($usuarioId !== '' && $usuarioId !== '0') {
                        $proximaSessaoResposta = (new Presenca())->obterResposta(
                            (int) $proximaSessao['id'],
                            $usuarioId
                        );
                    }
                }
            } catch (\Throwable $e) {
                error_log('PWA Home – falha ao obter próxima sessão: ' . $e->getMessage());
            }
        }

        // ─── Mural de Avisos (últimos comunicados) ──────────────────────
        $ultimosComunicados = [];
        if (FeatureFlags::pwaComunicacao()) {
            try {
                $comunicadoModel = new Comunicado();
                $ultimosComunicados = $comunicadoModel->listarRecentes(5);

                // Marca se o usuário já leu cada comunicado
                $usuarioId = trim((string) ($_SESSION['usuario_id'] ?? ''));
                if ($usuarioId !== '' && $usuarioId !== '0') {
                    foreach ($ultimosComunicados as &$c) {
                        $c['lido_pelo_usuario'] = $comunicadoModel->usuarioLeu((int) $c['id'], $usuarioId);
                    }
                    unset($c);
                }
            } catch (\Throwable $e) {
                error_log('PWA Home – falha ao obter comunicados: ' . $e->getMessage());
            }
        }

        require __DIR__ . '/../Views/pwa/home.php';
    }
}
