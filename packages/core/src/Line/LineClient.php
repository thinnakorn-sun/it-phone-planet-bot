<?php

declare(strict_types=1);

namespace App\Line;

use App\Env;

final class LineClient
{
    private string $channelSecret;
    private string $accessToken;

    public function __construct(?string $channelSecret = null, ?string $accessToken = null)
    {
        $this->channelSecret = $channelSecret ?? Env::get('LINE_CHANNEL_SECRET', '') ?? '';
        $this->accessToken = $accessToken ?? Env::get('LINE_CHANNEL_ACCESS_TOKEN', '') ?? '';
    }

    public function isConfigured(): bool
    {
        return $this->channelSecret !== '' && $this->accessToken !== '';
    }

    public function verifySignature(string $body, ?string $signature): bool
    {
        if ($this->channelSecret === '' || $signature === null || $signature === '') {
            return false;
        }

        $hash = base64_encode(hash_hmac('sha256', $body, $this->channelSecret, true));
        return hash_equals($hash, $signature);
    }

    /**
     * @param list<array<string, mixed>> $messages
     */
    public function reply(string $replyToken, array $messages): void
    {
        $this->request('https://api.line.me/v2/bot/message/reply', [
            'replyToken' => $replyToken,
            'messages' => array_slice($messages, 0, 5),
        ]);
    }

    /**
     * @param list<array<string, mixed>> $messages
     */
    public function push(string $to, array $messages): void
    {
        $this->request('https://api.line.me/v2/bot/message/push', [
            'to' => $to,
            'messages' => array_slice($messages, 0, 5),
        ]);
    }

    /**
     * @param list<string> $userIds
     * @param list<array<string, mixed>> $messages
     */
    public function multicast(array $userIds, array $messages): void
    {
        $userIds = array_values(array_filter($userIds));
        if ($userIds === []) {
            return;
        }

        $this->request('https://api.line.me/v2/bot/message/multicast', [
            'to' => $userIds,
            'messages' => array_slice($messages, 0, 5),
        ]);
    }

    public function getMessageContent(string $messageId): string
    {
        if ($this->accessToken === '') {
            throw new \RuntimeException('LINE_CHANNEL_ACCESS_TOKEN is empty');
        }

        $ch = curl_init('https://api-data.line.me/v2/bot/message/' . rawurlencode($messageId) . '/content');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->accessToken,
            ],
        ]);

        $result = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($result === false || $status >= 400) {
            throw new \RuntimeException('Failed to download LINE content: ' . ($error ?: "HTTP {$status}"));
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function request(string $url, array $payload): void
    {
        if ($this->accessToken === '') {
            throw new \RuntimeException('LINE_CHANNEL_ACCESS_TOKEN is empty');
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->accessToken,
            ],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
        ]);

        $result = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($result === false || $status >= 400) {
            $body = is_string($result) ? $result : '';
            throw new \RuntimeException('LINE API error: ' . ($error ?: "HTTP {$status} {$body}"));
        }
    }
}
