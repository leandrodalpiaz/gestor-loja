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
                ModuleGuards::requireBibliotecaAccess($openTestAccess, $session, $authorizer); // Apenas login
                $controller->index();
                return true;

            case '/assistencia/ocorrencias/salvar':
                ModuleGuards::requireAssistenciaAccess($openTestAccess, $session, $authorizer); // Exige hospitaleiro.manage
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

            case '/assistencia/tronco/registrar':
                ModuleGuards::requireAssistenciaAccess($openTestAccess, $session, $authorizer);
                $controller->registrarMovimentoTronco();
                return true;

            case '/assistencia/ocorrencias/repasse':
                ModuleGuards::requireAssistenciaAccess($openTestAccess, $session, $authorizer);
                $controller->registrarRepasseApoio();
                return true;

            default:
                return false;
        }
    }
}
