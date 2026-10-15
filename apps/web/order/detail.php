<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once __DIR__ . '/_customer.php';

use App\Helpers;
use App\Repositories\OrderRepository;

$auth = require_customer_access();
$code = (string) ($_GET['code'] ?? '');
$repo = new OrderRepository();
$order = $repo->findByCode($code);

if (
    !$order
    || (int) $order['customer_id'] !== $auth['customer_id']
    || (string) $order['line_user_id'] !== $auth['line_user_id']
) {
    http_response_code(403);
    echo '<!DOCTYPE html><html lang="th"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<link rel="stylesheet" href="../assets/order.css"><title>ไม่มีสิทธิ์</title></head>';
    echo '<body class="order-app"><div class="order-shell"><main class="order-wrap">';
    echo '<div class="card"><h2>ไม่มีสิทธิ์ดูออเดอร์นี้</h2><p class="muted">ออเดอร์นี้ไม่ใช่ของบัญชีคุณ</p></div></main></div></body></html>';
    exit;
}

$items = $repo->items((int) $order['id']);
$accessQ = 'access=' . rawurlencode($auth['access']);

$statusMap = [
    'pending_payment' => ['รอการชำระเงิน', 'badge-wait'],
    'awaiting_review' => ['รอตรวจสอบสลิป', 'badge-wait'],
    'paid' => ['ชำระเงินแล้ว', 'badge-ship'],
    'shipping' => ['จัดส่งแล้ว', 'badge-ship'],
    'completed' => ['สำเร็จ', 'badge-done'],
    'cancelled' => ['ยกเลิก', 'badge-done'],
    'rejected' => ['สลิปไม่ผ่าน', 'badge-wait'],
];
[$statusLabel, $badgeClass] = $statusMap[$order['status']] ?? [$order['status'], 'badge-done'];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายละเอียดออเดอร์</title>
    <link rel="stylesheet" href="../assets/order.css">
</head>
<body class="order-app">
<div class="order-shell">
    <div class="topbar green">
        <a class="back" href="<?= Helpers::e(Helpers::webUrl('order/history.php?' . $accessQ)) ?>">←</a>
        <h1>รายละเอียดคำสั่งซื้อ</h1>
    </div>

    <main class="order-wrap">
        <div class="card">
            <div class="order-no" style="margin-top:0">#<?= Helpers::e($order['order_code']) ?></div>
            <div class="kv"><span class="label">สถานะ</span><span class="value"><span class="badge <?= Helpers::e($badgeClass) ?>"><?= Helpers::e($statusLabel) ?></span></span></div>
            <div class="kv"><span class="label">วันที่</span><span class="value"><?= Helpers::e($order['created_at']) ?></span></div>
            <div class="kv"><span class="label">ผู้รับ</span><span class="value"><?= Helpers::e($order['shipping_name']) ?></span></div>
            <div class="kv"><span class="label">เบอร์</span><span class="value"><?= Helpers::e($order['shipping_phone']) ?></span></div>
            <div class="kv"><span class="label">ที่อยู่</span><span class="value"><?= Helpers::e($order['shipping_address']) ?></span></div>
            <div class="kv"><span class="label">ชำระเงิน</span><span class="value"><?= Helpers::e($order['payment_method'] ?: 'โอนเงินผ่านธนาคาร') ?></span></div>
            <?php if (!empty($order['tracking_number'])): ?>
                <div class="kv"><span class="label">เลขพัสดุ</span><span class="value"><?= Helpers::e($order['tracking_number']) ?></span></div>
            <?php endif; ?>
        </div>

        <div class="section-title green"><span class="dot">🧾</span> รายการสินค้า</div>
        <div class="card">
            <?php foreach ($items as $item): ?>
                <div class="kv">
                    <span class="label"><?= Helpers::e($item['product_name']) ?> x<?= (int) $item['quantity'] ?></span>
                    <span class="value"><?= Helpers::e(Helpers::baht($item['line_total'])) ?></span>
                </div>
            <?php endforeach; ?>
            <div class="total-row">
                <strong>ยอดรวม</strong>
                <span class="total"><?= Helpers::e(Helpers::baht($order['total_amount'])) ?></span>
            </div>
        </div>

        <?= customer_nav($auth['access'], 'history') ?>
    </main>
</div>
</body>
</html>
