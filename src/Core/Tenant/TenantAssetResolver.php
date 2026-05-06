<?php

namespace App\Core\Tenant;

class TenantAssetResolver
{
    private const PUBLIC_DIR = __DIR__ . '/../../../public';

    public static function resolve(?string $tenantSlug, string $relativePath, string $neutralFallback): string
    {
        $relativePath = trim(str_replace('\\', '/', $relativePath), '/');
        $neutralFallback = self::normalizeAssetPath($neutralFallback);

        $slug = self::normalizeSlug($tenantSlug);
        if ($slug !== '') {
            $candidate = '/assets/tenants/' . rawurlencode($slug) . '/' . $relativePath;
            if (self::assetExists($candidate)) {
                return $candidate;
            }
        }

        if (self::assetExists($neutralFallback)) {
            return $neutralFallback;
        }

        return '';
    }

    public static function resolveLogo(?string $tenantSlug): string
    {
        $slug = self::normalizeSlug($tenantSlug);
        if ($slug !== '') {
            foreach (['logo.png', 'logo.svg'] as $logoPath) {
                $candidate = '/assets/tenants/' . rawurlencode($slug) . '/' . $logoPath;
                if (self::assetExists($candidate)) {
                    return $candidate;
                }
            }
        }

        if (self::assetExists('/assets/logo-renascenca.png')) {
            return '/assets/logo-renascenca.png';
        }

        return self::assetExists('/assets/placeholders/logo-loja.svg')
            ? '/assets/placeholders/logo-loja.svg'
            : '';
    }

    private static function assetExists(string $assetPath): bool
    {
        $assetPath = self::normalizeAssetPath($assetPath);
        if ($assetPath === '') {
            return false;
        }

        return file_exists(self::PUBLIC_DIR . $assetPath);
    }

    private static function normalizeSlug(?string $tenantSlug): string
    {
        $slug = strtolower(trim((string) $tenantSlug));
        $slug = preg_replace('/[^a-z0-9_-]+/', '-', $slug) ?? '';

        return trim($slug, '-_');
    }

    private static function normalizeAssetPath(string $path): string
    {
        $path = '/' . ltrim(str_replace('\\', '/', trim($path)), '/');
        return $path === '/' ? '' : $path;
    }
}
