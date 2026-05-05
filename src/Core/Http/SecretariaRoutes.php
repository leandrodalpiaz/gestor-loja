<?php

namespace App\Core\Http;

use App\Controllers\SecretariaController;
use App\Core\Authorization\Authorizer;

class SecretariaRoutes
{
    public static function dispatch(
        string $requestUri,
        bool $openTestAccess,
        array $session,
        Authorizer $authorizer,
        callable $sessionHasRole
    ): bool {
        $controller = new SecretariaController();

        switch ($requestUri) {
            case '/secretaria':
                ModuleGuards::requireSecretariaAccess($openTestAccess, $session, $authorizer);
                $controller->index();
                return true;

            case '/secretaria/sessoes':
                ModuleGuards::requireSecretariaAccess($openTestAccess, $session, $authorizer);
                $controller->sessoes();
                return true;

            case '/secretaria/balaustres':
                ModuleGuards::requireSecretariaAccess($openTestAccess, $session, $authorizer);
                $controller->balaustres();
                return true;

            case '/secretaria/trabalhos-publicacoes':
                ModuleGuards::requireSecretariaAccess($openTestAccess, $session, $authorizer);
                $controller->trabalhosPublicacoes();
                return true;

            case '/secretaria/convites-externos':
                ModuleGuards::requireSecretariaAccess($openTestAccess, $session, $authorizer);
                $controller->convitesExternos();
                return true;

            case '/secretaria/convites-externos/salvar':
                ModuleGuards::requireSecretariaAccess($openTestAccess, $session, $authorizer);
                $controller->salvarConviteExterno();
                return true;

            case '/secretaria/convites-externos/remover-anexo':
                ModuleGuards::requireSecretariaAccess($openTestAccess, $session, $authorizer);
                $controller->removerAnexoConviteExterno();
                return true;

            case '/secretaria/convites-externos/presenca':
                ModuleGuards::requireSecretariaAccess($openTestAccess, $session, $authorizer);
                $controller->confirmarConviteExterno();
                return true;

            case '/secretaria/votacao':
                WebGuards::requireLogin($openTestAccess, $session);
                $controller->votacao();
                return true;

            case '/secretaria/nominata':
                ModuleGuards::requireAdminCargosView($openTestAccess, $session, $authorizer);
                $controller->nominata();
                return true;

            case '/secretaria/nominata/salvar':
                ModuleGuards::requireAdminCargosManage($openTestAccess, $session, $authorizer);
                $controller->salvarNominata();
                return true;

            case '/secretaria/acessos':
                ModuleGuards::requireAccessManage($openTestAccess, $session, $authorizer);
                $controller->acessos();
                return true;

            case '/secretaria/acessos/atualizar':
                ModuleGuards::requireAccessManage($openTestAccess, $session, $authorizer);
                $controller->atualizarAcesso();
                return true;

            case '/secretaria/convites':
                ModuleGuards::requireAccessManage($openTestAccess, $session, $authorizer);
                $controller->convites();
                return true;

            case '/secretaria/convites/gerar':
                ModuleGuards::requireAccessManage($openTestAccess, $session, $authorizer);
                $controller->gerarConvite();
                return true;

            case '/secretaria/conteudo-publico':
                ModuleGuards::requirePublicContentManage($openTestAccess, $session, $authorizer);
                $controller->conteudoPublico();
                return true;

            case '/secretaria/conteudo-publico/salvar':
                ModuleGuards::requirePublicContentManage($openTestAccess, $session, $authorizer);
                $controller->salvarConteudoPublico();
                return true;

            case '/secretaria/conteudo-publico/excluir':
                ModuleGuards::requirePublicContentManage($openTestAccess, $session, $authorizer);
                $controller->excluirConteudoPublico();
                return true;

            case '/secretaria/relatorio-anual':
                ModuleGuards::requireSecretariaAccess($openTestAccess, $session, $authorizer);
                $controller->relatorioAnual();
                return true;

            case '/secretaria/relatorio-gestao':
                ModuleGuards::requireSecretariaAccess($openTestAccess, $session, $authorizer);
                $controller->relatorioGestao();
                return true;

            case '/secretaria/sessoes/salvar':
                ModuleGuards::requireSecretariaAccess($openTestAccess, $session, $authorizer);
                $controller->salvarSessao();
                return true;

            case '/secretaria/sessoes/publicar-rascunho':
                ModuleGuards::requireSecretariaAccess($openTestAccess, $session, $authorizer);
                $controller->publicarSessaoRascunho();
                return true;

            case '/secretaria/sessoes/publicar':
                ModuleGuards::requireSecretariaAccess($openTestAccess, $session, $authorizer);
                $controller->publicarSessao();
                return true;

            case '/secretaria/sessoes/cancelar-rascunho':
                ModuleGuards::requireSecretariaAccess($openTestAccess, $session, $authorizer);
                $controller->cancelarRascunhoSessao();
                return true;

            case '/secretaria/sessoes/cancelar':
                ModuleGuards::requireSecretariaAccess($openTestAccess, $session, $authorizer);
                $controller->cancelarSessao();
                return true;

            case '/secretaria/sessoes/reabrir':
                ModuleGuards::requireSecretariaAccess($openTestAccess, $session, $authorizer);
                $controller->reabrirSessao();
                return true;

            case '/secretaria/trabalhos/salvar':
                ModuleGuards::requireSecretariaAccess($openTestAccess, $session, $authorizer);
                $controller->salvarTrabalho();
                return true;

            case '/secretaria/publicacoes/salvar':
                ModuleGuards::requireSecretariaAccess($openTestAccess, $session, $authorizer);
                $controller->salvarPublicacao();
                return true;

            case '/secretaria/balaustres/salvar':
                ModuleGuards::requireSecretariaAccess($openTestAccess, $session, $authorizer);
                $controller->salvarBalaustre();
                return true;

            case '/secretaria/balaustres/apto':
                ModuleGuards::requireSecretariaAccess($openTestAccess, $session, $authorizer);
                $controller->marcarBalaustreApto();
                return true;

            case '/secretaria/balaustres/abrir-votacao':
                WebGuards::requireLogin($openTestAccess, $session);
                WebGuards::requirePermission(
                    $authorizer->hasPermission('veneravel.manage'),
                    'Acesso restrito ao Veneravel Mestre.'
                );
                $controller->abrirVotacaoBalaustre();
                return true;

            case '/secretaria/balaustres/votar':
                WebGuards::requireLogin($openTestAccess, $session);
                $controller->votarBalaustre();
                return true;

            case '/secretaria/balaustres/encerrar-votacao':
                WebGuards::requireLogin($openTestAccess, $session);
                WebGuards::requirePermission(
                    $authorizer->hasPermission('veneravel.manage'),
                    'Acesso restrito ao Veneravel Mestre.'
                );
                $controller->encerrarVotacaoBalaustre();
                return true;

            default:
                return false;
        }
    }
}
