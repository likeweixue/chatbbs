<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * 个人主页留言板
 */
class Guestbook
{
    public static function list(int $profileUserId, int $limit = 20): array
    {
        return Database::fetchAll(
            'SELECT g.*, u.nickname, u.username, u.avatar
             FROM ' . Database::table('guestbook') . ' g
             LEFT JOIN ' . Database::table('users') . ' u ON u.id = g.user_id
             WHERE g.profile_user_id = ? AND g.status = 1
             ORDER BY g.id DESC LIMIT ' . max(1, min(100, $limit)),
            [$profileUserId]
        );
    }

    public static function count(int $profileUserId): int
    {
        return (int)Database::value(
            'SELECT COUNT(*) FROM ' . Database::table('guestbook') . ' WHERE profile_user_id = ? AND status = 1',
            [$profileUserId],
            0
        );
    }

    public static function add(int $profileUserId, int $userId, string $content): int
    {
        return Database::insert('guestbook', [
            'profile_user_id' => $profileUserId,
            'user_id' => $userId,
            'content' => mb_substr(trim($content), 0, 500),
            'status' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function find(int $id): ?array
    {
        return Database::fetch('SELECT * FROM ' . Database::table('guestbook') . ' WHERE id = ?', [$id]);
    }

    public static function delete(int $id): void
    {
        Database::delete('guestbook', 'id = ?', ['id' => $id]);
    }
}
