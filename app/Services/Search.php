<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * 搜索：帖子 / 用户 / 标签
 */
class Search
{
    public static function threads(string $keyword, int $page = 1, int $perPage = 20, int $forumId = 0): array
    {
        $where = 't.status = 1 AND (t.title LIKE ? OR t.content_text LIKE ?)';
        $params = ['%' . $keyword . '%', '%' . $keyword . '%'];
        if ($forumId > 0) {
            $where .= ' AND t.forum_id = ?';
            $params[] = $forumId;
        }
        $total = (int)Database::value('SELECT COUNT(*) FROM ' . Database::table('threads') . ' t WHERE ' . $where, $params, 0);
        $offset = ($page - 1) * $perPage;
        $items = Database::fetchAll(
            'SELECT t.*, u.nickname, u.avatar, f.name AS forum_name FROM ' . Database::table('threads') . ' t
             LEFT JOIN ' . Database::table('users') . ' u ON u.id = t.user_id
             LEFT JOIN ' . Database::table('forums') . ' f ON f.id = t.forum_id
             WHERE ' . $where . ' ORDER BY t.last_reply_at DESC LIMIT ' . $offset . ',' . $perPage,
            $params
        );
        return ['total' => $total, 'items' => $items];
    }

    public static function users(string $keyword, int $limit = 20): array
    {
        return Database::fetchAll(
            'SELECT id,username,nickname,avatar,threads,posts,signature FROM ' . Database::table('users')
            . ' WHERE status = 1 AND (username LIKE ? OR nickname LIKE ?) ORDER BY id DESC LIMIT ' . (int)$limit,
            ['%' . $keyword . '%', '%' . $keyword . '%']
        );
    }

    public static function tags(string $keyword, int $limit = 20): array
    {
        return Database::fetchAll(
            'SELECT * FROM ' . Database::table('tags') . ' WHERE status = 1 AND (name LIKE ? OR alias LIKE ?) ORDER BY threads DESC LIMIT ' . (int)$limit,
            ['%' . $keyword . '%', '%' . $keyword . '%']
        );
    }

    public static function log(string $keyword, int $userId, int $results): void
    {
        if ($keyword === '') {
            return;
        }
        Database::insert('search_logs', [
            'keyword' => mb_substr($keyword, 0, 100),
            'user_id' => $userId,
            'results' => $results,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function hotKeywords(int $limit = 10): array
    {
        return Database::fetchAll(
            'SELECT keyword, COUNT(*) AS cnt FROM ' . Database::table('search_logs')
            . ' WHERE created_at > DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY keyword ORDER BY cnt DESC LIMIT ' . (int)$limit
        );
    }
}
