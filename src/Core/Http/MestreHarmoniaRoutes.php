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
            case '/mestre-harmonia':
                WebGuards::requireLogin($openTestAccess, $session);
                WebGuards::requirePermission(
                    $sessionHasPermission('mestre_harmonia.manage'),
                    'Acesso restrito ao Mestre de Harmonia, Veneravel Mestre ou Administrador.'
                );
                (new MestreHarmoniaController())->index();
                return true;

            case '/miniapp/mestre-harmonia':
                requireMiniappAuth(['mestre_harmonia', 'veneravel', 'admin'], 'mestre_harmonia.manage');
                require __DIR__ . '/../../Views/miniapp/mestre_harmonia.php';
                return true;

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
