<?php

declare(strict_types=1);

namespace App;

final class Bootstrap
{
    private static string $root = '';

    public static function init(string $rootPath): void
    {
        self::$root = rtrim($rootPath, '/\\');

        spl_autoload_register(static function (string $class): void {
            $prefix = 'App\\';
            if (!str_starts_with($class, $prefix)) {
                return;
            }

            $relative = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen($prefix)));
            $file = self::$root . '/packages/core/src/' . $relative . '.php';
            if (is_file($file)) {
                require_once $file;
            }
        });

        Env::load(self::$root . '/.env');

        $timezone = Env::get('APP_TIMEZONE', 'Asia/Bangkok') ?? 'Asia/Bangkok';
        date_default_timezone_set($timezone);

        if (Env::bool('APP_DEBUG', false)) {
            error_reporting(E_ALL);
            ini_set('display_errors', '1');
        } else {
            error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
            ini_set('display_errors', '0');
        }
    }

    public static function root(string $path = ''): string
    {
        if (self::$root === '') {
            throw new \RuntimeException('Bootstrap is not initialized');
        }

        if ($path === '') {
            return self::$root;
        }

        return self::$root . DIRECTORY_SEPARATOR . ltrim($path, '/\\');
    }
}
