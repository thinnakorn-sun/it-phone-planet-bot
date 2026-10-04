<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database;
use PDO;

final class OrderRepository
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::connection();
    }

    public function create(array $order, array $items): int
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO orders
                (order_code, customer_id, status, total_amount, shipping_name, shipping_phone, shipping_address)
                VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $order['order_code'],
                $order['customer_id'],
                $order['status'],
                $order['total_amount'],
                $order['shipping_name'],
                $order['shipping_phone'],
                $order['shipping_address'],
            ]);
            $orderId = (int) $this->db->lastInsertId();

            $itemStmt = $this->db->prepare(
                'INSERT INTO order_items (order_id, product_id, product_name, unit_price, quantity, line_total)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );

            foreach ($items as $item) {
                $itemStmt->execute([
                    $orderId,
                    $item['product_id'],
                    $item['product_name'],
                    $item['unit_price'],
                    $item['quantity'],
                    $item['line_total'],
                ]);
            }

            $this->db->commit();
            return $orderId;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT o.*, c.line_user_id, c.display_name AS customer_name
             FROM orders o
             JOIN customers c ON c.id = o.customer_id
             WHERE o.id = ?
             LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByCode(string $code): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT o.*, c.line_user_id, c.display_name AS customer_name
             FROM orders o
             JOIN customers c ON c.id = o.customer_id
             WHERE o.order_code = ?
             LIMIT 1'
        );
        $stmt->execute([$code]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function latestForCustomer(int $customerId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM orders WHERE customer_id = ? ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute([$customerId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function items(int $orderId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM order_items WHERE order_id = ?');
        $stmt->execute([$orderId]);
        return $stmt->fetchAll();
    }

    public function allForAdmin(?string $status = null): array
    {
        if ($status) {
            $stmt = $this->db->prepare(
                'SELECT o.*, c.display_name AS customer_name, c.line_user_id
                 FROM orders o
                 JOIN customers c ON c.id = o.customer_id
                 WHERE o.status = ?
                 ORDER BY o.id DESC'
            );
            $stmt->execute([$status]);
            return $stmt->fetchAll();
        }

        $stmt = $this->db->query(
            'SELECT o.*, c.display_name AS customer_name, c.line_user_id
             FROM orders o
             JOIN customers c ON c.id = o.customer_id
             ORDER BY o.id DESC'
        );
        return $stmt->fetchAll();
    }

    public function updateStatus(int $orderId, string $status, ?string $adminNote = null): void
    {
        $stmt = $this->db->prepare(
            'UPDATE orders SET status = ?, admin_note = COALESCE(?, admin_note) WHERE id = ?'
        );
        $stmt->execute([$status, $adminNote, $orderId]);
    }

    public function setTracking(int $orderId, string $trackingNumber): void
    {
        $stmt = $this->db->prepare(
            'UPDATE orders SET tracking_number = ?, status = ? WHERE id = ?'
        );
        $stmt->execute([$trackingNumber, 'shipping', $orderId]);
    }

    public function addPayment(int $orderId, ?string $slipPath, ?string $messageId, ?string $ocrText = null, ?float $ocrAmount = null): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO payments (order_id, slip_path, slip_line_message_id, ocr_text, ocr_amount, status)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$orderId, $slipPath, $messageId, $ocrText, $ocrAmount, 'pending']);
        return (int) $this->db->lastInsertId();
    }

    public function latestPayment(int $orderId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM payments WHERE order_id = ? ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute([$orderId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function reviewPayment(int $paymentId, string $status, int $adminId): void
    {
        $stmt = $this->db->prepare(
            'UPDATE payments SET status = ?, reviewed_by = ?, reviewed_at = NOW() WHERE id = ?'
        );
        $stmt->execute([$status, $adminId, $paymentId]);
    }

    public function pendingPaymentOrderForCustomer(int $customerId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM orders
             WHERE customer_id = ? AND status IN ('pending_payment', 'awaiting_review')
             ORDER BY id DESC
             LIMIT 1"
        );
        $stmt->execute([$customerId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
