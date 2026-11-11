<?php

declare(strict_types=1);

namespace App\Admin\Controllers;

use App\Admin\AdminUI;
use App\Admin\BaseAdmin;
use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Models\Ban;

/**
 * 小黑屋：封禁 / 禁言记录管理
 */
class BanController extends BaseAdmin
{
    public function index(): string
    {
        $rows = Database::fetchAll(
            'SELECT b.*, u.status AS user_status, u.group_id AS user_group FROM ' . Database::table('user_bans') . ' b'
            . ' LEFT JOIN ' . Database::table('users') . ' u ON u.id = b.user_id'
            . ' ORDER BY b.id DESC'
        );

        $html = '<div class="adm-alert info">小黑屋记录用户的封禁 / 禁言信息。解封后该用户仍会保留历史记录，但不再出现在前台小黑屋中。</div>';
        $html .= AdminUI::table([
            'primary' => 'id',
            'rows' => $rows,
            'columns' => [
                ['key' => 'id', 'label' => 'ID', 'width' => '60px'],
                ['key' => 'username', 'label' => '用户名', 'render' => fn($r) => '<a href="' . h(admin_url('users/edit/' . (int)$r['user_id'])) . '">' . h((string)$r['username']) . '</a>'],
                ['key' => 'action', 'label' => '行为', 'width' => '80px', 'render' => fn($r) => Ban::actionLabel((string)$r['action']) === '禁言'
                    ? '<span class="adm-tag orange">禁言</span>' : '<span class="adm-tag red">封禁</span>'],
                ['key' => 'note', 'label' => '备注'],
                ['key' => 'operator_name', 'label' => '操作人', 'width' => '120px', 'render' => fn($r) => h((string)($r['operator_name'] !== '' ? $r['operator_name'] : '-'))],
                ['key' => 'created_at', 'label' => '操作时间', 'width' => '150px', 'render' => fn($r) => format_date($r['created_at'])],
                ['key' => 'expire_at', 'label' => '过期时间', 'width' => '150px', 'render' => fn($r) => $r['expire_at']
                    ? format_date($r['expire_at']) : '<span class="muted">永久</span>'],
            ],
            'actions' => [
                ['label' => '解封', 'url' => admin_url('bans/unban/{id}'), 'type' => 'post', 'class' => 'success'],
            ],
        ]);

        // 新增封禁
        $html .= '<div class="adm-panel"><div class="adm-panel-head">新增封禁 / 禁言</div><div class="adm-panel-body">'
            . AdminUI::form([
                'action' => admin_url('bans/save'),
                'data' => ['action' => 'ban', 'expire_at' => ''],
                'submit' => '提交',
                'fields' => [
                    ['name' => 'username', 'label' => '用户名', 'placeholder' => '输入要封禁的用户名'],
                    ['name' => 'action', 'label' => '行为', 'type' => 'select', 'options' => ['ban' => '封禁（禁用账号）', 'mute' => '禁言']],
                    ['name' => 'expire_at', 'label' => '过期时间', 'type' => 'text', 'placeholder' => '如 2026-12-31 23:59:59；留空为永久'],
                    ['name' => 'note', 'label' => '备注', 'type' => 'textarea', 'rows' => 2],
                ],
            ]) . '</div></div>';

        return $this->pageView('小黑屋', 'bans', $html);
    }

    public function save(): void
    {
        $username = trim((string)Request::post('username', ''));
        $action = (string)Request::post('action', 'ban') === 'mute' ? 'mute' : 'ban';
        $note = $this->pure('note', 255);
        $expire = trim((string)Request::post('expire_at', ''));
        if ($username === '') {
            $this->back('请输入用户名', 'error');
        }
        $user = Database::fetch('SELECT id, username, status, group_id FROM ' . Database::table('users') . ' WHERE username = ? OR nickname = ?', [$username, $username]);
        if (!$user) {
            $this->back('用户不存在', 'error');
        }
        if ($expire !== '' && strtotime($expire) === false) {
            $this->back('过期时间格式不正确，请使用 2026-12-31 23:59:59 这种格式', 'error');
        }
        $op = Auth::user();
        $operator = ['id' => (int)($op['id'] ?? 0), 'name' => (string)($op['nickname'] ?? $op['username'] ?? '')];
        Ban::record((int)$user['id'], (string)$user['username'], $action, $note, $expire, $operator);
        // 同步更新用户状态
        if ($action === 'ban') {
            Database::update('users', ['status' => 2], 'id = ?', ['i' => (int)$user['id']]);
        } else {
            Database::update('users', ['group_id' => 6], 'id = ?', ['i' => (int)$user['id']]);
        }
        $this->log('小黑屋封禁', $username . ' ' . $action);
        $this->back('已记录');
    }

    public function unban(int $id): void
    {
        $row = Database::fetch('SELECT * FROM ' . Database::table('user_bans') . ' WHERE id = ?', [$id]);
        if ($row) {
            Ban::unban((int)$row['user_id']);
            // 解封后恢复为正常用户组 / 状态（若不是管理员手动指定）
            Database::update('users', ['status' => 1, 'group_id' => 4], 'id = ?', ['i' => (int)$row['user_id']]);
            $this->log('小黑屋解封', (string)$row['username']);
        }
        $this->back('已解封');
    }
}
