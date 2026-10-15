<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once __DIR__ . '/_customer.php';

use App\Database;
use App\Helpers;

$auth = require_customer_access();

$db = Database::connection();
$stmt = $db->prepare('SELECT * FROM customers WHERE id = ? AND line_user_id = ? LIMIT 1');
$stmt->execute([$auth['customer_id'], $auth['line_user_id']]);
$customer = $stmt->fetch();
if (!$customer) {
    http_response_code(403);
    echo 'ไม่มีสิทธิ์';
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>บัญชีของฉัน</title>
    <link rel="stylesheet" href="../assets/order.css">
</head>
<body class="order-app">
<div class="order-shell" style="background:#f3f5f7">
    <div class="topbar green">
        <a class="back" href="<?= Helpers::e(Helpers::webUrl('order/home.php?access=' . rawurlencode($auth['access']))) ?>">←</a>
        <h1>บัญชีของฉัน</h1>
    </div>
    <main class="order-wrap">
        <div class="card">
            <h2 style="margin-top:0">ข้อมูลของคุณ</h2>
            <div class="kv"><span class="label">สถานะ</span><span class="value"><?= !empty($customer['is_member']) ? 'สมาชิกแล้ว' : 'ยังไม่เข้าสู่ระบบ' ?></span></div>
            <div class="kv"><span class="label">ชื่อ</span><span class="value"><?= Helpers::e($customer['member_name'] ?: $customer['display_name'] ?: '-') ?></span></div>
            <div class="kv"><span class="label">อีเมล</span><span class="value"><?= Helpers::e($customer['email'] ?: '-') ?></span></div>
            <div class="kv"><span class="label">เบอร์โทร</span><span class="value"><?= Helpers::e($customer['phone'] ?: '-') ?></span></div>
            <div class="kv"><span class="label">ที่อยู่</span><span class="value"><?= Helpers::e($customer['address'] ?: '-') ?></span></div>
            <p class="muted" style="margin-top:12px">ข้อมูลนี้เป็นของบัญชี LINE คุณเท่านั้น คนอื่นเปิดลิงก์นี้ไม่ได้</p>
        </div>
        <?= customer_nav($auth['access'], 'account') ?>
    </main>
</div>
</body>
</html>
