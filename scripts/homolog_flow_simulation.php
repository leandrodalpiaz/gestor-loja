<?php

declare(strict_types=1);

use App\Config\Database;
use App\Config\Env;
use App\Models\ConviteAcesso;

require_once __DIR__ . '/../src/autoload.php';
Env::load(__DIR__ . '/../.env');

$baseUrl = $argv[1] ?? 'http://127.0.0.1:8099';
$baseUrl = rtrim($baseUrl, '/');
$botToken = trim((string) ($_ENV['TELEGRAM_BOT_TOKEN'] ?? ''));

if ($botToken === '') {
    fwrite(STDERR, "TELEGRAM_BOT_TOKEN não configurado no .env\n");
    exit(2);
}

$db = Database::getConnection();
$conviteModel = new ConviteAcesso();

function normalizeStatus(string $body): string
{
    $body = trim($body);
    if ($body === '') {
        return 'empty-body';
    }
    if (stripos($body, 'Acesso restrito') !== false) {
        return 'forbidden';
    }
    if (stripos($body, 'Entrar no Gestor de Loja') !== false || stripos($body, '<form action="/login"') !== false) {
        return 'login-page';
    }
    return 'ok-body';
}

function findObreiroByCargo(PDO $db, string $codigo): ?array
{
    $sql = "SELECT o.id, o.nome_historico, o.nome, o.telegram_id, o.acesso_status, o.ativo
            FROM public.obreiros o
            JOIN public.atribuicoes_cargo ac ON ac.obreiro_id = o.id AND ac.fim_em IS NULL
            JOIN public.cargos c ON c.id = ac.cargo_id AND c.ativo = TRUE
            LEFT JOIN public.gestoes g ON g.id = ac.gestao_id
            WHERE c.codigo = :codigo
              AND o.ativo = TRUE
              AND (g.id IS NULL OR g.status = 'aberta')
            ORDER BY o.updated_at DESC NULLS LAST, o.created_at DESC NULLS LAST
            LIMIT 1";

    $stmt = $db->prepare($sql);
    $stmt->execute(['codigo' => strtoupper($codigo)]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function buildInitData(string $botToken, int $telegramId): string
{
    $params = [
        'auth_date' => (string) time(),
        'query_id' => 'AAH' . $telegramId . '_flowtest',
        'user' => json_encode([
            'id' => $telegramId,
            'is_bot' => false,
            'first_name' => 'Homolog',
            'last_name' => 'FlowTest',
            'username' => 'homolog_flow_' . $telegramId,
            'language_code' => 'pt-br',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    ];

    ksort($params);
    $pairs = [];
    foreach ($params as $k => $v) {
        $pairs[] = $k . '=' . $v;
    }
    $dataCheckString = implode("\n", $pairs);
    $secretKey = hash_hmac('sha256', $botToken, 'WebAppData', true);
    $hash = hash_hmac('sha256', $dataCheckString, $secretKey);

    $params['hash'] = $hash;
    return http_build_query($params);
}

function httpGet(string $url, string $cookieFile): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_COOKIEJAR => $cookieFile,
        CURLOPT_COOKIEFILE => $cookieFile,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);

    $raw = curl_exec($ch);
    $err = curl_error($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $finalUrl = (string) curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);

    if (!is_string($raw)) {
        return [
            'ok' => false,
            'error' => $err !== '' ? $err : 'curl_exec retornou false',
            'status' => $status,
            'final_url' => $finalUrl,
            'body' => '',
        ];
    }

    $body = substr($raw, $headerSize);

    return [
        'ok' => $err === '',
        'error' => $err,
        'status' => $status,
        'final_url' => $finalUrl,
        'body' => $body,
    ];
}

$scenarios = [
    [
        'label' => 'chanceler',
        'cargo' => 'CHANCELER',
        'route' => '/chanceler/sessao',
    ],
    [
        'label' => 'bibliotecario',
        'cargo' => 'BIBLIOTECARIO',
        'route' => '/biblioteca/emprestimos',
    ],
    [
        'label' => 'secretario',
        'cargo' => 'SECRETARIO',
        'route' => '/secretaria',
    ],
    [
        'label' => 'tesoureiro',
        'cargo' => 'TESOUREIRO',
        'route' => '/tesouraria/sessoes',
    ],
];

$results = [];
$exitCode = 0;
$telegramBase = 990000000;

foreach ($scenarios as $index => $scenario) {
    $row = findObreiroByCargo($db, $scenario['cargo']);
    if (!$row) {
        $results[] = [
            'scenario' => $scenario['label'],
            'ok' => false,
            'error' => 'Nenhum obreiro ativo encontrado para o cargo ' . $scenario['cargo'],
        ];
        $exitCode = 1;
        continue;
    }

    $obreiroId = (string) $row['id'];
    $origTelegram = $row['telegram_id'];
    $origStatus = (string) ($row['acesso_status'] ?? '');
    $origAtivo = (bool) ($row['ativo'] ?? true);

    $generated = $conviteModel->gerarParaObreiro($obreiroId);
    if (!($generated['ok'] ?? false)) {
        $results[] = [
            'scenario' => $scenario['label'],
            'ok' => false,
            'error' => 'Falha ao gerar convite: ' . (string) ($generated['erro'] ?? 'erro desconhecido'),
            'obreiro_id' => $obreiroId,
        ];
        $exitCode = 1;
        continue;
    }

    $token = (string) ($generated['token'] ?? '');
    $telegramId = $telegramBase + ($index * 1000) + random_int(10, 999);
    $consume = $conviteModel->consumir($token, $telegramId);

    if (!($consume['ok'] ?? false)) {
        $results[] = [
            'scenario' => $scenario['label'],
            'ok' => false,
            'error' => 'Falha ao consumir convite: ' . (string) ($consume['erro'] ?? 'erro desconhecido'),
            'obreiro_id' => $obreiroId,
        ];

        $stmtDeleteInvite = $db->prepare('DELETE FROM public.convites_acesso WHERE token = :token');
        $stmtDeleteInvite->execute(['token' => $token]);
        $exitCode = 1;
        continue;
    }

    $initData = buildInitData($botToken, $telegramId);
    $cookieFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'gestor_loja_flow_' . $scenario['label'] . '_' . getmypid() . '.cookie';

    $firstUrl = $baseUrl . $scenario['route'] . '?tg_webapp=1&init_data=' . rawurlencode($initData);
    $firstHit = httpGet($firstUrl, $cookieFile);

    $secondUrl = $baseUrl . $scenario['route'] . '?tg_webapp=1';
    $secondHit = httpGet($secondUrl, $cookieFile);

    @unlink($cookieFile);

    $firstBodyStatus = normalizeStatus((string) ($firstHit['body'] ?? ''));
    $secondBodyStatus = normalizeStatus((string) ($secondHit['body'] ?? ''));

    $okFlow = (bool) ($firstHit['ok'] ?? false)
        && (int) ($firstHit['status'] ?? 0) >= 200
        && (int) ($firstHit['status'] ?? 0) < 400
        && !str_contains((string) ($firstHit['final_url'] ?? ''), '/login')
        && $firstBodyStatus === 'ok-body'
        && (bool) ($secondHit['ok'] ?? false)
        && (int) ($secondHit['status'] ?? 0) >= 200
        && (int) ($secondHit['status'] ?? 0) < 400
        && !str_contains((string) ($secondHit['final_url'] ?? ''), '/login')
        && $secondBodyStatus === 'ok-body';

    if (!$okFlow) {
        $exitCode = 1;
    }

    $results[] = [
        'scenario' => $scenario['label'],
        'ok' => $okFlow,
        'obreiro_id' => $obreiroId,
        'obreiro_nome' => (string) ($row['nome_historico'] ?? $row['nome'] ?? ''),
        'invite_generated' => true,
        'invite_consumed' => true,
        'first_hit' => [
            'status' => $firstHit['status'] ?? 0,
            'final_url' => $firstHit['final_url'] ?? '',
            'body_status' => $firstBodyStatus,
            'error' => $firstHit['error'] ?? '',
        ],
        'second_hit' => [
            'status' => $secondHit['status'] ?? 0,
            'final_url' => $secondHit['final_url'] ?? '',
            'body_status' => $secondBodyStatus,
            'error' => $secondHit['error'] ?? '',
        ],
    ];

    $stmtRestore = $db->prepare(
        'UPDATE public.obreiros
         SET telegram_id = :telegram_id,
             acesso_status = :acesso_status,
             ativo = :ativo
         WHERE id = :id'
    );
    $stmtRestore->execute([
        'telegram_id' => $origTelegram,
        'acesso_status' => $origStatus,
        'ativo' => $origAtivo,
        'id' => $obreiroId,
    ]);

    $stmtDeleteInvite = $db->prepare('DELETE FROM public.convites_acesso WHERE token = :token');
    $stmtDeleteInvite->execute(['token' => $token]);
}

echo json_encode([
    'ok' => $exitCode === 0,
    'base_url' => $baseUrl,
    'timestamp' => date('c'),
    'results' => $results,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

exit($exitCode);
