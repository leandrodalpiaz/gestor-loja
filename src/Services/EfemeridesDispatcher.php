<?php

namespace App\Services;

use App\Models\EfemerideRegistro;
use App\Models\EfemeridePreviaDiaria;
use App\Models\HistoriaMaconica;
use App\Models\Obreiro;
use App\Bot\TelegramClient;

/**
 * Dispara a prévia diária de efemérides para Chanceler(es) e Admin(s) via Telegram.
 *
 * Desenhado para ser chamado como fire-and-forget (ex: register_shutdown_function)
 * no primeiro acesso ao dashboard do dia, contornando a limitação de CRONs
 * internos no plano Render Free (servidor dorme após 15 min de inatividade).
 */
class EfemeridesDispatcher
{
    /**
     * Dispara a prévia do dia se ainda não tiver sido enviada.
     * Idempotente: só envia uma vez por dia (controlado por efemerides_previas_diarias.disparado_em).
     *
     * @return array{disparado: bool, destinatarios: int, erro: ?string}
     */
    public static function dispatchIfNeeded(): array
    {
        try {
            $previaModel = new EfemeridePreviaDiaria();

            // Idempotência: se já foi disparado hoje, não faz nada
            if ($previaModel->foiDisparadoHoje()) {
                return ['disparado' => false, 'destinatarios' => 0, 'erro' => null];
            }

            // Recolher destinatários
            $targetChatIds = self::collectTargetChatIds();
            if (empty($targetChatIds)) {
                return ['disparado' => false, 'destinatarios' => 0, 'erro' => 'Nenhum destinatário configurado (TELEGRAM_CHAT_ID_CHANCELER ou SYSTEM_ADMIN_TELEGRAM_IDS).'];
            }

            // Recolher registros do dia
            $timezone = trim((string) ($_ENV['APP_TIMEZONE'] ?? 'America/Sao_Paulo'));
            $tz = new \DateTimeZone($timezone);
            $dtHoje = new \DateTimeImmutable('today', $tz);
            $hojeRef = $dtHoje->format('Y-m-d');
            $diaHoje = (int) $dtHoje->format('d');
            $mesHoje = (int) $dtHoje->format('m');

            $registroModel = new EfemerideRegistro();
            $registrosHoje = $registroModel->getRegistrosDoDia();

            // Injeta histórias do dia
            try {
                $historiaModel = new HistoriaMaconica();
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
                error_log('EfemeridesDispatcher: erro ao buscar historias: ' . $e->getMessage());
            }

            // Se não há registos, envia mensagem informativa
            if (empty($registrosHoje)) {
                $telegramClient = new TelegramClient();
                foreach ($targetChatIds as $chatId) {
                    $telegramClient->sendMessage($chatId, "Nenhuma efeméride para hoje.");
                }
                $previaModel->marcarComoDisparado();
                return ['disparado' => true, 'destinatarios' => count($targetChatIds), 'erro' => null];
            }

            // Compor e enviar
            $composer = new EfemeridesComposer();
            $cardService = new EfemeridesCardService();
            $telegramClient = new TelegramClient();

            foreach ($targetChatIds as $chatId) {
                foreach ($registrosHoje as $reg) {
                    $textoReg = $composer->composeDailyPreview([$reg]);
                    $cards = $cardService->buildCardsForDate($hojeRef, [$reg]);
                    $card = !empty($cards) ? $cards[0] : null;
                    $cardPath = $card['card_path'] ?? '';

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

                    $resolvedPath = EfemeridesCardService::resolveLocalPath($cardPath);
                    if ($resolvedPath !== '' && file_exists($resolvedPath)) {
                        $telegramClient->sendPhoto($chatId, $resolvedPath, $textoReg, ['reply_markup' => $keyboard]);
                    } else {
                        $telegramClient->sendMessage($chatId, $textoReg, ['reply_markup' => $keyboard]);
                    }
                }

                $dica = "💡 <b>Dica:</b> Para ajustes e correções (nomes, datas, parentescos ou motivos), faça a alteração diretamente no sistema no computador. Desta forma, a correção é salva e estará pronta no próximo envio.";
                $telegramClient->sendMessage($chatId, $dica, ['parse_mode' => 'HTML']);
            }

            $previaModel->marcarComoDisparado();
            error_log('EfemeridesDispatcher: prévia disparada para ' . count($targetChatIds) . ' destinatário(s).');

            return ['disparado' => true, 'destinatarios' => count($targetChatIds), 'erro' => null];
        } catch (\Throwable $e) {
            error_log('EfemeridesDispatcher: falha crítica: ' . $e->getMessage());
            return ['disparado' => false, 'destinatarios' => 0, 'erro' => $e->getMessage()];
        }
    }

    /**
     * @return string[]
     */
    private static function collectTargetChatIds(): array
    {
        $ids = [];

        // 1. Chanceler do .env
        $envChanceler = trim((string) ($_ENV['TELEGRAM_CHAT_ID_CHANCELER'] ?? ''));
        if ($envChanceler !== '') {
            $ids[] = $envChanceler;
        }

        // 2. Chanceleres ativos do banco
        try {
            $obreiroModel = new Obreiro();
            $obreiros = $obreiroModel->getAllAtivos();
            foreach ($obreiros as $ob) {
                $cargos = is_array($ob['cargos'] ?? null) ? $ob['cargos'] : [];
                if (in_array('chanceler', $cargos, true) || ($ob['cargo_principal'] ?? '') === 'chanceler') {
                    $telId = trim((string) ($ob['telegram_id'] ?? ''));
                    if ($telId !== '') {
                        $ids[] = $telId;
                    }
                }
            }
        } catch (\Throwable $e) {
            error_log('EfemeridesDispatcher: erro ao buscar chanceleres do banco: ' . $e->getMessage());
        }

        // 3. Admins do .env
        $envAdmins = trim((string) ($_ENV['SYSTEM_ADMIN_TELEGRAM_IDS'] ?? ''));
        if ($envAdmins !== '') {
            $adminIds = preg_split('/\s*,\s*/', $envAdmins, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            foreach ($adminIds as $adminId) {
                $adminId = trim((string) $adminId);
                if ($adminId !== '') {
                    $ids[] = $adminId;
                }
            }
        }

        return array_values(array_unique(array_filter($ids)));
    }
}
