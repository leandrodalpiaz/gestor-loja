<?php

namespace App\Bot;

use App\Config\Env;
use App\Config\AppEnv;

class TelegramClient
{
    private string $botToken;
    private string $apiUrl;

    public function __construct()
    {
        $this->botToken = Env::get('TELEGRAM_BOT_TOKEN', '');
        $this->apiUrl = "https://api.telegram.org/bot{$this->botToken}/";
    }

    private function normalizeMojibake(string $text): string
    {
        return strtr($text, [
            'Ã¡' => 'á',
            'Ã¢' => 'â',
            'Ã£' => 'ã',
            'Ã ' => 'à',
            'Ã©' => 'é',
            'Ãª' => 'ê',
            'Ã­' => 'í',
            'Ã³' => 'ó',
            'Ã´' => 'ô',
            'Ãµ' => 'õ',
            'Ãº' => 'ú',
            'Ã§' => 'ç',
            'Ã' => 'Á',
            'Ã‰' => 'É',
            'Ã' => 'Í',
            'Ã“' => 'Ó',
            'Ãš' => 'Ú',
            'Ã‡' => 'Ç',
            'â€¢' => '•',
            'Â·' => '·',
            'Âº' => 'º',
            'Âª' => 'ª',
            'nÂº' => 'nº',
            'NÂº' => 'Nº',
        ]);
    }

    private function normalizeArrayStrings(array $value): array
    {
        foreach ($value as $key => $item) {
            if (is_string($item)) {
                $value[$key] = $this->normalizeMojibake($item);
                continue;
            }

            if (is_array($item)) {
                $value[$key] = $this->normalizeArrayStrings($item);
            }
        }

        return $value;
    }

    public function sendMessage(int|string $chatId, string $text, array $options = []): bool
    {
        if (AppEnv::telegramDryRun()) {
            error_log('[telegram][dry-run] sendMessage chat_id=' . $chatId . ' text_len=' . strlen($text));
            return true;
        }

        $text = $this->normalizeMojibake($text);
        $options = $this->normalizeArrayStrings($options);

        $data = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => (string) ($options['parse_mode'] ?? 'HTML'),
        ];

        // Compatibilidade:
        // 1) sendMessage($chatId, $text, $keyboard)
        // 2) sendMessage($chatId, $text, ['parse_mode' => 'HTML', 'reply_markup' => $keyboard])
        if (isset($options['reply_markup']) && is_array($options['reply_markup'])) {
            $data['reply_markup'] = json_encode($options['reply_markup']);
        } elseif (isset($options['inline_keyboard']) || isset($options['keyboard'])) {
            $data['reply_markup'] = json_encode($options);
        }

        $requestOptions = [
            'http' => [
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($data),
                'ignore_errors' => true
            ]
        ];

        $context = stream_context_create($requestOptions);
        $result = @file_get_contents($this->apiUrl . 'sendMessage', false, $context);

        if ($result === false) {
            $error = error_get_last();
            error_log("ERRO API TELEGRAM: " . ($error['message'] ?? 'Desconhecido') . " | URL: " . $this->apiUrl . 'sendMessage');
            return false;
        }

        $payload = json_decode((string) $result, true);
        $ok = is_array($payload) && !empty($payload['ok']);
        if (!$ok) {
            error_log("ERRO API TELEGRAM sendMessage: " . (string) $result);
            return false;
        }

        error_log("RESPOSTA API TELEGRAM: " . $result);
        return true;
    }

    public function answerCallbackQuery(string $callbackQueryId, string $text = ''): bool
    {
        if (AppEnv::telegramDryRun()) {
            error_log('[telegram][dry-run] answerCallbackQuery id=' . $callbackQueryId . ' text_len=' . strlen($text));
            return true;
        }

        $data = [
            'callback_query_id' => $callbackQueryId
        ];

        if (!empty($text)) {
            $data['text'] = $this->normalizeMojibake($text);
        }

        $options = [
            'http' => [
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($data),
                'ignore_errors' => true
            ]
        ];

        $context = stream_context_create($options);
        $result = @file_get_contents($this->apiUrl . 'answerCallbackQuery', false, $context);
        if ($result === false) {
            return false;
        }

        $payload = json_decode((string) $result, true);
        return is_array($payload) && !empty($payload['ok']);
    }

    public function sendPhoto($chatId, $photoPath, $caption = '') {
        if (AppEnv::telegramDryRun()) {
            error_log('[telegram][dry-run] sendPhoto chat_id=' . $chatId . ' photo=' . (string) $photoPath);
            return true;
        }

        $url = "https://api.telegram.org/bot" . $this->botToken . "/sendPhoto";

        if (!file_exists($photoPath)) {
            error_log("Erro: Arquivo não encontrado - " . $photoPath);
            return false;
        }

        $postFields = [
            'chat_id' => $chatId,
            'photo' => new \CURLFile(realpath($photoPath)),
            'caption' => $this->normalizeMojibake((string) $caption),
            'parse_mode' => 'Markdown'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }
}
