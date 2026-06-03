<?php

namespace App\Core\Http;

use App\Controllers\VidaLojaController;
use App\Core\Authorization\Authorizer;

class VidaLojaRoutes
{
    public static function dispatch(string $requestUri, bool $openTestAccess, array $session, Authorizer $authorizer): bool
    {
        $controller = new VidaLojaController();

        switch ($requestUri) {
            case '/vida-loja':
                ModuleGuards::requireVidaLojaView($openTestAccess, $session, $authorizer);
                $controller->dashboard();
                return true;

            case '/vida-loja/sinais':
                ModuleGuards::requireVidaLojaView($openTestAccess, $session, $authorizer);
                $controller->sinais();
                return true;

            case '/vida-loja/sinais/acao':
                ModuleGuards::requireVidaLojaManage($openTestAccess, $session, $authorizer);
                $controller->atualizarSinalStatus();
                return true;

            case '/vida-loja/contato/salvar':
                ModuleGuards::requireVidaLojaAcompanhamentoCreate($openTestAccess, $session, $authorizer);
                $controller->salvarContato();
                return true;

            case '/vida-loja/contato/excluir':
                ModuleGuards::requireVidaLojaManage($openTestAccess, $session, $authorizer);
                $controller->excluirContato();
                return true;

            default:
                return false;
        }
    }
}
