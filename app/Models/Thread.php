<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Thread
{
    public static function find(int $id): ?array
    {
        return Database::fetch('SELECT * FROM ' . Database::table('threads') . ' WHERE id = ?', [$id]);
    }

    public static function detail(int $id): ?array
    {
        $t = self::find($id);
        if (!$t) {
            return null;
        }
        $t['user'] = User::withMeta(User::find((int)$t['user_id']));
        $t['forum'] = Forum::find((int)$t['forum_id']);
        $t['tags'] = self::tags($id);
        $t['mark'] = (int)$t['mark_id'] > 0
            ? Database::fetch('SELECT * FROM ' . Database::table('thread_marks') . ' WHERE id = ?', [$t['mark_id']])
            : null;
        return $t;
    }

    public static function tags(int $threadId): array
    {
        return Database::fetchAll(
            'SELECT t.* FROM ' . Database::table('thread_tags') . ' tt
             JOIN ' . Database::table('tags') . ' t ON t.id = tt.tag_id
             WHERE tt.thread_id = ? ORDER BY t.hot DESC',
            [$threadId]
        );
    }

    /**
     * 帖子列表
     */
    public static function list(array $opt = []): array
    {
        $forumId = (int)($opt['forum_id'] ?? 0);
        $userId = (int)($opt['user_id'] ?? 0);
        $tagId = (int)($opt['tag_id'] ?? 0);
        $keyword = (string)($opt['keyword'] ?? '');
        $status = $opt['status'] ?? 1;
        $page = max(1, (int)($opt['page'] ?? 1));
        $perPage = (int)($opt['per_page'] ?? 20);
        $order = (string)($opt['order'] ?? 'default');
        $essence = (int)($opt['essence'] ?? 0);
        $recommend = (int)($opt['recommend'] ?? 0);
        $recommendKind = (int)($opt['recommend_kind'] ?? 0);
        $markId = (int)($opt['mark_id'] ?? 0);
        $digest = (int)($opt['digest'] ?? 0);
        $announce = (int)($opt['announce'] ?? 0);

        $where = 't.status = ?';
        $params = [$status];

        if ($forumId > 0) {
            $where .= ' AND t.forum_id = ?';
            $params[] = $forumId;
        }
        if ($userId > 0) {
            $where .= ' AND t.user_id = ?';
            $params[] = $userId;
        }
        if ($tagId > 0) {
            $where .= ' AND EXISTS (SELECT 1 FROM ' . Database::table('thread_tags') . ' tt WHERE tt.thread_id = t.id AND tt.tag_id = ?)';
            $params[] = $tagId;
        }
        if ($keyword !== '') {
            $where .= ' AND (t.title LIKE ? OR t.content_text LIKE ?)';
            $params[] = '%' . $keyword . '%';
            $params[] = '%' . $keyword . '%';
        }
        if ($essence) {
            $where .= ' AND t.is_essence = 1';
        }
        if ($announce) {
            $where .= ' AND t.forum_id IN (SELECT af.id FROM ' . Database::table('forums') . " af WHERE af.slug = 'notice' OR af.name LIKE '%公告%')";
        }
        if ($recommendKind === 1 || $recommendKind === -1) {
            $where .= ' AND EXISTS (SELECT 1 FROM ' . Database::table('recommendations') . ' rec WHERE rec.thread_id = t.id AND rec.kind = ?)';
            $params[] = $recommendKind;
        } elseif ($recommend) {
            $where .= ' AND t.recommends > 0';
        }
        if ($markId > 0) {
            $where .= ' AND t.mark_id = ?';
            $params[] = $markId;
        }
        if ($digest) {
            $where .= ' AND t.is_digest = 1';
        }

        $orderBy = match ($order) {
            'new' => 't.created_at DESC',
            'hot' => 't.views DESC, t.replies DESC',
            'replies' => 't.replies DESC',
            'essence' => 't.is_top DESC, t.topped_at DESC, t.last_reply_at DESC, t.id DESC',
            default => 't.is_top DESC, t.topped_at DESC, t.last_reply_at DESC, t.id DESC',
        };

        $total = (int)Database::value(
            'SELECT COUNT(*) FROM ' . Database::table('threads') . ' t WHERE ' . $where,
            $params,
            0
        );
        $offset = ($page - 1) * $perPage;
        $sql = 'SELECT t.*, u.nickname, u.avatar, f.name AS forum_name
                FROM ' . Database::table('threads') . ' t
                LEFT JOIN ' . Database::table('users') . ' u ON u.id = t.user_id
                LEFT JOIN ' . Database::table('forums') . ' f ON f.id = t.forum_id
                WHERE ' . $where . ' ORDER BY ' . $orderBy . ' LIMIT ' . $offset . ',' . $perPage;
        $items = Database::fetchAll($sql, $params);
        foreach ($items as &$it) {
            $it['tags'] = self::tags((int)$it['id']);
        }
        return ['total' => $total, 'items' => $items, 'page' => $page, 'per_page' => $perPage];
    }

    public static function create(array $data): int
    {
        $now = date('Y-m-d H:i:s');
        $data = array_merge([
            'status' => 1,
            'created_at' => $now,
            'updated_at' => $now,
            'last_reply_at' => $now,
        ], $data);
        $id = Database::insert('threads', $data);
        Database::insert('posts', [
            'thread_id' => $id,
            'user_id' => $data['user_id'],
            'username' => $data['username'],
            'content' => $data['content'],
            'content_text' => $data['content_text'] ?? '',
            'floor' => 1,
            'is_first' => 1,
            'status' => $data['status'],
            'anonymous' => $data['anonymous'] ?? 0,
            'ip' => client_ip(),
            'created_at' => $now,
        ]);
        Database::query('UPDATE ' . Database::table('users') . ' SET threads = threads + 1 WHERE id = ?', [$data['user_id']]);
        Forum::incrementThreads((int)$data['forum_id']);
        Forum::syncStats((int)$data['forum_id']);
        return $id;
    }

    public static function update(int $id, array $data): int
    {
        return Database::update('threads', $data, 'id = ?', ['id' => $id]);
    }

    public static function delete(int $id): int
    {
        Database::delete('posts', 'thread_id = ?', ['tid' => $id]);
        Database::delete('thread_tags', 'thread_id = ?', ['tid' => $id]);
        Database::delete('likes', 'target_type = ? AND target_id = ?', ['t' => 'thread', 'id' => $id]);
        Database::delete('recommendations', 'thread_id = ?', ['tid' => $id]);
        $t = self::find($id);
        if ($t) {
            Database::query('UPDATE ' . Database::table('users') . ' SET threads = GREATEST(threads-1,0) WHERE id = ?', [$t['user_id']]);
        }
        return Database::delete('threads', 'id = ?', ['id' => $id]);
    }

    public static function softDelete(int $id): int
    {
        return self::update($id, ['status' => 2]);
    }

    public static function view(int $id): void
    {
        Database::query('UPDATE ' . Database::table('threads') . ' SET views = views + 1 WHERE id = ?', [$id]);
    }

    public static function syncReplies(int $threadId): void
    {
        $count = (int)Database::value(
            'SELECT COUNT(*) FROM ' . Database::table('posts') . ' WHERE thread_id = ? AND status = 1',
            [$threadId], 0
        );
        $last = Database::fetch(
            'SELECT p.user_id, p.username, p.created_at FROM ' . Database::table('posts') . ' p
             WHERE p.thread_id = ? AND p.status = 1 ORDER BY p.id DESC LIMIT 1',
            [$threadId]
        );
        Database::update('threads', [
            'replies' => max(0, $count - 1),
            'last_reply_at' => $last['created_at'] ?? date('Y-m-d H:i:s'),
            'last_reply_user_id' => $last['user_id'] ?? 0,
            'last_reply_user' => $last['username'] ?? '',
        ], 'id = ?', ['id' => $threadId]);
    }

    public static function setTags(int $threadId, array $tagIds): void
    {
        Database::delete('thread_tags', 'thread_id = ?', ['tid' => $threadId]);
        foreach ($tagIds as $tid) {
            $tid = (int)$tid;
            if ($tid <= 0) {
                continue;
            }
            Database::insert('thread_tags', ['thread_id' => $threadId, 'tag_id' => $tid]);
            Database::query('UPDATE ' . Database::table('tags') . ' SET threads = threads + 1, hot = hot + 1 WHERE id = ?', [$tid]);
        }
    }

    public static function setTop(int $id, int $top, int $scope = 0): void
    {
        self::update($id, [
            'is_top' => $top,
            'top_scope' => $scope,
            'topped_at' => $top ? date('Y-m-d H:i:s') : null,
        ]);
    }

    public static function marks(): array
    {
        return Database::fetchAll('SELECT * FROM ' . Database::table('thread_marks') . ' ORDER BY sort_order, id');
    }

    public static function move(int $id, int $forumId): void
    {
        $old = self::find($id);
        self::update($id, ['forum_id' => $forumId]);
        if ($old) {
            Forum::syncStats((int)$old['forum_id']);
        }
        Forum::syncStats($forumId);
    }

    public static function latest(int $limit = 10, int $forumId = 0): array
    {
        $where = 't.status = 1';
        $params = [];
        if ($forumId > 0) {
            $where .= ' AND t.forum_id = ?';
            $params[] = $forumId;
        }
        return Database::fetchAll(
            'SELECT t.id,t.title,t.created_at,t.views,t.replies,u.nickname,u.username
             FROM ' . Database::table('threads') . ' t
             LEFT JOIN ' . Database::table('users') . ' u ON u.id = t.user_id
             WHERE ' . $where . ' ORDER BY t.created_at DESC LIMIT ' . (int)$limit,
            $params
        );
    }

    public static function hot(int $limit = 10): array
    {
        return Database::fetchAll(
            'SELECT t.id,t.title,t.views,t.replies,t.likes FROM ' . Database::table('threads') . ' t
             WHERE t.status = 1 ORDER BY t.views DESC, t.replies DESC LIMIT ' . (int)$limit
        );
    }
}
