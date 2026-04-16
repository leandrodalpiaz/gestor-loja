<?php

namespace App\Core\Authorization;

class PermissionMap
{
    /**
     * Primeira matriz RBAC em modo compatível.
     * Papeis operacionais existentes continuam convivendo com papeis-base.
     */
    private const ROLE_PERMISSIONS = [
        'admin' => ['*'],
        'veneravel' => [
            'dashboard.view',
            'admin.cargos.view',
            'admin.cargos.manage',
            'admin.loja.view',
            'admin.auditoria.view',
            'hospitaleiro.manage',
            'secretaria.manage',
            'obreiros.view',
            'tesouraria.manage',
            'chancelaria.manage',
        ],
        'secretario' => [
            'dashboard.view',
            'admin.cargos.view',
            'admin.cargos.manage',
            'hospitaleiro.manage',
            'secretaria.manage',
            'obreiros.view',
            'obreiros.manage',
        ],
        'tesoureiro' => [
            'dashboard.view',
            'hospitaleiro.manage',
            'tesouraria.manage',
            'financeiro.self',
        ],
        'obreiro' => [
            'dashboard.view',
            'financeiro.self',
            'biblioteca.self',
        ],
        'bibliotecario' => [
            'dashboard.view',
            'biblioteca.manage',
            'biblioteca.self',
        ],
        'chanceler' => [
            'dashboard.view',
            'chancelaria.manage',
            'obreiros.view',
        ],
        'primeiro_vigilante' => [
            'dashboard.view',
            'obreiros.view',
        ],
        'segundo_vigilante' => [
            'dashboard.view',
            'obreiros.view',
        ],
        'hospitaleiro' => [
            'dashboard.view',
            'hospitaleiro.manage',
        ],
    ];

    private const ROUTE_PERMISSIONS = [
        '/dashboard' => 'dashboard.view',
        '/admin/cargos' => 'admin.cargos.view',
        '/admin/loja' => 'admin.loja.view',
        '/admin/loja/salvar' => 'admin.loja.manage',
        '/admin/auditoria' => 'admin.auditoria.view',
        '/secretaria' => 'secretaria.manage',
        '/assistencia' => 'hospitaleiro.manage',
        '/assistencia/ocorrencias/salvar' => 'hospitaleiro.manage',
        '/assistencia/ocorrencias/status' => 'hospitaleiro.manage',
        '/assistencia/ocorrencias/visita' => 'hospitaleiro.manage',
        '/obreiros' => 'obreiros.view',
        '/obreiros/novo' => 'obreiros.manage',
        '/obreiros/salvar' => 'obreiros.manage',
        '/obreiros/editar' => 'obreiros.manage',
        '/obreiros/atualizar' => 'obreiros.manage',
        '/tesouraria/caixa' => 'tesouraria.manage',
        '/financeiro/minhas-obrigacoes' => 'financeiro.self',
        '/biblioteca' => 'biblioteca.self',
        '/biblioteca/detalhes' => 'biblioteca.self',
        '/biblioteca/meus-emprestimos' => 'biblioteca.self',
        '/biblioteca/solicitar' => 'biblioteca.self',
        '/biblioteca/comentar' => 'biblioteca.self',
        '/biblioteca/reagir' => 'biblioteca.self',
        '/biblioteca/adicionar' => 'biblioteca.manage',
        '/biblioteca/editar' => 'biblioteca.manage',
        '/biblioteca/excluir' => 'biblioteca.manage',
        '/biblioteca/emprestimos' => 'biblioteca.manage',
        '/biblioteca/devolver' => 'biblioteca.manage',
    ];

    public function permissionsForRoles(array $roles): array
    {
        $permissions = [];

        foreach ($roles as $role) {
            foreach (self::ROLE_PERMISSIONS[$role] ?? [] as $permission) {
                $permissions[] = $permission;
            }
        }

        return array_values(array_unique($permissions));
    }

    public function permissionForRoute(string $route): ?string
    {
        return self::ROUTE_PERMISSIONS[$route] ?? null;
    }
}
