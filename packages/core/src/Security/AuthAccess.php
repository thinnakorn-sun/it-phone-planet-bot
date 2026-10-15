<?php

declare(strict_types=1);

namespace App\Security;

use App\Helpers;

/** ลิงก์เข้าสู่ระบบ / สมัครสมาชิก ผูกกับ LINE user */
final class AuthAccess
{
    public static function issue(string $lineUserId, int $customerId, int $ttlSeconds = 7200): string
    {
        return OrderToken::make([
            'scope' => 'auth',
            'line_user_id' => $lineUserId,
            'customer_id' => $customerId,
        ], $ttlSeconds);
    }

    public static function require(string $token): array
    {
        $payload = OrderToken::parse($token);
        if (($payload['scope'] ?? '') !== 'auth') {
            throw new \RuntimeException('ลิงก์ไม่ถูกต้อง กรุณาเปิดจาก LINE อีกครั้ง');
        }
        if (empty($payload['line_user_id']) || empty($payload['customer_id'])) {
            throw new \RuntimeException('ข้อมูลไม่ครบ');
        }
        return $payload;
    }

    public static function loginUrl(string $lineUserId, int $customerId): string
    {
        $token = self::issue($lineUserId, $customerId);
        return Helpers::publicBaseUrl() . '/auth/login.php?access=' . rawurlencode($token);
    }

    public static function registerUrl(string $lineUserId, int $customerId): string
    {
        $token = self::issue($lineUserId, $customerId);
        return Helpers::publicBaseUrl() . '/auth/register.php?access=' . rawurlencode($token);
    }
}
