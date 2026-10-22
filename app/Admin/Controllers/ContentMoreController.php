<?php

declare(strict_types=1);

namespace App\Admin\Controllers;

use App\Admin\AdminUI;
use App\Admin\BaseAdmin;
use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Security;
use App\Models\Attachment;
use App\Models\Report;
use App\Models\Say;
use App\Models\Tag;
use App\Models\Thread;
use App\Models\User;

/**
 * 内容管理：标签 / 标记 / 说说 / 推荐 / 附件 / 举报
 */
class ContentMoreController extends BaseAdmin
{
    /* ================== 标签 ================== */
    public function tags(): string
    {
        $kw = (string)Request::get('kw', '');
        $where = '1=1';
        $params = [];
        if ($kw !== '') {
            $where .= ' AND (name LIKE ? OR alias LIKE ?)';
            $params[] = '%' . $kw . '%';
            $params[] = '%' . $kw . '%';
        }
        $t = $this->queryPage(
            'SELECT * FROM ' . Database::table('tags') . ' WHERE ' . $where . ' ORDER BY threads DESC, id DESC',
            'SELECT COUNT(*) FROM ' . Database::table('tags') . ' WHERE ' . $where,
            $params,
            20,
            $this->baseUrl('tags', ['kw' => $kw])
        );

        $html = AdminUI::filter([
            'action' => admin_url('tags'),
            'fields' => [['name' => 'kw', 'label' => '标签关键词', 'value' => $kw]],
        ]);
        $html .= AdminUI::table([
            'primary' => 'id',
            'rows' => $t['items'],
            'bulk_action' => admin_url('tags/bulk'),
            'bulk' => [
                ['label' => '通过', 'value' => 'pass', 'class' => 'success'],
                ['label' => '删除', 'value' => 'delete', 'class' => 'danger', 'confirm' => '确定删除选中标签？'],
            ],
            'columns' => [
                ['key' => 'id', 'label' => 'ID', 'width' => '60px'],
                ['key' => 'name', 'label' => '标签', 'render' => fn($r) => '<span class="adm-tag" style="background:' . h((string)$r['color']) . '22;color:' . h((string)$r['color']) . '">'
                    . h((string)$r['name']) . '</span>'],
                ['key' => 'alias', 'label' => '别名', 'width' => '160px'],
                ['key' => 'description', 'label' => '描述', 'render' => fn($r) => h(str_cut((string)$r['description'], 40))],
                ['key' => 'threads', 'label' => '主题数', 'width' => '80px'],
                ['key' => 'hot', 'label' => '热度', 'width' => '70px'],
                ['key' => 'status', 'label' => '状态', 'width' => '80px', 'render' => fn($r) => (int)$r['status'] === 1
                    ? '<span class="adm-tag green">通过</span>' : '<span class="adm-tag orange">待审</span>'],
            ],
            'actions' => [
                ['label' => '编辑', 'url' => admin_url('tags/edit/{id}')],
                ['label' => '删除', 'url' => admin_url('tags/delete/{id}'), 'type' => 'post', 'class' => 'danger', 'confirm' => '确定删除该标签？'],
            ],
        ]);
        $html .= '<div class="adm-pager">' . $t['pager'] . '</div>';

        $tagOpts = [];
        foreach (Database::fetchAll('SELECT id, name FROM ' . Database::table('tags') . ' ORDER BY id DESC LIMIT 200') as $tg) {
            $tagOpts[(int)$tg['id']] = (string)$tg['name'];
        }
        $html .= '<div class="adm-panel"><div class="adm-panel-head">新增标签</div><div class="adm-panel-body">'
            . AdminUI::form([
                'action' => admin_url('tags/save'),
                'submit' => '新增',
                'fields' => [
                    ['name' => 'name', 'label' => '标签名', 'placeholder' => '多个可用逗号分隔批量新增'],
                    ['name' => 'alias', 'label' => '别名', 'tip' => '多个别名用逗号分隔'],
                    ['name' => 'color', 'label' => '颜色', 'type' => 'color', 'value' => '#4D698E'],
                    ['name' => 'description', 'label' => '描述', 'type' => 'textarea'],
                    ['name' => 'status', 'label' => '状态', 'type' => 'select', 'options' => [1 => '通过', 0 => '待审']],
                ],
            ]) . '</div></div>';

        $html .= '<div class="adm-panel"><div class="adm-panel-head">标签合并</div><div class="adm-panel-body">'
            . AdminUI::form([
                'action' => admin_url('tags/merge'),
                'submit' => '合并',
                'fields' => [
                    ['name' => 'source_id', 'label' => '源标签', 'type' => 'select', 'options' => $tagOpts, 'tip' => '将被合并并删除'],
                    ['name' => 'target_id', 'label' => '目标标签', 'type' => 'select', 'options' => $tagOpts],
                ],
            ]) . '</div></div>';

        return $this->pageView('标签管理（共 ' . $t['total'] . ' 个）', 'tags', $html);
    }

    public function tagEdit(int $id): string
    {
        $tag = Tag::find($id);
        if (!$tag) {
            return $this->pageView('标签不存在', 'tags', '<div class="adm-alert error">标签不存在</div>');
        }
        $html = AdminUI::form([
            'action' => admin_url('tags/save'),
            'data' => $tag,
            'hidden' => ['id' => $id],
            'submit' => '保存',
            'back' => admin_url('tags'),
            'fields' => [
                ['name' => 'name', 'label' => '标签名'],
                ['name' => 'alias', 'label' => '别名'],
                ['name' => 'slug', 'label' => '标识'],
                ['name' => 'color', 'label' => '颜色', 'type' => 'color'],
                ['name' => 'icon', 'label' => '图标'],
                ['name' => 'description', 'label' => '描述', 'type' => 'textarea'],
                ['name' => 'status', 'label' => '状态', 'type' => 'select', 'options' => [1 => '通过', 0 => '待审']],
            ],
        ]);
        return $this->pageView('编辑标签：' . (string)$tag['name'], 'tags', $html);
    }

    public function tagSave(): void
    {
        $id = Request::int('id');
        $name = $this->pure('name', 50);
        if ($name === '') {
            $this->back('标签名不能为空', 'error');
        }
        if ($id > 0) {
            Tag::update($id, [
                'name' => $name,
                'alias' => $this->pure('alias', 255),
                'slug' => $this->pure('slug', 80),
                'color' => $this->pure('color', 20),
                'icon' => $this->pure('icon', 255),
                'description' => $this->pure('description', 255),
                'status' => Request::int('status', 1),
            ]);
            $this->log('编辑标签', $name);
            $this->back('标签已保存');
        }
        $names = array_filter(array_map('trim', preg_split('/[,，]/u', $name)));
        foreach ($names as $n) {
            if (Tag::findByName($n)) {
                continue;
            }
            Tag::create($n, [
                'color' => $this->pure('color', 20),
                'alias' => $this->pure('alias', 255),
                'description' => $this->pure('description', 255),
                'status' => Request::int('status', 1),
            ]);
        }
        $this->log('新增标签', $name);
        $this->back('标签已新增');
    }

    public function tagDelete(int $id): void
    {
        Tag::delete($id);
        $this->log('删除标签', 'ID:' . $id);
        $this->back('标签已删除');
    }

    public function tagMerge(): void
    {
        $source = Request::int('source_id');
        $target = Request::int('target_id');
        if ($source <= 0 || $target <= 0 || $source === $target) {
            $this->back('请选择不同的源标签与目标标签', 'error');
        }
        Tag::merge($source, $target);
        $this->log('合并标签', $source . '→' . $target);
        $this->back('标签已合并');
    }

    public function tagBulk(): void
    {
        $ids = $this->ids();
        $act = (string)Request::post('act', '');
        if (!$ids) {
            $this->back('请先选择标签', 'error');
        }
        foreach ($ids as $id) {
            if ($act === 'delete') {
                Tag::delete($id);
            } elseif ($act === 'pass') {
                Tag::update($id, ['status' => 1]);
            }
        }
        $this->log('标签批量操作', $act);
        $this->back('操作完成');
    }

    /* ================== 帖子标记 ================== */
    public function marks(): string
    {
        $rows = Database::fetchAll('SELECT * FROM ' . Database::table('thread_marks') . ' ORDER BY sort_order ASC, id ASC');
        $groupOpts = [];
        foreach (User::groups() as $g) {
            $groupOpts[(int)$g['id']] = (string)$g['name'];
        }
        $html = AdminUI::table([
            'primary' => 'id',
            'rows' => $rows,
            'columns' => [
                ['key' => 'id', 'label' => 'ID', 'width' => '60px'],
                ['key' => 'name', 'label' => '标记名称', 'render' => fn($r) => '<span class="adm-tag" style="background:' . h((string)$r['color']) . '22;color:' . h((string)$r['color']) . '">'
                    . h((string)$r['name']) . '</span>'],
                ['key' => 'icon', 'label' => '图标', 'width' => '80px'],
                ['key' => 'groups', 'label' => '可用用户组', 'width' => '200px', 'render' => function ($r) use ($groupOpts) {
                    $ids = array_filter(array_map('intval', explode(',', (string)$r['groups'])));
                    if (!$ids) {
                        return '<span class="muted">不限</span>';
                    }
                    $out = [];
                    foreach ($ids as $i) {
                        $out[] = h($groupOpts[$i] ?? ('#' . $i));
                    }
                    return implode('、', $out);
                }],
                ['key' => 'sort_order', 'label' => '排序', 'width' => '80px'],
            ],
            'actions' => [
                ['label' => '编辑', 'url' => admin_url('marks/edit/{id}')],
                ['label' => '删除', 'url' => admin_url('marks/delete/{id}'), 'type' => 'post', 'class' => 'danger', 'confirm' => '确定删除该标记？'],
            ],
        ]);

        $edit = null;
        $editId = Request::int('edit');
        if ($editId > 0) {
            $edit = Database::fetch('SELECT * FROM ' . Database::table('thread_marks') . ' WHERE id = ?', [$editId]) ?: null;
        }
        $html .= '<div class="adm-panel"><div class="adm-panel-head">' . ($edit ? '编辑标记' : '新增标记') . '</div><div class="adm-panel-body">'
            . AdminUI::form([
                'action' => admin_url('marks/save'),
                'data' => $edit ?? [],
                'hidden' => ['id' => $edit['id'] ?? 0],
                'submit' => '保存',
                'fields' => [
                    ['name' => 'name', 'label' => '标记名称', 'placeholder' => '如：精华、公告、活动'],
                    ['name' => 'color', 'label' => '颜色', 'type' => 'color'],
                    ['name' => 'icon', 'label' => '图标', 'tip' => 'emoji 或图标 class'],
                    ['name' => 'groups', 'label' => '可用用户组', 'type' => 'checkboxes', 'options' => $groupOpts, 'tip' => '不勾选表示所有用户组可用'],
                    ['name' => 'sort_order', 'label' => '排序', 'type' => 'number'],
                ],
            ]) . '</div></div>';

        return $this->pageView('帖子标记', 'marks', $html, [
            ['label' => '+ 新增标记', 'url' => admin_url('marks'), 'class' => 'primary'],
        ]);
    }

    public function markSave(): void
    {
        $id = Request::int('id');
        $data = [
            'name' => $this->pure('name', 50),
            'color' => $this->pure('color', 20),
            'icon' => $this->pure('icon', 50),
            'groups' => implode(',', array_map('intval', (array)Request::post('groups', []))),
            'sort_order' => Request::int('sort_order'),
        ];
        if ($data['name'] === '') {
            $this->back('标记名称不能为空', 'error');
        }
        if ($id > 0) {
            Database::update('thread_marks', $data, 'id = ?', ['i' => $id]);
        } else {
            Database::insert('thread_marks', $data);
        }
        $this->log('保存帖子标记', $data['name']);
        $this->back('标记已保存');
    }

    public function markDelete(int $id): void
    {
        Database::update('threads', ['mark_id' => 0], 'mark_id = ?', ['m' => $id]);
        Database::delete('thread_marks', 'id = ?', ['i' => $id]);
        $this->log('删除帖子标记', 'ID:' . $id);
        $this->back('标记已删除');
    }

    /* ================== 说说 ================== */
    public function says(): string
    {
        $filters = ['status' => Request::input('status', ''), 'kw' => (string)Request::get('kw', '')];
        $where = '1=1';
        $params = [];
        if ($filters['status'] !== '') {
            $where .= ' AND s.status = ?';
            $params[] = (int)$filters['status'];
        }
        if ($filters['kw'] !== '') {
            $where .= ' AND s.content LIKE ?';
            $params[] = '%' . $filters['kw'] . '%';
        }
        $st = Database::table('says');
        $ut = Database::table('users');
        $p = $this->queryPage(
            'SELECT s.*, u.username FROM ' . $st . ' s LEFT JOIN ' . $ut . ' u ON u.id = s.user_id
             WHERE ' . $where . ' ORDER BY s.id DESC',
            'SELECT COUNT(*) FROM ' . $st . ' s WHERE ' . $where,
            $params,
            20,
            $this->baseUrl('says', $filters)
        );

        $html = AdminUI::filter([
            'action' => admin_url('says'),
            'fields' => [
                ['name' => 'status', 'label' => '全部状态', 'type' => 'select', 'options' => ['1' => '正常', '0' => '待审核'], 'value' => $filters['status']],
                ['name' => 'kw', 'label' => '内容关键词', 'value' => $filters['kw']],
            ],
        ]);
        $html .= AdminUI::table([
            'primary' => 'id',
            'rows' => $p['items'],
            'bulk_action' => admin_url('says/bulk'),
            'bulk' => [
                ['label' => '通过', 'value' => 'pass', 'class' => 'success'],
                ['label' => '隐藏', 'value' => 'hide', 'class' => 'warn'],
                ['label' => '删除', 'value' => 'delete', 'class' => 'danger', 'confirm' => '确定删除选中说说？'],
            ],
            'columns' => [
                ['key' => 'id', 'label' => 'ID', 'width' => '60px'],
                ['key' => 'content', 'label' => '内容', 'render' => function ($r) {
                    $imgs = json_decode((string)$r['images'], true);
                    $n = is_array($imgs) ? count($imgs) : 0;
                    return h(str_cut(strip_tags((string)$r['content']), 70)) . ($n > 0 ? ' <span class="adm-tag blue">' . $n . '图</span>' : '');
                }],
                ['key' => 'username', 'label' => '作者', 'width' => '110px'],
                ['key' => 'likes', 'label' => '点赞', 'width' => '70px'],
                ['key' => 'comments', 'label' => '评论', 'width' => '70px'],
                ['key' => 'status', 'label' => '状态', 'width' => '80px', 'render' => fn($r) => (int)$r['status'] === 1
                    ? '<span class="adm-tag green">正常</span>' : '<span class="adm-tag orange">待审</span>'],
                ['key' => 'created_at', 'label' => '时间', 'width' => '130px', 'render' => fn($r) => format_date($r['created_at'])],
            ],
            'actions' => [
                ['label' => '删除', 'url' => admin_url('says/delete/{id}'), 'type' => 'post', 'class' => 'danger', 'confirm' => '确定删除该说说？'],
            ],
        ]);
        $html .= '<div class="adm-pager">' . $p['pager'] . '</div>';

        return $this->pageView('说说管理（共 ' . $p['total'] . ' 条）', 'says', $html, [
            ['label' => '待审 ' . (int)Database::count('says', 'status = 0'), 'url' => admin_url('says?status=0'), 'class' => 'warn'],
        ]);
    }

    public function sayDelete(int $id): void
    {
        Say::delete($id);
        $this->log('删除说说', 'ID:' . $id);
        $this->back('说说已删除');
    }

    public function sayBulk(): void
    {
        $ids = $this->ids();
        $act = (string)Request::post('act', '');
        if (!$ids) {
            $this->back('请先选择说说', 'error');
        }
        $in = implode(',', array_map('intval', $ids));
        if ($act === 'pass') {
            Database::query('UPDATE ' . Database::table('says') . ' SET status = 1 WHERE id IN (' . $in . ')');
        } elseif ($act === 'hide') {
            Database::query('UPDATE ' . Database::table('says') . ' SET status = 0 WHERE id IN (' . $in . ')');
        } elseif ($act === 'delete') {
            Database::query('DELETE FROM ' . Database::table('says') . ' WHERE id IN (' . $in . ')');
            Database::query('DELETE FROM ' . Database::table('say_comments') . ' WHERE say_id IN (' . $in . ')');
        }
        $this->log('说说批量操作', $act);
        $this->back('操作完成');
    }

    /* ================== 推荐 / 避雷 ================== */
    public function recommends(): string
    {
        $kind = Request::int('kind', 1);
        if (!in_array($kind, [1, -1], true)) {
            $kind = 1;
        }
        $rt = Database::table('recommendations');
        $tt = Database::table('threads');
        $ut = Database::table('users');
        $p = $this->queryPage(
            'SELECT r.*, t.title, u.username FROM ' . $rt . ' r
             LEFT JOIN ' . $tt . ' t ON t.id = r.thread_id
             LEFT JOIN ' . $ut . ' u ON u.id = r.user_id
             WHERE r.kind = ? ORDER BY r.id DESC',
            'SELECT COUNT(*) FROM ' . $rt . ' r WHERE r.kind = ?',
            [$kind],
            20,
            $this->baseUrl('recommends', ['kind' => $kind])
        );

        $html = AdminUI::filter([
            'action' => admin_url('recommends'),
            'fields' => [
                ['name' => 'kind', 'label' => '类型', 'type' => 'select', 'options' => [1 => '推荐', -1 => '避雷'], 'value' => $kind],
            ],
        ]);
        $html .= AdminUI::table([
            'primary' => 'id',
            'rows' => $p['items'],
            'columns' => [
                ['key' => 'id', 'label' => 'ID', 'width' => '60px'],
                ['key' => 'title', 'label' => '主题', 'render' => fn($r) => '<a href="' . h(url('thread/' . (int)$r['thread_id'])) . '" target="_blank">'
                    . h(str_cut((string)($r['title'] ?? ('#' . (int)$r['thread_id'])), 40)) . '</a>'],
                ['key' => 'username', 'label' => '操作人', 'width' => '110px'],
                ['key' => 'kind', 'label' => '类型', 'width' => '80px', 'render' => fn($r) => (int)$r['kind'] === 1
                    ? '<span class="adm-tag green">推荐</span>' : '<span class="adm-tag red">避雷</span>'],
                ['key' => 'reason', 'label' => '理由', 'render' => fn($r) => h(str_cut((string)$r['reason'], 40))],
                ['key' => 'created_at', 'label' => '时间', 'width' => '130px', 'render' => fn($r) => format_date($r['created_at'])],
            ],
            'actions' => [
                ['label' => '删除', 'url' => admin_url('recommends/delete/{id}'), 'type' => 'post', 'class' => 'danger', 'confirm' => '确定删除该记录？'],
            ],
        ]);
        $html .= '<div class="adm-pager">' . $p['pager'] . '</div>';
        return $this->pageView('推荐 / 避雷管理（共 ' . $p['total'] . ' 条）', 'recommends', $html);
    }

    public function recommendDelete(int $id): void
    {
        $row = Database::fetch('SELECT * FROM ' . Database::table('recommendations') . ' WHERE id = ?', [$id]);
        Database::delete('recommendations', 'id = ?', ['i' => $id]);
        if ($row) {
            \App\Models\Recommend::sync((int)$row['thread_id']);
        }
        $this->log('删除推荐记录', 'ID:' . $id);
        $this->back('记录已删除');
    }

    /* ================== 附件库 ================== */
    public function attachments(): string
    {
        $filters = ['space' => (string)Request::get('space', ''), 'kw' => (string)Request::get('kw', '')];
        $where = '1=1';
        $params = [];
        if ($filters['space'] !== '') {
            $where .= ' AND a.space = ?';
            $params[] = $filters['space'];
        }
        if ($filters['kw'] !== '') {
            $where .= ' AND a.name LIKE ?';
            $params[] = '%' . $filters['kw'] . '%';
        }
        $at = Database::table('attachments');
        $ut = Database::table('users');
        $p = $this->queryPage(
            'SELECT a.*, u.username FROM ' . $at . ' a LEFT JOIN ' . $ut . ' u ON u.id = a.user_id
             WHERE ' . $where . ' ORDER BY a.id DESC',
            'SELECT COUNT(*) FROM ' . $at . ' a WHERE ' . $where,
            $params,
            20,
            $this->baseUrl('attachments', $filters)
        );
        $total = (int)Database::value('SELECT COUNT(*) FROM ' . Database::table('attachments'), [], 0);
        $size = (float)Database::value('SELECT SUM(size) FROM ' . Database::table('attachments'), [], 0);

        $html = '<div class="adm-stats">'
            . '<div class="adm-stat"><div class="adm-stat__label">附件总数</div><div class="adm-stat__value">' . $total . '</div></div>'
            . '<div class="adm-stat"><div class="adm-stat__label">占用空间</div><div class="adm-stat__value">' . format_size($size) . '</div></div>'
            . '</div>';
        $html .= AdminUI::filter([
            'action' => admin_url('attachments'),
            'fields' => [
                ['name' => 'space', 'label' => '全部空间', 'type' => 'select', 'options' => ['shared' => '共享空间', 'private' => '私人空间', 'avatars' => '头像', 'covers' => '封面'], 'value' => $filters['space']],
                ['name' => 'kw', 'label' => '文件名', 'value' => $filters['kw']],
            ],
        ]);
        $html .= AdminUI::table([
            'primary' => 'id',
            'rows' => $p['items'],
            'bulk_action' => admin_url('attachments/bulk'),
            'bulk' => [
                ['label' => '删除', 'value' => 'delete', 'class' => 'danger', 'confirm' => '确定删除选中附件？文件将一并删除！'],
            ],
            'columns' => [
                ['key' => 'id', 'label' => 'ID', 'width' => '60px'],
                ['key' => 'name', 'label' => '文件名', 'render' => fn($r) => h(str_cut((string)$r['name'], 40))],
                ['key' => 'space', 'label' => '空间', 'width' => '90px', 'render' => fn($r) => match ((string)$r['space']) {
                    'private' => '<span class="adm-tag orange">私人</span>',
                    'avatars' => '<span class="adm-tag blue">头像</span>',
                    'covers' => '<span class="adm-tag blue">封面</span>',
                    default => '<span class="adm-tag green">共享</span>',
                }],
                ['key' => 'size', 'label' => '大小', 'width' => '90px', 'render' => fn($r) => format_size((float)$r['size'])],
                ['key' => 'username', 'label' => '上传者', 'width' => '110px'],
                ['key' => 'downloads', 'label' => '下载', 'width' => '70px'],
                ['key' => 'fee', 'label' => '收费', 'width' => '90px', 'render' => fn($r) => (float)$r['fee'] > 0 ? money((float)$r['fee']) : '免费'],
                ['key' => 'created_at', 'label' => '时间', 'width' => '130px', 'render' => fn($r) => format_date($r['created_at'])],
            ],
            'actions' => [
                ['label' => '删除', 'url' => admin_url('attachments/delete/{id}'), 'type' => 'post', 'class' => 'danger', 'confirm' => '确定删除该附件？'],
            ],
        ]);
        $html .= '<div class="adm-pager">' . $p['pager'] . '</div>';
        return $this->pageView('附件库（共 ' . $p['total'] . ' 个）', 'attachments', $html);
    }

    public function attachmentDelete(int $id): void
    {
        Attachment::delete($id);
        $this->log('删除附件', 'ID:' . $id);
        $this->back('附件已删除');
    }

    public function attachmentBulk(): void
    {
        $ids = $this->ids();
        $act = (string)Request::post('act', '');
        if (!$ids) {
            $this->back('请先选择附件', 'error');
        }
        foreach ($ids as $id) {
            if ($act === 'delete') {
                Attachment::delete($id);
            }
        }
        $this->log('附件批量删除', implode(',', $ids));
        $this->back('操作完成');
    }

    /* ================== 举报审核 ================== */
    public function reports(): string
    {
        $status = Request::input('status', '0');
        $where = '1=1';
        $params = [];
        if ($status !== '') {
            $where .= ' AND r.status = ?';
            $params[] = (int)$status;
        }
        $rpt = Database::table('reports');
        $ut = Database::table('users');
        $p = $this->queryPage(
            'SELECT r.*, u.username FROM ' . $rpt . ' r LEFT JOIN ' . $ut . ' u ON u.id = r.user_id
             WHERE ' . $where . ' ORDER BY r.status ASC, r.id DESC',
            'SELECT COUNT(*) FROM ' . $rpt . ' r WHERE ' . $where,
            $params,
            20,
            $this->baseUrl('reports', ['status' => $status])
        );
        $html = AdminUI::filter([
            'action' => admin_url('reports'),
            'fields' => [
                ['name' => 'status', 'label' => '全部状态', 'type' => 'select', 'options' => ['0' => '待处理', '1' => '已处理', '2' => '已驳回'], 'value' => $status],
            ],
        ]);
        $html .= AdminUI::table([
            'primary' => 'id',
            'rows' => $p['items'],
            'columns' => [
                ['key' => 'id', 'label' => 'ID', 'width' => '60px'],
                ['key' => 'target_type', 'label' => '类型', 'width' => '90px', 'render' => fn($r) => match ((string)$r['target_type']) {
                    'thread' => '主题', 'post' => '回复', 'user' => '用户', 'say' => '说说', default => (string)$r['target_type'],
                }],
                ['key' => 'target_id', 'label' => '举报对象', 'width' => '260px', 'render' => fn($r) => h(str_cut((string)Report::targetTitle($r), 34))],
                ['key' => 'username', 'label' => '举报人', 'width' => '110px'],
                ['key' => 'reason', 'label' => '原因', 'render' => fn($r) => h(str_cut((string)$r['reason'], 30))],
                ['key' => 'status', 'label' => '状态', 'width' => '90px', 'render' => fn($r) => match ((int)$r['status']) {
                    1 => '<span class="adm-tag green">已处理</span>',
                    2 => '<span class="adm-tag gray">已驳回</span>',
                    default => '<span class="adm-tag red">待处理</span>',
                }],
                ['key' => 'created_at', 'label' => '时间', 'width' => '130px', 'render' => fn($r) => format_date($r['created_at'])],
            ],
            'actions' => [
                ['label' => '详情', 'url' => admin_url('reports/view/{id}')],
                ['label' => '通过', 'url' => admin_url('reports/act/{id}/1'), 'type' => 'post', 'confirm' => '标记为已处理？'],
                ['label' => '驳回', 'url' => admin_url('reports/act/{id}/2'), 'type' => 'post', 'class' => 'danger'],
            ],
        ]);
        $html .= '<div class="adm-pager">' . $p['pager'] . '</div>';
        return $this->pageView('举报审核（待处理 ' . (int)Report::pending() . '）', 'reports', $html);
    }

    public function reportView(int $id): string
    {
        $report = Report::find($id);
        if (!$report) {
            return $this->pageView('举报不存在', 'reports', '<div class="adm-alert error">举报记录不存在</div>');
        }
        $html = '<div class="adm-panel"><div class="adm-panel-head">举报详情 #' . $id . '</div><div class="adm-panel-body">';
        $html .= '<dl class="adm-kv">'
            . '<dt>举报类型</dt><dd>' . h((string)$report['target_type']) . '</dd>'
            . '<dt>举报对象</dt><dd>' . h((string)Report::targetTitle($report)) . '（ID:' . (int)$report['target_id'] . '）</dd>'
            . '<dt>举报人</dt><dd>' . h((string)$report['username']) . '</dd>'
            . '<dt>举报原因</dt><dd>' . h((string)$report['reason']) . '</dd>'
            . '<dt>详细说明</dt><dd>' . nl2br(h((string)$report['detail'])) . '</dd>'
            . '<dt>当前状态</dt><dd>' . ((int)$report['status'] === 0 ? '待处理' : ((int)$report['status'] === 1 ? '已处理' : '已驳回')) . '</dd>'
            . '<dt>举报时间</dt><dd>' . h(format_date($report['created_at'])) . '</dd>'
            . '</dl>';
        $html .= '</div></div>';

        $html .= '<div class="adm-panel"><div class="adm-panel-head">处理举报</div><div class="adm-panel-body">'
            . AdminUI::form([
                'action' => admin_url('reports/handle'),
                'hidden' => ['id' => $id],
                'submit' => '提交处理',
                'fields' => [
                    ['name' => 'status', 'label' => '处理结果', 'type' => 'select', 'options' => [1 => '已处理（违规成立）', 2 => '已驳回']],
                    ['name' => 'note', 'label' => '处理备注', 'type' => 'textarea', 'tip' => '将记录到操作日志'],
                    ['name' => 'punish', 'label' => '同时处理', 'type' => 'select', 'options' => [
                        0 => '仅记录，不处理内容',
                        1 => '删除被举报内容',
                        2 => '隐藏被举报内容',
                    ]],
                ],
            ]) . '</div></div>';

        return $this->pageView('举报详情', 'reports', $html, [
            ['label' => '返回列表', 'url' => admin_url('reports')],
        ]);
    }

    public function reportHandle(): void
    {
        $adminId = (int)(Auth::id() ?? 0);
        $id = Request::int('id');
        $status = Request::int('status', 1);
        $note = $this->pure('note', 255);
        $punish = Request::int('punish');
        $report = Report::find($id);
        if (!$report) {
            $this->back('举报记录不存在', 'error');
        }
        Report::handle($id, $status, $adminId, $note);

        if ($status === 1 && $punish > 0) {
            $tid = (int)$report['target_id'];
            if ($report['target_type'] === 'thread' && $punish === 1) {
                Thread::delete($tid);
            } elseif ($report['target_type'] === 'thread' && $punish === 2) {
                Thread::update($tid, ['status' => 2]);
            } elseif ($report['target_type'] === 'post') {
                if ($punish === 1) {
                    \App\Models\Post::delete($tid);
                } else {
                    \App\Models\Post::softDelete($tid);
                }
            } elseif ($report['target_type'] === 'say') {
                Say::delete($tid);
            }
        }
        $this->log('处理举报', 'ID:' . $id . ' 结果:' . $status);
        $this->back('举报已处理');
    }

    public function reportAct(int $id, int $status): void
    {
        Report::handle($id, $status, (int)(Auth::id() ?? 0), '');
        $this->log('处理举报', 'ID:' . $id . ' 结果:' . $status);
        $this->back('举报已处理');
    }
}
