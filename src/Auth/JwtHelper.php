<?php
// src/Auth/JwtHelper.php

class JwtHelper {
    private static $secret = 'super_secret_key_change_me';
    private static $algo = 'HS256';

    public static function generate(array $payload, $exp = 3600) {
        $header = ["typ" => "JWT", "alg" => self::$algo];
        $payload["exp"] = time() + $exp;
        $segments = [
            self::base64url_encode(json_encode($header)),
            self::base64url_encode(json_encode($payload))
        ];
        $signing_input = implode('.', $segments);
        $signature = self::base64url_encode(
            hash_hmac('sha256', $signing_input, self::$secret, true)
        );
        return $signing_input . '.' . $signature;
    }

    public static function validate($jwt) {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) return false;
        list($header_b64, $payload_b64, $signature_b64) = $parts;
        $signing_input = $header_b64 . '.' . $payload_b64;
        $expected_signature = self::base64url_encode(
            hash_hmac('sha256', $signing_input, self::$secret, true)
        );
        if (!hash_equals($expected_signature, $signature_b64)) return false;
        $payload = json_decode(self::base64url_decode($payload_b64), true);
        if (!$payload || !isset($payload["exp"])) return false;
        if ($payload["exp"] < time()) return false;
        return $payload;
    }

    private static function base64url_encode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64url_decode($data) {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
