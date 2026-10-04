<?php

declare(strict_types=1);

require_once __DIR__ . '/_init.php';

use App\Auth;
use App\Helpers;

if (Auth::check()) {
    Helpers::redirect(Helpers::adminUrl('index.php'));
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    if (Auth::attempt($username, $password)) {
        Helpers::redirect(Helpers::adminUrl('index.php'));
    }
    $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin</title>
    <link rel="stylesheet" href="<?= Helpers::e(Helpers::assetUrl('style.css')) ?>">
</head>
<body>
<main class="card">
    <h1>เข้าสู่ระบบผู้ดูแล</h1>
    <p class="muted">ใช้ username/password จากไฟล์ .env หลังรัน install.php</p>
    <?php if ($error): ?><p class="error"><?= Helpers::e($error) ?></p><?php endif; ?>
    <form method="post">
        <label>Username</label>
        <input type="text" name="username" required>
        <label>Password</label>
        <input type="password" name="password" required>
        <p style="margin-top:16px"><button type="submit">เข้าสู่ระบบ</button></p>
    </form>
</main>
</body>
</html>
