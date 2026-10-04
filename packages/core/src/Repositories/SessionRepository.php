<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database;
use PDO;

final class SessionRepository
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::connection();
    }

    public function get(string $lineUserId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM bot_sessions WHERE line_user_id = ? LIMIT 1');
        $stmt->execute([$lineUserId]);
        $row = $stmt->fetch();

        if (!$row) {
            return [
                'line_user_id' => $lineUserId,
                'state' => 'idle',
                'context' => [],
            ];
        }

        $context = [];
        if (!empty($row['context_json'])) {
            $decoded = json_decode((string) $row['context_json'], true);
            if (is_array($decoded)) {
                $context = $decoded;
            }
        }

        return [
            'line_user_id' => $lineUserId,
            'state' => $row['state'],
            'context' => $context,
        ];
    }

    public function set(string $lineUserId, string $state, array $context = []): void
    {
        $json = json_encode($context, JSON_UNESCAPED_UNICODE);
        $stmt = $this->db->prepare(
            'INSERT INTO bot_sessions (line_user_id, state, context_json)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE state = VALUES(state), context_json = VALUES(context_json), updated_at = NOW()'
        );
        $stmt->execute([$lineUserId, $state, $json]);
    }

    public function clear(string $lineUserId): void
    {
        $this->set($lineUserId, 'idle', []);
    }
}
