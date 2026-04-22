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
            'public_content.manage',
            'hospitaleiro.manage',
            'secretaria.manage',
            'obreiros.view',
            'tesouraria.manage',
            'chancelaria.manage',
            'veneravel.manage',
            'orador.view',
            'mestre_banquetes.manage',
            'mestre_harmonia.manage',
            'vigilancia.primeiro.manage',
            'vigilancia.segundo.manage',
            'biblioteca.classificar',
        ],
        'secretario' => [
            'dashboard.view',
            'admin.cargos.view',
            'admin.cargos.manage',
            'public_content.manage',
            'access.manage',
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
            'biblioteca.classificar',
        ],
        'chanceler' => [
            'dashboard.view',
            'chancelaria.manage',
            'obreiros.view',
        ],
        'primeiro_vigilante' => [
            'dashboard.view',
            'obreiros.view',
            'vigilancia.primeiro.manage',
            'biblioteca.classificar',
        ],
        'segundo_vigilante' => [
            'dashboard.view',
            'obreiros.view',
            'vigilancia.segundo.manage',
            'biblioteca.classificar',
        ],
        'hospitaleiro' => [
            'dashboard.view',
            'hospitaleiro.manage',
        ],
        'orador' => [
            'dashboard.view',
            'orador.view',
        ],
        'mestre_banquetes' => [
            'dashboard.view',
            'mestre_banquetes.manage',
        ],
        'mestre_harmonia' => [
            'dashboard.view',
            'mestre_harmonia.manage',
        ],
        'mestre_de_harmonia' => [
            'dashboard.view',
            'mestre_harmonia.manage',
        ],
    ];

    private const ROUTE_PERMISSIONS = [
        '/dashboard' => 'dashboard.view',
        '/veneravel' => 'veneravel.manage',
        '/veneravel/dashboard' => 'veneravel.manage',
        '/veneravel/sessoes/publicar' => 'veneravel.manage',
        '/veneravel/sessoes/cancelar' => 'veneravel.manage',
        '/veneravel/sessoes/reabrir' => 'veneravel.manage',
        '/veneravel/sessoes/realizar' => 'veneravel.manage',
        '/veneravel/balaustres/abrir-votacao' => 'veneravel.manage',
        '/veneravel/balaustres/encerrar-votacao' => 'veneravel.manage',
        '/orador' => 'orador.view',
        '/orador/dashboard' => 'orador.view',
        '/miniapp/orador' => 'orador.view',
        '/api/miniapp/orador/dashboard' => 'orador.view',
        '/mestre-banquetes' => 'mestre_banquetes.manage',
        '/mestre-banquetes/dashboard' => 'mestre_banquetes.manage',
        '/mestre-banquetes/operacao/salvar' => 'mestre_banquetes.manage',
        '/mestre-harmonia' => 'mestre_harmonia.manage',
        '/miniapp/mestre-harmonia' => 'mestre_harmonia.manage',
        '/api/mestre-harmonia/scan' => 'mestre_harmonia.manage',
        '/api/mestre-harmonia/audio' => 'mestre_harmonia.manage',
        '/api/mestre-harmonia/operador' => 'mestre_harmonia.manage',
        '/chanceler/sessao' => 'chancelaria.manage',
        '/chanceler/sessao/dashboard' => 'chancelaria.manage',
        '/chanceler/sessao/presenca' => 'chancelaria.manage',
        '/admin/cargos' => 'admin.cargos.view',
        '/admin/loja' => 'admin.loja.view',
        '/admin/loja/salvar' => 'admin.loja.manage',
        '/admin/auditoria' => 'admin.auditoria.view',
        '/admin/acessos' => 'access.manage',
        '/admin/acessos/atualizar' => 'access.manage',
        '/admin/convites' => 'access.manage',
        '/admin/convites/gerar' => 'access.manage',
        '/admin/conteudo-publico' => 'public_content.manage',
        '/admin/conteudo-publico/salvar' => 'public_content.manage',
        '/admin/conteudo-publico/excluir' => 'public_content.manage',
        '/secretaria' => 'secretaria.manage',
        '/primeiro-vigilante' => 'vigilancia.primeiro.manage',
        '/primeiro-vigilante/trilha/atualizar' => 'vigilancia.primeiro.manage',
        '/primeiro-vigilante/trilha/acao-rapida' => 'vigilancia.primeiro.manage',
        '/primeiro-vigilante/leitura/salvar' => 'vigilancia.primeiro.manage',
        '/primeiro-vigilante/certificado/solicitar' => 'vigilancia.primeiro.manage',
        '/segundo-vigilante' => 'vigilancia.segundo.manage',
        '/segundo-vigilante/trilha/atualizar' => 'vigilancia.segundo.manage',
        '/segundo-vigilante/trilha/acao-rapida' => 'vigilancia.segundo.manage',
        '/segundo-vigilante/leitura/salvar' => 'vigilancia.segundo.manage',
        '/segundo-vigilante/certificado/solicitar' => 'vigilancia.segundo.manage',
        '/segundo-vigilante/exaltacao/recomendar' => 'vigilancia.segundo.manage',
        '/assistencia' => 'hospitaleiro.manage',
        '/assistencia/ocorrencias/salvar' => 'hospitaleiro.manage',
        '/assistencia/ocorrencias/status' => 'hospitaleiro.manage',
        '/assistencia/ocorrencias/visita' => 'hospitaleiro.manage',
        '/obreiros' => 'obreiros.view',
        '/obreiros/novo' => 'obreiros.manage',
        '/obreiros/salvar' => 'obreiros.manage',
        '/obreiros/editar' => 'obreiros.manage',
        '/obreiros/atualizar' => 'obreiros.manage',
        '/obreiros/inativar' => 'obreiros.manage',
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
        '/biblioteca/classificar' => 'biblioteca.classificar',
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
