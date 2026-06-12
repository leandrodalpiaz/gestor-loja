<?php

namespace App\Core\Http;

class JsonResponse
{
    public static function send(array $payload, int $status = 200): void
    {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($status);

        $json = json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
            | JSON_INVALID_UTF8_SUBSTITUTE
        );

        if ($json === false) {
            error_log('[JsonResponse] Falha ao serializar resposta: ' . json_last_error_msg());
            http_response_code(500);
            $json = '{"ok":false,"erro":"Falha ao gerar resposta JSON."}';
        }

        echo $json;
        exit;
    }

    public static function error(string $message, int $status = 400): void
    {
        self::send(['ok' => false, 'erro' => $message], $status);
    }
}
