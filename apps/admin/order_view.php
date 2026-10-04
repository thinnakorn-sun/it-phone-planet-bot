<?php

declare(strict_types=1);

require_once __DIR__ . '/_init.php';

use App\Auth;
use App\Helpers;
use App\Repositories\OrderRepository;
use App\Services\OrderService;

Auth::requireLogin();

$id = (int) ($_GET['id'] ?? 0);
$repo = new OrderRepository();
$service = new OrderService();
$order = $repo->find($id);

if (!$order) {
    http_response_code(404);
    echo 'Order not found';
    exit;
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    try {
        if ($action === 'approve') {
            $service->approvePayment($id, Auth::id());
            $message = 'อนุมัติสลิปและตัดสต๊อกแล้ว';
        } elseif ($action === 'reject') {
            $reason = trim((string) ($_POST['reason'] ?? ''));
            $service->rejectPayment($id, Auth::id(), $reason);
            $message = 'ปฏิเสธสลิปแล้ว และแจ้งลูกค้าแล้ว';
        } elseif ($action === 'tracking') {
            $tracking = trim((string) ($_POST['tracking_number'] ?? ''));
            if ($tracking === '') {
                throw new RuntimeException('กรุณากรอกเลขพัสดุ');
            }
            $service->setTracking($id, $tracking);
            $message = 'บันทึกเลขพัสดุและส่งให้ลูกค้าแล้ว';
        } elseif ($action === 'complete') {
            $repo->updateStatus($id, 'completed');
            $message = 'ปิดออเดอร์สำเร็จแล้ว';
        }
        $order = $repo->find($id) ?? $order;
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$items = $repo->items($id);
$payment = $repo->latestPayment($id);

ob_start();
?>
<h1>ออเดอร์ <?= Helpers::e($order['order_code']) ?></h1>
<?php if ($message): ?><p class="ok"><?= Helpers::e($message) ?></p><?php endif; ?>
<?php if ($error): ?><p class="error"><?= Helpers::e($error) ?></p><?php endif; ?>

<div class="grid-2">
    <div class="panel">
        <h2>รายละเอียด</h2>
        <p><strong>สถานะ:</strong> <?= Helpers::e($order['status']) ?></p>
        <p><strong>ลูกค้า:</strong> <?= Helpers::e($order['customer_name'] ?: '-') ?></p>
        <p><strong>LINE User ID:</strong> <?= Helpers::e($order['line_user_id']) ?></p>
        <p><strong>ยอดรวม:</strong> <?= Helpers::e(Helpers::money($order['total_amount'])) ?></p>
        <p><strong>ชื่อผู้รับ:</strong> <?= Helpers::e($order['shipping_name']) ?></p>
        <p><strong>เบอร์:</strong> <?= Helpers::e($order['shipping_phone']) ?></p>
        <p><strong>ที่อยู่:</strong> <?= Helpers::e($order['shipping_address']) ?></p>
        <p><strong>เลขพัสดุ:</strong> <?= Helpers::e($order['tracking_number'] ?: '-') ?></p>
        <h3>รายการสินค้า</h3>
        <ul>
            <?php foreach ($items as $item): ?>
                <li><?= Helpers::e($item['product_name']) ?> x<?= (int) $item['quantity'] ?>
                    (<?= Helpers::e(Helpers::money($item['line_total'])) ?>)</li>
            <?php endforeach; ?>
        </ul>
    </div>

    <div class="panel">
        <h2>สลิป / การชำระเงิน</h2>
        <?php if ($payment): ?>
            <p><strong>สถานะสลิป:</strong> <?= Helpers::e($payment['status']) ?></p>
            <?php if (!empty($payment['ocr_amount'])): ?>
                <p><strong>OCR ยอด:</strong> <?= Helpers::e(Helpers::money($payment['ocr_amount'])) ?></p>
            <?php endif; ?>
            <?php if (!empty($payment['ocr_text'])): ?>
                <p class="muted"><?= nl2br(Helpers::e($payment['ocr_text'])) ?></p>
            <?php endif; ?>
            <?php if (!empty($payment['slip_path'])): ?>
                <p><a href="<?= Helpers::e(Helpers::appUrl('slip.php?id=' . (int) $payment['id'])) ?>" target="_blank">เปิดดูสลิป</a></p>
            <?php endif; ?>
            <?php if ($order['status'] === 'awaiting_review'): ?>
                <div class="actions">
                    <form method="post">
                        <input type="hidden" name="action" value="approve">
                        <button type="submit">อนุมัติสลิป</button>
                    </form>
                    <form method="post">
                        <input type="hidden" name="action" value="reject">
                        <label>เหตุผลถ้าปฏิเสธ</label>
                        <input type="text" name="reason" placeholder="เช่น ยอดไม่ตรง">
                        <button class="btn-danger" type="submit">ปฏิเสธสลิป</button>
                    </form>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <p class="muted">ยังไม่มีสลิป</p>
        <?php endif; ?>

        <hr style="margin:20px 0;border:0;border-top:1px solid #d9e2ec">
        <h2>อัปเดตเลขพัสดุ</h2>
        <form method="post">
            <input type="hidden" name="action" value="tracking">
            <label>เลขพัสดุ</label>
            <input type="text" name="tracking_number" value="<?= Helpers::e($order['tracking_number'] ?? '') ?>" required>
            <p><button type="submit">บันทึกและส่งหาลูกค้า</button></p>
        </form>

        <?php if (in_array($order['status'], ['shipping', 'paid'], true)): ?>
            <form method="post">
                <input type="hidden" name="action" value="complete">
                <button class="btn-secondary" type="submit">ปิดออเดอร์ (สำเร็จ)</button>
            </form>
        <?php endif; ?>
    </div>
</div>
<p><a href="orders.php">← กลับรายการออเดอร์</a></p>
<?php
$content = ob_get_clean();
$title = 'รายละเอียดออเดอร์';
$active = 'orders';
require __DIR__ . '/_layout.php';
