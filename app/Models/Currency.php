<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * 货币系统
 */
class Currency
{
    /**
     * 变动余额
     */
    public static function change(int $userId, float $amount, string $type = 'other', string $note = '', int $relatedId = 0, int $adminId = 0): bool
    {
        if ($userId <= 0 || $amount == 0) {
            return false;
        }
        $user = User::find($userId);
        if (!$user) {
            return false;
        }
        $balance = (float)$user['money'] + $amount;
        if ($balance < 0) {
            return false;
        }
        Database::update('users', ['money' => $balance], 'id = ?', ['id' => $userId]);
        Database::insert('currency_logs', [
            'user_id' => $userId,
            'amount' => $amount,
            'balance' => $balance,
            'type' => $type,
            'note' => $note,
            'related_id' => $relatedId,
            'admin_id' => $adminId,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        return true;
    }

    public static function balance(int $userId): float
    {
        return (float)Database::value('SELECT money FROM ' . Database::table('users') . ' WHERE id = ?', [$userId], 0);
    }

    public static function logs(int $userId = 0, int $page = 1, int $perPage = 20, string $type = ''): array
    {
        $where = '1=1';
        $params = [];
        if ($userId > 0) {
            $where .= ' AND l.user_id = ?';
            $params[] = $userId;
        }
        if ($type !== '') {
            $where .= ' AND l.type = ?';
            $params[] = $type;
        }
        $total = (int)Database::value('SELECT COUNT(*) FROM ' . Database::table('currency_logs') . ' l WHERE ' . $where, $params, 0);
        $offset = ($page - 1) * $perPage;
        $items = Database::fetchAll(
            'SELECT l.*, u.username, u.nickname FROM ' . Database::table('currency_logs') . ' l
             LEFT JOIN ' . Database::table('users') . ' u ON u.id = l.user_id
             WHERE ' . $where . ' ORDER BY l.id DESC LIMIT ' . $offset . ',' . $perPage,
            $params
        );
        return ['total' => $total, 'items' => $items];
    }

    /**
     * 发帖 / 回帖 / 签到 奖励
     */
    public static function reward(int $userId, string $action): void
    {
        $map = [
            'thread' => (float)\App\Core\Settings::get('reward_thread', 5),
            'post' => (float)\App\Core\Settings::get('reward_post', 2),
            'say' => (float)\App\Core\Settings::get('say_reward', 1),
            'register' => (float)\App\Core\Settings::get('reward_register', 50),
        ];
        $amount = $map[$action] ?? 0;
        if ($amount <= 0) {
            return;
        }
        $names = [
            'thread' => '发帖奖励', 'post' => '回帖奖励', 'say' => '发布说说', 'register' => '注册奖励',
        ];
        self::change($userId, $amount, $action, $names[$action] ?? '奖励');
    }

    public static function typeName(string $type): string
    {
        return [
            'thread' => '发帖', 'post' => '回帖', 'say' => '说说', 'register' => '注册',
            'checkin' => '签到', 'download' => '附件下载', 'buy' => '购买',
            'admin_add' => '管理员充值', 'admin_sub' => '管理员扣减', 'other' => '其他',
        ][$type] ?? $type;
    }
}
