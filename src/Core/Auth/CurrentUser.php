<?php

namespace App\Core\Auth;

class CurrentUser
{
    public function __construct(
        private readonly array $session,
        private readonly mixed $roleNormalizer = null,
    ) {
    }

    public function isAuthenticated(): bool
    {
        return isset($this->session['usuario_logado']) && is_array($this->session['usuario_logado']);
    }

    public function id(): ?string
    {
        $id = $this->session['usuario_id'] ?? null;
        if ($id === null || $id === '') {
            return null;
        }

        return (string) $id;
    }

    public function data(): ?array
    {
        $user = $this->session['usuario_logado'] ?? null;
        return is_array($user) ? $user : null;
    }

    public function isTestUser(): bool
    {
        $user = $this->session['usuario_logado'] ?? null;

        return !empty($this->session['test_user'])
            || (is_array($user) && !empty($user['is_test_user']));
    }

    public function primaryRole(): string
    {
        return $this->normalizeRole((string) ($this->session['usuario_cargo'] ?? ''));
    }

    public function roles(): array
    {
        $roles = $this->session['usuario_cargos'] ?? [$this->session['usuario_cargo'] ?? ''];
        if (!is_array($roles)) {
            $roles = [$roles];
        }

        $roles = array_map(fn ($role) => $this->normalizeRole((string) $role), $roles);
        $roles = array_values(array_unique(array_filter($roles)));

        return $roles;
    }

    private function normalizeRole(string $role): string
    {
        if ($this->roleNormalizer !== null) {
            return (string) call_user_func($this->roleNormalizer, $role);
        }

        $role = strtolower(trim($role));
        return preg_replace('/[^a-z0-9_]+/', '', $role) ?? '';
    }
}
