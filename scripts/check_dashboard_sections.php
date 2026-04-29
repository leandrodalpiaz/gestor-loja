<?php

declare(strict_types=1);

require __DIR__ . '/../src/autoload.php';

use App\Core\Authorization\PermissionMap;
use App\Core\DashboardSections;

/**
 * Regression guard:
 * - no duplicated href inside the rendered dashboard menu for any tested profile
 * - tested profiles include each single role and pairwise role combinations
 */
$permissionMap = new PermissionMap();

$reflection = new ReflectionClass(PermissionMap::class);
$rolePermissions = (array) $reflection->getConstant('ROLE_PERMISSIONS');
$roles = array_values(array_keys($rolePermissions));
sort($roles);

$profiles = [];

foreach ($roles as $role) {
    $profiles[] = [$role];
}

for ($i = 0; $i < count($roles); $i++) {
    for ($j = $i + 1; $j < count($roles); $j++) {
        $profiles[] = [$roles[$i], $roles[$j]];
    }
}

$failures = [];

foreach ($profiles as $profileRoles) {
    $permissions = $permissionMap->permissionsForRoles($profileRoles);

    // Regra de sessao atual: perfis nao-admin tecnicos herdam papel-base obreiro.
    if (!in_array('admin', $profileRoles, true) && !in_array('obreiro', $profileRoles, true)) {
        $permissions = array_values(array_unique(array_merge(
            $permissions,
            $permissionMap->permissionsForRoles(['obreiro'])
        )));
    }

    $can = static fn(string $permission): bool => in_array('*', $permissions, true) || in_array($permission, $permissions, true);
    $isSystemAdmin = in_array('admin', $profileRoles, true);
    $sections = DashboardSections::build($can, $isSystemAdmin);

    $seen = [];
    $duplicates = [];

    foreach ($sections as $section) {
        foreach ((array) ($section['itens'] ?? []) as $item) {
            $href = trim((string) ($item['href'] ?? ''));
            if ($href === '') {
                continue;
            }
            if (isset($seen[$href])) {
                $duplicates[$href] = true;
                continue;
            }
            $seen[$href] = true;
        }
    }

    if ($duplicates !== []) {
        $failures[] = [
            'profile' => implode('+', $profileRoles),
            'hrefs' => array_keys($duplicates),
        ];
    }
}

if ($failures !== []) {
    fwrite(STDERR, "[FAIL] Dashboard sections have duplicated href(s).\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, '- profile=' . $failure['profile'] . ' duplicated=' . implode(', ', $failure['hrefs']) . "\n");
    }
    exit(1);
}

echo '[OK] Dashboard sections are unique for all tested profiles.' . PHP_EOL;
exit(0);
