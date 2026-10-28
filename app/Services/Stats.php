<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Cache;
use App\Core\Database;

/**
 * 论坛统计
 */
class Stats
{
    public static function forum(): array
    {
        return Cache::remember('stats_forum', 300, function () {
            return [
                'threads' => (int)Database::value('SELECT COUNT(*) FROM ' . Database::table('threads') . ' WHERE status = 1', [], 0),
                'posts' => (int)Database::value('SELECT COUNT(*) FROM ' . Database::table('posts') . ' p JOIN ' . Database::table('threads') . ' t ON t.id = p.thread_id WHERE p.status = 1 AND p.is_first = 0 AND t.status = 1', [], 0),
                'users' => (int)Database::value('SELECT COUNT(*) FROM ' . Database::table('users') . ' WHERE status = 1', [], 0),
                'today_threads' => (int)Database::value('SELECT COUNT(*) FROM ' . Database::table('threads') . ' WHERE DATE(created_at) = CURDATE() AND status = 1', [], 0),
                'today_posts' => (int)Database::value('SELECT COUNT(*) FROM ' . Database::table('posts') . ' p JOIN ' . Database::table('threads') . ' t ON t.id = p.thread_id WHERE DATE(p.created_at) = CURDATE() AND p.status = 1 AND p.is_first = 0 AND t.status = 1', [], 0),
                'today_users' => (int)Database::value('SELECT COUNT(*) FROM ' . Database::table('users') . ' WHERE DATE(created_at) = CURDATE()', [], 0),
                'forums' => (int)Database::value('SELECT COUNT(*) FROM ' . Database::table('forums') . ' WHERE type = 2', [], 0),
                'attachments' => (int)Database::value('SELECT COUNT(*) FROM ' . Database::table('attachments'), [], 0),
            ];
        });
    }

    /**
     * 日历：当月每日发帖数
     */
    public static function calendar(string $month = ''): array
    {
        $month = $month ?: date('Y-m');
        return Cache::remember('calendar_' . $month, 600, function () use ($month) {
            $rows = Database::fetchAll(
                'SELECT DATE(created_at) AS d, COUNT(*) AS c FROM ' . Database::table('threads')
                . ' WHERE DATE_FORMAT(created_at, "%Y-%m") = ? AND status = 1 GROUP BY d',
                [$month]
            );
            $out = [];
            foreach ($rows as $r) {
                $out[(string)$r['d']] = (int)$r['c'];
            }
            return $out;
        });
    }

    public static function dashboard(): array
    {
        $s = self::forum();
        $s['pending_reports'] = (int)Database::value('SELECT COUNT(*) FROM ' . Database::table('reports') . ' WHERE status = 0', [], 0);
        $s['pending_threads'] = (int)Database::value('SELECT COUNT(*) FROM ' . Database::table('threads') . ' WHERE status = 0', [], 0);
        $s['pending_says'] = (int)Database::value('SELECT COUNT(*) FROM ' . Database::table('says') . ' WHERE status = 0', [], 0);
        $s['online_users'] = (int)Database::value(
            'SELECT COUNT(*) FROM ' . Database::table('users') . ' WHERE last_active_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)', [], 0
        );
        $s['money_total'] = (float)Database::value('SELECT SUM(money) FROM ' . Database::table('users'), [], 0);
        return $s;
    }

    /**
     * 近 N 天注册/发帖趋势
     */
    public static function trend(int $days = 7): array
    {
        return Cache::remember('trend_' . $days, 1800, function () use ($days) {
            $out = [];
            for ($i = $days - 1; $i >= 0; $i--) {
                $d = date('Y-m-d', strtotime("-{$i} days"));
                $out[] = [
                    'date' => $d,
                    'label' => date('m-d', strtotime($d)),
                    'threads' => (int)Database::value('SELECT COUNT(*) FROM ' . Database::table('threads') . ' WHERE DATE(created_at) = ? AND status = 1', [$d], 0),
                    'posts' => (int)Database::value('SELECT COUNT(*) FROM ' . Database::table('posts') . ' p JOIN ' . Database::table('threads') . ' t ON t.id = p.thread_id WHERE DATE(p.created_at) = ? AND p.status = 1 AND p.is_first = 0 AND t.status = 1', [$d], 0),
                    'users' => (int)Database::value('SELECT COUNT(*) FROM ' . Database::table('users') . ' WHERE DATE(created_at) = ?', [$d], 0),
                ];
            }
            return $out;
        });
    }

    public static function clearCache(): void
    {
        Cache::forget('stats_forum');
        Cache::flush();
    }
}
