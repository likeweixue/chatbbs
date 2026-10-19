<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * 签到
 */
class Checkin
{
    public static function today(int $userId): ?array
    {
        return Database::fetch(
            'SELECT * FROM ' . Database::table('checkins') . ' WHERE user_id = ? AND day = CURDATE()',
            [$userId]
        );
    }

    public static function do(int $userId): array
    {
        if (self::today($userId)) {
            return ['ok' => false, 'msg' => '今天已经签到过了'];
        }
        $u = User::find($userId);
        $continue = 1;
        $yesterday = Database::fetch(
            'SELECT * FROM ' . Database::table('checkins') . ' WHERE user_id = ? AND day = DATE_SUB(CURDATE(), INTERVAL 1 DAY)',
            [$userId]
        );
        if ($yesterday) {
            $continue = (int)$yesterday['continue_days'] + 1;
        }
        $base = (float)\App\Core\Settings::get('reward_checkin', 3);
        $extra = (float)\App\Core\Settings::get('reward_checkin_extra', 1);
        $bonus = 0;
        if ($continue >= 30) {
            $bonus = 20;
        } elseif ($continue >= 7) {
            $bonus = 10;
        } elseif ($continue >= 3) {
            $bonus = 5;
        }
        $reward = $base + floor($continue / 7) * $extra + $bonus;

        Database::insert('checkins', [
            'user_id' => $userId,
            'day' => date('Y-m-d'),
            'reward' => $reward,
            'continue_days' => $continue,
            'is_makeup' => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        Database::query('UPDATE ' . Database::table('users') . ' SET checkin_days = checkin_days + 1, checkin_continue = ? WHERE id = ?', [$continue, $userId]);
        Currency::change($userId, $reward, 'checkin', '签到奖励（连续 ' . $continue . ' 天）');

        Notification::send($userId, 'checkin', '签到成功', '获得 ' . $reward . ' ' . currency_name() . '，已连续签到 ' . $continue . ' 天', url('checkin'));

        return ['ok' => true, 'msg' => '签到成功，获得 ' . $reward . ' ' . currency_name(), 'reward' => $reward, 'continue' => $continue];
    }

    /**
     * 当月签到记录（用于日历）
     */
    public static function month(int $userId, string $month = ''): array
    {
        $month = $month ?: date('Y-m');
        $rows = Database::fetchAll(
            'SELECT day, continue_days, is_makeup FROM ' . Database::table('checkins')
            . ' WHERE user_id = ? AND DATE_FORMAT(day, "%Y-%m") = ?',
            [$userId, $month]
        );
        $out = [];
        foreach ($rows as $r) {
            $out[substr((string)$r['day'], -2)] = [
                'continue' => (int)$r['continue_days'],
                'makeup' => (int)$r['is_makeup'],
            ];
        }
        return $out;
    }

    public static function continueDays(int $userId): int
    {
        $u = User::find($userId);
        return $u ? (int)$u['checkin_continue'] : 0;
    }

    public static function totalDays(int $userId): int
    {
        return (int)Database::value('SELECT COUNT(*) FROM ' . Database::table('checkins') . ' WHERE user_id = ?', [$userId], 0);
    }

    public static function cards(int $userId): int
    {
        return (int)Database::value(
            'SELECT COUNT(*) FROM ' . Database::table('checkin_cards') . ' WHERE user_id = ? AND used_at IS NULL',
            [$userId], 0
        );
    }

    /**
     * 补签
     */
    public static function makeup(int $userId, string $day): array
    {
        if (self::cards($userId) <= 0) {
            return ['ok' => false, 'msg' => '没有可用的补签卡'];
        }
        $exists = Database::fetch('SELECT id FROM ' . Database::table('checkins') . ' WHERE user_id = ? AND day = ?', [$userId, $day]);
        if ($exists) {
            return ['ok' => false, 'msg' => '该日期已签到'];
        }
        $card = Database::fetch('SELECT id FROM ' . Database::table('checkin_cards') . ' WHERE user_id = ? AND used_at IS NULL LIMIT 1', [$userId]);
        Database::update('checkin_cards', ['used_at' => date('Y-m-d H:i:s'), 'used_day' => $day], 'id = ?', ['id' => $card['id']]);
        Database::insert('checkins', [
            'user_id' => $userId, 'day' => $day, 'reward' => 0, 'continue_days' => 0, 'is_makeup' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        return ['ok' => true, 'msg' => '补签成功'];
    }

    public static function rank(int $limit = 10): array
    {
        return Database::fetchAll(
            'SELECT c.user_id, u.username, u.nickname, u.avatar, COUNT(*) AS days, MAX(c.continue_days) AS best
             FROM ' . Database::table('checkins') . ' c
             JOIN ' . Database::table('users') . ' u ON u.id = c.user_id
             GROUP BY c.user_id, u.username, u.nickname, u.avatar
             ORDER BY days DESC LIMIT ' . (int)$limit
        );
    }

    /**
     * 签到奖励预览
     */
    public static function preview(int $continue): array
    {
        $base = (float)\App\Core\Settings::get('reward_checkin', 3);
        $extra = (float)\App\Core\Settings::get('reward_checkin_extra', 1);
        $out = [];
        for ($i = 1; $i <= 7; $i++) {
            $c = $continue + $i;
            $bonus = $c >= 30 ? 20 : ($c >= 7 ? 10 : ($c >= 3 ? 5 : 0));
            $out[] = ['day' => $c, 'reward' => $base + floor($c / 7) * $extra + $bonus, 'bonus' => $bonus];
        }
        return $out;
    }
}
