<?php

declare(strict_types=1);

namespace App\Admin\Controllers;

use App\Admin\AdminUI;
use App\Admin\BaseAdmin;
use App\Core\Database;
use App\Core\Request;
use App\Core\Security;
use App\Models\Currency;
use App\Models\User;
use App\Services\Level;

/**
 * 用户管理：用户 / 用户组 / 认证 / 等级 / 签到 / 货币 / 私信
 */
class UserAdminController extends BaseAdmin
{
    /* ================== 用户列表 ================== */
    public function users(): string
    {
        $filters = [
            'kw' => (string)Request::get('kw', ''),
            'group' => (string)Request::get('group', ''),
            'status' => Request::input('status', ''),
        ];
        $ut = Database::table('users');
        $gt = Database::table('user_groups');
        $where = '1=1';
        $params = [];
        if ($filters['kw'] !== '') {
            $where .= ' AND (u.username LIKE ? OR u.email LIKE ? OR u.nickname LIKE ?)';
            $params[] = '%' . $filters['kw'] . '%';
            $params[] = '%' . $filters['kw'] . '%';
            $params[] = '%' . $filters['kw'] . '%';
        }
        if ($filters['group'] !== '') {
            $where .= ' AND u.group_id = ?';
            $params[] = (int)$filters['group'];
        }
        if ($filters['status'] !== '') {
            $where .= ' AND u.status = ?';
            $params[] = (int)$filters['status'];
        }

        $p = $this->queryPage(
            'SELECT u.*, g.name AS group_name, g.color AS group_color FROM ' . $ut . ' u
             LEFT JOIN ' . $gt . ' g ON g.id = u.group_id
             WHERE ' . $where . ' ORDER BY u.id DESC',
            'SELECT COUNT(*) FROM ' . $ut . ' u WHERE ' . $where,
            $params,
            20,
            $this->baseUrl('users', $filters)
        );

        $groupOpts = ['' => '全部用户组'] + self::groupOptions();

        $html = AdminUI::filter([
            'action' => admin_url('users'),
            'fields' => [
                ['name' => 'kw', 'label' => '用户名/邮箱/昵称', 'value' => $filters['kw']],
                ['name' => 'group', 'label' => '全部用户组', 'type' => 'select', 'options' => $groupOpts, 'value' => $filters['group']],
                ['name' => 'status', 'label' => '全部状态', 'type' => 'select', 'options' => [1 => '正常', 0 => '待激活', 2 => '禁用'], 'value' => $filters['status']],
            ],
        ]);
        $html .= AdminUI::table([
            'primary' => 'id',
            'rows' => $p['items'],
            'bulk_action' => admin_url('users/bulk'),
            'bulk' => [
                ['label' => '启用', 'value' => 'enable', 'class' => 'success'],
                ['label' => '禁用', 'value' => 'disable', 'class' => 'warn'],
                ['label' => '禁言', 'value' => 'mute', 'class' => 'warn'],
                ['label' => '删除', 'value' => 'delete', 'class' => 'danger', 'confirm' => '确定彻底删除选中用户？该操作不可恢复！'],
            ],
            'columns' => [
                ['key' => 'id', 'label' => 'ID', 'width' => '60px'],
                ['key' => 'username', 'label' => '用户', 'render' => function ($r) {
                    return '<img class="thumb" src="' . h(avatar_url($r)) . '" alt=""> '
                        . '<a href="' . h(admin_url('users/edit/' . (int)$r['id'])) . '">' . h((string)$r['username']) . '</a>'
                        . ((string)$r['nickname'] !== '' ? ' <span class="muted">' . h((string)$r['nickname']) . '</span>' : '')
                        . ((int)$r['is_admin'] === 1 ? ' <span class="adm-tag red">管理员</span>' : '');
                }],
                ['key' => 'email', 'label' => '邮箱', 'width' => '180px'],
                ['key' => 'group_name', 'label' => '用户组', 'width' => '110px', 'render' => fn($r) => '<span class="adm-tag" style="background:' . h((string)$r['group_color']) . '22;color:' . h((string)$r['group_color']) . '">'
                    . h((string)($r['group_name'] ?? '-')) . '</span>'],
                ['key' => 'money', 'label' => '货币', 'width' => '90px', 'render' => fn($r) => money((float)$r['money'])],
                ['key' => 'threads', 'label' => '主题', 'width' => '70px'],
                ['key' => 'posts', 'label' => '回复', 'width' => '70px'],
                ['key' => 'status', 'label' => '状态', 'width' => '90px', 'render' => fn($r) => match ((int)$r['status']) {
                    1 => '<span class="adm-tag green">正常</span>',
                    0 => '<span class="adm-tag orange">待激活</span>',
                    default => '<span class="adm-tag red">禁用</span>',
                }],
                ['key' => 'created_at', 'label' => '注册时间', 'width' => '130px', 'render' => fn($r) => format_date($r['created_at'])],
            ],
            'actions' => [
                ['label' => '编辑', 'url' => admin_url('users/edit/{id}')],
                ['label' => '充值', 'url' => admin_url('users/money/{id}')],
                ['label' => '删除', 'url' => admin_url('users/delete/{id}'), 'type' => 'post', 'class' => 'danger', 'confirm' => '确定删除该用户？'],
            ],
        ]);
        $html .= '<div class="adm-pager">' . $p['pager'] . '</div>';

        return $this->pageView('用户管理（共 ' . $p['total'] . ' 位）', 'users', $html, [
            ['label' => '+ 添加用户', 'url' => admin_url('users/create'), 'class' => 'primary'],
        ]);
    }

    public function userForm(int $id = 0): string
    {
        $data = $id > 0 ? User::find($id) : null;
        if ($id > 0 && !$data) {
            return $this->pageView('用户不存在', 'users', '<div class="adm-alert error">用户不存在</div>');
        }
        $fields = [
            ['name' => 'username', 'label' => '用户名'],
            ['name' => 'nickname', 'label' => '昵称'],
            ['name' => 'email', 'label' => '邮箱'],
        ];
        if ($id === 0) {
            $fields[] = ['name' => 'password', 'label' => '初始密码', 'type' => 'password'];
        }
        $fields = array_merge($fields, [
            ['name' => 'group_id', 'label' => '用户组', 'type' => 'select', 'options' => self::groupOptions()],
            ['name' => 'level_id', 'label' => '等级', 'type' => 'select', 'options' => self::levelOptions()],
            ['name' => 'cert_id', 'label' => '认证', 'type' => 'select', 'options' => [0 => '（无）'] + self::certOptions()],
            ['name' => 'status', 'label' => '状态', 'type' => 'select', 'options' => [1 => '正常', 0 => '待激活', 2 => '禁用']],
            ['name' => 'is_admin', 'label' => '设为管理员', 'type' => 'switch'],
            ['name' => 'money', 'label' => '货币余额', 'type' => 'number', 'step' => '0.01'],
            ['name' => 'exp', 'label' => '经验值', 'type' => 'number'],
            ['name' => 'signature', 'label' => '个性签名'],
            ['name' => 'bio', 'label' => '个人简介', 'type' => 'textarea'],
        ]);

        $html = AdminUI::form([
            'action' => admin_url('users/save'),
            'data' => $data ?? [],
            'hidden' => ['id' => $id],
            'submit' => '保存用户',
            'back' => admin_url('users'),
            'fields' => $fields,
        ] + ($id > 0 ? [] : []));

        if ($id > 0) {
            $html .= '<div class="adm-panel"><div class="adm-panel-head">重置密码</div><div class="adm-panel-body">'
                . AdminUI::form([
                    'action' => admin_url('users/password'),
                    'hidden' => ['id' => $id],
                    'submit' => '重置密码',
                    'fields' => [['name' => 'password', 'label' => '新密码', 'type' => 'password', 'tip' => '留空则不修改，至少 6 位']],
                ]) . '</div></div>';
        }

        return $this->pageView($id > 0 ? '编辑用户：' . (string)$data['username'] : '添加用户', 'users', $html);
    }

    public function userSave(): void
    {
        $id = Request::int('id');
        $username = $this->pure('username', 50);
        $email = $this->pure('email', 120);

        if ($id > 0) {
            $data = [
                'username' => $username,
                'nickname' => $this->pure('nickname', 50),
                'email' => $email,
                'group_id' => Request::int('group_id', 4),
                'level_id' => Request::int('level_id', 1),
                'cert_id' => Request::int('cert_id'),
                'status' => Request::int('status', 1),
                'is_admin' => Request::bool('is_admin') ? 1 : 0,
                'money' => (float)Request::input('money', 0),
                'exp' => Request::int('exp'),
                'signature' => $this->pure('signature', 255),
                'bio' => $this->pure('bio', 500),
            ];
            User::update($id, $data);
            $this->log('编辑用户', $username);
            $this->back('用户资料已保存');
        }

        $password = Request::raw('password');
        if ($username === '' || $email === '' || strlen($password) < 6) {
            $this->back('用户名、邮箱必填，密码至少 6 位', 'error');
        }
        if (User::findByUsername($username) || User::findByEmail($email)) {
            $this->back('用户名或邮箱已存在', 'error');
        }
        User::create([
            'username' => $username,
            'nickname' => $this->pure('nickname', 50) ?: $username,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'group_id' => Request::int('group_id', 4),
            'level_id' => Request::int('level_id', 1),
            'cert_id' => Request::int('cert_id'),
            'status' => Request::int('status', 1),
            'is_admin' => Request::bool('is_admin') ? 1 : 0,
            'money' => (float)Request::input('money', 0),
            'exp' => Request::int('exp'),
            'signature' => $this->pure('signature', 255),
            'bio' => $this->pure('bio', 500),
            'email_verified' => 1,
        ]);
        $this->log('新增用户', $username);
        $this->back('用户已创建');
    }

    public function userPassword(): void
    {
        $id = Request::int('id');
        $password = Request::raw('password');
        if (strlen($password) < 6) {
            $this->back('密码至少 6 位', 'error');
        }
        User::update($id, ['password' => password_hash($password, PASSWORD_DEFAULT)]);
        $this->log('重置用户密码', 'ID:' . $id);
        $this->back('密码已重置');
    }

    public function userMoney(int $id): string
    {
        $user = User::find($id);
        if (!$user) {
            return $this->pageView('用户不存在', 'users', '<div class="adm-alert error">用户不存在</div>');
        }
        $logs = Currency::logs($id, 1, 20);
        $rows = '';
        foreach (($logs['items'] ?? []) as $l) {
            $rows .= '<tr><td>' . ((float)$l['amount'] > 0 ? '<span class="adm-tag green">+' . (float)$l['amount'] . '</span>'
                    : '<span class="adm-tag red">' . (float)$l['amount'] . '</span>') . '</td>'
                . '<td>' . h((string)Currency::typeName((string)$l['type'])) . '</td>'
                . '<td>' . h((string)$l['note']) . '</td>'
                . '<td>' . h(format_date($l['created_at'])) . '</td></tr>';
        }
        $html = '<div class="adm-panel"><div class="adm-panel-head">账户充值 / 扣款（当前余额：' . money((float)$user['money']) . '）</div>'
            . '<div class="adm-panel-body">'
            . AdminUI::form([
                'action' => admin_url('users/money/save'),
                'hidden' => ['id' => $id],
                'submit' => '确认操作',
                'fields' => [
                    ['name' => 'amount', 'label' => '变动金额', 'type' => 'number', 'step' => '0.01', 'tip' => '正数充值，负数扣款'],
                    ['name' => 'note', 'label' => '备注说明'],
                ],
            ]) . '</div></div>';
        $html .= '<div class="adm-panel"><div class="adm-panel-head">最近流水</div><div class="adm-panel-body tight">'
            . '<table class="adm-table"><thead><tr><th>金额</th><th>类型</th><th>说明</th><th>时间</th></tr></thead><tbody>'
            . ($rows ?: '<tr><td colspan="4" class="adm-empty">暂无流水</td></tr>')
            . '</tbody></table></div></div>';

        return $this->pageView('货币管理：' . (string)$user['username'], 'users', $html, [
            ['label' => '返回用户列表', 'url' => admin_url('users')],
        ]);
    }

    public function userMoneySave(): void
    {
        $id = Request::int('id');
        $amount = (float)Request::input('amount', 0);
        $note = $this->pure('note', 255);
        if ($amount == 0.0) {
            $this->back('变动金额不能为 0', 'error');
        }
        Currency::change($id, $amount, 'admin', $note, 0, (int)(\App\Core\Auth::id() ?? 0));
        $this->log('调整用户货币', 'ID:' . $id . ' 金额:' . $amount);
        $this->back('货币已调整');
    }

    public function userDelete(int $id): void
    {
        if ($id === (int)(\App\Core\Auth::id() ?? 0)) {
            $this->back('不能删除当前登录的账号', 'error');
        }
        Database::delete('users', 'id = ?', ['i' => $id]);
        Database::delete('user_profiles', 'user_id = ?', ['u' => $id]);
        $this->log('删除用户', 'ID:' . $id);
        $this->back('用户已删除');
    }

    public function userBulk(): void
    {
        $ids = $this->ids();
        $act = (string)Request::post('act', '');
        if (!$ids) {
            $this->back('请先选择用户', 'error');
        }
        $in = implode(',', array_map('intval', $ids));
        switch ($act) {
            case 'enable':
                Database::query('UPDATE ' . Database::table('users') . ' SET status = 1 WHERE id IN (' . $in . ')');
                break;
            case 'disable':
                Database::query('UPDATE ' . Database::table('users') . ' SET status = 2 WHERE id IN (' . $in . ')');
                break;
            case 'mute':
                Database::query('UPDATE ' . Database::table('users') . ' SET group_id = 6 WHERE id IN (' . $in . ')');
                break;
            case 'delete':
                $self = (int)(\App\Core\Auth::id() ?? 0);
                $ids = array_filter($ids, static fn($i) => $i !== $self);
                if ($ids) {
                    $in = implode(',', $ids);
                    Database::query('DELETE FROM ' . Database::table('users') . ' WHERE id IN (' . $in . ')');
                }
                break;
        }
        $this->log('用户批量操作', $act . ':' . $in);
        $this->back('批量操作完成');
    }

    /* ================== 用户组 ================== */
    public function groups(): string
    {
        $rows = Database::fetchAll('SELECT * FROM ' . Database::table('user_groups') . ' ORDER BY sort_order ASC, id ASC');
        $permList = self::permissionList();

        $html = AdminUI::table([
            'primary' => 'id',
            'rows' => $rows,
            'columns' => [
                ['key' => 'id', 'label' => 'ID', 'width' => '60px'],
                ['key' => 'name', 'label' => '组名', 'render' => fn($r) => '<span class="adm-tag" style="background:' . h((string)$r['color']) . '22;color:' . h((string)$r['color']) . '">'
                    . h((string)$r['name']) . '</span>' . ((int)$r['is_system'] ? ' <span class="adm-tag gray">系统</span>' : '')],
                ['key' => 'slug', 'label' => '标识', 'width' => '120px'],
                ['key' => 'description', 'label' => '说明', 'render' => fn($r) => h(str_cut((string)$r['description'], 40))],
                ['key' => 'permissions', 'label' => '权限数', 'width' => '90px', 'render' => function ($r) {
                    $p = json_decode((string)$r['permissions'], true);
                    return is_array($p) ? count($p) : 0;
                }],
                ['key' => 'sort_order', 'label' => '排序', 'width' => '70px'],
            ],
            'actions' => [
                ['label' => '编辑', 'url' => admin_url('groups/edit/{id}')],
                ['label' => '删除', 'url' => admin_url('groups/delete/{id}'), 'type' => 'post', 'class' => 'danger', 'confirm' => '确定删除该用户组？'],
            ],
        ]);

        $edit = null;
        $editId = Request::int('edit');
        if ($editId > 0) {
            $edit = Database::fetch('SELECT * FROM ' . Database::table('user_groups') . ' WHERE id = ?', [$editId]) ?: null;
        }
        $permValue = $edit ? (json_decode((string)$edit['permissions'], true) ?: []) : [];
        $html .= '<div class="adm-panel"><div class="adm-panel-head">' . ($edit ? '编辑用户组：' . h((string)$edit['name']) : '新增用户组') . '</div><div class="adm-panel-body">'
            . AdminUI::form([
                'action' => admin_url('groups/save'),
                'data' => $edit ?? [],
                'hidden' => ['id' => $edit['id'] ?? 0],
                'submit' => '保存',
                'fields' => [
                    ['name' => 'name', 'label' => '组名称'],
                    ['name' => 'slug', 'label' => '标识', 'tip' => '英文，如 vip'],
                    ['name' => 'description', 'label' => '说明', 'type' => 'textarea'],
                    ['name' => 'color', 'label' => '颜色', 'type' => 'color'],
                    ['name' => 'permissions', 'label' => '权限', 'type' => 'checkboxes', 'options' => $permList, 'value' => $permValue],
                    ['name' => 'sort_order', 'label' => '排序', 'type' => 'number'],
                ],
            ]) . '</div></div>';

        return $this->pageView('用户组管理', 'groups', $html);
    }

    public function groupSave(): void
    {
        $id = Request::int('id');
        $perms = array_values(array_map('strval', (array)Request::post('permissions', [])));
        $data = [
            'name' => $this->pure('name', 50),
            'slug' => $this->pure('slug', 50),
            'description' => $this->pure('description', 255),
            'color' => $this->pure('color', 20),
            'permissions' => json_encode($perms, JSON_UNESCAPED_UNICODE),
            'sort_order' => Request::int('sort_order'),
        ];
        if ($data['name'] === '') {
            $this->back('组名称不能为空', 'error');
        }
        if ($id > 0) {
            Database::update('user_groups', $data, 'id = ?', ['i' => $id]);
        } else {
            $data['slug'] = $data['slug'] ?: 'group' . time();
            Database::insert('user_groups', $data);
        }
        $this->log('保存用户组', $data['name']);
        $this->back('用户组已保存');
    }

    public function groupDelete(int $id): void
    {
        $row = Database::fetch('SELECT * FROM ' . Database::table('user_groups') . ' WHERE id = ?', [$id]);
        if (!$row) {
            $this->back('用户组不存在', 'error');
        }
        if ((int)$row['is_system'] === 1) {
            $this->back('系统内置用户组不可删除', 'error');
        }
        Database::update('users', ['group_id' => 4], 'group_id = ?', ['g' => $id]);
        Database::delete('user_groups', 'id = ?', ['i' => $id]);
        $this->log('删除用户组', 'ID:' . $id);
        $this->back('用户组已删除');
    }

    /* ================== 认证 ================== */
    public function certs(): string
    {
        $rows = Database::fetchAll('SELECT * FROM ' . Database::table('user_certifications') . ' ORDER BY sort_order ASC, id ASC');
        $html = AdminUI::table([
            'primary' => 'id',
            'rows' => $rows,
            'columns' => [
                ['key' => 'id', 'label' => 'ID', 'width' => '60px'],
                ['key' => 'name', 'label' => '认证名称', 'render' => fn($r) => '<span class="adm-tag" style="background:' . h((string)$r['color']) . '22;color:' . h((string)$r['color']) . '">'
                    . h((string)$r['icon'] . ' ' . $r['name']) . '</span>'],
                ['key' => 'description', 'label' => '说明'],
                ['key' => 'sort_order', 'label' => '排序', 'width' => '70px'],
            ],
            'actions' => [
                ['label' => '编辑', 'url' => admin_url('certs?edit={id}')],
                ['label' => '删除', 'url' => admin_url('certs/delete/{id}'), 'type' => 'post', 'class' => 'danger', 'confirm' => '确定删除该认证？'],
            ],
        ]);
        $edit = null;
        $editId = Request::int('edit');
        if ($editId > 0) {
            $edit = Database::fetch('SELECT * FROM ' . Database::table('user_certifications') . ' WHERE id = ?', [$editId]) ?: null;
        }
        $html .= '<div class="adm-panel"><div class="adm-panel-head">' . ($edit ? '编辑认证' : '新增认证') . '</div><div class="adm-panel-body">'
            . AdminUI::form([
                'action' => admin_url('certs/save'),
                'data' => $edit ?? [],
                'hidden' => ['id' => $edit['id'] ?? 0],
                'submit' => '保存',
                'fields' => [
                    ['name' => 'name', 'label' => '认证名称', 'placeholder' => '如：官方认证'],
                    ['name' => 'icon', 'label' => '图标', 'tip' => 'emoji 或图标字符'],
                    ['name' => 'color', 'label' => '颜色', 'type' => 'color'],
                    ['name' => 'description', 'label' => '说明'],
                    ['name' => 'sort_order', 'label' => '排序', 'type' => 'number'],
                ],
            ]) . '</div></div>';
        return $this->pageView('认证管理', 'certs', $html);
    }

    public function certSave(): void
    {
        $id = Request::int('id');
        $data = [
            'name' => $this->pure('name', 50),
            'icon' => $this->pure('icon', 255),
            'color' => $this->pure('color', 20),
            'description' => $this->pure('description', 255),
            'sort_order' => Request::int('sort_order'),
        ];
        if ($data['name'] === '') {
            $this->back('认证名称不能为空', 'error');
        }
        if ($id > 0) {
            Database::update('user_certifications', $data, 'id = ?', ['i' => $id]);
        } else {
            Database::insert('user_certifications', $data);
        }
        $this->log('保存认证', $data['name']);
        $this->back('认证已保存');
    }

    public function certDelete(int $id): void
    {
        Database::update('users', ['cert_id' => 0], 'cert_id = ?', ['c' => $id]);
        Database::delete('user_certifications', 'id = ?', ['i' => $id]);
        $this->log('删除认证', 'ID:' . $id);
        $this->back('认证已删除');
    }

    /* ================== 等级 ================== */
    public function levels(): string
    {
        $rows = Database::fetchAll('SELECT * FROM ' . Database::table('user_levels') . ' ORDER BY sort_order ASC, id ASC');
        $html = AdminUI::table([
            'primary' => 'id',
            'rows' => $rows,
            'columns' => [
                ['key' => 'id', 'label' => 'ID', 'width' => '60px'],
                ['key' => 'name', 'label' => '等级名称', 'render' => fn($r) => h((string)$r['icon'] . ' ' . $r['name'])],
                ['key' => 'min_exp', 'label' => '所需经验', 'width' => '100px'],
                ['key' => 'need_threads', 'label' => '主题数', 'width' => '80px'],
                ['key' => 'need_posts', 'label' => '回复数', 'width' => '80px'],
                ['key' => 'need_likes', 'label' => '获赞数', 'width' => '80px'],
                ['key' => 'need_days', 'label' => '注册天数', 'width' => '90px'],
                ['key' => 'need_checkin', 'label' => '签到数', 'width' => '80px'],
                ['key' => 'auto_upgrade', 'label' => '自动升级', 'width' => '90px', 'render' => fn($r) => (int)$r['auto_upgrade'] === 1
                    ? '<span class="adm-tag green">是</span>' : '<span class="adm-tag gray">否</span>'],
            ],
            'actions' => [
                ['label' => '编辑', 'url' => admin_url('levels?edit={id}')],
                ['label' => '删除', 'url' => admin_url('levels/delete/{id}'), 'type' => 'post', 'class' => 'danger', 'confirm' => '确定删除该等级？'],
            ],
        ]);
        $edit = null;
        $editId = Request::int('edit');
        if ($editId > 0) {
            $edit = Database::fetch('SELECT * FROM ' . Database::table('user_levels') . ' WHERE id = ?', [$editId]) ?: null;
        }
        $html .= '<div class="adm-panel"><div class="adm-panel-head">' . ($edit ? '编辑等级' : '新增等级') . '</div><div class="adm-panel-body">'
            . AdminUI::form([
                'action' => admin_url('levels/save'),
                'data' => $edit ?? [],
                'hidden' => ['id' => $edit['id'] ?? 0],
                'submit' => '保存',
                'fields' => [
                    ['name' => 'name', 'label' => '等级名称', 'placeholder' => '如：V1 新手'],
                    ['name' => 'icon', 'label' => '图标'],
                    ['name' => 'min_exp', 'label' => '所需经验', 'type' => 'number'],
                    ['name' => 'need_threads', 'label' => '所需主题数', 'type' => 'number'],
                    ['name' => 'need_posts', 'label' => '所需回复数', 'type' => 'number'],
                    ['name' => 'need_likes', 'label' => '所需获赞数', 'type' => 'number'],
                    ['name' => 'need_days', 'label' => '注册天数', 'type' => 'number'],
                    ['name' => 'need_checkin', 'label' => '签到天数', 'type' => 'number'],
                    ['name' => 'auto_upgrade', 'label' => '自动升级', 'type' => 'switch'],
                    ['name' => 'sort_order', 'label' => '排序', 'type' => 'number'],
                ],
            ]) . '</div></div>';
        return $this->pageView('等级管理', 'levels', $html);
    }

    public function levelSave(): void
    {
        $id = Request::int('id');
        $data = [
            'name' => $this->pure('name', 50),
            'icon' => $this->pure('icon', 255),
            'min_exp' => Request::int('min_exp'),
            'need_threads' => Request::int('need_threads'),
            'need_posts' => Request::int('need_posts'),
            'need_likes' => Request::int('need_likes'),
            'need_days' => Request::int('need_days'),
            'need_checkin' => Request::int('need_checkin'),
            'auto_upgrade' => Request::bool('auto_upgrade') ? 1 : 0,
            'sort_order' => Request::int('sort_order'),
        ];
        if ($data['name'] === '') {
            $this->back('等级名称不能为空', 'error');
        }
        if ($id > 0) {
            Database::update('user_levels', $data, 'id = ?', ['i' => $id]);
        } else {
            Database::insert('user_levels', $data);
        }
        $this->log('保存等级', $data['name']);
        $this->back('等级已保存');
    }

    public function levelDelete(int $id): void
    {
        Database::update('users', ['level_id' => 1], 'level_id = ?', ['l' => $id]);
        Database::delete('user_levels', 'id = ?', ['i' => $id]);
        $this->log('删除等级', 'ID:' . $id);
        $this->back('等级已删除');
    }

    /* ================== 签到记录 ================== */
    public function checkins(): string
    {
        $filters = ['kw' => (string)Request::get('kw', ''), 'day' => (string)Request::get('day', '')];
        $ct = Database::table('checkins');
        $ut = Database::table('users');
        $where = '1=1';
        $params = [];
        if ($filters['kw'] !== '') {
            $where .= ' AND u.username LIKE ?';
            $params[] = '%' . $filters['kw'] . '%';
        }
        if ($filters['day'] !== '') {
            $where .= ' AND c.day = ?';
            $params[] = $filters['day'];
        }
        $p = $this->queryPage(
            'SELECT c.*, u.username FROM ' . $ct . ' c LEFT JOIN ' . $ut . ' u ON u.id = c.user_id
             WHERE ' . $where . ' ORDER BY c.id DESC',
            'SELECT COUNT(*) FROM ' . $ct . ' c LEFT JOIN ' . $ut . ' u ON u.id = c.user_id WHERE ' . $where,
            $params,
            20,
            $this->baseUrl('checkins', $filters)
        );

        $html = AdminUI::filter([
            'action' => admin_url('checkins'),
            'fields' => [
                ['name' => 'kw', 'label' => '用户名', 'value' => $filters['kw']],
                ['name' => 'day', 'label' => '日期(YYYY-MM-DD)', 'value' => $filters['day']],
            ],
        ]);
        $html .= AdminUI::table([
            'primary' => 'id',
            'rows' => $p['items'],
            'columns' => [
                ['key' => 'id', 'label' => 'ID', 'width' => '60px'],
                ['key' => 'username', 'label' => '用户', 'width' => '160px'],
                ['key' => 'day', 'label' => '签到日期', 'width' => '130px'],
                ['key' => 'reward', 'label' => '奖励', 'width' => '110px', 'render' => fn($r) => money((float)$r['reward'])],
                ['key' => 'continue_days', 'label' => '连续天数', 'width' => '100px'],
                ['key' => 'is_makeup', 'label' => '补签', 'width' => '80px', 'render' => fn($r) => (int)$r['is_makeup'] === 1
                    ? '<span class="adm-tag orange">补签</span>' : '-'],
                ['key' => 'created_at', 'label' => '时间', 'width' => '140px', 'render' => fn($r) => format_date($r['created_at'])],
            ],
        ]);
        $html .= '<div class="adm-pager">' . $p['pager'] . '</div>';

        $rank = \App\Models\Checkin::rank(10);
        $rankHtml = '<div class="adm-panel"><div class="adm-panel-head">签到排行榜 TOP10</div><div class="adm-panel-body tight">'
            . '<table class="adm-table"><thead><tr><th>用户</th><th>累计签到</th><th>最高连续</th></tr></thead><tbody>';
        foreach ($rank as $rk) {
            $rankHtml .= '<tr><td>' . h((string)$rk['username']) . '</td><td>' . (int)($rk['days'] ?? 0) . '</td>'
                . '<td>' . (int)($rk['best'] ?? 0) . '</td></tr>';
        }
        $rankHtml .= '</tbody></table></div></div>';

        return $this->pageView('签到记录（共 ' . $p['total'] . ' 条）', 'checkins', $html . $rankHtml);
    }

    /* ================== 货币管理 ================== */
    public function currency(): string
    {
        $filters = ['type' => (string)Request::get('type', ''), 'kw' => (string)Request::get('kw', '')];
        $lt = Database::table('currency_logs');
        $ut = Database::table('users');
        $where = '1=1';
        $params = [];
        if ($filters['type'] !== '') {
            $where .= ' AND l.type = ?';
            $params[] = $filters['type'];
        }
        if ($filters['kw'] !== '') {
            $where .= ' AND u.username LIKE ?';
            $params[] = '%' . $filters['kw'] . '%';
        }
        $p = $this->queryPage(
            'SELECT l.*, u.username FROM ' . $lt . ' l LEFT JOIN ' . $ut . ' u ON u.id = l.user_id
             WHERE ' . $where . ' ORDER BY l.id DESC',
            'SELECT COUNT(*) FROM ' . $lt . ' l LEFT JOIN ' . $ut . ' u ON u.id = l.user_id WHERE ' . $where,
            $params,
            20,
            $this->baseUrl('currency', $filters)
        );

        $totalMoney = (float)Database::value('SELECT SUM(money) FROM ' . Database::table('users'), [], 0);
        $inMoney = (float)Database::value('SELECT SUM(amount) FROM ' . $lt . ' WHERE amount > 0', [], 0);
        $outMoney = (float)Database::value('SELECT SUM(amount) FROM ' . $lt . ' WHERE amount < 0', [], 0);

        $html = '<div class="adm-stats">'
            . '<div class="adm-stat"><div class="adm-stat__label">全站货币总量</div><div class="adm-stat__value">' . money($totalMoney) . '</div></div>'
            . '<div class="adm-stat"><div class="adm-stat__label">累计发放</div><div class="adm-stat__value" style="color:#27ae60">' . money($inMoney) . '</div></div>'
            . '<div class="adm-stat"><div class="adm-stat__label">累计消耗</div><div class="adm-stat__value" style="color:#e34d4d">' . money(abs($outMoney)) . '</div></div>'
            . '</div>';

        $html .= AdminUI::filter([
            'action' => admin_url('currency'),
            'fields' => [
                ['name' => 'type', 'label' => '全部类型', 'type' => 'select', 'options' => self::currencyTypes(), 'value' => $filters['type']],
                ['name' => 'kw', 'label' => '用户名', 'value' => $filters['kw']],
            ],
        ]);
        $html .= AdminUI::table([
            'primary' => 'id',
            'rows' => $p['items'],
            'columns' => [
                ['key' => 'id', 'label' => 'ID', 'width' => '60px'],
                ['key' => 'username', 'label' => '用户', 'width' => '140px'],
                ['key' => 'amount', 'label' => '变动', 'width' => '110px', 'render' => fn($r) => (float)$r['amount'] >= 0
                    ? '<span class="adm-tag green">+' . (float)$r['amount'] . '</span>'
                    : '<span class="adm-tag red">' . (float)$r['amount'] . '</span>'],
                ['key' => 'balance', 'label' => '余额', 'width' => '110px', 'render' => fn($r) => money((float)$r['balance'])],
                ['key' => 'type', 'label' => '类型', 'width' => '110px', 'render' => fn($r) => h(Currency::typeName((string)$r['type']))],
                ['key' => 'note', 'label' => '说明', 'render' => fn($r) => h(str_cut((string)$r['note'], 40))],
                ['key' => 'created_at', 'label' => '时间', 'width' => '140px', 'render' => fn($r) => format_date($r['created_at'])],
            ],
        ]);
        $html .= '<div class="adm-pager">' . $p['pager'] . '</div>';

        return $this->pageView('货币管理', 'currency', $html);
    }

    /* ================== 私信管理 ================== */
    public function messages(): string
    {
        $mt = Database::table('private_messages');
        $p = $this->queryPage(
            'SELECT m.*, f.username AS from_name, t.username AS to_name FROM ' . $mt . ' m
             LEFT JOIN ' . Database::table('users') . ' f ON f.id = m.from_id
             LEFT JOIN ' . Database::table('users') . ' t ON t.id = m.to_id
             ORDER BY m.id DESC',
            'SELECT COUNT(*) FROM ' . $mt,
            [],
            20,
            admin_url('messages')
        );
        $html = '<div class="adm-alert info">此处仅用于违规内容审查，正常私信受隐私保护。</div>';
        $html .= AdminUI::table([
            'primary' => 'id',
            'rows' => $p['items'],
            'bulk_action' => admin_url('messages/bulk'),
            'bulk' => [['label' => '删除', 'value' => 'delete', 'class' => 'danger', 'confirm' => '确定删除选中私信？']],
            'columns' => [
                ['key' => 'id', 'label' => 'ID', 'width' => '60px'],
                ['key' => 'from_name', 'label' => '发送人', 'width' => '130px'],
                ['key' => 'to_name', 'label' => '接收人', 'width' => '130px'],
                ['key' => 'content', 'label' => '内容', 'render' => fn($r) => h(str_cut(strip_tags((string)$r['content']), 70))],
                ['key' => 'is_read', 'label' => '状态', 'width' => '80px', 'render' => fn($r) => (int)$r['is_read'] === 1
                    ? '<span class="adm-tag gray">已读</span>' : '<span class="adm-tag green">未读</span>'],
                ['key' => 'created_at', 'label' => '时间', 'width' => '140px', 'render' => fn($r) => format_date($r['created_at'])],
            ],
            'actions' => [
                ['label' => '删除', 'url' => admin_url('messages/delete/{id}'), 'type' => 'post', 'class' => 'danger', 'confirm' => '确定删除该私信？'],
            ],
        ]);
        $html .= '<div class="adm-pager">' . $p['pager'] . '</div>';
        return $this->pageView('私信管理（共 ' . $p['total'] . ' 条）', 'messages', $html);
    }

    public function messageDelete(int $id): void
    {
        Database::delete('private_messages', 'id = ?', ['i' => $id]);
        $this->log('删除私信', 'ID:' . $id);
        $this->back('私信已删除');
    }

    public function messageBulk(): void
    {
        $ids = $this->ids();
        if (!$ids) {
            $this->back('请先选择私信', 'error');
        }
        $in = implode(',', array_map('intval', $ids));
        if ((string)Request::post('act', '') === 'delete') {
            Database::query('DELETE FROM ' . Database::table('private_messages') . ' WHERE id IN (' . $in . ')');
        }
        $this->log('私信批量删除', $in);
        $this->back('操作完成');
    }

    /* ================== 辅助 ================== */
    public static function groupOptions(): array
    {
        $opts = [];
        foreach (User::groups() as $g) {
            $opts[(int)$g['id']] = (string)$g['name'];
        }
        return $opts;
    }

    public static function levelOptions(): array
    {
        $opts = [];
        foreach (User::levels() as $l) {
            $opts[(int)$l['id']] = (string)$l['name'];
        }
        return $opts;
    }

    public static function certOptions(): array
    {
        $opts = [];
        foreach (User::certs() as $c) {
            $opts[(int)$c['id']] = (string)$c['name'];
        }
        return $opts;
    }

    public static function currencyTypes(): array
    {
        return [
            'thread' => '发帖奖励',
            'post' => '回复奖励',
            'checkin' => '签到奖励',
            'register' => '注册奖励',
            'like' => '点赞奖励',
            'buy' => '附件购买',
            'admin' => '管理员调整',
            'other' => '其它',
        ];
    }
}
