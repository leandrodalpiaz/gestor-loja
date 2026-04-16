<?php

namespace App\Core\Http;

use App\Controllers\BibliotecaController;
use App\Core\Authorization\Authorizer;

class BibliotecaRoutes
{
    public static function dispatch(
        string $requestUri,
        string $method,
        bool $openTestAccess,
        array $session,
        Authorizer $authorizer
    ): bool {
        $controller = new BibliotecaController();

        switch ($requestUri) {
            case '/biblioteca':
                ModuleGuards::requireBibliotecaAccess($openTestAccess, $session, $authorizer);
                $controller->index();
                return true;

            case '/biblioteca/detalhes':
                ModuleGuards::requireBibliotecaAccess($openTestAccess, $session, $authorizer);
                $controller->detalhes((int) ($_GET['id'] ?? 0));
                return true;

            case '/biblioteca/meus-emprestimos':
                ModuleGuards::requireBibliotecaAccess($openTestAccess, $session, $authorizer);
                $controller->meusEmprestimos(trim((string) ($session['usuario_id'] ?? '')));
                return true;

            case '/biblioteca/solicitar':
                ModuleGuards::requireBibliotecaAccess($openTestAccess, $session, $authorizer);
                $controller->solicitar(
                    (int) ($_POST['acervo_id'] ?? 0),
                    trim((string) ($session['usuario_id'] ?? ''))
                );
                return true;

            case '/biblioteca/comentar':
                ModuleGuards::requireBibliotecaAccess($openTestAccess, $session, $authorizer);
                $controller->comentar(
                    (int) ($_POST['acervo_id'] ?? 0),
                    trim((string) ($session['usuario_id'] ?? ''))
                );
                return true;

            case '/biblioteca/reagir':
                ModuleGuards::requireBibliotecaAccess($openTestAccess, $session, $authorizer);
                $controller->reagir(
                    (int) ($_POST['acervo_id'] ?? 0),
                    trim((string) ($session['usuario_id'] ?? ''))
                );
                return true;

            case '/biblioteca/adicionar':
                ModuleGuards::requireBibliotecaManageAccess($openTestAccess, $session, $authorizer);
                $controller->adicionar();
                return true;

            case '/biblioteca/editar':
                ModuleGuards::requireBibliotecaManageAccess($openTestAccess, $session, $authorizer);
                $id = $method === 'POST'
                    ? (int) ($_POST['id'] ?? 0)
                    : (int) ($_GET['id'] ?? 0);
                $controller->editar($id);
                return true;

            case '/biblioteca/excluir':
                ModuleGuards::requireBibliotecaManageAccess($openTestAccess, $session, $authorizer);
                $controller->excluir((int) ($_POST['id'] ?? 0));
                return true;

            case '/biblioteca/emprestimos':
                ModuleGuards::requireBibliotecaManageAccess($openTestAccess, $session, $authorizer);
                $controller->emprestimos();
                return true;

            case '/biblioteca/devolver':
                ModuleGuards::requireBibliotecaManageAccess($openTestAccess, $session, $authorizer);
                $controller->devolver((int) ($_POST['id'] ?? 0));
                return true;

            default:
                return false;
        }
    }
}
