<?php

declare(strict_types=1);

namespace App\Admin\Controllers;

use App\Admin\BaseAdmin;
use App\Core\Database;
use App\Models\Report;
use App\Services\Stats;

/**
 * 后台仪表盘
 */
class DashboardController extends BaseAdmin
{
    public function index(): string
    {
        $stats = Stats::dashboard();
        $trend = Stats::trend(7);

        $latestThreads = Database::fetchAll(
            'SELECT t.id, t.title, t.created_at, t.status, u.username FROM ' . Database::table('threads') . ' t
             LEFT JOIN ' . Database::table('users') . ' u ON u.id = t.user_id
             ORDER BY t.id DESC LIMIT 10'
        );
        $latestUsers = Database::fetchAll(
            'SELECT id, username, email, created_at, group_id FROM ' . Database::table('users') . '
             ORDER BY id DESC LIMIT 10'
        );
        $hotForums = Database::fetchAll(
            'SELECT id, name, threads, posts FROM ' . Database::table('forums') . '
             WHERE type = 2 ORDER BY threads DESC LIMIT 8'
        );

        return $this->render('dashboard', [
            'title' => '全局概览',
            'current_key' => 'dashboard.index',
            'stats' => $stats,
            'trend' => $trend,
            'latest_threads' => $latestThreads,
            'latest_users' => $latestUsers,
            'hot_forums' => $hotForums,
            'pending_reports' => (int)Report::pending(),
            'sys' => self::sysInfo(),
        ]);
    }

    /**
     * 简要系统信息
     */
    public static function sysInfo(): array
    {
        $size = 0;
        foreach (glob(CB_UPLOAD . '/*/*') ?: [] as $f) {
            if (is_file($f)) {
                $size += filesize($f);
            }
        }
        return [
            'php' => PHP_VERSION,
            'os' => PHP_OS,
            'software' => (string)($_SERVER['SERVER_SOFTWARE'] ?? 'CLI'),
            'mysql' => self::mysqlVersion(),
            'upload_max' => (string)ini_get('upload_max_filesize'),
            'memory_limit' => (string)ini_get('memory_limit'),
            'upload_size' => $size,
            'version' => CB_VERSION,
        ];
    }

    private static function mysqlVersion(): string
    {
        try {
            return (string)Database::value('SELECT VERSION()', [], '-');
        } catch (\Throwable $e) {
            return '-';
        }
    }
}
