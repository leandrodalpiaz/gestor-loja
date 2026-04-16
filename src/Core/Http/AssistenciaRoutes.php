<?php

namespace App\Core\Http;

use App\Controllers\HospitaleiroController;
use App\Core\Authorization\Authorizer;

class AssistenciaRoutes
{
    public static function dispatch(string $requestUri, bool $openTestAccess, array $session, Authorizer $authorizer): bool
    {
        $controller = new HospitaleiroController();

        switch ($requestUri) {
            case '/assistencia':
                ModuleGuards::requireAssistenciaAccess($openTestAccess, $session, $authorizer);
                $controller->index();
                return true;

            case '/assistencia/ocorrencias/salvar':
                ModuleGuards::requireAssistenciaAccess($openTestAccess, $session, $authorizer);
                $controller->salvarOcorrencia();
                return true;

            case '/assistencia/ocorrencias/status':
                ModuleGuards::requireAssistenciaAccess($openTestAccess, $session, $authorizer);
                $controller->atualizarStatusOcorrencia();
                return true;

            case '/assistencia/ocorrencias/visita':
                ModuleGuards::requireAssistenciaAccess($openTestAccess, $session, $authorizer);
                $controller->registrarVisita();
                return true;

            default:
                return false;
        }
    }
}
