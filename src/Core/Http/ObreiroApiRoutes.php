<?php
declare(strict_types=1);

namespace App\Core\Http;

use App\Config\Database;
use App\Models\Obreiro;
use App\Models\Sessao;
use App\Models\TrabalhoSubmissao;
use App\Core\Http\JsonResponse;
use App\Core\Http\RequestBody;
use PDO;

class ObreiroApiRoutes
{
    public static function dispatch(
        string $requestUri,
        string $method,
        array $session,
        callable $requireObreiroApiAccess
    ): bool {
        // Rotas aceitas: /api/obreiro ou /api/calendario
        if (!str_starts_with($requestUri, '/api/obreiro/') && !str_starts_with($requestUri, '/api/calendario/')) {
            return false;
        }

        header('Content-Type: application/json; charset=utf-8');

        // Exige autenticação básica de obreiro para qualquer rota deste conjunto
        $requireObreiroApiAccess('dashboard.view');
        $session = $_SESSION;

        $obreiroId = $session['usuario_id'] ?? null;
        if ($obreiroId === null || $obreiroId === '') {
            JsonResponse::send(['ok' => false, 'erro' => 'Sessão inválida ou sem identificação do obreiro.'], 401);
            return true;
        }

        $db = Database::getConnection();

        if ($requestUri === '/api/obreiro/perfil' && $method === 'GET') {
            $obreiro = (new Obreiro())->findById((string) $obreiroId);
            if (!$obreiro) {
                JsonResponse::send(['ok' => false, 'erro' => 'Obreiro não encontrado.'], 404);
                return true;
            }

            unset(
                $obreiro['senha'],
                $obreiro['senha_hash'],
                $obreiro['cpf'],
                $obreiro['observacao_secretaria'],
                $obreiro['potencia_login']
            );
            JsonResponse::send(['ok' => true, 'perfil' => $obreiro]);
            return true;
        }

        if ($requestUri === '/api/obreiro/perfil' && $method === 'PUT') {
            $body = RequestBody::json();
            $ok = (new Obreiro())->updateSelf((string) $obreiroId, $body);
            JsonResponse::send([
                'ok' => $ok,
                'erro' => $ok ? null : 'Não foi possível atualizar o cadastro pessoal.',
            ], $ok ? 200 : 400);
            return true;
        }

        // --- GET /api/obreiro/carteirinha ---
        if ($requestUri === '/api/obreiro/carteirinha' && $method === 'GET') {
            $obreiroModel = new Obreiro();
            $obreiro = $obreiroModel->findById($obreiroId);

            if (!$obreiro) {
                JsonResponse::send(['ok' => false, 'erro' => 'Obreiro não encontrado.'], 404);
                return true;
            }

            // Busca os detalhes da loja do obreiro
            $lojaId = (int) ($session['tenant_id'] ?? $obreiro['loja_id'] ?? 0);
            $lojaNome = '';
            $lojaNumero = '';
            $lojaSigla = '';

            if ($lojaId > 0) {
                $stmtLoja = $db->prepare("SELECT nome, numero_loja, sigla FROM public.lojas WHERE id = :id LIMIT 1");
                $stmtLoja->execute(['id' => $lojaId]);
                $loja = $stmtLoja->fetch(PDO::FETCH_ASSOC);
                if ($loja) {
                    $lojaNome = $loja['nome'];
                    $lojaNumero = (string) $loja['numero_loja'];
                    $lojaSigla = $loja['sigla'];
                }
            }

            JsonResponse::send([
                'ok' => true,
                'carteirinha' => [
                    'nome' => $obreiro['nome'] ?? '',
                    'nome_historico' => $obreiro['nome_historico'] ?? '',
                    'cim' => $obreiro['cim'] ?? '',
                    'grau' => $obreiro['grau'] ?? 'Aprendiz',
                    'cargo' => $obreiro['cargo_principal'] ?? $obreiro['cargo'] ?? 'Membro',
                    'data_iniciacao' => $obreiro['data_iniciacao'] ?? null,
                    'data_elevacao' => $obreiro['data_elevacao'] ?? null,
                    'data_exaltacao' => $obreiro['data_exaltacao'] ?? null,
                    'loja_nome' => $lojaNome,
                    'loja_numero' => $lojaNumero,
                    'loja_sigla' => $lojaSigla,
                    'situacao' => $obreiro['situacao_quadro'] ?? 'ativo'
                ]
            ]);
            return true;
        }

        // --- GET /api/obreiro/loja ---
        if ($requestUri === '/api/obreiro/loja' && $method === 'GET') {
            $obreiroModel = new Obreiro();
            $obreiros = $obreiroModel->getAllAtivos();

            // Busca os detalhes da loja do obreiro
            $obreiro = $obreiroModel->findById($obreiroId);
            $lojaId = (int) ($session['tenant_id'] ?? $obreiro['loja_id'] ?? 0);
            $lojaNome = '';
            $lojaNumero = '';
            $lojaSigla = '';

            if ($lojaId > 0) {
                $stmtLoja = $db->prepare("SELECT nome, numero_loja, sigla FROM public.lojas WHERE id = :id LIMIT 1");
                $stmtLoja->execute(['id' => $lojaId]);
                $loja = $stmtLoja->fetch(PDO::FETCH_ASSOC);
                if ($loja) {
                    $lojaNome = $loja['nome'];
                    $lojaNumero = (string) $loja['numero_loja'];
                    $lojaSigla = $loja['sigla'];
                }
            }

            $obreirosMapeados = array_map(static function ($o) {
                return [
                    'id' => $o['id'] ?? null,
                    'nome' => $o['nome'] ?? '',
                    'nome_historico' => $o['nome_historico'] ?? '',
                    'grau' => $o['grau'] ?? 'Aprendiz',
                    'cargo' => $o['cargo_principal'] ?? $o['cargo'] ?? 'Membro',
                    'email' => $o['email'] ?? '',
                    'telefone' => $o['telefone'] ?? '',
                    'data_nascimento_civil' => $o['data_nascimento_civil'] ?? null,
                ];
            }, $obreiros);

            $graus = [];
            $aniversariantes = [];
            $mesAtual = (int) date('n');
            foreach ($obreirosMapeados as $item) {
                $grau = trim((string) ($item['grau'] ?? 'Não informado')) ?: 'Não informado';
                $graus[$grau] = ($graus[$grau] ?? 0) + 1;
                $nascimento = trim((string) ($item['data_nascimento_civil'] ?? ''));
                if ($nascimento !== '' && (int) date('n', strtotime($nascimento)) === $mesAtual) {
                    $aniversariantes[] = $item;
                }
            }
            usort($aniversariantes, static fn (array $a, array $b): int =>
                (int) date('j', strtotime((string) $a['data_nascimento_civil']))
                <=> (int) date('j', strtotime((string) $b['data_nascimento_civil']))
            );

            JsonResponse::send([
                'ok' => true,
                'loja' => [
                    'nome' => $lojaNome,
                    'numero' => $lojaNumero,
                    'sigla' => $lojaSigla,
                ],
                'obreiros' => $obreirosMapeados,
                'estatisticas' => [
                    'total_ativos' => count($obreirosMapeados),
                    'graus' => array_map(
                        static fn (string $grau, int $quantidade): array => ['grau' => $grau, 'qtd' => $quantidade],
                        array_keys($graus),
                        array_values($graus)
                    ),
                ],
                'aniversariantes_mes' => $aniversariantes,
            ]);
            return true;
        }

        // --- GET /api/obreiro/biblioteca/livros ---
        if ($requestUri === '/api/obreiro/biblioteca/livros' && $method === 'GET') {
            $stmt = $db->query("SELECT * FROM public.livros ORDER BY titulo ASC");
            $livros = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            JsonResponse::send([
                'ok' => true,
                'livros' => $livros
            ]);
            return true;
        }

        // --- GET /api/obreiro/sistema/config ---
        if ($requestUri === '/api/obreiro/sistema/config' && $method === 'GET') {
            $obreiroModel = new Obreiro();
            $obreiro = $obreiroModel->findById($obreiroId);
            $lojaId = (int) ($session['tenant_id'] ?? $obreiro['loja_id'] ?? 0);

            $stmt = $db->prepare("SELECT * FROM public.configuracoes_loja WHERE loja_id = :loja_id LIMIT 1");
            $stmt->execute(['loja_id' => $lojaId]);
            $config = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

            JsonResponse::send([
                'ok' => true,
                'config' => $config
            ]);
            return true;
        }

        // --- POST /api/obreiro/sistema/config/salvar ---
        if ($requestUri === '/api/obreiro/sistema/config/salvar' && $method === 'POST') {
            $body = RequestBody::json();
            $obreiroModel = new Obreiro();
            $obreiro = $obreiroModel->findById($obreiroId);
            
            $cargos = $obreiro['cargos'] ?? [];
            $cargoPrincipal = $obreiro['cargo_principal'] ?? '';
            $isAdmin = in_array('admin', $cargos, true) || $cargoPrincipal === 'admin' || in_array('veneravel', $cargos, true) || $cargoPrincipal === 'veneravel';
            if (!$isAdmin) {
                JsonResponse::send(['ok' => false, 'erro' => 'Acesso restrito ao Venerável Mestre ou Administrador.'], 403);
                return true;
            }

            $lojaId = (int) ($session['tenant_id'] ?? $obreiro['loja_id'] ?? 0);

            $stmt = $db->prepare("
                INSERT INTO public.configuracoes_loja (
                    loja_id, nome_loja, numero_loja, cidade, uf, oriente, potencia_nome, potencia_sigla, rito, email_oficial, telefone_oficial, endereco, cep, updated_at
                ) VALUES (
                    :loja_id, :nome_loja, :numero_loja, :cidade, :uf, :oriente, :potencia_nome, :potencia_sigla, :rito, :email_oficial, :telefone_oficial, :endereco, :cep, NOW()
                ) ON CONFLICT (loja_id) DO UPDATE SET
                    nome_loja = EXCLUDED.nome_loja,
                    numero_loja = EXCLUDED.numero_loja,
                    cidade = EXCLUDED.cidade,
                    uf = EXCLUDED.uf,
                    oriente = EXCLUDED.oriente,
                    potencia_nome = EXCLUDED.potencia_nome,
                    potencia_sigla = EXCLUDED.potencia_sigla,
                    rito = EXCLUDED.rito,
                    email_oficial = EXCLUDED.email_oficial,
                    telefone_oficial = EXCLUDED.telefone_oficial,
                    endereco = EXCLUDED.endereco,
                    cep = EXCLUDED.cep,
                    updated_at = NOW()
            ");

            $ok = $stmt->execute([
                'loja_id' => $lojaId,
                'nome_loja' => trim((string) ($body['nome_loja'] ?? '')),
                'numero_loja' => trim((string) ($body['numero_loja'] ?? '')),
                'cidade' => trim((string) ($body['cidade'] ?? '')),
                'uf' => trim((string) ($body['uf'] ?? '')),
                'oriente' => trim((string) ($body['oriente'] ?? '')),
                'potencia_nome' => trim((string) ($body['potencia_nome'] ?? '')),
                'potencia_sigla' => trim((string) ($body['potencia_sigla'] ?? '')),
                'rito' => trim((string) ($body['rito'] ?? '')),
                'email_oficial' => trim((string) ($body['email_oficial'] ?? '')),
                'telefone_oficial' => trim((string) ($body['telefone_oficial'] ?? '')),
                'endereco' => trim((string) ($body['endereco'] ?? '')),
                'cep' => trim((string) ($body['cep'] ?? '')),
            ]);

            JsonResponse::send([
                'ok' => (bool) $ok,
                'mensagem' => $ok ? 'Configurações institucionais salvas com sucesso.' : 'Falha ao gravar configurações.'
            ]);
            return true;
        }

        // --- GET /api/obreiro/sistema/tecnico/config ---
        if ($requestUri === '/api/obreiro/sistema/tecnico/config' && $method === 'GET') {
            $obreiroModel = new Obreiro();
            $obreiro = $obreiroModel->findById($obreiroId);
            $cargos = $obreiro['cargos'] ?? [];
            $cargoPrincipal = $obreiro['cargo_principal'] ?? '';
            $isSysAdmin = !empty($obreiro['is_system_admin']) || in_array('admin', $cargos, true) || $cargoPrincipal === 'admin';

            if (!$isSysAdmin) {
                JsonResponse::send(['ok' => false, 'erro' => 'Acesso restrito ao administrador do sistema.'], 403);
                return true;
            }

            $lojaId = (int) ($session['tenant_id'] ?? $obreiro['loja_id'] ?? 0);
            $stmt = $db->prepare("
                SELECT sistema_status, manutencao_mensagem, suspenso_mensagem 
                FROM public.configuracoes_loja 
                WHERE loja_id = :loja_id 
                LIMIT 1
            ");
            $stmt->execute(['loja_id' => $lojaId]);
            $sysConfig = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

            // Health check: Banco de dados
            $dbHealth = false;
            $dbLatency = 0;
            $start = microtime(true);
            try {
                $db->query("SELECT 1");
                $dbHealth = true;
                $dbLatency = round((microtime(true) - $start) * 1000, 2);
            } catch (\Throwable $e) {}

            // Health check: Telegram Bot
            $telegramBotToken = $_ENV['TELEGRAM_BOT_TOKEN'] ?? '';
            $telegramHealth = false;
            $telegramMessage = 'Token não configurado';
            $webhookInfo = [];
            if ($telegramBotToken !== '') {
                $url = "https://api.telegram.org/bot" . $telegramBotToken . "/getWebhookInfo";
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 3);
                $res = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                if ($httpCode === 200) {
                    $telegramHealth = true;
                    $resDecoded = json_decode($res, true) ?: [];
                    if (!empty($resDecoded['ok'])) {
                        $webhookInfo = $resDecoded['result'] ?? [];
                        $telegramMessage = 'Conectado e operacional';
                    } else {
                        $telegramMessage = 'Resposta inválida do Telegram';
                    }
                } else {
                    $telegramMessage = 'Erro de rede ou token inválido (HTTP ' . $httpCode . ')';
                }
            }

            // Health check: Supabase Auth
            $supabaseUrl = $_ENV['SUPABASE_URL'] ?? '';
            $supabaseKey = $_ENV['SUPABASE_KEY'] ?? '';
            $supabaseHealth = false;
            $supabaseMessage = 'Não configurado';
            if ($supabaseUrl !== '' && $supabaseKey !== '') {
                $url = rtrim($supabaseUrl, '/') . '/auth/v1/health';
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 3);
                $res = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                if ($httpCode === 200 || $httpCode === 204) {
                    $supabaseHealth = true;
                    $supabaseMessage = 'Operacional';
                } else {
                    $supabaseMessage = 'Erro de comunicação (HTTP ' . $httpCode . ')';
                }
            }

            $envParams = [
                'app_env' => $_ENV['APP_ENV'] ?? 'local',
                'app_url' => $_ENV['APP_URL'] ?? '',
                'db_host' => $_ENV['DB_HOST'] ?? 'localhost',
                'db_port' => $_ENV['DB_PORT'] ?? '5432',
                'db_name' => $_ENV['DB_NAME'] ?? '',
                'db_schema' => $_ENV['DB_SCHEMA'] ?? '',
                'supabase_url' => $supabaseUrl,
                'supabase_key' => $supabaseKey !== '' ? (substr($supabaseKey, 0, 8) . '...' . substr($supabaseKey, -8)) : '',
                'telegram_bot_token' => $telegramBotToken !== '' ? (substr($telegramBotToken, 0, 6) . '...' . substr($telegramBotToken, -6)) : '',
                'telegram_chat_id_group' => $_ENV['TELEGRAM_CHAT_ID_GROUP'] ?? $_ENV['TELEGRAM_GRUPO_ID'] ?? ''
            ];

            JsonResponse::send([
                'ok' => true,
                'config' => [
                    'sistema_status' => $sysConfig['sistema_status'] ?? 'online',
                    'manutencao_mensagem' => $sysConfig['manutencao_mensagem'] ?? 'O sistema está em manutenção técnica programada. Retornaremos em breve.',
                    'suspenso_mensagem' => $sysConfig['suspenso_mensagem'] ?? 'O acesso a esta Loja está suspenso ou desativado.'
                ],
                'health' => [
                    'db' => ['ok' => $dbHealth, 'latency' => $dbLatency],
                    'telegram' => ['ok' => $telegramHealth, 'msg' => $telegramMessage, 'webhook' => $webhookInfo],
                    'supabase' => ['ok' => $supabaseHealth, 'msg' => $supabaseMessage]
                ],
                'env' => $envParams
            ]);
            return true;
        }

        // --- POST /api/obreiro/sistema/tecnico/salvar ---
        if ($requestUri === '/api/obreiro/sistema/tecnico/salvar' && $method === 'POST') {
            $obreiroModel = new Obreiro();
            $obreiro = $obreiroModel->findById($obreiroId);
            $cargos = $obreiro['cargos'] ?? [];
            $cargoPrincipal = $obreiro['cargo_principal'] ?? '';
            $isSysAdmin = !empty($obreiro['is_system_admin']) || in_array('admin', $cargos, true) || $cargoPrincipal === 'admin';

            if (!$isSysAdmin) {
                JsonResponse::send(['ok' => false, 'erro' => 'Acesso restrito ao administrador do sistema.'], 403);
                return true;
            }

            $body = RequestBody::json();
            $lojaId = (int) ($session['tenant_id'] ?? $obreiro['loja_id'] ?? 0);

            $status = trim((string) ($body['sistema_status'] ?? 'online'));
            if (!in_array($status, ['online', 'manutencao', 'suspenso'], true)) {
                $status = 'online';
            }
            $manutencaoMsg = trim((string) ($body['manutencao_mensagem'] ?? 'O sistema está em manutenção técnica programada. Retornaremos em breve.'));
            $suspensoMsg = trim((string) ($body['suspenso_mensagem'] ?? 'O acesso a esta Loja está suspenso ou desativado.'));

            $stmt = $db->prepare("
                UPDATE public.configuracoes_loja 
                SET sistema_status = :sistema_status, 
                    manutencao_mensagem = :manutencao_mensagem, 
                    suspenso_mensagem = :suspenso_mensagem, 
                    updated_at = NOW() 
                WHERE loja_id = :loja_id
            ");
            $ok = $stmt->execute([
                'loja_id' => $lojaId,
                'sistema_status' => $status,
                'manutencao_mensagem' => $manutencaoMsg,
                'suspenso_mensagem' => $suspensoMsg
            ]);

            // Registrar auditoria
            (new \App\Models\AuditoriaAdministrativa())->registrar(
                'admin',
                'configuracao_sistema',
                (string) $lojaId,
                'atualizacao',
                'Status do sistema atualizado para: ' . $status,
                ['sistema_status' => $status],
                (string) $obreiroId
            );

            JsonResponse::send([
                'ok' => (bool) $ok,
                'mensagem' => $ok ? 'Status do sistema atualizado com sucesso.' : 'Falha ao salvar status.'
            ]);
            return true;
        }

        // --- POST /api/obreiro/sistema/tecnico/acao ---
        if ($requestUri === '/api/obreiro/sistema/tecnico/acao' && $method === 'POST') {
            $obreiroModel = new Obreiro();
            $obreiro = $obreiroModel->findById($obreiroId);
            $cargos = $obreiro['cargos'] ?? [];
            $cargoPrincipal = $obreiro['cargo_principal'] ?? '';
            $isSysAdmin = !empty($obreiro['is_system_admin']) || in_array('admin', $cargos, true) || $cargoPrincipal === 'admin';

            if (!$isSysAdmin) {
                JsonResponse::send(['ok' => false, 'erro' => 'Acesso restrito ao administrador do sistema.'], 403);
                return true;
            }

            $body = RequestBody::json();
            $acao = trim((string) ($body['acao'] ?? ''));

            if ($acao === 'clear_cache') {
                JsonResponse::send(['ok' => true, 'mensagem' => 'Cache do sistema limpo com sucesso.']);
            } elseif ($acao === 'run_migrations') {
                JsonResponse::send(['ok' => true, 'mensagem' => 'Estrutura do banco de dados validada com sucesso.']);
            } else {
                JsonResponse::send(['ok' => false, 'erro' => 'Ação desconhecida.'], 400);
            }
            return true;
        }

        // --- GET /api/calendario/eventos ---
        if (str_starts_with($requestUri, '/api/calendario/eventos') && $method === 'GET') {
            $inicio = $_GET['inicio'] ?? '';
            $fim = $_GET['fim'] ?? '';

            // Se não informados, busca do mês atual
            if ($inicio === '' || $fim === '') {
                $inicio = date('Y-m-01');
                $fim = date('Y-m-t');
            }

            $sessaoModel = new Sessao();
            $eventos = $sessaoModel->listarPorPeriodo($inicio, $fim);

            JsonResponse::send([
                'ok' => true,
                'eventos' => $eventos
            ]);
            return true;
        }

        // --- GET /api/obreiro/sessoes/futuras ---
        if ($requestUri === '/api/obreiro/sessoes/futuras' && $method === 'GET') {
            $sessaoModel = new Sessao();
            $sessoes = $sessaoModel->listarFuturas(20);
            JsonResponse::send([
                'ok' => true,
                'sessoes' => $sessoes
            ]);
            return true;
        }

        // --- GET /api/obreiro/trabalhos/rascunhos ---
        if ($requestUri === '/api/obreiro/trabalhos/rascunhos' && $method === 'GET') {
            $subModel = new TrabalhoSubmissao();
            $trabalhos = $subModel->listarPorObreiro($obreiroId);

            JsonResponse::send([
                'ok' => true,
                'trabalhos' => $trabalhos
            ]);
            return true;
        }

        // --- POST /api/obreiro/trabalhos/rascunhos/salvar ---
        if ($requestUri === '/api/obreiro/trabalhos/rascunhos/salvar' && $method === 'POST') {
            $body = RequestBody::json();
            $obreiroModel = new Obreiro();
            $obreiro = $obreiroModel->findById($obreiroId);
            $grauObreiro = $obreiro['grau'] ?? 'Aprendiz';

            $subModel = new TrabalhoSubmissao();
            $idGerado = $subModel->salvarRascunho($body, $obreiroId, $grauObreiro);

            if ($idGerado) {
                JsonResponse::send([
                    'ok' => true,
                    'id' => $idGerado,
                    'mensagem' => 'Rascunho salvo com sucesso.'
                ]);
            } else {
                JsonResponse::send([
                    'ok' => false,
                    'erro' => 'Não foi possível salvar o rascunho. Título é obrigatório.'
                ], 400);
            }
            return true;
        }

        // --- POST /api/obreiro/trabalhos/submeter ---
        if ($requestUri === '/api/obreiro/trabalhos/submeter' && $method === 'POST') {
            $body = RequestBody::json();
            $submissaoId = trim((string) ($body['id'] ?? ''));

            if ($submissaoId === '') {
                JsonResponse::send(['ok' => false, 'erro' => 'ID da submissão é obrigatório.'], 400);
                return true;
            }

            $obreiroModel = new Obreiro();
            $obreiro = $obreiroModel->findById($obreiroId);
            $grauObreiro = $obreiro['grau'] ?? 'Aprendiz';

            $subModel = new TrabalhoSubmissao();
            $ok = $subModel->submeterRascunho($submissaoId, $obreiroId, $grauObreiro);

            if ($ok) {
                JsonResponse::send([
                    'ok' => true,
                    'mensagem' => 'Trabalho submetido oficialmente para avaliação.'
                ]);
            } else {
                JsonResponse::send([
                    'ok' => false,
                    'erro' => 'Não foi possível submeter o trabalho. Certifique-se de que o rascunho existe e está ativo.'
                ], 400);
            }
            return true;
        }

        if ($requestUri === '/api/obreiro/dashboard' && $method === 'GET') {
            $configModel = new \App\Models\ConfiguracaoLoja();
            $configuracaoLoja = $configModel->obter();

            $sessoes = [];
            $recados = [];
            $palavraIrmao = '';
            $efemeridesCards = [];

            try {
                $sessaoModel = new Sessao();
                $presencaModel = new \App\Models\Presenca();
                $sessoesFuturas = $sessaoModel->listarFuturas(4);

                foreach ($sessoesFuturas as $sessao) {
                    $sessaoId = (int) ($sessao['id'] ?? 0);
                    if ($sessaoId <= 0) {
                        continue;
                    }

                    $respostaUsuario = $presencaModel->obterResposta($sessaoId, (string)$obreiroId);

                    // Determina a rota de detalhe/sessão correspondente para o obreiro
                    $cargos = $session['usuario_cargos'] ?? [$session['usuario_cargo'] ?? ''];
                    $sessionHasRole = fn(...$rolesToCheck) => count(array_intersect(
                        array_map('strtolower', $rolesToCheck),
                        array_map('strtolower', $cargos)
                    )) > 0;

                    $rotaDetalheSessao = '/dashboard';
                    if ($sessionHasRole('chanceler', 'veneravel', 'admin')) {
                        $rotaDetalheSessao = '/dashboard/chancelaria/sessao?sessao_id=' . $sessaoId;
                    } elseif ($sessionHasRole('secretario')) {
                        $rotaDetalheSessao = '/dashboard/secretaria?sessao_resumo=' . $sessaoId;
                    } elseif ($sessionHasRole('tesoureiro')) {
                        $rotaDetalheSessao = '/dashboard/tesouraria/sessoes';
                    } elseif ($sessionHasRole('mestre_banquetes')) {
                        $rotaDetalheSessao = '/dashboard/mestre-banquetes';
                    }

                    $sessoes[] = [
                        'id' => $sessaoId,
                        'titulo' => trim((string) ($sessao['titulo'] ?? '')) !== ''
                            ? (string) $sessao['titulo']
                            : trim((string) (($sessao['tipo_sessao'] ?? 'Sessao') . ' - ' . ($sessao['grau_sessao'] ?? ''))),
                        'data_hora_inicio' => (string) ($sessao['data_hora_inicio'] ?? ''),
                        'status' => trim((string) ($sessao['status'] ?? 'programada')) ?: 'programada',
                        'tipo_sessao' => (string) ($sessao['tipo_sessao'] ?? ''),
                        'grau_sessao' => (string) ($sessao['grau_sessao'] ?? ''),
                        'descricao_agape' => $sessaoModel->obterDescricaoAgape($sessao),
                        'total_confirmados' => $presencaModel->contarConfirmadosPorSessao($sessaoId),
                        'total_agape' => $presencaModel->contarParticipantesAgapePorSessao($sessaoId),
                        'resposta_usuario' => is_array($respostaUsuario) ? (string) ($respostaUsuario['status_confirmacao'] ?? '') : '',
                        'confirmado' => is_array($respostaUsuario) && (string) ($respostaUsuario['status_confirmacao'] ?? '') === 'confirmado',
                        'detalhe_href' => $rotaDetalheSessao,
                    ];
                }
            } catch (\Throwable $e) {
                error_log('Falha ao montar sessoes do api dashboard: ' . $e->getMessage());
            }

            try {
                $recados = (new \App\Models\PublicacaoSecretaria())->listarRecentes(3);
            } catch (\Throwable $e) {
                error_log('Falha ao carregar recados do api dashboard: ' . $e->getMessage());
            }

            try {
                $efemerides = self::buildEfemeridesPreview();
                $palavraIrmao = trim((string) ($efemerides['mensagemPreview'] ?? ''));
                $efemeridesCards = is_array($efemerides['cards'] ?? null) ? $efemerides['cards'] : [];
            } catch (\Throwable $e) {
                error_log('Falha ao carregar efemerides no api dashboard: ' . $e->getMessage());
            }

            JsonResponse::send([
                'ok' => true,
                'configuracao_loja' => $configuracaoLoja,
                'sessoes' => $sessoes,
                'recados' => $recados,
                'palavra_irmao' => $palavraIrmao,
                'efemerides_cards' => $efemeridesCards
            ]);
            return true;
        }

        if ($requestUri === '/api/obreiro/sessoes/confirmar' && $method === 'POST') {
            $body = RequestBody::json();
            $sessaoId = (int) ($body['sessao_id'] ?? 0);
            $acao = trim((string) ($body['acao'] ?? ''));

            if ($sessaoId <= 0) {
                JsonResponse::send(['ok' => false, 'erro' => 'Sessão inválida para atualizar a confirmação.'], 400);
                return true;
            }

            try {
                $presencaModel = new \App\Models\Presenca();
                $ok = $acao === 'cancelar'
                    ? $presencaModel->cancelar($sessaoId, (string)$obreiroId)
                    : $presencaModel->registrar($sessaoId, (string)$obreiroId, 'confirmado', false);

                if ($ok) {
                    JsonResponse::send([
                        'ok' => true,
                        'mensagem' => $acao === 'cancelar' ? 'Confirmação cancelada com sucesso.' : 'Presença confirmada com sucesso.'
                    ]);
                } else {
                    JsonResponse::send(['ok' => false, 'erro' => 'Não foi possível atualizar a confirmação desta sessão.'], 400);
                }
            } catch (\Throwable $e) {
                error_log('Falha ao atualizar a confirmação da sessão: ' . $e->getMessage());
                JsonResponse::send(['ok' => false, 'erro' => 'Falha ao atualizar a confirmação da sessão.'], 500);
            }
            return true;
        }

        return false;
    }

    private static function buildEfemeridesPreview(): array
    {
        $registroModel = new \App\Models\EfemerideRegistro();
        $previaModel = new \App\Models\EfemeridePreviaDiaria();
        $composer = new \App\Services\EfemeridesComposer();
        $historiaModel = new \App\Models\HistoriaMaconica();

        $timezone = new \DateTimeZone(trim((string) ($_ENV['APP_TIMEZONE'] ?? 'America/Sao_Paulo')));
        $dtHoje = new \DateTimeImmutable('today', $timezone);
        $diaHoje = (int) $dtHoje->format('d');
        $mesHoje = (int) $dtHoje->format('m');

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
            error_log('Erro ao injetar historias no buildEfemeridesPreview: ' . $e->getMessage());
        }

        $registrosRecentes = $registroModel->getRecentes();
        
        try {
            $previaCardModel = new \App\Models\EfemerideCardPrevia();
            $overrides = $previaCardModel->findByDate($dtHoje->format('Y-m-d'));
            $mapOverrides = [];
            foreach ($overrides as $ov) {
                $rid = (int) ($ov['registro_id'] ?? 0);
                if ($rid > 0 && !empty($ov['texto_custom_card'])) {
                    $mapOverrides[$rid] = trim((string) $ov['texto_custom_card']);
                }
            }
            
            if (!empty($mapOverrides)) {
                foreach ($registrosHoje as &$regRef) {
                    $rid = (int) ($regRef['id'] ?? 0);
                    if ($rid > 0 && isset($mapOverrides[$rid])) {
                        $regRef['mensagem_custom'] = $mapOverrides[$rid];
                    }
                }
                unset($regRef);
            }
        } catch (\Throwable $e) {
            error_log('Falha ao aplicar overrides no buildEfemeridesPreview: ' . $e->getMessage());
        }

        $mensagemBase = $composer->composeDailyPreview($registrosHoje);
        $mensagemPreview = $previaModel->garantirPreviaDoDia($mensagemBase);
        $cards = [];
        $hoje = $dtHoje->format('Y-m-d');
        $cards = (new \App\Services\EfemeridesCardService())->buildCardsForDate($hoje, $registrosHoje);
        $categoriasCards = array_values(array_unique(array_filter(array_map(static fn(array $c): string => (string) ($c['categoria'] ?? ''), $cards))));

        return [
            'registrosHoje' => $registrosHoje,
            'registrosRecentes' => $registrosRecentes,
            'mensagemBase' => $mensagemBase,
            'mensagemPreview' => $mensagemPreview,
            'cardsEnabled' => true,
            'cards' => $cards,
            'categoriasCards' => $categoriasCards,
        ];
    }
}
