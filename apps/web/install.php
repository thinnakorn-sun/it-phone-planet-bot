<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/bootstrap.php';

use App\Auth;
use App\Database;
use App\Env;
use App\Helpers;

$messages = [];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = Database::connection();

        $tables = $db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        $required = ['admins', 'customers', 'categories', 'products', 'orders', 'order_items', 'payments', 'faqs', 'bot_sessions'];
        $missing = array_values(array_diff($required, $tables));
        if ($missing !== []) {
            throw new RuntimeException('ยังไม่ได้ import schema.sql ตารางที่ขาด: ' . implode(', ', $missing));
        }

        Auth::ensureAdminFromEnv($db);

        $faqCount = (int) $db->query('SELECT COUNT(*) FROM faqs')->fetchColumn();
        if ($faqCount === 0) {
            $errors[] = 'ยังไม่มี FAQ — แนะนำให้ import database/seed.sql ด้วย';
        }

        $productCount = (int) $db->query('SELECT COUNT(*) FROM products')->fetchColumn();
        if ($productCount === 0) {
            $errors[] = 'ยังไม่มีสินค้า — แนะนำให้ import database/seed.sql ด้วย';
        }

        $messages[] = 'ติดตั้งแอดมินจาก .env สำเร็จ';
        $messages[] = 'Username: ' . Env::get('ADMIN_USERNAME');
        $messages[] = 'ลบหรือล็อกไฟล์ install.php หลังใช้งานจริง';
    } catch (Throwable $e) {
        $errors[] = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install</title>
    <link rel="stylesheet" href="<?= Helpers::e(Helpers::assetUrl('style.css')) ?>">
</head>
<body>
<main class="card">
    <h1>ติดตั้งระบบ</h1>
    <ol>
        <li>สร้างไฟล์ <code>.env</code> จาก <code>.env.example</code></li>
        <li>Import <code>database/schema.sql</code> และ <code>database/seed.sql</code></li>
        <li>กดปุ่มด้านล่างเพื่อสร้าง/อัปเดตรหัสแอดมินจากค่าใน .env</li>
    </ol>

    <?php foreach ($messages as $message): ?>
        <p class="ok"><?= Helpers::e($message) ?></p>
    <?php endforeach; ?>
    <?php foreach ($errors as $error): ?>
        <p class="error"><?= Helpers::e($error) ?></p>
    <?php endforeach; ?>

    <form method="post">
        <button type="submit">สร้าง/อัปเดตแอดมินจาก .env</button>
    </form>
    <p><a href="<?= Helpers::e(Helpers::adminUrl('login.php')) ?>">ไปหน้า Login</a></p>
</main>
</body>
</html>
