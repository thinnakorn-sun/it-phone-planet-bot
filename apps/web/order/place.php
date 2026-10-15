<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/bootstrap.php';

use App\Helpers;
use App\Repositories\CustomerRepository;
use App\Repositories\OrderRepository;
use App\Repositories\SessionRepository;
use App\Security\CustomerAccess;
use App\Security\OrderToken;
use App\Services\OrderService;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo 'Method not allowed';
    exit;
}

$token = (string) ($_POST['token'] ?? '');

try {
    $payload = OrderToken::parse($token);

    $orderService = new OrderService();
    $order = $orderService->createOrder(
        (int) $payload['customer_id'],
        (int) $payload['product_id'],
        (int) $payload['quantity'],
        (string) $payload['shipping_name'],
        (string) $payload['shipping_phone'],
        (string) $payload['shipping_address'],
        (string) ($payload['payment_method'] ?? 'โอนเงินผ่านธนาคาร')
    );

    (new CustomerRepository())->updateContact(
        (int) $payload['customer_id'],
        (string) $payload['shipping_phone'],
        (string) $payload['shipping_address'],
        (string) $payload['shipping_name']
    );

    (new SessionRepository())->clear((string) $payload['line_user_id']);

    $items = (new OrderRepository())->items((int) $order['id']);
    $orderService->pushOrderCreated($order, $items);

    $access = CustomerAccess::issue(
        (string) $payload['line_user_id'],
        (int) $payload['customer_id'],
        (string) ($payload['shipping_name'] ?? '')
    );

    Helpers::redirect(Helpers::webUrl(
        'order/success.php?code=' . rawurlencode((string) $order['order_code'])
        . '&access=' . rawurlencode($access)
    ));
} catch (Throwable $e) {
    http_response_code(400);
    echo '<p style="font-family:sans-serif;padding:24px">' . Helpers::e($e->getMessage()) . '</p>';
}
