<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/bootstrap.php';

use App\Helpers;
use App\Repositories\CustomerRepository;
use App\Security\AuthAccess;

/**
 * @return array{line_user_id:string,customer_id:int,access:string,customer:array}
 */
function require_auth_access(): array
{
    $access = (string) ($_GET['access'] ?? $_POST['access'] ?? '');
    try {
        $payload = AuthAccess::require($access);
    } catch (Throwable $e) {
        http_response_code(403);
        echo '<!DOCTYPE html><html lang="th"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
        echo '<link rel="stylesheet" href="../assets/order.css"><title>ลิงก์หมดอายุ</title></head><body class="order-app">';
        echo '<div class="order-shell"><main class="order-wrap"><div class="card">';
        echo '<h2>เปิดจาก LINE อีกครั้ง</h2><p class="muted">' . Helpers::e($e->getMessage()) . '</p>';
        echo '</div></main></div></body></html>';
        exit;
    }

    $repo = new CustomerRepository();
    $customer = $repo->findById((int) $payload['customer_id']);
    if (!$customer || (string) $customer['line_user_id'] !== (string) $payload['line_user_id']) {
        // อาจถูก rebind จาก login — ลองหาจาก line id
        $customer = $repo->findOrCreateByLineId((string) $payload['line_user_id']);
    }

    return [
        'line_user_id' => (string) $payload['line_user_id'],
        'customer_id' => (int) $customer['id'],
        'access' => $access,
        'customer' => $customer,
    ];
}
