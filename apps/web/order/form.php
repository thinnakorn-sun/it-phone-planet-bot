<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/bootstrap.php';

use App\Helpers;
use App\Repositories\ProductRepository;
use App\Security\OrderToken;

$error = '';
$token = (string) ($_GET['token'] ?? $_POST['token'] ?? '');

try {
    $payload = OrderToken::parse($token);
} catch (Throwable $e) {
    http_response_code(400);
    echo '<p style="font-family:sans-serif;padding:24px">' . Helpers::e($e->getMessage()) . '</p>';
    exit;
}

$repo = new ProductRepository();
$product = $repo->find((int) $payload['product_id']);
if (!$product || !(int) $product['is_active']) {
    echo '<p style="font-family:sans-serif;padding:24px">ไม่พบสินค้า</p>';
    exit;
}

$qty = max(1, (int) ($payload['quantity'] ?? 1));
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $qty = max(1, min(99, (int) ($_POST['quantity'] ?? 1)));
    $name = trim((string) ($_POST['shipping_name'] ?? ''));
    $phone = trim((string) ($_POST['shipping_phone'] ?? ''));
    $address = trim((string) ($_POST['shipping_address'] ?? ''));
    $payment = trim((string) ($_POST['payment_method'] ?? 'โอนเงินผ่านธนาคาร'));

    if ($name === '' || $phone === '' || $address === '') {
        $error = 'กรุณากรอกชื่อ เบอร์โทร และที่อยู่ให้ครบ';
    } elseif ((int) $product['stock'] < $qty) {
        $error = 'สินค้าคงเหลือไม่พอ';
    } else {
        $next = OrderToken::make([
            'line_user_id' => $payload['line_user_id'],
            'customer_id' => (int) $payload['customer_id'],
            'product_id' => (int) $product['id'],
            'quantity' => $qty,
            'shipping_name' => $name,
            'shipping_phone' => $phone,
            'shipping_address' => $address,
            'payment_method' => $payment,
        ]);
        Helpers::redirect(Helpers::webUrl('order/summary.php?token=' . rawurlencode($next)));
    }
}

$img = Helpers::productImageUrl($product['image_url'] ?? null)
    ?: Helpers::localProductImageUrl($product['image_url'] ?? null);
$nameVal = (string) ($_POST['shipping_name'] ?? '');
$phoneVal = (string) ($_POST['shipping_phone'] ?? '');
$addressVal = (string) ($_POST['shipping_address'] ?? '');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>กรอกข้อมูลสั่งซื้อ</title>
    <link rel="stylesheet" href="../assets/order.css">
</head>
<body class="order-app">
<div class="order-shell">
    <div class="topbar">
        <a class="back" href="javascript:history.back()">←</a>
        <h1>🛒 สั่งซื้อสินค้า</h1>
        <span class="icon">🛍️</span>
    </div>

    <main class="order-wrap">
        <?php if ($error): ?><div class="error"><?= Helpers::e($error) ?></div><?php endif; ?>

        <form method="post">
            <input type="hidden" name="token" value="<?= Helpers::e($token) ?>">

            <div class="section-title"><span class="dot">📦</span> ข้อมูลสินค้า</div>
            <div class="card">
                <div class="product-box">
                    <?php if ($img): ?>
                        <img src="<?= Helpers::e($img) ?>" alt="">
                    <?php else: ?>
                        <div style="width:78px;height:78px;border-radius:14px;background:#f3f6f9"></div>
                    <?php endif; ?>
                    <div>
                        <div class="name"><?= Helpers::e($product['name']) ?></div>
                        <div class="price"><?= Helpers::e(Helpers::baht($product['price'])) ?></div>
                        <div class="qty-row">
                            <span>จำนวน</span>
                            <div class="qty-control">
                                <button type="button" onclick="chg(-1)">−</button>
                                <input id="quantity" type="number" name="quantity" min="1" max="99" value="<?= (int) $qty ?>">
                                <button type="button" onclick="chg(1)">+</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="section-title"><span class="dot">📍</span> ที่อยู่จัดส่ง</div>
            <div class="card">
                <label>ชื่อ-สกุล</label>
                <input type="text" name="shipping_name" required placeholder="กรอกชื่อ-สกุล" value="<?= Helpers::e($nameVal) ?>">
                <label>เบอร์โทรศัพท์</label>
                <input type="tel" name="shipping_phone" required placeholder="กรอกเบอร์โทรศัพท์" value="<?= Helpers::e($phoneVal) ?>">
                <label>ที่อยู่</label>
                <textarea name="shipping_address" required placeholder="กรอกที่อยู่จัดส่ง"><?= Helpers::e($addressVal) ?></textarea>
            </div>

            <div class="section-title"><span class="dot">💳</span> วิธีการชำระเงิน</div>
            <div class="card">
                <select name="payment_method">
                    <option value="โอนเงินผ่านธนาคาร">โอนเงินผ่านธนาคาร</option>
                </select>
            </div>

            <button class="btn btn-pink" type="submit">🛍️ ยืนยันการสั่งซื้อ</button>
        </form>
    </main>
</div>
<script>
function chg(d){
  const el=document.getElementById('quantity');
  let v=parseInt(el.value||'1',10)+d;
  if(v<1)v=1; if(v>99)v=99;
  el.value=v;
}
</script>
</body>
</html>
