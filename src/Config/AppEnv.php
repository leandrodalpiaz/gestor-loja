<?php

namespace App\Config;

final class AppEnv
{
    public static function name(): string
    {
        return strtolower(trim((string) Env::get('APP_ENV', 'local')));
    }

    public static function isProduction(): bool
    {
        return self::name() === 'production';
    }

    public static function telegramDryRun(): bool
    {
        if (Env::get('TELEGRAM_DRY_RUN', null) !== null) {
            return Env::bool('TELEGRAM_DRY_RUN', false);
        }

        // Default seguro: em não-produção, não envia Telegram real.
        return !self::isProduction();
    }
}

