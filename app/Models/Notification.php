<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Notification
{
    /**
     * 发送通知
     */
    public static function send(int $userId, string $type, string $title, string $content = '', string $link = '', int $fromId = 0): int
    {
        if ($userId <= 0 || $userId === $fromId) {
            return 0;
        }
        return Database::insert('notifications', [
            'user_id' => $userId,
            'from_id' => $fromId,
            'type' => $type,
            'title' => $title,
            'content' => $content,
            'link' => $link,
            'is_read' => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function list(int $userId, int $page = 1, int $perPage = 20, ?bool $unread = null): array
    {
        $where = 'user_id = ?';
        $params = [$userId];
        if ($unread === true) {
            $where .= ' AND is_read = 0';
        }
        $total = (int)Database::value('SELECT COUNT(*) FROM ' . Database::table('notifications') . ' WHERE ' . $where, $params, 0);
        $offset = ($page - 1) * $perPage;
        $items = Database::fetchAll(
            'SELECT n.*, u.nickname AS from_name, u.avatar AS from_avatar
             FROM ' . Database::table('notifications') . ' n
             LEFT JOIN ' . Database::table('users') . ' u ON u.id = n.from_id
             WHERE ' . $where . ' ORDER BY n.is_read ASC, n.id DESC LIMIT ' . $offset . ',' . $perPage,
            $params
        );
        return ['total' => $total, 'items' => $items];
    }

    public static function unreadCount(int $userId): int
    {
        if ($userId <= 0) {
            return 0;
        }
        return (int)Database::value(
            'SELECT COUNT(*) FROM ' . Database::table('notifications') . ' WHERE user_id = ? AND is_read = 0',
            [$userId], 0
        );
    }

    public static function markRead(int $userId, int $id = 0): void
    {
        if ($id > 0) {
            Database::update('notifications', ['is_read' => 1], 'id = ? AND user_id = ?', ['id' => $id, 'uid' => $userId]);
        } else {
            Database::update('notifications', ['is_read' => 1], 'user_id = ?', ['uid' => $userId]);
        }
    }

    public static function delete(int $userId, int $id): void
    {
        Database::delete('notifications', 'id = ? AND user_id = ?', ['id' => $id, 'uid' => $userId]);
    }

    /**
     * 全站系统通知
     */
    public static function broadcast(string $title, string $content, string $link = ''): int
    {
        $n = 0;
        $users = Database::fetchAll('SELECT id FROM ' . Database::table('users') . ' WHERE status = 1');
        foreach ($users as $u) {
            self::send((int)$u['id'], 'system', $title, $content, $link);
            $n++;
        }
        return $n;
    }
}
