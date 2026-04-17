<?php

namespace App\Core\Http;

use App\Controllers\BibliotecaController;
use App\Controllers\PrimeiroVigilanteController;
use App\Controllers\SegundoVigilanteController;

class VigilanciaRoutes
{
    public static function dispatch(
        string $requestUri,
        bool $openTestAccess,
        array $session,
        callable $sessionHasPermission
    ): bool {
        switch ($requestUri) {
            case '/primeiro-vigilante':
                WebGuards::requireLogin($openTestAccess, $session);
                WebGuards::requirePermission(
                    $sessionHasPermission('vigilancia.primeiro.manage'),
                    'Acesso restrito ao 1o Vigilante, Veneravel Mestre ou Administrador.'
                );
                (new PrimeiroVigilanteController())->index();
                return true;

            case '/primeiro-vigilante/aprendiz':
                WebGuards::requireLogin($openTestAccess, $session);
                $aprendizId = trim((string) ($_GET['id'] ?? ''));
                $usuarioId = trim((string) ($session['usuario_id'] ?? ''));
                $podeVerQualquerAprendiz = $sessionHasPermission('vigilancia.primeiro.manage');
                $podeVerProprio = $usuarioId !== '' && $usuarioId === $aprendizId;
                if (!$podeVerQualquerAprendiz && !$podeVerProprio) {
                    WebGuards::forbidHtml('Acesso restrito ao 1o Vigilante, Veneravel Mestre, Administrador ou ao proprio Aprendiz.');
                }
                (new PrimeiroVigilanteController())->aprendiz($aprendizId, !$podeVerQualquerAprendiz && $podeVerProprio);
                return true;

            case '/primeiro-vigilante/trilha/atualizar':
                WebGuards::requireLogin($openTestAccess, $session);
                WebGuards::requirePermission($sessionHasPermission('vigilancia.primeiro.manage'), 'Acesso restrito ao 1o Vigilante, Veneravel Mestre ou Administrador.');
                (new PrimeiroVigilanteController())->atualizarEtapa();
                return true;

            case '/primeiro-vigilante/trilha/acao-rapida':
                WebGuards::requireLogin($openTestAccess, $session);
                WebGuards::requirePermission($sessionHasPermission('vigilancia.primeiro.manage'), 'Acesso restrito ao 1o Vigilante, Veneravel Mestre ou Administrador.');
                (new PrimeiroVigilanteController())->acaoRapidaEtapa();
                return true;

            case '/primeiro-vigilante/leitura/salvar':
                WebGuards::requireLogin($openTestAccess, $session);
                WebGuards::requirePermission($sessionHasPermission('vigilancia.primeiro.manage'), 'Acesso restrito ao 1o Vigilante, Veneravel Mestre ou Administrador.');
                (new PrimeiroVigilanteController())->salvarLeituraSugerida();
                return true;

            case '/primeiro-vigilante/certificado/solicitar':
                WebGuards::requireLogin($openTestAccess, $session);
                WebGuards::requirePermission($sessionHasPermission('vigilancia.primeiro.manage'), 'Acesso restrito ao 1o Vigilante, Veneravel Mestre ou Administrador.');
                (new PrimeiroVigilanteController())->solicitarCertificado();
                return true;

            case '/segundo-vigilante':
                WebGuards::requireLogin($openTestAccess, $session);
                WebGuards::requirePermission(
                    $sessionHasPermission('vigilancia.segundo.manage'),
                    'Acesso restrito ao 2o Vigilante, Veneravel Mestre ou Administrador.'
                );
                (new SegundoVigilanteController())->index();
                return true;

            case '/segundo-vigilante/companheiro':
                WebGuards::requireLogin($openTestAccess, $session);
                $companheiroId = trim((string) ($_GET['id'] ?? ''));
                $usuarioId = trim((string) ($session['usuario_id'] ?? ''));
                $podeVerQualquerCompanheiro = $sessionHasPermission('vigilancia.segundo.manage');
                $podeVerProprio = $usuarioId !== '' && $usuarioId === $companheiroId;
                if (!$podeVerQualquerCompanheiro && !$podeVerProprio) {
                    WebGuards::forbidHtml('Acesso restrito ao 2o Vigilante, Veneravel Mestre, Administrador ou ao proprio Companheiro.');
                }
                (new SegundoVigilanteController())->companheiro($companheiroId, !$podeVerQualquerCompanheiro && $podeVerProprio);
                return true;

            case '/segundo-vigilante/trilha/atualizar':
                WebGuards::requireLogin($openTestAccess, $session);
                WebGuards::requirePermission($sessionHasPermission('vigilancia.segundo.manage'), 'Acesso restrito ao 2o Vigilante, Veneravel Mestre ou Administrador.');
                (new SegundoVigilanteController())->atualizarEtapa();
                return true;

            case '/segundo-vigilante/trilha/acao-rapida':
                WebGuards::requireLogin($openTestAccess, $session);
                WebGuards::requirePermission($sessionHasPermission('vigilancia.segundo.manage'), 'Acesso restrito ao 2o Vigilante, Veneravel Mestre ou Administrador.');
                (new SegundoVigilanteController())->acaoRapidaEtapa();
                return true;

            case '/segundo-vigilante/leitura/salvar':
                WebGuards::requireLogin($openTestAccess, $session);
                WebGuards::requirePermission($sessionHasPermission('vigilancia.segundo.manage'), 'Acesso restrito ao 2o Vigilante, Veneravel Mestre ou Administrador.');
                (new SegundoVigilanteController())->salvarLeituraSugerida();
                return true;

            case '/segundo-vigilante/certificado/solicitar':
                WebGuards::requireLogin($openTestAccess, $session);
                WebGuards::requirePermission($sessionHasPermission('vigilancia.segundo.manage'), 'Acesso restrito ao 2o Vigilante, Veneravel Mestre ou Administrador.');
                (new SegundoVigilanteController())->solicitarCertificado();
                return true;

            case '/segundo-vigilante/exaltacao/recomendar':
                WebGuards::requireLogin($openTestAccess, $session);
                WebGuards::requirePermission($sessionHasPermission('vigilancia.segundo.manage'), 'Acesso restrito ao 2o Vigilante, Veneravel Mestre ou Administrador.');
                (new SegundoVigilanteController())->recomendarExaltacao();
                return true;

            case '/meu-aprendizado':
                WebGuards::requireLogin($openTestAccess, $session);
                $usuarioId = trim((string) ($session['usuario_id'] ?? ''));
                if ($usuarioId === '') {
                    WebGuards::forbidHtml('Nao foi possivel identificar o Aprendiz logado.');
                }
                (new PrimeiroVigilanteController())->aprendiz($usuarioId, true);
                return true;

            case '/meu-companheirismo':
                WebGuards::requireLogin($openTestAccess, $session);
                $usuarioId = trim((string) ($session['usuario_id'] ?? ''));
                if ($usuarioId === '') {
                    WebGuards::forbidHtml('Nao foi possivel identificar o Companheiro logado.');
                }
                (new SegundoVigilanteController())->companheiro($usuarioId, true);
                return true;

            case '/biblioteca/classificar':
                WebGuards::requireLogin($openTestAccess, $session);
                WebGuards::requirePermission($sessionHasPermission('biblioteca.classificar'), 'Acesso restrito para classificar leitura sugerida.');
                (new BibliotecaController())->classificar();
                return true;

            default:
                return false;
        }
    }

}
