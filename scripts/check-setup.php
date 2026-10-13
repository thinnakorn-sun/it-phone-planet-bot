<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

use App\Database;
use App\Env;
use App\Helpers;

$checks = [];

$checks[] = [
    'name' => 'PHP version >= 8.0',
    'ok' => version_compare(PHP_VERSION, '8.0.0', '>='),
    'detail' => PHP_VERSION,
];

foreach (['pdo_mysql', 'curl', 'mbstring', 'json'] as $ext) {
    $checks[] = [
        'name' => "ext {$ext}",
        'ok' => extension_loaded($ext),
        'detail' => extension_loaded($ext) ? 'loaded' : 'missing',
    ];
}

try {
    $db = Database::connection();
    $tables = $db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    $required = ['admins', 'customers', 'categories', 'products', 'orders', 'order_items', 'payments', 'faqs', 'bot_sessions'];
    $missing = array_values(array_diff($required, $tables));
    $checks[] = [
        'name' => 'Database connection',
        'ok' => true,
        'detail' => Env::get('DB_NAME'),
    ];
    $checks[] = [
        'name' => 'Required tables',
        'ok' => $missing === [],
        'detail' => $missing === [] ? 'all present' : ('missing: ' . implode(', ', $missing)),
    ];
    $adminCount = (int) $db->query('SELECT COUNT(*) FROM admins')->fetchColumn();
    $productCount = (int) $db->query('SELECT COUNT(*) FROM products')->fetchColumn();
    $checks[] = [
        'name' => 'Admin account exists',
        'ok' => $adminCount > 0,
        'detail' => "count={$adminCount}",
    ];
    $checks[] = [
        'name' => 'Seed products exist',
        'ok' => $productCount > 0,
        'detail' => "count={$productCount}",
    ];
} catch (Throwable $e) {
    $checks[] = [
        'name' => 'Database connection',
        'ok' => false,
        'detail' => $e->getMessage(),
    ];
}

$slipDir = Helpers::basePath('storage/slips');
$logDir = Helpers::basePath('storage/logs');
$checks[] = [
    'name' => 'storage/slips writable',
    'ok' => is_dir($slipDir) && is_writable($slipDir),
    'detail' => $slipDir,
];
$checks[] = [
    'name' => 'storage/logs writable',
    'ok' => is_dir($logDir) && is_writable($logDir),
    'detail' => $logDir,
];

$lineSecret = Env::get('LINE_CHANNEL_SECRET', '') ?? '';
$lineToken = Env::get('LINE_CHANNEL_ACCESS_TOKEN', '') ?? '';
$checks[] = [
    'name' => 'LINE keys filled',
    'ok' => $lineSecret !== '' && $lineToken !== '',
    'detail' => ($lineSecret === '' || $lineToken === '')
        ? 'ยังว่าง — ใส่ใน .env ทีหลังได้'
        : 'configured',
];

$failed = 0;
foreach ($checks as $check) {
    $mark = $check['ok'] ? 'OK' : 'FAIL';
    if (!$check['ok']) {
        $failed++;
    }
    echo "[{$mark}] {$check['name']} — {$check['detail']}\n";
}

echo "\nAPP_URL=" . Helpers::appUrl() . "\n";
echo "ADMIN_URL=" . Helpers::adminUrl() . "\n";
echo "WEBHOOK=" . Helpers::appUrl('webhook.php') . "\n";

// LINE keys ว่างถือว่ายังไม่พร้อม แต่ไม่ถือเป็น error ของเครื่อง local
$criticalFailed = 0;
foreach ($checks as $check) {
    if (!$check['ok'] && $check['name'] !== 'LINE keys filled') {
        $criticalFailed++;
    }
}

exit($criticalFailed > 0 ? 1 : 0);
