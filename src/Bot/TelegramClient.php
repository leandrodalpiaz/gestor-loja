<?php

namespace App\Bot;

use App\Config\Env;

class TelegramClient
{
    private string $botToken;
    private string $apiUrl;

    public function __construct()
    {
        $this->botToken = Env::get('TELEGRAM_BOT_TOKEN', '');
        $this->apiUrl = "https://api.telegram.org/bot{$this->botToken}/";
    }

    public function sendMessage(int|string $chatId, string $text, array $replyMarkup = []): bool
    {
        $data = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML'
        ];

        if (!empty($replyMarkup)) {
            $data['reply_markup'] = json_encode($replyMarkup);
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
        $data = [
            'callback_query_id' => $callbackQueryId
        ];

        if (!empty($text)) {
            $data['text'] = $text;
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
}