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

    public function updateContact(int $customerId, string $phone, string $address, ?string $name = null): void
    {
        $stmt = $this->db->prepare(
            'UPDATE customers SET phone = ?, address = ?, display_name = COALESCE(?, display_name) WHERE id = ?'
        );
        $stmt->execute([$phone, $address, $name, $customerId]);
    }
}
