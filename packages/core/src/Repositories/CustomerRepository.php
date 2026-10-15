<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database;
use PDO;

final class CustomerRepository
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::connection();
    }

    public function findOrCreateByLineId(string $lineUserId, ?string $displayName = null): array
    {
        $stmt = $this->db->prepare('SELECT * FROM customers WHERE line_user_id = ? LIMIT 1');
        $stmt->execute([$lineUserId]);
        $customer = $stmt->fetch();

        if ($customer) {
            if ($displayName && $displayName !== ($customer['display_name'] ?? null)) {
                $update = $this->db->prepare('UPDATE customers SET display_name = ? WHERE id = ?');
                $update->execute([$displayName, $customer['id']]);
                $customer['display_name'] = $displayName;
            }
            return $customer;
        }

        $insert = $this->db->prepare(
            'INSERT INTO customers (line_user_id, display_name) VALUES (?, ?)'
        );
        $insert->execute([$lineUserId, $displayName]);

        return $this->findOrCreateByLineId($lineUserId, $displayName);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM customers WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM customers WHERE email = ? LIMIT 1');
        $stmt->execute([mb_strtolower(trim($email))]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByEmailOrPhone(string $login): ?array
    {
        $login = trim($login);
        $email = mb_strtolower($login);
        $phone = preg_replace('/\D+/', '', $login) ?: $login;

        $stmt = $this->db->prepare(
            'SELECT * FROM customers
             WHERE email = ? OR phone = ? OR REPLACE(phone, "-", "") = ?
             LIMIT 1'
        );
        $stmt->execute([$email, $login, $phone]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function isMember(array $customer): bool
    {
        return (int) ($customer['is_member'] ?? 0) === 1
            && !empty($customer['password_hash']);
    }

    public function registerMember(
        int $customerId,
        string $memberName,
        string $email,
        string $phone,
        string $password
    ): array {
        $email = mb_strtolower(trim($email));
        $existing = $this->findByEmail($email);
        if ($existing && (int) $existing['id'] !== $customerId) {
            throw new \RuntimeException('อีเมลนี้ถูกใช้สมัครแล้ว กรุณาใช้อีเมลอื่น หรือเข้าสู่ระบบ');
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare(
            'UPDATE customers
             SET member_name = ?, display_name = COALESCE(NULLIF(display_name, ""), ?),
                 email = ?, phone = ?, password_hash = ?, is_member = 1, last_login_at = NOW()
             WHERE id = ?'
        );
        $stmt->execute([$memberName, $memberName, $email, $phone, $hash, $customerId]);

        $customer = $this->findById($customerId);
        if (!$customer) {
            throw new \RuntimeException('ไม่พบข้อมูลสมาชิก');
        }
        return $customer;
    }

    public function loginMember(string $login, string $password, string $lineUserId): array
    {
        $found = $this->findByEmailOrPhone($login);
        if (!$found || empty($found['password_hash'])) {
            throw new \RuntimeException('ไม่พบบัญชีนี้ หรือยังไม่ได้สมัครสมาชิก');
        }

        if (!password_verify($password, (string) $found['password_hash'])) {
            throw new \RuntimeException('รหัสผ่านไม่ถูกต้อง');
        }

        // ผูก LINE ปัจจุบันกับบัญชีสมาชิก (กรณีสมัครจาก LINE อื่นแล้วล็อกอินใหม่)
        if ((string) $found['line_user_id'] !== $lineUserId) {
            $conflict = $this->db->prepare('SELECT id FROM customers WHERE line_user_id = ? AND id <> ? LIMIT 1');
            $conflict->execute([$lineUserId, $found['id']]);
            $other = $conflict->fetch();
            if ($other) {
                // ย้าย LINE id มาที่บัญชีที่ล็อกอินสำเร็จ และเคลียร์ของเก่า
                $this->db->prepare('UPDATE customers SET line_user_id = CONCAT("old_", id, "_", line_user_id) WHERE id = ?')
                    ->execute([(int) $other['id']]);
            }
            $this->db->prepare('UPDATE customers SET line_user_id = ? WHERE id = ?')
                ->execute([$lineUserId, $found['id']]);
        }

        $this->db->prepare(
            'UPDATE customers SET is_member = 1, last_login_at = NOW() WHERE id = ?'
        )->execute([(int) $found['id']]);

        $customer = $this->findById((int) $found['id']);
        if (!$customer) {
            throw new \RuntimeException('เข้าสู่ระบบไม่สำเร็จ');
        }
        return $customer;
    }

    public function updateContact(int $customerId, string $phone, string $address, ?string $name = null): void
    {
        $stmt = $this->db->prepare(
            'UPDATE customers SET phone = ?, address = ?, display_name = COALESCE(?, display_name) WHERE id = ?'
        );
        $stmt->execute([$phone, $address, $name, $customerId]);
    }
}
