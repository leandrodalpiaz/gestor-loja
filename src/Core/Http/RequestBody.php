<?php

namespace App\Core\Http;

class RequestBody
{
    public static function json(): array
    {
        static $parsed = null;
        if ($parsed !== null) {
            return $parsed;
        }

        $raw = file_get_contents('php://input');
        if (!is_string($raw) || trim($raw) === '') {
            $parsed = [];
            return $parsed;
        }

        $decoded = json_decode($raw, true);
        $parsed = is_array($decoded) ? $decoded : [];

        return $parsed;
    }
}
