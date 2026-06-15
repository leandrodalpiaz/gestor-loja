<?php

namespace App\Core\Http;

use App\Controllers\AdminController;
use App\Models\ConteudoPublico;
use App\Models\ConviteAcesso;
use App\Models\Obreiro;
use App\Models\RelatorioSecretariaAnual;

class AdminApiRoutes
{
    public static function dispatch(
        string $requestUri,
        string $method,
        callable $requireAdminApiAccess
    ): bool {
        if (!str_starts_with($requestUri, '/api/admin')) {
            return false;
        }

        header('Content-Type: application/json; charset=utf-8');

        $controller = new AdminController();

        if ($requestUri === '/api/admin/cargos' && $method === 'GET') {
            $requireAdminApiAccess('admin.cargos.view');
            JsonResponse::send(['ok' => true, 'dados' => $controller->montarPayloadMiniapp()]);
            return true;
        }

        if ($requestUri === '/api/admin/cargos/gestao/abrir' && $method === 'POST') {
            $requireAdminApiAccess('admin.cargos.manage');
            $body = RequestBody::json();
            JsonResponse::send($controller->abrirGestaoMiniapp(
                (string) ($body['titulo'] ?? ''),
                (string) ($body['inicio_em'] ?? ''),
                (string) ($body['observacao'] ?? '')
            ));
            return true;
        }

        if ($requestUri === '/api/admin/cargos/gestao/encerrar' && $method === 'POST') {
            $requireAdminApiAccess('admin.cargos.manage');
            $body = RequestBody::json();
            JsonResponse::send($controller->encerrarGestaoMiniapp(
                (int) ($body['gestao_id'] ?? 0),
                (string) ($body['encerrada_em'] ?? '')
            ));
            return true;
        }

        if ($requestUri === '/api/admin/cargos/atribuir' && $method === 'POST') {
            $requireAdminApiAccess('admin.cargos.manage');
            $body = RequestBody::json();
            JsonResponse::send($controller->atribuirCargoMiniapp(
                (string) ($body['cargo_codigo'] ?? ''),
                (string) ($body['obreiro_id'] ?? ''),
                isset($body['gestao_id']) ? (int) $body['gestao_id'] : null,
                (string) ($body['inicio_em'] ?? ''),
                (string) ($body['observacao'] ?? '')
            ));
            return true;
        }

        if ($requestUri === '/api/admin/convites' && $method === 'GET') {
            $requireAdminApiAccess('access.manage');
            $obreiroModel = new Obreiro();
            $conviteModel = new ConviteAcesso();
            JsonResponse::send([
                'ok' => true,
                'pendentes' => $obreiroModel->listarPendentesAcesso(),
                'convites' => $conviteModel->listarRecentes(40),
            ]);
            return true;
        }

        if ($requestUri === '/api/admin/convites/gerar' && $method === 'POST') {
            $requireAdminApiAccess('access.manage');
            $body = RequestBody::json();
            $resultado = (new ConviteAcesso())->gerarParaObreiro((string) ($body['obreiro_id'] ?? ''));
            JsonResponse::send($resultado);
            return true;
        }

        if ($requestUri === '/api/admin/acessos' && $method === 'GET') {
            $requireAdminApiAccess('access.manage');
            JsonResponse::send([
                'ok' => true,
                'itens' => (new Obreiro())->listarPendentesAcesso(),
            ]);
            return true;
        }

        if ($requestUri === '/api/admin/acessos/atualizar' && $method === 'POST') {
            $requireAdminApiAccess('access.manage');
            $body = RequestBody::json();
            $id = trim((string) ($body['id'] ?? ''));
            $status = trim((string) ($body['status'] ?? ''));

            if ($id === '' || $status === '') {
                JsonResponse::send(['ok' => false, 'erro' => 'Informe o obreiro e o status desejado.']);
                return true;
            }

            $ok = (new Obreiro())->atualizarAcessoStatus($id, $status);
            JsonResponse::send([
                'ok' => $ok,
                'erro' => $ok ? null : 'Não foi possível atualizar o status de acesso.',
            ]);
            return true;
        }

        if ($requestUri === '/api/admin/conteudo-publico' && $method === 'GET') {
            $requireAdminApiAccess('public_content.manage');
            JsonResponse::send([
                'ok' => true,
                'itens' => (new ConteudoPublico())->listarParaAdmin(),
                'tipos' => ConteudoPublico::TIPOS,
            ]);
            return true;
        }

        if ($requestUri === '/api/admin/conteudo-publico/salvar' && $method === 'POST') {
            $requireAdminApiAccess('public_content.manage');
            $body = RequestBody::json();
            try {
                $id = (new ConteudoPublico())->salvar($body);
                JsonResponse::send(['ok' => true, 'id' => $id]);
            } catch (\Throwable $e) {
                JsonResponse::send(['ok' => false, 'erro' => 'Não foi possível salvar o conteúdo. Revise os campos informados.']);
            }
            return true;
        }

        if ($requestUri === '/api/admin/conteudo-publico/excluir' && $method === 'POST') {
            $requireAdminApiAccess('public_content.manage');
            $body = RequestBody::json();
            try {
                (new ConteudoPublico())->excluir((int) ($body['id'] ?? 0));
                JsonResponse::send(['ok' => true]);
            } catch (\Throwable $e) {
                JsonResponse::send(['ok' => false, 'erro' => 'Não foi possível remover o conteúdo.']);
            }
            return true;
        }

        if ($requestUri === '/api/admin/secretaria/relatorio-anual' && $method === 'GET') {
            $requireAdminApiAccess('secretaria.manage');
            $ano = (int) ($_GET['ano'] ?? date('Y'));
            if ($ano < 2000 || $ano > 2100) {
                $ano = (int) date('Y');
            }

            $relatorio = (new RelatorioSecretariaAnual())->montar($ano);
            $anosDisponiveis = [];
            $anoAtual = (int) date('Y');
            for ($i = $anoAtual; $i >= max(2000, $anoAtual - 8); $i--) {
                $anosDisponiveis[] = $i;
            }

            JsonResponse::send([
                'ok' => true,
                'relatorio' => $relatorio,
                'anos_disponiveis' => $anosDisponiveis,
            ]);
            return true;
        }

        return false;
    }
}
