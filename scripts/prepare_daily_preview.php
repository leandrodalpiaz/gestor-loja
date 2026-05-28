<?php

require_once __DIR__ . '/../src/autoload.php';

use App\Config\Env;
use App\Models\EfemerideRegistro;
use App\Models\EfemeridePreviaDiaria;
use App\Services\EfemeridesComposer;

Env::load(__DIR__ . '/../.env');

$registroModel = new EfemerideRegistro();
$composer = new EfemeridesComposer();
$previaModel = new EfemeridePreviaDiaria();

$registrosHoje = $registroModel->getRegistrosDoDia();
$mensagemBase = $composer->composeDailyPreview($registrosHoje);

$ok = $previaModel->prepararAutomaticaDoDia($mensagemBase);

if (!$ok) {
    fwrite(STDERR, "Falha ao preparar prévia diária.\n");
    exit(1);
}

echo "Prévia diária preparada com sucesso.\n";

// --- DISPARO AUTOMÁTICO VIA TELEGRAM ---
use App\Services\TelegramService;
use App\Models\Obreiro;

$telegramService = new TelegramService();
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
    foreach ($targetChatIds as $chatId) {
        $sent = $telegramService->sendMessageToChat($chatId, $mensagemBase);
        if ($sent) {
            echo "Enviado com sucesso para o ID: $chatId\n";
        } else {
            echo "Falha ao enviar para o ID: $chatId | Erro: " . $telegramService->getLastError() . "\n";
        }
    }
} else {
    echo "Nenhum ID de Telegram de Chanceler ou Administrador configurado para o disparo automático.\n";
}

