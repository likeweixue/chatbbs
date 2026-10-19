<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * 私信 / 好友 / 关注 / 黑名单
 */
class Message
{
    public static function send(int $fromId, int $toId, string $content): int
    {
        if ($toId <= 0 || $content === '') {
            return 0;
        }
        if (self::isBlocked($toId, $fromId)) {
            return 0;
        }
        $id = Database::insert('private_messages', [
            'from_id' => $fromId, 'to_id' => $toId, 'content' => $content,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        Notification::send($toId, 'pm', '收到一条消息', mb_substr(strip_tags($content), 0, 60), url('pm?user=' . $fromId), $fromId);
        return $id;
    }

    /**
     * 会话列表
     */
    public static function conversations(int $userId): array
    {
        $rows = Database::fetchAll(
            'SELECT * FROM ' . Database::table('private_messages')
            . ' WHERE (from_id = ? AND from_deleted = 0) OR (to_id = ? AND to_deleted = 0)
               ORDER BY id DESC LIMIT 500',
            [$userId, $userId]
        );
        $list = [];
        foreach ($rows as $r) {
            $other = (int)$r['from_id'] === $userId ? (int)$r['to_id'] : (int)$r['from_id'];
            if (!isset($list[$other])) {
                $list[$other] = [
                    'user_id' => $other,
                    'user' => User::find($other),
                    'last' => $r,
                    'unread' => 0,
                ];
            }
            if ((int)$r['to_id'] === $userId && !(int)$r['is_read']) {
                $list[$other]['unread']++;
            }
        }
        return array_values($list);
    }

    public static function thread(int $userId, int $otherId, int $limit = 100): array
    {
        $rows = Database::fetchAll(
            'SELECT * FROM ' . Database::table('private_messages')
            . ' WHERE (from_id = ? AND to_id = ? AND from_deleted = 0)
                  OR (from_id = ? AND to_id = ? AND to_deleted = 0)
               ORDER BY id DESC LIMIT ' . (int)$limit,
            [$userId, $otherId, $otherId, $userId]
        );
        Database::update('private_messages', ['is_read' => 1], 'from_id = ? AND to_id = ?', ['f' => $otherId, 't' => $userId]);
        return array_reverse($rows);
    }

    public static function unreadTotal(int $userId): int
    {
        return (int)Database::value(
            'SELECT COUNT(*) FROM ' . Database::table('private_messages') . ' WHERE to_id = ? AND is_read = 0 AND to_deleted = 0',
            [$userId], 0
        );
    }

    public static function deleteConversation(int $userId, int $otherId): void
    {
        Database::update('private_messages', ['from_deleted' => 1], 'from_id = ? AND to_id = ?', ['f' => $userId, 't' => $otherId]);
        Database::update('private_messages', ['to_deleted' => 1], 'from_id = ? AND to_id = ?', ['f' => $otherId, 't' => $userId]);
    }

    // ---------- 好友 ----------
    public static function addFriend(int $userId, int $friendId, string $note = ''): array
    {
        if ($userId === $friendId) {
            return ['ok' => false, 'msg' => '不能添加自己'];
        }
        $exists = Database::fetch('SELECT * FROM ' . Database::table('friends') . ' WHERE user_id = ? AND friend_id = ?', [$userId, $friendId]);
        if ($exists) {
            return ['ok' => false, 'msg' => '已发送过申请'];
        }
        Database::insert('friends', [
            'user_id' => $userId, 'friend_id' => $friendId, 'note' => $note, 'status' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        Database::insert('friends', [
            'user_id' => $friendId, 'friend_id' => $userId, 'note' => '', 'status' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        Notification::send($friendId, 'friend', '新增好友', '', url('user/' . $userId), $userId);
        return ['ok' => true, 'msg' => '已添加好友'];
    }

    public static function removeFriend(int $userId, int $friendId): void
    {
        Database::delete('friends', 'user_id = ? AND friend_id = ?', ['u' => $userId, 'f' => $friendId]);
        Database::delete('friends', 'user_id = ? AND friend_id = ?', ['u' => $friendId, 'f' => $userId]);
    }

    public static function friends(int $userId): array
    {
        return Database::fetchAll(
            'SELECT f.*, u.username, u.nickname, u.avatar, u.signature
             FROM ' . Database::table('friends') . ' f
             JOIN ' . Database::table('users') . ' u ON u.id = f.friend_id
             WHERE f.user_id = ? AND f.status = 1 ORDER BY f.id DESC',
            [$userId]
        );
    }

    // ---------- 关注 ----------
    public static function toggleFollow(int $userId, int $followId): array
    {
        $exists = Database::fetch('SELECT id FROM ' . Database::table('follows') . ' WHERE user_id = ? AND follow_id = ?', [$userId, $followId]);
        if ($exists) {
            Database::delete('follows', 'id = ?', ['id' => $exists['id']]);
            return ['following' => false, 'msg' => '已取消关注'];
        }
        Database::insert('follows', [
            'user_id' => $userId, 'follow_id' => $followId, 'created_at' => date('Y-m-d H:i:s'),
        ]);
        Notification::send($followId, 'follow', '新的粉丝', '', url('user/' . $userId), $userId);
        return ['following' => true, 'msg' => '关注成功'];
    }

    public static function followers(int $userId): array
    {
        return Database::fetchAll(
            'SELECT f.*, u.username, u.nickname, u.avatar
             FROM ' . Database::table('follows') . ' f
             JOIN ' . Database::table('users') . ' u ON u.id = f.user_id
             WHERE f.follow_id = ? ORDER BY f.id DESC',
            [$userId]
        );
    }

    public static function following(int $userId): array
    {
        return Database::fetchAll(
            'SELECT f.*, u.username, u.nickname, u.avatar
             FROM ' . Database::table('follows') . ' f
             JOIN ' . Database::table('users') . ' u ON u.id = f.follow_id
             WHERE f.user_id = ? ORDER BY f.id DESC',
            [$userId]
        );
    }

    // ---------- 黑名单 ----------
    public static function toggleBlock(int $userId, int $blockId): array
    {
        $exists = Database::fetch('SELECT id FROM ' . Database::table('blacklists') . ' WHERE user_id = ? AND block_id = ?', [$userId, $blockId]);
        if ($exists) {
            Database::delete('blacklists', 'id = ?', ['id' => $exists['id']]);
            return ['blocked' => false, 'msg' => '已解除屏蔽'];
        }
        Database::insert('blacklists', [
            'user_id' => $userId, 'block_id' => $blockId, 'created_at' => date('Y-m-d H:i:s'),
        ]);
        return ['blocked' => true, 'msg' => '已加入黑名单'];
    }

    public static function isBlocked(int $userId, int $blockId): bool
    {
        return (bool)Database::value(
            'SELECT id FROM ' . Database::table('blacklists') . ' WHERE user_id = ? AND block_id = ?',
            [$userId, $blockId]
        );
    }

    public static function blacklist(int $userId): array
    {
        return Database::fetchAll(
            'SELECT b.*, u.username, u.nickname, u.avatar
             FROM ' . Database::table('blacklists') . ' b
             JOIN ' . Database::table('users') . ' u ON u.id = b.block_id
             WHERE b.user_id = ? ORDER BY b.id DESC',
            [$userId]
        );
    }
}
