<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once __DIR__ . '/_customer.php';

use App\Helpers;
use App\Repositories\OrderRepository;

$auth = require_customer_access();
$code = (string) ($_GET['code'] ?? '');
$order = (new OrderRepository())->findByCode($code);

if (
    !$order
    || (int) $order['customer_id'] !== $auth['customer_id']
    || (string) $order['line_user_id'] !== $auth['line_user_id']
) {
    http_response_code(403);
    echo 'ไม่มีสิทธิ์ดูออเดอร์นี้';
    exit;
}

$accessQ = 'access=' . rawurlencode($auth['access']);
$months = [1=>'มกราคม',2=>'กุมภาพันธ์',3=>'มีนาคม',4=>'เมษายน',5=>'พฤษภาคม',6=>'มิถุนายน',7=>'กรกฎาคม',8=>'สิงหาคม',9=>'กันยายน',10=>'ตุลาคม',11=>'พฤศจิกายน',12=>'ธันวาคม'];
$ts = strtotime((string) $order['created_at']);
$created = date('j', $ts) . ' ' . $months[(int) date('n', $ts)] . ' ' . (date('Y', $ts) + 543) . ' ' . date('H:i', $ts) . ' น.';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สั่งซื้อสำเร็จ</title>
    <link rel="stylesheet" href="../assets/order.css">
</head>
<body class="order-app">
<div class="order-shell">
    <div class="topbar">
        <a class="back" href="<?= Helpers::e(Helpers::webUrl('order/history.php?' . $accessQ)) ?>">←</a>
        <h1>ITPHONEPLANET</h1>
    </div>

    <main class="order-wrap">
        <div class="card success-card">
            <div class="success-head">
                <div class="check">✓</div>
                <div>
                    <h2>สั่งซื้อสำเร็จ!</h2>
                    <div class="muted">ขอบคุณที่สั่งซื้อสินค้ากับเรา</div>
                </div>
            </div>

            <div class="order-no">#<?= Helpers::e($order['order_code']) ?></div>

            <div class="info-list">
                <div class="info-item">
                    <div class="ico">📅</div>
                    <div>
                        <div class="lab">วันที่สั่งซื้อ</div>
                        <div class="val"><?= Helpers::e($created) ?></div>
                    </div>
                </div>
                <div class="info-item">
                    <div class="ico">💰</div>
                    <div>
                        <div class="lab">ยอดรวมทั้งสิ้น</div>
                        <div class="val"><?= Helpers::e(Helpers::baht($order['total_amount'])) ?></div>
                    </div>
                </div>
                <div class="info-item">
                    <div class="ico">🚚</div>
                    <div>
                        <div class="lab">การจัดส่ง</div>
                        <div class="val">จัดส่งโดย Kerry Express</div>
                    </div>
                </div>
                <div class="info-item">
                    <div class="ico">⏱️</div>
                    <div>
                        <div class="lab">สถานะการสั่งซื้อ</div>
                        <div class="val"><span class="badge badge-ok">รอการชำระเงิน</span></div>
                    </div>
                </div>
            </div>

            <a class="btn btn-green" href="<?= Helpers::e(Helpers::webUrl('order/detail.php?code=' . rawurlencode($order['order_code']) . '&' . $accessQ)) ?>">ดูรายละเอียดคำสั่งซื้อ</a>
            <p class="muted" style="margin-top:12px;text-align:center">กลับไปที่ LINE เพื่อดูบัญชีโอนและแนบสลิปได้เลย</p>
        </div>

        <?= customer_nav($auth['access'], 'history') ?>
    </main>
</div>
</body>
</html>
