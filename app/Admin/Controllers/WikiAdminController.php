<?php

declare(strict_types=1);

namespace App\Admin\Controllers;

use App\Admin\AdminUI;
use App\Admin\BaseAdmin;
use App\Core\Database;
use App\Core\Request;
use App\Models\Wiki;

/**
 * Wiki 词条管理
 */
class WikiAdminController extends BaseAdmin
{
    public function index(): string
    {
        $rows = Database::fetchAll(
            'SELECT w.*, u.username FROM ' . Database::table('wiki_pages') . ' w'
            . ' LEFT JOIN ' . Database::table('users') . ' u ON u.id = w.user_id'
            . ' ORDER BY w.updated_at DESC'
        );
        $editId = Request::int('edit');
        $edit = $editId > 0 ? Database::fetch('SELECT * FROM ' . Database::table('wiki_pages') . ' WHERE id = ?', [$editId]) : null;

        $html = '<div class="adm-alert info">Wiki 词条所有登录用户都可以创建和编辑，后台可在这里统一管理。</div>';
        $html .= AdminUI::table([
            'primary' => 'id',
            'rows' => $rows,
            'columns' => [
                ['key' => 'id', 'label' => 'ID', 'width' => '60px'],
                ['key' => 'title', 'label' => '标题', 'render' => fn($r) => '<b>' . h((string)$r['title']) . '</b> <span class="muted">' . h((string)$r['slug']) . '</span>'],
                ['key' => 'username', 'label' => '作者', 'width' => '120px'],
                ['key' => 'status', 'label' => '状态', 'width' => '80px', 'render' => fn($r) => (int)$r['status'] === 1
                    ? '<span class="adm-tag green">显示</span>' : '<span class="adm-tag gray">隐藏</span>'],
                ['key' => 'updated_at', 'label' => '更新时间', 'width' => '140px', 'render' => fn($r) => format_date($r['updated_at'])],
            ],
            'actions' => [
                ['label' => '编辑', 'url' => admin_url('wiki?edit={id}')],
                ['label' => '删除', 'url' => admin_url('wiki/delete/{id}'), 'type' => 'post', 'class' => 'danger', 'confirm' => '确定删除该词条？'],
            ],
        ]);

        $html .= '<div class="adm-panel"><div class="adm-panel-head">' . ($edit ? '编辑词条' : '新增词条') . '</div><div class="adm-panel-body">'
            . AdminUI::form([
                'action' => admin_url('wiki/save'),
                'data' => $edit ?? [],
                'hidden' => ['id' => $edit['id'] ?? 0],
                'submit' => '保存词条',
                'fields' => [
                    ['name' => 'title', 'label' => '标题'],
                    ['name' => 'slug', 'label' => 'URL 标识', 'tip' => '留空自动生成'],
                    ['name' => 'content_text', 'label' => '内容', 'type' => 'textarea', 'rows' => 14],
                    ['name' => 'status', 'label' => '显示词条', 'type' => 'switch'],
                ],
            ]) . '</div></div>';

        return $this->pageView('Wiki 维基', 'wiki', $html);
    }

    public function save(): void
    {
        $id = Request::int('id');
        $title = $this->pure('title', 200);
        if ($title === '') {
            $this->back('标题不能为空', 'error');
        }
        $slug = trim((string)Request::post('slug', ''));
        if ($slug === '') {
            $slug = preg_match('/^[a-z0-9\-_]+$/i', $title) ? strtolower(str_replace(' ', '-', $title)) : 'w' . substr(md5($title . microtime(true)), 0, 8);
        }
        $slug = preg_replace('/[^a-z0-9\-_]/i', '-', $slug) ?: ('w' . substr(md5($title), 0, 8));
        $exists = Database::value('SELECT id FROM ' . Database::table('wiki_pages') . ' WHERE slug = ?', [$slug]);
        if ($exists && (int)$exists !== $id) {
            $this->back('URL 标识已被占用', 'error');
        }
        $text = trim((string)Request::post('content_text', ''));
        $data = [
            'title' => $title,
            'slug' => $slug,
            'content_text' => $text,
            'content_html' => nl2br(h($text)),
            'status' => Request::bool('status') ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if ($id > 0) {
            Wiki::update($id, $data);
            $this->log('编辑 Wiki 词条', $title);
        } else {
            $data['user_id'] = (int)(\App\Core\Auth::id() ?? 0);
            $data['created_at'] = date('Y-m-d H:i:s');
            Wiki::create($data);
            $this->log('新增 Wiki 词条', $title);
        }
        $this->back('词条已保存');
    }

    public function delete(int $id): void
    {
        Wiki::delete($id);
        $this->log('删除 Wiki 词条', 'ID:' . $id);
        $this->back('词条已删除');
    }
}
