<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\View;
use App\Models\Tag;
use App\Models\Thread;
use App\Models\User;
use App\Services\Stats;

class TagController extends BaseController
{
    public function index(): string
    {
        $tags = Tag::all();
        return $this->render('tag/index', [
            'title' => '标签',
            'tags' => $tags,
            'stats_site' => Stats::forum(),
            'hot_tags' => Tag::hot(15),
            'new_users' => User::newest(8),
            'latest_threads' => Thread::latest(8),
        ]);
    }

    public function view(int $id): string
    {
        $tag = Tag::find($id);
        if (!$tag) {
            \App\Core\Router::notFound();
            exit;
        }
        $page = max(1, Request::int('page', 1));
        $result = Thread::list(['tag_id' => $id, 'page' => $page, 'per_page' => 20, 'order' => 'new']);
        return $this->render('tag/view', [
            'title' => '#' . (string)$tag['name'],
            'tag' => $tag,
            'threads' => $result['items'],
            'total' => $result['total'],
            'pager' => View::paginate($result['total'], 20, $page, url('tag/' . $id)),
            'stats_site' => Stats::forum(),
            'hot_tags' => Tag::hot(15),
            'new_users' => User::newest(8),
            'latest_threads' => Thread::latest(8),
        ]);
    }
}
