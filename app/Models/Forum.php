<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Forum
{
    /**
     * 分区 + 下属板块
     */
    /**
     * 论坛树（支持无限层级：分区下可有板块，板块下也可再建分区或板块）
     */
    public static function tree(bool $onlyVisible = true): array
    {
        $where = $onlyVisible ? 'WHERE visible = 1' : '';
        $all = Database::fetchAll('SELECT * FROM ' . Database::table('forums') . ' ' . $where . ' ORDER BY sort_order, id');
        $map = [];
        $childrenOf = [];
        foreach ($all as $f) {
            $id = (int)$f['id'];
            $f['children'] = [];
            $map[$id] = $f;
            $childrenOf[(int)$f['parent_id']][] = $id;
        }
        $build = function (int $pid) use (&$build, &$childrenOf, &$map): array {
            $out = [];
            foreach ($childrenOf[$pid] ?? [] as $id) {
                if (!isset($map[$id])) {
                    continue;
                }
                $node = $map[$id];
                $node['children'] = $build($id);
                $out[] = $node;
            }
            return $out;
        };
        return $build(0);
    }

    /**
     * 带层级缩进的下拉选项（后台选择上级时使用）
     *
     * @return array [id => '　└ 名称']
     */
    public static function options(bool $onlyVisible = false): array
    {
        $opts = [0 => '（作为顶级）'];
        $walk = function (array $nodes, int $level) use (&$walk, &$opts): void {
            foreach ($nodes as $n) {
                $prefix = $level > 0 ? str_repeat('　', $level) . '└ ' : '';
                $opts[(int)$n['id']] = $prefix . (string)$n['name'];
                if (!empty($n['children'])) {
                    $walk($n['children'], $level + 1);
                }
            }
        };
        $walk(self::tree($onlyVisible), 0);
        return $opts;
    }

    /**
     * 判断某论坛是否为 $forumId 的后代（防止把父级移到自己的子级下）
     */
    public static function isDescendant(int $forumId, int $ancestorId): bool
    {
        if ($forumId <= 0 || $ancestorId <= 0) {
            return false;
        }
        $guard = 0;
        $pid = (int)(Database::value(
            'SELECT parent_id FROM ' . Database::table('forums') . ' WHERE id = ?',
            [$forumId], 0
        ) ?? 0);
        while ($pid > 0 && $guard++ < 50) {
            if ($pid === $ancestorId) {
                return true;
            }
            $pid = (int)(Database::value(
                'SELECT parent_id FROM ' . Database::table('forums') . ' WHERE id = ?',
                [$pid], 0
            ) ?? 0);
        }
        return false;
    }

    public static function find(int $id): ?array
    {
        return Database::fetch('SELECT * FROM ' . Database::table('forums') . ' WHERE id = ?', [$id]);
    }

    public static function findBySlug(string $slug): ?array
    {
        return Database::fetch('SELECT * FROM ' . Database::table('forums') . ' WHERE slug = ?', [$slug]);
    }

    public static function all(): array
    {
        return Database::fetchAll('SELECT * FROM ' . Database::table('forums') . ' ORDER BY sort_order, id');
    }

    public static function boards(): array
    {
        return Database::fetchAll('SELECT * FROM ' . Database::table('forums') . ' WHERE type = 2 ORDER BY sort_order, id');
    }

    public static function create(array $data): int
    {
        return Database::insert('forums', array_merge(['created_at' => date('Y-m-d H:i:s')], $data));
    }

    public static function update(int $id, array $data): int
    {
        return Database::update('forums', $data, 'id = ?', ['id' => $id]);
    }

    public static function delete(int $id): int
    {
        Database::update('forums', ['parent_id' => 0], 'parent_id = ?', ['pid' => $id]);
        return Database::delete('forums', 'id = ?', ['id' => $id]);
    }

    public static function canView(?array $forum, ?array $user): bool
    {
        if (!$forum || !(int)$forum['visible']) {
            return false;
        }
        if ((int)$forum['need_login'] && !$user) {
            return false;
        }
        $groups = trim((string)$forum['view_groups']);
        if ($groups !== '') {
            $allow = array_filter(array_map('intval', explode(',', $groups)));
            if ($allow && (!$user || !in_array((int)$user['group_id'], $allow, true))) {
                return false;
            }
        }
        return true;
    }

    public static function canPost(?array $forum, ?array $user): bool
    {
        if (!$forum || !(int)$forum['allow_post']) {
            return false;
        }
        if (!$user) {
            return false;
        }
        if ((int)$user['status'] !== 1) {
            return false;
        }
        if (in_array((int)$user['group_id'], [6], true)) {
            return false;
        }
        $groups = trim((string)$forum['post_groups']);
        if ($groups !== '') {
            $allow = array_filter(array_map('intval', explode(',', $groups)));
            if ($allow && !in_array((int)$user['group_id'], $allow, true)) {
                return false;
            }
        }
        if ((int)$forum['need_level'] > 0 && (int)$user['level_id'] < (int)$forum['need_level']) {
            return false;
        }
        return true;
    }

    public static function isModerator(array $forum, ?array $user): bool
    {
        if (!$user) {
            return false;
        }
        if (in_array((int)$user['group_id'], [1, 2], true)) {
            return true;
        }
        $mods = array_filter(array_map('trim', preg_split('/[,\s]+/', (string)$forum['moderators'])));
        foreach ($mods as $m) {
            if (is_numeric($m) && (int)$m === (int)$user['id']) {
                return true;
            }
            if (strtolower($m) === strtolower((string)$user['username'])) {
                return true;
            }
        }
        return false;
    }

    public static function incrementThreads(int $forumId): void
    {
        Database::query(
            'UPDATE ' . Database::table('forums') . ' SET threads = threads + 1, today_threads = today_threads + 1 WHERE id = ?',
            [$forumId]
        );
    }

    public static function syncStats(int $forumId): void
    {
        $threads = (int)Database::value('SELECT COUNT(*) FROM ' . Database::table('threads') . ' WHERE forum_id = ? AND status = 1', [$forumId], 0);
        $posts = (int)Database::value('SELECT COUNT(*) FROM ' . Database::table('posts') . ' WHERE is_first = 0 AND thread_id IN (SELECT id FROM ' . Database::table('threads') . ' WHERE forum_id = ? AND status = 1)', [$forumId], 0);
        $last = Database::fetch(
            'SELECT t.id,t.title,t.last_reply_user,t.last_reply_at FROM ' . Database::table('threads') . ' t WHERE t.forum_id = ? AND t.status = 1 ORDER BY t.last_reply_at DESC LIMIT 1',
            [$forumId]
        );
        Database::update('forums', [
            'threads' => $threads,
            'posts' => $posts,
            'last_thread_id' => $last['id'] ?? 0,
            'last_thread_title' => $last['title'] ?? '',
            'last_post_user' => $last['last_reply_user'] ?? '',
            'last_post_at' => $last['last_reply_at'] ?? null,
        ], 'id = ?', ['id' => $forumId]);
    }
}
