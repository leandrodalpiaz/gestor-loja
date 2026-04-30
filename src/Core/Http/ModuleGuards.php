<?php

namespace App\Core\Http;

use App\Core\Authorization\Authorizer;

class ModuleGuards
{
    public static function requireAdminCargosView(bool $openTestAccess, array $session, Authorizer $authorizer): void
    {
        WebGuards::requireAuthenticatedPermission(
            $openTestAccess,
            $session,
            $authorizer->hasPermission('admin.cargos.view'),
            'Acesso restrito a Secretaria ou ao Veneravel Mestre.'
        );
    }

    public static function requireAdminCargosManage(bool $openTestAccess, array $session, Authorizer $authorizer): void
    {
        WebGuards::requireAuthenticatedPermission(
            $openTestAccess,
            $session,
            $authorizer->hasPermission('admin.cargos.manage'),
            'Acesso restrito a Secretaria ou ao Veneravel Mestre.'
        );
    }

    public static function requireAdminLojaView(bool $openTestAccess, array $session, Authorizer $authorizer): void
    {
        WebGuards::requireAuthenticatedPermission(
            $openTestAccess,
            $session,
            $authorizer->hasPermission('admin.loja.view'),
            'Acesso restrito.'
        );
    }

    public static function requireAdminLojaManage(bool $openTestAccess, array $session, Authorizer $authorizer): void
    {
        WebGuards::requireAuthenticatedPermission(
            $openTestAccess,
            $session,
            $authorizer->hasPermission('admin.loja.manage'),
            'Acesso restrito.'
        );
    }

    public static function requireAdminAuditoriaView(bool $openTestAccess, array $session, Authorizer $authorizer): void
    {
        WebGuards::requireAuthenticatedPermission(
            $openTestAccess,
            $session,
            $authorizer->hasPermission('admin.auditoria.view'),
            'Acesso restrito ao Veneravel Mestre.'
        );
    }

    public static function requirePublicContentManage(bool $openTestAccess, array $session, Authorizer $authorizer): void
    {
        WebGuards::requireAuthenticatedPermission(
            $openTestAccess,
            $session,
            $authorizer->hasPermission('public_content.manage'),
            'Acesso restrito a Secretaria ou ao Veneravel Mestre.'
        );
    }

    public static function requireAccessManage(bool $openTestAccess, array $session, Authorizer $authorizer): void
    {
        WebGuards::requireAuthenticatedPermission(
            $openTestAccess,
            $session,
            $authorizer->hasPermission('access.manage'),
            'Acesso restrito a Secretaria.'
        );
    }

    public static function requireBibliotecaAccess(bool $openTestAccess, array $session, Authorizer $authorizer): void
    {
        // Biblioteca (catálogo) é consulta básica para todo obreiro autenticado.
        // A operação (gerência) continua protegida por permissões específicas.
        WebGuards::requireLogin($openTestAccess, $session);
    }

    public static function requireBibliotecaManageAccess(bool $openTestAccess, array $session, Authorizer $authorizer): void
    {
        self::requireBibliotecaAccess($openTestAccess, $session, $authorizer);
        WebGuards::requirePermission(
            $authorizer->hasPermission('biblioteca.manage'),
            'Acesso restrito ao Bibliotecario ou ao Veneravel Mestre.'
        );
    }

    public static function requireSecretariaAccess(bool $openTestAccess, array $session, Authorizer $authorizer): void
    {
        WebGuards::requireAuthenticatedPermission(
            $openTestAccess,
            $session,
            $authorizer->hasPermission('secretaria.manage'),
            'Acesso restrito a Secretaria ou ao Veneravel Mestre.'
        );
    }

    public static function requireObreirosViewAccess(bool $openTestAccess, array $session, Authorizer $authorizer): void
    {
        WebGuards::requireAuthenticatedPermission(
            $openTestAccess,
            $session,
            $authorizer->hasPermission('obreiros.view'),
            'Acesso restrito a Secretaria, Vigilancia, Chancelaria ou Veneravel Mestre.'
        );
    }

    public static function requireObreirosManageAccess(bool $openTestAccess, array $session, Authorizer $authorizer): void
    {
        WebGuards::requireAuthenticatedPermission(
            $openTestAccess,
            $session,
            $authorizer->hasPermission('obreiros.manage'),
            'Acesso restrito a Secretaria.'
        );
    }

    public static function requireAssistenciaAccess(bool $openTestAccess, array $session, Authorizer $authorizer): void
    {
        WebGuards::requireAuthenticatedPermission(
            $openTestAccess,
            $session,
            $authorizer->hasPermission('hospitaleiro.manage'),
            'Acesso restrito ao Mestre Hospitaleiro, Secretaria, Tesouraria ou Veneravel Mestre.'
        );
    }
}
