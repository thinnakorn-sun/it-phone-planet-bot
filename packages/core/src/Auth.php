<?php

declare(strict_types=1);

namespace App;

use PDO;

final class Auth
{
    public static function startSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    public static function attempt(string $username, string $password): bool
    {
        self::startSession();
        $db = Database::connection();
        $stmt = $db->prepare('SELECT * FROM admins WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if (!$admin || !password_verify($password, (string) $admin['password_hash'])) {
            return false;
        }

        $_SESSION['admin_id'] = (int) $admin['id'];
        $_SESSION['admin_username'] = (string) $admin['username'];
        $_SESSION['admin_name'] = (string) $admin['display_name'];
        return true;
    }

    public static function check(): bool
    {
        self::startSession();
        return !empty($_SESSION['admin_id']);
    }

    public static function id(): int
    {
        self::startSession();
        return (int) ($_SESSION['admin_id'] ?? 0);
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            Helpers::redirect(Helpers::adminUrl('login.php'));
        }
    }

    public static function logout(): void
    {
        self::startSession();
        $_SESSION = [];
        session_destroy();
    }

    public static function ensureAdminFromEnv(PDO $db): void
    {
        $username = Env::require('ADMIN_USERNAME');
        $password = Env::require('ADMIN_PASSWORD');
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $db->prepare('SELECT id FROM admins WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $existing = $stmt->fetch();

        if ($existing) {
            $update = $db->prepare('UPDATE admins SET password_hash = ? WHERE id = ?');
            $update->execute([$hash, $existing['id']]);
            return;
        }

        $insert = $db->prepare(
            'INSERT INTO admins (username, password_hash, display_name) VALUES (?, ?, ?)'
        );
        $insert->execute([$username, $hash, 'Administrator']);
    }
}
