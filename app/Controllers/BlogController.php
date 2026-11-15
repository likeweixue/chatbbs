<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Security;
use App\Core\Settings;
use App\Models\Blog;

/**
 * 前台博客
 */
class BlogController extends BaseController
{
    public function index(): string
    {
        $categoryId = Request::int('category', 0);
        $latest = Blog::posts(5);
        $categories = Blog::categories();
        $counts = [];
        foreach (\App\Core\Database::fetchAll(
            'SELECT category_id, COUNT(*) AS c FROM ' . \App\Core\Database::table('blog_posts')
            . ' WHERE status = 1 GROUP BY category_id'
        ) as $r) {
            $counts[(int)$r['category_id']] = (int)$r['c'];
        }
        foreach ($categories as &$c) {
            $c['count'] = $counts[(int)$c['id']] ?? 0;
        }
        unset($c);
        $tags = [];
        foreach (Blog::posts(500) as $p) {
            foreach (array_filter(array_map('trim', explode(',', (string)$p['tags']))) as $t) {
                $tags[$t] = ($tags[$t] ?? 0) + 1;
            }
        }
        arsort($tags);
        return $this->render('blog/index', [
            'title' => L('博客'),
            'latest' => $latest,
            'categories' => $categories,
            'tags' => $tags,
            'intro' => (string)Settings::get('blog_intro', ''),
            'category_id' => $categoryId,
        ]);
    }

    public function view(int $id): string
    {
        $post = Blog::find($id);
        if (!$post || (int)$post['status'] !== 1) {
            \App\Core\Router::notFound();
            exit;
        }
        Blog::view($id);
        $cat = null;
        if ((int)$post['category_id'] > 0) {
            $cat = \App\Core\Database::fetch('SELECT * FROM ' . \App\Core\Database::table('blog_categories') . ' WHERE id = ?', [(int)$post['category_id']]);
        }
        return $this->render('blog/view', [
            'title' => (string)$post['title'],
            'post' => $post,
            'category' => $cat,
        ]);
    }

    public function my(): string
    {
        $this->requireLogin();
        return $this->render('blog/my', [
            'title' => L('我的博客'),
            'posts' => Blog::posts(200, 0, (int)$this->userId()),
            'categories' => Blog::categories((int)$this->userId()),
        ]);
    }

    public function createForm(): string
    {
        $this->requireLogin();
        return $this->render('blog/create', [
            'title' => L('写文章'),
            'categories' => Blog::categories((int)$this->userId()),
        ]);
    }

    public function store(): void
    {
        $this->requireLogin();
        $title = trim((string)Request::post('title', ''));
        $text = trim((string)Request::post('content_text', ''));
        if ($title === '' || mb_strlen($title) > 200) {
            $this->back('标题不能为空且不超过 200 字');
        }
        if ($text === '') {
            $this->back('内容不能为空');
        }
        $uid = (int)$this->userId();
        $user = $this->user();
        $catName = trim((string)Request::post('category', ''));
        $categoryId = $catName !== '' ? Blog::categoryId($uid, $catName) : 0;
        $slug = 'b' . substr(md5($title . microtime(true) . $uid), 0, 10);
        $id = Blog::create([
            'user_id' => $uid,
            'username' => (string)($user['username'] ?? ''),
            'title' => mb_substr($title, 0, 200),
            'slug' => $slug,
            'content' => nl2br(h($text)),
            'content_text' => mb_substr($text, 0, 5000),
            'category_id' => $categoryId,
            'tags' => mb_substr(trim((string)Request::post('tags', '')), 0, 255),
            'status' => 1,
        ]);
        \App\Core\Session::flash('success', '文章已发布');
        \redirect('blog/' . $id);
    }
}
