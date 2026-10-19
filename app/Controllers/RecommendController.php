<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Settings;
use App\Core\View;
use App\Models\Notification;
use App\Models\Recommend;
use App\Models\Tag;
use App\Models\Thread;
use App\Models\User;
use App\Services\Stats;

class RecommendController extends BaseController
{
    public function index(): string
    {
        if (!(int)Settings::get('recommend_enable', 1)) {
            return $this->render('errors/notice', ['title' => '功能未开启', 'message' => '推荐功能已关闭。']);
        }
        $kind = Request::int('kind', 1) === -1 ? -1 : 1;
        $page = max(1, Request::int('page', 1));
        $result = Recommend::list($page, 20, $kind);
        return $this->render('recommend/index', [
            'title' => $kind === 1 ? '推荐' : '避雷区',
            'threads' => $result['items'],
            'total' => $result['total'],
            'kind' => $kind,
            'pager' => View::paginate($result['total'], 20, $page, url('recommend?kind=' . $kind)),
            'stats_site' => Stats::forum(),
            'hot_tags' => Tag::hot(15),
            'new_users' => User::newest(8),
            'latest_threads' => Thread::latest(8),
        ]);
    }

    public function toggle(): void
    {
        $this->requireLogin();
        if (!(int)Settings::get('recommend_enable', 1)) {
            $this->fail('推荐功能未开启');
        }
        $threadId = Request::int('thread_id');
        $kind = Request::int('kind', 1) === -1 ? -1 : 1;
        $thread = Thread::find($threadId);
        if (!$thread) {
            $this->fail('帖子不存在');
        }
        $user = $this->user();
        $res = Recommend::toggle((int)$user['id'], $threadId, $kind, (string)Request::post('reason', ''));
        if ($res['active'] && $kind === 1 && (int)$thread['user_id'] !== (int)$user['id']) {
            Notification::send((int)$thread['user_id'], 'recommend', '你的帖子被推荐了',
                (string)$thread['title'], url('thread/' . $threadId), (int)$user['id']);
        }
        $this->success($res, $res['msg']);
    }
}
