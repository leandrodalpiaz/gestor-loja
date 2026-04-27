<?php

namespace App\Core\Tenant;

use PDO;
use RuntimeException;

class StoreTenantResolver
{
    public function __construct(
        private readonly PDO $db,
        private readonly ?TenantContext $tenantContext = null,
        private readonly array $env = [],
    ) {
    }

    public function resolveLojaId(): int
    {
        $loja = $this->resolveLoja();
        if (!$loja) {
            throw new RuntimeException('Loja não identificada. Verifique a configuração do ambiente.');
        }

        return (int) $loja['id'];
    }

    public function resolveLoja(): ?array
    {
        $tenantId = trim((string) ($this->tenantContext?->id() ?? ''));
        if ($tenantId !== '' && ctype_digit($tenantId)) {
            $loja = $this->findLojaById((int) $tenantId);
            if ($loja) {
                return $loja;
            }
        }

        $tenantSlug = trim((string) ($this->tenantContext?->slug() ?? ''));
        if ($tenantSlug !== '') {
            $loja = $this->findLojaBySlug($tenantSlug);
            if ($loja) {
                return $loja;
            }
        }

        $envLojaNumero = trim((string) ($this->env['APP_LOJA_NUMERO'] ?? ''));
        if ($envLojaNumero !== '') {
            $loja = $this->findLojaByNumero($envLojaNumero);
            if ($loja) {
                return $loja;
            }
        }

        return null;
    }

    private function findLojaById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $stmt = $this->db->prepare(
            "SELECT id, numero_loja, sigla, nome
             FROM public.lojas
             WHERE id = :id
             LIMIT 1"
        );
        $stmt->execute(['id' => $id]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function findLojaBySlug(string $slug): ?array
    {
        $slug = trim($slug);
        if ($slug === '') {
            return null;
        }

        $slugNormalized = strtolower($slug);
        $slugNormalized = preg_replace('/[^a-z0-9\-]+/', '-', $slugNormalized) ?? $slugNormalized;
        $slugNormalized = trim($slugNormalized, '-');
        $slugNoLojaPrefix = preg_replace('/^loja-/', '', $slugNormalized) ?? $slugNormalized;
        $slugWithLojaPrefix = str_starts_with($slugNormalized, 'loja-') ? $slugNormalized : ('loja-' . $slugNormalized);

        $stmt = $this->db->prepare(
            "SELECT id, numero_loja, sigla, nome
             FROM public.lojas
             WHERE LOWER(sigla) = LOWER(:slug)
                OR LOWER(numero_loja) = LOWER(:slug)
                OR LOWER(REPLACE(nome, ' ', '-')) = LOWER(:slug)
                OR LOWER(REPLACE(nome, ' ', '-')) = LOWER(:slug_with_loja)
                OR LOWER(REPLACE(nome, ' ', '-')) = LOWER(:slug_no_loja)
             ORDER BY id
             LIMIT 1"
        );
        $stmt->execute([
            'slug' => $slug,
            'slug_with_loja' => $slugWithLojaPrefix,
            'slug_no_loja' => $slugNoLojaPrefix,
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function findLojaByNumero(string $numero): ?array
    {
        $numero = trim($numero);
        if ($numero === '') {
            return null;
        }

        $stmt = $this->db->prepare(
            "SELECT id, numero_loja, sigla, nome
             FROM public.lojas
             WHERE numero_loja = :numero
             LIMIT 1"
        );
        $stmt->execute(['numero' => $numero]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}
