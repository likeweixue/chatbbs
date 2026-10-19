<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Say
{
    public static function list(int $page = 1, int $perPage = 15, int $userId = 0): array
    {
        $where = 's.status = 1';
        $params = [];
        if ($userId > 0) {
            $where .= ' AND s.user_id = ?';
            $params[] = $userId;
        }
        $total = (int)Database::value('SELECT COUNT(*) FROM ' . Database::table('says') . ' s WHERE ' . $where, $params, 0);
        $offset = ($page - 1) * $perPage;
        $items = Database::fetchAll(
            'SELECT s.*, u.nickname, u.username, u.avatar FROM ' . Database::table('says') . ' s
             LEFT JOIN ' . Database::table('users') . ' u ON u.id = s.user_id
             WHERE ' . $where . ' ORDER BY s.id DESC LIMIT ' . $offset . ',' . $perPage,
            $params
        );
        return ['total' => $total, 'items' => $items];
    }

    public static function find(int $id): ?array
    {
        return Database::fetch('SELECT * FROM ' . Database::table('says') . ' WHERE id = ?', [$id]);
    }

    public static function create(int $userId, string $content, array $images = []): int
    {
        return Database::insert('says', [
            'user_id' => $userId,
            'content' => $content,
            'images' => json_encode($images, JSON_UNESCAPED_UNICODE),
            'status' => (int)\App\Core\Settings::get('say_audit', 0) ? 0 : 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function delete(int $id, int $userId = 0): int
    {
        $where = 'id = ?';
        $params = ['id' => $id];
        if ($userId > 0) {
            $where .= ' AND user_id = ?';
            $params['uid'] = $userId;
        }
        Database::delete('say_comments', 'say_id = ?', ['sid' => $id]);
        Database::delete('likes', "target_type = 'say' AND target_id = ?", ['id' => $id]);
        return Database::delete('says', $where, $params);
    }

    public static function comments(int $sayId): array
    {
        return Database::fetchAll(
            'SELECT c.*, u.avatar FROM ' . Database::table('say_comments') . ' c
             LEFT JOIN ' . Database::table('users') . ' u ON u.id = c.user_id
             WHERE c.say_id = ? ORDER BY c.id ASC',
            [$sayId]
        );
    }

    public static function addComment(int $sayId, int $userId, string $username, string $content, int $replyTo = 0): int
    {
        $id = Database::insert('say_comments', [
            'say_id' => $sayId,
            'user_id' => $userId,
            'username' => $username,
            'content' => $content,
            'reply_to' => $replyTo,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        Database::query('UPDATE ' . Database::table('says') . ' SET comments = comments + 1 WHERE id = ?', [$sayId]);
        return $id;
    }

    public static function deleteComment(int $id): int
    {
        $c = Database::fetch('SELECT * FROM ' . Database::table('say_comments') . ' WHERE id = ?', [$id]);
        $n = Database::delete('say_comments', 'id = ?', ['id' => $id]);
        if ($c) {
            Database::query('UPDATE ' . Database::table('says') . ' SET comments = GREATEST(comments-1,0) WHERE id = ?', [$c['say_id']]);
        }
        return $n;
    }
}
