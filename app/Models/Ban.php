<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * 小黑屋：封禁 / 禁言记录
 */
class Ban
{
    /**
     * 记录一条封禁 / 禁言
     */
    public static function record(int $userId, string $username, string $action, string $note, ?string $expireAt, array $operator): int
    {
        $action = $action === 'mute' ? 'mute' : 'ban';
        $expireAt = $expireAt !== null && $expireAt !== '' ? $expireAt : null;
        return Database::insert('user_bans', [
            'user_id' => $userId,
            'username' => $username,
            'action' => $action,
            'note' => $note,
            'operator_id' => (int)($operator['id'] ?? 0),
            'operator_name' => (string)($operator['name'] ?? ''),
            'expire_at' => $expireAt,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * 生效中的封禁（未过期）
     */
    public static function active(): array
    {
        return Database::fetchAll(
            'SELECT * FROM ' . Database::table('user_bans')
            . ' WHERE expire_at IS NULL OR expire_at > ? ORDER BY id DESC',
            [date('Y-m-d H:i:s')]
        );
    }

    /**
     * 解封：把该用户仍生效的封禁标记为已过期
     */
    public static function unban(int $userId): void
    {
        Database::query(
            'UPDATE ' . Database::table('user_bans') . ' SET expire_at = ? WHERE user_id = ? AND (expire_at IS NULL OR expire_at > ?)',
            [date('Y-m-d H:i:s'), $userId, date('Y-m-d H:i:s')]
        );
    }

    public static function actionLabel(string $action): string
    {
        return $action === 'mute' ? '禁言' : '封禁';
    }
}
