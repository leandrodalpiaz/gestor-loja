<?php

namespace App\Core\Tenant;

use PDO;

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
        $tenantId = trim((string) ($this->tenantContext?->id() ?? ''));
        if ($tenantId !== '' && ctype_digit($tenantId)) {
            return (int) $tenantId;
        }

        $tenantSlug = trim((string) ($this->tenantContext?->slug() ?? ''));
        if ($tenantSlug !== '') {
            $stmt = $this->db->prepare(
                "SELECT id
                 FROM public.lojas
                 WHERE LOWER(sigla) = LOWER(:slug)
                    OR LOWER(numero_loja) = LOWER(:slug)
                    OR LOWER(REPLACE(nome, ' ', '-')) = LOWER(:slug)
                 ORDER BY id
                 LIMIT 1"
            );
            $stmt->execute(['slug' => $tenantSlug]);
            $id = $stmt->fetchColumn();
            if ($id !== false) {
                return (int) $id;
            }
        }

        $envLojaNumero = trim((string) ($this->env['APP_LOJA_NUMERO'] ?? ''));
        if ($envLojaNumero !== '') {
            $stmt = $this->db->prepare("SELECT id FROM public.lojas WHERE numero_loja = :numero LIMIT 1");
            $stmt->execute(['numero' => $envLojaNumero]);
            $id = $stmt->fetchColumn();
            if ($id !== false) {
                return (int) $id;
            }
        }

        $stmt = $this->db->query("SELECT id FROM public.lojas ORDER BY id LIMIT 1");
        $id = $stmt->fetchColumn();

        return $id !== false ? (int) $id : 1;
    }
}
