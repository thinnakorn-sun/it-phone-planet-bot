<?php

declare(strict_types=1);

namespace App\Security;

use App\Env;
use App\Helpers;

/**
 * Signed access for customer-only pages (history / account).
 * ไม่ใช้ line_user_id ดิบใน URL เพื่อกันแอบดูของคนอื่น
 */
final class CustomerAccess
{
    public static function issue(string $lineUserId, int $customerId, string $displayName = '', int $ttlSeconds = 86400): string
    {
        return OrderToken::make([
            'scope' => 'customer',
            'line_user_id' => $lineUserId,
            'customer_id' => $customerId,
            'display_name' => $displayName,
        ], $ttlSeconds);
    }

    public static function require(string $token): array
    {
        $payload = OrderToken::parse($token);
        if (($payload['scope'] ?? '') !== 'customer') {
            throw new \RuntimeException('ไม่มีสิทธิ์เข้าถึงหน้านี้');
        }
        if (empty($payload['line_user_id']) || empty($payload['customer_id'])) {
            throw new \RuntimeException('ข้อมูลผู้ใช้ไม่ครบ');
        }
        return $payload;
    }

    public static function historyUrl(string $lineUserId, int $customerId, string $displayName = '', ?string $status = null): string
    {
        $token = self::issue($lineUserId, $customerId, $displayName);
        $url = Helpers::publicBaseUrl() . '/order/history.php?access=' . rawurlencode($token);
        if ($status) {
            $url .= '&status=' . rawurlencode($status);
        }
        return $url;
    }

    public static function accountUrl(string $lineUserId, int $customerId, string $displayName = ''): string
    {
        $token = self::issue($lineUserId, $customerId, $displayName);
        return Helpers::publicBaseUrl() . '/order/account.php?access=' . rawurlencode($token);
    }

    public static function homeUrl(string $lineUserId, int $customerId, string $displayName = ''): string
    {
        $token = self::issue($lineUserId, $customerId, $displayName);
        return Helpers::publicBaseUrl() . '/order/home.php?access=' . rawurlencode($token);
    }

    public static function detailUrl(string $orderCode, string $lineUserId, int $customerId, string $displayName = ''): string
    {
        $token = self::issue($lineUserId, $customerId, $displayName);
        return Helpers::publicBaseUrl() . '/order/detail.php?code=' . rawurlencode($orderCode) . '&access=' . rawurlencode($token);
    }

    public static function secretHint(): string
    {
        return substr(hash('sha256', Env::get('LINE_CHANNEL_SECRET', 'x') ?? 'x'), 0, 8);
    }
}
