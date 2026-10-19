<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Post
{
    public static function find(int $id): ?array
    {
        return Database::fetch('SELECT * FROM ' . Database::table('posts') . ' WHERE id = ?', [$id]);
    }

    public static function listByThread(int $threadId, int $page = 1, int $perPage = 15): array
    {
        $total = (int)Database::value(
            'SELECT COUNT(*) FROM ' . Database::table('posts') . ' WHERE thread_id = ? AND status = 1',
            [$threadId], 0
        );
        $offset = ($page - 1) * $perPage;
        $items = Database::fetchAll(
            'SELECT p.*, u.nickname, u.avatar, u.signature, u.level_id, u.cert_id, u.group_id, u.threads AS user_threads, u.posts AS user_posts
             FROM ' . Database::table('posts') . ' p
             LEFT JOIN ' . Database::table('users') . ' u ON u.id = p.user_id
             WHERE p.thread_id = ? AND p.status = 1
             ORDER BY p.floor ASC, p.id ASC LIMIT ' . $offset . ',' . $perPage,
            [$threadId]
        );
        return ['total' => $total, 'items' => $items, 'page' => $page, 'per_page' => $perPage];
    }

    public static function create(array $data): int
    {
        $threadId = (int)$data['thread_id'];
        $floor = (int)Database::value(
            'SELECT MAX(floor) FROM ' . Database::table('posts') . ' WHERE thread_id = ?',
            [$threadId], 0
        ) + 1;
        $data['floor'] = $floor;
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['ip'] = client_ip();
        $data['is_first'] = 0;
        $id = Database::insert('posts', $data);
        Database::query('UPDATE ' . Database::table('users') . ' SET posts = posts + 1 WHERE id = ?', [$data['user_id']]);
        Thread::syncReplies($threadId);
        $t = Thread::find($threadId);
        if ($t) {
            Forum::syncStats((int)$t['forum_id']);
        }
        return $id;
    }

    public static function update(int $id, array $data): int
    {
        return Database::update('posts', $data, 'id = ?', ['id' => $id]);
    }

    public static function delete(int $id): int
    {
        $p = self::find($id);
        if (!$p) {
            return 0;
        }
        $n = Database::delete('posts', 'id = ?', ['id' => $id]);
        Database::query('UPDATE ' . Database::table('users') . ' SET posts = GREATEST(posts-1,0) WHERE id = ?', [$p['user_id']]);
        Thread::syncReplies((int)$p['thread_id']);
        return $n;
    }

    public static function softDelete(int $id): int
    {
        $p = self::find($id);
        $n = Database::update('posts', ['status' => 0], 'id = ?', ['id' => $id]);
        if ($p) {
            Thread::syncReplies((int)$p['thread_id']);
        }
        return $n;
    }

    public static function listByUser(int $userId, int $limit = 10): array
    {
        return Database::fetchAll(
            'SELECT p.id,p.thread_id,p.content_text,p.created_at,t.title
             FROM ' . Database::table('posts') . ' p
             LEFT JOIN ' . Database::table('threads') . ' t ON t.id = p.thread_id
             WHERE p.user_id = ? AND p.status = 1 AND t.status = 1
             ORDER BY p.id DESC LIMIT ' . (int)$limit,
            [$userId]
        );
    }
}
