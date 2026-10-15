<?php

declare(strict_types=1);

namespace App\Security;

use App\Env;

final class OrderToken
{
    public static function make(array $payload, int $ttlSeconds = 7200): string
    {
        $payload['exp'] = time() + $ttlSeconds;
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $body = rtrim(strtr(base64_encode((string) $json), '+/', '-_'), '=');
        $sig = hash_hmac('sha256', $body, self::secret());
        return $body . '.' . $sig;
    }

    public static function parse(string $token): array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 2) {
            throw new \RuntimeException('โทเคนไม่ถูกต้อง');
        }

        [$body, $sig] = $parts;
        $expected = hash_hmac('sha256', $body, self::secret());
        if (!hash_equals($expected, $sig)) {
            throw new \RuntimeException('โทเคนไม่ถูกต้อง');
        }

        $json = base64_decode(strtr($body, '-_', '+/'));
        $payload = json_decode((string) $json, true);
        if (!is_array($payload)) {
            throw new \RuntimeException('โทเคนเสียหาย');
        }

        if (($payload['exp'] ?? 0) < time()) {
            throw new \RuntimeException('ลิงก์หมดอายุ กรุณาเลือกสินค้าใหม่จาก LINE');
        }

        return $payload;
    }

    private static function secret(): string
    {
        $secret = Env::get('LINE_CHANNEL_SECRET', '') ?? '';
        if ($secret === '') {
            $secret = Env::get('ADMIN_PASSWORD', 'local-secret') ?? 'local-secret';
        }
        return $secret;
    }
}
