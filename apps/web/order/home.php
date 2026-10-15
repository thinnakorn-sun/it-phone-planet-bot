<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once __DIR__ . '/_customer.php';

use App\Helpers;
use App\Repositories\OrderRepository;

$auth = require_customer_access();
$orders = (new OrderRepository())->listForCustomer($auth['customer_id']);
$latest = $orders[0] ?? null;

$statusMap = [
    'pending_payment' => 'รอการชำระเงิน',
    'awaiting_review' => 'รอตรวจสอบสลิป',
    'paid' => 'ชำระเงินแล้ว',
    'shipping' => 'กำลังจัดส่ง',
    'completed' => 'สำเร็จ',
    'rejected' => 'สลิปไม่ผ่าน',
];
$badgeClass = match ($latest['status'] ?? '') {
    'paid', 'shipping', 'completed' => 'badge-ship',
    default => 'badge-wait',
};
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>หน้าหลักของฉัน</title>
    <link rel="stylesheet" href="../assets/order.css">
</head>
<body class="order-app">
<div class="order-shell" style="background:#f3f5f7">
    <div class="topbar green">
        <span class="back" style="visibility:hidden">←</span>
        <h1>หน้าหลัก</h1>
    </div>
    <main class="order-wrap">
        <div class="card">
            <h2 style="margin:0 0 8px">สวัสดี<?= $auth['display_name'] !== '' ? ' ' . Helpers::e($auth['display_name']) : '' ?></h2>
            <p class="muted" style="margin:0">นี่เป็นหน้าสำหรับบัญชีของคุณเท่านั้น สั่งซื้อสินค้าเพิ่มได้ที่แชท LINE</p>
        </div>

        <?php if ($latest): ?>
            <div class="section-title green"><span class="dot">🧾</span> ออเดอร์ล่าสุดของคุณ</div>
            <a class="history-item" href="<?= Helpers::e(Helpers::webUrl('order/detail.php?code=' . rawurlencode($latest['order_code']) . '&access=' . rawurlencode($auth['access']))) ?>">
                <div class="history-top">
                    <strong>#<?= Helpers::e($latest['order_code']) ?></strong>
                    <span class="badge <?= Helpers::e($badgeClass) ?>"><?= Helpers::e($statusMap[$latest['status']] ?? $latest['status']) ?></span>
                </div>
                <div class="history-bot">
                    <span class="muted"><?= Helpers::e($latest['created_at']) ?></span>
                    <span class="amount"><?= Helpers::e(Helpers::baht($latest['total_amount'])) ?></span>
                </div>
            </a>
        <?php else: ?>
            <div class="card"><p class="muted" style="margin:0">คุณยังไม่มีคำสั่งซื้อ</p></div>
        <?php endif; ?>

        <?= customer_nav($auth['access'], 'home') ?>
    </main>
</div>
</body>
</html>
