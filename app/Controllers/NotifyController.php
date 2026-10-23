<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\View;
use App\Models\Notification;
use App\Models\Tag;
use App\Models\Thread;
use App\Models\User;
use App\Services\Stats;

class NotifyController extends BaseController
{
    public function index(): string
    {
        $this->requireLogin();
        $uid = $this->userId();
        $page = max(1, Request::int('page', 1));
        $filter = (string)Request::get('filter', 'all');
        $result = Notification::list($uid, $page, 20, $filter === 'unread' ? true : null);
        return $this->render('notification/index', [
            'title' => '通知',
            'items' => $result['items'],
            'total' => $result['total'],
            'filter' => $filter,
            'pager' => View::paginate($result['total'], 20, $page, url('notifications?filter=' . $filter)),
            'stats_site' => Stats::forum(),
            'hot_tags' => Tag::hot(15),
            'new_users' => User::newest(8),
            'latest_threads' => Thread::latest(8),
        ]);
    }

    public function readAll(): void
    {
        $this->requireLogin();
        Notification::markRead($this->userId());
        $this->success(null, '已全部标记为已读');
    }

    public function read(int $id): void
    {
        $this->requireLogin();
        Notification::markRead($this->userId(), $id);
        $this->success(null, '已读');
    }

    public function delete(int $id): void
    {
        $this->requireLogin();
        Notification::delete($this->userId(), $id);
        $this->success(null, '已删除');
    }

    public function count(): void
    {
        $uid = $this->userId();
        $this->success([
            'notifications' => $uid ? Notification::unreadCount($uid) : 0,
            'messages' => $uid ? \App\Models\Message::unreadTotal($uid) : 0,
        ]);
    }

    public static function typeIcon(string $type): string
    {
        $icons = [
            'system' => 'bell', 'like' => 'thumbs-up', 'reply' => 'comment', 'mention' => 'reply',
            'pm' => 'mail', 'audit' => 'shield', 'level' => 'star', 'checkin' => 'calendar',
            'follow' => 'user', 'friend' => 'user', 'recommend' => 'star', 'say' => 'comment', 'guestbook' => 'comment',
        ];
        return cb_icon($icons[$type] ?? 'bell');
    }
}
