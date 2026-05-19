<?php

namespace App\Core\Http;

use App\Core\Authorization\PermissionMap;
use App\Models\Balaustre;
use App\Models\AssistenteTelemetria;
use App\Models\EfemerideRegistro;
use App\Models\HistoriaMaconica;
use App\Models\MensagemComplementar;
use App\Models\Obreiro;
use App\Models\PalavraDia;
use App\Models\Sessao;
use App\Services\AssistenteFluxoService;

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
        callable $contentPermissionService,
        callable $buildEfemeridesPreview
    ): bool {
        if (!preg_match('~^/api/miniapp~', $requestUri)) {
            return false;
        }

        header('Content-Type: application/json; charset=utf-8');

        $tenantIdFromSession = trim((string) ($session['tenant_id'] ?? ''));
        if ($tenantIdFromSession === '') {
            JsonResponse::error('Loja não identificada. Verifique a configuração do ambiente.', 503);
        }

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
            str_starts_with($requestUri, '/api/miniapp/biblioteca') => ['obreiro', 'bibliotecario', 'primeiro_vigilante', 'segundo_vigilante', 'veneravel', 'admin'],
            str_starts_with($requestUri, '/api/miniapp/admin') => ['admin', 'veneravel'],
            str_starts_with($requestUri, '/api/miniapp/orador') => ['orador', 'veneravel', 'admin'],
            str_starts_with($requestUri, '/api/miniapp/veneravel') => ['veneravel', 'admin'],
            str_starts_with($requestUri, '/api/miniapp/hospitaleiro') => ['hospitaleiro', 'secretario', 'tesoureiro', 'veneravel', 'admin'],
            str_starts_with($requestUri, '/api/miniapp/assistente') => ['admin', 'veneravel', 'secretario', 'tesoureiro', 'chanceler', 'orador', 'hospitaleiro', 'mestre_banquetes', 'mestre_harmonia', 'primeiro_vigilante', 'segundo_vigilante', 'bibliotecario'],
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

            $rolesSource = $miniappObreiro['cargos'] ?? null;
            if (!is_array($rolesSource) || $rolesSource === []) {
                $rolesSource = [$miniappObreiro['cargo_principal'] ?? $miniappObreiro['cargo'] ?? ''];
            }

            $roles = array_values(array_unique(array_filter(array_map(
                $normalizeRole,
                $rolesSource
            ))));
            if (!in_array('obreiro', $roles, true)) {
                $roles[] = 'obreiro';
            }
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

        $efemerideModel = null;
        $mensagensModel = null;
        $resolveEfemerideModel = static function () use (&$efemerideModel): EfemerideRegistro {
            if (!$efemerideModel instanceof EfemerideRegistro) {
                $efemerideModel = new EfemerideRegistro();
            }

            return $efemerideModel;
        };
        $resolveMensagensModel = static function () use (&$mensagensModel): MensagemComplementar {
            if (!$mensagensModel instanceof MensagemComplementar) {
                $mensagensModel = new MensagemComplementar();
            }

            return $mensagensModel;
        };

        $miniappCanPermission = static function (string $permission) use (
            $authorizedBySession,
            $sessionHasPermission,
            $miniappObreiro,
            $permissionMap,
            $normalizeRole
        ): bool {
            if ($authorizedBySession) {
                return $sessionHasPermission($permission);
            }

            $rolesSource = $miniappObreiro['cargos'] ?? null;
            if (!is_array($rolesSource) || $rolesSource === []) {
                $rolesSource = [$miniappObreiro['cargo_principal'] ?? $miniappObreiro['cargo'] ?? ''];
            }

            $roles = array_values(array_unique(array_filter(array_map($normalizeRole, $rolesSource))));
            if (!in_array('obreiro', $roles, true)) {
                $roles[] = 'obreiro';
            }
            $permissions = $permissionMap->permissionsForRoles($roles);

            return in_array('*', $permissions, true) || in_array($permission, $permissions, true);
        };

        if ($requestUri === '/api/miniapp/assistente/interpretar' && in_array($method, ['GET', 'POST'], true)) {
            $comando = trim((string) ($body['comando'] ?? $body['q'] ?? $_GET['q'] ?? ''));
            $assistenteService = new AssistenteFluxoService();
            $resultado = $assistenteService->interpretar($comando, $miniappCanPermission);

            $obreiroModel = new Obreiro();
            $resultado = $assistenteService->aplicarDesambiguacaoMensalidade(
                $resultado,
                $comando,
                static function (string $nomeBusca) use ($obreiroModel): array {
                    $ativos = $obreiroModel->buscarAtivosPorNome($nomeBusca, 8);
                    return array_map(static function (array $item): array {
                        return [
                            'id' => (string) ($item['id'] ?? ''),
                            'nome' => (string) ($item['nome_historico'] ?? $item['nome'] ?? ''),
                            'cim' => (string) ($item['cim'] ?? ''),
                        ];
                    }, $ativos);
                }
            );

            try {
                (new AssistenteTelemetria())->registrar([
                    'canal' => 'miniapp',
                    'comando_original' => $comando,
                    'comando_normalizado' => preg_replace('/\s+/', ' ', strtolower(trim((string) iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $comando)))) ?: strtolower(trim($comando)),
                    'intent' => (string) ($resultado['intent'] ?? 'desconhecida'),
                    'confidence' => (float) ($resultado['confidence'] ?? 0),
                    'allowed' => !empty($resultado['allowed']),
                    'action_target' => (string) (($resultado['action']['target'] ?? null) ?: ''),
                    'user_id' => (string) ($miniappObreiro['id'] ?? ''),
                    'tenant_id' => (string) ($miniappObreiro['loja_id'] ?? ($session['tenant_id'] ?? '')),
                    'metadata' => [
                        'needs_disambiguation' => !empty($resultado['needs_disambiguation']),
                    ],
                ]);
            } catch (\Throwable $e) {
                error_log('[assistente.telemetria] falha ao registrar evento miniapp: ' . $e->getMessage());
            }

            JsonResponse::send(['ok' => true, 'resultado' => $resultado]);
        }

        if ($requestUri === '/api/miniapp/efemeride/salvar' && $method === 'POST') {
            $id = (int) ($body['id'] ?? 0);
            $nome = trim((string) ($body['nome'] ?? ''));
            $tipo = trim((string) ($body['tipo'] ?? ''));
            $dataEvento = trim((string) ($body['data_evento'] ?? ''));
            $dataValida = \DateTimeImmutable::createFromFormat('Y-m-d', $dataEvento) !== false;
            if ($nome === '' || $tipo === '' || $dataEvento === '' || !$dataValida) {
                JsonResponse::send(['ok' => false, 'erro' => 'Dados inválidos para salvar efeméride.']);
            }

            if ($id > 0) {
                $ok = $resolveEfemerideModel()->atualizar($id, $body);
            } else {
                $createdBy = (int) ($miniappObreiro['id'] ?? ($session['usuario_id'] ?? 0));
                $ok = $resolveEfemerideModel()->create($body, $createdBy > 0 ? $createdBy : null);
            }

            JsonResponse::send(['ok' => $ok]);
        }

        if ($requestUri === '/api/miniapp/efemeride/desativar' && $method === 'POST') {
            $id = (int) ($body['id'] ?? 0);
            JsonResponse::send(['ok' => $id > 0 ? $resolveEfemerideModel()->desativar($id) : false]);
        }

        if ($requestUri === '/api/miniapp/efemerides/listar' && $method === 'GET') {
            JsonResponse::send(['ok' => true, 'registros' => $resolveEfemerideModel()->buscarComFiltros(['ativo' => 'all'], 300)]);
        }

        if ($requestUri === '/api/miniapp/efemerides/preview' && $method === 'GET') {
            $dadosEfemerides = $buildEfemeridesPreview();
            $mensagemPreview = trim((string) ($dadosEfemerides['mensagemPreview'] ?? ''));
            $cards = is_array($dadosEfemerides['cards'] ?? null) ? $dadosEfemerides['cards'] : [];
            JsonResponse::send([
                'ok' => true,
                'mensagem_preview' => $mensagemPreview,
                'cards_total' => count($cards),
            ]);
        }

        if ($requestUri === '/api/miniapp/efemerides/enviar-grupo' && $method === 'POST') {
            $dadosEfemerides = $buildEfemeridesPreview();
            $mensagemPreview = trim((string) ($dadosEfemerides['mensagemPreview'] ?? ''));
            $cards = is_array($dadosEfemerides['cards'] ?? null) ? $dadosEfemerides['cards'] : [];

            if ($mensagemPreview === '') {
                JsonResponse::send(['ok' => false, 'erro' => 'Previa vazia.']);
            }

            $telegramService = new \App\Services\TelegramService();
            if (!$telegramService->sendMessageToGroup($mensagemPreview)) {
                JsonResponse::send(['ok' => false, 'erro' => $telegramService->getLastError()]);
            }

            $cardsEnviados = 0;
            foreach ($cards as $card) {
                $absPath = trim((string) ($card['card_path'] ?? ''));
                if ($absPath === '' || !file_exists($absPath)) {
                    continue;
                }
                $caption = trim((string) ($card['titulo'] ?? $card['descricao'] ?? 'Efemeride'));
                if ($telegramService->sendPhotoToGroup($absPath, $caption)) {
                    $cardsEnviados++;
                }
            }

            JsonResponse::send(['ok' => true, 'cards_enviados' => $cardsEnviados]);
        }

        if ($requestUri === '/api/miniapp/efemerides/excluir' && $method === 'POST') {
            $id = (int) ($body['id'] ?? 0);
            JsonResponse::send(['ok' => $id > 0 ? $resolveEfemerideModel()->excluir($id) : false]);
        }

        if ($requestUri === '/api/miniapp/historico/listar' && $method === 'GET') {
            $historias = (new HistoriaMaconica())->listar(['ativo' => 'all'], 300);
            JsonResponse::send(['ok' => true, 'registros' => $historias]);
        }

        if ($requestUri === '/api/miniapp/historico/salvar' && $method === 'POST') {
            $id = (int) ($body['id'] ?? 0);
            $titulo = trim((string) ($body['titulo'] ?? ''));
            $texto = trim((string) ($body['texto'] ?? ''));
            $dia = (int) ($body['dia'] ?? 0);
            $mes = (int) ($body['mes'] ?? 0);
            if ($titulo === '' || $texto === '' || $dia <= 0 || $mes <= 0) {
                JsonResponse::send(['ok' => false, 'erro' => 'Dados inválidos para salvar histórico.']);
            }

            $model = new HistoriaMaconica();
            if ($id > 0) {
                $ok = $model->atualizar($id, $body);
            } else {
                $createdBy = isset($miniappObreiro['id']) ? (int) $miniappObreiro['id'] : (isset($session['usuario_id']) ? (int) $session['usuario_id'] : null);
                $ok = $model->create($body, $createdBy > 0 ? $createdBy : null);
            }

            JsonResponse::send(['ok' => $ok]);
        }

        if ($requestUri === '/api/miniapp/historico/toggle' && $method === 'POST') {
            $id = (int) ($body['id'] ?? 0);
            JsonResponse::send(['ok' => $id > 0 ? (new HistoriaMaconica())->toggleAtivo($id) : false]);
        }

        if ($requestUri === '/api/miniapp/historico/excluir' && $method === 'POST') {
            $id = (int) ($body['id'] ?? 0);
            JsonResponse::send(['ok' => $id > 0 ? (new HistoriaMaconica())->excluir($id) : false]);
        }

        if ($requestUri === '/api/miniapp/palavra-dia/listar' && $method === 'GET') {
            $mensagens = (new PalavraDia())->listar(['ativo' => 'all'], 300);
            JsonResponse::send(['ok' => true, 'mensagens' => $mensagens]);
        }

        if ($requestUri === '/api/miniapp/palavra-dia/salvar' && $method === 'POST') {
            $id = (int) ($body['id'] ?? 0);
            $mensagem = trim((string) ($body['mensagem'] ?? ''));
            if ($mensagem === '') {
                JsonResponse::send(['ok' => false, 'erro' => 'Mensagem vazia.']);
            }

            $model = new PalavraDia();
            if ($id > 0) {
                $ok = $model->atualizar($id, $body);
            } else {
                $createdBy = isset($miniappObreiro['id']) ? (int) $miniappObreiro['id'] : (isset($session['usuario_id']) ? (int) $session['usuario_id'] : null);
                $ok = $model->create($body, $createdBy > 0 ? $createdBy : null);
            }

            JsonResponse::send(['ok' => $ok]);
        }

        if ($requestUri === '/api/miniapp/palavra-dia/toggle' && $method === 'POST') {
            $id = (int) ($body['id'] ?? 0);
            JsonResponse::send(['ok' => $id > 0 ? (new PalavraDia())->toggleAtivo($id) : false]);
        }

        if ($requestUri === '/api/miniapp/palavra-dia/excluir' && $method === 'POST') {
            $id = (int) ($body['id'] ?? 0);
            JsonResponse::send(['ok' => $id > 0 ? (new PalavraDia())->excluir($id) : false]);
        }

        if ($requestUri === '/api/miniapp/fallback/listar' && $method === 'GET') {
            JsonResponse::send(['ok' => true, 'mensagens' => $resolveMensagensModel()->listarPorTipo('fallback')]);
        }

        if ($requestUri === '/api/miniapp/fallback/salvar' && $method === 'POST') {
            $id = (int) ($body['id'] ?? 0);
            $mensagem = trim((string) ($body['mensagem'] ?? ''));
            if ($mensagem === '') {
                JsonResponse::send(['ok' => false, 'erro' => 'Mensagem vazia.']);
            }

            $mensagens = $resolveMensagensModel();
            $ok = $id > 0 ? $mensagens->atualizar($id, $mensagem) : $mensagens->criar('fallback', $mensagem);
            JsonResponse::send(['ok' => $ok]);
        }

        if ($requestUri === '/api/miniapp/fallback/toggle' && $method === 'POST') {
            $id = (int) ($body['id'] ?? 0);
            JsonResponse::send(['ok' => $id > 0 ? $resolveMensagensModel()->toggleAtivo($id) : false]);
        }

        if ($requestUri === '/api/miniapp/fallback/excluir' && $method === 'POST') {
            $id = (int) ($body['id'] ?? 0);
            JsonResponse::send(['ok' => $id > 0 ? $resolveMensagensModel()->excluir($id) : false]);
        }

        if ($requestUri === '/api/miniapp/aprendizado' && $method === 'GET') {
            $roles = array_values(array_unique(array_map(
                static fn ($role) => strtolower((string) $role),
                $miniappObreiro['cargos'] ?? [$miniappObreiro['cargo_principal'] ?? $miniappObreiro['cargo'] ?? '']
            )));
            $aprendizId = trim((string) ($_GET['aprendiz_id'] ?? ''));
            $usuarioIdMiniapp = trim((string) ($miniappObreiro['id'] ?? ''));
            $podeConsultarOutros = in_array('primeiro_vigilante', $roles, true) || in_array('veneravel', $roles, true);
            $aprendizIdConsulta = $podeConsultarOutros && $aprendizId !== '' ? $aprendizId : $usuarioIdMiniapp;
            $controller = new \App\Controllers\PrimeiroVigilanteController();
            $payload = $controller->montarPayloadMiniapp($aprendizIdConsulta);
            if ($payload === null) {
                JsonResponse::send(['ok' => false, 'erro' => 'Aprendiz não encontrado para acompanhamento.']);
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
            $podeConsultarOutros = in_array('segundo_vigilante', $roles, true) || in_array('veneravel', $roles, true);
            $companheiroIdConsulta = $podeConsultarOutros && $companheiroId !== '' ? $companheiroId : $usuarioIdMiniapp;
            $controller = new \App\Controllers\SegundoVigilanteController();
            $payload = $controller->montarPayloadMiniapp($companheiroIdConsulta);
            if ($payload === null) {
                JsonResponse::send(['ok' => false, 'erro' => 'Companheiro não encontrado para acompanhamento.']);
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
            $ok = $sessaoId > 0 ? (new Sessao())->marcarPublicada($sessaoId, $autorId !== '' ? $autorId : null, 'Publicação realizada pela Secretaria no miniapp.') : false;
            JsonResponse::send(['ok' => $ok, 'erro' => $ok ? null : 'Não foi possível publicar a sessão.']);
        }

        if ($requestUri === '/api/miniapp/secretaria/sessao/cancelar' && $method === 'POST') {
            $sessaoId = (int) ($body['sessao_id'] ?? 0);
            $autorId = trim((string) ($miniappObreiro['id'] ?? $session['usuario_id'] ?? ''));
            $ok = $sessaoId > 0 ? (new Sessao())->cancelar($sessaoId, $autorId !== '' ? $autorId : null, 'Cancelamento realizado pela Secretaria no miniapp.') : false;
            JsonResponse::send(['ok' => $ok, 'erro' => $ok ? null : 'Não foi possível cancelar a sessão.']);
        }

        if ($requestUri === '/api/miniapp/secretaria/sessao/reabrir' && $method === 'POST') {
            $sessaoId = (int) ($body['sessao_id'] ?? 0);
            $autorId = trim((string) ($miniappObreiro['id'] ?? $session['usuario_id'] ?? ''));
            $ok = $sessaoId > 0 ? (new Sessao())->reabrir($sessaoId, $autorId !== '' ? $autorId : null, 'Reabertura realizada pela Secretaria no miniapp.') : false;
            JsonResponse::send(['ok' => $ok, 'erro' => $ok ? null : 'Não foi possível reabrir a sessão.']);
        }

        if ($requestUri === '/api/miniapp/secretaria/trabalho/salvar' && $method === 'POST') {
            $controller = new \App\Controllers\SecretariaController();
            $autorId = trim((string) ($miniappObreiro['id'] ?? $session['usuario_id'] ?? ''));
            JsonResponse::send($controller->salvarTrabalhoMiniapp($body, $autorId !== '' ? $autorId : null));
        }

        if ($requestUri === '/api/miniapp/secretaria/publicacao/salvar' && $method === 'POST') {
            $controller = new \App\Controllers\SecretariaController();
            $autorId = trim((string) ($miniappObreiro['id'] ?? $session['usuario_id'] ?? ''));
            JsonResponse::send($controller->salvarPublicacaoMiniapp($body, $autorId !== '' ? $autorId : null));
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
            JsonResponse::send(['ok' => $ok, 'erro' => $ok ? null : 'Não foi possível marcar o balaustre como apto.']);
        }

        if ($requestUri === '/api/miniapp/secretaria/balaustre/abrir-votacao' && $method === 'POST') {
            $balaustreId = (int) ($body['balaustre_id'] ?? 0);
            $autorId = trim((string) ($miniappObreiro['id'] ?? $session['usuario_id'] ?? ''));
            JsonResponse::send($balaustreId > 0 ? (new Balaustre())->abrirVotacao($balaustreId, $autorId !== '' ? $autorId : null) : ['ok' => false, 'erro' => 'Balaustre inválido.']);
        }

        if ($requestUri === '/api/miniapp/secretaria/balaustre/encerrar-votacao' && $method === 'POST') {
            $balaustreId = (int) ($body['balaustre_id'] ?? 0);
            JsonResponse::send($balaustreId > 0 ? (new Balaustre())->encerrarVotacaoPorBalaustre($balaustreId) : ['ok' => false, 'erro' => 'Balaustre inválido.']);
        }

        if ($requestUri === '/api/miniapp/secretaria/balaustre/votar' && $method === 'POST') {
            $controller = new \App\Controllers\SecretariaController();
            $obreiroId = trim((string) ($miniappObreiro['id'] ?? $session['usuario_id'] ?? ''));
            JsonResponse::send($controller->votarBalaustreMiniapp($body, $obreiroId));
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
            $scope = (string) ($_GET['scope'] ?? $_GET['acervo'] ?? 'minha');
            $lojaId = (int) ($_GET['loja_id'] ?? 0);
            $obreiroId = trim((string) ($miniappObreiro['id'] ?? $session['usuario_id'] ?? ''));
            JsonResponse::send(['ok' => true, 'dados' => $controller->montarPayloadMiniapp(
                $obreiroId !== '' ? $obreiroId : null,
                $acervoId > 0 ? $acervoId : null,
                $scope,
                $lojaId > 0 ? $lojaId : null
            )]);
        }

        if ($requestUri === '/api/miniapp/biblioteca/solicitar' && $method === 'POST') {
            $controller = new \App\Controllers\BibliotecaController();
            $obreiroId = trim((string) ($miniappObreiro['id'] ?? $session['usuario_id'] ?? ''));
            JsonResponse::send($controller->solicitarMiniapp(
                (int) ($body['acervo_id'] ?? 0),
                $obreiroId,
                isset($body['loja_id']) ? (int) $body['loja_id'] : null,
                (string) ($body['scope'] ?? 'minha')
            ));
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
        return true;
    }
}
