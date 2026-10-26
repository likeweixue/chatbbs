<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Medal
{
    public static function all(): array
    {
        return Database::fetchAll('SELECT * FROM ' . Database::table('medals') . ' ORDER BY sort_order ASC, id ASC');
    }

    public static function visible(): array
    {
        return Database::fetchAll('SELECT * FROM ' . Database::table('medals') . ' WHERE visible = 1 ORDER BY sort_order ASC, id ASC');
    }

    public static function find(int $id): ?array
    {
        return Database::fetch('SELECT * FROM ' . Database::table('medals') . ' WHERE id = ?', [$id]);
    }

    public static function create(array $data): int
    {
        return Database::insert('medals', $data);
    }

    public static function update(int $id, array $data): int
    {
        return Database::update('medals', $data, 'id = ?', ['id' => $id]);
    }

    public static function delete(int $id): void
    {
        Database::delete('medals', 'id = ?', ['id' => $id]);
    }

    public static function forUser(int $userId): array
    {
        return Database::fetchAll(
            'SELECT m.* FROM ' . Database::table('user_medals') . ' um
             INNER JOIN ' . Database::table('medals') . ' m ON m.id = um.medal_id
             WHERE um.user_id = ? AND m.visible = 1
             ORDER BY m.sort_order ASC, m.id ASC',
            [$userId]
        );
    }

    public static function idsForUser(int $userId): array
    {
        $rows = Database::fetchAll('SELECT medal_id FROM ' . Database::table('user_medals') . ' WHERE user_id = ?', [$userId]);
        return array_map('intval', array_column($rows, 'medal_id'));
    }

    public static function setForUser(int $userId, array $medalIds): void
    {
        Database::delete('user_medals', 'user_id = ?', ['id' => $userId]);
        foreach (array_unique(array_map('intval', $medalIds)) as $mid) {
            if ($mid > 0) {
                Database::insert('user_medals', [
                    'user_id' => $userId,
                    'medal_id' => $mid,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }
    }
}
