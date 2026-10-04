<?php

declare(strict_types=1);

namespace App;

final class Env
{
    private static bool $loaded = false;

    /** @var array<string, string> */
    private static array $values = [];

    public static function load(string $path): void
    {
        if (self::$loaded) {
            return;
        }

        if (!is_file($path)) {
            throw new \RuntimeException('.env file not found at: ' . $path);
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            throw new \RuntimeException('Unable to read .env file');
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $pos = strpos($line, '=');
            if ($pos === false) {
                continue;
            }

            $key = trim(substr($line, 0, $pos));
            $value = trim(substr($line, $pos + 1));

            if (
                (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))
            ) {
                $value = substr($value, 1, -1);
            }

            self::$values[$key] = $value;
            $_ENV[$key] = $value;
            putenv($key . '=' . $value);
        }

        self::$loaded = true;
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        if (array_key_exists($key, self::$values)) {
            return self::$values[$key];
        }

        $value = $_ENV[$key] ?? getenv($key);
        if ($value === false || $value === null) {
            return $default;
        }

        return (string) $value;
    }

    public static function require(string $key): string
    {
        $value = self::get($key);
        if ($value === null || $value === '') {
            throw new \RuntimeException("Missing required environment variable: {$key}");
        }

        return $value;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = self::get($key);
        if ($value === null || $value === '') {
            return $default;
        }

        return in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true);
    }

    /**
     * @return list<string>
     */
    public static function list(string $key): array
    {
        $value = self::get($key, '');
        if ($value === null || trim($value) === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $value))));
    }
}
