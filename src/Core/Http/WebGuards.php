<?php

namespace App\Core\Http;

class WebGuards
{
    public static function requireLogin(bool $openTestAccess, array $session): void
    {
        if (!$openTestAccess && !isset($session['usuario_logado'])) {
            header('Location: /login');
            exit;
        }
    }

    public static function requirePermission(bool $allowed, string $message = 'Acesso restrito.'): void
    {
        if (!$allowed) {
            http_response_code(403);
            echo $message;
            exit;
        }
    }

    public static function requireAuthenticatedPermission(
        bool $openTestAccess,
        array $session,
        bool $allowed,
        string $message = 'Acesso restrito.'
    ): void {
        self::requireLogin($openTestAccess, $session);

        if (!$openTestAccess) {
            self::requirePermission($allowed, $message);
        }
    }

    public static function requireJsonLogin(bool $openTestAccess, array $session): void
    {
        if (!$openTestAccess && !isset($session['usuario_logado'])) {
            header('Content-Type: application/json; charset=utf-8');
            JsonResponse::error('Nao autenticado.', 401);
        }
    }
}
