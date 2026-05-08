<?php

namespace App\Core\Http;

class WebGuards
{
    public static function renderTelegramWebAppBridge(): void
    {
        $requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $path = parse_url($requestUri, PHP_URL_PATH) ?: '/';
        $query = [];
        parse_str((string) parse_url($requestUri, PHP_URL_QUERY), $query);

        unset($query['init_data'], $query['initData']);
        $query['tg_webapp'] = '1';
        $targetBase = $path . (!empty($query) ? '?' . http_build_query($query) : '');
        $targetBaseJs = json_encode($targetBase, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        header('Content-Type: text/html; charset=UTF-8');
        echo <<<'HTML'
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aguarde...</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
</head>
<body style="font-family:Arial,sans-serif;background:#0f172a;color:#e2e8f0;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;padding:24px;text-align:center">
    <div>
        <div style="font-size:20px;font-weight:600">Abrindo painel...</div>
        <p style="margin-top:12px;line-height:1.5;color:#cbd5e1">Estamos validando seu acesso do Telegram para entrar no painel.</p>
    </div>
HTML;
        echo '<script>';
        echo 'const targetBase = ' . $targetBaseJs . ';';
        echo 'const tg = window.Telegram && window.Telegram.WebApp ? window.Telegram.WebApp : null;';
        echo 'const initData = tg && tg.initData ? tg.initData : "";';
        echo 'if (tg && typeof tg.ready === "function") { tg.ready(); }';
        echo 'if (!initData) {';
        echo 'document.body.innerHTML = "<div style=\"font-family:Arial,sans-serif;padding:24px;text-align:center\"><strong>Não foi possível validar o Telegram.</strong><p>Abra este painel a partir do botão do bot e tente novamente.</p></div>";';
        echo '} else {';
        echo 'const separator = targetBase.includes("?") ? "&" : "?";';
        echo 'window.location.replace(targetBase + separator + "init_data=" + encodeURIComponent(initData));';
        echo '}';
        echo '</script></body></html>';
        exit;
    }

    public static function forbidHtml(string $message = 'Esta ação segue regras de responsabilidade da Loja.'): void
    {
        http_response_code(403);
        echo $message;
        exit;
    }

    public static function requireLogin(bool $openTestAccess, array $session): void
    {
        if (!$openTestAccess && !isset($session['usuario_logado'])) {
            if (!empty($_GET['tg_webapp'])) {
                self::renderTelegramWebAppBridge();
            }
            header('Location: /login');
            exit;
        }
    }

    public static function requirePermission(bool $allowed, string $message = 'Esta ação segue regras de responsabilidade da Loja.'): void
    {
        if (!$allowed) {
            self::forbidHtml($message);
        }
    }

    public static function requireAuthenticatedPermission(
        bool $openTestAccess,
        array $session,
        bool $allowed,
        string $message = 'Esta ação segue regras de responsabilidade da Loja.'
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

        if (trim((string) ($session['tenant_id'] ?? '')) === '') {
            header('Content-Type: application/json; charset=utf-8');
            JsonResponse::error('Loja nao identificada. Verifique a configuracao do ambiente.', 503);
        }
    }
}
