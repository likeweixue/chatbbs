<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
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
        ]);
    }

    public function view(int $id): string
    {
        $circle = Circle::find($id);
        if (!$circle || (int)$circle['status'] !== 1) {
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

    public function store(int $id): void
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
