<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Settings;
use App\Models\Circle;
use App\Models\Thread;
use App\Services\Stats;

/**
 * 前台圈子
 */
class CircleController extends BaseController
{
    public function index(): string
    {
        $circles = Circle::all();
        return $this->render('circle/index', [
            'title' => L('圈子'),
            'circles' => $circles,
            'my_id' => (int)(Auth::id() ?? 0),
            'can_create' => $this->canCreate(),
        ]);
    }

    public function createForm(): string
    {
        $this->requireLogin();
        if (!$this->canCreate()) {
            $this->back('当前未开放用户创建圈子');
        }
        return $this->render('circle/create', ['title' => '创建圈子']);
    }

    public function store(): void
    {
        $this->requireLogin();
        if (!$this->canCreate()) {
            $this->back('当前未开放用户创建圈子');
        }
        $name = trim((string)Request::post('name', ''));
        if ($name === '') {
            $this->back('圈子名称不能为空');
        }
        $slug = 'c' . substr(md5($name . microtime(true) . (string)Auth::id()), 0, 8);
        $needAudit = (int)Settings::get('circle_need_audit', 1) === 1 && !Auth::isAdmin();
        $uid = (int)$this->userId();
        $id = Circle::create([
            'name' => mb_substr($name, 0, 80),
            'slug' => $slug,
            'description' => mb_substr(trim((string)Request::post('description', '')), 0, 500),
            'icon' => trim((string)Request::post('icon', '')),
            'color' => trim((string)Request::post('color', '#4D698E')) ?: '#4D698E',
            'owner_id' => $uid,
            'is_open' => 1,
            'status' => $needAudit ? 0 : 1,
            'member_count' => 0,
        ]);
        // 创建者自动成为成员
        Database::insert('circle_members', [
            'circle_id' => $id,
            'user_id' => $uid,
            'role' => 1,
            'joined_at' => date('Y-m-d H:i:s'),
        ]);
        Circle::syncCount($id);
        \App\Core\Session::flash('success', $needAudit ? '圈子已提交，等待管理员审核' : '圈子已创建');
        \redirect('circle/' . $id);
    }

    private function canCreate(): bool
    {
        return Auth::isAdmin() || (Auth::check() && (int)Settings::get('circle_user_create', 0) === 1);
    }

    public function view(int $id): string
    {
        $circle = Circle::find($id);
        $isOwnerOrAdmin = $circle && (Auth::isAdmin() || (int)($circle['owner_id'] ?? 0) === (int)(Auth::id() ?? 0));
        if (!$circle || ((int)$circle['status'] !== 1 && !$isOwnerOrAdmin)) {
            \App\Core\Router::notFound();
            exit;
        }
        $user = $this->user();
        $uid = (int)($user['id'] ?? 0);
        $page = max(1, Request::int('page', 1));
        $perPage = max(5, (int)Settings::get('threads_per_page', 20));
        $result = Thread::list([
            'forum_id' => (int)$circle['forum_id'],
            'page' => $page,
            'per_page' => $perPage,
            'order' => 'new',
        ]);
        $isMember = $uid > 0 && Circle::isMember($id, $uid);
        return $this->render('circle/view', [
            'title' => (string)$circle['name'],
            'circle' => $circle,
            'threads' => $result['items'],
            'total' => $result['total'],
            'pager' => \App\Core\View::paginate($result['total'], $perPage, $page, url('circle/' . $id)),
            'is_member' => $isMember,
            'members' => Circle::members($id, 24),
            'can_post' => $uid > 0 && ($isMember || Auth::isModerator()),
        ]);
    }

    public function join(int $id): void
    {
        $this->requireLogin();
        $ok = Circle::join($id, (int)$this->userId());
        \App\Core\Session::flash($ok ? 'success' : 'error', $ok ? '已加入圈子' : '加入失败');
        \redirect('circle/' . $id);
    }

    public function leave(int $id): void
    {
        $this->requireLogin();
        $ok = Circle::leave($id, (int)$this->userId());
        \App\Core\Session::flash($ok ? 'success' : 'error', $ok ? '已退出圈子' : '退出失败');
        \redirect('circle/' . $id);
    }

    public function postForm(int $id): string
    {
        $this->requireLogin();
        $circle = Circle::find($id);
        if (!$circle || (int)$circle['status'] !== 1) {
            \App\Core\Router::notFound();
            exit;
        }
        $uid = (int)$this->userId();
        if (!Circle::isMember($id, $uid) && !Auth::isModerator()) {
            $this->back('请先加入圈子再发帖');
        }
        return $this->render('circle/post', [
            'title' => '在「' . $circle['name'] . '」发帖',
            'circle' => $circle,
        ]);
    }

    public function storePost(int $id): void
    {
        $this->requireLogin();
        $circle = Circle::find($id);
        if (!$circle || (int)$circle['status'] !== 1) {
            $this->back('圈子不存在');
        }
        $uid = (int)$this->userId();
        if (!Circle::isMember($id, $uid) && !Auth::isModerator()) {
            $this->back('请先加入圈子再发帖');
        }
        $title = trim((string)Request::post('title', ''));
        $body = trim((string)Request::post('content', ''));
        if (mb_strlen($title) < 2 || mb_strlen($title) > 100) {
            $this->back('标题长度需在 2 - 100 字之间');
        }
        if (mb_strlen($body) < 2) {
            $this->back('内容太短了');
        }
        $status = (int)Settings::get('thread_audit', 0) ? 0 : 1;
        $html = nl2br(h($body));
        $tid = Thread::create([
            'forum_id' => (int)$circle['forum_id'],
            'user_id' => $uid,
            'username' => (string)($this->user()['username'] ?? ''),
            'title' => $title,
            'content' => $html,
            'content_text' => $body,
            'status' => $status,
            'last_reply_user' => (string)($this->user()['username'] ?? ''),
            'last_reply_user_id' => $uid,
        ]);
        Stats::clearCache();
        \App\Core\Session::flash('success', $status === 0 ? '发布成功，等待审核' : '发布成功');
        \redirect('thread/' . $tid);
    }
}
