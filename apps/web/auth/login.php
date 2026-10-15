<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

use App\Helpers;
use App\Repositories\CustomerRepository;
use App\Security\AuthAccess;

$ctx = require_auth_access();
$error = '';
$loginValue = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $loginValue = trim((string) ($_POST['login'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    try {
        if ($loginValue === '' || $password === '') {
            throw new RuntimeException('กรุณากรอกอีเมล/เบอร์โทร และรหัสผ่าน');
        }
        $repo = new CustomerRepository();
        $customer = $repo->loginMember($loginValue, $password, $ctx['line_user_id']);
        $doneAccess = AuthAccess::issue((string) $customer['line_user_id'], (int) $customer['id']);
        Helpers::redirect(Helpers::webUrl('auth/done.php?access=' . rawurlencode($doneAccess) . '&mode=login'));
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$registerUrl = Helpers::webUrl('auth/register.php?access=' . rawurlencode($ctx['access']));
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ | ITPHONEPLANET</title>
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
        <h2>เข้าสู่ระบบ</h2>
        <p class="sub">กรอกอีเมลหรือเบอร์โทรที่สมัครไว้</p>
        <?php if ($error !== ''): ?>
            <div class="auth-error"><?= Helpers::e($error) ?></div>
        <?php endif; ?>
        <form method="post">
            <input type="hidden" name="access" value="<?= Helpers::e($ctx['access']) ?>">
            <label>อีเมล หรือ เบอร์โทร</label>
            <input type="text" name="login" value="<?= Helpers::e($loginValue) ?>" placeholder="you@email.com หรือ 08xxxxxxxx" required autocomplete="username">
            <label>รหัสผ่าน</label>
            <input type="password" name="password" placeholder="••••••••" required autocomplete="current-password">
            <button class="btn btn-pink" type="submit">เข้าสู่ระบบ</button>
        </form>
        <div class="auth-switch">ยังไม่มีบัญชี? <a href="<?= Helpers::e($registerUrl) ?>">สมัครสมาชิก</a></div>
    </div>
</div>
</body>
</html>
