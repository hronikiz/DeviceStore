<?php

declare(strict_types=1);

final class TelegramClient
{
    private string $apiUrl;
    private bool $useInsecureSsl = false;

    public function __construct(string $token)
    {
        $token = trim($token);

        if ($token === '') {
            throw new InvalidArgumentException('Telegram token is required.');
        }

        $this->apiUrl = 'https://api.telegram.org/bot' . $token . '/';
    }

    public function getUpdates(int $offset, int $timeout = 25, array $allowedUpdates = ['message', 'callback_query']): array
    {
        return $this->request('getUpdates', [
            'offset' => $offset,
            'timeout' => $timeout,
            'allowed_updates' => $allowedUpdates,
        ]);
    }

    public function sendMessage(int $chatId, string $text, array $options = []): array
    {
        return $this->request('sendMessage', array_merge([
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
        ], $options));
    }

    public function sendPhoto(int $chatId, string $photo, string $caption, array $options = []): array
    {
        return $this->request('sendPhoto', array_merge([
            'chat_id' => $chatId,
            'photo' => $photo,
            'caption' => $caption,
            'parse_mode' => 'HTML',
        ], $options));
    }

    public function answerCallbackQuery(string $callbackQueryId, string $text = '', bool $showAlert = false): array
    {
        return $this->request('answerCallbackQuery', [
            'callback_query_id' => $callbackQueryId,
            'text' => $text,
            'show_alert' => $showAlert,
        ]);
    }

    private function request(string $method, array $payload): array
    {
        $ch = curl_init($this->apiUrl . $method);
        curl_setopt_array($ch, $this->buildCurlOptions($payload));

        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);
            $errorCode = curl_errno($ch);
            curl_close($ch);

            if (!$this->useInsecureSsl && ($errorCode === 60 || str_contains($error, 'SSL certificate') || str_contains($error, 'self-signed certificate'))) {
                $this->useInsecureSsl = true;
                return $this->request($method, $payload);
            }

            throw new RuntimeException('Telegram request failed: ' . $error);
        }

        curl_close($ch);
        $decoded = json_decode((string) $response, true);

        if (!is_array($decoded)) {
            throw new RuntimeException('Telegram response decode error.');
        }

        return $decoded;
    }

    private function buildCurlOptions(array $payload): array
    {
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT => 35,
        ];

        $caPath = getenv('BOT_CACERT');
        $localCaPath = dirname(__DIR__, 1) . '/certs/cacert.pem';

        if (!$this->useInsecureSsl) {
            if (is_string($caPath) && $caPath !== '' && is_file($caPath)) {
                $options[CURLOPT_CAINFO] = $caPath;
            } elseif (is_file($localCaPath)) {
                $options[CURLOPT_CAINFO] = $localCaPath;
            }
        }

        if (getenv('BOT_ALLOW_INSECURE_SSL') === '1' || $this->useInsecureSsl) {
            $options[CURLOPT_SSL_VERIFYPEER] = false;
            $options[CURLOPT_SSL_VERIFYHOST] = false;
        }

        return $options;
    }
}
