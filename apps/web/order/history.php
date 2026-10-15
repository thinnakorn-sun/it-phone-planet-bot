<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once __DIR__ . '/_customer.php';

use App\Helpers;
use App\Repositories\OrderRepository;

$auth = require_customer_access();
$status = (string) ($_GET['status'] ?? '');
$orders = (new OrderRepository())->listForCustomer($auth['customer_id'], $status !== '' ? $status : null);
$orderRepo = new OrderRepository();
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

$monthsShort = [1=>'ม.ค.',2=>'ก.พ.',3=>'มี.ค.',4=>'เม.ย.',5=>'พ.ค.',6=>'มิ.ย.',7=>'ก.ค.',8=>'ส.ค.',9=>'ก.ย.',10=>'ต.ค.',11=>'พ.ย.',12=>'ธ.ค.'];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ประวัติการสั่งซื้อ</title>
    <link rel="stylesheet" href="../assets/order.css">
</head>
<body class="order-app">
<div class="order-shell" style="background:#f3f5f7">
    <div class="topbar green">
        <a class="back" href="<?= Helpers::e(Helpers::webUrl('order/home.php?' . $accessQ)) ?>">←</a>
        <h1>ประวัติการสั่งซื้อ</h1>
    </div>

    <main class="order-wrap">
        <?php if ($auth['display_name'] !== ''): ?>
            <p class="muted" style="margin:0 0 12px">ของ <?= Helpers::e($auth['display_name']) ?> เท่านั้น</p>
        <?php endif; ?>

        <div class="tabs">
            <a class="<?= $status === '' ? 'active' : '' ?>" href="?<?= Helpers::e($accessQ) ?>">ทั้งหมด</a>
            <a class="<?= $status === 'pending' ? 'active' : '' ?>" href="?<?= Helpers::e($accessQ) ?>&status=pending">รอชำระเงิน</a>
            <a class="<?= $status === 'shipping' ? 'active' : '' ?>" href="?<?= Helpers::e($accessQ) ?>&status=shipping">กำลังจัดส่ง</a>
            <a class="<?= $status === 'completed' ? 'active' : '' ?>" href="?<?= Helpers::e($accessQ) ?>&status=completed">สำเร็จ</a>
        </div>

        <?php if ($orders === []): ?>
            <div class="card"><p class="muted" style="margin:0">ยังไม่มีคำสั่งซื้อ</p></div>
        <?php endif; ?>

        <?php foreach ($orders as $order): ?>
            <?php
            [$label, $badge] = $statusMap[$order['status']] ?? [$order['status'], 'badge-done'];
            $ts = strtotime((string) $order['created_at']);
            $dateText = date('j', $ts) . ' ' . $monthsShort[(int) date('n', $ts)] . ' ' . (date('Y', $ts) + 543) . ' ' . date('H:i', $ts) . ' น.';
            $itemCount = count($orderRepo->items((int) $order['id']));
            $detailUrl = Helpers::webUrl('order/detail.php?code=' . rawurlencode($order['order_code']) . '&' . $accessQ);
            ?>
            <a class="history-item" href="<?= Helpers::e($detailUrl) ?>">
                <div class="history-top">
                    <strong>#<?= Helpers::e($order['order_code']) ?></strong>
                    <span class="badge <?= Helpers::e($badge) ?>"><?= Helpers::e($label) ?></span>
                </div>
                <div class="history-mid"><?= Helpers::e($dateText) ?></div>
                <div class="history-bot">
                    <span class="muted"><?= (int) $itemCount ?> รายการ</span>
                    <span>
                        <span class="amount"><?= Helpers::e(Helpers::baht($order['total_amount'])) ?></span>
                        <span class="chev"> ›</span>
                    </span>
                </div>
            </a>
        <?php endforeach; ?>

        <?= customer_nav($auth['access'], 'history') ?>
    </main>
</div>
</body>
</html>
