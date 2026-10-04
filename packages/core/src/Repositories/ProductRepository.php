<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database;
use PDO;

final class ProductRepository
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::connection();
    }

    public function allActiveCategories(): array
    {
        $stmt = $this->db->query(
            'SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order ASC, name ASC'
        );
        return $stmt->fetchAll();
    }

    public function allCategories(): array
    {
        $stmt = $this->db->query(
            'SELECT * FROM categories ORDER BY sort_order ASC, name ASC'
        );
        return $stmt->fetchAll();
    }

    public function findCategory(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM categories WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function productsByCategory(int $categoryId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM products
             WHERE category_id = ? AND is_active = 1
             ORDER BY name ASC'
        );
        $stmt->execute([$categoryId]);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT p.*, c.name AS category_name
             FROM products p
             JOIN categories c ON c.id = p.category_id
             WHERE p.id = ?
             LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function allForAdmin(): array
    {
        $stmt = $this->db->query(
            'SELECT p.*, c.name AS category_name
             FROM products p
             JOIN categories c ON c.id = p.category_id
             ORDER BY p.id DESC'
        );
        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO products (category_id, sku, name, description, price, stock, image_url, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['category_id'],
            $data['sku'] ?: null,
            $data['name'],
            $data['description'] ?: null,
            $data['price'],
            $data['stock'],
            $data['image_url'] ?: null,
            $data['is_active'] ?? 1,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->db->prepare(
            'UPDATE products
             SET category_id = ?, sku = ?, name = ?, description = ?, price = ?, stock = ?, image_url = ?, is_active = ?
             WHERE id = ?'
        );
        $stmt->execute([
            $data['category_id'],
            $data['sku'] ?: null,
            $data['name'],
            $data['description'] ?: null,
            $data['price'],
            $data['stock'],
            $data['image_url'] ?: null,
            $data['is_active'] ?? 1,
            $id,
        ]);
    }

    public function adjustStock(int $productId, int $delta): void
    {
        $stmt = $this->db->prepare('UPDATE products SET stock = stock + ? WHERE id = ?');
        $stmt->execute([$delta, $productId]);
    }

    public function search(string $keyword): array
    {
        $like = '%' . $keyword . '%';
        $stmt = $this->db->prepare(
            'SELECT * FROM products
             WHERE is_active = 1 AND (name LIKE ? OR description LIKE ? OR sku LIKE ?)
             ORDER BY name ASC
             LIMIT 10'
        );
        $stmt->execute([$like, $like, $like]);
        return $stmt->fetchAll();
    }
}
