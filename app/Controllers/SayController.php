<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Session;
use App\Core\Settings;
use App\Core\Security;
use App\Core\Upload;
use App\Core\View;
use App\Models\Currency;
use App\Models\Like;
use App\Models\Notification;
use App\Models\Say;
use App\Models\Tag;
use App\Models\Thread;
use App\Models\User;
use App\Services\Level;
use App\Services\Stats;

class SayController extends BaseController
{
    public function index(): string
    {
        if (!(int)Settings::get('say_enable', 1)) {
            return $this->render('errors/notice', ['title' => '功能未开启', 'message' => '说说功能已关闭。']);
        }
        $page = max(1, Request::int('page', 1));
        $userId = Request::int('user');
        $result = Say::list($page, 15, $userId);
        $user = $this->user();
        $liked = [];
        if ($user) {
            $liked = Like::likedIds((int)$user['id'], 'say', array_column($result['items'], 'id'));
        }
        return $this->render('say/index', [
            'title' => '说说',
            'says' => $result['items'],
            'liked' => $liked,
            'total' => $result['total'],
            'pager' => View::paginate($result['total'], 15, $page, url('say')),
            'can_publish' => $user && Auth::can('say.create'),
            'allow_comment' => (int)Settings::get('say_allow_comment', 1),
            'allow_like' => (int)Settings::get('say_allow_like', 1),
            'stats_site' => Stats::forum(),
            'hot_tags' => Tag::hot(15),
            'new_users' => User::newest(8),
            'latest_threads' => Thread::latest(8),
        ]);
    }

    public function publish(): void
    {
        $this->requireLogin();
        if (!(int)Settings::get('say_enable', 1)) {
            $this->fail('说说功能未开启');
        }
        $raw = (string)Request::post('content', '');
        // 简洁模式为纯文本（自动换行），高级模式为富文本
        [$content, $text] = \App\Services\Content::process($raw);
        if (mb_strlen($text) < 1) {
            $this->fail('内容不能为空');
        }
        if (mb_strlen($text) > 1000) {
            $this->fail('内容过长');
        }

        $images = [];
        if ((int)Settings::get('say_allow_image', 1)) {
            for ($i = 1; $i <= 9; $i++) {
                $file = Request::file('image' . $i);
                if ($file && !empty($file['name'])) {
                    [$ok, $res] = Upload::save($file, 'shared', $this->userId());
                    if ($ok) {
                        $images[] = $res['url'];
                    }
                }
            }
        }
        $id = Say::create($this->userId(), $content, $images);
        $sayUser = $this->user();
        \App\Services\Content::mentions($content, (int)$sayUser['id'], 'say', $id, url('say#' . $id));
        if (!(int)Settings::get('say_audit', 0)) {
            Currency::reward($this->userId(), 'say');
            Level::addExp($this->userId(), 2);
        }
        $msg = (int)Settings::get('say_audit', 0) ? '发布成功，等待审核' : '发布成功';
        // 普通表单提交：提示后跳回说说页，避免页面直接显示 JSON
        if (Request::isAjax()) {
            $this->success(['id' => $id], $msg);
        }
        Session::flash('success', $msg);
        \redirect('say');
    }

    public function delete(int $id): void
    {
        $this->requireLogin();
        $say = Say::find($id);
        if (!$say) {
            $this->fail('不存在');
        }
        if ((int)$say['user_id'] !== $this->userId() && !Auth::isAdmin()) {
            $this->fail('无权删除');
        }
        Say::delete($id);
        $this->success(null, '已删除');
    }

    public function comment(int $id): void
    {
        $this->requireLogin();
        if (!(int)Settings::get('say_allow_comment', 1)) {
            $this->fail('评论功能未开启');
        }
        $content = trim(Security::text((string)Request::post('content', ''), 300));
        if ($content === '') {
            $this->fail('评论不能为空');
        }
        $user = $this->user();
        $cid = Say::addComment($id, (int)$user['id'], (string)$user['username'], $content, Request::int('reply_to'));
        $say = Say::find($id);
        if ($say && (int)$say['user_id'] !== (int)$user['id']) {
            Notification::send((int)$say['user_id'], 'say', '有人评论了你的说说', mb_substr($content, 0, 60), url('say#' . $id), (int)$user['id']);
        }
        $this->success(['id' => $cid], '评论成功');
    }

    public function deleteComment(int $id): void
    {
        $this->requireLogin();
        $c = \App\Core\Database::fetch('SELECT * FROM ' . \App\Core\Database::table('say_comments') . ' WHERE id = ?', [$id]);
        if (!$c) {
            $this->fail('不存在');
        }
        if ((int)$c['user_id'] !== $this->userId() && !Auth::isAdmin()) {
            $this->fail('无权删除');
        }
        Say::deleteComment($id);
        $this->success(null, '已删除');
    }
}
