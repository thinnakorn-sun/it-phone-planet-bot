<?php

declare(strict_types=1);

namespace App;

final class Helpers
{
    public static function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function money(float|string|null $amount): string
    {
        return number_format((float) $amount, 2) . ' บาท';
    }

    public static function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }

    public static function jsonResponse(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function basePath(string $path = ''): string
    {
        return Bootstrap::root($path);
    }

    public static function appUrl(string $path = ''): string
    {
        $base = rtrim(Env::get('APP_URL', '') ?? '', '/');
        if ($path === '') {
            return $base;
        }

        return $base . '/' . ltrim($path, '/');
    }

    public static function adminUrl(string $path = ''): string
    {
        $base = rtrim(Env::get('ADMIN_URL', '') ?? '', '/');
        if ($base === '') {
            // fallback เผื่อยังไม่ได้ตั้ง ADMIN_URL
            $base = preg_replace('#/apps/web$#', '/apps/admin', self::appUrl()) ?: (self::appUrl() . '/../admin');
            $base = rtrim((string) $base, '/');
        }

        if ($path === '') {
            return $base;
        }

        return $base . '/' . ltrim($path, '/');
    }

    public static function assetUrl(string $path = ''): string
    {
        $base = rtrim(Env::get('ASSET_URL', '') ?? '', '/');
        if ($base === '') {
            $base = self::appUrl('assets');
        }

        if ($path === '') {
            return $base;
        }

        return $base . '/' . ltrim($path, '/');
    }

    public static function orderCode(): string
    {
        return 'ITP' . date('ymd') . strtoupper(bin2hex(random_bytes(2)));
    }

    public static function log(string $message): void
    {
        $file = self::basePath('storage/logs/app.log');
        $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
        file_put_contents($file, $line, FILE_APPEND);
    }
}
