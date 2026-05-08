<?php

namespace App\Controllers;

use App\Config\FeatureFlags;
use App\Core\Auth\CurrentUser;
use App\Core\Authorization\Authorizer;
use App\Core\Authorization\PermissionMap;
use App\Models\Comunicado;
use App\Models\ObrigacaoFinanceira;
use App\Models\Obreiro;
use App\Models\Presenca;
use App\Models\Sessao;

class PwaHomeController
{
    public function index(): void
    {
        $authorizer = new Authorizer(new CurrentUser($_SESSION), new PermissionMap());
        $links = [
            'sessoes' => FeatureFlags::pwaSessoes(),
            'biblioteca' => FeatureFlags::pwaBiblioteca(),
            'comunicacao' => FeatureFlags::pwaComunicacao(),
            'perfil' => true,
            'irmaos' => true,
            'hospitalaria' => $authorizer->hasPermission('hospitaleiro.manage'),
            'harmonia' => $authorizer->hasPermission('mestre_harmonia.manage'),
            'tesouraria' => $authorizer->hasPermission('tesouraria.manage'),
            'chancelaria' => $authorizer->hasPermission('chancelaria.manage'),
            'cargo_modules' => $this->cargoModules($authorizer),
        ];
        $usuarioId = trim((string) ($_SESSION['usuario_id'] ?? ''));

        // ─── Próxima Sessão (Hero Card) ─────────────────────────────────
        $proximaSessao = null;
        $proximaSessaoResposta = null;
        if (FeatureFlags::pwaSessoes()) {
            try {
                $sessaoModel = new Sessao();
                $proximaSessao = $sessaoModel->obterProximaSessao();
                if ($proximaSessao) {
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

        $resumoFinanceiro = [
            'parcelas_em_aberto' => 0,
            'parcelas_atrasadas' => 0,
            'saldo_em_aberto' => 0.0,
            'total_pago' => 0.0,
            'proximo_vencimento' => null,
        ];
        if ($usuarioId !== '' && $usuarioId !== '0') {
            try {
                $resumoFinanceiro = (new ObrigacaoFinanceira())->obterResumoObreiro($usuarioId);
            } catch (\Throwable $e) {
                error_log('PWA Home - falha ao obter resumo financeiro: ' . $e->getMessage());
            }
        }

        $paraSuaAtencao = [];
        if ($usuarioId !== '' && $usuarioId !== '0') {
            try {
                $obreiro = (new Obreiro())->findById($usuarioId);
                if (is_array($obreiro)) {
                    if (empty($obreiro['telefone'])) {
                        $paraSuaAtencao[] = 'Completar cadastro: telefone.';
                    }
                    if (empty($obreiro['data_nascimento_civil'])) {
                        $paraSuaAtencao[] = 'Completar cadastro: data de nascimento.';
                    }
                    if (empty($obreiro['email'])) {
                        $paraSuaAtencao[] = 'Completar cadastro: e-mail.';
                    }
                }
            } catch (\Throwable $e) {
                error_log('PWA Home - falha ao obter cadastro do obreiro: ' . $e->getMessage());
            }
        }
        if ($proximaSessao && (($proximaSessaoResposta['status_confirmacao'] ?? '') === '')) {
            $paraSuaAtencao[] = 'Confirmar presença na próxima sessão.';
        }
        if (FeatureFlags::pwaComunicacao()) {
            $naoLidos = 0;
            foreach ($ultimosComunicados as $comunicado) {
                if (empty($comunicado['lido_pelo_usuario'])) {
                    $naoLidos++;
                }
            }
            if ($naoLidos > 0) {
                $paraSuaAtencao[] = sprintf('Ler %d comunicado(s) recente(s).', $naoLidos);
            }
        }

        require __DIR__ . '/../Views/pwa/home.php';
    }

    private function cargoModules(Authorizer $authorizer): array
    {
        $items = [];

        foreach (PwaCargosController::modules() as $slug => $module) {
            if ($authorizer->hasPermission((string) $module['permission'])) {
                $items[] = [
                    'href' => '/pwa/' . $slug,
                    'label' => (string) $module['title'],
                    'description' => (string) $module['summary'],
                ];
            }
        }

        return $items;
    }
}
