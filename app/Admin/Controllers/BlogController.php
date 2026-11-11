<?php

declare(strict_types=1);

namespace App\Admin\Controllers;

use App\Admin\AdminUI;
use App\Admin\BaseAdmin;
use App\Models\Blog;

/**
 * 博客管理
 */
class BlogController extends BaseAdmin
{
    public function index(): string
    {
        $rows = Blog::allForAdmin();
        $html = '<div class="adm-alert info">博客是用户个人文章，支持自定义分类与标签。这里可以审核 / 隐藏 / 删除文章。</div>';
        $html .= AdminUI::table([
            'primary' => 'id',
            'rows' => $rows,
            'columns' => [
                ['key' => 'id', 'label' => 'ID', 'width' => '60px'],
                ['key' => 'title', 'label' => '标题'],
                ['key' => 'username', 'label' => '作者', 'width' => '120px'],
                ['key' => 'views', 'label' => '浏览', 'width' => '80px'],
                ['key' => 'status', 'label' => '状态', 'width' => '80px', 'render' => fn($r) => (int)$r['status'] === 1
                    ? '<span class="adm-tag green">显示</span>' : '<span class="adm-tag gray">隐藏</span>'],
                ['key' => 'created_at', 'label' => '发布时间', 'width' => '140px', 'render' => fn($r) => format_date($r['created_at'])],
            ],
            'actions' => [
                ['label' => '切换显示', 'url' => admin_url('blog/toggle/{id}'), 'type' => 'post'],
                ['label' => '删除', 'url' => admin_url('blog/delete/{id}'), 'type' => 'post', 'class' => 'danger', 'confirm' => '确定删除该文章？'],
            ],
        ]);
        return $this->pageView('博客管理', 'blog', $html);
    }

    public function toggle(int $id): void
    {
        $post = Blog::find($id);
        if ($post) {
            Blog::update($id, ['status' => (int)$post['status'] === 1 ? 0 : 1]);
            $this->log('切换博客状态', (string)$post['title']);
        }
        $this->back('已更新');
    }

    public function delete(int $id): void
    {
        Blog::delete($id);
        $this->log('删除博客', 'ID:' . $id);
        $this->back('文章已删除');
    }
}
