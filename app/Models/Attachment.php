<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Attachment
{
    public static function find(int $id): ?array
    {
        return Database::fetch('SELECT * FROM ' . Database::table('attachments') . ' WHERE id = ?', [$id]);
    }

    public static function create(array $data): int
    {
        return Database::insert('attachments', array_merge(['created_at' => date('Y-m-d H:i:s')], $data));
    }

    public static function update(int $id, array $data): int
    {
        return Database::update('attachments', $data, 'id = ?', ['id' => $id]);
    }

    public static function delete(int $id): bool
    {
        $a = self::find($id);
        if (!$a) {
            return false;
        }
        \App\Core\Upload::delete((string)$a['space'], (string)$a['path']);
        Database::delete('attachment_downloads', 'attachment_id = ?', ['aid' => $id]);
        Database::delete('attachments', 'id = ?', ['id' => $id]);
        \App\Core\Logger::admin('删除附件', '#' . $id . ' ' . $a['name']);
        return true;
    }

    /**
     * 用户附件列表
     */
    public static function list(int $userId, string $space = '', int $page = 1, int $perPage = 20, string $keyword = ''): array
    {
        $where = 'user_id = ?';
        $params = [$userId];
        if ($space !== '') {
            $where .= ' AND space = ?';
            $params[] = $space;
        }
        if ($keyword !== '') {
            $where .= ' AND name LIKE ?';
            $params[] = '%' . $keyword . '%';
        }
        $total = (int)Database::value('SELECT COUNT(*) FROM ' . Database::table('attachments') . ' WHERE ' . $where, $params, 0);
        $offset = ($page - 1) * $perPage;
        $items = Database::fetchAll(
            'SELECT * FROM ' . Database::table('attachments') . ' WHERE ' . $where
            . ' ORDER BY id DESC LIMIT ' . $offset . ',' . $perPage,
            $params
        );
        return ['total' => $total, 'items' => $items];
    }

    /**
     * 后台：所有附件
     */
    public static function adminList(int $page = 1, int $perPage = 20, string $space = '', string $keyword = ''): array
    {
        $where = '1=1';
        $params = [];
        if ($space !== '') {
            $where .= ' AND space = ?';
            $params[] = $space;
        }
        if ($keyword !== '') {
            $where .= ' AND name LIKE ?';
            $params[] = '%' . $keyword . '%';
        }
        $total = (int)Database::value('SELECT COUNT(*) FROM ' . Database::table('attachments') . ' WHERE ' . $where, $params, 0);
        $offset = ($page - 1) * $perPage;
        $items = Database::fetchAll(
            'SELECT a.*, u.username FROM ' . Database::table('attachments') . ' a
             LEFT JOIN ' . Database::table('users') . ' u ON u.id = a.user_id
             WHERE ' . $where . ' ORDER BY a.id DESC LIMIT ' . $offset . ',' . $perPage,
            $params
        );
        return ['total' => $total, 'items' => $items];
    }

    public static function canDownload(array $att, ?array $user): array
    {
        $rule = (string)$att['download_rule'];
        if ($rule === 'login' && !$user) {
            return [false, '请登录后下载', null];
        }
        if (!$user) {
            return [false, '请登录后下载', null];
        }
        if ($rule === 'group') {
            $groups = array_filter(array_map('intval', explode(',', (string)$att['download_groups'])));
            if ($groups && !in_array((int)$user['group_id'], $groups, true)) {
                return [false, '当前用户组无权下载', null];
            }
        }
        $limit = (int)$att['download_limit'];
        if ($limit > 0) {
            $used = (int)Database::value(
                'SELECT COUNT(*) FROM ' . Database::table('attachment_downloads') . ' WHERE attachment_id = ? AND user_id = ?',
                [$att['id'], $user['id']], 0
            );
            if ($used >= $limit) {
                return [false, '已达下载次数上限', null];
            }
        }
        if ((float)$att['fee'] > 0) {
            $paid = Database::value(
                'SELECT id FROM ' . Database::table('attachment_downloads') . ' WHERE attachment_id = ? AND user_id = ? AND paid > 0',
                [$att['id'], $user['id']]
            );
            if (!$paid) {
                return [false, '该附件为付费内容，需支付 ' . $att['fee'] . ' 后下载', 'pay'];
            }
        }
        return [true, '', null];
    }

    public static function recordDownload(int $attId, int $userId, float $paid = 0): void
    {
        Database::query('UPDATE ' . Database::table('attachments') . ' SET downloads = downloads + 1 WHERE id = ?', [$attId]);
        Database::insert('attachment_downloads', [
            'attachment_id' => $attId,
            'user_id' => $userId,
            'paid' => $paid,
            'ip' => client_ip(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function usage(int $userId): array
    {
        $rows = Database::fetchAll(
            'SELECT space, COUNT(*) AS cnt, SUM(size) AS total FROM ' . Database::table('attachments') . ' WHERE user_id = ? GROUP BY space',
            [$userId]
        );
        $out = ['shared' => ['cnt' => 0, 'size' => 0], 'private' => ['cnt' => 0, 'size' => 0]];
        foreach ($rows as $r) {
            $out[$r['space']] = ['cnt' => (int)$r['cnt'], 'size' => (int)$r['total']];
        }
        return $out;
    }
}
