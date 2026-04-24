<?php

namespace App\Services;

/**
 * Valida o initData enviado pelo Telegram Web App (Mini App).
 * Implementa a verificação HMAC-SHA256 conforme documentação oficial:
 * https://core.telegram.org/bots/webapps#validating-data-received-via-the-mini-app
 */
class TelegramInitDataValidator
{
    private const MAX_AGE_SECONDS = 3600; // 1 hora

    /**
     * Valida o initData e retorna o array do usuário Telegram,
     * ou null se a assinatura for inválida ou expirada.
     *
     * @param string $initData  Valor bruto de window.Telegram.WebApp.initData
     * @param string $botToken  Token do bot (TELEGRAM_BOT_TOKEN)
     * @return array|null       ['id' => int, 'first_name' => string, ...]
     */
    public static function validate(string $initData, string $botToken): ?array
    {
        if ($initData === '' || $botToken === '') {
            return null;
        }

        parse_str($initData, $params);

        $hash = $params['hash'] ?? '';
        if ($hash === '') {
            return null;
        }
        unset($params['hash']);

        // Monta data-check-string (pares key=value ordenados) conforme especificação.
        ksort($params);
        $dataCheckString = implode("\n", array_map(
            static fn(string $k, string $v) => "{$k}={$v}",
            array_keys($params),
            array_values($params)
        ));

        // Deriva a chave secreta a partir do token do bot (fluxo oficial do Telegram).
        $secretKey = hash_hmac('sha256', $botToken, 'WebAppData', true);
        $expectedHash = hash_hmac('sha256', $dataCheckString, $secretKey);

        if (!hash_equals($expectedHash, $hash)) {
            return null;
        }

        // Rejeita payload fora da janela de validade definida.
        $authDate = (int) ($params['auth_date'] ?? 0);
        if ((time() - $authDate) > self::MAX_AGE_SECONDS) {
            return null;
        }

        $userJson = $params['user'] ?? '{}';
        $user = json_decode($userJson, true);

        if (!is_array($user) || empty($user['id'])) {
            return null;
        }

        return $user;
    }

    /**
     * Emite resposta JSON 403 e encerra execução se initData for inválido.
     * Atalho para uso nos endpoints da API Mini App.
     */
    public static function requireValid(string $initData, string $botToken): array
    {
        $user = self::validate($initData, $botToken);
        if ($user === null) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['erro' => 'initData inválido ou expirado.']);
            exit;
        }
        return $user;
    }
}
