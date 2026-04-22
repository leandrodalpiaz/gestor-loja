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
            'Acesso restrito ao Administrador, Secretario ou Veneravel Mestre.'
        );
    }

    public static function requireAdminCargosManage(bool $openTestAccess, array $session, Authorizer $authorizer): void
    {
        WebGuards::requireAuthenticatedPermission(
            $openTestAccess,
            $session,
            $authorizer->hasPermission('admin.cargos.manage'),
            'Acesso restrito ao Administrador, Secretario ou Veneravel Mestre.'
        );
    }

    public static function requireAdminLojaView(bool $openTestAccess, array $session, Authorizer $authorizer): void
    {
        WebGuards::requireAuthenticatedPermission(
            $openTestAccess,
            $session,
            $authorizer->hasPermission('admin.loja.view'),
            'Acesso restrito ao Administrador.'
        );
    }

    public static function requireAdminLojaManage(bool $openTestAccess, array $session, Authorizer $authorizer): void
    {
        WebGuards::requireAuthenticatedPermission(
            $openTestAccess,
            $session,
            $authorizer->hasPermission('admin.loja.manage'),
            'Acesso restrito ao Administrador.'
        );
    }

    public static function requireAdminAuditoriaView(bool $openTestAccess, array $session, Authorizer $authorizer): void
    {
        WebGuards::requireAuthenticatedPermission(
            $openTestAccess,
            $session,
            $authorizer->hasPermission('admin.auditoria.view'),
            'Acesso restrito ao Administrador ou Veneravel Mestre.'
        );
    }

    public static function requirePublicContentManage(bool $openTestAccess, array $session, Authorizer $authorizer): void
    {
        WebGuards::requireAuthenticatedPermission(
            $openTestAccess,
            $session,
            $authorizer->hasPermission('public_content.manage'),
            'Acesso restrito ao Administrador, Secretario ou Veneravel Mestre.'
        );
    }

    public static function requireAccessManage(bool $openTestAccess, array $session, Authorizer $authorizer): void
    {
        WebGuards::requireAuthenticatedPermission(
            $openTestAccess,
            $session,
            $authorizer->hasPermission('access.manage'),
            'Acesso restrito ao Administrador ou Secretario.'
        );
    }

    public static function requireBibliotecaAccess(bool $openTestAccess, array $session, Authorizer $authorizer): void
    {
        WebGuards::requireAuthenticatedPermission(
            $openTestAccess,
            $session,
            $authorizer->hasPermission('biblioteca.self'),
            'Acesso restrito a obreiros autenticados.'
        );
    }

    public static function requireBibliotecaManageAccess(bool $openTestAccess, array $session, Authorizer $authorizer): void
    {
        self::requireBibliotecaAccess($openTestAccess, $session, $authorizer);
        WebGuards::requirePermission(
            $authorizer->hasPermission('biblioteca.manage'),
            'Acesso restrito ao Bibliotecario, Veneravel Mestre ou Administrador.'
        );
    }

    public static function requireSecretariaAccess(bool $openTestAccess, array $session, Authorizer $authorizer): void
    {
        WebGuards::requireAuthenticatedPermission(
            $openTestAccess,
            $session,
            $authorizer->hasPermission('secretaria.manage'),
            'Acesso restrito ao Secretario, Veneravel Mestre ou Administrador.'
        );
    }

    public static function requireObreirosViewAccess(bool $openTestAccess, array $session, Authorizer $authorizer): void
    {
        WebGuards::requireAuthenticatedPermission(
            $openTestAccess,
            $session,
            $authorizer->hasPermission('obreiros.view'),
            'Acesso restrito a Secretaria, 1o Vigilante, 2o Vigilante, Chancelaria, Veneravel Mestre ou Administrador.'
        );
    }

    public static function requireObreirosManageAccess(bool $openTestAccess, array $session, Authorizer $authorizer): void
    {
        WebGuards::requireAuthenticatedPermission(
            $openTestAccess,
            $session,
            $authorizer->hasPermission('obreiros.manage'),
            'Acesso restrito ao Secretario ou Administrador.'
        );
    }

    public static function requireAssistenciaAccess(bool $openTestAccess, array $session, Authorizer $authorizer): void
    {
        WebGuards::requireAuthenticatedPermission(
            $openTestAccess,
            $session,
            $authorizer->hasPermission('hospitaleiro.manage'),
            'Acesso restrito ao Mestre Hospitaleiro, Secretario, Tesoureiro, Veneravel Mestre ou Administrador.'
        );
    }
}
