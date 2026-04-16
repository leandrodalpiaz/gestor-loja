<?php

namespace App\Core\Http;

class JsonResponse
{
    public static function send(array $payload, int $status = 200): void
    {
        http_response_code($status);
        echo json_encode($payload);
        exit;
    }

    public static function error(string $message, int $status = 400): void
    {
        self::send(['ok' => false, 'erro' => $message], $status);
    }
}
