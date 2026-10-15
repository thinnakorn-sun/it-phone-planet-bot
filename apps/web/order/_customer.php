<?php

declare(strict_types=1);

use App\Helpers;
use App\Security\CustomerAccess;

/**
 * @return array{line_user_id:string,customer_id:int,display_name:string,access:string}
 */
function require_customer_access(): array
{
    $access = (string) ($_GET['access'] ?? $_POST['access'] ?? '');
    try {
        $payload = CustomerAccess::require($access);
    } catch (Throwable $e) {
        http_response_code(403);
        echo '<!DOCTYPE html><html lang="th"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">';
        echo '<link rel="stylesheet" href="../assets/order.css"><title>ไม่มีสิทธิ์</title></head>';
        echo '<body class="order-app"><div class="order-shell"><main class="order-wrap">';
        echo '<div class="card"><h2>ไม่มีสิทธิ์เข้าถึง</h2><p class="muted">' . Helpers::e($e->getMessage()) . '</p>';
        echo '<p class="muted">กรุณาเปิดหน้านี้จากปุ่มใน LINE ของบัญชีคุณเท่านั้น</p></div></main></div></body></html>';
        exit;
    }

    return [
        'line_user_id' => (string) $payload['line_user_id'],
        'customer_id' => (int) $payload['customer_id'],
        'display_name' => (string) ($payload['display_name'] ?? ''),
        'access' => $access,
    ];
}

function customer_nav(string $access, string $active = 'history'): string
{
    $home = Helpers::webUrl('order/home.php?access=' . rawurlencode($access));
    $history = Helpers::webUrl('order/history.php?access=' . rawurlencode($access));
    $account = Helpers::webUrl('order/account.php?access=' . rawurlencode($access));

    $aHome = $active === 'home' ? 'active' : '';
    $aHistory = $active === 'history' ? 'active' : '';
    $aAccount = $active === 'account' ? 'active' : '';

    return '<nav class="bottom-nav">'
        . '<a class="' . $aHome . '" href="' . Helpers::e($home) . '">หน้าหลัก</a>'
        . '<a class="' . $aHistory . '" href="' . Helpers::e($history) . '">ประวัติการสั่งซื้อ</a>'
        . '<a class="' . $aAccount . '" href="' . Helpers::e($account) . '">บัญชีของฉัน</a>'
        . '</nav>';
}
