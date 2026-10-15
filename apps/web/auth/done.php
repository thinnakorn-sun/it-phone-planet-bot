<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

use App\Helpers;
use App\Repositories\CustomerRepository;

$ctx = require_auth_access();
$mode = (string) ($_GET['mode'] ?? 'login');
$repo = new CustomerRepository();
$customer = $repo->findById($ctx['customer_id']) ?? $ctx['customer'];
$name = (string) ($customer['member_name'] ?: $customer['display_name'] ?: 'สมาชิก');
$isLogin = $mode === 'login';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isLogin ? 'เข้าสู่ระบบสำเร็จ' : 'สมัครสมาชิกสำเร็จ' ?> | ITPHONEPLANET</title>
    <link rel="stylesheet" href="../assets/auth.css">
</head>
<body class="auth-app">
<div class="auth-shell">
    <div class="auth-brand">
        <div class="auth-logo">iT<br><span>PHONE</span></div>
        <h1>ITPHONEPLANET</h1>
        <p>ร้านจำหน่ายอุปกรณ์ไอทีและมือถือ</p>
    </div>
    <div class="auth-card">
        <div class="auth-ok">
            <div class="check">✓</div>
            <h2><?= $isLogin ? 'เข้าสู่ระบบสำเร็จ' : 'สมัครสมาชิกสำเร็จ' ?></h2>
            <p class="sub">สวัสดี <?= Helpers::e($name) ?> สามารถกลับไปที่ LINE เพื่อเลือกซื้อสินค้าได้เลย</p>
        </div>
        <p class="sub" style="text-align:center;margin:0">พิมพ์ใน LINE ว่า <strong>เลือกซื้อสินค้า</strong> หรือกดปุ่มในแชท</p>
    </div>
</div>
</body>
</html>
