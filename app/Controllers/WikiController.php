<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Router;
use App\Core\Security;
use App\Core\Session;
use App\Models\Wiki;

class WikiController extends BaseController
{
    public function index(): string
    {
        return $this->render('wiki/index', [
            'title' => 'Wiki 词条',
            'pages' => Wiki::list(200),
        ]);
    }

    public function view(string $slug): string
    {
        $page = Wiki::findBySlug($slug);
        if (!$page) {
            Router::notFound();
            exit;
        }
        return $this->render('wiki/view', [
            'title' => (string)$page['title'],
            'page' => $page,
        ]);
    }

    public function createForm(): string
    {
        $this->requireLogin();
        return $this->render('wiki/form', [
            'title' => '创建词条',
            'page' => null,
        ]);
    }

    public function store(): void
    {
        $this->requireLogin();
        $title = trim((string)Request::post('title', ''));
        $html = Security::cleanHtml(Request::raw('content_html'));
        if ($title === '' || trim(strip_tags($html)) === '') {
            $this->back('标题和内容不能为空');
        }
        $slug = $this->slug((string)Request::post('slug', ''), $title);
        $userId = (int)($this->user()['id'] ?? 0);
        Wiki::create([
            'slug' => $slug,
            'title' => mb_substr($title, 0, 200),
            'content_html' => $html,
            'content_text' => mb_substr(trim(strip_tags($html)), 0, 5000),
            'user_id' => $userId,
            'status' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        Session::flash('success', '词条已创建');
        \redirect('wiki/' . $slug);
    }

    public function editForm(string $slug): string
    {
        $this->requireLogin();
        $page = Wiki::findBySlug($slug);
        if (!$page) {
            Router::notFound();
            exit;
        }
        if (!$this->canEdit($page)) {
            $this->back('没有权限编辑该词条');
        }
        return $this->render('wiki/form', [
            'title' => '编辑词条',
            'page' => $page,
        ]);
    }

    public function update(string $slug): void
    {
        $this->requireLogin();
        $page = Wiki::findBySlug($slug);
        if (!$page) {
            $this->back('词条不存在');
        }
        if (!$this->canEdit($page)) {
            $this->back('没有权限编辑该词条');
        }
        $title = trim((string)Request::post('title', ''));
        $html = Security::cleanHtml(Request::raw('content_html'));
        if ($title === '' || trim(strip_tags($html)) === '') {
            $this->back('标题和内容不能为空');
        }
        $postedSlug = strtolower(trim((string)Request::post('slug', '')));
        $newSlug = $postedSlug === (string)$page['slug'] ? (string)$page['slug'] : $this->slug($postedSlug, $title);
        if ($newSlug !== (string)$page['slug'] && Wiki::slugExists($newSlug)) {
            $this->back('该词条路径已被使用');
        }
        Wiki::update((int)$page['id'], [
            'slug' => $newSlug,
            'title' => mb_substr($title, 0, 200),
            'content_html' => $html,
            'content_text' => mb_substr(trim(strip_tags($html)), 0, 5000),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        Session::flash('success', '词条已保存');
        \redirect('wiki/' . $newSlug);
    }

    private function canEdit(array $page): bool
    {
        // Wiki 采用社区协作模式：任何登录用户都可以编辑词条。
        return $this->user() !== null;
    }

    private function slug(string $slug, string $title): string
    {
        $slug = strtolower(trim($slug));
        if ($slug === '') {
            $slug = preg_replace('/[^a-z0-9]+/i', '-', $title) ?: '';
            $slug = trim($slug, '-');
        }
        if ($slug === '' || !preg_match('/^[a-z0-9\-_]+$/', $slug) || Wiki::slugExists($slug)) {
            $slug = 'w' . date('ymd') . substr(md5($title . microtime(true)), 0, 5);
        }
        return mb_substr($slug, 0, 100);
    }
}
