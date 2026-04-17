<?php

namespace App\Core\Http;

use App\Core\Authorization\PermissionMap;
use App\Models\Balaustre;
use App\Models\EfemerideRegistro;
use App\Models\HistoriaMaconica;
use App\Models\MensagemComplementar;
use App\Models\PalavraDia;
use App\Models\Sessao;

class MiniappApiRoutes
{
    public static function dispatch(
        string $requestUri,
        string $method,
        array $session,
        callable $sessionHasRole,
        callable $sessionHasPermission,
        callable $resolveObreiroByInitData,
        callable $normalizeRole,
        PermissionMap $permissionMap,
        callable $contentPermissionService
    ): bool {
        if (!preg_match('~^/api/miniapp~', $requestUri)) {
            return false;
        }

        header('Content-Type: application/json; charset=utf-8');

        $body = RequestBody::json();
        $initData = trim((string) ($body['initData'] ?? $body['init_data'] ?? $_GET['initData'] ?? $_GET['init_data'] ?? ''));
        $miniappObreiro = null;
        $miniappAllowedRoles = match (true) {
            str_starts_with($requestUri, '/api/miniapp/historico') => $contentPermissionService()->getAllowedRoles('historia'),
            str_starts_with($requestUri, '/api/miniapp/palavra-dia') => $contentPermissionService()->getAllowedRoles('palavra_dia'),
            str_starts_with($requestUri, '/api/miniapp/efemerides') => $contentPermissionService()->getAllowedRoles('efemerides'),
            str_starts_with($requestUri, '/api/miniapp/secretaria') => ['secretario', 'veneravel', 'admin'],
            str_starts_with($requestUri, '/api/miniapp/aprendizado') => ['primeiro_vigilante', 'veneravel', 'admin'],
            str_starts_with($requestUri, '/api/miniapp/primeiro-vigilante') => ['primeiro_vigilante', 'veneravel', 'admin'],
            str_starts_with($requestUri, '/api/miniapp/companheirismo') => ['segundo_vigilante', 'veneravel', 'admin'],
            str_starts_with($requestUri, '/api/miniapp/segundo-vigilante') => ['segundo_vigilante', 'veneravel', 'admin'],
            str_starts_with($requestUri, '/api/miniapp/mestre-banquetes') => ['mestre_banquetes', 'veneravel', 'admin'],
            str_starts_with($requestUri, '/api/miniapp/mestre-harmonia') => ['mestre_harmonia', 'veneravel', 'admin'],
            str_starts_with($requestUri, '/api/miniapp/tesouraria') => ['tesoureiro', 'veneravel', 'admin'],
            str_starts_with($requestUri, '/api/miniapp/biblioteca') => ['bibliotecario', 'primeiro_vigilante', 'segundo_vigilante', 'veneravel', 'admin'],
            str_starts_with($requestUri, '/api/miniapp/admin') => ['admin', 'veneravel'],
            str_starts_with($requestUri, '/api/miniapp/orador') => ['orador', 'veneravel', 'admin'],
            str_starts_with($requestUri, '/api/miniapp/veneravel') => ['veneravel', 'admin'],
            str_starts_with($requestUri, '/api/miniapp/hospitaleiro') => ['hospitaleiro', 'secretario', 'tesoureiro', 'veneravel', 'admin'],
            default => ['chanceler', 'veneravel', 'admin'],
        };

        $miniappRequiredPermission = match (true) {
            str_starts_with($requestUri, '/api/miniapp/secretaria') => 'secretaria.manage',
            str_starts_with($requestUri, '/api/miniapp/primeiro-vigilante') => 'vigilancia.primeiro.manage',
            str_starts_with($requestUri, '/api/miniapp/segundo-vigilante') => 'vigilancia.segundo.manage',
            str_starts_with($requestUri, '/api/miniapp/tesouraria') => 'tesouraria.manage',
            str_starts_with($requestUri, '/api/miniapp/biblioteca') => 'biblioteca.self',
            str_starts_with($requestUri, '/api/miniapp/admin') => 'admin.cargos.view',
            str_starts_with($requestUri, '/api/miniapp/chanceler') => 'chancelaria.manage',
            str_starts_with($requestUri, '/api/miniapp/mestre-banquetes') => 'mestre_banquetes.manage',
            str_starts_with($requestUri, '/api/miniapp/mestre-harmonia') => 'mestre_harmonia.manage',
            str_starts_with($requestUri, '/api/miniapp/orador') => 'orador.view',
            str_starts_with($requestUri, '/api/miniapp/veneravel') => 'veneravel.manage',
            str_starts_with($requestUri, '/api/miniapp/hospitaleiro') => 'hospitaleiro.manage',
            default => null,
        };

        $authorizedBySession = isset($session['usuario_logado']) && (
            $sessionHasRole(...$miniappAllowedRoles)
            || ($miniappRequiredPermission !== null && $sessionHasPermission($miniappRequiredPermission))
        );

        if ($authorizedBySession) {
            $miniappObreiro = $session['usuario_logado'];
        } else {
            $miniappObreiro = $resolveObreiroByInitData($initData);
            if (!$miniappObreiro) {
                JsonResponse::error('Nao autenticado no miniapp.', 401);
            }

            $roles = array_values(array_unique(array_filter(array_map(
                $normalizeRole,
                $miniappObreiro['cargos'] ?? [$miniappObreiro['cargo_principal'] ?? $miniappObreiro['cargo'] ?? '']
            ))));
            $temPermissaoMiniapp = false;
            foreach ($miniappAllowedRoles as $allowedRole) {
                if (in_array($allowedRole, $roles, true)) {
                    $temPermissaoMiniapp = true;
                    break;
                }
            }
            if (!$temPermissaoMiniapp && $miniappRequiredPermission !== null) {
                $miniappPermissions = $permissionMap->permissionsForRoles($roles);
                $temPermissaoMiniapp = in_array('*', $miniappPermissions, true) || in_array($miniappRequiredPermission, $miniappPermissions, true);
            }
            if (!$temPermissaoMiniapp) {
                JsonResponse::error('Acesso restrito para este miniapp.', 403);
            }
        }

        $efemerideModel = new EfemerideRegistro();
        $historiaModel = new HistoriaMaconica();
        $palavraDiaModel = new PalavraDia();
        $mensagensModel = new MensagemComplementar();

        if ($requestUri === '/api/miniapp/efemeride/salvar' && $method === 'POST') {
            $id = (int) ($body['id'] ?? 0);
            $nome = trim((string) ($body['nome'] ?? ''));
            $tipo = trim((string) ($body['tipo'] ?? ''));
            $dataEvento = trim((string) ($body['data_evento'] ?? ''));
            $dataValida = \DateTimeImmutable::createFromFormat('Y-m-d', $dataEvento) !== false;
            if ($nome === '' || $tipo === '' || $dataEvento === '' || !$dataValida) {
                JsonResponse::send(['ok' => false, 'erro' => 'Dados invalidos para salvar efemeride.']);
            }

            if ($id > 0) {
                $ok = $efemerideModel->atualizar($id, $body);
            } else {
                $createdBy = (int) ($miniappObreiro['id'] ?? ($session['usuario_id'] ?? 0));
                $ok = $efemerideModel->create($body, $createdBy > 0 ? $createdBy : null);
            }

            JsonResponse::send(['ok' => $ok]);
        }

        if ($requestUri === '/api/miniapp/efemeride/desativar' && $method === 'POST') {
            $id = (int) ($body['id'] ?? 0);
            JsonResponse::send(['ok' => $id > 0 ? $efemerideModel->desativar($id) : false]);
        }

        if ($requestUri === '/api/miniapp/efemerides/listar' && $method === 'GET') {
            JsonResponse::send(['ok' => true, 'registros' => $efemerideModel->buscarComFiltros(['ativo' => 'all'], 300)]);
        }

        if ($requestUri === '/api/miniapp/efemerides/excluir' && $method === 'POST') {
            $id = (int) ($body['id'] ?? 0);
            JsonResponse::send(['ok' => $id > 0 ? $efemerideModel->excluir($id) : false]);
        }

        if ($requestUri === '/api/miniapp/historico/listar' && $method === 'GET') {
            $registros = $efemerideModel->buscarComFiltros(['tipo' => 'HistÃ³ria', 'ativo' => 'all'], 300);
            if ($registros === []) {
                $registros = $efemerideModel->buscarComFiltros(['tipo' => 'Historia', 'ativo' => 'all'], 300);
            }
            JsonResponse::send(['ok' => true, 'registros' => $registros]);
        }

        if ($requestUri === '/api/miniapp/fallback/listar' && $method === 'GET') {
            JsonResponse::send(['ok' => true, 'mensagens' => $mensagensModel->listarPorTipo('fallback')]);
        }

        if ($requestUri === '/api/miniapp/fallback/salvar' && $method === 'POST') {
            $id = (int) ($body['id'] ?? 0);
            $mensagem = trim((string) ($body['mensagem'] ?? ''));
            if ($mensagem === '') {
                JsonResponse::send(['ok' => false, 'erro' => 'Mensagem vazia.']);
            }

            $ok = $id > 0 ? $mensagensModel->atualizar($id, $mensagem) : $mensagensModel->criar('fallback', $mensagem);
            JsonResponse::send(['ok' => $ok]);
        }

        if ($requestUri === '/api/miniapp/fallback/toggle' && $method === 'POST') {
            $id = (int) ($body['id'] ?? 0);
            JsonResponse::send(['ok' => $id > 0 ? $mensagensModel->toggleAtivo($id) : false]);
        }

        if ($requestUri === '/api/miniapp/fallback/excluir' && $method === 'POST') {
            $id = (int) ($body['id'] ?? 0);
            JsonResponse::send(['ok' => $id > 0 ? $mensagensModel->excluir($id) : false]);
        }

        if ($requestUri === '/api/miniapp/aprendizado' && $method === 'GET') {
            $roles = array_values(array_unique(array_map(
                static fn ($role) => strtolower((string) $role),
                $miniappObreiro['cargos'] ?? [$miniappObreiro['cargo_principal'] ?? $miniappObreiro['cargo'] ?? '']
            )));
            $aprendizId = trim((string) ($_GET['aprendiz_id'] ?? ''));
            $usuarioIdMiniapp = trim((string) ($miniappObreiro['id'] ?? ''));
            $podeConsultarOutros = in_array('primeiro_vigilante', $roles, true) || in_array('veneravel', $roles, true) || in_array('admin', $roles, true);
            $aprendizIdConsulta = $podeConsultarOutros && $aprendizId !== '' ? $aprendizId : $usuarioIdMiniapp;
            $controller = new \App\Controllers\PrimeiroVigilanteController();
            $payload = $controller->montarPayloadMiniapp($aprendizIdConsulta);
            if ($payload === null) {
                JsonResponse::send(['ok' => false, 'erro' => 'Aprendiz nao encontrado para acompanhamento.']);
            }
            JsonResponse::send(['ok' => true, 'dados' => $payload]);
        }

        if ($requestUri === '/api/miniapp/primeiro-vigilante/dashboard' && $method === 'GET') {
            $aprendizId = trim((string) ($_GET['aprendiz_id'] ?? ''));
            $controller = new \App\Controllers\PrimeiroVigilanteController();
            JsonResponse::send(['ok' => true, 'dados' => $controller->montarPayloadPainelMiniapp($aprendizId !== '' ? $aprendizId : null)]);
        }

        if ($requestUri === '/api/miniapp/primeiro-vigilante/leitura/salvar' && $method === 'POST') {
            $controller = new \App\Controllers\PrimeiroVigilanteController();
            $autorId = trim((string) ($miniappObreiro['id'] ?? $session['usuario_id'] ?? ''));
            JsonResponse::send($controller->salvarLeituraSugeridaMiniapp(
                trim((string) ($body['aprendiz_id'] ?? '')),
                isset($body['acervo_id']) && (int) $body['acervo_id'] > 0 ? (int) $body['acervo_id'] : null,
                trim((string) ($body['observacao_leitura'] ?? '')) ?: null,
                $autorId !== '' ? $autorId : null
            ));
        }

        if ($requestUri === '/api/miniapp/primeiro-vigilante/trilha/atualizar' && $method === 'POST') {
            $controller = new \App\Controllers\PrimeiroVigilanteController();
            $autorId = trim((string) ($miniappObreiro['id'] ?? $session['usuario_id'] ?? ''));
            JsonResponse::send($controller->atualizarEtapaMiniapp(
                trim((string) ($body['aprendiz_id'] ?? '')),
                (int) ($body['etapa_ordem'] ?? 0),
                trim((string) ($body['status'] ?? '')),
                trim((string) ($body['observacao_vigilante'] ?? '')) ?: null,
                $autorId !== '' ? $autorId : null
            ));
        }

        if ($requestUri === '/api/miniapp/primeiro-vigilante/certificado/solicitar' && $method === 'POST') {
            $controller = new \App\Controllers\PrimeiroVigilanteController();
            $autorId = trim((string) ($miniappObreiro['id'] ?? $session['usuario_id'] ?? ''));
            JsonResponse::send($controller->solicitarCertificadoMiniapp(
                trim((string) ($body['aprendiz_id'] ?? '')),
                trim((string) ($body['observacao_certificado'] ?? '')) ?: null,
                $autorId !== '' ? $autorId : null
            ));
        }

        if ($requestUri === '/api/miniapp/companheirismo' && $method === 'GET') {
            $roles = array_values(array_unique(array_map(
                static fn ($role) => strtolower((string) $role),
                $miniappObreiro['cargos'] ?? [$miniappObreiro['cargo_principal'] ?? $miniappObreiro['cargo'] ?? '']
            )));
            $companheiroId = trim((string) ($_GET['companheiro_id'] ?? ''));
            $usuarioIdMiniapp = trim((string) ($miniappObreiro['id'] ?? ''));
            $podeConsultarOutros = in_array('segundo_vigilante', $roles, true) || in_array('veneravel', $roles, true) || in_array('admin', $roles, true);
            $companheiroIdConsulta = $podeConsultarOutros && $companheiroId !== '' ? $companheiroId : $usuarioIdMiniapp;
            $controller = new \App\Controllers\SegundoVigilanteController();
            $payload = $controller->montarPayloadMiniapp($companheiroIdConsulta);
            if ($payload === null) {
                JsonResponse::send(['ok' => false, 'erro' => 'Companheiro nao encontrado para acompanhamento.']);
            }
            JsonResponse::send(['ok' => true, 'dados' => $payload]);
        }

        if ($requestUri === '/api/miniapp/segundo-vigilante/dashboard' && $method === 'GET') {
            $companheiroId = trim((string) ($_GET['companheiro_id'] ?? ''));
            $controller = new \App\Controllers\SegundoVigilanteController();
            JsonResponse::send(['ok' => true, 'dados' => $controller->montarPayloadPainelMiniapp($companheiroId !== '' ? $companheiroId : null)]);
        }

        if ($requestUri === '/api/miniapp/segundo-vigilante/trilha/atualizar' && $method === 'POST') {
            $controller = new \App\Controllers\SegundoVigilanteController();
            $autorId = trim((string) ($miniappObreiro['id'] ?? $session['usuario_id'] ?? ''));
            JsonResponse::send($controller->atualizarEtapaMiniapp(
                trim((string) ($body['companheiro_id'] ?? '')),
                (int) ($body['etapa_ordem'] ?? 0),
                trim((string) ($body['status'] ?? '')),
                trim((string) ($body['observacao_vigilante'] ?? '')) ?: null,
                $autorId !== '' ? $autorId : null
            ));
        }

        if ($requestUri === '/api/miniapp/segundo-vigilante/leitura/salvar' && $method === 'POST') {
            $controller = new \App\Controllers\SegundoVigilanteController();
            $autorId = trim((string) ($miniappObreiro['id'] ?? $session['usuario_id'] ?? ''));
            JsonResponse::send($controller->salvarLeituraSugeridaMiniapp(
                trim((string) ($body['companheiro_id'] ?? '')),
                isset($body['acervo_id']) && (int) $body['acervo_id'] > 0 ? (int) $body['acervo_id'] : null,
                trim((string) ($body['observacao_leitura'] ?? '')) ?: null,
                $autorId !== '' ? $autorId : null
            ));
        }

        if ($requestUri === '/api/miniapp/segundo-vigilante/certificado/solicitar' && $method === 'POST') {
            $controller = new \App\Controllers\SegundoVigilanteController();
            $autorId = trim((string) ($miniappObreiro['id'] ?? $session['usuario_id'] ?? ''));
            JsonResponse::send($controller->solicitarCertificadoMiniapp(
                trim((string) ($body['companheiro_id'] ?? '')),
                trim((string) ($body['observacao_certificado'] ?? '')) ?: null,
                $autorId !== '' ? $autorId : null
            ));
        }

        if ($requestUri === '/api/miniapp/segundo-vigilante/exaltacao/recomendar' && $method === 'POST') {
            $controller = new \App\Controllers\SegundoVigilanteController();
            $autorId = trim((string) ($miniappObreiro['id'] ?? $session['usuario_id'] ?? ''));
            JsonResponse::send($controller->recomendarExaltacaoMiniapp(
                trim((string) ($body['companheiro_id'] ?? '')),
                trim((string) ($body['observacao_exaltacao'] ?? '')) ?: null,
                $autorId !== '' ? $autorId : null
            ));
        }

        if ($requestUri === '/api/miniapp/secretaria/dashboard' && $method === 'GET') {
            $sessaoId = (int) ($_GET['sessao_id'] ?? 0);
            $controller = new \App\Controllers\SecretariaController();
            JsonResponse::send(['ok' => true, 'dados' => $controller->montarPayloadMiniapp($sessaoId > 0 ? $sessaoId : null)]);
        }

        if ($requestUri === '/api/miniapp/secretaria/sessao/salvar' && $method === 'POST') {
            $controller = new \App\Controllers\SecretariaController();
            $autorId = trim((string) ($miniappObreiro['id'] ?? $session['usuario_id'] ?? ''));
            JsonResponse::send($controller->salvarSessaoMiniapp($body, $autorId !== '' ? $autorId : null));
        }

        if ($requestUri === '/api/miniapp/secretaria/sessao/publicar' && $method === 'POST') {
            $sessaoId = (int) ($body['sessao_id'] ?? 0);
            $autorId = trim((string) ($miniappObreiro['id'] ?? $session['usuario_id'] ?? ''));
            $ok = $sessaoId > 0 ? (new Sessao())->marcarPublicada($sessaoId, $autorId !== '' ? $autorId : null, 'Publicacao realizada pela Secretaria no miniapp.') : false;
            JsonResponse::send(['ok' => $ok, 'erro' => $ok ? null : 'Nao foi possivel publicar a sessao.']);
        }

        if ($requestUri === '/api/miniapp/secretaria/sessao/cancelar' && $method === 'POST') {
            $sessaoId = (int) ($body['sessao_id'] ?? 0);
            $autorId = trim((string) ($miniappObreiro['id'] ?? $session['usuario_id'] ?? ''));
            $ok = $sessaoId > 0 ? (new Sessao())->cancelar($sessaoId, $autorId !== '' ? $autorId : null, 'Cancelamento realizado pela Secretaria no miniapp.') : false;
            JsonResponse::send(['ok' => $ok, 'erro' => $ok ? null : 'Nao foi possivel cancelar a sessao.']);
        }

        if ($requestUri === '/api/miniapp/secretaria/sessao/reabrir' && $method === 'POST') {
            $sessaoId = (int) ($body['sessao_id'] ?? 0);
            $autorId = trim((string) ($miniappObreiro['id'] ?? $session['usuario_id'] ?? ''));
            $ok = $sessaoId > 0 ? (new Sessao())->reabrir($sessaoId, $autorId !== '' ? $autorId : null, 'Reabertura realizada pela Secretaria no miniapp.') : false;
            JsonResponse::send(['ok' => $ok, 'erro' => $ok ? null : 'Nao foi possivel reabrir a sessao.']);
        }

        if ($requestUri === '/api/miniapp/secretaria/trabalho/salvar' && $method === 'POST') {
            $controller = new \App\Controllers\SecretariaController();
            $autorId = trim((string) ($miniappObreiro['id'] ?? $session['usuario_id'] ?? ''));
            JsonResponse::send($controller->salvarTrabalhoMiniapp($body, $autorId !== '' ? $autorId : null));
        }

        if ($requestUri === '/api/miniapp/secretaria/balaustre/salvar' && $method === 'POST') {
            $controller = new \App\Controllers\SecretariaController();
            $autorId = trim((string) ($miniappObreiro['id'] ?? $session['usuario_id'] ?? ''));
            JsonResponse::send($controller->salvarBalaustreMiniapp($body, $autorId !== '' ? $autorId : null));
        }

        if ($requestUri === '/api/miniapp/secretaria/balaustre/apto' && $method === 'POST') {
            $balaustreId = (int) ($body['balaustre_id'] ?? 0);
            $autorId = trim((string) ($miniappObreiro['id'] ?? $session['usuario_id'] ?? ''));
            $ok = $balaustreId > 0 ? (new Balaustre())->marcarAptoVotacao($balaustreId, $autorId !== '' ? $autorId : null) : false;
            JsonResponse::send(['ok' => $ok, 'erro' => $ok ? null : 'Nao foi possivel marcar o balaustre como apto.']);
        }

        if ($requestUri === '/api/miniapp/secretaria/balaustre/abrir-votacao' && $method === 'POST') {
            $balaustreId = (int) ($body['balaustre_id'] ?? 0);
            $autorId = trim((string) ($miniappObreiro['id'] ?? $session['usuario_id'] ?? ''));
            JsonResponse::send($balaustreId > 0 ? (new Balaustre())->abrirVotacao($balaustreId, $autorId !== '' ? $autorId : null) : ['ok' => false, 'erro' => 'Balaustre invalido.']);
        }

        if ($requestUri === '/api/miniapp/secretaria/balaustre/encerrar-votacao' && $method === 'POST') {
            $balaustreId = (int) ($body['balaustre_id'] ?? 0);
            JsonResponse::send($balaustreId > 0 ? (new Balaustre())->encerrarVotacaoPorBalaustre($balaustreId) : ['ok' => false, 'erro' => 'Balaustre invalido.']);
        }

        if ($requestUri === '/api/miniapp/chanceler/dashboard' && $method === 'GET') {
            $sessaoId = (int) ($_GET['sessao_id'] ?? 0);
            $controller = new \App\Controllers\ChancelerSessaoController();
            JsonResponse::send(['ok' => true, 'dados' => $controller->montarPayloadMiniapp($sessaoId > 0 ? $sessaoId : null)]);
        }

        if ($requestUri === '/api/miniapp/chanceler/presenca' && $method === 'POST') {
            $controller = new \App\Controllers\ChancelerSessaoController();
            $sessaoId = (int) ($body['sessao_id'] ?? 0);
            $obreiroId = trim((string) ($body['obreiro_id'] ?? ''));
            $presente = filter_var($body['presente'] ?? false, FILTER_VALIDATE_BOOL);
            $autorId = trim((string) ($miniappObreiro['id'] ?? $session['usuario_id'] ?? ''));
            JsonResponse::send($controller->registrarPresencaMiniapp($sessaoId, $obreiroId, $presente, $autorId !== '' ? $autorId : null));
        }

        if ($requestUri === '/api/miniapp/mestre-banquetes/dashboard' && $method === 'GET') {
            $sessaoId = (int) ($_GET['sessao_id'] ?? 0);
            $controller = new \App\Controllers\MestreBanquetesController();
            JsonResponse::send(['ok' => true, 'dados' => $controller->montarPayloadMiniapp($sessaoId > 0 ? $sessaoId : null)]);
        }

        if ($requestUri === '/api/miniapp/mestre-banquetes/operacao/salvar' && $method === 'POST') {
            $controller = new \App\Controllers\MestreBanquetesController();
            $autorId = isset($miniappObreiro['id']) ? (int) $miniappObreiro['id'] : (isset($session['usuario_id']) ? (int) $session['usuario_id'] : null);
            JsonResponse::send($controller->salvarOperacaoMiniapp($body, $autorId));
        }

        if ($requestUri === '/api/miniapp/mestre-harmonia/dashboard' && $method === 'GET') {
            $controller = new \App\Controllers\MestreHarmoniaController();
            $sessaoPath = trim((string) ($_GET['sessao_path'] ?? ''));
            JsonResponse::send(['ok' => true, 'dados' => $controller->montarPayloadMiniapp($sessaoPath !== '' ? $sessaoPath : null)]);
        }

        if ($requestUri === '/api/miniapp/mestre-harmonia/operador' && $method === 'POST') {
            $controller = new \App\Controllers\MestreHarmoniaController();
            JsonResponse::send($controller->salvarOperadorMiniapp($body));
        }

        if ($requestUri === '/api/miniapp/mestre-harmonia/controle' && $method === 'POST') {
            $controller = new \App\Controllers\MestreHarmoniaController();
            JsonResponse::send($controller->executarAcaoMiniapp(trim((string) ($body['acao'] ?? '')), $body));
        }

        if ($requestUri === '/api/miniapp/tesouraria/dashboard' && $method === 'GET') {
            $controller = new \App\Controllers\TesourariaController();
            JsonResponse::send(['ok' => true, 'dados' => $controller->montarPayloadMiniapp()]);
        }

        if ($requestUri === '/api/miniapp/tesouraria/comprovante/aprovar' && $method === 'POST') {
            $controller = new \App\Controllers\TesourariaController();
            $usuarioId = trim((string) ($miniappObreiro['id'] ?? $session['usuario_id'] ?? ''));
            JsonResponse::send($controller->aprovarComprovanteMiniapp($body, $usuarioId !== '' ? $usuarioId : null));
        }

        if ($requestUri === '/api/miniapp/tesouraria/comprovante/rejeitar' && $method === 'POST') {
            $controller = new \App\Controllers\TesourariaController();
            $usuarioId = trim((string) ($miniappObreiro['id'] ?? $session['usuario_id'] ?? ''));
            JsonResponse::send($controller->rejeitarComprovanteMiniapp((int) ($body['id'] ?? 0), (string) ($body['motivo'] ?? ''), $usuarioId !== '' ? $usuarioId : null));
        }

        if ($requestUri === '/api/miniapp/tesouraria/regularidade/definir' && $method === 'POST') {
            $controller = new \App\Controllers\TesourariaController();
            $usuarioId = trim((string) ($miniappObreiro['id'] ?? $session['usuario_id'] ?? ''));
            JsonResponse::send($controller->definirRegularidadeMiniapp(
                (string) ($body['obreiro_id'] ?? ''),
                (int) ($body['mes'] ?? date('n')),
                (int) ($body['ano'] ?? date('Y')),
                (string) ($body['status'] ?? 'regular'),
                $usuarioId !== '' ? $usuarioId : null
            ));
        }

        if ($requestUri === '/api/miniapp/tesouraria/fechamento/fechar' && $method === 'POST') {
            $controller = new \App\Controllers\TesourariaController();
            $usuarioId = trim((string) ($miniappObreiro['id'] ?? $session['usuario_id'] ?? ''));
            JsonResponse::send($controller->fecharCompetenciaMiniapp((int) ($body['mes'] ?? date('n')), (int) ($body['ano'] ?? date('Y')), $usuarioId !== '' ? $usuarioId : null));
        }

        if ($requestUri === '/api/miniapp/biblioteca/dashboard' && $method === 'GET') {
            $controller = new \App\Controllers\BibliotecaController();
            $acervoId = (int) ($_GET['acervo_id'] ?? 0);
            $obreiroId = trim((string) ($miniappObreiro['id'] ?? $session['usuario_id'] ?? ''));
            JsonResponse::send(['ok' => true, 'dados' => $controller->montarPayloadMiniapp($obreiroId !== '' ? $obreiroId : null, $acervoId > 0 ? $acervoId : null)]);
        }

        if ($requestUri === '/api/miniapp/biblioteca/solicitar' && $method === 'POST') {
            $controller = new \App\Controllers\BibliotecaController();
            $obreiroId = trim((string) ($miniappObreiro['id'] ?? $session['usuario_id'] ?? ''));
            JsonResponse::send($controller->solicitarMiniapp((int) ($body['acervo_id'] ?? 0), $obreiroId));
        }

        if ($requestUri === '/api/miniapp/biblioteca/comentar' && $method === 'POST') {
            $controller = new \App\Controllers\BibliotecaController();
            $obreiroId = trim((string) ($miniappObreiro['id'] ?? $session['usuario_id'] ?? ''));
            JsonResponse::send($controller->comentarMiniapp((int) ($body['acervo_id'] ?? 0), $obreiroId, (string) ($body['comentario'] ?? '')));
        }

        if ($requestUri === '/api/miniapp/biblioteca/reagir' && $method === 'POST') {
            $controller = new \App\Controllers\BibliotecaController();
            $obreiroId = trim((string) ($miniappObreiro['id'] ?? $session['usuario_id'] ?? ''));
            JsonResponse::send($controller->reagirMiniapp((int) ($body['acervo_id'] ?? 0), $obreiroId, !empty($body['gostei'])));
        }

        if ($requestUri === '/api/miniapp/admin/dashboard' && $method === 'GET') {
            $controller = new \App\Controllers\AdminController();
            JsonResponse::send(['ok' => true, 'dados' => $controller->montarPayloadMiniapp()]);
        }

        if ($requestUri === '/api/miniapp/admin/gestao/abrir' && $method === 'POST') {
            $controller = new \App\Controllers\AdminController();
            JsonResponse::send($controller->abrirGestaoMiniapp((string) ($body['titulo'] ?? ''), (string) ($body['inicio_em'] ?? ''), (string) ($body['observacao'] ?? '')));
        }

        if ($requestUri === '/api/miniapp/admin/gestao/encerrar' && $method === 'POST') {
            $controller = new \App\Controllers\AdminController();
            JsonResponse::send($controller->encerrarGestaoMiniapp((int) ($body['gestao_id'] ?? 0), (string) ($body['encerrada_em'] ?? '')));
        }

        if ($requestUri === '/api/miniapp/admin/cargo/atribuir' && $method === 'POST') {
            $controller = new \App\Controllers\AdminController();
            JsonResponse::send($controller->atribuirCargoMiniapp(
                (string) ($body['cargo_codigo'] ?? ''),
                (string) ($body['obreiro_id'] ?? ''),
                isset($body['gestao_id']) ? (int) $body['gestao_id'] : null,
                (string) ($body['inicio_em'] ?? ''),
                (string) ($body['observacao'] ?? '')
            ));
        }

        if ($requestUri === '/api/miniapp/admin/configuracao/salvar' && $method === 'POST') {
            $controller = new \App\Controllers\AdminController();
            JsonResponse::send($controller->salvarConfiguracaoMiniapp($body));
        }

        if ($requestUri === '/api/miniapp/orador/dashboard' && $method === 'GET') {
            $sessaoId = (int) ($_GET['sessao_id'] ?? 0);
            $controller = new \App\Controllers\OradorController();
            JsonResponse::send(['ok' => true, 'dados' => $controller->montarPayloadMiniapp($sessaoId > 0 ? $sessaoId : null)]);
        }

        if ($requestUri === '/api/miniapp/veneravel/dashboard' && $method === 'GET') {
            $sessaoId = (int) ($_GET['sessao_id'] ?? 0);
            $controller = new \App\Controllers\VeneravelController();
            JsonResponse::send(['ok' => true, 'dados' => $controller->montarPayloadMiniapp($sessaoId > 0 ? $sessaoId : null)]);
        }

        if ($requestUri === '/api/miniapp/hospitaleiro/dashboard' && $method === 'GET') {
            $controller = new \App\Controllers\HospitaleiroController();
            JsonResponse::send(['ok' => true, 'dados' => $controller->montarPayloadMiniapp()]);
        }

        if ($requestUri === '/api/miniapp/hospitaleiro/ocorrencias/salvar' && $method === 'POST') {
            $controller = new \App\Controllers\HospitaleiroController();
            $autorId = trim((string) ($miniappObreiro['id'] ?? $session['usuario_id'] ?? ''));
            JsonResponse::send($controller->salvarOcorrenciaMiniapp($body, $autorId !== '' ? $autorId : null));
        }

        if ($requestUri === '/api/miniapp/hospitaleiro/ocorrencias/status' && $method === 'POST') {
            $controller = new \App\Controllers\HospitaleiroController();
            $autorId = trim((string) ($miniappObreiro['id'] ?? $session['usuario_id'] ?? ''));
            JsonResponse::send($controller->atualizarStatusMiniapp(
                (int) ($body['ocorrencia_id'] ?? 0),
                trim((string) ($body['status'] ?? '')),
                $autorId !== '' ? $autorId : null,
                trim((string) ($body['observacao_status'] ?? '')) ?: null
            ));
        }

        if ($requestUri === '/api/miniapp/hospitaleiro/visita' && $method === 'POST') {
            $controller = new \App\Controllers\HospitaleiroController();
            $autorId = trim((string) ($miniappObreiro['id'] ?? $session['usuario_id'] ?? ''));
            JsonResponse::send($controller->registrarVisitaMiniapp(
                (int) ($body['ocorrencia_id'] ?? 0),
                $autorId !== '' ? $autorId : null,
                trim((string) ($body['observacao_visita'] ?? '')) ?: null,
                trim((string) ($body['data_proxima_acao'] ?? '')) ?: null
            ));
        }

        if (preg_match('~^/api/miniapp/veneravel/sessao/(publicar|cancelar|reabrir|realizar)$~', $requestUri, $m) && $method === 'POST') {
            $controller = new \App\Controllers\VeneravelController();
            $sessaoId = (int) ($body['sessao_id'] ?? 0);
            $autorId = trim((string) ($miniappObreiro['id'] ?? $session['usuario_id'] ?? ''));
            JsonResponse::send($controller->executarAcaoSessaoMiniapp($m[1], $sessaoId, $autorId !== '' ? $autorId : null));
        }

        if (preg_match('~^/api/miniapp/veneravel/balaustre/(abrir-votacao|encerrar-votacao)$~', $requestUri, $m) && $method === 'POST') {
            $controller = new \App\Controllers\VeneravelController();
            $balaustreId = (int) ($body['balaustre_id'] ?? 0);
            $autorId = trim((string) ($miniappObreiro['id'] ?? $session['usuario_id'] ?? ''));
            $acao = $m[1] === 'abrir-votacao' ? 'abrir' : 'encerrar';
            JsonResponse::send($controller->executarAcaoBalaustreMiniapp($acao, $balaustreId, $autorId !== '' ? $autorId : null));
        }

        JsonResponse::error('API miniapp nao encontrada.', 404);
    }
}
