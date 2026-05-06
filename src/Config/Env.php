<?php

namespace App\Config;

class Env
{
    public static function load(string $path): void
    {
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
