<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Settings;

/**
 * 「附近的人」：按 IP 网段判定（方案 A）
 *
 * 浏览器做不到真正的局域网广播发现（同源策略限制），所以这里用服务端判定：
 * 把当前访客 IP 与其他用户最近一次登录 IP 做网段比较，
 * IPv4 取 /24（192.168.1.x → 192.168.1.0/24），IPv6 取 /48。
 * 同一出口 IP（公司 / 学校 / 家庭宽带）下的用户会互相可见，
 * 且只有主动打开「允许附近的人发现我」的用户才会出现在列表里。
 */
class Lan
{
    /**
     * 网段前缀：IPv4 取前三段，IPv6 取前三组
     */
    public static function segment(string $ip): string
    {
        $ip = trim($ip);
        if ($ip === '') {
            return '';
        }
        if (str_contains($ip, ':')) {
            // IPv6：取前 3 组（/48）
            $parts = explode(':', $ip);
            return implode(':', array_slice($parts, 0, 3));
        }
        // IPv4：取前 3 段（/24）
        $parts = explode('.', $ip);
        if (count($parts) !== 4) {
            return '';
        }
        return $parts[0] . '.' . $parts[1] . '.' . $parts[2];
    }

    public static function enabled(): bool
    {
        return (int)Settings::get('lan_enable', 1) === 1;
    }

    /**
     * 附近的人（同网段 + 已开启「发现我」）
     *
     * @return array<int, array<string, mixed>>
     */
    public static function nearby(int $userId, string $myIp, int $limit = 30): array
    {
        if ($userId <= 0 || !self::enabled()) {
            return [];
        }
        $seg = self::segment($myIp);
        if ($seg === '') {
            return [];
        }
        $limit = max(1, min(100, $limit));

        // 先用网段前缀做一次粗筛（能用上 last_login_ip 的前缀索引思路，避免全表函数计算）
        try {
            $rows = Database::fetchAll(
                'SELECT id, username, nickname, avatar, last_login_ip, last_active_at, last_login_at
             FROM ' . Database::table('users') . '
             WHERE id <> ? AND lan_discover = 1 AND status = 1
               AND last_login_ip <> "" AND last_login_ip LIKE ?
             ORDER BY last_active_at DESC, id DESC
             LIMIT ' . $limit,
                [$userId, $seg . '%']
            );
        } catch (\Throwable $e) {
            // users.lan_discover 字段还没补上（未执行表结构修复）时静默跳过
            return [];
        }
        // 再精确比对网段，避免 LIKE 把 192.168.1.1 与 192.168.10.x 混在一起
        $out = [];
        foreach ($rows as $r) {
            if (self::segment((string)$r['last_login_ip']) === $seg) {
                $r['segment'] = $seg;
                $out[] = $r;
            }
        }
        return $out;
    }

    /**
     * 公开的群（附近的人可以直接加入）
     */
    public static function openGroups(int $userId, int $limit = 20): array
    {
        if (!ChatGroup::installed()) {
            return [];
        }
        $limit = max(1, min(100, $limit));
        $sql = 'SELECT g.* FROM ' . Database::table('chat_groups') . ' g WHERE g.is_open = 1';
        $params = [];
        if ($userId > 0) {
            $sql .= ' AND g.id NOT IN (SELECT group_id FROM ' . Database::table('chat_group_members') . ' WHERE user_id = ?)';
            $params[] = $userId;
        }
        $sql .= ' ORDER BY g.member_count DESC, g.id DESC LIMIT ' . $limit;
        try {
            return Database::fetchAll($sql, $params);
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * 切换「允许附近的人发现我」
     */
    public static function setDiscover(int $userId, bool $on): void
    {
        if ($userId <= 0) {
            return;
        }
        try {
            Database::update('users', ['lan_discover' => $on ? 1 : 0], 'id = ?', [$userId]);
        } catch (\Throwable $e) {
            // 字段缺失时忽略
        }
    }

    public static function discoverOn(int $userId): bool
    {
        try {
            $row = Database::fetch('SELECT lan_discover FROM ' . Database::table('users') . ' WHERE id = ? LIMIT 1', [$userId]);
        } catch (\Throwable $e) {
            return false;
        }
        return $row ? (int)$row['lan_discover'] === 1 : false;
    }

    /**
     * 记录当前 IP，保证「附近的人」能匹配到最新出口 IP
     */
    public static function touchIp(int $userId, string $ip): void
    {
        if ($userId <= 0 || $ip === '') {
            return;
        }
        Database::update('users', ['last_login_ip' => $ip, 'last_active_at' => date('Y-m-d H:i:s')], 'id = ?', [$userId]);
    }
}
