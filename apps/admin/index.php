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
    'paid' => (int) $db->query("SELECT COUNT(*) FROM orders WHERE status = 'paid'")->fetchColumn(),
    'shipping' => (int) $db->query("SELECT COUNT(*) FROM orders WHERE status = 'shipping'")->fetchColumn(),
    'out_of_stock' => (int) $db->query('SELECT COUNT(*) FROM products WHERE is_active = 1 AND stock <= 0')->fetchColumn(),
    'low_stock' => (int) $db->query('SELECT COUNT(*) FROM products WHERE is_active = 1 AND stock > 0 AND stock <= 5')->fetchColumn(),
];

$stockRows = $db->query(
    'SELECT id, name, stock FROM products WHERE is_active = 1 ORDER BY stock ASC, name ASC LIMIT 12'
)->fetchAll();

ob_start();
?>
<h1>แดชบอร์ด</h1>
<div class="grid-2">
    <div class="panel"><h3>สินค้าใช้งาน</h3><p style="font-size:2rem;margin:0"><?= $stats['products'] ?></p></div>
    <div class="panel"><h3>คำสั่งซื้อทั้งหมด</h3><p style="font-size:2rem;margin:0"><?= $stats['orders'] ?></p></div>
    <div class="panel"><h3>ชำระเงินแล้ว (รอส่ง)</h3><p style="font-size:2rem;margin:0"><?= $stats['paid'] ?></p></div>
    <div class="panel"><h3>กำลังจัดส่ง</h3><p style="font-size:2rem;margin:0"><?= $stats['shipping'] ?></p></div>
    <div class="panel"><h3>สินค้าหมดสต็อก</h3><p style="font-size:2rem;margin:0;color:#be123c"><?= $stats['out_of_stock'] ?></p></div>
    <div class="panel"><h3>สต็อกเหลือน้อย (≤5)</h3><p style="font-size:2rem;margin:0;color:#b45309"><?= $stats['low_stock'] ?></p></div>
</div>

<div class="panel" style="margin-top:20px">
    <h2 style="margin-top:0">สต็อกสินค้า (เรียงจากเหลือน้อย)</h2>
    <table>
        <thead>
        <tr>
            <th>ID</th>
            <th>สินค้า</th>
            <th>คงเหลือ</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($stockRows as $row): ?>
            <?php
            $stock = (int) $row['stock'];
            $color = $stock <= 0 ? '#be123c' : ($stock <= 5 ? '#b45309' : '#0f766e');
            $label = $stock <= 0 ? 'หมด' : ($stock <= 5 ? 'ใกล้หมด' : 'พอ');
            ?>
            <tr>
                <td><?= (int) $row['id'] ?></td>
                <td><?= Helpers::e($row['name']) ?></td>
                <td style="font-weight:700;color:<?= $color ?>"><?= $stock ?> ชิ้น <span class="muted">(<?= $label ?>)</span></td>
                <td><a href="products.php?edit=<?= (int) $row['id'] ?>">ปรับสต็อก</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<p style="margin-top:20px">
    <a class="btn" href="orders.php?status=paid">ออเดอร์ชำระแล้ว</a>
    <a class="btn btn-secondary" href="products.php">จัดการสินค้า / สต็อก</a>
</p>
<?php
$content = ob_get_clean();
$title = 'แดชบอร์ด';
$active = 'dashboard';
require __DIR__ . '/_layout.php';
