<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * 圈子（兴趣小组）
 */
class Circle
{
    public static function all(bool $onlyVisible = true): array
    {
        $where = $onlyVisible ? ' WHERE status = 1' : '';
        return Database::fetchAll(
            'SELECT * FROM ' . Database::table('circles') . $where . ' ORDER BY member_count DESC, id DESC'
        );
    }

    public static function find(int $id): ?array
    {
        return Database::fetch('SELECT * FROM ' . Database::table('circles') . ' WHERE id = ?', [$id]);
    }

    public static function findBySlug(string $slug): ?array
    {
        return Database::fetch('SELECT * FROM ' . Database::table('circles') . ' WHERE slug = ?', [$slug]);
    }

    public static function create(array $data): int
    {
        return Database::insert('circles', array_merge(['created_at' => date('Y-m-d H:i:s')], $data));
    }

    public static function update(int $id, array $data): int
    {
        return Database::update('circles', $data, 'id = ?', ['i' => $id]);
    }

    public static function delete(int $id): void
    {
        Database::delete('circle_members', 'circle_id = ?', ['c' => $id]);
        Database::delete('circles', 'id = ?', ['i' => $id]);
    }

    public static function isMember(int $circleId, int $userId): bool
    {
        if ($circleId <= 0 || $userId <= 0) {
            return false;
        }
        return (bool)Database::value(
            'SELECT id FROM ' . Database::table('circle_members') . ' WHERE circle_id = ? AND user_id = ?',
            [$circleId, $userId]
        );
    }

    public static function join(int $circleId, int $userId): bool
    {
        if ($circleId <= 0 || $userId <= 0 || self::isMember($circleId, $userId)) {
            return false;
        }
        $circle = self::find($circleId);
        if (!$circle || (int)$circle['is_open'] !== 1) {
            return false;
        }
        Database::insert('circle_members', [
            'circle_id' => $circleId,
            'user_id' => $userId,
            'role' => 0,
            'joined_at' => date('Y-m-d H:i:s'),
        ]);
        self::syncCount($circleId);
        return true;
    }

    public static function leave(int $circleId, int $userId): bool
    {
        $deleted = Database::delete(
            'circle_members',
            'circle_id = ? AND user_id = ? AND role = 0',
            ['c' => $circleId, 'u' => $userId]
        );
        if ($deleted) {
            self::syncCount($circleId);
            return true;
        }
        return false;
    }

    public static function syncCount(int $circleId): void
    {
        $n = (int)Database::value('SELECT COUNT(*) FROM ' . Database::table('circle_members') . ' WHERE circle_id = ?', [$circleId], 0);
        Database::update('circles', ['member_count' => $n], 'id = ?', ['i' => $circleId]);
    }

    public static function members(int $circleId, int $limit = 20): array
    {
        return Database::fetchAll(
            'SELECT m.*, u.username, u.nickname, u.avatar FROM ' . Database::table('circle_members') . ' m'
            . ' LEFT JOIN ' . Database::table('users') . ' u ON u.id = m.user_id'
            . ' WHERE m.circle_id = ? ORDER BY m.role DESC, m.id ASC LIMIT ' . (int)$limit,
            [$circleId]
        );
    }
}
