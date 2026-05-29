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
            "\xC3\x83\xC2\xA1" => "\xC3\xA1",
            "\xC3\x83\xC2\xA2" => "\xC3\xA2",
            "\xC3\x83\xC2\xA3" => "\xC3\xA3",
            "\xC3\x83\xC2\xA0" => "\xC3\xA0",
            "\xC3\x83\xC2\xA9" => "\xC3\xA9",
            "\xC3\x83\xC2\xAA" => "\xC3\xAA",
            "\xC3\x83\xC2\xAD" => "\xC3\xAD",
            "\xC3\x83\xC2\xB3" => "\xC3\xB3",
            "\xC3\x83\xC2\xB4" => "\xC3\xB4",
            "\xC3\x83\xC2\xB5" => "\xC3\xB5",
            "\xC3\x83\xC2\xBA" => "\xC3\xBA",
            "\xC3\x83\xC2\xA7" => "\xC3\xA7",
            "\xC3\x83\xC2\x81" => "\xC3\x81",
            "\xC3\x83\xC2\x89" => "\xC3\x89",
            "\xC3\x83\xC2\x8D" => "\xC3\x8D",
            "\xC3\x83\xC2\x93" => "\xC3\x93",
            "\xC3\x83\xC2\x9A" => "\xC3\x9A",
            "\xC3\x83\xC2\x87" => "\xC3\x87",
            "\xC3\xA2\xC2\x80\xC2\xA2" => "\xE2\x80\xA2",
            "\xC3\x82\xC2\xB7" => "\xC2\xB7",
            "\xC3\x82\xC2\xBA" => "\xC2\xBA",
            "\xC3\x82\xC2\xAA" => "\xC2\xAA",
            "n\xC3\x82\xC2\xBA" => "n\xC2\xBA",
            "N\xC3\x82\xC2\xBA" => "N\xC2\xBA",
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

    public function sendPhoto($chatId, $photoPath, $caption = '', array $options = []) {
        if (AppEnv::telegramDryRun()) {
            error_log('[telegram][dry-run] sendPhoto chat_id=' . $chatId . ' photo=' . (string) $photoPath);
            return true;
        }

        $url = "https://api.telegram.org/bot" . $this->botToken . "/sendPhoto";

        if (!file_exists($photoPath)) {
            error_log("Erro: Arquivo não encontrado - " . $photoPath);
            return false;
        }

        $caption = $this->normalizeMojibake((string) $caption);
        $options = $this->normalizeArrayStrings($options);

        $postFields = [
            'chat_id' => $chatId,
            'photo' => new \CURLFile(realpath($photoPath)),
            'caption' => $caption,
            'parse_mode' => (string) ($options['parse_mode'] ?? 'HTML')
        ];

        if (isset($options['reply_markup']) && is_array($options['reply_markup'])) {
            $postFields['reply_markup'] = json_encode($options['reply_markup']);
        } elseif (isset($options['inline_keyboard']) || isset($options['keyboard'])) {
            $postFields['reply_markup'] = json_encode($options);
        }

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

    public function editMessageText(int|string $chatId, int $messageId, string $text, array $options = []): bool
    {
        if (AppEnv::telegramDryRun()) {
            error_log('[telegram][dry-run] editMessageText chat_id=' . $chatId . ' message_id=' . $messageId . ' text_len=' . strlen($text));
            return true;
        }

        $text = $this->normalizeMojibake($text);
        $options = $this->normalizeArrayStrings($options);

        $data = [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $text,
            'parse_mode' => (string) ($options['parse_mode'] ?? 'HTML'),
        ];

        if (isset($options['reply_markup']) && is_array($options['reply_markup'])) {
            $data['reply_markup'] = json_encode($options['reply_markup']);
        } elseif (isset($options['inline_keyboard']) || isset($options['keyboard'])) {
            $data['reply_markup'] = json_encode($options);
        } else {
            $data['reply_markup'] = json_encode(['inline_keyboard' => []]);
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
        $result = @file_get_contents($this->apiUrl . 'editMessageText', false, $context);

        if ($result === false) {
            $error = error_get_last();
            error_log("ERRO API TELEGRAM editMessageText: " . ($error['message'] ?? 'Desconhecido'));
            return false;
        }

        $payload = json_decode((string) $result, true);
        return is_array($payload) && !empty($payload['ok']);
    }

    public function editMessageCaption(int|string $chatId, int $messageId, string $caption, array $options = []): bool
    {
        if (AppEnv::telegramDryRun()) {
            error_log('[telegram][dry-run] editMessageCaption chat_id=' . $chatId . ' message_id=' . $messageId . ' caption_len=' . strlen($caption));
            return true;
        }

        $caption = $this->normalizeMojibake($caption);
        $options = $this->normalizeArrayStrings($options);

        $data = [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'caption' => $caption,
            'parse_mode' => (string) ($options['parse_mode'] ?? 'HTML'),
        ];

        if (isset($options['reply_markup']) && is_array($options['reply_markup'])) {
            $data['reply_markup'] = json_encode($options['reply_markup']);
        } elseif (isset($options['inline_keyboard']) || isset($options['keyboard'])) {
            $data['reply_markup'] = json_encode($options);
        } else {
            $data['reply_markup'] = json_encode(['inline_keyboard' => []]);
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
        $result = @file_get_contents($this->apiUrl . 'editMessageCaption', false, $context);

        if ($result === false) {
            $error = error_get_last();
            error_log("ERRO API TELEGRAM editMessageCaption: " . ($error['message'] ?? 'Desconhecido'));
            return false;
        }

        $payload = json_decode((string) $result, true);
        return is_array($payload) && !empty($payload['ok']);
    }
}
