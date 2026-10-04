<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database;
use PDO;

final class FaqRepository
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::connection();
    }

    public function allActive(): array
    {
        $stmt = $this->db->query(
            'SELECT * FROM faqs WHERE is_active = 1 ORDER BY sort_order ASC, id ASC'
        );
        return $stmt->fetchAll();
    }

    public function match(string $text): ?array
    {
        $text = mb_strtolower(trim($text));
        foreach ($this->allActive() as $faq) {
            $keywords = array_map('trim', explode(',', (string) $faq['keywords']));
            foreach ($keywords as $keyword) {
                if ($keyword !== '' && mb_strpos($text, mb_strtolower($keyword)) !== false) {
                    return $faq;
                }
            }
        }

        return null;
    }
}
