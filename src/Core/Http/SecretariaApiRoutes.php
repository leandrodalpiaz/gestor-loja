<?php

namespace App\Core\Http;

use App\Models\Obreiro;
use App\Models\Sessao;
use App\Models\Balaustre;
use App\Models\Presenca;
use App\Models\PresencaSessao;
use App\Models\TrabalhoSessao;
use App\Models\TrabalhoSubmissao;
use App\Models\PublicacaoSecretaria;
use App\Models\ConfiguracaoLoja;
use App\Models\Cargo;
use App\Models\ConviteExterno;

class SecretariaApiRoutes
{
    public static function dispatch(
        string $requestUri,
        string $method,
        array $session,
        callable $requireSecretariaApiAccess
    ): bool {
        if (!str_starts_with($requestUri, '/api/secretaria')) {
            return false;
        }

        header('Content-Type: application/json; charset=utf-8');

        // Permissões específicas por endpoint
        $permissaoRequerida = 'secretaria.manage';
        if (str_contains($requestUri, '/api/secretaria/obreiros')) {
            // Operações de listagem/visualização podem exigir apenas obreiros.view
            $permissaoRequerida = ($method === 'GET') ? 'obreiros.view' : 'obreiros.manage';
        } elseif (
            $requestUri === '/api/secretaria/votacao'
            || preg_match('~^/api/secretaria/sessoes/\d+/balaustre/votar$~', $requestUri)
        ) {
            $permissaoRequerida = 'dashboard.view';
        }

        // Executa validação de JWT/Sessão e Permissão
        $requireSecretariaApiAccess($permissaoRequerida);
        $session = $_SESSION;

        $usuarioId = $session['usuario_id'] ?? null;

        // 1. Dashboard Info
        if ($requestUri === '/api/secretaria/dashboard' && $method === 'GET') {
            $sessaoModel = new Sessao();
            $obreiroModel = new Obreiro();
            $trabalhoModel = new TrabalhoSessao();
            $publicacaoModel = new PublicacaoSecretaria();
            $balaustreModel = new Balaustre();

            $proximaSessao = $sessaoModel->obterProximaSessao();
            $sessoesFuturas = $sessaoModel->listarFuturas(8);
            $sessoesRecentes = $sessaoModel->listarRecentes(8);
            $obreirosAtivos = $obreiroModel->getAllAtivos();
            $resumoCadastros = $obreiroModel->obterResumoSecretaria();
            $trabalhos = $trabalhoModel->listarRecentes(8);
            $publicacoes = $publicacaoModel->listarRecentes(8);
            $balaustres = $balaustreModel->listarRecentes(8);

            $resumo = [
                'obreiros_ativos' => count($obreirosAtivos),
                'sessoes_futuras' => count($sessoesFuturas),
                'trabalhos_pendentes' => count(array_filter($trabalhos, static fn ($item) => ($item['status_envio_potencia'] ?? '') === 'pendente')),
                'publicacoes_rascunho' => count(array_filter($publicacoes, static fn ($item) => ($item['status_publicacao'] ?? '') === 'rascunho')),
                'balaustres_aptos' => count(array_filter($balaustres, static fn ($item) => ($item['status'] ?? '') === 'apto_votacao')),
                'obreiros_com_alerta' => (int) ($resumoCadastros['com_alerta'] ?? 0),
                'obreiros_com_telegram' => (int) ($resumoCadastros['com_telegram'] ?? 0),
            ];

            JsonResponse::send([
                'ok' => true,
                'resumo' => $resumo,
                'proxima_sessao' => $proximaSessao,
                'sessoes_futuras' => $sessoesFuturas,
                'sessoes_recentes' => $sessoesRecentes,
                'balaustres' => $balaustres,
                'trabalhos' => $trabalhos,
                'publicacoes' => $publicacoes
            ]);
            return true;
        }

        if ($requestUri === '/api/secretaria/convites-externos' && $method === 'GET') {
            $model = new ConviteExterno();
            $convites = $model->listarRecentes(80);
            foreach ($convites as &$convite) {
                $convite['confirmados'] = $model->listarConfirmados((int) $convite['id']);
            }
            unset($convite);
            JsonResponse::send(['ok' => true, 'convites' => $convites]);
            return true;
        }

        if ($requestUri === '/api/secretaria/convites-externos' && $method === 'POST') {
            $body = RequestBody::json();
            $arquivo = is_array($body['anexo'] ?? null) ? $body['anexo'] : [];
            unset($body['anexo']);
            $ok = (new ConviteExterno())->criar($body, $arquivo, (string) $usuarioId);
            JsonResponse::send([
                'ok' => $ok,
                'erro' => $ok ? null : 'Não foi possível registrar o convite externo.',
            ], $ok ? 200 : 400);
            return true;
        }

        if (preg_match('~^/api/secretaria/convites-externos/(\d+)/presenca$~', $requestUri, $m) && $method === 'POST') {
            $body = RequestBody::json();
            $ok = (new ConviteExterno())->definirPresenca(
                (int) $m[1],
                (string) $usuarioId,
                trim((string) ($body['status'] ?? 'pendente'))
            );
            JsonResponse::send(['ok' => $ok], $ok ? 200 : 400);
            return true;
        }

        if (preg_match('~^/api/secretaria/convites-externos/(\d+)/anexo$~', $requestUri, $m) && $method === 'DELETE') {
            $ok = (new ConviteExterno())->removerAnexo((int) $m[1]);
            JsonResponse::send(['ok' => $ok], $ok ? 200 : 400);
            return true;
        }

        // 1.1 Trabalhos e publicacoes - listar pendencias, recentes e sessoes
        if ($requestUri === '/api/secretaria/trabalhos-publicacoes' && $method === 'GET') {
            $pendentes = (new TrabalhoSubmissao())->listarPendentesSecretaria(250);
            $trabalhosRecentes = (new TrabalhoSessao())->listarRecentes(60);
            $sessoes = (new Sessao())->listarRecentes(60);

            JsonResponse::send([
                'ok' => true,
                'pendentes' => $pendentes,
                'trabalhos_recentes' => $trabalhosRecentes,
                'sessoes' => $sessoes,
            ]);
            return true;
        }

        // 1.2 Trabalhos e publicacoes - arquivar submissao
        if ($requestUri === '/api/secretaria/trabalhos-publicacoes/arquivar' && $method === 'POST') {
            $body = RequestBody::json();
            $id = trim((string) ($body['id'] ?? ''));
            $payload = [
                'sessao_id' => (int) ($body['sessao_id'] ?? 0),
                'tipo_trabalho' => trim((string) ($body['tipo_trabalho'] ?? 'peca_arquitetura')) ?: 'peca_arquitetura',
                'titulo' => trim((string) ($body['titulo'] ?? '')),
                'autor_id' => trim((string) ($body['autor_id'] ?? '')) ?: null,
                'arquivo_pdf_path' => trim((string) ($body['arquivo_pdf_path'] ?? '')) ?: null,
                'status_envio_potencia' => trim((string) ($body['status_envio_potencia'] ?? 'pendente')) ?: 'pendente',
                'observacao' => trim((string) ($body['observacao'] ?? '')) ?: null,
            ];

            if ($id === '' || $payload['sessao_id'] <= 0 || $payload['titulo'] === '' || ($payload['arquivo_pdf_path'] ?? '') === null) {
                JsonResponse::send(['ok' => false, 'erro' => 'Preencha sessão, título e arquivo PDF antes de arquivar.']);
                return true;
            }

            $ok = (new TrabalhoSubmissao())->arquivarSecretaria($id, (string) ($usuarioId ?? ''), $payload);

            JsonResponse::send([
                'ok' => $ok,
                'erro' => $ok ? null : 'Não foi possível arquivar agora.'
            ]);
            return true;
        }

        // 1.3 Balaustres em votacao - listar votacoes abertas para o obreiro atual
        if ($requestUri === '/api/secretaria/votacao' && $method === 'GET') {
            $balaustreModel = new Balaustre();
            $usuarioUuidValido = is_string($usuarioId) && (bool) preg_match(
                '/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[1-5][0-9a-fA-F]{3}-[89abAB][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/',
                trim($usuarioId)
            );
            $roles = array_values(array_unique(array_map(
                static fn ($role) => strtolower((string) $role),
                $session['usuario_cargos'] ?? [$session['usuario_cargo'] ?? '']
            )));

            $podeAcompanharTodas = in_array('secretario', $roles, true)
                || in_array('veneravel', $roles, true);

            $votacoesAbertas = $balaustreModel->listarAbertosParaObreiro((string) $usuarioId, $podeAcompanharTodas);
            $votacoesAbertas = array_map(static function (array $row) use ($balaustreModel): array {
                $balaustreCompleto = $balaustreModel->buscarPorId((int) ($row['id'] ?? 0));
                $row['sessao_id'] = (int) ($balaustreCompleto['sessao_id'] ?? 0);
                return $row;
            }, $votacoesAbertas);
            $elegibilidadeVoto = $usuarioUuidValido
                ? $balaustreModel->listarElegibilidadeDoObreiroNosBalaustres(
                    (string) $usuarioId,
                    array_map(static fn ($row) => (int) ($row['id'] ?? 0), $votacoesAbertas)
                )
                : [];

            JsonResponse::send([
                'ok' => true,
                'votacoes_abertas' => $votacoesAbertas,
                'elegibilidade_voto' => $elegibilidadeVoto,
            ]);
            return true;
        }

        // 2. Obreiros - Listar
        if ($requestUri === '/api/secretaria/obreiros' && $method === 'GET') {
            $obreiroModel = new Obreiro();
            $filtros = [
                'busca' => trim((string) ($_GET['busca'] ?? '')),
                'situacao' => trim((string) ($_GET['situacao'] ?? '')),
                'grau' => trim((string) ($_GET['grau'] ?? '')),
                'alerta' => trim((string) ($_GET['alerta'] ?? $_GET['pendencia'] ?? '')),
                'cargo_codigo' => trim((string) ($_GET['cargo_codigo'] ?? '')),
                'ordenacao' => trim((string) ($_GET['ordenacao'] ?? 'nome')),
            ];
            $obreiros = $obreiroModel->listarParaSecretaria($filtros);
            $resumo = $obreiroModel->obterResumoSecretaria($filtros);
            
            // Também retorna cargos para popular combos de filtros
            $cargos = (new Cargo())->listarResumoCargos();

            JsonResponse::send([
                'ok' => true,
                'obreiros' => $obreiros,
                'resumo' => $resumo,
                'cargos' => $cargos
            ]);
            return true;
        }

        // 3. Obreiros - Obter Único
        if (preg_match('~^/api/secretaria/obreiros/([0-9a-fA-F-]{36})$~', $requestUri, $m) && $method === 'GET') {
            $obreiroModel = new Obreiro();
            $obreiro = $obreiroModel->findById($m[1]);
            if ($obreiro !== null) {
                unset(
                    $obreiro['senha'],
                    $obreiro['senha_hash'],
                    $obreiro['password'],
                    $obreiro['password_hash'],
                    $obreiro['auth_user_id']
                );
            }
            JsonResponse::send([
                'ok' => $obreiro !== null,
                'obreiro' => $obreiro
            ]);
            return true;
        }

        // 4. Obreiros - Salvar (Criar ou Atualizar)
        if ($requestUri === '/api/secretaria/obreiros/salvar' && $method === 'POST') {
            $body = RequestBody::json();
            $obreiroModel = new Obreiro();
            
            $isUpdate = !empty($body['id']);
            $ok = $isUpdate ? $obreiroModel->update($body) : $obreiroModel->create($body);
            
            JsonResponse::send([
                'ok' => $ok,
                'erro' => $ok ? null : 'Falha ao salvar dados do obreiro. Verifique se o CIM já existe.'
            ]);
            return true;
        }

        // 5. Obreiros - Inativar
        if ($requestUri === '/api/secretaria/obreiros/inativar' && $method === 'POST') {
            $body = RequestBody::json();
            $id = trim((string) ($body['id'] ?? ''));
            if ($id === '') {
                JsonResponse::send(['ok' => false, 'erro' => 'ID do obreiro não informado.']);
                return true;
            }
            $obreiroModel = new Obreiro();
            $ok = $obreiroModel->inativarPorId($id);
            JsonResponse::send(['ok' => $ok]);
            return true;
        }

        // 6. Obreiros - Excluir
        if (preg_match('~^/api/secretaria/obreiros/([0-9a-fA-F-]{36})$~', $requestUri, $m) && $method === 'DELETE') {
            $obreiroModel = new Obreiro();
            $ok = $obreiroModel->excluirPorId($m[1]);
            JsonResponse::send(['ok' => $ok]);
            return true;
        }

        // 7. Sessões - Listar
        if ($requestUri === '/api/secretaria/sessoes' && $method === 'GET') {
            $sessaoModel = new Sessao();
            $futuras = $sessaoModel->listarFuturas(100);
            $recentes = $sessaoModel->listarRecentes(100);
            JsonResponse::send([
                'ok' => true,
                'futuras' => $futuras,
                'recentes' => $recentes
            ]);
            return true;
        }

        // 8. Sessões - Obter Única (com presenças)
        if (preg_match('~^/api/secretaria/sessoes/(\d+)$~', $requestUri, $m) && $method === 'GET') {
            $sessaoId = (int) $m[1];
            $sessaoModel = new Sessao();
            $sessao = $sessaoModel->findById($sessaoId);
            
            if (!$sessao) {
                JsonResponse::send(['ok' => false, 'erro' => 'Sessão não encontrada.'], 404);
                return true;
            }

            $presencaModel = new Presenca();
            $presencaSessaoModel = new PresencaSessao();

            $confirmados = $presencaModel->listarConfirmadosPorSessao($sessaoId);
            $participantesAgape = $presencaModel->listarParticipantesAgapePorSessao($sessaoId);
            
            // Presenças reais/físicas marcadas
            $mapaPresenca = $presencaSessaoModel->listarMapaPorSessao($sessaoId);
            $presencasAgape = $presencaSessaoModel->listarPresencasAgapePorSessao($sessaoId);

            JsonResponse::send([
                'ok' => true,
                'sessao' => $sessao,
                'confirmados' => $confirmados,
                'participantes_agape' => $participantesAgape,
                'mapa_presenca' => $mapaPresenca,
                'presencas_agape' => $presencasAgape
            ]);
            return true;
        }

        // 9. Sessões - Salvar (Criar ou Atualizar)
        if ($requestUri === '/api/secretaria/sessoes/salvar' && $method === 'POST') {
            $body = RequestBody::json();
            $sessaoModel = new Sessao();
            
            $sessaoId = (int) ($body['id'] ?? 0);
            
            // Ajusta campos de data/hora
            $dataInicio = trim((string) ($body['data_inicio'] ?? ''));
            $horaInicio = trim((string) ($body['hora_inicio'] ?? ''));
            $horaFim = trim((string) ($body['hora_fim'] ?? ''));
            
            $dataHoraInicio = null;
            if ($dataInicio !== '' && $horaInicio !== '') {
                $dataHoraInicio = $dataInicio . ' ' . $horaInicio . ':00';
            }
            
            $dataHoraFim = null;
            if ($dataInicio !== '' && $horaFim !== '') {
                $dataHoraFim = $dataInicio . ' ' . $horaFim . ':00';
            }

            $payload = [
                'data_hora_inicio' => $dataHoraInicio,
                'data_hora_fim' => $dataHoraFim,
                'grau_sessao' => trim((string) ($body['grau_sessao'] ?? '')),
                'grau_personalizado' => trim((string) ($body['grau_personalizado'] ?? '')),
                'tipo_sessao_principal' => trim((string) ($body['tipo_sessao_principal'] ?? '')),
                'tipo_sessao_subtipo' => trim((string) ($body['tipo_sessao_subtipo'] ?? '')),
                'tipo_sessao_personalizado' => trim((string) ($body['tipo_sessao_personalizado'] ?? '')),
                'traje_tipo' => trim((string) ($body['traje_tipo'] ?? '')),
                'traje_personalizado' => trim((string) ($body['traje_personalizado'] ?? '')),
                'agape_modalidade' => trim((string) ($body['agape_modalidade'] ?? 'nao_havera')),
                'agape_modelo_financeiro' => trim((string) ($body['agape_modelo_financeiro'] ?? 'oficial_loja')),
                'agape_valor' => trim((string) ($body['agape_valor'] ?? '')),
                'gestao_referencia' => trim((string) ($body['gestao_referencia'] ?? '')),
                'natureza_sessao' => trim((string) ($body['natureza_sessao'] ?? '')),
                'formato_sessao' => trim((string) ($body['formato_sessao'] ?? '')),
                'finalidade_ritual' => trim((string) ($body['finalidade_ritual'] ?? '')),
                'templo_local' => trim((string) ($body['templo_local'] ?? '')),
                'sessao_branca' => !empty($body['sessao_branca']),
                'sessao_a_campo' => !empty($body['sessao_a_campo']),
                'conta_relatorio_potencia' => !empty($body['conta_relatorio_potencia']),
                'observacao_relatorio' => trim((string) ($body['observacao_relatorio'] ?? '')),
                'titulo' => trim((string) ($body['titulo'] ?? '')),
                'ordem_dia' => trim((string) ($body['ordem_dia'] ?? '')),
                'observacao_interna' => trim((string) ($body['observacao_interna'] ?? '')),
            ];

            if (empty($payload['data_hora_inicio'])) {
                JsonResponse::send(['ok' => false, 'erro' => 'Informe a data e horário de início da sessão.']);
                return true;
            }

            if ($sessaoId > 0) {
                $ok = $sessaoModel->atualizar($sessaoId, $payload, $usuarioId, 'Atualização via API Angular');
            } else {
                $sessaoId = $sessaoModel->criar($payload, $usuarioId);
                $ok = (bool) $sessaoId;
            }

            JsonResponse::send([
                'ok' => $ok,
                'id' => $sessaoId,
                'erro' => $ok ? null : 'Erro ao persistir sessão.'
            ]);
            return true;
        }

        // 10. Sessões - Ações Administrativas (Cancelar, Reabrir, Publicar)
        if (preg_match('~^/api/secretaria/sessoes/(\d+)/action$~', $requestUri, $m) && $method === 'POST') {
            $sessaoId = (int) $m[1];
            $body = RequestBody::json();
            $action = trim((string) ($body['action'] ?? ''));

            $sessaoModel = new Sessao();
            $ok = false;
            $erro = null;

            switch ($action) {
                case 'cancelar':
                    $ok = $sessaoModel->cancelar($sessaoId, $usuarioId, 'Cancelamento via API Angular');
                    break;
                case 'reabrir':
                    $ok = $sessaoModel->reabrir($sessaoId, $usuarioId, 'Reabertura via API Angular');
                    break;
                case 'publicar':
                    $ok = $sessaoModel->marcarPublicada($sessaoId, $usuarioId, 'Publicação via API Angular');
                    if ($ok) {
                        // Também gera publicação automática do resumo
                        $sessao = $sessaoModel->findById($sessaoId);
                        $cfg = (new ConfiguracaoLoja())->obter();
                        $conteudo = $sessaoModel->comporResumoPublicacao($sessao, $cfg);
                        (new PublicacaoSessao())->registrar($sessaoId, 'resumo_proxima_sessao', 'erp', $conteudo, $usuarioId);
                    }
                    break;
                case 'realizar':
                    $ok = $sessaoModel->marcarRealizada($sessaoId, $usuarioId, 'Sessão concluída via API Angular');
                    break;
                default:
                    $erro = 'Ação desconhecida.';
                    break;
            }

            JsonResponse::send([
                'ok' => $ok,
                'erro' => $ok ? null : ($erro ?: 'Falha ao executar ação na sessão.')
            ]);
            return true;
        }

        // 11. Sessões - Salvar Presenças Físicas
        if (preg_match('~^/api/secretaria/sessoes/(\d+)/presenca$~', $requestUri, $m) && $method === 'POST') {
            $sessaoId = (int) $m[1];
            $body = RequestBody::json();
            $presencas = is_array($body['presencas'] ?? null) ? $body['presencas'] : [];

            $presencaSessaoModel = new PresencaSessao();
            $ok = true;

            foreach ($presencas as $item) {
                $obreiroId = trim((string) ($item['obreiro_id'] ?? ''));
                $presente = !empty($item['presente']);
                $observacao = trim((string) ($item['observacao'] ?? '')) ?: null;

                if ($obreiroId !== '') {
                    $res = $presencaSessaoModel->registrar($sessaoId, $obreiroId, $presente, $usuarioId, $observacao);
                    if (!$res) {
                        $ok = false;
                    }
                }
            }

            JsonResponse::send(['ok' => $ok]);
            return true;
        }

        // 12. Sessões - Salvar Presença Ágape
        if (preg_match('~^/api/secretaria/sessoes/(\d+)/agape$~', $requestUri, $m) && $method === 'POST') {
            $sessaoId = (int) $m[1];
            $body = RequestBody::json();
            $presentesAgapeObreiroIds = is_array($body['presentes_agape_obreiro_ids'] ?? null) ? $body['presentes_agape_obreiro_ids'] : [];

            $presencaSessaoModel = new PresencaSessao();
            $ok = $presencaSessaoModel->salvarAgape($sessaoId, $presentesAgapeObreiroIds);

            JsonResponse::send(['ok' => $ok]);
            return true;
        }

        // 13. Balaustres - Obter de uma Sessão
        if (preg_match('~^/api/secretaria/sessoes/(\d+)/balaustre$~', $requestUri, $m) && $method === 'GET') {
            $sessaoId = (int) $m[1];
            $balaustreModel = new Balaustre();
            $balaustre = $balaustreModel->buscarPorSessao($sessaoId);
            
            // Retorna dados hidratados e o texto do Preview se for rascunho
            $preview = '';
            $dados = [];
            if ($balaustre) {
                $capturado = $balaustre['dados_capturados'] ?? null;
                if (is_string($capturado)) {
                    $decoded = json_decode($capturado, true);
                    $dados = is_array($decoded) ? $decoded : [];
                } else {
                    $dados = is_array($capturado) ? $capturado : [];
                }
                $preview = trim((string) ($balaustre['texto_final'] ?? ''));
                if ($preview === '' && $dados !== []) {
                    $preview = \App\Models\BalaustreComposer::build($dados);
                }
            }

            JsonResponse::send([
                'ok' => true,
                'balaustre' => $balaustre,
                'dados_capturados' => $dados,
                'preview' => $preview
            ]);
            return true;
        }

        // 14. Balaustres - Salvar
        if (preg_match('~^/api/secretaria/sessoes/(\d+)/balaustre/salvar$~', $requestUri, $m) && $method === 'POST') {
            $sessaoId = (int) $m[1];
            $body = RequestBody::json();
            $balaustreModel = new Balaustre();
            
            $ok = $balaustreModel->salvarPorSessao($sessaoId, $body, $usuarioId);
            
            JsonResponse::send([
                'ok' => $ok,
                'erro' => $ok ? null : 'Não foi possível salvar o balaustre.'
            ]);
            return true;
        }

        // 15. Balaustres - Ações (Apto Votação, Abrir Votação, Encerrar Votação)
        if (preg_match('~^/api/secretaria/sessoes/(\d+)/balaustre/action$~', $requestUri, $m) && $method === 'POST') {
            $sessaoId = (int) $m[1];
            $body = RequestBody::json();
            $action = trim((string) ($body['action'] ?? ''));

            $balaustreModel = new Balaustre();
            $balaustre = $balaustreModel->buscarPorSessao($sessaoId);

            if (!$balaustre) {
                JsonResponse::send(['ok' => false, 'erro' => 'Balaustre não encontrado para esta sessão.']);
                return true;
            }

            $balaustreId = (int) $balaustre['id'];
            $ok = false;
            $erro = null;

            if ($action === 'marcar-apto') {
                $valida = $balaustreModel->validarParaApto($balaustreId);
                if (!$valida['ok']) {
                    JsonResponse::send(['ok' => false, 'erro' => $valida['erro']]);
                    return true;
                }
                $ok = $balaustreModel->marcarAptoVotacao($balaustreId, $usuarioId);
            } elseif ($action === 'abrir-votacao') {
                $res = $balaustreModel->abrirVotacao($balaustreId, $usuarioId);
                $ok = !empty($res['ok']);
                $erro = $res['erro'] ?? null;
            } elseif ($action === 'encerrar-votacao') {
                $res = $balaustreModel->encerrarVotacaoPorBalaustre($balaustreId);
                $ok = !empty($res['ok']);
                $erro = $res['erro'] ?? null;
            }

            JsonResponse::send([
                'ok' => $ok,
                'erro' => $ok ? null : ($erro ?: 'Falha ao executar ação no balaustre.')
            ]);
            return true;
        }

        // 16. Balaustres - Registrar Voto (pode ser usado por qualquer Obreiro autenticado)
        if (preg_match('~^/api/secretaria/sessoes/(\d+)/balaustre/votar$~', $requestUri, $m) && $method === 'POST') {
            // Esse endpoint em particular é usado por qualquer obreiro logado na votação de atas.
            // Sobrescreve permissão genérica da secretaria para permitir obreiro comum logado
            $sessaoId = (int) $m[1];
            $body = RequestBody::json();
            $voto = trim((string) ($body['voto'] ?? ''));
            $justificativa = trim((string) ($body['justificativa'] ?? '')) ?: null;

            $balaustreModel = new Balaustre();
            $balaustre = $balaustreModel->buscarPorSessao($sessaoId);
            if (!$balaustre) {
                JsonResponse::send(['ok' => false, 'erro' => 'Balaustre não encontrado.']);
                return true;
            }

            $res = $balaustreModel->registrarVotoPorBalaustre((int)$balaustre['id'], $usuarioId, $voto, $justificativa);
            JsonResponse::send($res);
            return true;
        }

        return false;
    }
}
