<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Security;
use App\Models\Message;
use App\Models\Tag;
use App\Models\Thread;
use App\Models\User;
use App\Services\Stats;

class MessageController extends BaseController
{
    public function index(): string
    {
        $this->requireLogin();
        $uid = $this->userId();
        $with = Request::int('user');
        return $this->render('message/index', [
            'title' => '聊天',
            'conversations' => Message::conversations($uid),
            'with' => $with > 0 ? User::find($with) : null,
            'messages' => $with > 0 ? Message::thread($uid, $with) : [],
            'stats_site' => Stats::forum(),
            'hot_tags' => Tag::hot(15),
            'new_users' => User::newest(8),
            'latest_threads' => Thread::latest(8),
        ]);
    }

    public function send(): void
    {
        $this->requireLogin();
        if (!Auth::can('pm.send')) {
            $this->fail('没有发送消息的权限');
        }
        $toId = Request::int('to_id');
        $content = trim(Security::text((string)Request::post('content', ''), 1000));
        if ($toId <= 0 || $content === '') {
            $this->fail('参数不完整');
        }
        if ($toId === $this->userId()) {
            $this->fail('不能给自己发消息');
        }
        if (!User::find($toId)) {
            $this->fail('用户不存在');
        }
        $id = Message::send($this->userId(), $toId, $content);
        if (!$id) {
            $this->fail('发送失败，对方可能已屏蔽你');
        }
        $this->success(['id' => $id], '已发送');
    }

    public function deleteConversation(int $userId): void
    {
        $this->requireLogin();
        Message::deleteConversation($this->userId(), $userId);
        $this->success(null, '已删除会话');
    }

    // ---------- 社交 ----------
    public function follow(): void
    {
        $this->requireLogin();
        $id = Request::int('user_id');
        if ($id <= 0 || $id === $this->userId()) {
            $this->fail('参数错误');
        }
        $res = Message::toggleFollow($this->userId(), $id);
        $this->success($res, $res['msg']);
    }

    public function friend(): void
    {
        $this->requireLogin();
        $id = Request::int('user_id');
        $res = Message::addFriend($this->userId(), $id, (string)Request::post('note', ''));
        $res['ok'] ? $this->success($res, $res['msg']) : $this->fail($res['msg']);
    }

    public function unfriend(): void
    {
        $this->requireLogin();
        Message::removeFriend($this->userId(), Request::int('user_id'));
        $this->success(null, '已解除好友关系');
    }

    public function block(): void
    {
        $this->requireLogin();
        $res = Message::toggleBlock($this->userId(), Request::int('user_id'));
        $this->success($res, $res['msg']);
    }
}
