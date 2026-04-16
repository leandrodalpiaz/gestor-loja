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

            case '/secretaria/votacao':
                WebGuards::requireLogin($openTestAccess, $session);
                $controller->votacao();
                return true;

            case '/secretaria/relatorio-anual':
                ModuleGuards::requireSecretariaAccess($openTestAccess, $session, $authorizer);
                $controller->relatorioAnual();
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
                    (bool) $sessionHasRole('veneravel', 'admin'),
                    'Acesso restrito ao Veneravel Mestre ou Administrador.'
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
                    (bool) $sessionHasRole('veneravel', 'admin'),
                    'Acesso restrito ao Veneravel Mestre ou Administrador.'
                );
                $controller->encerrarVotacaoBalaustre();
                return true;

            default:
                return false;
        }
    }
}
