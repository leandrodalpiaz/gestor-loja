<?php

namespace App\Core\Http;

use App\Core\Authorization\Authorizer;
use App\Models\Cargo;
use App\Models\Obreiro;

class ObreirosRoutes
{
    public static function dispatch(
        string $requestUri,
        string $method,
        bool $openTestAccess,
        array $session,
        Authorizer $authorizer
    ): bool {
        switch ($requestUri) {
            case '/obreiros':
                ModuleGuards::requireObreirosViewAccess($openTestAccess, $session, $authorizer);
                $obreiroModel = new Obreiro();
                $filtrosObreiros = [
                    'busca' => trim((string) ($_GET['busca'] ?? '')),
                    'situacao' => trim((string) ($_GET['situacao'] ?? '')),
                    'grau' => trim((string) ($_GET['grau'] ?? '')),
                    'alerta' => trim((string) ($_GET['alerta'] ?? $_GET['pendencia'] ?? '')),
                    'cargo_codigo' => trim((string) ($_GET['cargo_codigo'] ?? '')),
                    'ordenacao' => trim((string) ($_GET['ordenacao'] ?? 'nome')),
                ];
                $obreiros = $obreiroModel->listarParaSecretaria($filtrosObreiros);
                $resumoObreiros = $obreiroModel->obterResumoSecretaria($filtrosObreiros);
                $cargosFiltros = (new Cargo())->listarResumoCargos();
                $podeGerenciarObreiros = $authorizer->hasPermission('obreiros.manage');
                $podeGerarConvitesAcesso = $authorizer->hasPermission('access.manage');
                require __DIR__ . '/../../Views/obreiros.php';
                return true;

            case '/obreiros/novo':
                ModuleGuards::requireObreirosManageAccess($openTestAccess, $session, $authorizer);
                require __DIR__ . '/../../Views/obreiro_form.php';
                return true;

            case '/obreiros/salvar':
                ModuleGuards::requireObreirosManageAccess($openTestAccess, $session, $authorizer);
                if ($method !== 'POST') {
                    header('Location: /obreiros/novo');
                    exit;
                }

                $obreiroModel = new Obreiro();
                $ok = $obreiroModel->create($_POST);
                if ($ok) {
                    header('Location: /obreiros?sucesso=1');
                } else {
                    header('Location: /obreiros/novo?erro=1');
                }
                exit;

            case '/obreiros/editar':
                ModuleGuards::requireObreirosManageAccess($openTestAccess, $session, $authorizer);
                $id = (string) ($_GET['id'] ?? '');
                if ($id === '') {
                    header('Location: /obreiros');
                    exit;
                }

                $obreiroModel = new Obreiro();
                $obreiro = $obreiroModel->findById($id);
                if (!$obreiro) {
                    http_response_code(404);
                    echo 'Obreiro não encontrado.';
                    exit;
                }

                require __DIR__ . '/../../Views/obreiro_editar.php';
                return true;

            case '/obreiros/atualizar':
                ModuleGuards::requireObreirosManageAccess($openTestAccess, $session, $authorizer);
                if ($method !== 'POST') {
                    header('Location: /obreiros');
                    exit;
                }

                $obreiroId = (string) ($_POST['id'] ?? '');
                if ($obreiroId === '') {
                    header('Location: /obreiros?erro=1');
                    exit;
                }

                $obreiroModel = new Obreiro();
                $ok = $obreiroModel->update($_POST);
                if ($ok) {
                    header('Location: /obreiros/editar?id=' . urlencode($obreiroId) . '&sucesso=1');
                } else {
                    header('Location: /obreiros/editar?id=' . urlencode($obreiroId) . '&erro=1');
                }
                exit;

            case '/obreiros/inativar':
                ModuleGuards::requireObreirosManageAccess($openTestAccess, $session, $authorizer);
                if ($method !== 'POST') {
                    header('Location: /obreiros');
                    exit;
                }

                $obreiroId = (string) ($_POST['id'] ?? '');
                if ($obreiroId === '') {
                    header('Location: /obreiros?erro=1');
                    exit;
                }

                $obreiroModel = new Obreiro();
                $ok = $obreiroModel->inativarPorId($obreiroId);
                header('Location: /obreiros?' . ($ok ? 'sucesso=1' : 'erro=1'));
                exit;

            case '/obreiros/excluir':
                ModuleGuards::requireObreirosManageAccess($openTestAccess, $session, $authorizer);
                if ($method !== 'POST') {
                    header('Location: /obreiros');
                    exit;
                }

                $obreiroId = (string) ($_POST['id'] ?? '');
                if ($obreiroId === '') {
                    header('Location: /obreiros?erro=1');
                    exit;
                }

                $obreiroModel = new Obreiro();
                $ok = $obreiroModel->excluirPorId($obreiroId);
                header('Location: /obreiros?' . ($ok ? 'sucesso=1' : 'erro=1'));
                exit;

            default:
                return false;
        }
    }
}
