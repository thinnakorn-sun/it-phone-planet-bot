<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

use App\Helpers;
use App\Repositories\CustomerRepository;
use App\Security\AuthAccess;

$ctx = require_auth_access();
$error = '';
$form = [
    'member_name' => (string) ($ctx['customer']['member_name'] ?? $ctx['customer']['display_name'] ?? ''),
    'email' => (string) ($ctx['customer']['email'] ?? ''),
    'phone' => (string) ($ctx['customer']['phone'] ?? ''),
];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $form['member_name'] = trim((string) ($_POST['member_name'] ?? ''));
    $form['email'] = trim((string) ($_POST['email'] ?? ''));
    $form['phone'] = trim((string) ($_POST['phone'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $password2 = (string) ($_POST['password_confirm'] ?? '');

    try {
        if ($form['member_name'] === '' || $form['email'] === '' || $form['phone'] === '' || $password === '') {
            throw new RuntimeException('กรุณากรอกข้อมูลให้ครบ');
        }
        if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('รูปแบบอีเมลไม่ถูกต้อง');
        }
        if (strlen($password) < 6) {
            throw new RuntimeException('รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษ');
        }
        if ($password !== $password2) {
            throw new RuntimeException('รหัสผ่านยืนยันไม่ตรงกัน');
        }

        $repo = new CustomerRepository();
        if ($repo->isMember($ctx['customer'])) {
            throw new RuntimeException('บัญชี LINE นี้สมัครสมาชิกแล้ว กรุณาเข้าสู่ระบบ');
        }

        $customer = $repo->registerMember(
            $ctx['customer_id'],
            $form['member_name'],
            $form['email'],
            $form['phone'],
            $password
        );
        $doneAccess = AuthAccess::issue((string) $customer['line_user_id'], (int) $customer['id']);
        Helpers::redirect(Helpers::webUrl('auth/done.php?access=' . rawurlencode($doneAccess) . '&mode=register'));
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$loginUrl = Helpers::webUrl('auth/login.php?access=' . rawurlencode($ctx['access']));
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สมัครสมาชิก | ITPHONEPLANET</title>
    <link rel="stylesheet" href="../assets/auth.css">
</head>
<body class="auth-app">
<div class="auth-shell">
    <div class="auth-brand">
        <div class="auth-logo">iT<br><span>PHONE</span></div>
        <h1>ITPHONEPLANET</h1>
        <p>สร้างบัญชีเพื่อสั่งซื้อสินค้า</p>
    </div>
    <div class="auth-card">
        <h2>สมัครสมาชิก</h2>
        <p class="sub">กรอกข้อมูลให้ครบเพื่อเริ่มสั่งซื้อ</p>
        <?php if ($error !== ''): ?>
            <div class="auth-error"><?= Helpers::e($error) ?></div>
        <?php endif; ?>
        <form method="post">
            <input type="hidden" name="access" value="<?= Helpers::e($ctx['access']) ?>">
            <label>ชื่อ-นามสกุล</label>
            <input type="text" name="member_name" value="<?= Helpers::e($form['member_name']) ?>" required autocomplete="name">
            <label>อีเมล</label>
            <input type="email" name="email" value="<?= Helpers::e($form['email']) ?>" required autocomplete="email">
            <label>เบอร์โทร</label>
            <input type="tel" name="phone" value="<?= Helpers::e($form['phone']) ?>" required autocomplete="tel">
            <label>รหัสผ่าน</label>
            <input type="password" name="password" required autocomplete="new-password" minlength="6">
            <label>ยืนยันรหัสผ่าน</label>
            <input type="password" name="password_confirm" required autocomplete="new-password" minlength="6">
            <button class="btn btn-pink" type="submit">สมัครสมาชิก</button>
        </form>
        <div class="auth-switch">มีบัญชีแล้ว? <a href="<?= Helpers::e($loginUrl) ?>">เข้าสู่ระบบ</a></div>
    </div>
</div>
</body>
</html>
