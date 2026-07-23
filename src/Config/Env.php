<?php

namespace App\Config;

class Env
{
    public static function load(string $path): void
    {
        // 1. Repopular $_ENV e $_SERVER com as variáveis de ambiente reais do SO (injetadas pelo Render/Docker)
        // Isso contorna a diretiva variables_order do php.ini de produção, que limpa $_ENV.
        $chavesConhecidas = [
            'DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASS', 'DB_SCHEMA',
            'TELEGRAM_BOT_TOKEN', 'TELEGRAM_CHAT_ID_GROUP', 'TELEGRAM_CHAT_ID_CHANCELER',
            'TELEGRAM_WEBHOOK_SECRET', 'APP_URL', 'APP_TIMEZONE', 'APP_ENV', 'APP_LOJA_NUMERO',
            'APP_DEFAULT_TENANT_SLUG', 'APP_DEFAULT_TENANT_ID', 'APP_DEFAULT_TENANT_NAME',
            'APP_ALLOW_ENV_TENANT_FALLBACK', 'APP_TEST_OPEN_ACCESS', 'APP_TEST_ALLOW_ALL_PANELS',
            'SYSTEM_ADMIN_TELEGRAM_IDS', 'SYSTEM_ADMIN_EMAIL', 'SUPABASE_JWT_SECRET', 'SUPABASE_PROJECT_REF',
            'FRONTEND_ALLOWED_ORIGINS', 'CRON_EFEMERIDES_TOKEN', 'CRON_SECRET_TOKEN', 'TELEGRAM_DRY_RUN',
            'ANGULAR_DEV_PORT', 'TEST_USER_LOGIN', 'TEST_USER_PASSWORD', 'FRONTEND_URL',
            'SYSTEM_ADMIN_WEB_LOGIN', 'SYSTEM_ADMIN_WEB_PASSWORD', 'SYSTEM_ADMIN_LOGIN', 'SYSTEM_ADMIN_PASSWORD',
            'MESTRE_HARMONIA_BASE_PATH', 'APP_LOJA_SIGLA', 'PUBLIC_ADS_ENABLED',
            'TELEGRAM_CHAT_ID', 'TELEGRAM_GRUPO_ID', 'TELEGRAM_GROUP_ID',
            'SUPABASE_URL', 'SUPABASE_KEY',
        ];
        foreach ($chavesConhecidas as $envKey) {
            if (!isset($_ENV[$envKey]) || $_ENV[$envKey] === '') {
                $val = getenv($envKey);
                if ($val !== false) {
                    $_ENV[$envKey] = $val;
                    $_SERVER[$envKey] = $val;
                }
            }
        }

        if (!file_exists($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (str_starts_with(trim($line), '#')) {
                continue;
            }

            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            if (getenv($name) === false && !array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv(sprintf('%s=%s', $name, $value));
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }

    public static function get(string $key, $default = null)
    {
        $value = getenv($key);
        if ($value === false) {
            $value = $_ENV[$key] ?? $_SERVER[$key] ?? $default;
        }
        return $value;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $raw = self::get($key, null);
        if ($raw === null) {
            return $default;
        }

        if (is_bool($raw)) {
            return $raw;
        }

        $value = strtolower(trim((string) $raw));
        if ($value === '') {
            return $default;
        }

        return in_array($value, ['1', 'true', 'yes', 'y', 'on'], true);
    }
}
