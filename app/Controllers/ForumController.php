<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Settings;
use App\Core\View;
use App\Models\Forum;
use App\Models\Thread;
use App\Services\Spotlight;
use App\Services\Stats;

class ForumController extends BaseController
{
    public function index(): string
    {
        $tree = Forum::tree();
        $user = $this->user();
        foreach ($tree as &$cat) {
            foreach ($cat['children'] as &$board) {
                $board['can_view'] = Forum::canView($board, $user);
            }
        }
        return $this->render('forum/index', [
            'title' => '板块列表',
            'tree' => $tree,
            'stats' => Stats::forum(),
            'spotlight' => Spotlight::cells(),
            'hot_tags' => \App\Models\Tag::hot(15),
            'new_users' => \App\Models\User::newest(8),
            'latest_threads' => Thread::latest(8),
        ]);
    }

    public function view(int $id): string
    {
        $forum = Forum::find($id);
        if (!$forum) {
            \App\Core\Router::notFound();
            exit;
        }
        $user = $this->user();
        if (!Forum::canView($forum, $user)) {
            return $this->render('errors/notice', [
                'title' => '无权访问',
                'message' => '你没有权限访问该板块，可能需要登录或提升等级。',
            ]);
        }
        $page = max(1, Request::int('page', 1));
        $perPage = max(5, (int)Settings::get('threads_per_page', 20));
        $tabRes = $this->resolveTab();
        $result = Thread::list([
            'forum_id' => $id,
            'page' => $page,
            'per_page' => $perPage,
            'order' => $tabRes['order'],
            'essence' => $tabRes['essence'],
            'mark_id' => $tabRes['mark_id'],
            'digest' => $tabRes['digest'],
            'recommend_kind' => $tabRes['recommend_kind'],
        ]);
        $baseUrl = url('forum/' . $id . '?tab=' . rawurlencode($tabRes['tab']));

        return $this->render('forum/view', [
            'title' => (string)$forum['name'],
            'forum' => $forum,
            'can_post' => Forum::canPost($forum, $user),
            'is_moderator' => Forum::isModerator($forum, $user),
            'threads' => $result['items'],
            'total' => $result['total'],
            'pager' => View::paginate($result['total'], $perPage, $page, $baseUrl),
            'order' => $tabRes['order'],
            'tab' => $tabRes['tab'],
            'list_tabs' => self::listTabs(),
            'stats' => Stats::forum(),
            'hot_tags' => \App\Models\Tag::hot(15),
            'new_users' => \App\Models\User::newest(8),
            'latest_threads' => Thread::latest(8),
        ]);
    }
}
