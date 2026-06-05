<?php

require_once __DIR__ . '/../src/autoload.php';

use App\Config\Env;
use App\Models\EfemerideRegistro;
use App\Models\EfemeridePreviaDiaria;
use App\Services\EfemeridesComposer;
use App\Bot\TelegramClient;
use App\Services\EfemeridesCardService;
use App\Models\HistoriaMaconica;

Env::load(__DIR__ . '/../.env');

// Aumentar o limite de tempo para evitar timeout durante o aquecimento do Render
set_time_limit(120);

// Forçar o despertar do servidor web Render (se estiver dormindo) para evitar delays de cold start aos usuários
$appUrl = trim((string) (Env::get('APP_URL') ?: ''));
if ($appUrl !== '') {
    echo "Acordando o servidor web Render em: $appUrl ...\n";
    $pingUrl = rtrim($appUrl, '/') . '/health.php';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $pingUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60); // Aguarda até 60 segundos pela inicialização fria do Render
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    echo "Ping de aquecimento concluído com status HTTP: $httpCode\n";
}

$registroModel = new EfemerideRegistro();
$historiaModel = new HistoriaMaconica();
$composer = new EfemeridesComposer();
$previaModel = new EfemeridePreviaDiaria();

$timezone = new \DateTimeZone('America/Sao_Paulo');
$dtHoje = new \DateTimeImmutable('today', $timezone);
$hoje = $dtHoje->format('Y-m-d');
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
    error_log('prepare_daily_preview.php: erro ao buscar historias: ' . $e->getMessage());
}

$mensagemBase = $composer->composeDailyPreview($registrosHoje);
$ok = $previaModel->prepararAutomaticaDoDia($mensagemBase);

if (!$ok) {
    fwrite(STDERR, "Falha ao preparar prévia diária.\n");
    exit(1);
}

echo "Prévia diária preparada com sucesso.\n";

// --- DISPARO AUTOMÁTICO VIA TELEGRAM ---
use App\Models\Obreiro;

$targetChatIds = [];

// 1. Chanceler direto do arquivo .env (se definido)
$envChanceler = trim((string) (Env::get('TELEGRAM_CHAT_ID_CHANCELER') ?: ''));
if ($envChanceler !== '') {
    $targetChatIds[] = $envChanceler;
}

// 2. Chanceler(es) ativo(s) da base de dados com Telegram ID integrado
try {
    $obreiroModel = new Obreiro();
    $obreiros = $obreiroModel->getAllAtivos();
    foreach ($obreiros as $ob) {
        $cargos = is_array($ob['cargos'] ?? null) ? $ob['cargos'] : [];
        if (in_array('chanceler', $cargos, true) || ($ob['cargo_principal'] ?? '') === 'chanceler') {
            $telId = trim((string) ($ob['telegram_id'] ?? ''));
            if ($telId !== '') {
                $targetChatIds[] = $telId;
            }
        }
    }
} catch (\Throwable $e) {
    error_log("prepare_daily_preview: erro ao buscar chanceleres do banco: " . $e->getMessage());
}

// 3. Administradores do Sistema do arquivo .env
$envAdmins = trim((string) (Env::get('SYSTEM_ADMIN_TELEGRAM_IDS') ?: ''));
if ($envAdmins !== '') {
    $adminIds = preg_split('/\s*,\s*/', $envAdmins, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    foreach ($adminIds as $adminId) {
        $adminId = trim((string) $adminId);
        if ($adminId !== '') {
            $targetChatIds[] = $adminId;
        }
    }
}

// Limpar duplicados e IDs nulos/vazios
$targetChatIds = array_values(array_unique(array_filter($targetChatIds)));

if (!empty($targetChatIds)) {
    echo "Disparando prévia de efemérides para os IDs de Telegram: " . implode(', ', $targetChatIds) . "\n";
    
    $telegramClient = new TelegramClient();
    $cardService = new EfemeridesCardService();

    foreach ($targetChatIds as $chatId) {
        if (empty($registrosHoje)) {
            $telegramClient->sendMessage($chatId, "Nenhuma efeméride para hoje.");
            continue;
        }

        foreach ($registrosHoje as $reg) {
            $textoReg = $composer->composeDailyPreview([$reg]);
            
            // Gerar cartão
            $cards = $cardService->buildCardsForDate($hoje, [$reg]);
            $card = !empty($cards) ? $cards[0] : null;
            $cardPath = $card['card_path'] ?? '';

            // Botões
            $source = ($reg['tipo'] === 'História') ? 'his' : 'reg';
            $id = $reg['id'];
            $keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => '📄 Enviar Texto', 'callback_data' => "ef_tx_{$source}_{$id}"],
                        ['text' => '🖼️ Enviar Cartão', 'callback_data' => "ef_cd_{$source}_{$id}"],
                    ],
                    [
                        ['text' => '✨ Enviar Texto + Cartão', 'callback_data' => "ef_bo_{$source}_{$id}"],
                    ],
                    [
                        ['text' => '❌ Não enviar', 'callback_data' => "ef_no_{$source}_{$id}"],
                    ]
                ]
            ];

            $resolvedPath = \App\Services\EfemeridesCardService::resolveLocalPath($cardPath);
            if ($resolvedPath !== '' && file_exists($resolvedPath)) {
                $res = $telegramClient->sendPhoto($chatId, $resolvedPath, $textoReg, ['reply_markup' => $keyboard]);
                if ($res) {
                    echo "Foto de efeméride enviada para ID: $chatId\n";
                } else {
                    echo "Falha ao enviar foto para ID: $chatId\n";
                }
            } else {
                $res = $telegramClient->sendMessage($chatId, $textoReg, ['reply_markup' => $keyboard]);
                if ($res) {
                    echo "Texto de efeméride enviado para ID: $chatId\n";
                } else {
                    echo "Falha ao enviar texto para ID: $chatId\n";
                }
            }
        }

        // Dica final
        $dica = "💡 <b>Dica:</b> Para ajustes e correções (nomes, datas, parentescos ou motivos), faça a alteração diretamente no sistema no computador. Desta forma, a correção é salva e estará pronta no próximo envio.";
        $telegramClient->sendMessage($chatId, $dica, ['parse_mode' => 'HTML']);
    }
} else {
    echo "Nenhum ID de Telegram de Chanceler ou Administrador configurado para o disparo automático.\n";
}

