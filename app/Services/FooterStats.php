<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Settings;

/**
 * 双栏页脚上方的统计 / 友情链接面板
 */
class FooterStats
{
    /** 是否显示友链面板（后台开关 + 存在配置） */
    public static function links(): array
    {
        if ((int)Settings::get('links_in_footer', 1) !== 1) {
            return [];
        }
        $raw = (string)Settings::get('friend_links', '');
        $out = [];
        foreach (preg_split('/\r\n|\r|\n/', $raw) ?: [] as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $parts = preg_split('/\s*\|\s*/', $line);
            $name = trim((string)($parts[0] ?? ''));
            $url = trim((string)($parts[1] ?? ''));
            $desc = trim((string)($parts[2] ?? ''));
            if ($url === '') {
                continue;
            }
            if (!preg_match('#^https?://#i', $url)) {
                $url = 'https://' . $url;
            }
            $out[] = ['name' => $name !== '' ? $name : $url, 'url' => $url, 'desc' => $desc];
        }
        return $out;
    }

    /** 论坛统计：帖子 / 会员 / 最新会员等 */
    public static function stats(): array
    {
        $stats = Stats::forum();
        $newest = Database::fetch(
            'SELECT id, username, nickname FROM ' . Database::table('users') . ' WHERE status = 1 ORDER BY id DESC LIMIT 1'
        );
        return [
            'threads' => (int)($stats['threads'] ?? 0),
            'posts' => (int)($stats['posts'] ?? 0),
            'users' => (int)($stats['users'] ?? 0),
            'today_threads' => (int)($stats['today_threads'] ?? 0),
            'today_posts' => (int)($stats['today_posts'] ?? 0),
            'today_users' => (int)($stats['today_users'] ?? 0),
            'newest' => $newest ? ['id' => (int)$newest['id'], 'name' => (string)($newest['nickname'] ?: $newest['username'])] : null,
        ];
    }
}
