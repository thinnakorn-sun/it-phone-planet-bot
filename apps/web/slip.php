<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/bootstrap.php';

use App\Auth;
use App\Database;
use App\Helpers;

Auth::requireLogin();

$id = (int) ($_GET['id'] ?? 0);
$db = Database::connection();
$stmt = $db->prepare('SELECT * FROM payments WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$payment = $stmt->fetch();

if (!$payment || empty($payment['slip_path'])) {
    http_response_code(404);
    echo 'Slip not found';
    exit;
}

$path = Helpers::basePath((string) $payment['slip_path']);
if (!is_file($path)) {
    http_response_code(404);
    echo 'File missing';
    exit;
}

$mime = mime_content_type($path) ?: 'image/jpeg';
header('Content-Type: ' . $mime);
header('Content-Length: ' . (string) filesize($path));
readfile($path);
exit;
