<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Notification;
use App\Models\User;

/**
 * 等级 / 经验
 */
class Level
{
    public const EXP_THREAD = 10;
    public const EXP_POST = 5;
    public const EXP_LIKE = 2;
    public const EXP_CHECKIN = 5;

    public static function addExp(int $userId, int $exp): void
    {
        if ($userId <= 0 || $exp <= 0) {
            return;
        }
        Database::query('UPDATE ' . Database::table('users') . ' SET exp = exp + ? WHERE id = ?', [$exp, $userId]);
        self::checkUpgrade($userId);
    }

    /**
     * 检查并自动升级
     */
    public static function checkUpgrade(int $userId): ?array
    {
        $user = User::find($userId);
        if (!$user) {
            return null;
        }
        $levels = Database::fetchAll(
            'SELECT * FROM ' . Database::table('user_levels') . ' WHERE auto_upgrade = 1 ORDER BY min_exp DESC, id DESC'
        );
        $days = max(0, (int)floor((time() - strtotime((string)$user['created_at'])) / 86400));
        foreach ($levels as $lv) {
            $meet = (int)$user['exp'] >= (int)$lv['min_exp']
                && (int)$user['threads'] >= (int)$lv['need_threads']
                && (int)$user['posts'] >= (int)$lv['need_posts']
                && (int)$user['likes_received'] >= (int)$lv['need_likes']
                && $days >= (int)$lv['need_days']
                && (int)$user['checkin_days'] >= (int)$lv['need_checkin'];
            if ($meet && (int)$user['level_id'] < (int)$lv['id']) {
                Database::update('users', ['level_id' => $lv['id']], 'id = ?', ['id' => $userId]);
                Notification::send($userId, 'level', '恭喜升级', '你的等级已提升至「' . $lv['name'] . '」', url('user/' . $userId));
                return $lv;
            }
        }
        return null;
    }

    /**
     * 升级进度
     */
    public static function progress(array $user): array
    {
        $levels = Database::fetchAll('SELECT * FROM ' . Database::table('user_levels') . ' ORDER BY sort_order, id');
        $current = null;
        $next = null;
        foreach ($levels as $lv) {
            if ((int)$lv['id'] === (int)$user['level_id']) {
                $current = $lv;
                continue;
            }
            if ($current !== null && $next === null) {
                $next = $lv;
            }
        }
        $percent = 0;
        if ($current && $next) {
            $span = max(1, (int)$next['min_exp'] - (int)$current['min_exp']);
            $percent = min(100, round(((int)$user['exp'] - (int)$current['min_exp']) / $span * 100, 1));
        } elseif ($current) {
            $percent = 100;
        }
        return ['current' => $current, 'next' => $next, 'percent' => $percent];
    }

    /**
     * 帖子标题颜色规则
     */
    public static function titleStyle(array $thread, array $user): array
    {
        $color = (string)($thread['title_color'] ?? '');
        $bold = (int)($thread['title_bold'] ?? 0) === 1;
        if ($color === '') {
            // 按用户组 / 等级 / 认证的展示色
            $group = Database::fetch('SELECT color FROM ' . Database::table('user_groups') . ' WHERE id = ?', [$user['group_id'] ?? 4]);
            $color = (string)($group['color'] ?? '');
        }
        return ['color' => $color, 'bold' => $bold];
    }
}
