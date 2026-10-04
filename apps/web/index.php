<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/bootstrap.php';

use App\Env;
use App\Helpers;

$appName = Env::get('APP_NAME', 'iT Phone Planet Chatbot');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= Helpers::e($appName) ?></title>
    <link rel="stylesheet" href="<?= Helpers::e(Helpers::assetUrl('style.css')) ?>">
</head>
<body class="public-home">
<main class="card">
    <h1><?= Helpers::e($appName) ?></h1>
    <p>ระบบแชทบอตสนับสนุนการขายผ่าน LINE Official Account</p>
    <ul>
        <li>Webhook: <code><?= Helpers::e(Helpers::appUrl('webhook.php')) ?></code></li>
        <li>หน้าแอดมิน: <a href="<?= Helpers::e(Helpers::adminUrl('login.php')) ?>">เข้าสู่ระบบผู้ดูแล</a></li>
        <li>ติดตั้งเริ่มต้น: <a href="<?= Helpers::e(Helpers::appUrl('install.php')) ?>">install.php</a></li>
    </ul>
</main>
</body>
</html>
