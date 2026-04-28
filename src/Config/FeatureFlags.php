<?php

namespace App\Config;

final class FeatureFlags
{
    public static function enabled(string $key, bool $default = false): bool
    {
        return Env::bool($key, $default);
    }

    public static function pwaSessoes(): bool
    {
        return self::enabled('FEATURE_PWA_SESSOES', false);
    }

    public static function pwaBiblioteca(): bool
    {
        return self::enabled('FEATURE_PWA_BIBLIOTECA', false);
    }

    public static function pwaComunicacao(): bool
    {
        return self::enabled('FEATURE_PWA_COMUNICACAO', false);
    }

    public static function pwaAdminCrud(): bool
    {
        return self::enabled('FEATURE_PWA_ADMIN_CRUD', false);
    }
}

