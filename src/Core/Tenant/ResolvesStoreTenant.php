<?php

namespace App\Core\Tenant;

use PDO;

trait ResolvesStoreTenant
{
    protected function resolveCurrentStoreId(PDO $db): int
    {
        $tenantContext = TenantContext::fromSessionAndEnv($_SESSION ?? [], $_ENV);
        $tenantResolver = new StoreTenantResolver($db, $tenantContext, $_ENV);

        return $tenantResolver->resolveLojaId();
    }
}
