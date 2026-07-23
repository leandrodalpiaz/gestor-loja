<?php

namespace App\Core\Http;

use App\Controllers\MestreHarmoniaController;

class MestreHarmoniaRoutes
{
    public static function dispatch(
        string $requestUri,
        bool $openTestAccess,
        array $session,
        callable $sessionHasPermission,
        callable $requireJsonLogin
    ): bool {
        switch ($requestUri) {
            case '/api/mestre-harmonia/scan':
                $requireJsonLogin();
                if (!$sessionHasPermission('mestre_harmonia.manage')) {
                    JsonResponse::error('Acesso restrito ao Mestre de Harmonia, Veneravel Mestre ou Administrador.', 403);
                }
                (new MestreHarmoniaController())->scan();
                return true;

            case '/api/mestre-harmonia/audio':
                $requireJsonLogin();
                if (!$sessionHasPermission('mestre_harmonia.manage')) {
                    JsonResponse::error('Acesso restrito ao Mestre de Harmonia, Veneravel Mestre ou Administrador.', 403);
                }
                (new MestreHarmoniaController())->audio();
                return true;

            case '/api/mestre-harmonia/operador':
                $requireJsonLogin();
                if (!$sessionHasPermission('mestre_harmonia.manage')) {
                    JsonResponse::error('Acesso restrito ao Mestre de Harmonia, Veneravel Mestre ou Administrador.', 403);
                }
                (new MestreHarmoniaController())->salvarOperador();
                return true;

            default:
                return false;
        }
    }
}
