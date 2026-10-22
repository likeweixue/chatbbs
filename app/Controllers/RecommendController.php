<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Settings;
use App\Models\Notification;
use App\Models\Recommend;
use App\Models\Thread;

class RecommendController extends BaseController
{
    /**
     * 旧版独立的「推荐 / 避雷」页面已合并到首页筛选标签，这里保留跳转兼容老链接。
     */
    public function index(): void
    {
        $kind = Request::int('kind', 1) === -1 ? 'avoid' : 'recommend';
        \redirect('?tab=' . $kind);
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
