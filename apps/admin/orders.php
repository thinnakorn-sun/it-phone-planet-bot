<?php

declare(strict_types=1);

require_once __DIR__ . '/_init.php';

use App\Auth;
use App\Helpers;
use App\Repositories\OrderRepository;

Auth::requireLogin();
$repo = new OrderRepository();
$status = isset($_GET['status']) && $_GET['status'] !== '' ? (string) $_GET['status'] : null;
$orders = $repo->allForAdmin($status);

$statusLabels = [
    'pending_payment' => 'รอชำระเงิน',
    'awaiting_review' => 'รอตรวจสลิป',
    'paid' => 'ชำระแล้ว',
    'shipping' => 'กำลังจัดส่ง',
    'completed' => 'สำเร็จ',
    'cancelled' => 'ยกเลิก',
    'rejected' => 'สลิปไม่ผ่าน',
];

ob_start();
?>
<h1>คำสั่งซื้อ</h1>
<p>
    <a href="orders.php">ทั้งหมด</a> |
    <a href="orders.php?status=awaiting_review">รอตรวจสลิป</a> |
    <a href="orders.php?status=paid">ชำระแล้ว</a> |
    <a href="orders.php?status=shipping">กำลังจัดส่ง</a>
</p>
<div class="panel">
    <table>
        <thead>
        <tr>
            <th>รหัส</th>
            <th>ลูกค้า</th>
            <th>ยอด</th>
            <th>สถานะ</th>
            <th>เลขพัสดุ</th>
            <th>วันที่</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($orders as $order): ?>
            <tr>
                <td><?= Helpers::e($order['order_code']) ?></td>
                <td><?= Helpers::e($order['customer_name'] ?: $order['line_user_id']) ?></td>
                <td><?= Helpers::e(Helpers::money($order['total_amount'])) ?></td>
                <td><span class="badge"><?= Helpers::e($statusLabels[$order['status']] ?? $order['status']) ?></span></td>
                <td><?= Helpers::e($order['tracking_number'] ?: '-') ?></td>
                <td><?= Helpers::e($order['created_at']) ?></td>
                <td><a href="order_view.php?id=<?= (int) $order['id'] ?>">จัดการ</a></td>
            </tr>
        <?php endforeach; ?>
        <?php if ($orders === []): ?>
            <tr><td colspan="7">ยังไม่มีคำสั่งซื้อ</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
<?php
$content = ob_get_clean();
$title = 'คำสั่งซื้อ';
$active = 'orders';
require __DIR__ . '/_layout.php';
