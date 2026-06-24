<?php

namespace App\Core\Auth;

use App\Config\Env;

class SupabaseJwtValidator
{
    /**
     * Valida um token JWT do Supabase e retorna seu payload em caso de sucesso.
     * Retorna null se o token for inválido, expirado ou se houver falha na assinatura.
     */
    public static function validate(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            error_log('[JWT Validator] Token inválido: estrutura incorreta.');
            return null;
        }

        [$headerEncoded, $payloadEncoded, $signatureEncoded] = $parts;

        $header = json_decode(self::base64UrlDecode($headerEncoded), true);
        $alg = strtolower($header['alg'] ?? '');
        if (!$header || ($alg !== 'hs256' && $alg !== 'es256')) {
            error_log('[JWT Validator] Algoritmo não suportado ou cabeçalho inválido: ' . ($header['alg'] ?? 'nenhum'));
            return null;
        }

        if ($alg === 'hs256') {
            // Obtém o segredo do JWT do Supabase a partir das variáveis de ambiente
            $secret = trim((string) (Env::get('SUPABASE_JWT_SECRET') ?: Env::get('JWT_SECRET') ?: ''));
            if ($secret === '') {
                // Se for ambiente local, podemos ter um fallback para facilitar testes, mas alertar
                $appEnv = strtolower(trim((string) ($_ENV['APP_ENV'] ?? 'local')));
                if ($appEnv === 'local' || $appEnv === 'development' || $appEnv === 'dev') {
                    $secret = 'super-secret-jwt-key-for-local-testing-only-1234567890';
                    error_log('[JWT Validator] AVISO: SUPABASE_JWT_SECRET não configurado. Usando chave de teste local.');
                } else {
                    error_log('[JWT Validator] ERRO CRÍTICO: SUPABASE_JWT_SECRET não está configurado nas variáveis de ambiente.');
                    return null;
                }
            }

            // Verifica a assinatura do token HS256
            $signatureExpected = self::base64UrlDecode($signatureEncoded);
            $signatureCalculated = hash_hmac('sha256', "$headerEncoded.$payloadEncoded", $secret, true);

            if (!hash_equals($signatureExpected, $signatureCalculated)) {
                // Tenta decodificar o segredo caso ele esteja codificado em base64 (como padrao no Supabase)
                $decodedSecret = base64_decode($secret, true);
                if ($decodedSecret !== false && $decodedSecret !== '') {
                    $signatureCalculatedDecoded = hash_hmac('sha256', "$headerEncoded.$payloadEncoded", $decodedSecret, true);
                    if (hash_equals($signatureExpected, $signatureCalculatedDecoded)) {
                        $signatureCalculated = $signatureCalculatedDecoded;
                    }
                }
            }

            if (!hash_equals($signatureExpected, $signatureCalculated)) {
                error_log('[JWT Validator] Assinatura do token HS256 nao corresponde.');
                return null;
            }
        } elseif ($alg === 'es256') {
            $kid = $header['kid'] ?? '';
            if ($kid === '') {
                error_log('[JWT Validator] kid ausente no cabeçalho do token ES256.');
                return null;
            }

            $jwks = self::getJwks();
            if (!$jwks || !isset($jwks['keys'])) {
                error_log('[JWT Validator] Falha ao recuperar as chaves JWKS do Supabase.');
                return null;
            }

            $jwk = null;
            foreach ($jwks['keys'] as $key) {
                if (($key['kid'] ?? '') === $kid) {
                    $jwk = $key;
                    break;
                }
            }

            if (!$jwk) {
                error_log('[JWT Validator] Nenhuma chave correspondente encontrada para kid: ' . $kid);
                return null;
            }

            $pemKey = self::jwkToPem($jwk);
            if (!$pemKey) {
                error_log('[JWT Validator] Falha ao converter JWK em PEM.');
                return null;
            }

            $rawSignature = self::base64UrlDecode($signatureEncoded);
            if (strlen($rawSignature) !== 64) {
                error_log('[JWT Validator] Assinatura do token ES256 com tamanho incorreto (esperado 64 bytes).');
                return null;
            }

            $derSignature = self::signatureToDer($rawSignature);

            // Verifica a assinatura via OpenSSL usando a chave pública
            $dataToVerify = "$headerEncoded.$payloadEncoded";
            $verificationResult = openssl_verify($dataToVerify, $derSignature, $pemKey, OPENSSL_ALGO_SHA256);

            if ($verificationResult !== 1) {
                error_log('[JWT Validator] Falha na verificação da assinatura do token ES256 via OpenSSL.');
                return null;
            }
        }

        // Decodifica o payload
        $payload = json_decode(self::base64UrlDecode($payloadEncoded), true);
        if (!$payload) {
            error_log('[JWT Validator] Falha ao decodificar o payload JSON.');
            return null;
        }

        // Verifica expiração (claim 'exp')
        $exp = isset($payload['exp']) ? (int) $payload['exp'] : 0;
        if ($exp > 0 && time() >= $exp) {
            error_log('[JWT Validator] Token expirado em ' . date('Y-m-d H:i:s', $exp));
            return null;
        }

        return $payload;
    }

    /**
     * Decodifica uma string codificada em Base64 URL Safe.
     */
    private static function base64UrlDecode(string $input): string
    {
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $padlen = 4 - $remainder;
            $input .= str_repeat('=', $padlen);
        }
        return base64_decode(strtr($input, '-_', '+/')) ?: '';
    }

    /**
     * Recupera as chaves públicas (JWKS) do Supabase e as armazena em cache local.
     */
    private static function getJwks(): ?array
    {
        $basePath = realpath(__DIR__ . '/../../..') ?: __DIR__;
        $cachePath = $basePath . '/storage/jwks_cache.json';
        $cacheTime = 24 * 3600; // 24 horas
        $appEnv = strtolower(trim((string) (Env::get('APP_ENV') ?: 'local')));

        if (file_exists($cachePath) && (time() - filemtime($cachePath) < $cacheTime)) {
            $data = json_decode(file_get_contents($cachePath), true);
            if ($data) {
                return $data;
            }
        }

        $projectRef = trim((string) (Env::get('SUPABASE_PROJECT_REF') ?: 'qkclleforgiatyljydqz'));
        $url = "https://{$projectRef}.supabase.co/auth/v1/.well-known/jwks.json";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if (($httpCode !== 200 || !$response) && in_array($appEnv, ['local', 'development', 'dev'], true)) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlErrorFallback = curl_error($ch);
            curl_close($ch);

            if ($httpCode === 200 && $response) {
                error_log('[JWT Validator] AVISO: JWKS carregado sem verificacao SSL apenas em ambiente local.');
            } else {
                $curlError = $curlError !== '' ? $curlError : $curlErrorFallback;
            }
        }

        if ($httpCode !== 200 || !$response) {
            error_log("[JWT Validator] Erro ao buscar JWKS de $url (HTTP $httpCode)" . ($curlError !== '' ? " - $curlError" : ''));
            if (file_exists($cachePath)) {
                return json_decode(file_get_contents($cachePath), true);
            }
            return null;
        }

        $jwks = json_decode($response, true);
        if ($jwks) {
            $dir = dirname($cachePath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            file_put_contents($cachePath, $response);
            return $jwks;
        }

        return null;
    }

    /**
     * Converte um JWK EC P-256 (coordenadas x e y) para o formato PEM.
     */
    private static function jwkToPem(array $jwk): ?string
    {
        if (($jwk['kty'] ?? '') !== 'EC' || ($jwk['crv'] ?? '') !== 'P-256') {
            return null;
        }

        $x = self::base64UrlDecode($jwk['x'] ?? '');
        $y = self::base64UrlDecode($jwk['y'] ?? '');

        if (strlen($x) !== 32 || strlen($y) !== 32) {
            return null;
        }

        // Cabeçalho DER estático de 26 bytes para chave pública EC P-256 (prime256v1)
        $derHeader = hex2bin('3059301306072a8648ce3d020106082a8648ce3d030107034200');
        
        // Ponto não compactado inicia com 0x04, seguido de X e Y
        $publicKeyData = "\x04" . $x . $y;

        $der = $derHeader . $publicKeyData;

        return "-----BEGIN PUBLIC KEY-----\n" .
               chunk_split(base64_encode($der), 64, "\n") .
               "-----END PUBLIC KEY-----";
    }

    /**
     * Converte uma assinatura ES256 bruta (concatenação R + S de 64 bytes) para o formato DER (ASN.1).
     */
    private static function signatureToDer(string $rawSignature): string
    {
        $r = substr($rawSignature, 0, 32);
        $s = substr($rawSignature, 32, 32);

        $encodeInteger = function(string $data) {
            $data = ltrim($data, "\x00");
            if ($data === '') {
                $data = "\x00";
            }
            if (ord($data[0]) & 0x80) {
                $data = "\x00" . $data;
            }
            return "\x02" . chr(strlen($data)) . $data;
        };

        $derR = $encodeInteger($r);
        $derS = $encodeInteger($s);

        $totalLength = strlen($derR) + strlen($derS);

        return "\x30" . chr($totalLength) . $derR . $derS;
    }
}
