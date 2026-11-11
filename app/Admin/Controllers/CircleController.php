<?php

declare(strict_types=1);

namespace App\Admin\Controllers;

use App\Admin\AdminUI;
use App\Admin\BaseAdmin;
use App\Core\Database;
use App\Core\Request;
use App\Models\Circle;
use App\Models\Forum;

/**
 * 圈子（兴趣小组）管理
 */
class CircleController extends BaseAdmin
{
    public function index(): string
    {
        $rows = Circle::all(false);
        $editId = Request::int('edit');
        $edit = $editId > 0 ? Circle::find($editId) : null;

        $html = '<div class="adm-alert info">圈子是兴趣小组，成员可加入并在圈子内发帖。删除圈子不会删除已发布的帖子。</div>';
        $html .= AdminUI::table([
            'primary' => 'id',
            'rows' => $rows,
            'columns' => [
                ['key' => 'id', 'label' => 'ID', 'width' => '60px'],
                ['key' => 'name', 'label' => '圈子名称', 'render' => fn($r) => '<b>' . h((string)$r['name']) . '</b> <span class="muted">' . h((string)$r['slug']) . '</span>'],
                ['key' => 'description', 'label' => '简介', 'render' => fn($r) => h(str_cut((string)$r['description'], 40))],
                ['key' => 'member_count', 'label' => '成员', 'width' => '80px'],
                ['key' => 'is_open', 'label' => '加入', 'width' => '80px', 'render' => fn($r) => (int)$r['is_open'] === 1
                    ? '<span class="adm-tag green">开放</span>' : '<span class="adm-tag gray">审核</span>'],
                ['key' => 'status', 'label' => '状态', 'width' => '80px', 'render' => fn($r) => (int)$r['status'] === 1
                    ? '<span class="adm-tag green">显示</span>' : '<span class="adm-tag gray">隐藏</span>'],
            ],
            'actions' => [
                ['label' => '编辑', 'url' => admin_url('circles?edit={id}')],
                ['label' => '删除', 'url' => admin_url('circles/delete/{id}'), 'type' => 'post', 'class' => 'danger', 'confirm' => '确定删除该圈子？'],
            ],
        ]);

        $html .= '<div class="adm-panel"><div class="adm-panel-head">' . ($edit ? '编辑圈子' : '新增圈子') . '</div><div class="adm-panel-body">'
            . AdminUI::form([
                'action' => admin_url('circles/save'),
                'data' => $edit ?? [],
                'hidden' => ['id' => $edit['id'] ?? 0],
                'submit' => '保存圈子',
                'fields' => [
                    ['name' => 'name', 'label' => '圈子名称'],
                    ['name' => 'slug', 'label' => 'URL 标识', 'tip' => '留空自动生成，建议英文或拼音'],
                    ['name' => 'description', 'label' => '简介', 'type' => 'textarea'],
                    ['name' => 'icon', 'label' => '图标', 'type' => 'image', 'tip' => '支持图片地址或 emoji'],
                    ['name' => 'color', 'label' => '主题色', 'type' => 'color'],
                    ['name' => 'is_open', 'label' => '允许自由加入', 'type' => 'switch', 'tip' => '关闭后仅管理员可拉人'],
                    ['name' => 'status', 'label' => '显示圈子', 'type' => 'switch'],
                ],
            ]) . '</div></div>';

        return $this->pageView('圈子管理', 'circles', $html);
    }

    public function save(): void
    {
        $id = Request::int('id');
        $name = $this->pure('name', 80);
        if ($name === '') {
            $this->back('圈子名称不能为空', 'error');
        }
        $slug = trim((string)Request::post('slug', ''));
        if ($slug === '') {
            $slug = preg_match('/^[a-z0-9\-_]+$/i', $name) ? strtolower(str_replace([' ', '_'], '-', $name)) : 'c' . substr(md5($name . microtime(true)), 0, 8);
        }
        $slug = preg_replace('/[^a-z0-9\-_]/i', '-', $slug) ?: ('c' . substr(md5($name), 0, 8));
        $exists = Circle::findBySlug($slug);
        if ($exists && (int)$exists['id'] !== $id) {
            $this->back('URL 标识已被占用', 'error');
        }
        $data = [
            'name' => $name,
            'slug' => $slug,
            'description' => $this->pure('description', 500),
            'icon' => $this->pure('icon', 500),
            'color' => $this->pure('color', 20),
            'is_open' => Request::bool('is_open') ? 1 : 0,
            'status' => Request::bool('status') ? 1 : 0,
        ];
        if ($id > 0) {
            Circle::update($id, $data);
            $circle = Circle::find($id);
            if ($circle && (int)$circle['forum_id'] <= 0) {
                $this->ensureForum($id, $name, $slug, $data['color']);
            }
            $this->log('编辑圈子', $name);
        } else {
            $data['owner_id'] = (int)(\App\Core\Auth::id() ?? 0);
            $id = Circle::create($data);
            $this->ensureForum($id, $name, $slug, $data['color']);
            $this->log('新增圈子', $name);
        }
        $this->back('圈子已保存');
    }

    public function delete(int $id): void
    {
        Circle::delete($id);
        $this->log('删除圈子', 'ID:' . $id);
        $this->back('圈子已删除');
    }

    /** 为圈子创建一个隐藏板块用于存放帖子 */
    private function ensureForum(int $circleId, string $name, string $slug, string $color): void
    {
        $forumSlug = 'circle-' . $slug;
        $forum = Forum::findBySlug($forumSlug);
        if (!$forum) {
            $forumId = Forum::create([
                'parent_id' => 0,
                'name' => $name,
                'slug' => $forumSlug,
                'description' => '',
                'icon' => '',
                'color' => $color,
                'type' => 2,
                'sort_order' => 999,
                'visible' => 0,
            ]);
        } else {
            $forumId = (int)$forum['id'];
        }
        Circle::update($circleId, ['forum_id' => (int)$forumId]);
    }
}
