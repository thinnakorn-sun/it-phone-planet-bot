<?php

declare(strict_types=1);

require_once __DIR__ . '/_init.php';

use App\Auth;
use App\Database;
use App\Helpers;

Auth::requireLogin();
$db = Database::connection();

$stats = [
    'products' => (int) $db->query('SELECT COUNT(*) FROM products WHERE is_active = 1')->fetchColumn(),
    'orders' => (int) $db->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
    'awaiting' => (int) $db->query("SELECT COUNT(*) FROM orders WHERE status = 'awaiting_review'")->fetchColumn(),
    'shipping' => (int) $db->query("SELECT COUNT(*) FROM orders WHERE status = 'shipping'")->fetchColumn(),
];

ob_start();
?>
<h1>แดชบอร์ด</h1>
<div class="grid-2">
    <div class="panel"><h3>สินค้าใช้งาน</h3><p style="font-size:2rem;margin:0"><?= $stats['products'] ?></p></div>
    <div class="panel"><h3>คำสั่งซื้อทั้งหมด</h3><p style="font-size:2rem;margin:0"><?= $stats['orders'] ?></p></div>
    <div class="panel"><h3>รอตรวจสลิป</h3><p style="font-size:2rem;margin:0"><?= $stats['awaiting'] ?></p></div>
    <div class="panel"><h3>กำลังจัดส่ง</h3><p style="font-size:2rem;margin:0"><?= $stats['shipping'] ?></p></div>
</div>
<p style="margin-top:20px">
    <a class="btn" href="orders.php?status=awaiting_review">ไปตรวจสลิป</a>
    <a class="btn btn-secondary" href="products.php">จัดการสินค้า</a>
</p>
<?php
$content = ob_get_clean();
$title = 'แดชบอร์ด';
$active = 'dashboard';
require __DIR__ . '/_layout.php';
