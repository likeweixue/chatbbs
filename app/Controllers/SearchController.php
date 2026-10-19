<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\View;
use App\Models\Tag;
use App\Models\Thread;
use App\Models\User;
use App\Services\Search;
use App\Services\Stats;

class SearchController extends BaseController
{
    public function index(): string
    {
        $keyword = trim((string)Request::get('q', ''));
        $type = (string)Request::get('type', 'thread');
        $page = max(1, Request::int('page', 1));
        $forumId = Request::int('forum');

        $result = ['total' => 0, 'items' => []];
        if ($keyword !== '') {
            if ($type === 'user') {
                $items = Search::users($keyword, 50);
                $result = ['total' => count($items), 'items' => $items];
                Search::log($keyword, $this->userId(), count($items));
            } elseif ($type === 'tag') {
                $items = Search::tags($keyword, 50);
                $result = ['total' => count($items), 'items' => $items];
            } else {
                $result = Search::threads($keyword, $page, 20, $forumId);
                Search::log($keyword, $this->userId(), $result['total']);
            }
        }

        return $this->render('search/index', [
            'title' => '搜索',
            'keyword' => $keyword,
            'type' => $type,
            'result' => $result,
            'forums' => \App\Models\Forum::boards(),
            'forum_id' => $forumId,
            'hot_keywords' => Search::hotKeywords(10),
            'pager' => View::paginate($result['total'], 20, $page, url('search?q=' . urlencode($keyword) . '&type=' . $type)),
            'stats_site' => Stats::forum(),
            'hot_tags' => Tag::hot(15),
            'new_users' => User::newest(8),
            'latest_threads' => Thread::latest(8),
        ]);
    }
}
