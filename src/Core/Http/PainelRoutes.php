<?php

namespace App\Core\Http;

use App\Controllers\ChancelerSessaoController;
use App\Controllers\MestreBanquetesController;
use App\Controllers\OradorController;
use App\Controllers\PwaBibliotecaController;
use App\Controllers\PwaComunicacaoController;
use App\Controllers\PwaAdminController;
use App\Controllers\PwaHomeController;
use App\Controllers\PwaSessoesController;
use App\Controllers\VeneravelController;
use App\Core\Authorization\Authorizer;
use App\Core\Tenant\TenantAssetResolver;
use App\Config\FeatureFlags;
use App\Models\ConfiguracaoLoja;
use App\Models\Obreiro;
use App\Models\Presenca;
use App\Models\PublicacaoSecretaria;
use App\Models\Sessao;

class PainelRoutes
{
    public static function dispatch(
        string $requestUri,
        string $method,
        bool $openTestAccess,
        array $session,
        Authorizer $authorizer,
        callable $sessionHasRole,
        callable $sessionHasPermission,
        callable $buildEfemeridesPreview,
        callable $canManageContentCategory
    ): bool {
        switch ($requestUri) {
            case '/':
            case '/index.php':
            case '/dashboard':
                self::dashboard($method, $openTestAccess, $session, $authorizer, $sessionHasRole, $sessionHasPermission, $buildEfemeridesPreview);
                return true;

            case '/veneravel':
            case '/veneravel/dashboard':
                self::requirePermissionPanel($openTestAccess, $session, $sessionHasPermission, 'veneravel.manage', 'Acesso restrito ao Veneravel Mestre ou Administrador.');
                (new VeneravelController())->index();
                return true;

            case '/orador':
            case '/orador/dashboard':
                self::requirePermissionPanel($openTestAccess, $session, $sessionHasPermission, 'orador.view', 'Acesso restrito ao Orador, Veneravel Mestre ou Administrador.');
                (new OradorController())->index();
                return true;

            case '/miniapp/orador':
                requireMiniappAuth(['orador', 'veneravel'], 'orador.view');
                require __DIR__ . '/../../Views/miniapp/orador.php';
                return true;

            case '/api/miniapp/orador/dashboard':
                $miniappUser = requireMiniappAuth(['orador', 'veneravel'], 'orador.view');
                $controller = new OradorController();
                $sessaoId = isset($_GET['sessao_id']) ? (int) $_GET['sessao_id'] : null;
                JsonResponse::send([
                    'ok' => true,
                    'dados' => $controller->montarPayloadMiniapp($sessaoId),
                    'usuario' => [
                        'id' => $miniappUser['id'] ?? null,
                        'nome' => $miniappUser['nome_completo'] ?? null,
                    ],
                ]);

            case '/mestre-banquetes':
            case '/mestre-banquetes/dashboard':
                self::requirePermissionPanel($openTestAccess, $session, $sessionHasPermission, 'mestre_banquetes.manage', 'Acesso restrito ao Mestre de Banquetes, Veneravel Mestre ou Administrador.');
                (new MestreBanquetesController())->index();
                return true;

            case '/mestre-banquetes/operacao/salvar':
                self::requirePermissionPanel($openTestAccess, $session, $sessionHasPermission, 'mestre_banquetes.manage', 'Acesso restrito ao Mestre de Banquetes, Veneravel Mestre ou Administrador.');
                (new MestreBanquetesController())->salvarOperacao();
                return true;

            case '/chanceler/sessao':
            case '/chanceler/sessao/dashboard':
                WebGuards::requireLogin($openTestAccess, $session);
                if (
                    !$sessionHasPermission('chancelaria.manage')
                    && !$canManageContentCategory('efemerides')
                    && !$canManageContentCategory('historia')
                    && !$canManageContentCategory('palavra_dia')
                ) {
                    WebGuards::forbidHtml('Acesso restrito aos responsaveis pelos conteudos da Chancelaria.');
                }
                (new ChancelerSessaoController())->index();
                return true;

            case '/chanceler/sessao/presenca':
                self::requirePermissionPanel($openTestAccess, $session, $sessionHasPermission, 'chancelaria.manage', 'Acesso restrito ao Chanceler, Veneravel Mestre ou Administrador.');
                (new ChancelerSessaoController())->registrarPresenca();
                return true;

            case '/chanceler/sessao/visitante':
                self::requirePermissionPanel($openTestAccess, $session, $sessionHasPermission, 'chancelaria.manage', 'Acesso restrito ao Chanceler, Veneravel Mestre ou Administrador.');
                (new ChancelerSessaoController())->registrarVisitante();
                return true;

            case '/pwa/sessoes':
                WebGuards::requireLogin($openTestAccess, $session);
                if (!FeatureFlags::pwaSessoes()) {
                    WebGuards::forbidHtml('Recurso indisponível.');
                }
                (new PwaSessoesController())->index();
                return true;

            case '/pwa/sessoes/atualizar':
                WebGuards::requireLogin($openTestAccess, $session);
                if (!FeatureFlags::pwaSessoes()) {
                    WebGuards::forbidHtml('Recurso indisponível.');
                }
                (new PwaSessoesController())->atualizar();
                return true;

            case '/pwa/biblioteca':
                WebGuards::requireLogin($openTestAccess, $session);
                WebGuards::requirePermission(
                    $authorizer->hasPermission('biblioteca.self') || $authorizer->hasPermission('biblioteca.manage'),
                    'Acesso restrito ao módulo Biblioteca.'
                );
                if (!FeatureFlags::pwaBiblioteca()) {
                    WebGuards::forbidHtml('Recurso indisponível.');
                }
                (new PwaBibliotecaController())->index();
                return true;

            case '/pwa/biblioteca/meus-emprestimos':
                WebGuards::requireLogin($openTestAccess, $session);
                WebGuards::requirePermission(
                    $authorizer->hasPermission('biblioteca.self') || $authorizer->hasPermission('biblioteca.manage'),
                    'Acesso restrito ao módulo Biblioteca.'
                );
                if (!FeatureFlags::pwaBiblioteca()) {
                    WebGuards::forbidHtml('Recurso indisponível.');
                }
                (new PwaBibliotecaController())->meusEmprestimos();
                return true;

            case '/pwa/biblioteca/detalhes':
                WebGuards::requireLogin($openTestAccess, $session);
                WebGuards::requirePermission(
                    $authorizer->hasPermission('biblioteca.self') || $authorizer->hasPermission('biblioteca.manage'),
                    'Acesso restrito ao módulo Biblioteca.'
                );
                if (!FeatureFlags::pwaBiblioteca()) {
                    WebGuards::forbidHtml('Recurso indisponível.');
                }
                $id = (int) ($_GET['id'] ?? 0);
                if ($id <= 0) {
                    header('Location: /pwa/biblioteca');
                    exit;
                }
                (new PwaBibliotecaController())->detalhes($id);
                return true;

            case '/pwa/biblioteca/adicionar':
                WebGuards::requireLogin($openTestAccess, $session);
                WebGuards::requirePermission(
                    $authorizer->hasPermission('biblioteca.manage'),
                    'Acesso restrito ao Bibliotecário e Administradores.'
                );
                if (!FeatureFlags::pwaBiblioteca()) {
                    WebGuards::forbidHtml('Recurso indisponível.');
                }
                (new PwaBibliotecaController())->adicionar();
                return true;

            case '/pwa/biblioteca/classificar':
                WebGuards::requireLogin($openTestAccess, $session);
                WebGuards::requirePermission(
                    $authorizer->hasPermission('biblioteca.classificar'),
                    'Acesso restrito.'
                );
                if (!FeatureFlags::pwaBiblioteca()) {
                    WebGuards::forbidHtml('Recurso indisponível.');
                }
                (new PwaBibliotecaController())->classificar();
                return true;

            case '/pwa/comunicacao':
                WebGuards::requireLogin($openTestAccess, $session);
                WebGuards::requirePermission($authorizer->hasPermission('dashboard.view'), 'Acesso restrito ao painel.');
                if (!FeatureFlags::pwaComunicacao()) {
                    WebGuards::forbidHtml('Recurso indisponível.');
                }
                (new PwaComunicacaoController())->index();
                return true;

            case '/pwa/comunicacao/ler':
                WebGuards::requireLogin($openTestAccess, $session);
                WebGuards::requirePermission($authorizer->hasPermission('dashboard.view'), 'Acesso restrito ao painel.');
                if (!FeatureFlags::pwaComunicacao()) {
                    WebGuards::forbidHtml('Recurso indisponível.');
                }
                (new PwaComunicacaoController())->ler();
                return true;

            case '/pwa/comunicacao/novo':
                WebGuards::requireLogin($openTestAccess, $session);
                WebGuards::requirePermission(
                    $authorizer->hasPermission('secretaria.manage') || $authorizer->hasPermission('admin.cargos.view'),
                    'Acesso restrito à publicação de comunicados.'
                );
                if (!FeatureFlags::pwaComunicacao()) {
                    WebGuards::forbidHtml('Recurso indisponível.');
                }
                (new PwaComunicacaoController())->novo();
                return true;

            case '/pwa':
                WebGuards::requireLogin($openTestAccess, $session);
                WebGuards::requirePermission($authorizer->hasPermission('dashboard.view'), 'Acesso restrito ao painel.');
                (new PwaHomeController())->index();
                return true;

            case '/pwa/admin':
                WebGuards::requireLogin($openTestAccess, $session);
                WebGuards::requirePermission($authorizer->hasPermission('dashboard.view'), 'Acesso restrito ao painel.');
                if (!FeatureFlags::pwaAdminCrud()) {
                    WebGuards::forbidHtml('Recurso indisponível.');
                }
                (new PwaAdminController())->index();
                return true;

            case '/veneravel/sessoes/publicar':
                self::requirePermissionPanel($openTestAccess, $session, $sessionHasPermission, 'veneravel.manage', 'Acesso restrito ao Veneravel Mestre ou Administrador.');
                (new VeneravelController())->publicarSessao();
                return true;

            case '/veneravel/sessoes/cancelar':
                self::requirePermissionPanel($openTestAccess, $session, $sessionHasPermission, 'veneravel.manage', 'Acesso restrito ao Veneravel Mestre ou Administrador.');
                (new VeneravelController())->cancelarSessao();
                return true;

            case '/veneravel/sessoes/reabrir':
                self::requirePermissionPanel($openTestAccess, $session, $sessionHasPermission, 'veneravel.manage', 'Acesso restrito ao Veneravel Mestre ou Administrador.');
                (new VeneravelController())->reabrirSessao();
                return true;

            case '/veneravel/sessoes/realizar':
                self::requirePermissionPanel($openTestAccess, $session, $sessionHasPermission, 'veneravel.manage', 'Acesso restrito ao Veneravel Mestre ou Administrador.');
                (new VeneravelController())->realizarSessao();
                return true;

            case '/veneravel/balaustres/abrir-votacao':
                self::requirePermissionPanel($openTestAccess, $session, $sessionHasPermission, 'veneravel.manage', 'Acesso restrito ao Veneravel Mestre ou Administrador.');
                (new VeneravelController())->abrirVotacaoBalaustre();
                return true;

            case '/veneravel/balaustres/encerrar-votacao':
                self::requirePermissionPanel($openTestAccess, $session, $sessionHasPermission, 'veneravel.manage', 'Acesso restrito ao Veneravel Mestre ou Administrador.');
                (new VeneravelController())->encerrarVotacaoBalaustre();
                return true;

            default:
                return false;
        }
    }

    private static function requirePermissionPanel(
        bool $openTestAccess,
        array $session,
        callable $sessionHasPermission,
        string $permission,
        string $message,
    ): void {
        WebGuards::requireLogin($openTestAccess, $session);
        if (!$sessionHasPermission($permission)) {
            WebGuards::forbidHtml($message);
        }
    }

    private static function dashboard(
        string $method,
        bool $openTestAccess,
        array $session,
        Authorizer $authorizer,
        callable $sessionHasRole,
        callable $sessionHasPermission,
        callable $buildEfemeridesPreview
    ): void {
        WebGuards::requireLogin($openTestAccess, $session);
        WebGuards::requirePermission($authorizer->hasPermission('dashboard.view'), 'Acesso restrito ao painel principal.');

        $dashboardMensagemSucesso = $_SESSION['mensagem_sucesso'] ?? null;
        $dashboardMensagemErro = $_SESSION['mensagem_erro'] ?? null;
        unset($_SESSION['mensagem_sucesso'], $_SESSION['mensagem_erro']);

        $dashboardPermissions = [
            'dashboard.view' => $sessionHasPermission('dashboard.view'),
            'admin.cargos.view' => $sessionHasPermission('admin.cargos.view'),
            'admin.loja.view' => $sessionHasPermission('admin.loja.view'),
            'admin.loja.manage' => $sessionHasPermission('admin.loja.manage'),
            'secretaria.manage' => $sessionHasPermission('secretaria.manage'),
            'tesouraria.manage' => $sessionHasPermission('tesouraria.manage'),
            'financeiro.self' => $sessionHasPermission('financeiro.self'),
            'biblioteca.manage' => $sessionHasPermission('biblioteca.manage'),
            'biblioteca.self' => $sessionHasPermission('biblioteca.self'),
            'biblioteca.classificar' => $sessionHasPermission('biblioteca.classificar'),
            'obreiros.manage' => $sessionHasPermission('obreiros.manage'),
            'obreiros.view' => $sessionHasPermission('obreiros.view'),
            'chancelaria.manage' => $sessionHasPermission('chancelaria.manage'),
            'veneravel.manage' => $sessionHasPermission('veneravel.manage'),
            'orador.view' => $sessionHasPermission('orador.view'),
            'mestre_banquetes.manage' => $sessionHasPermission('mestre_banquetes.manage'),
            'mestre_harmonia.manage' => $sessionHasPermission('mestre_harmonia.manage'),
            'vigilancia.primeiro.manage' => $sessionHasPermission('vigilancia.primeiro.manage'),
            'vigilancia.segundo.manage' => $sessionHasPermission('vigilancia.segundo.manage'),
            'hospitaleiro.manage' => $sessionHasPermission('hospitaleiro.manage'),
        ];

        $dashboardConfiguracaoLoja = (new ConfiguracaoLoja())->obter();
        $dashboardTenantSlug = trim((string) ($session['tenant_slug'] ?? ''));
        $dashboardLogoUrl = TenantAssetResolver::resolveLogo($dashboardTenantSlug);

        $dashboardUsuarioId = trim((string) ($session['usuario_id'] ?? ''));
        $dashboardObreiro = null;
        if ($dashboardUsuarioId !== '' && $dashboardUsuarioId !== '0') {
            try {
                $dashboardObreiro = (new Obreiro())->findById($dashboardUsuarioId);
            } catch (\Throwable $e) {
                error_log('Falha ao localizar obreiro do dashboard: ' . $e->getMessage());
            }
        }

        if ($method === 'POST' && ($_POST['dashboard_action'] ?? '') === 'sessao_confirmacao') {
            $sessaoId = (int) ($_POST['sessao_id'] ?? 0);
            $acao = trim((string) ($_POST['acao'] ?? ''));

            if ($sessaoId <= 0) {
                $_SESSION['mensagem_erro'] = 'Sessão inválida para atualizar a confirmação.';
            } elseif (!$dashboardObreiro || $dashboardUsuarioId === '' || $dashboardUsuarioId === '0') {
                $_SESSION['mensagem_erro'] = 'A confirmacao direta no dashboard requer um obreiro real autenticado.';
            } else {
                try {
                    $presencaModel = new Presenca();
                    $ok = $acao === 'cancelar'
                        ? $presencaModel->cancelar($sessaoId, $dashboardUsuarioId)
                        : $presencaModel->registrar($sessaoId, $dashboardUsuarioId, 'confirmado', false);

                    if ($ok) {
                        $_SESSION['mensagem_sucesso'] = $acao === 'cancelar'
                            ? 'Confirmacao cancelada com sucesso.'
                            : 'Presenca confirmada com sucesso.';
                    } else {
                        $_SESSION['mensagem_erro'] = 'Não foi possível atualizar a confirmação desta sessão.';
                    }
                } catch (\Throwable $e) {
                    $_SESSION['mensagem_erro'] = 'Falha ao atualizar a confirmação da sessão.';
                    error_log('Falha no POST do dashboard: ' . $e->getMessage());
                }
            }

            header('Location: /dashboard#sessoes-loja');
            exit;
        }

        $dashboardSessoes = [];
        $dashboardOutrasLojas = [];
        try {
            $sessaoModel = new Sessao();
            $presencaModel = new Presenca();
            $sessoesFuturas = $sessaoModel->listarFuturas(4);

            foreach ($sessoesFuturas as $sessao) {
                $sessaoId = (int) ($sessao['id'] ?? 0);
                if ($sessaoId <= 0) {
                    continue;
                }

                $respostaUsuario = $dashboardUsuarioId !== '' && $dashboardUsuarioId !== '0'
                    ? $presencaModel->obterResposta($sessaoId, $dashboardUsuarioId)
                    : null;

                $rotaDetalheSessao = '/dashboard#sessoes-loja';
                if ($sessionHasRole('chanceler', 'veneravel', 'admin')) {
                    $rotaDetalheSessao = '/chanceler/sessao?sessao_id=' . urlencode((string) $sessaoId);
                } elseif ($sessionHasRole('secretario')) {
                    $rotaDetalheSessao = '/secretaria?sessao_resumo=' . urlencode((string) $sessaoId);
                } elseif ($sessionHasRole('tesoureiro')) {
                    $rotaDetalheSessao = '/tesouraria/sessoes';
                } elseif ($sessionHasRole('mestre_banquetes')) {
                    $rotaDetalheSessao = '/mestre-banquetes';
                }

                $dashboardSessoes[] = [
                    'id' => $sessaoId,
                    'titulo' => trim((string) ($sessao['titulo'] ?? '')) !== ''
                        ? (string) $sessao['titulo']
                        : trim((string) (($sessao['tipo_sessao'] ?? 'Sessao') . ' - ' . ($sessao['grau_sessao'] ?? ''))),
                    'data_hora_inicio' => (string) ($sessao['data_hora_inicio'] ?? ''),
                    'status' => trim((string) ($sessao['status'] ?? 'programada')) ?: 'programada',
                    'tipo_sessao' => (string) ($sessao['tipo_sessao'] ?? ''),
                    'grau_sessao' => (string) ($sessao['grau_sessao'] ?? ''),
                    'descricao_agape' => $sessaoModel->obterDescricaoAgape($sessao),
                    'total_confirmados' => $presencaModel->contarConfirmadosPorSessao($sessaoId),
                    'total_agape' => $presencaModel->contarParticipantesAgapePorSessao($sessaoId),
                    'resposta_usuario' => is_array($respostaUsuario) ? (string) ($respostaUsuario['status_confirmacao'] ?? '') : '',
                    'confirmado' => is_array($respostaUsuario) && (string) ($respostaUsuario['status_confirmacao'] ?? '') === 'confirmado',
                    'detalhe_href' => $rotaDetalheSessao,
                ];
            }
        } catch (\Throwable $e) {
            error_log('Falha ao montar sessoes do dashboard: ' . $e->getMessage());
            $dashboardSessoes = [];
        }

        $dashboardRecados = [];
        try {
            $dashboardRecados = (new PublicacaoSecretaria())->listarRecentes(3);
        } catch (\Throwable $e) {
            error_log('Falha ao carregar recados do dashboard: ' . $e->getMessage());
        }

        $dashboardPalavraIrmao = '';
        try {
            $dashboardEfemerides = $buildEfemeridesPreview();
            $dashboardPalavraIrmao = trim((string) ($dashboardEfemerides['mensagemPreview'] ?? ''));
        } catch (\Throwable $e) {
            error_log('Falha ao carregar palavra do irmao no dashboard: ' . $e->getMessage());
        }

        require __DIR__ . '/../../Views/dashboard.php';
        exit;
    }
}
