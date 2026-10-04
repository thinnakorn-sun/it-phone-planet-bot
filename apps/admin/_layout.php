<?php

declare(strict_types=1);

use App\Env;
use App\Helpers;

/** @var string $title */
/** @var string $content */
/** @var string $active */

$appName = Env::get('APP_NAME', 'Admin');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= Helpers::e($title) ?> | <?= Helpers::e($appName) ?></title>
    <link rel="stylesheet" href="<?= Helpers::e(Helpers::assetUrl('style.css')) ?>">
</head>
<body>
<div class="layout">
    <aside class="sidebar">
        <h2>ผู้ดูแลระบบ</h2>
        <a class="<?= ($active ?? '') === 'dashboard' ? 'active' : '' ?>" href="<?= Helpers::e(Helpers::adminUrl('index.php')) ?>">แดชบอร์ด</a>
        <a class="<?= ($active ?? '') === 'products' ? 'active' : '' ?>" href="<?= Helpers::e(Helpers::adminUrl('products.php')) ?>">สินค้า</a>
        <a class="<?= ($active ?? '') === 'orders' ? 'active' : '' ?>" href="<?= Helpers::e(Helpers::adminUrl('orders.php')) ?>">คำสั่งซื้อ</a>
        <a class="<?= ($active ?? '') === 'faqs' ? 'active' : '' ?>" href="<?= Helpers::e(Helpers::adminUrl('faqs.php')) ?>">FAQ</a>
        <a href="<?= Helpers::e(Helpers::adminUrl('logout.php')) ?>">ออกจากระบบ</a>
    </aside>
    <main class="content">
        <?= $content ?>
    </main>
</div>
</body>
</html>
