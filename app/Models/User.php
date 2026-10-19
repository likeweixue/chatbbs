<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class User
{
    public static function find(int $id): ?array
    {
        return Database::fetch('SELECT * FROM ' . Database::table('users') . ' WHERE id = ?', [$id]);
    }

    public static function findByUsername(string $username): ?array
    {
        return Database::fetch('SELECT * FROM ' . Database::table('users') . ' WHERE username = ?', [$username]);
    }

    public static function findByEmail(string $email): ?array
    {
        return Database::fetch('SELECT * FROM ' . Database::table('users') . ' WHERE email = ?', [$email]);
    }

    public static function findByAccount(string $account): ?array
    {
        return Database::fetch(
            'SELECT * FROM ' . Database::table('users') . ' WHERE username = ? OR email = ? LIMIT 1',
            [$account, $account]
        );
    }

    public static function create(array $data): int
    {
        return Database::insert('users', array_merge([
            'group_id' => 4,
            'level_id' => 1,
            'status' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'last_active_at' => date('Y-m-d H:i:s'),
        ], $data));
    }

    public static function update(int $id, array $data): int
    {
        return Database::update('users', $data, 'id = ?', ['id' => $id]);
    }

    /**
     * 用户展示信息（含组、等级、认证）
     */
    public static function withMeta(?array $user): ?array
    {
        if (!$user) {
            return null;
        }
        static $groups = [], $levels = [], $certs = [];
        $gid = (int)$user['group_id'];
        if (!isset($groups[$gid])) {
            $groups[$gid] = Database::fetch('SELECT * FROM ' . Database::table('user_groups') . ' WHERE id = ?', [$gid]) ?: [];
        }
        $lid = (int)$user['level_id'];
        if (!isset($levels[$lid])) {
            $levels[$lid] = Database::fetch('SELECT * FROM ' . Database::table('user_levels') . ' WHERE id = ?', [$lid]) ?: [];
        }
        $cid = (int)($user['cert_id'] ?? 0);
        if ($cid > 0 && !isset($certs[$cid])) {
            $certs[$cid] = Database::fetch('SELECT * FROM ' . Database::table('user_certifications') . ' WHERE id = ?', [$cid]) ?: [];
        }
        $user['group'] = $groups[$gid];
        $user['level'] = $levels[$lid];
        $user['cert'] = $cid > 0 ? ($certs[$cid] ?? null) : null;
        return $user;
    }

    public static function stats(int $id): array
    {
        $u = self::find($id);
        if (!$u) {
            return [];
        }
        $followers = (int)Database::value('SELECT COUNT(*) FROM ' . Database::table('follows') . ' WHERE follow_id = ?', [$id], 0);
        $following = (int)Database::value('SELECT COUNT(*) FROM ' . Database::table('follows') . ' WHERE user_id = ?', [$id], 0);
        $friends = (int)Database::value('SELECT COUNT(*) FROM ' . Database::table('friends') . ' WHERE user_id = ? AND status = 1', [$id], 0);
        return [
            'threads' => (int)$u['threads'],
            'posts' => (int)$u['posts'],
            'likes_received' => (int)$u['likes_received'],
            'followers' => $followers,
            'following' => $following,
            'friends' => $friends,
            'money' => (float)$u['money'],
            'exp' => (int)$u['exp'],
            'says' => (int)Database::value('SELECT COUNT(*) FROM ' . Database::table('says') . ' WHERE user_id = ? AND status = 1', [$id], 0),
        ];
    }

    public static function list(int $page = 1, int $perPage = 20, string $keyword = '', string $group = ''): array
    {
        $where = '1=1';
        $params = [];
        if ($keyword !== '') {
            $where .= ' AND (username LIKE ? OR nickname LIKE ? OR email LIKE ?)';
            $params[] = '%' . $keyword . '%';
            $params[] = '%' . $keyword . '%';
            $params[] = '%' . $keyword . '%';
        }
        if ($group !== '') {
            $where .= ' AND group_id = ?';
            $params[] = (int)$group;
        }
        $total = (int)Database::value('SELECT COUNT(*) FROM ' . Database::table('users') . ' WHERE ' . $where, $params, 0);
        $offset = ($page - 1) * $perPage;
        $sql = 'SELECT * FROM ' . Database::table('users') . ' WHERE ' . $where
            . ' ORDER BY id DESC LIMIT ' . (int)$offset . ',' . (int)$perPage;
        return ['total' => $total, 'items' => Database::fetchAll($sql, $params)];
    }

    public static function newest(int $limit = 8): array
    {
        return Database::fetchAll(
            'SELECT id,username,nickname,avatar,created_at FROM ' . Database::table('users')
            . ' WHERE status = 1 ORDER BY id DESC LIMIT ' . (int)$limit
        );
    }

    public static function active(int $limit = 8): array
    {
        return Database::fetchAll(
            'SELECT id,username,nickname,avatar,threads,posts FROM ' . Database::table('users')
            . ' WHERE status = 1 ORDER BY (threads + posts) DESC, id DESC LIMIT ' . (int)$limit
        );
    }

    public static function touch(int $id): void
    {
        Database::query('UPDATE ' . Database::table('users') . ' SET last_active_at = ? WHERE id = ?', [date('Y-m-d H:i:s'), $id]);
    }

    public static function isFollowing(int $userId, int $targetId): bool
    {
        return (bool)Database::value(
            'SELECT COUNT(*) FROM ' . Database::table('follows') . ' WHERE user_id = ? AND follow_id = ?',
            [$userId, $targetId], 0
        );
    }

    public static function isFriend(int $userId, int $targetId): bool
    {
        return (bool)Database::value(
            'SELECT COUNT(*) FROM ' . Database::table('friends') . ' WHERE user_id = ? AND friend_id = ? AND status = 1',
            [$userId, $targetId], 0
        );
    }

    public static function isBlocked(int $userId, int $targetId): bool
    {
        return (bool)Database::value(
            'SELECT COUNT(*) FROM ' . Database::table('blacklists') . ' WHERE user_id = ? AND block_id = ?',
            [$userId, $targetId], 0
        );
    }

    public static function groups(): array
    {
        return Database::fetchAll('SELECT * FROM ' . Database::table('user_groups') . ' ORDER BY sort_order, id');
    }

    public static function levels(): array
    {
        return Database::fetchAll('SELECT * FROM ' . Database::table('user_levels') . ' ORDER BY sort_order, id');
    }

    public static function certs(): array
    {
        return Database::fetchAll('SELECT * FROM ' . Database::table('user_certifications') . ' ORDER BY sort_order, id');
    }
}
