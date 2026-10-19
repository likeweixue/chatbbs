<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * 推荐 / 避雷
 */
class Recommend
{
    public static function toggle(int $userId, int $threadId, int $kind, string $reason = ''): array
    {
        $row = Database::fetch(
            'SELECT * FROM ' . Database::table('recommendations') . ' WHERE thread_id = ? AND user_id = ?',
            [$threadId, $userId]
        );
        if ($row) {
            if ((int)$row['kind'] === $kind) {
                Database::delete('recommendations', 'id = ?', ['id' => $row['id']]);
                self::sync($threadId);
                return ['active' => false, 'msg' => '已取消'];
            }
            Database::update('recommendations', ['kind' => $kind, 'reason' => $reason], 'id = ?', ['id' => $row['id']]);
            self::sync($threadId);
            return ['active' => true, 'msg' => $kind > 0 ? '推荐成功' : '已标记避雷'];
        }
        Database::insert('recommendations', [
            'thread_id' => $threadId, 'user_id' => $userId, 'kind' => $kind, 'reason' => $reason,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        self::sync($threadId);
        return ['active' => true, 'msg' => $kind > 0 ? '推荐成功' : '已标记避雷'];
    }

    public static function sync(int $threadId): void
    {
        $n = (int)Database::value(
            'SELECT COUNT(*) FROM ' . Database::table('recommendations') . ' WHERE thread_id = ? AND kind = 1',
            [$threadId], 0
        );
        Database::update('threads', ['recommends' => $n], 'id = ?', ['id' => $threadId]);
    }

    public static function exists(int $userId, int $threadId): ?array
    {
        return Database::fetch(
            'SELECT * FROM ' . Database::table('recommendations') . ' WHERE thread_id = ? AND user_id = ?',
            [$threadId, $userId]
        );
    }

    public static function list(int $page = 1, int $perPage = 20, int $kind = 1): array
    {
        $where = 'r.kind = ? AND t.status = 1';
        $params = [$kind];
        $total = (int)Database::value(
            'SELECT COUNT(*) FROM ' . Database::table('recommendations') . ' r
             JOIN ' . Database::table('threads') . ' t ON t.id = r.thread_id WHERE ' . $where,
            $params, 0
        );
        $offset = ($page - 1) * $perPage;
        $items = Database::fetchAll(
            'SELECT t.*, u.nickname, u.avatar, r.created_at AS rec_at, r.reason, f.name AS forum_name,
                    (SELECT COUNT(*) FROM ' . Database::table('recommendations') . ' x WHERE x.thread_id = t.id AND x.kind = -1) AS avoid_count
             FROM ' . Database::table('recommendations') . ' r
             JOIN ' . Database::table('threads') . ' t ON t.id = r.thread_id
             LEFT JOIN ' . Database::table('users') . ' u ON u.id = t.user_id
             LEFT JOIN ' . Database::table('forums') . ' f ON f.id = t.forum_id
             WHERE ' . $where . ' ORDER BY r.id DESC LIMIT ' . $offset . ',' . $perPage,
            $params
        );
        return ['total' => $total, 'items' => $items];
    }
}
