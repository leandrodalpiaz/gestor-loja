<?php

namespace App\Core\Tenant;

class TenantContext
{
    public function __construct(
        private readonly ?string $tenantId,
        private readonly string $tenantSlug,
        private readonly string $tenantName,
        private readonly bool $resolvedFromSession = false,
    ) {
    }

    public static function fromSessionAndEnv(array $session, array $env): self
    {
        $tenantId = self::stringOrNull($session['tenant_id'] ?? $env['APP_DEFAULT_TENANT_ID'] ?? null);
        $tenantSlug = self::stringOrFallback(
            $session['tenant_slug'] ?? $env['APP_DEFAULT_TENANT_SLUG'] ?? null,
            'renascenca'
        );
        $tenantName = self::stringOrFallback(
            $session['tenant_name'] ?? $env['APP_DEFAULT_TENANT_NAME'] ?? null,
            'Loja Renascenca'
        );

        $resolvedFromSession = isset($session['tenant_id']) || isset($session['tenant_slug']) || isset($session['tenant_name']);

        return new self($tenantId, $tenantSlug, $tenantName, $resolvedFromSession);
    }

    public function id(): ?string
    {
        return $this->tenantId;
    }

    public function slug(): string
    {
        return $this->tenantSlug;
    }

    public function name(): string
    {
        return $this->tenantName;
    }

    public function resolvedFromSession(): bool
    {
        return $this->resolvedFromSession;
    }

    public function toSessionPayload(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'tenant_slug' => $this->tenantSlug,
            'tenant_name' => $this->tenantName,
        ];
    }

    private static function stringOrNull(mixed $value): ?string
    {
        $value = trim((string) $value);
        return $value !== '' ? $value : null;
    }

    private static function stringOrFallback(mixed $value, string $fallback): string
    {
        $value = trim((string) $value);
        return $value !== '' ? $value : $fallback;
    }
}
