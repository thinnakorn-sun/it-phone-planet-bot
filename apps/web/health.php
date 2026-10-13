<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/bootstrap.php';

use App\Database;
use App\Env;
use App\Helpers;

$status = [
    'app' => Env::get('APP_NAME', 'iT Phone Planet Chatbot'),
    'ok' => true,
    'php' => PHP_VERSION,
    'database' => false,
    'line_configured' => false,
    'urls' => [
        'app' => Helpers::appUrl(),
        'admin' => Helpers::adminUrl(),
        'webhook' => Helpers::appUrl('webhook.php'),
    ],
    'time' => date('c'),
];

try {
    Database::connection()->query('SELECT 1');
    $status['database'] = true;
} catch (Throwable $e) {
    $status['ok'] = false;
    $status['database_error'] = $e->getMessage();
}

$secret = Env::get('LINE_CHANNEL_SECRET', '') ?? '';
$token = Env::get('LINE_CHANNEL_ACCESS_TOKEN', '') ?? '';
$status['line_configured'] = ($secret !== '' && $token !== '');

http_response_code($status['ok'] ? 200 : 500);
header('Content-Type: application/json; charset=utf-8');
echo json_encode($status, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
