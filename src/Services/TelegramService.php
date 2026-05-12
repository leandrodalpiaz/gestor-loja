<?php

namespace App\Services;

class TelegramService
{
    private string $botToken;
    private string $groupChatId;
    private string $reviewChatId;
    private string $lastError = '';

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

    public function sendPhotoToGroup(string $absolutePath, string $caption = ''): bool
    {
        if (empty($this->botToken) || empty($this->groupChatId)) {
            $this->lastError = 'Bot token ou chat_id do grupo não configurado.';
            return false;
        }

        if (!file_exists($absolutePath)) {
            $this->lastError = 'Arquivo de foto não encontrado localmente.';
            return false;
        }

        if (\App\Config\AppEnv::telegramDryRun()) {
            error_log("TelegramBot (DRY RUN PHOTO): [Chat {$this->groupChatId}] Sent path: $absolutePath Caption: $caption");
            return true;
        }

        return $this->postPhotoToTelegram($this->groupChatId, $absolutePath, $caption);
    }

    public function sendMessageToChat(string $chatId, string $message): bool
    {
        if (empty($this->botToken) || empty($chatId)) {
            $this->lastError = 'TELEGRAM_BOT_TOKEN ou chat_id nao configurado.';
            error_log("TelegramBot Error: " . $this->lastError);
            return false;
        }

        if (\App\Config\AppEnv::telegramDryRun()) {
            error_log("TelegramBot (DRY RUN): [Chat $chatId] $message");
            return true;
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
            $this->lastError = 'HTTP ' . $httpCode . ' | Response: ' . (string) $response;
            error_log('TelegramBot Error: ' . $this->lastError);
            curl_close($ch);
            return false;
        }

        $payload = json_decode((string) $response, true);
        if (!is_array($payload) || empty($payload['ok'])) {
            $descricao = is_array($payload) ? (string) ($payload['description'] ?? 'Resposta inválida da API Telegram.') : 'Resposta inválida da API Telegram.';
            $this->lastError = $descricao;
            error_log('TelegramBot Error: ' . $descricao . ' | Response: ' . (string) $response);
            curl_close($ch);
            return false;
        }

        curl_close($ch);
        $this->lastError = '';
        return true;
    }

    private function postPhotoToTelegram(string $chatId, string $filePath, string $caption): bool
    {
        $url = "https://api.telegram.org/bot{$this->botToken}/sendPhoto";

        $payload = [
            'chat_id' => $chatId,
            'photo' => new \CURLFile($filePath),
            'caption' => mb_substr($caption, 0, 1024),
            'parse_mode' => 'HTML'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 45);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($response === false || $httpCode !== 200) {
            $this->lastError = 'PHOTO HTTP ' . $httpCode . ' | Response: ' . (string) $response;
            error_log('TelegramBot Error: ' . $this->lastError);
            curl_close($ch);
            return false;
        }

        $resData = json_decode((string) $response, true);
        if (!is_array($resData) || empty($resData['ok'])) {
            $desc = is_array($resData) ? (string) ($resData['description'] ?? 'Rejeição API Photo.') : 'Rejeição API Photo.';
            $this->lastError = $desc;
            curl_close($ch);
            return false;
        }

        curl_close($ch);
        $this->lastError = '';
        return true;
    }

    public function getLastError(): string
    {
        return $this->lastError;
    }
}
