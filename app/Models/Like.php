<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Like
{
    public static function toggle(int $userId, string $type, int $targetId): array
    {
        $exists = Database::value(
            'SELECT id FROM ' . Database::table('likes') . ' WHERE user_id = ? AND target_type = ? AND target_id = ?',
            [$userId, $type, $targetId]
        );
        if ($exists) {
            Database::delete('likes', 'id = ?', ['id' => $exists]);
            self::syncCount($type, $targetId, -1);
            if ($type === 'thread') {
                $t = Thread::find($targetId);
                if ($t && (int)$t['user_id'] > 0) {
                    Database::query('UPDATE ' . Database::table('users') . ' SET likes_received = GREATEST(likes_received-1,0) WHERE id = ?', [$t['user_id']]);
                }
            }
            return ['liked' => false, 'msg' => '已取消点赞'];
        }
        Database::insert('likes', [
            'user_id' => $userId, 'target_type' => $type, 'target_id' => $targetId,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        self::syncCount($type, $targetId, 1);
        if ($type === 'thread') {
            $t = Thread::find($targetId);
            if ($t && (int)$t['user_id'] > 0) {
                Database::query('UPDATE ' . Database::table('users') . ' SET likes_received = likes_received + 1 WHERE id = ?', [$t['user_id']]);
            }
        }
        Database::query('UPDATE ' . Database::table('users') . ' SET likes_given = likes_given + 1 WHERE id = ?', [$userId]);
        return ['liked' => true, 'msg' => '点赞成功'];
    }

    public static function syncCount(string $type, int $targetId, int $delta): void
    {
        $table = match ($type) {
            'thread' => 'threads',
            'post' => 'posts',
            'say' => 'says',
            default => null,
        };
        if (!$table) {
            return;
        }
        Database::query(
            'UPDATE ' . Database::table($table) . ' SET likes = GREATEST(likes + ?, 0) WHERE id = ?',
            [$delta, $targetId]
        );
    }

    public static function has(int $userId, string $type, int $targetId): bool
    {
        if ($userId <= 0) {
            return false;
        }
        return (bool)Database::value(
            'SELECT id FROM ' . Database::table('likes') . ' WHERE user_id = ? AND target_type = ? AND target_id = ?',
            [$userId, $type, $targetId]
        );
    }

    public static function likedIds(int $userId, string $type, array $ids): array
    {
        if ($userId <= 0 || !$ids) {
            return [];
        }
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $params = array_merge([$userId, $type], $ids);
        $rows = Database::fetchAll(
            'SELECT target_id FROM ' . Database::table('likes') . " WHERE user_id = ? AND target_type = ? AND target_id IN ($ph)",
            $params
        );
        return array_map('intval', array_column($rows, 'target_id'));
    }
}
