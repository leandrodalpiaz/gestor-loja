<?php

namespace App\Services;

class TelegramService
{
    private string $botToken;
    private string $groupChatId;
    private string $reviewChatId;

    public function __construct()
    {
        $this->botToken = $_ENV['TELEGRAM_BOT_TOKEN'] ?? '';
        $defaultChat = $_ENV['TELEGRAM_CHAT_ID'] ?? '';
        $this->groupChatId = $_ENV['TELEGRAM_CHAT_ID_GROUP'] ?? $defaultChat;
        $this->reviewChatId = $_ENV['TELEGRAM_CHAT_ID_CHANCELER'] ?? $defaultChat;
    }

    public function sendMessage(string $message): bool
    {
        return $this->sendMessageToGroup($message);
    }

    public function sendMessageToGroup(string $message): bool
    {
        return $this->sendMessageToChat($this->groupChatId, $message);
    }

    public function sendMessageToReview(string $message): bool
    {
        return $this->sendMessageToChat($this->reviewChatId, $message);
    }

    public function sendMessageToChat(string $chatId, string $message): bool
    {
        if (empty($this->botToken) || empty($chatId)) {
            error_log("TelegramBot Error: TELEGRAM_BOT_TOKEN or chat_id not configured.");
            return false;
        }

        foreach ($this->splitMessage($message) as $chunk) {
            $ok = $this->postToTelegram($chatId, $chunk);
            if (!$ok) {
                return false;
            }
        }

        return true;
    }

    private function splitMessage(string $message, int $chunkSize = 3500): array
    {
        if (strlen($message) <= $chunkSize) {
            return [$message];
        }

        $parts = preg_split("/(\r?\n){2}/", $message) ?: [$message];
        $chunks = [];
        $current = '';

        foreach ($parts as $part) {
            $candidate = $current === '' ? $part : $current . "\n\n" . $part;
            if (strlen($candidate) > $chunkSize && $current !== '') {
                $chunks[] = $current;
                $current = $part;
            } else {
                $current = $candidate;
            }
        }

        if ($current !== '') {
            $chunks[] = $current;
        }

        return $chunks;
    }

    private function postToTelegram(string $chatId, string $message): bool
    {
        $url = "https://api.telegram.org/bot{$this->botToken}/sendMessage";

        $data = [
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'HTML'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($response === false || $httpCode !== 200) {
            error_log('TelegramBot Error: HTTP ' . $httpCode . ' | Response: ' . (string) $response);
        }

        curl_close($ch);

        return $httpCode === 200;
    }
}
