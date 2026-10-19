<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * 群聊：群 / 成员 / 群消息
 */
class ChatGroup
{
    /** 成员角色 */
    public const ROLE_MEMBER = 0;
    public const ROLE_ADMIN = 1;
    public const ROLE_OWNER = 2;

    /* ==================== 表结构自检（升级后自动补齐） ==================== */

    /**
     * 安全执行：聊天表缺失时自动尝试建表并重试一次；仍失败则返回默认值，
     * 保证「表没建好」只会让聊天不可用，不会把整站打成 500。
     *
     * @param callable $fn 真正要执行的查询
     * @param mixed $default 失败时的返回值
     */
    private static function attempt(callable $fn, $default)
    {
        try {
            return $fn();
        } catch (\Throwable $e) {
            if (!self::heal($e)) {
                return $default;
            }
            try {
                return $fn();
            } catch (\Throwable $e2) {
                return $default;
            }
        }
    }

    /**
     * 只在「表不存在 / 缺字段」这类错误时补一次结构（每个请求最多一次）
     */
    private static function heal(\Throwable $e): bool
    {
        static $tried = false;
        if ($tried) {
            return false;
        }
        $msg = $e->getMessage();
        $code = method_exists($e, 'getCode') ? (string)$e->getCode() : '';
        if (stripos($msg, "doesn't exist") === false
            && stripos($msg, 'Unknown column') === false
            && $code !== '42S02' && $code !== '42S22') {
            return false;
        }
        $tried = true;
        return self::install();
    }

    /**
     * 聊天三表是否已就绪：不存在则自动尝试创建（每个请求最多检测一次）
     */
    public static function installed(): bool
    {
        static $ok = null;
        if ($ok !== null) {
            return $ok;
        }
        try {
            $exists = (int)Database::value(
                'SELECT COUNT(*) FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN (?, ?, ?)',
                [
                    Database::table('chat_groups'),
                    Database::table('chat_group_members'),
                    Database::table('chat_group_messages'),
                ],
                0
            );
            if ($exists === 3) {
                return $ok = true;
            }
        } catch (\Throwable $e) {
            return $ok = false;
        }
        return $ok = self::install();
    }

    /**
     * 幂等建表 + 补齐 users.lan_discover（与后台「检查并修复表结构」保持一致）
     */
    public static function install(): bool
    {
        try {
            foreach (self::ddl() as $table => $body) {
                Database::query(
                    'CREATE TABLE IF NOT EXISTS ' . Database::table($table) . ' (' . $body . ')'
                    . ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci'
                );
            }
            $has = (int)Database::value(
                'SELECT COUNT(*) FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
                [Database::table('users'), 'lan_discover'], 0
            );
            if ($has === 0) {
                Database::query('ALTER TABLE ' . Database::table('users')
                    . ' ADD COLUMN `lan_discover` tinyint(1) NOT NULL DEFAULT 0 COMMENT \'允许附近的人发现我\'');
            }
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 聊天三表建表语句
     *
     * @return array<string, string>
     */
    private static function ddl(): array
    {
        return [
            'chat_groups' => '`id` int unsigned NOT NULL AUTO_INCREMENT,
                  `name` varchar(60) NOT NULL DEFAULT \'\',
                  `avatar` varchar(255) NOT NULL DEFAULT \'\',
                  `owner_id` int unsigned NOT NULL DEFAULT 0,
                  `notice` varchar(255) NOT NULL DEFAULT \'\',
                  `member_count` int unsigned NOT NULL DEFAULT 1,
                  `is_open` tinyint(1) NOT NULL DEFAULT 1,
                  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                  PRIMARY KEY (`id`), KEY `idx_owner` (`owner_id`), KEY `idx_open` (`is_open`)',
            'chat_group_members' => '`id` int unsigned NOT NULL AUTO_INCREMENT,
                  `group_id` int unsigned NOT NULL DEFAULT 0,
                  `user_id` int unsigned NOT NULL DEFAULT 0,
                  `role` tinyint(1) NOT NULL DEFAULT 0,
                  `last_read_id` int unsigned NOT NULL DEFAULT 0,
                  `joined_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  PRIMARY KEY (`id`), UNIQUE KEY `uk_group_user` (`group_id`,`user_id`), KEY `idx_user` (`user_id`)',
            'chat_group_messages' => '`id` int unsigned NOT NULL AUTO_INCREMENT,
                  `group_id` int unsigned NOT NULL DEFAULT 0,
                  `user_id` int unsigned NOT NULL DEFAULT 0,
                  `content` text,
                  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  PRIMARY KEY (`id`), KEY `idx_group` (`group_id`,`id`)',
        ];
    }

    /* ==================== 群 ==================== */

    public static function find(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        return self::attempt(
            static fn() => Database::fetch('SELECT * FROM ' . Database::table('chat_groups') . ' WHERE id = ? LIMIT 1', [$id]),
            null
        );
    }

    /**
     * 我加入的群（带最后一条消息与未读数）
     */
    public static function myGroups(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }
        return self::attempt(static function () use ($userId) {
            $rows = Database::fetchAll(
                'SELECT g.*, m.role, m.last_read_id,
                    (SELECT COUNT(*) FROM ' . Database::table('chat_group_messages') . ' gm
                      WHERE gm.group_id = g.id AND gm.id > m.last_read_id AND gm.user_id <> ?) AS unread
             FROM ' . Database::table('chat_group_members') . ' m
             INNER JOIN ' . Database::table('chat_groups') . ' g ON g.id = m.group_id
             WHERE m.user_id = ?
             ORDER BY g.updated_at DESC, g.id DESC',
                [$userId, $userId]
            );
            foreach ($rows as &$g) {
                $g['last'] = Database::fetch(
                    'SELECT gm.content, gm.created_at, u.username, u.nickname
                 FROM ' . Database::table('chat_group_messages') . ' gm
                 LEFT JOIN ' . Database::table('users') . ' u ON u.id = gm.user_id
                 WHERE gm.group_id = ? ORDER BY gm.id DESC LIMIT 1',
                    [(int)$g['id']]
                );
            }
            unset($g);
            return $rows;
        }, []);
    }

    /**
     * 建群（创建者自动成为群主）
     */
    public static function create(int $ownerId, string $name, string $notice = '', int $isOpen = 1): int
    {
        $name = trim($name);
        if ($ownerId <= 0 || $name === '') {
            return 0;
        }
        Database::begin();
        try {
            $gid = Database::insert('chat_groups', [
                'name' => mb_substr($name, 0, 60),
                'owner_id' => $ownerId,
                'notice' => mb_substr($notice, 0, 255),
                'member_count' => 1,
                'is_open' => $isOpen ? 1 : 0,
            ]);
            Database::insert('chat_group_members', [
                'group_id' => $gid,
                'user_id' => $ownerId,
                'role' => self::ROLE_OWNER,
                'last_read_id' => 0,
            ]);
            Database::commit();
            return $gid;
        } catch (\Throwable $e) {
            Database::rollback();
            return 0;
        }
    }

    /* ==================== 成员 ==================== */

    public static function member(int $groupId, int $userId): ?array
    {
        return self::attempt(
            static fn() => Database::fetch(
                'SELECT * FROM ' . Database::table('chat_group_members') . ' WHERE group_id = ? AND user_id = ? LIMIT 1',
                [$groupId, $userId]
            ),
            null
        );
    }

    public static function isMember(int $groupId, int $userId): bool
    {
        return self::member($groupId, $userId) !== null;
    }

    public static function members(int $groupId): array
    {
        return self::attempt(
            static fn() => Database::fetchAll(
                'SELECT m.*, u.username, u.nickname, u.avatar, u.group_id AS role_group
             FROM ' . Database::table('chat_group_members') . ' m
             LEFT JOIN ' . Database::table('users') . ' u ON u.id = m.user_id
             WHERE m.group_id = ? ORDER BY m.role DESC, m.id ASC',
                [$groupId]
            ),
            []
        );
    }

    public static function addMember(int $groupId, int $userId, int $role = self::ROLE_MEMBER): bool
    {
        if ($groupId <= 0 || $userId <= 0 || self::isMember($groupId, $userId)) {
            return false;
        }
        try {
            Database::insert('chat_group_members', [
                'group_id' => $groupId,
                'user_id' => $userId,
                'role' => $role,
                'last_read_id' => (int)Database::value(
                    'SELECT IFNULL(MAX(id),0) FROM ' . Database::table('chat_group_messages') . ' WHERE group_id = ?',
                    [$groupId], 0
                ),
            ]);
            self::syncCount($groupId);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function removeMember(int $groupId, int $userId): bool
    {
        $n = Database::delete('chat_group_members', 'group_id = ? AND user_id = ?', [$groupId, $userId]);
        if ($n > 0) {
            self::syncCount($groupId);
        }
        return $n > 0;
    }

    public static function syncCount(int $groupId): void
    {
        self::attempt(static function () use ($groupId) {
            $n = (int)Database::value(
                'SELECT COUNT(*) FROM ' . Database::table('chat_group_members') . ' WHERE group_id = ?',
                [$groupId], 0
            );
            Database::update('chat_groups', ['member_count' => $n], 'id = ?', [$groupId]);
            return true;
        }, false);
    }

    /* ==================== 消息 ==================== */

    public static function messages(int $groupId, int $afterId = 0, int $limit = 50): array
    {
        $limit = max(1, min(200, $limit));
        return self::attempt(static function () use ($groupId, $afterId, $limit) {
            $sql = 'SELECT gm.*, u.username, u.nickname, u.avatar
                FROM ' . Database::table('chat_group_messages') . ' gm
                LEFT JOIN ' . Database::table('users') . ' u ON u.id = gm.user_id
                WHERE gm.group_id = ?';
            $params = [$groupId];
            if ($afterId > 0) {
                $sql .= ' AND gm.id > ?';
                $params[] = $afterId;
            }
            $sql .= ' ORDER BY gm.id DESC LIMIT ' . $limit;
            $rows = Database::fetchAll($sql, $params);
            return array_reverse($rows);
        }, []);
    }

    public static function send(int $groupId, int $userId, string $content): int
    {
        $content = trim($content);
        if ($groupId <= 0 || $userId <= 0 || $content === '') {
            return 0;
        }
        Database::begin();
        try {
            $id = Database::insert('chat_group_messages', [
                'group_id' => $groupId,
                'user_id' => $userId,
                'content' => mb_substr($content, 0, 2000),
            ]);
            Database::update('chat_groups', ['updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$groupId]);
            Database::commit();
            return $id;
        } catch (\Throwable $e) {
            Database::rollback();
            return 0;
        }
    }

    public static function markRead(int $groupId, int $userId): void
    {
        self::attempt(static function () use ($groupId, $userId) {
            $max = (int)Database::value(
                'SELECT IFNULL(MAX(id),0) FROM ' . Database::table('chat_group_messages') . ' WHERE group_id = ?',
                [$groupId], 0
            );
            Database::update(
                'chat_group_members',
                ['last_read_id' => $max],
                'group_id = ? AND user_id = ?',
                [$groupId, $userId]
            );
            return true;
        }, false);
    }

    public static function unreadTotal(int $userId): int
    {
        return (int)self::attempt(
            static fn() => (int)Database::value(
                'SELECT COUNT(*) FROM ' . Database::table('chat_group_messages') . ' gm
             INNER JOIN ' . Database::table('chat_group_members') . ' m
                ON m.group_id = gm.group_id AND m.user_id = ?
             WHERE gm.id > m.last_read_id AND gm.user_id <> ?',
                [$userId, $userId], 0
            ),
            0
        );
    }

    /* ==================== 后台管理 ==================== */

    public static function adminList(int $limit = 50, int $offset = 0): array
    {
        $limit = max(1, min(200, $limit));
        return self::attempt(
            static fn() => Database::fetchAll(
                'SELECT g.*, u.username, u.nickname
             FROM ' . Database::table('chat_groups') . ' g
             LEFT JOIN ' . Database::table('users') . ' u ON u.id = g.owner_id
             ORDER BY g.id DESC LIMIT ' . $limit . ' OFFSET ' . max(0, $offset)
            ),
            []
        );
    }

    public static function deleteGroup(int $groupId): void
    {
        try {
            Database::delete('chat_group_messages', 'group_id = ?', [$groupId]);
            Database::delete('chat_group_members', 'group_id = ?', [$groupId]);
            Database::delete('chat_groups', 'id = ?', [$groupId]);
        } catch (\Throwable $e) {
            // 表缺失时忽略，避免后台解散群把整站打成 500
        }
    }
}
