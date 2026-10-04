<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/bootstrap.php';

use App\Helpers;
use App\Line\WebhookHandler;

try {
    $body = file_get_contents('php://input') ?: '';
    $signature = $_SERVER['HTTP_X_LINE_SIGNATURE'] ?? null;
    (new WebhookHandler())->handle($body, $signature);
} catch (Throwable $e) {
    Helpers::log('Webhook fatal: ' . $e->getMessage());
    http_response_code(500);
    echo 'Error';
}
