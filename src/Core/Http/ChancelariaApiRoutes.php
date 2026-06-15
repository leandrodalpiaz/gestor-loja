<?php

namespace App\Core\Http;

use App\Models\Balaustre;
use App\Models\EfemerideRegistro;
use App\Models\Obreiro;
use App\Models\Presenca;
use App\Models\PresencaSessao;
use App\Models\Sessao;

class ChancelariaApiRoutes
{
    /**
     * Tipos de efeméride válidos.
     */
    private const TIPOS_VALIDOS = [
        'Aniversario',
        'Iniciacao',
        'Elevacao',
        'Exaltacao',
        'Instalacao',
        'Oriente Eterno',
        'Historia',
        'Posse Grao Mestre',
        'Concessao de Membro Honorario',
        'Filiacao',
    ];

    public static function dispatch(
        string $requestUri,
        string $method,
        array $session,
        callable $requireChancelariaApiAccess
    ): bool {
        if (!str_starts_with($requestUri, '/api/chancelaria/')) {
            return false;
        }

        header('Content-Type: application/json; charset=utf-8');
        $requireChancelariaApiAccess();
        $autorId = trim((string) ($session['usuario_id'] ?? '')) ?: null;

        if ($requestUri === '/api/chancelaria/sessao' && $method === 'GET') {
            $sessaoModel = new Sessao();
            $sessaoId = (int) ($_GET['sessao_id'] ?? 0);
            $data = trim((string) ($_GET['data_sessao'] ?? ''));
            $sessao = $sessaoId > 0 ? $sessaoModel->findById($sessaoId) : null;

            if (!$sessao && $data !== '') {
                $sessao = $sessaoModel->obterOuCriarControleChancelerPorData($data, $autorId);
            }
            if (!$sessao) {
                $sessao = $sessaoModel->obterProximaSessao();
            }

            $sessoes = $sessaoModel->listarFuturas(8);
            if ($sessao && !empty($sessao['id'])) {
                $sessaoJaListada = array_filter(
                    $sessoes,
                    static fn(array $item): bool => (int) ($item['id'] ?? 0) === (int) $sessao['id']
                );
                if ($sessaoJaListada === []) {
                    array_unshift($sessoes, $sessao);
                }
            }
            $presencas = [];
            $confirmados = [];
            $visitantes = [];
            if ($sessao && !empty($sessao['id'])) {
                $id = (int) $sessao['id'];
                $presencas = (new PresencaSessao())->listarMapaPorSessao($id);
                $confirmados = (new Presenca())->listarConfirmadosPorSessao($id);
                $visitantes = (new Balaustre())->obterResumoVisitantesPorSessao($id);
            }
            if ($presencas === []) {
                $presencas = array_map(static fn(array $item): array => [
                    'id' => (string) ($item['id'] ?? ''),
                    'nome' => (string) ($item['nome_historico'] ?? $item['nome'] ?? 'Obreiro'),
                    'cim' => (string) ($item['cim'] ?? ''),
                    'grau' => (string) ($item['grau'] ?? ''),
                    'presente' => false,
                    'observacao' => null,
                ], (new Obreiro())->getAllAtivos());
            }

            JsonResponse::send([
                'ok' => true,
                'sessao' => $sessao,
                'sessoes' => $sessoes,
                'presencas' => $presencas,
                'confirmados' => $confirmados,
                'visitantes' => $visitantes,
            ]);
            return true;
        }

        if ($requestUri === '/api/chancelaria/sessao/presenca' && $method === 'POST') {
            $body = RequestBody::json();
            $sessaoId = (int) ($body['sessao_id'] ?? 0);
            $obreiroId = trim((string) ($body['obreiro_id'] ?? ''));
            if ($sessaoId <= 0 || !preg_match('/^[0-9a-f-]{36}$/i', $obreiroId)) {
                JsonResponse::send(['ok' => false, 'erro' => 'Sessão ou obreiro inválido.']);
                return true;
            }
            $ok = (new PresencaSessao())->registrar(
                $sessaoId,
                $obreiroId,
                filter_var($body['presente'] ?? false, FILTER_VALIDATE_BOOL),
                $autorId,
                trim((string) ($body['observacao'] ?? '')) ?: null
            );
            JsonResponse::send(['ok' => $ok, 'erro' => $ok ? null : 'Não foi possível atualizar a presença.']);
            return true;
        }

        if ($requestUri === '/api/chancelaria/sessao/visitante' && $method === 'POST') {
            $body = RequestBody::json();
            $sessaoId = (int) ($body['sessao_id'] ?? 0);
            if ($sessaoId <= 0 || trim((string) ($body['nome'] ?? '')) === '') {
                JsonResponse::send(['ok' => false, 'erro' => 'Informe a sessão e o nome do visitante.']);
                return true;
            }
            $ok = (new Balaustre())->adicionarVisitanteSessao($sessaoId, $body, $autorId);
            JsonResponse::send(['ok' => $ok, 'erro' => $ok ? null : 'Não foi possível registrar o visitante.']);
            return true;
        }

        if (preg_match('~^/api/chancelaria/sessao/(\d+)/(cancelar|excluir)$~', $requestUri, $m) && $method === 'POST') {
            $sessaoId = (int) $m[1];
            $sessaoModel = new Sessao();
            $ok = $m[2] === 'cancelar'
                ? $sessaoModel->cancelar($sessaoId, $autorId, 'Cancelamento solicitado no painel Angular da Chancelaria.')
                : $sessaoModel->excluir($sessaoId);
            JsonResponse::send(['ok' => $ok, 'erro' => $ok ? null : 'Não foi possível concluir a operação.']);
            return true;
        }

        // --- GET /api/chancelaria/efemerides/hoje ---
        if ($requestUri === '/api/chancelaria/efemerides/hoje' && $method === 'GET') {
            $model = new EfemerideRegistro();
            $registros = $model->getRegistrosDoDia();
            JsonResponse::send(['ok' => true, 'registros' => $registros]);
            return true;
        }

        // --- GET /api/chancelaria/efemerides ---
        if ($requestUri === '/api/chancelaria/efemerides' && $method === 'GET') {
            $filtros = [
                'termo'    => trim((string) ($_GET['termo'] ?? '')),
                'tipo'     => trim((string) ($_GET['tipo'] ?? '')),
                'ativo'    => trim((string) ($_GET['ativo'] ?? '1')),
                'data_ini' => trim((string) ($_GET['data_ini'] ?? '')),
                'data_fim' => trim((string) ($_GET['data_fim'] ?? '')),
            ];

            $model = new EfemerideRegistro();
            $registros = $model->buscarComFiltros($filtros);
            JsonResponse::send(['ok' => true, 'registros' => $registros]);
            return true;
        }

        // --- POST /api/chancelaria/efemerides/salvar ---
        if ($requestUri === '/api/chancelaria/efemerides/salvar' && $method === 'POST') {
            $body = RequestBody::json();

            $nome = trim((string) ($body['nome'] ?? ''));
            if ($nome === '') {
                JsonResponse::send(['ok' => false, 'erro' => 'O campo Nome é obrigatório.']);
                return true;
            }

            $tipo = trim((string) ($body['tipo'] ?? ''));
            if (!in_array($tipo, self::TIPOS_VALIDOS, true)) {
                JsonResponse::send(['ok' => false, 'erro' => 'Tipo de efeméride inválido.']);
                return true;
            }

            $dataEvento = trim((string) ($body['data_evento'] ?? ''));
            if ($dataEvento === '') {
                JsonResponse::send(['ok' => false, 'erro' => 'A data do evento é obrigatória.']);
                return true;
            }

            $dados = [
                'nome'           => $nome,
                'tipo'           => $tipo,
                'data_evento'    => $dataEvento,
                'vinculo'        => trim((string) ($body['vinculo'] ?? '')) ?: null,
                'parentesco'     => trim((string) ($body['parentesco'] ?? '')) ?: null,
                'local'          => trim((string) ($body['local'] ?? '')) ?: null,
                'mensagem_custom' => trim((string) ($body['mensagem_custom'] ?? '')) ?: null,
            ];

            $model = new EfemerideRegistro();
            $createdBy = $autorId !== null && ctype_digit($autorId) ? (int) $autorId : null;
            $ok = $model->create($dados, $createdBy);
            JsonResponse::send([
                'ok'  => (bool) $ok,
                'erro' => $ok ? null : 'Falha ao criar efeméride.',
            ]);
            return true;
        }

        // --- POST /api/chancelaria/efemerides/atualizar ---
        if ($requestUri === '/api/chancelaria/efemerides/atualizar' && $method === 'POST') {
            $body = RequestBody::json();

            $id = (int) ($body['id'] ?? 0);
            if ($id <= 0) {
                JsonResponse::send(['ok' => false, 'erro' => 'ID da efeméride não informado.']);
                return true;
            }

            $nome = trim((string) ($body['nome'] ?? ''));
            if ($nome === '') {
                JsonResponse::send(['ok' => false, 'erro' => 'O campo Nome é obrigatório.']);
                return true;
            }

            $tipo = trim((string) ($body['tipo'] ?? ''));
            if (!in_array($tipo, self::TIPOS_VALIDOS, true)) {
                JsonResponse::send(['ok' => false, 'erro' => 'Tipo de efeméride inválido.']);
                return true;
            }

            $dados = [
                'id'             => $id,
                'nome'           => $nome,
                'tipo'           => $tipo,
                'data_evento'    => trim((string) ($body['data_evento'] ?? '')),
                'vinculo'        => trim((string) ($body['vinculo'] ?? '')) ?: null,
                'parentesco'     => trim((string) ($body['parentesco'] ?? '')) ?: null,
                'local'          => trim((string) ($body['local'] ?? '')) ?: null,
                'mensagem_custom' => trim((string) ($body['mensagem_custom'] ?? '')) ?: null,
            ];

            $model = new EfemerideRegistro();
            $ok = $model->atualizar($id, $dados);
            JsonResponse::send([
                'ok'  => (bool) $ok,
                'erro' => $ok ? null : 'Falha ao atualizar efeméride.',
            ]);
            return true;
        }

        // --- POST /api/chancelaria/efemerides/desativar ---
        if ($requestUri === '/api/chancelaria/efemerides/desativar' && $method === 'POST') {
            $body = RequestBody::json();
            $id = (int) ($body['id'] ?? 0);
            if ($id <= 0) {
                JsonResponse::send(['ok' => false, 'erro' => 'ID da efeméride não informado.']);
                return true;
            }

            $model = new EfemerideRegistro();
            $ok = $model->desativar($id);
            JsonResponse::send(['ok' => (bool) $ok]);
            return true;
        }

        // --- POST /api/chancelaria/efemerides/excluir ---
        if ($requestUri === '/api/chancelaria/efemerides/excluir' && $method === 'POST') {
            $body = RequestBody::json();
            $id = (int) ($body['id'] ?? 0);
            if ($id <= 0) {
                JsonResponse::send(['ok' => false, 'erro' => 'ID da efeméride não informado.']);
                return true;
            }

            $model = new EfemerideRegistro();
            $ok = $model->excluir($id);
            JsonResponse::send(['ok' => (bool) $ok]);
            return true;
        }

        // --- POST /api/chancelaria/certificado/gerar ---
        if ($requestUri === '/api/chancelaria/certificado/gerar' && $method === 'POST') {
            $body = RequestBody::json();

            $nome = trim((string) ($body['nome_visitante'] ?? ''));
            $loja = trim((string) ($body['loja_visitante'] ?? ''));
            $oriente = trim((string) ($body['oriente'] ?? ''));
            $tipoSessao = trim((string) ($body['tipo_sessao'] ?? ''));
            $grauSessao = trim((string) ($body['grau_sessao'] ?? ''));
            $dataSessao = trim((string) ($body['data_sessao'] ?? ''));
            $chatId = trim((string) ($body['chat_id'] ?? ''));

            if ($nome === '' || $loja === '' || $oriente === '' || $tipoSessao === '' || $grauSessao === '' || $dataSessao === '') {
                JsonResponse::send(['ok' => false, 'erro' => 'Todos os campos obrigatórios (*) devem ser preenchidos.']);
                return true;
            }

            try {
                require_once __DIR__ . '/../../Services/CertificadoGenerator.php';
                $generator = new \App\Services\CertificadoGenerator();
                $caminhoImagem = $generator->gerar($nome, $loja, $oriente, $tipoSessao, $grauSessao, $dataSessao);

                if (!empty($chatId)) {
                    require_once __DIR__ . '/../../Bot/TelegramClient.php';
                    $telegram = new \App\Bot\TelegramClient();
                    $telegram->sendPhoto($chatId, $caminhoImagem, "Certificado gerado com sucesso!\n\nAgora é só encaminhar para o Irmão {$nome}.");
                }

                $relativeUrl = '/temp/' . basename($caminhoImagem);
                JsonResponse::send([
                    'ok' => true,
                    'caminho_imagem' => $relativeUrl,
                    'mensagem' => 'Certificado gerado com sucesso!' . (!empty($chatId) ? ' Enviado também via Telegram.' : '')
                ]);
            } catch (\Exception $e) {
                JsonResponse::send([
                    'ok' => false,
                    'erro' => 'Erro ao gerar o certificado: ' . $e->getMessage()
                ]);
            }
            return true;
        }

        // --- GET /api/chancelaria/efemerides/dashboard ---
        if ($requestUri === '/api/chancelaria/efemerides/dashboard' && $method === 'GET') {
            $tz = new \DateTimeZone(trim((string) ($_ENV['APP_TIMEZONE'] ?? 'America/Sao_Paulo')));
            $dtHoje = new \DateTimeImmutable('today', $tz);
            $hojeRef = $dtHoje->format('Y-m-d');
            $diaHoje = (int) $dtHoje->format('d');
            $mesHoje = (int) $dtHoje->format('m');

            $registroModel = new \App\Models\EfemerideRegistro();
            $previaModel = new \App\Models\EfemeridePreviaDiaria();
            $composer = new \App\Services\EfemeridesComposer();
            $historiaModel = new \App\Models\HistoriaMaconica();
            $obreiroModel = new \App\Models\Obreiro();

            $registrosHoje = $registroModel->getRegistrosDoDia();
            try {
                $historiasHoje = $historiaModel->buscarPorDiaMes($diaHoje, $mesHoje, true);
                foreach ($historiasHoje as $hist) {
                    $ano = $hist['ano_ref'] ?? $dtHoje->format('Y');
                    $registrosHoje[] = [
                        'id' => (int) ($hist['id'] ?? 0),
                        'nome' => trim((string) ($hist['titulo'] ?? 'Nossa História')),
                        'tipo' => 'História',
                        'data_evento' => sprintf('%04d-%02d-%02d', $ano, $mesHoje, $diaHoje),
                        'mensagem_custom' => trim((string) ($hist['texto'] ?? '')),
                        'local' => trim((string) ($hist['fonte'] ?? '')),
                        'vinculo' => 'Nossa História',
                    ];
                }
            } catch (\Throwable $e) {
                error_log('Erro ao injetar historias no dashboard: ' . $e->getMessage());
            }

            try {
                $previaCardModel = new \App\Models\EfemerideCardPrevia();
                $overrides = $previaCardModel->findByDate($hojeRef);
                $mapOverrides = [];
                foreach ($overrides as $ov) {
                    $rid = (int) ($ov['registro_id'] ?? 0);
                    if ($rid > 0 && !empty($ov['texto_custom_card'])) {
                        $mapOverrides[$rid] = trim((string) $ov['texto_custom_card']);
                    }
                }
                if ($mapOverrides !== []) {
                    foreach ($registrosHoje as &$regRef) {
                        $rid = (int) ($regRef['id'] ?? 0);
                        if ($rid > 0 && isset($mapOverrides[$rid])) {
                            $regRef['mensagem_custom'] = $mapOverrides[$rid];
                        }
                    }
                    unset($regRef);
                }
            } catch (\Throwable $e) {
                error_log('Falha ao aplicar overrides no dashboard: ' . $e->getMessage());
            }

            $mensagemBase = $composer->composeDailyPreview($registrosHoje);
            $mensagemPreview = $previaModel->garantirPreviaDoDia($mensagemBase);

            $cardService = new \App\Services\EfemeridesCardService();
            $cards = $registrosHoje !== [] ? $cardService->buildCardsForDate($hojeRef, $registrosHoje) : [];
            $categoriasCards = array_values(array_unique(array_filter(array_map(static fn($r) => strtolower(trim((string) ($r['vinculo'] ?? $r['tipo'] ?? ''))), $registrosHoje))));

            $registrosRecentes = $registroModel->buscarComFiltros([], 300);
            $vinculosPadrao = $registroModel->getVinculosPadrao();
            $obreirosList = $obreiroModel->getAllAtivos();
            $historiasRecentes = $historiaModel->listar([], 300);

            JsonResponse::send([
                'ok' => true,
                'registrosHoje' => $registrosHoje,
                'mensagemBase' => $mensagemBase,
                'mensagemPreview' => $mensagemPreview,
                'cards' => $cards,
                'categoriasCards' => $categoriasCards,
                'registrosRecentes' => $registrosRecentes,
                'vinculosPadrao' => $vinculosPadrao,
                'obreiros' => $obreirosList,
                'historias' => $historiasRecentes,
                'tiposEfemeride' => self::TIPOS_VALIDOS,
            ]);
            return true;
        }

        // --- POST /api/chancelaria/efemerides/salvar-previa ---
        if ($requestUri === '/api/chancelaria/efemerides/salvar-previa' && $method === 'POST') {
            $body = RequestBody::json();
            $mensagem = trim((string) ($body['mensagem_preview'] ?? ''));
            $tz = new \DateTimeZone(trim((string) ($_ENV['APP_TIMEZONE'] ?? 'America/Sao_Paulo')));
            $hojeRef = (new \DateTimeImmutable('today', $tz))->format('Y-m-d');
            $ok = (new \App\Models\EfemeridePreviaDiaria())->salvarOuAtualizar($hojeRef, $mensagem, false);
            JsonResponse::send(['ok' => $ok, 'erro' => $ok ? null : 'Não foi possível salvar a prévia.']);
            return true;
        }

        // --- POST /api/chancelaria/efemerides/enviar-previa ---
        if ($requestUri === '/api/chancelaria/efemerides/enviar-previa' && $method === 'POST') {
            $tz = new \DateTimeZone(trim((string) ($_ENV['APP_TIMEZONE'] ?? 'America/Sao_Paulo')));
            $hojeRef = (new \DateTimeImmutable('today', $tz))->format('Y-m-d');
            $previa = (new \App\Models\EfemeridePreviaDiaria())->findByDate($hojeRef);
            $mensagem = trim((string) ($previa['mensagem_previa'] ?? ''));
            if ($mensagem === '') {
                JsonResponse::send(['ok' => false, 'erro' => 'Mensagem de prévia está vazia.']);
                return true;
            }
            $telegramService = new \App\Services\TelegramService();
            $chatPrivadoDestino = trim((string) ($session['usuario_logado']['telegram_id'] ?? ''));
            if ($chatPrivadoDestino === '') {
                $chatPrivadoDestino = trim((string) ($_ENV['TELEGRAM_CHAT_ID_CHANCELER'] ?? ''));
            }
            $ok = $telegramService->sendMessageToChat($chatPrivadoDestino, $mensagem);
            JsonResponse::send(['ok' => $ok, 'erro' => $ok ? null : 'Falha ao enviar prévia: ' . $telegramService->getLastError()]);
            return true;
        }

        // --- POST /api/chancelaria/efemerides/enviar-grupo ---
        if ($requestUri === '/api/chancelaria/efemerides/enviar-grupo' && $method === 'POST') {
            $tz = new \DateTimeZone(trim((string) ($_ENV['APP_TIMEZONE'] ?? 'America/Sao_Paulo')));
            $hojeRef = (new \DateTimeImmutable('today', $tz))->format('Y-m-d');
            $previa = (new \App\Models\EfemeridePreviaDiaria())->findByDate($hojeRef);
            $mensagem = trim((string) ($previa['mensagem_previa'] ?? ''));
            if ($mensagem === '') {
                JsonResponse::send(['ok' => false, 'erro' => 'Mensagem de prévia está vazia.']);
                return true;
            }
            $telegramService = new \App\Services\TelegramService();
            $ok = $telegramService->sendMessageToGroup($mensagem);
            JsonResponse::send(['ok' => $ok, 'erro' => $ok ? null : 'Falha ao enviar mensagem no grupo: ' . $telegramService->getLastError()]);
            return true;
        }

        // --- POST /api/chancelaria/efemerides/aprovar-e-enviar-tudo ---
        if ($requestUri === '/api/chancelaria/efemerides/aprovar-e-enviar-tudo' && $method === 'POST') {
            $tz = new \DateTimeZone(trim((string) ($_ENV['APP_TIMEZONE'] ?? 'America/Sao_Paulo')));
            $dtHoje = new \DateTimeImmutable('today', $tz);
            $hojeRef = $dtHoje->format('Y-m-d');
            $diaHoje = (int) $dtHoje->format('d');
            $mesHoje = (int) $dtHoje->format('m');

            $previaModel = new \App\Models\EfemeridePreviaDiaria();
            $registroModel = new \App\Models\EfemerideRegistro();
            $historiaModel = new \App\Models\HistoriaMaconica();

            $previa = $previaModel->findByDate($hojeRef);
            $mensagem = trim((string) ($previa['mensagem_previa'] ?? ''));

            if ($mensagem === '') {
                JsonResponse::send(['ok' => false, 'erro' => 'Mensagem de prévia está vazia.']);
                return true;
            }

            $previaModel->salvarOuAtualizar($hojeRef, $mensagem, true);

            $registrosHoje = $registroModel->getRegistrosDoDia();
            try {
                $historiasHoje = $historiaModel->buscarPorDiaMes($diaHoje, $mesHoje, true);
                foreach ($historiasHoje as $hist) {
                    $ano = $hist['ano_ref'] ?? $dtHoje->format('Y');
                    $registrosHoje[] = [
                        'id' => (int) ($hist['id'] ?? 0),
                        'nome' => trim((string) ($hist['titulo'] ?? 'Nossa História')),
                        'tipo' => 'História',
                        'data_evento' => sprintf('%04d-%02d-%02d', $ano, $mesHoje, $diaHoje),
                        'mensagem_custom' => trim((string) ($hist['texto'] ?? '')),
                        'local' => trim((string) ($hist['fonte'] ?? '')),
                        'vinculo' => 'Nossa História',
                    ];
                }
            } catch (\Throwable $e) {}

            try {
                $previaCardModel = new \App\Models\EfemerideCardPrevia();
                $overrides = $previaCardModel->findByDate($hojeRef);
                $mapOverrides = [];
                foreach ($overrides as $ov) {
                    $rid = (int) ($ov['registro_id'] ?? 0);
                    if ($rid > 0 && !empty($ov['texto_custom_card'])) {
                        $mapOverrides[$rid] = trim((string) $ov['texto_custom_card']);
                    }
                }
                if ($mapOverrides !== []) {
                    foreach ($registrosHoje as &$regRef) {
                        $rid = (int) ($regRef['id'] ?? 0);
                        if ($rid > 0 && isset($mapOverrides[$rid])) {
                            $regRef['mensagem_custom'] = $mapOverrides[$rid];
                        }
                    }
                    unset($regRef);
                }
            } catch (\Throwable $e) {}

            $cardService = new \App\Services\EfemeridesCardService();
            $listaCards = $registrosHoje !== [] ? $cardService->buildCardsForDate($hojeRef, $registrosHoje) : [];

            $telegram = new \App\Services\TelegramService();
            $okMsg = $telegram->sendMessageToGroup($mensagem);

            $errosFotos = 0;
            $totalFotos = count($listaCards);
            if ($okMsg && $totalFotos > 0) {
                foreach ($listaCards as $c) {
                    $absPath = \App\Services\EfemeridesCardService::resolveLocalPath($c['card_path'] ?? '');
                    if ($absPath !== '' && file_exists($absPath)) {
                        $desc = $c['titulo'] ?? $c['descricao'] ?? 'Efeméride';
                        if (!$telegram->sendPhotoToGroup($absPath, "🖼 *Card:* " . $desc)) {
                            $errosFotos++;
                        }
                    } else {
                        $errosFotos++;
                    }
                }
            }

            JsonResponse::send([
                'ok' => $okMsg,
                'total_cards' => $totalFotos,
                'cards_enviados' => ($totalFotos - $errosFotos),
                'erro' => $okMsg ? null : 'Erro ao enviar no grupo: ' . $telegram->getLastError()
            ]);
            return true;
        }

        // --- POST /api/chancelaria/efemerides/cards-aprovar-todos ---
        if ($requestUri === '/api/chancelaria/efemerides/cards-aprovar-todos' && $method === 'POST') {
            $tz = new \DateTimeZone(trim((string) ($_ENV['APP_TIMEZONE'] ?? 'America/Sao_Paulo')));
            $dtHoje = new \DateTimeImmutable('today', $tz);
            $hojeRef = $dtHoje->format('Y-m-d');
            $diaHoje = (int) $dtHoje->format('d');
            $mesHoje = (int) $dtHoje->format('m');

            $registroModel = new \App\Models\EfemerideRegistro();
            $historiaModel = new \App\Models\HistoriaMaconica();

            $registrosHoje = $registroModel->getRegistrosDoDia();
            try {
                $historiasHoje = $historiaModel->buscarPorDiaMes($diaHoje, $mesHoje, true);
                foreach ($historiasHoje as $hist) {
                    $ano = $hist['ano_ref'] ?? $dtHoje->format('Y');
                    $registrosHoje[] = [
                        'id' => (int) ($hist['id'] ?? 0),
                        'nome' => trim((string) ($hist['titulo'] ?? 'Nossa História')),
                        'tipo' => 'História',
                        'data_evento' => sprintf('%04d-%02d-%02d', $ano, $mesHoje, $diaHoje),
                        'mensagem_custom' => trim((string) ($hist['texto'] ?? '')),
                        'local' => trim((string) ($hist['fonte'] ?? '')),
                        'vinculo' => 'Nossa História',
                    ];
                }
            } catch (\Throwable $e) {}

            try {
                $previaCardModel = new \App\Models\EfemerideCardPrevia();
                $overrides = $previaCardModel->findByDate($hojeRef);
                $mapOverrides = [];
                foreach ($overrides as $ov) {
                    $rid = (int) ($ov['registro_id'] ?? 0);
                    if ($rid > 0 && !empty($ov['texto_custom_card'])) {
                        $mapOverrides[$rid] = trim((string) $ov['texto_custom_card']);
                    }
                }
                if ($mapOverrides !== []) {
                    foreach ($registrosHoje as &$regRef) {
                        $rid = (int) ($regRef['id'] ?? 0);
                        if ($rid > 0 && isset($mapOverrides[$rid])) {
                            $regRef['mensagem_custom'] = $mapOverrides[$rid];
                        }
                    }
                    unset($regRef);
                }
            } catch (\Throwable $e) {}

            $cardService = new \App\Services\EfemeridesCardService();
            $cards = $registrosHoje !== [] ? $cardService->buildCardsForDate($hojeRef, $registrosHoje) : [];
            JsonResponse::send(['ok' => true, 'total' => count($cards)]);
            return true;
        }

        // --- POST /api/chancelaria/efemerides/cards-configurar ---
        if ($requestUri === '/api/chancelaria/efemerides/cards-configurar' && $method === 'POST') {
            $body = RequestBody::json();
            $registroId = (int) ($body['registro_id'] ?? 0);
            $ocultarIdade = !empty($body['ocultar_idade']);
            $textoCustom = trim((string) ($body['texto_custom_card'] ?? ''));
            $templateCard = trim((string) ($body['template_card'] ?? ''));

            $tz = new \DateTimeZone(trim((string) ($_ENV['APP_TIMEZONE'] ?? 'America/Sao_Paulo')));
            $hojeRef = (new \DateTimeImmutable('today', $tz))->format('Y-m-d');

            $registro = (new \App\Models\EfemerideRegistro())->findById($registroId, true);
            if (!$registro) {
                $hist = (new \App\Models\HistoriaMaconica())->findById($registroId);
                if ($hist) {
                    $registro = [
                        'id' => (int) $hist['id'],
                        'nome' => trim((string) ($hist['titulo'] ?? '')),
                        'tipo' => 'História',
                        'data_evento' => sprintf('%04d-%02d-%02d', $hist['ano_ref'] ?? date('Y'), $hist['mes'], $hist['dia']),
                        'mensagem_custom' => trim((string) ($hist['texto'] ?? '')),
                        'local' => trim((string) ($hist['fonte'] ?? '')),
                        'vinculo' => 'Nossa História',
                    ];
                }
            }

            if (!$registro) {
                JsonResponse::send(['ok' => false, 'erro' => 'Registro de efeméride não encontrado.']);
                return true;
            }

            $service = new \App\Services\EfemeridesCardService();
            (new \App\Models\EfemerideCardPrevia())->upsert($hojeRef, $registroId, [
                'ocultar_idade' => $ocultarIdade,
                'texto_custom_card' => $textoCustom !== '' ? $textoCustom : null,
                'template_card' => $templateCard !== '' ? $templateCard : null
            ]);

            $card = $service->buildCardForRegistro(
                $hojeRef,
                $registro,
                $ocultarIdade,
                $textoCustom !== '' ? $textoCustom : null,
                $templateCard !== '' ? $templateCard : null
            );

            JsonResponse::send(['ok' => true, 'card' => $card]);
            return true;
        }

        // --- POST /api/chancelaria/efemerides/cards-template-categorias ---
        if ($requestUri === '/api/chancelaria/efemerides/cards-template-categorias' && $method === 'POST') {
            $body = RequestBody::json();
            $categorias = isset($body['categorias']) && is_array($body['categorias']) ? $body['categorias'] : [];
            $template = trim((string) ($body['template_slug'] ?? ''));
            if ($template === '' || $categorias === []) {
                JsonResponse::send(['ok' => false, 'erro' => 'Categorias ou template inválido.']);
                return true;
            }
            $model = new \App\Models\EfemerideCardCategoriaTemplate();
            $ok = true;
            foreach ($categorias as $categoria) {
                $cat = trim((string) $categoria);
                if ($cat === '') continue;
                $ok = $ok && $model->salvar($cat, $template);
            }
            JsonResponse::send(['ok' => $ok, 'erro' => $ok ? null : 'Não foi possível salvar o template das categorias.']);
            return true;
        }

        // --- POST /api/chancelaria/historias/salvar ---
        if ($requestUri === '/api/chancelaria/historias/salvar' && $method === 'POST') {
            $body = RequestBody::json();
            $titulo = trim((string) ($body['titulo'] ?? ''));
            $texto = trim((string) ($body['texto'] ?? ''));
            $dia = (int) ($body['dia'] ?? 0);
            $mes = (int) ($body['mes'] ?? 0);
            if ($titulo === '' || $texto === '' || $dia <= 0 || $mes <= 0) {
                JsonResponse::send(['ok' => false, 'erro' => 'Título, texto, dia e mês são obrigatórios.']);
                return true;
            }
            $model = new \App\Models\HistoriaMaconica();
            $ok = $model->create([
                'titulo' => $titulo,
                'texto' => $texto,
                'dia' => $dia,
                'mes' => $mes,
                'ano_ref' => !empty($body['ano_ref']) ? (int) $body['ano_ref'] : null,
                'fonte' => trim((string) ($body['fonte'] ?? '')) ?: null,
            ], $autorId ? (int) $autorId : null);
            JsonResponse::send(['ok' => (bool)$ok, 'erro' => $ok ? null : 'Não foi possível salvar a história.']);
            return true;
        }

        // --- POST /api/chancelaria/historias/atualizar ---
        if ($requestUri === '/api/chancelaria/historias/atualizar' && $method === 'POST') {
            $body = RequestBody::json();
            $id = (int) ($body['id'] ?? 0);
            $titulo = trim((string) ($body['titulo'] ?? ''));
            $texto = trim((string) ($body['texto'] ?? ''));
            $dia = (int) ($body['dia'] ?? 0);
            $mes = (int) ($body['mes'] ?? 0);
            if ($id <= 0 || $titulo === '' || $texto === '' || $dia <= 0 || $mes <= 0) {
                JsonResponse::send(['ok' => false, 'erro' => 'Campos obrigatórios inválidos.']);
                return true;
            }
            $model = new \App\Models\HistoriaMaconica();
            $ok = $model->atualizar($id, [
                'titulo' => $titulo,
                'texto' => $texto,
                'dia' => $dia,
                'mes' => $mes,
                'ano_ref' => !empty($body['ano_ref']) ? (int) $body['ano_ref'] : null,
                'fonte' => trim((string) ($body['fonte'] ?? '')) ?: null,
            ]);
            JsonResponse::send(['ok' => (bool)$ok, 'erro' => $ok ? null : 'Não foi possível atualizar a história.']);
            return true;
        }

        // --- POST /api/chancelaria/historias/excluir ---
        if ($requestUri === '/api/chancelaria/historias/excluir' && $method === 'POST') {
            $body = RequestBody::json();
            $id = (int) ($body['id'] ?? 0);
            if ($id <= 0) {
                JsonResponse::send(['ok' => false, 'erro' => 'ID inválido.']);
                return true;
            }
            $ok = (new \App\Models\HistoriaMaconica())->excluir($id);
            JsonResponse::send(['ok' => (bool)$ok, 'erro' => $ok ? null : 'Não foi possível excluir a história.']);
            return true;
        }

        return false;
    }
}
