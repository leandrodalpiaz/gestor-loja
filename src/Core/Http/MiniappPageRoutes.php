<?php

namespace App\Core\Http;

class MiniappPageRoutes
{
    public static function dispatch(string $requestUri): bool
    {
        switch ($requestUri) {
            case '/miniapp/admin':
                requireMiniappAuth(['admin', 'veneravel'], 'admin.cargos.view');
                require __DIR__ . '/../../Views/miniapp/admin.php';
                return true;

            case '/miniapp/aniversario':
                require __DIR__ . '/../../Views/miniapp/aniversario.php';
                return true;

            case '/miniapp/efemerides':
                require __DIR__ . '/../../Views/miniapp/efemerides.php';
                return true;

            case '/miniapp/data-maconica':
                require __DIR__ . '/../../Views/miniapp/data-maconica.php';
                return true;

            case '/miniapp/historico':
                require __DIR__ . '/../../Views/miniapp/historico.php';
                return true;

            case '/miniapp/palavra-dia':
                require __DIR__ . '/../../Views/miniapp/palavra_dia.php';
                return true;

            case '/miniapp/fallback':
                require __DIR__ . '/../../Views/miniapp/fallback.php';
                return true;

            case '/miniapp/aprendizado':
                require __DIR__ . '/../../Views/miniapp/aprendizado.php';
                return true;

            case '/miniapp/primeiro-vigilante':
                require __DIR__ . '/../../Views/miniapp/primeiro_vigilante.php';
                return true;

            case '/miniapp/companheirismo':
                require __DIR__ . '/../../Views/miniapp/companheirismo.php';
                return true;

            case '/miniapp/segundo-vigilante':
                require __DIR__ . '/../../Views/miniapp/segundo_vigilante.php';
                return true;

            case '/miniapp/secretaria':
                requireMiniappAuth(['secretario', 'veneravel', 'admin'], 'secretaria.manage');
                require __DIR__ . '/../../Views/miniapp/secretaria.php';
                return true;

            case '/miniapp/hospitaleiro':
                requireMiniappAuth(['hospitaleiro', 'secretario', 'tesoureiro', 'veneravel', 'admin'], 'hospitaleiro.manage');
                require __DIR__ . '/../../Views/miniapp/hospitaleiro.php';
                return true;

            case '/miniapp/assistente':
                requireMiniappAuth([
                    'admin',
                    'veneravel',
                    'secretario',
                    'tesoureiro',
                    'chanceler',
                    'orador',
                    'hospitaleiro',
                    'mestre_banquetes',
                    'mestre_harmonia',
                    'primeiro_vigilante',
                    'segundo_vigilante',
                    'bibliotecario',
                ], 'dashboard.view');
                require __DIR__ . '/../../Views/miniapp/assistente.php';
                return true;

            case '/miniapp/chanceler':
                requireMiniappAuth(['chanceler', 'veneravel', 'admin'], 'chancelaria.manage');
                require __DIR__ . '/../../Views/miniapp/chanceler.php';
                return true;

            case '/miniapp/mestre-banquetes':
                requireMiniappAuth(['mestre_banquetes', 'veneravel', 'admin'], 'mestre_banquetes.manage');
                require __DIR__ . '/../../Views/miniapp/mestre_banquetes.php';
                return true;

            case '/miniapp/veneravel':
                requireMiniappAuth(['veneravel', 'admin'], 'veneravel.manage');
                require __DIR__ . '/../../Views/miniapp/veneravel.php';
                return true;

            case '/miniapp/biblioteca':
                requireMiniappAuth(
                    ['bibliotecario', 'primeiro_vigilante', 'segundo_vigilante', 'veneravel', 'admin'],
                    'biblioteca.self'
                );
                require __DIR__ . '/../../Views/miniapp/biblioteca.php';
                return true;

            case '/biblioteca/novo':
                require __DIR__ . '/../../../public/tg/novo.php';
                return true;

            case '/biblioteca/scanner':
                require __DIR__ . '/../../../public/tg/scanner.php';
                return true;

            default:
                return false;
        }
    }
}
