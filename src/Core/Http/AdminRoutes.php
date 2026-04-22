<?php

namespace App\Core\Http;

use App\Controllers\AdminController;
use App\Core\Authorization\Authorizer;

class AdminRoutes
{
    public static function dispatch(string $requestUri, bool $openTestAccess, array $session, Authorizer $authorizer): bool
    {
        $controller = new AdminController();

        switch ($requestUri) {
            case '/admin/acessos':
                ModuleGuards::requireAccessManage($openTestAccess, $session, $authorizer);
                $controller->acessosPendentes();
                return true;

            case '/admin/acessos/atualizar':
                ModuleGuards::requireAccessManage($openTestAccess, $session, $authorizer);
                $controller->atualizarAcesso();
                return true;

            case '/admin/conteudo-publico':
                ModuleGuards::requirePublicContentManage($openTestAccess, $session, $authorizer);
                $controller->conteudoPublico();
                return true;

            case '/admin/conteudo-publico/salvar':
                ModuleGuards::requirePublicContentManage($openTestAccess, $session, $authorizer);
                $controller->salvarConteudoPublico();
                return true;

            case '/admin/conteudo-publico/excluir':
                ModuleGuards::requirePublicContentManage($openTestAccess, $session, $authorizer);
                $controller->excluirConteudoPublico();
                return true;

            case '/admin/cargos':
                ModuleGuards::requireAdminCargosView($openTestAccess, $session, $authorizer);
                $controller->listarCargos();
                return true;

            case '/admin/cargos/salvar':
                ModuleGuards::requireAdminCargosManage($openTestAccess, $session, $authorizer);
                $controller->salvarCargo();
                return true;

            case '/admin/cargos/gestao/salvar':
                ModuleGuards::requireAdminCargosManage($openTestAccess, $session, $authorizer);
                $controller->salvarGestao();
                return true;

            case '/admin/cargos/gestao/encerrar':
                ModuleGuards::requireAdminCargosManage($openTestAccess, $session, $authorizer);
                $controller->encerrarGestao();
                return true;

            case '/admin/loja':
                ModuleGuards::requireAdminLojaView($openTestAccess, $session, $authorizer);
                $controller->configuracoesLoja();
                return true;

            case '/admin/auditoria':
                ModuleGuards::requireAdminAuditoriaView($openTestAccess, $session, $authorizer);
                $controller->auditoriaCritica();
                return true;

            case '/admin/loja/salvar':
                ModuleGuards::requireAdminLojaManage($openTestAccess, $session, $authorizer);
                $controller->salvarConfiguracoesLoja();
                return true;

            case '/admin/convites':
                ModuleGuards::requireAccessManage($openTestAccess, $session, $authorizer);
                $controller->convitesAcesso();
                return true;

            case '/admin/convites/gerar':
                ModuleGuards::requireAccessManage($openTestAccess, $session, $authorizer);
                $controller->gerarConviteAcesso();
                return true;

            default:
                return false;
        }
    }
}
