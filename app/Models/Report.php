<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Report
{
    public static function create(int $userId, string $type, int $targetId, string $reason, string $detail = ''): int
    {
        $exists = Database::value(
            'SELECT id FROM ' . Database::table('reports') . ' WHERE user_id = ? AND target_type = ? AND target_id = ? AND status = 0',
            [$userId, $type, $targetId]
        );
        if ($exists) {
            return 0;
        }
        return Database::insert('reports', [
            'user_id' => $userId, 'target_type' => $type, 'target_id' => $targetId,
            'reason' => $reason, 'detail' => $detail, 'status' => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function list(int $page = 1, int $perPage = 20, int $status = -1): array
    {
        $where = '1=1';
        $params = [];
        if ($status >= 0) {
            $where .= ' AND status = ?';
            $params[] = $status;
        }
        $total = (int)Database::value('SELECT COUNT(*) FROM ' . Database::table('reports') . ' WHERE ' . $where, $params, 0);
        $offset = ($page - 1) * $perPage;
        $items = Database::fetchAll(
            'SELECT r.*, u.username FROM ' . Database::table('reports') . ' r
             LEFT JOIN ' . Database::table('users') . ' u ON u.id = r.user_id
             WHERE ' . $where . ' ORDER BY r.status ASC, r.id DESC LIMIT ' . $offset . ',' . $perPage,
            $params
        );
        return ['total' => $total, 'items' => $items];
    }

    public static function find(int $id): ?array
    {
        return Database::fetch('SELECT * FROM ' . Database::table('reports') . ' WHERE id = ?', [$id]);
    }

    public static function handle(int $id, int $status, int $adminId, string $note = ''): void
    {
        Database::update('reports', [
            'status' => $status,
            'handle_user' => $adminId,
            'handle_note' => $note,
            'handled_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', ['id' => $id]);
    }

    public static function pending(): int
    {
        return (int)Database::value('SELECT COUNT(*) FROM ' . Database::table('reports') . ' WHERE status = 0', [], 0);
    }

    public static function targetTitle(array $report): string
    {
        switch ($report['target_type']) {
            case 'thread':
                $t = Thread::find((int)$report['target_id']);
                return $t ? (string)$t['title'] : '(已删除)';
            case 'post':
                $p = Post::find((int)$report['target_id']);
                return $p ? mb_substr(strip_tags((string)$p['content_text']), 0, 40) : '(已删除)';
            case 'say':
                $s = Say::find((int)$report['target_id']);
                return $s ? mb_substr(strip_tags((string)$s['content']), 0, 40) : '(已删除)';
            case 'user':
                $u = User::find((int)$report['target_id']);
                return $u ? (string)$u['username'] : '(已删除)';
        }
        return '(未知)';
    }
}
