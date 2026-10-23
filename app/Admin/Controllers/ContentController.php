<?php

declare(strict_types=1);

namespace App\Admin\Controllers;

use App\Admin\AdminUI;
use App\Admin\BaseAdmin;
use App\Core\Database;
use App\Core\Request;
use App\Core\Security;
use App\Core\Settings;
use App\Models\Forum;
use App\Models\Post;
use App\Models\Thread;
use App\Models\User;
use App\Services\Spotlight;

/**
 * 内容管理：板块 / 主题 / 回复
 */
class ContentController extends BaseAdmin
{
    /* ======================================================================
     * 板块管理
     * ==================================================================== */
    public function forums(): string
    {
        $list = Database::fetchAll(
            'SELECT * FROM ' . Database::table('forums') . ' ORDER BY sort_order ASC, id ASC'
        );
        $sorted = [];
        foreach ($list as $f) {
            if ((int)$f['parent_id'] === 0) {
                $sorted[] = $f;
                foreach ($list as $c) {
                    if ((int)$c['parent_id'] === (int)$f['id']) {
                        $sorted[] = $c;
                    }
                }
            }
        }
        if (!$sorted) {
            $sorted = $list;
        }

        // 批量移动目标下拉
        $moveOpts = Forum::options(false);
        $moveHtml = '<select class="adm-input" style="width:auto" name="target_id">';
        foreach ($moveOpts as $k => $v) {
            $moveHtml .= '<option value="' . (int)$k . '">' . h($v) . '</option>';
        }
        $moveHtml .= '</select>';

        $html = '<div class="adm-alert info">提示：分区（type=1）用于归类，板块（type=2）才能发帖；支持无限层级（板块下也能再建分区或板块）。删除前请先移动或删除其下级。</div>';
        // 板块页顶部聚合设置（四个格子）
        $spotItems = Spotlight::items();
        $spotData = ['forum_spotlight_enable' => (int)Settings::get('forum_spotlight_enable', 1)];
        for ($i = 1; $i <= 4; $i++) {
            $spotData['spotlight_' . $i] = $spotItems[$i - 1]['t'] ?? 'none';
        }
        $spotOpts = ['none' => '不显示'] + Spotlight::types();
        $spotFields = [
            ['name' => 'forum_spotlight_enable', 'label' => '显示聚合', 'type' => 'switch',
                'tip' => '关闭后板块页只显示板块列表，不显示顶部聚合。'],
        ];
        for ($i = 1; $i <= 4; $i++) {
            $spotFields[] = ['name' => 'spotlight_' . $i, 'label' => '格子 ' . $i, 'type' => 'select', 'options' => $spotOpts];
        }
        $html .= '<div class="adm-panel"><div class="adm-panel-head">板块页顶部聚合</div><div class="adm-panel-body">'
            . AdminUI::form([
                'action' => admin_url('forums/spotlight'),
                'data' => $spotData,
                'submit' => '保存聚合设置',
                'fields' => $spotFields,
            ]) . '</div></div>';
        $html .= AdminUI::table([
            'primary' => 'id',
            'rows' => $sorted,
            'bulk_action' => admin_url('forums/bulk'),
            'bulk_extra' => $moveHtml,
            'bulk' => [
                ['label' => '移动到 →', 'value' => 'move', 'class' => 'primary'],
                ['label' => '显示', 'value' => 'show', 'class' => 'success'],
                ['label' => '隐藏', 'value' => 'hide', 'class' => 'warn'],
                ['label' => '批量删除', 'value' => 'delete', 'class' => 'danger', 'confirm' => '确定删除选中板块？该操作不可恢复！'],
            ],
            'columns' => [
                ['key' => 'id', 'label' => 'ID', 'width' => '60px'],
                ['key' => 'name', 'label' => '名称', 'render' => function ($r) {
                    $isChild = (int)$r['parent_id'] > 0;
                    $prefix = $isChild ? '　└ ' : '';
                    $indent = $isChild ? '<span class="adm-tree-indent"></span>' : '';
                    $color = (string)$r['color'];
                    return $indent . $prefix . '<span class="adm-color-dot" style="background:' . h($color) . '"></span>'
                        . '<a href="' . h(admin_url('forums/edit/' . (int)$r['id'])) . '">' . h((string)$r['name']) . '</a>'
                        . ((int)$r['visible'] === 0 ? ' <span class="adm-tag gray">隐藏</span>' : '');
                }],
                ['key' => 'slug', 'label' => '标识', 'width' => '130px'],
                ['key' => 'type', 'label' => '类型', 'width' => '80px', 'render' => fn($r) => (int)$r['type'] === 1
                    ? '<span class="adm-tag blue">分区</span>' : '<span class="adm-tag green">板块</span>'],
                ['key' => 'threads', 'label' => '主题', 'width' => '80px'],
                ['key' => 'posts', 'label' => '回复', 'width' => '80px'],
                ['key' => 'sort_order', 'label' => '排序', 'width' => '170px', 'render' => function ($r) {
                    $id = (int)$r['id'];
                    $url = h(admin_url('forums/sort'));
                    return '<div class="adm-row-actions" style="justify-content:flex-start">'
                        . '<span style="min-width:18px;margin-right:6px;text-align:center;color:var(--adm-muted);font-size:12px">' . (int)$r['sort_order'] . '</span>'
                        . '<button type="button" class="adm-link" data-forum-sort data-dir="up" data-id="' . $id . '" data-url="' . $url . '">↑ 上移</button>'
                        . '<button type="button" class="adm-link" data-forum-sort data-dir="down" data-id="' . $id . '" data-url="' . $url . '">↓ 下移</button>'
                        . '</div>';
                }],
            ],
            'actions' => [
                ['label' => '编辑', 'url' => admin_url('forums/edit/{id}')],
                ['label' => '清空', 'url' => admin_url('forums/clear/{id}'), 'type' => 'post', 'class' => 'danger', 'confirm' => '确定清空该板块下所有主题？'],
                ['label' => '删除', 'url' => admin_url('forums/delete/{id}'), 'type' => 'post', 'class' => 'danger', 'confirm' => '确定删除该板块？'],
            ],
        ]);

        return $this->pageView('板块管理', 'forums', $html, [
            ['label' => '+ 新增板块', 'url' => admin_url('forums/create'), 'class' => 'primary'],
            ['label' => '重建统计', 'url' => admin_url('forums/resync'), 'type' => 'post', 'confirm' => '重新统计各板块主题/回复数？'],
        ]);
    }

    /**
     * 保存板块页顶部聚合设置（4 个格子）
     */
    public function forumSpotlightSave(): void
    {
        $types = Spotlight::types();
        $items = [];
        for ($i = 1; $i <= 4; $i++) {
            $t = (string)Request::post('spotlight_' . $i, 'none');
            if (isset($types[$t])) {
                $items[] = ['t' => $t, 'title' => $types[$t]];
            }
        }
        Settings::set('forum_spotlight_enable', Request::bool('forum_spotlight_enable') ? 1 : 0);
        Settings::set('forum_spotlight_items', json_encode($items, JSON_UNESCAPED_UNICODE));
        $this->log('保存板块页聚合', count($items) . ' 个格子');
        $this->back('板块页聚合设置已保存');
    }

    /**
     * 板块排序：在同类（同一父级）内上移 / 下移，重新编号避免 sort_order 重复
     */
    public function forumSort(): void
    {
        $id = Request::int('id');
        $dir = (string)Request::post('dir', 'up') === 'up' ? 'up' : 'down';
        $row = Database::fetch('SELECT id, parent_id FROM ' . Database::table('forums') . ' WHERE id = ?', [$id]);
        if (!$row) {
            $this->json(['ok' => false, 'msg' => '板块不存在']);
        }
        $rows = Database::fetchAll(
            'SELECT id FROM ' . Database::table('forums') . ' WHERE parent_id = ? ORDER BY sort_order ASC, id ASC',
            [(int)$row['parent_id']]
        );
        $ids = array_map('intval', array_column($rows, 'id'));
        $idx = array_search($id, $ids, true);
        if ($idx === false) {
            $this->json(['ok' => false, 'msg' => '板块不存在']);
        }
        $swap = $dir === 'up' ? $idx - 1 : $idx + 1;
        if ($swap < 0 || $swap >= count($ids)) {
            $this->json(['ok' => true, 'msg' => $dir === 'up' ? '已经在最上面了' : '已经在最下面了']);
        }
        $tmp = $ids[$idx];
        $ids[$idx] = $ids[$swap];
        $ids[$swap] = $tmp;
        foreach ($ids as $i => $sid) {
            Database::update('forums', ['sort_order' => $i + 1], 'id = ?', [$sid]);
        }
        $this->log('调整板块排序', 'ID:' . $id . ' ' . $dir);
        $this->json(['ok' => true, 'msg' => '排序已更新']);
    }

    public function forumForm(int $id = 0): string
    {
        $data = $id > 0 ? Forum::find($id) : null;
        if ($id > 0 && !$data) {
            return $this->pageView('板块不存在', 'forums', '<div class="adm-alert error">板块不存在</div>');
        }
        // 上级可选任意论坛（支持无限层级），并排除自身及其后代
        $parentOpts = Forum::options(false);
        if ($id > 0) {
            foreach (array_keys($parentOpts) as $fid) {
                if ((int)$fid === $id || Forum::isDescendant((int)$fid, $id)) {
                    unset($parentOpts[$fid]);
                }
            }
        }
        $groupOpts = [];
        foreach (User::groups() as $g) {
            $groupOpts[(int)$g['id']] = (string)$g['name'];
        }

        $html = AdminUI::form([
            'action' => admin_url('forums/save'),
            'data' => $data ?? [],
            'hidden' => ['id' => $id],
            'submit' => '保存板块',
            'back' => admin_url('forums'),
            'fields' => [
                ['name' => 'name', 'label' => '板块名称', 'placeholder' => '如：技术交流'],
                ['name' => 'slug', 'label' => 'URL 标识', 'tip' => '留空自动生成，建议使用英文或拼音'],
                ['name' => 'type', 'label' => '类型', 'type' => 'select', 'options' => [1 => '分区（归类用）', 2 => '板块（可发帖）']],
                ['name' => 'parent_id', 'label' => '上级', 'type' => 'select', 'options' => $parentOpts,
                    'tip' => '可选择任意分区或板块作为上级，支持无限层级嵌套'],
                ['name' => 'description', 'label' => '板块简介', 'type' => 'textarea'],
                ['name' => 'icon', 'label' => '图标', 'tip' => '可填 emoji 或图标 class，如 💬'],
                ['name' => 'color', 'label' => '主题色', 'type' => 'color'],
                ['name' => 'cover', 'label' => '封面图地址', 'tip' => '留空则使用主题色背景'],
                ['name' => 'visible', 'label' => '是否显示', 'type' => 'switch'],
                ['name' => 'need_login', 'label' => '需登录访问', 'type' => 'switch'],
                ['name' => 'allow_post', 'label' => '允许发帖', 'type' => 'switch'],
                ['name' => 'allow_reply', 'label' => '允许回复', 'type' => 'switch'],
                ['name' => 'allow_anonymous', 'label' => '允许匿名', 'type' => 'switch'],
                ['name' => 'allow_attachment', 'label' => '允许附件', 'type' => 'switch'],
                ['name' => 'need_level', 'label' => '发帖等级要求', 'type' => 'number', 'tip' => '0 表示不限制'],
                ['name' => 'need_money', 'label' => '发帖货币要求', 'type' => 'number', 'step' => '0.01'],
                ['name' => 'view_groups', 'label' => '可访问用户组', 'type' => 'checkboxes', 'options' => $groupOpts, 'tip' => '全部不勾选表示不限制'],
                ['name' => 'post_groups', 'label' => '可发帖用户组', 'type' => 'checkboxes', 'options' => $groupOpts],
                ['name' => 'moderators', 'label' => '版主', 'tip' => '多个用户名用英文逗号分隔'],
                ['name' => 'sort_order', 'label' => '排序值', 'type' => 'number', 'tip' => '越小越靠前'],
            ],
        ]);

        return $this->pageView($id > 0 ? '编辑板块：' . (string)$data['name'] : '新增板块', 'forums', $html);
    }

    public function forumSave(): void
    {
        $id = Request::int('id');
        $name = $this->pure('name', 80);
        if ($name === '') {
            $this->back('板块名称不能为空', 'error');
        }
        $slug = trim((string)Request::post('slug', ''));
        if ($slug === '') {
            $slug = preg_match('/^[a-z0-9\-_]+$/i', $name)
                ? strtolower(str_replace([' ', '_'], '-', $name))
                : 'f' . date('md') . substr(md5($name . microtime(true)), 0, 4);
        }
        $data = [
            'name' => $name,
            'slug' => $slug,
            'type' => Request::int('type', 2) === 1 ? 1 : 2,
            'parent_id' => Request::int('parent_id'),
            'description' => $this->pure('description', 500),
            'icon' => $this->pure('icon', 255),
            'color' => $this->pure('color', 20),
            'cover' => $this->pure('cover', 255),
            'visible' => Request::bool('visible') ? 1 : 0,
            'need_login' => Request::bool('need_login') ? 1 : 0,
            'allow_post' => Request::bool('allow_post') ? 1 : 0,
            'allow_reply' => Request::bool('allow_reply') ? 1 : 0,
            'allow_anonymous' => Request::bool('allow_anonymous') ? 1 : 0,
            'allow_attachment' => Request::bool('allow_attachment') ? 1 : 0,
            'need_level' => Request::int('need_level'),
            'need_money' => (float)Request::input('need_money', 0),
            'view_groups' => implode(',', array_map('intval', (array)Request::post('view_groups', []))),
            'post_groups' => implode(',', array_map('intval', (array)Request::post('post_groups', []))),
            'moderators' => $this->pure('moderators', 255),
            'sort_order' => Request::int('sort_order'),
        ];

        if ($id > 0) {
            Forum::update($id, $data);
            $this->log('编辑板块', $name);
        } else {
            $id = Forum::create($data);
            $this->log('新增板块', $name);
        }
        $this->back('板块已保存');
    }

    public function forumDelete(int $id): void
    {
        $children = (int)Database::value(
            'SELECT COUNT(*) FROM ' . Database::table('forums') . ' WHERE parent_id = ?', [$id], 0
        );
        $threads = (int)Database::value(
            'SELECT COUNT(*) FROM ' . Database::table('threads') . ' WHERE forum_id = ?', [$id], 0
        );
        if ($children > 0) {
            $this->back('该分区下还有 ' . $children . ' 个板块，请先处理', 'error');
        }
        if ($threads > 0) {
            $this->back('该板块下还有 ' . $threads . ' 个主题，请先清空或移动', 'error');
        }
        Forum::delete($id);
        $this->log('删除板块', 'ID:' . $id);
        $this->back('板块已删除');
    }

    public function forumClear(int $id): void
    {
        Database::delete('threads', 'forum_id = ?', ['f' => $id]);
        Database::delete('posts', 'thread_id NOT IN (SELECT id FROM ' . Database::table('threads') . ')');
        Forum::syncStats($id);
        $this->log('清空板块', 'ID:' . $id);
        $this->back('板块主题已清空');
    }

    public function forumBulk(): void
    {
        $ids = $this->ids();
        $act = (string)Request::post('act', '');
        if (!$ids) {
            $this->back('请先选择板块', 'error');
        }
        $in = implode(',', array_map('intval', $ids));
        $target = Request::int('target_id');
        if ($act === 'move') {
            $moved = 0;
            foreach ($ids as $fid) {
                if ($fid === $target || Forum::isDescendant($target, $fid)) {
                    continue; // 不能移动到自己或自己的子级下
                }
                Database::update('forums', ['parent_id' => $target], 'id = ?', ['i' => $fid]);
                $moved++;
            }
            $this->log('批量移动板块', '至:' . $target . ' 共' . $moved);
            $this->back('已移动 ' . $moved . ' 个板块');
        }
        if ($act === 'show' || $act === 'hide') {
            Database::query(
                'UPDATE ' . Database::table('forums') . ' SET visible = ? WHERE id IN (' . $in . ')',
                [$act === 'show' ? 1 : 0]
            );
        } elseif ($act === 'delete') {
            foreach ($ids as $fid) {
                $child = (int)Database::value(
                    'SELECT COUNT(*) FROM ' . Database::table('forums') . ' WHERE parent_id = ?', [$fid], 0
                );
                if ($child > 0) {
                    continue; // 有下级则跳过，避免孤儿节点
                }
                Database::delete('forums', 'id = ?', ['i' => $fid]);
            }
        }
        $this->log('板块批量操作', $act . ':' . $in);
        $this->back('批量操作完成');
    }

    public function forumResync(): void
    {
        foreach (Database::fetchAll('SELECT id FROM ' . Database::table('forums')) as $f) {
            Forum::syncStats((int)$f['id']);
        }
        $this->log('重建板块统计');
        $this->back('统计已重建');
    }

    /* ======================================================================
     * 主题管理
     * ==================================================================== */
    public function threads(): string
    {
        $filters = [
            'forum_id' => Request::int('forum_id'),
            'status' => Request::input('status', ''),
            'kw' => (string)Request::get('kw', ''),
            'user' => (string)Request::get('user', ''),
        ];
        $where = '1=1';
        $params = [];
        if ($filters['forum_id'] > 0) {
            $where .= ' AND t.forum_id = ?';
            $params[] = $filters['forum_id'];
        }
        if ($filters['status'] !== '') {
            $where .= ' AND t.status = ?';
            $params[] = (int)$filters['status'];
        }
        if ($filters['kw'] !== '') {
            $where .= ' AND t.title LIKE ?';
            $params[] = '%' . $filters['kw'] . '%';
        }
        if ($filters['user'] !== '') {
            $where .= ' AND t.username LIKE ?';
            $params[] = '%' . $filters['user'] . '%';
        }

        $t = Database::table('threads');
        $f = Database::table('forums');
        $p = $this->queryPage(
            'SELECT t.*, f.name AS forum_name FROM ' . $t . ' t LEFT JOIN ' . $f . ' f ON f.id = t.forum_id
             WHERE ' . $where . ' ORDER BY t.id DESC',
            'SELECT COUNT(*) FROM ' . $t . ' t WHERE ' . $where,
            $params,
            20,
            $this->baseUrl('threads', $filters)
        );

        $forumOpts = [];
        foreach (Forum::all() as $fo) {
            $forumOpts[(int)$fo['id']] = (string)$fo['name'];
        }
        $marks = [];
        foreach (Thread::marks() as $m) {
            $marks[(int)$m['id']] = $m;
        }

        $html = AdminUI::filter([
            'action' => admin_url('threads'),
            'fields' => [
                ['name' => 'forum_id', 'label' => '全部板块', 'type' => 'select', 'options' => $forumOpts, 'value' => $filters['forum_id']],
                ['name' => 'status', 'label' => '全部状态', 'type' => 'select', 'options' => ['1' => '正常', '0' => '待审核', '2' => '回收站'], 'value' => $filters['status']],
                ['name' => 'kw', 'label' => '标题关键词', 'value' => $filters['kw']],
                ['name' => 'user', 'label' => '作者', 'value' => $filters['user']],
            ],
        ]);

        $html .= AdminUI::table([
            'primary' => 'id',
            'rows' => $p['items'],
            'bulk_action' => admin_url('threads/bulk'),
            'bulk' => [
                ['label' => '通过审核', 'value' => 'approve', 'class' => 'success'],
                ['label' => '加精华', 'value' => 'essence', 'class' => 'primary'],
                ['label' => '取消精华', 'value' => 'unessence'],
                ['label' => '全局置顶', 'value' => 'top'],
                ['label' => '取消置顶', 'value' => 'untop'],
                ['label' => '移入回收站', 'value' => 'trash', 'class' => 'warn'],
                ['label' => '彻底删除', 'value' => 'delete', 'class' => 'danger', 'confirm' => '确定彻底删除选中主题？不可恢复！'],
            ],
            'columns' => [
                ['key' => 'id', 'label' => 'ID', 'width' => '60px'],
                ['key' => 'title', 'label' => '标题', 'render' => function ($r) use ($marks) {
                    $style = (string)$r['title_color'] !== '' ? ' style="color:' . h((string)$r['title_color']) . '"' : '';
                    $title = '<span' . $style . '>' . ((int)$r['title_bold'] ? '<b>' . h((string)$r['title']) . '</b>' : h((string)$r['title'])) . '</span>';
                    $tags = '';
                    if ((int)$r['is_top'] === 1) {
                        $tags .= ' <span class="adm-tag red">' . ((int)$r['top_scope'] === 1 ? '全局置顶' : '置顶') . '</span>';
                    }
                    if ((int)$r['is_essence'] === 1) {
                        $tags .= ' <span class="adm-tag orange">精华</span>';
                    }
                    if ((int)$r['is_closed'] === 1) {
                        $tags .= ' <span class="adm-tag gray">关闭</span>';
                    }
                    if (isset($marks[(int)$r['mark_id']])) {
                        $m = $marks[(int)$r['mark_id']];
                        $tags .= ' <span class="adm-tag" style="background:' . h((string)$m['color']) . '22;color:' . h((string)$m['color']) . '">'
                            . h((string)$m['name']) . '</span>';
                    }
                    return '<a href="' . h(url('thread/' . (int)$r['id'])) . '" target="_blank">' . $title . '</a>' . $tags;
                }],
                ['key' => 'forum_name', 'label' => '板块', 'width' => '110px'],
                ['key' => 'username', 'label' => '作者', 'width' => '100px'],
                ['key' => 'replies', 'label' => '回复', 'width' => '60px'],
                ['key' => 'views', 'label' => '浏览', 'width' => '60px'],
                ['key' => 'status', 'label' => '状态', 'width' => '80px', 'render' => function ($r) {
                    return match ((int)$r['status']) {
                        1 => '<span class="adm-tag green">正常</span>',
                        0 => '<span class="adm-tag orange">待审核</span>',
                        default => '<span class="adm-tag red">回收站</span>',
                    };
                }],
                ['key' => 'created_at', 'label' => '发布时间', 'width' => '130px', 'render' => fn($r) => format_date($r['created_at'])],
            ],
            'actions' => [
                ['label' => '编辑', 'url' => admin_url('threads/edit/{id}')],
                ['label' => '删除', 'url' => admin_url('threads/act/{id}/trash'), 'type' => 'post', 'class' => 'danger', 'confirm' => '确定移入回收站？'],
            ],
        ]);
        $html .= '<div class="adm-pager">' . $p['pager'] . '</div>';

        return $this->pageView('主题管理（共 ' . $p['total'] . ' 条）', 'threads', $html, [
            ['label' => '待审核 ' . (int)Database::count('threads', 'status = 0'), 'url' => admin_url('threads?status=0'), 'class' => 'warn'],
        ]);
    }

    public function threadEdit(int $id): string
    {
        $thread = Thread::find($id);
        if (!$thread) {
            return $this->pageView('主题不存在', 'threads', '<div class="adm-alert error">主题不存在</div>');
        }
        $first = Database::fetch(
            'SELECT * FROM ' . Database::table('posts') . ' WHERE thread_id = ? AND is_first = 1 LIMIT 1', [$id]
        );
        $forumOpts = [];
        foreach (Forum::all() as $fo) {
            $forumOpts[(int)$fo['id']] = (string)$fo['name'];
        }
        $markOpts = [0 => '（无）'];
        foreach (Thread::marks() as $m) {
            $markOpts[(int)$m['id']] = (string)$m['name'];
        }

        $html = AdminUI::form([
            'action' => admin_url('threads/save'),
            'data' => array_merge($thread, ['content' => (string)($first['content'] ?? '')]),
            'hidden' => ['id' => $id],
            'submit' => '保存主题',
            'back' => admin_url('threads'),
            'fields' => [
                ['name' => 'title', 'label' => '标题'],
                ['name' => 'title_color', 'label' => '标题颜色', 'type' => 'color'],
                ['name' => 'title_bold', 'label' => '标题加粗', 'type' => 'switch'],
                ['name' => 'forum_id', 'label' => '所属板块', 'type' => 'select', 'options' => $forumOpts],
                ['name' => 'mark_id', 'label' => '帖子标记', 'type' => 'select', 'options' => $markOpts],
                ['name' => 'status', 'label' => '状态', 'type' => 'select', 'options' => [1 => '正常', 0 => '待审核', 2 => '回收站']],
                ['name' => 'is_top', 'label' => '置顶', 'type' => 'switch'],
                ['name' => 'top_scope', 'label' => '全局置顶', 'type' => 'switch'],
                ['name' => 'is_essence', 'label' => '精华', 'type' => 'switch'],
                ['name' => 'is_closed', 'label' => '关闭回复', 'type' => 'switch'],
                ['name' => 'is_digest', 'label' => '首页推荐', 'type' => 'switch'],
                ['name' => 'attachment_fee', 'label' => '附件收费', 'type' => 'number', 'step' => '0.01'],
                ['name' => 'created_at', 'label' => '发布时间', 'tip' => '格式：YYYY-MM-DD HH:MM:SS，修改后前台显示的发布时间随之变化'],
                ['name' => 'edited_at', 'label' => '最后编辑时间', 'tip' => '留空表示未编辑过；填写后帖子正文上方会显示"XXX 编辑于 …"'],
                ['name' => 'content', 'label' => '正文内容', 'type' => 'editor'],
            ],
        ]);

        return $this->pageView('编辑主题 #' . $id, 'threads', $html, [
            ['label' => '前台查看', 'url' => url('thread/' . $id)],
        ]);
    }

    public function threadSave(): void
    {
        $id = Request::int('id');
        $thread = Thread::find($id);
        if (!$thread) {
            $this->back('主题不存在', 'error');
        }
        $content = Security::cleanHtml(Request::raw('content'));
        Thread::update($id, [
            'title' => $this->pure('title', 200),
            'title_color' => $this->pure('title_color', 20),
            'title_bold' => Request::bool('title_bold') ? 1 : 0,
            'forum_id' => Request::int('forum_id'),
            'mark_id' => Request::int('mark_id'),
            'status' => Request::int('status', 1),
            'is_top' => Request::bool('is_top') ? 1 : 0,
            'top_scope' => Request::bool('top_scope') ? 1 : 0,
            'is_essence' => Request::bool('is_essence') ? 1 : 0,
            'is_closed' => Request::bool('is_closed') ? 1 : 0,
            'is_digest' => Request::bool('is_digest') ? 1 : 0,
            'attachment_fee' => (float)Request::input('attachment_fee', 0),
        ]);
        Database::update(
            'posts',
            ['content' => $content, 'content_text' => \App\Services\Content::toText($content)],
            'thread_id = ? AND is_first = 1',
            ['t' => $id]
        );
        // 发布时间 / 编辑时间（后台可修正）
        $createdAt = trim((string)Request::post('created_at', ''));
        if ($createdAt !== '' && preg_match('/^\d{4}-\d{2}-\d{2}( \d{2}:\d{2}(:\d{2})?)?$/', $createdAt)) {
            if (strlen($createdAt) === 10) { $createdAt .= ' 00:00:00'; }
            if (strlen($createdAt) === 16) { $createdAt .= ':00'; }
            Database::update('threads', ['created_at' => $createdAt], 'id = ?', ['i' => $id]);
        }
        $editedAt = trim((string)Request::post('edited_at', ''));
        if ($editedAt !== '' && preg_match('/^\d{4}-\d{2}-\d{2}( \d{2}:\d{2}(:\d{2})?)?$/', $editedAt)) {
            if (strlen($editedAt) === 10) { $editedAt .= ' 00:00:00'; }
            if (strlen($editedAt) === 16) { $editedAt .= ':00'; }
            Database::update('threads', [
                'edited_at' => $editedAt,
                'edited_by' => (int)(\App\Core\Auth::id() ?? 0),
            ], 'id = ?', ['i' => $id]);
        }
        if ((int)$thread['forum_id'] !== Request::int('forum_id')) {
            Forum::syncStats((int)$thread['forum_id']);
            Forum::syncStats(Request::int('forum_id'));
        }
        $this->log('编辑主题', 'ID:' . $id);
        $this->back('主题已保存');
    }

    public function threadAct(int $id, string $act): void
    {
        $thread = Thread::find($id);
        if (!$thread) {
            $this->back('主题不存在', 'error');
        }
        switch ($act) {
            case 'approve':
                Thread::update($id, ['status' => 1]);
                break;
            case 'essence':
                Thread::update($id, ['is_essence' => 1]);
                break;
            case 'unessence':
                Thread::update($id, ['is_essence' => 0]);
                break;
            case 'top':
                Thread::setTop($id, 1, 1);
                break;
            case 'untop':
                Thread::setTop($id, 0, 0);
                break;
            case 'close':
                Thread::update($id, ['is_closed' => 1]);
                break;
            case 'open':
                Thread::update($id, ['is_closed' => 0]);
                break;
            case 'trash':
                Thread::softDelete($id);
                break;
            case 'restore':
                Thread::update($id, ['status' => 1]);
                break;
            case 'delete':
                Database::delete('posts', 'thread_id = ?', ['t' => $id]);
                Thread::delete($id);
                break;
            default:
                $this->back('未知操作', 'error');
        }
        Forum::syncStats((int)$thread['forum_id']);
        $this->log('主题操作：' . $act, 'ID:' . $id);
        $this->back('操作成功');
    }

    public function threadBulk(): void
    {
        $ids = $this->ids();
        $act = (string)Request::post('act', '');
        if (!$ids) {
            $this->back('请先选择主题', 'error');
        }
        foreach ($ids as $id) {
            switch ($act) {
                case 'approve':
                    Thread::update($id, ['status' => 1]);
                    break;
                case 'essence':
                    Thread::update($id, ['is_essence' => 1]);
                    break;
                case 'unessence':
                    Thread::update($id, ['is_essence' => 0]);
                    break;
                case 'top':
                    Thread::setTop($id, 1, 1);
                    break;
                case 'untop':
                    Thread::setTop($id, 0, 0);
                    break;
                case 'trash':
                    Thread::softDelete($id);
                    break;
                case 'delete':
                    Database::delete('posts', 'thread_id = ?', ['t' => $id]);
                    Thread::delete($id);
                    break;
            }
        }
        $this->log('主题批量操作', $act . ':' . implode(',', $ids));
        $this->back('批量操作完成');
    }

    /* ======================================================================
     * 回复管理
     * ==================================================================== */
    public function posts(): string
    {
        $filters = [
            'kw' => (string)Request::get('kw', ''),
            'user' => (string)Request::get('user', ''),
            'thread_id' => Request::int('thread_id'),
        ];
        $where = '1=1';
        $params = [];
        if ($filters['kw'] !== '') {
            $where .= ' AND p.content_text LIKE ?';
            $params[] = '%' . $filters['kw'] . '%';
        }
        if ($filters['user'] !== '') {
            $where .= ' AND p.username LIKE ?';
            $params[] = '%' . $filters['user'] . '%';
        }
        if ($filters['thread_id'] > 0) {
            $where .= ' AND p.thread_id = ?';
            $params[] = $filters['thread_id'];
        }

        $pt = Database::table('posts');
        $tt = Database::table('threads');
        $p = $this->queryPage(
            'SELECT p.*, t.title AS thread_title FROM ' . $pt . ' p
             LEFT JOIN ' . $tt . ' t ON t.id = p.thread_id
             WHERE ' . $where . ' ORDER BY p.id DESC',
            'SELECT COUNT(*) FROM ' . $pt . ' p WHERE ' . $where,
            $params,
            20,
            $this->baseUrl('posts', $filters)
        );

        $html = AdminUI::filter([
            'action' => admin_url('posts'),
            'fields' => [
                ['name' => 'kw', 'label' => '内容关键词', 'value' => $filters['kw']],
                ['name' => 'user', 'label' => '作者', 'value' => $filters['user']],
                ['name' => 'thread_id', 'label' => '主题ID', 'value' => $filters['thread_id']],
            ],
        ]);
        $html .= AdminUI::table([
            'primary' => 'id',
            'rows' => $p['items'],
            'bulk_action' => admin_url('posts/bulk'),
            'bulk' => [
                ['label' => '隐藏', 'value' => 'hide', 'class' => 'warn'],
                ['label' => '恢复', 'value' => 'show'],
                ['label' => '彻底删除', 'value' => 'delete', 'class' => 'danger', 'confirm' => '确定彻底删除选中回复？'],
            ],
            'columns' => [
                ['key' => 'id', 'label' => 'ID', 'width' => '60px'],
                ['key' => 'content_text', 'label' => '内容', 'render' => fn($r) => h(str_cut((string)$r['content_text'], 60))],
                ['key' => 'username', 'label' => '作者', 'width' => '100px'],
                ['key' => 'thread_title', 'label' => '所属主题', 'width' => '200px', 'render' => fn($r) => (int)$r['thread_id'] > 0
                    ? '<a href="' . h(url('thread/' . (int)$r['thread_id'])) . '" target="_blank">' . h(str_cut((string)$r['thread_title'], 24)) . '</a>'
                    : '<span class="muted">-</span>'],
                ['key' => 'floor', 'label' => '楼层', 'width' => '60px'],
                ['key' => 'status', 'label' => '状态', 'width' => '80px', 'render' => fn($r) => (int)$r['status'] === 1
                    ? '<span class="adm-tag green">正常</span>' : '<span class="adm-tag gray">隐藏</span>'],
                ['key' => 'created_at', 'label' => '时间', 'width' => '130px', 'render' => fn($r) => format_date($r['created_at'])],
            ],
            'actions' => [
                ['label' => '删除', 'url' => admin_url('posts/delete/{id}'), 'type' => 'post', 'class' => 'danger', 'confirm' => '确定删除该回复？'],
            ],
        ]);
        $html .= '<div class="adm-pager">' . $p['pager'] . '</div>';

        return $this->pageView('回复管理（共 ' . $p['total'] . ' 条）', 'posts', $html);
    }

    public function postDelete(int $id): void
    {
        $post = Post::find($id);
        if ($post) {
            Post::delete($id);
            Thread::syncReplies((int)$post['thread_id']);
            $this->log('删除回复', 'ID:' . $id);
        }
        $this->back('回复已删除');
    }

    public function postBulk(): void
    {
        $ids = $this->ids();
        $act = (string)Request::post('act', '');
        if (!$ids) {
            $this->back('请先选择回复', 'error');
        }
        foreach ($ids as $id) {
            if ($act === 'delete') {
                $post = Post::find($id);
                Post::delete($id);
                if ($post) {
                    Thread::syncReplies((int)$post['thread_id']);
                }
            } elseif ($act === 'hide' || $act === 'show') {
                Database::update('posts', ['status' => $act === 'show' ? 1 : 0], 'id = ?', ['i' => $id]);
            }
        }
        $this->log('回复批量操作', $act . ':' . implode(',', $ids));
        $this->back('批量操作完成');
    }
}
