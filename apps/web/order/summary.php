<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/bootstrap.php';

use App\Helpers;
use App\Repositories\ProductRepository;
use App\Security\OrderToken;

$token = (string) ($_GET['token'] ?? '');
try {
    $payload = OrderToken::parse($token);
} catch (Throwable $e) {
    http_response_code(400);
    echo '<p style="font-family:sans-serif;padding:24px">' . Helpers::e($e->getMessage()) . '</p>';
    exit;
}

foreach (['product_id', 'quantity', 'shipping_name', 'shipping_phone', 'shipping_address'] as $key) {
    if (empty($payload[$key])) {
        Helpers::redirect(Helpers::webUrl('order/form.php?token=' . rawurlencode($token)));
    }
}

$product = (new ProductRepository())->find((int) $payload['product_id']);
if (!$product) {
    echo '<p style="font-family:sans-serif;padding:24px">ไม่พบสินค้า</p>';
    exit;
}

$qty = (int) $payload['quantity'];
$total = (float) $product['price'] * $qty;
$img = Helpers::productImageUrl($product['image_url'] ?? null)
    ?: Helpers::localProductImageUrl($product['image_url'] ?? null);
$editToken = OrderToken::make([
    'line_user_id' => $payload['line_user_id'],
    'customer_id' => (int) $payload['customer_id'],
    'product_id' => (int) $payload['product_id'],
    'quantity' => $qty,
]);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สรุปคำสั่งซื้อ</title>
    <link rel="stylesheet" href="../assets/order.css">
</head>
<body class="order-app">
<div class="order-shell">
    <div class="topbar">
        <a class="back" href="<?= Helpers::e(Helpers::webUrl('order/form.php?token=' . rawurlencode($editToken))) ?>">←</a>
        <h1>📋 สรุปคำสั่งซื้อ</h1>
    </div>

    <main class="order-wrap">
        <div class="section-title"><span class="dot">🛒</span> รายการสินค้า</div>
        <div class="card">
            <div class="product-box">
                <?php if ($img): ?><img src="<?= Helpers::e($img) ?>" alt=""><?php endif; ?>
                <div>
                    <div class="name"><?= Helpers::e($product['name']) ?></div>
                    <div class="price"><?= Helpers::e(Helpers::baht($product['price'])) ?></div>
                    <div class="muted" style="margin-top:6px">จำนวน <?= $qty ?> ชิ้น</div>
                </div>
            </div>
            <div class="total-row">
                <span class="muted">รวมรายการนี้</span>
                <span class="price"><?= Helpers::e(Helpers::baht($total)) ?></span>
            </div>
        </div>

        <div class="section-title blue"><span class="dot">📍</span> ที่อยู่จัดส่ง</div>
        <div class="card">
            <div class="kv"><span class="label">ชื่อ</span><span class="value"><?= Helpers::e($payload['shipping_name']) ?></span></div>
            <div class="kv"><span class="label">เบอร์โทร</span><span class="value"><?= Helpers::e($payload['shipping_phone']) ?></span></div>
            <div class="kv"><span class="label">ที่อยู่</span><span class="value"><?= Helpers::e($payload['shipping_address']) ?></span></div>
        </div>

        <div class="section-title blue"><span class="dot">💳</span> วิธีการชำระเงิน</div>
        <div class="card">
            <div class="kv">
                <span class="label">ช่องทาง</span>
                <span class="value"><?= Helpers::e($payload['payment_method'] ?? 'โอนเงินผ่านธนาคาร') ?></span>
            </div>
            <div class="total-row">
                <strong>ยอดรวมทั้งหมด</strong>
                <span class="total"><?= Helpers::e(Helpers::baht($total)) ?></span>
            </div>
        </div>

        <div class="btn-row">
            <a class="btn btn-outline" href="<?= Helpers::e(Helpers::webUrl('order/form.php?token=' . rawurlencode($editToken))) ?>">แก้ไขข้อมูล</a>
            <form method="post" action="<?= Helpers::e(Helpers::webUrl('order/place.php')) ?>" style="margin:0">
                <input type="hidden" name="token" value="<?= Helpers::e($token) ?>">
                <button class="btn btn-green" type="submit">ยืนยันการสั่งซื้อ</button>
            </form>
        </div>
    </main>
</div>
</body>
</html>
