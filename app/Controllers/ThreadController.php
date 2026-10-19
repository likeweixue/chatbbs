<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\Security;
use App\Core\Session;
use App\Core\Settings;
use App\Core\View;
use App\Models\Currency;
use App\Models\Forum;
use App\Models\Like;
use App\Models\Notification;
use App\Models\Post;
use App\Models\Recommend;
use App\Models\Tag;
use App\Models\Thread;
use App\Services\Content;
use App\Services\Level;
use App\Services\Stats;

class ThreadController extends BaseController
{
    public function view(int $id): string
    {
        $thread = Thread::detail($id);
        if (!$thread || (int)$thread['status'] === 2) {
            \App\Core\Router::notFound();
            exit;
        }
        // 编辑信息（编辑人昵称）
        if (!empty($thread['edited_by'])) {
            $editor = \App\Core\Database::fetch(
                'SELECT username, nickname FROM ' . \App\Core\Database::table('users') . ' WHERE id = ?',
                [(int)$thread['edited_by']]
            );
            $thread['edited_username'] = $editor ? ($editor['nickname'] ?: $editor['username']) : '用户';
        }
        $user = $this->user();
        $forum = $thread['forum'];
        if ($forum && !Forum::canView($forum, $user)) {
            return $this->render('errors/notice', ['title' => '无权访问', 'message' => '你没有权限查看该内容。']);
        }
        if ((int)$thread['status'] === 0 && !($user && ((int)$user['id'] === (int)$thread['user_id'] || \App\Core\Auth::isAdmin()))) {
            return $this->render('errors/notice', ['title' => '待审核', 'message' => '该帖正在审核中，暂不可见。']);
        }

        $page = max(1, Request::int('page', 1));
        $perPage = max(5, (int)Settings::get('posts_per_page', 15));
        $result = Post::listByThread($id, $page, $perPage);

        // 楼中楼：取出本页各楼层的子回复（超出预览条数的部分由前端折叠）
        $replyPreview = max(1, (int)Settings::get('reply_preview_count', 5));
        $subposts = [];
        $pageIds = array_values(array_filter(array_map('intval', array_column($result['items'], 'id'))));
        if ($pageIds) {
            $in = implode(',', array_fill(0, count($pageIds), '?'));
            $childRows = Database::fetchAll(
                'SELECT p.*, u.nickname, u.avatar
                 FROM ' . Database::table('posts') . ' p
                 LEFT JOIN ' . Database::table('users') . ' u ON u.id = p.user_id
                 WHERE p.thread_id = ? AND p.status = 1 AND p.reply_to IN (' . $in . ')
                 ORDER BY p.id ASC',
                array_merge([$id], $pageIds)
            );
            $childIds = [];
            foreach ($childRows as $row) {
                $pid = (int)$row['reply_to'];
                if (!isset($subposts[$pid])) {
                    $subposts[$pid] = [];
                }
                $subposts[$pid][] = $row;
                $childIds[] = (int)$row['id'];
            }
            // 子回复只 nested 显示，不再重复出现在楼层列表中
            if ($childIds) {
                $result['items'] = array_values(array_filter(
                    $result['items'],
                    static fn($p) => !in_array((int)$p['id'], $childIds, true)
                ));
            }
        }

        // 统计在线时长 / 活跃
        if ($user) {
            \App\Models\User::touch((int)$user['id']);
        }
        Database::query('UPDATE ' . Database::table('threads') . ' SET views = views + 1 WHERE id = ?', [$id]);

        $liked = false;
        $recommend = null;
        if ($user) {
            $liked = Like::has((int)$user['id'], 'thread', $id);
            $recommend = Recommend::exists((int)$user['id'], $id);
            $likedPosts = Like::likedIds((int)$user['id'], 'post', array_column($result['items'], 'id'));
        } else {
            $likedPosts = [];
        }

        return $this->render('thread/view', [
            'title' => (string)$thread['title'],
            'thread' => $thread,
            'posts' => $result['items'],
            'subposts' => $subposts,
            'reply_preview' => $replyPreview,
            // 帖子页侧边栏只保留「发布主题 + 目录」
            'show_toc' => true,
            'liked' => $liked,
            'liked_posts' => $likedPosts,
            'recommend' => $recommend,
            'total' => $result['total'],
            'pager' => View::paginate($result['total'], $perPage, $page, url('thread/' . $id)),
            'can_reply' => $user && (int)$thread['is_closed'] === 0 && Forum::canPost($forum, $user),
            'is_moderator' => \App\Core\Auth::isAdmin() || ($forum && Forum::isModerator($forum, $user)),
            'stats' => Stats::forum(),
            'hot_tags' => Tag::hot(15),
            'new_users' => \App\Models\User::newest(8),
            'latest_threads' => Thread::latest(8),
        ]);
    }

    /**
     * 判断某表是否存在指定字段（带静态缓存，升级前不报错）
     */
    private static function hasColumn(string $table, string $col): bool
    {
        static $cache = [];
        $key = $table . '.' . $col;
        if (!isset($cache[$key])) {
            try {
                $row = \App\Core\Database::fetch(
                    'SHOW COLUMNS FROM ' . \App\Core\Database::table($table) . ' LIKE ?',
                    [$col]
                );
                $cache[$key] = (bool)$row;
            } catch (\Throwable $e) {
                $cache[$key] = false;
            }
        }
        return $cache[$key];
    }

    /**
     * Markdown 源码 / 正文格式字段：表结构未升级时自动忽略，避免写入报错
     */
    private static function mdFields(string $table, string $md, string $format): array
    {
        $out = [];
        if (self::hasColumn($table, 'content_md')) {
            // 富文本模式写空串，避免从 Markdown 切回后再次编辑仍进入 Markdown
            $out['content_md'] = $md;
        }
        if (self::hasColumn($table, 'content_format')) {
            $out['content_format'] = $format;
        }
        return $out;
    }

    /**
     * 版权许可证字段：表结构未升级时自动忽略，避免写入报错
     */
    private static function licenseFields(string $table): array
    {
        $key = (string)Request::post('license', '');
        if (!array_key_exists($key, \license_options())) {
            $key = '';
        }
        $out = ['license' => $key, 'license_custom' => ''];
        if ($key === 'custom') {
            $out['license_custom'] = mb_substr(trim(strip_tags((string)Request::post('license_custom', ''))), 0, 500);
        }
        if (!self::hasColumn($table, 'license')) {
            unset($out['license'], $out['license_custom']);
        }
        return $out;
    }

    /**
     * 违禁词拦截：命中则直接报错，并告诉用户具体是哪些词
     *
     * 前端编辑器会用同一份词表做实时检测并高亮，这里做最终兜底。
     */
    private static function guardForbidden(string $text): void
    {
        if (!\App\Models\Forbidden::enabled()) {
            return;
        }
        $hits = \App\Models\Forbidden::check($text);
        if (!$hits) {
            return;
        }
        \App\Models\Forbidden::countHits($hits);
        $list = implode('、', array_slice($hits, 0, 10));
        $msg = '内容包含违禁词，无法发布：' . $list;
        if (count($hits) > 10) {
            $msg .= ' 等 ' . count($hits) . ' 个词';
        }
        if (\App\Core\Request::isAjax()) {
            \App\Core\Response::fail($msg, ['forbidden' => $hits]);
        }
        \App\Core\Session::flash('forbidden_words', $hits);
        \App\Core\Session::flash('error', $msg);
        \App\Core\Response::back();
    }

    /**
     * 读取并解析正文（支持 富文本 / Markdown 双模式）
     *
     * @return array [html, text, md, format]
     */
    private static function readContent(): array
    {
        $raw = (string)Request::post('content', '');
        $format = (string)Request::post('content_format', '') === 'markdown' ? 'markdown' : 'rich';
        [$html, $text, $md] = Content::process($raw, $format);
        return [$html, $text, $md, $format];
    }

    public function createForm(): string
    {
        $this->requireLogin();
        $forumId = Request::int('forum', 0);
        $forums = Forum::tree();
        $user = $this->user();
        foreach ($forums as &$cat) {
            foreach ($cat['children'] as &$b) {
                $b['can_post'] = Forum::canPost($b, $user);
            }
        }
        return $this->render('thread/edit', [
            'title' => '发布主题',
            // 发帖页侧栏显示「目录」面板（由编辑器内容实时生成），不再显示关于 / 日历等模块
            'show_toc' => true,
            // 侧栏「发布主题」按钮在编辑页改为直接提交当前表单，而不是跳转到新建页
            'editing_page' => 'create',
            'forums' => $forums,
            'forum_id' => $forumId,
            'thread' => null,
            'tags' => Tag::all(50),
            'max_tags' => (int)Settings::get('max_tags', 5),
            'allow_user_tag' => (int)Settings::get('allow_user_tag', 0),
            'stats' => Stats::forum(),
            'hot_tags' => Tag::hot(15),
            'new_users' => \App\Models\User::newest(8),
            'latest_threads' => Thread::latest(8),
        ]);
    }

    public function store(): void
    {
        $this->requireLogin();
        $user = $this->user();
        $forumId = Request::int('forum_id');
        $title = trim((string)Request::post('title', ''));
        $raw = (string)Request::post('content', '');
        $forum = Forum::find($forumId);

        if (mb_strlen($title) < 2 || mb_strlen($title) > 100) {
            $this->backWithErrors(['title' => '标题长度需在 2 - 100 字之间']);
        }
        if (!$forum) {
            $this->backWithErrors(['forum_id' => '请选择板块']);
        }
        if (!Forum::canPost($forum, $user)) {
            $this->back('你没有在该板块发帖的权限');
        }

        [$html, $text, $md, $format] = self::readContent();
        if (mb_strlen($text) < 2) {
            $this->backWithErrors(['content' => '内容太短了']);
        }
        // 违禁词：命中即禁止发布（区别于敏感词，敏感词只是替换 / 转审核）
        self::guardForbidden($title . ' ' . $text);
        if (Security::hasSensitive($title . $text) && (int)Settings::get('thread_audit', 0) === 0) {
            // 命中敏感词按发帖审核处理
            $status = 0;
        } else {
            $status = (int)Settings::get('thread_audit', 0) ? 0 : 1;
        }

        $tagNames = array_filter(array_map('trim', explode(',', (string)Request::post('tags', ''))));
        $allowCreateTag = (int)Settings::get('allow_user_tag', 0) === 1 || \App\Core\Auth::isAdmin();
        $tagIds = Tag::resolve(array_slice($tagNames, 0, (int)Settings::get('max_tags', 5)), $allowCreateTag);

        $id = Thread::create([
            'forum_id' => $forumId,
            'user_id' => (int)$user['id'],
            'username' => (string)$user['username'],
            'title' => $title,
            'content' => Content::renderMentions(Content::highlight(Content::lazyImages($html))),
            'content_text' => $text,
            'status' => $status,
            'anonymous' => Request::bool('anonymous') ? 1 : 0,
            'attachment_fee' => (float)Request::post('attachment_fee', 0),
            'last_reply_user' => (string)$user['username'],
            'last_reply_user_id' => (int)$user['id'],
        ] + self::mdFields('threads', $md, $format) + self::licenseFields('threads'));
        Thread::setTags($id, $tagIds);
        Content::bindAttachments($html, $id, (int)$user['id']);

        Currency::reward((int)$user['id'], 'thread');
        Level::addExp((int)$user['id'], Level::EXP_THREAD);
        Stats::clearCache();

        if ($status === 0) {
            Session::flash('success', '发布成功，等待管理员审核');
        } else {
            Session::flash('success', '发布成功');
        }
        \redirect('thread/' . $id);
    }

    public function editForm(int $id): string
    {
        $this->requireLogin();
        $thread = Thread::find($id);
        if (!$thread) {
            \App\Core\Router::notFound();
            exit;
        }
        $user = $this->user();
        if ((int)$thread['user_id'] !== (int)$user['id'] && !\App\Core\Auth::isAdmin()) {
            $this->back('无权编辑');
        }
        $forums = Forum::tree();
        foreach ($forums as &$cat) {
            foreach ($cat['children'] as &$b) {
                $b['can_post'] = Forum::canPost($b, $user);
            }
        }
        $currentTags = Thread::tags($id);
        return $this->render('thread/edit', [
            'title' => '编辑主题',
            'show_toc' => true,
            'editing_page' => 'edit',
            'forums' => $forums,
            'forum_id' => (int)$thread['forum_id'],
            'thread' => $thread,
            'current_tags' => $currentTags,
            'tags' => Tag::all(50),
            'max_tags' => (int)Settings::get('max_tags', 5),
            'allow_user_tag' => (int)Settings::get('allow_user_tag', 0),
            'stats' => Stats::forum(),
            'hot_tags' => Tag::hot(15),
            'new_users' => \App\Models\User::newest(8),
            'latest_threads' => Thread::latest(8),
        ]);
    }

    public function update(int $id): void
    {
        $this->requireLogin();
        $thread = Thread::find($id);
        if (!$thread) {
            \App\Core\Router::notFound();
            exit;
        }
        $user = $this->user();
        if ((int)$thread['user_id'] !== (int)$user['id'] && !\App\Core\Auth::isAdmin()) {
            $this->back('无权编辑');
        }
        $title = trim((string)Request::post('title', ''));
        $raw = (string)Request::post('content', '');
        [$html, $text, $md, $format] = self::readContent();
        if (mb_strlen($title) < 2 || mb_strlen($text) < 2) {
            $this->backWithErrors(['title' => '标题或内容不合法']);
        }

        // 违禁词：命中即禁止发布
        self::guardForbidden($title . ' ' . $text);
        $base = [
            'title' => $title,
            'content' => Content::renderMentions(Content::highlight(Content::lazyImages($html))),
            'content_text' => $text,
            'forum_id' => Request::int('forum_id', (int)$thread['forum_id']),
        ] + self::mdFields('threads', $md, $format) + self::licenseFields('threads');
        try {
            // 记录编辑痕迹（前台帖子正文上方显示"XX 编辑于 …"）
            Thread::update($id, $base + [
                'edited_at' => date('Y-m-d H:i:s'),
                'edited_by' => (int)$user['id'],
                'edited_count' => (int)($thread['edited_count'] ?? 0) + 1,
            ]);
        } catch (\Throwable $e) {
            // 表结构尚未升级（缺少 edited_* 字段）时回退，不影响正常编辑
            Thread::update($id, $base);
        }
        Database::update('posts', [
            'content' => Content::renderMentions(Content::highlight(Content::lazyImages($html))),
            'content_text' => $text,
        ], 'thread_id = ? AND is_first = 1', ['tid' => $id]);

        $tagNames = array_filter(array_map('trim', explode(',', (string)Request::post('tags', ''))));
        $tagIds = Tag::resolve(array_slice($tagNames, 0, (int)Settings::get('max_tags', 5)), \App\Core\Auth::isAdmin());
        Thread::setTags($id, $tagIds);
        Content::bindAttachments($html, $id, (int)$user['id']);

        Session::flash('success', '已保存');
        \redirect('thread/' . $id);
    }

    public function reply(int $id): void
    {
        $this->requireLogin();
        $thread = Thread::find($id);
        if (!$thread) {
            \App\Core\Router::notFound();
            exit;
        }
        if ((int)$thread['is_closed']) {
            $this->fail('该帖已关闭，无法回复');
        }
        $user = $this->user();
        $forum = Forum::find((int)$thread['forum_id']);
        if (!Forum::canPost($forum, $user)) {
            $this->fail('没有回复权限');
        }
        [$html, $text, $md, $format] = self::readContent();
        if (mb_strlen($text) < 1) {
            $this->fail('回复内容不能为空');
        }
        // 违禁词：命中即禁止发布
        self::guardForbidden($text);
        $status = (int)Settings::get('thread_audit', 0) ? 0 : 1;
        $postId = Post::create([
            'thread_id' => $id,
            'user_id' => (int)$user['id'],
            'username' => (string)$user['username'],
            'content' => Content::renderMentions(Content::highlight(Content::lazyImages($html))),
            'content_text' => $text,
            'status' => $status,
            'anonymous' => Request::bool('anonymous') ? 1 : 0,
            'reply_to' => Request::int('reply_to'),
        ] + self::mdFields('posts', $md, $format));

        Content::mentions($text, (int)$user['id'], 'post', $postId, url('thread/' . $id . '#post-' . $postId));
        if ((int)$thread['user_id'] !== (int)$user['id']) {
            Notification::send((int)$thread['user_id'], 'reply', '你的帖子有了新回复',
                mb_substr($text, 0, 60), url('thread/' . $id . '#post-' . $postId), (int)$user['id']);
        }
        if (Request::int('reply_to') > 0) {
            $p = Post::find(Request::int('reply_to'));
            if ($p) {
                Notification::send((int)$p['user_id'], 'reply', '有人回复了你的楼层',
                    mb_substr($text, 0, 60), url('thread/' . $id . '#post-' . $postId), (int)$user['id']);
            }
        }

        Currency::reward((int)$user['id'], 'post');
        Level::addExp((int)$user['id'], Level::EXP_POST);
        Stats::clearCache();

        $target = 'thread/' . $id . '#post-' . $postId;
        // 异步提交返回 JSON；普通表单提交则提示后跳回帖子页，避免页面直接显示 JSON
        if (Request::isAjax()) {
            $this->success(['post_id' => $postId, 'url' => url($target)], '回复成功');
        }
        if ($status === 0) {
            Session::flash('success', '回复成功，等待管理员审核');
        } else {
            Session::flash('success', '回复成功');
        }
        \redirect($target);
    }

    public function delete(int $id): void
    {
        $this->requireLogin();
        $thread = Thread::find($id);
        if (!$thread) {
            $this->fail('帖子不存在');
        }
        $user = $this->user();
        if ((int)$thread['user_id'] !== (int)$user['id'] && !\App\Core\Auth::isAdmin()) {
            $this->fail('无权删除');
        }
        Thread::delete($id);
        Stats::clearCache();
        // 帖子已删除，非 Ajax 提交时跳回首页（否则会停在已失效的详情页）
        if (!Request::isAjax()) {
            Session::flash('success', '已删除');
            \redirect('');
        }
        $this->success(null, '已删除');
    }

    public function deletePost(int $id): void
    {
        $this->requireLogin();
        $post = Post::find($id);
        if (!$post) {
            $this->fail('回复不存在');
        }
        $user = $this->user();
        $thread = Thread::find((int)$post['thread_id']);
        $isOwner = (int)$post['user_id'] === (int)$user['id'];
        $isThreadOwner = $thread && (int)$thread['user_id'] === (int)$user['id'];
        if (!$isOwner && !$isThreadOwner && !\App\Core\Auth::isAdmin()) {
            $this->fail('无权删除');
        }
        if ((int)$post['is_first'] === 1) {
            Post::softDelete($id);
            Thread::softDelete((int)$post['thread_id']);
            $back = '';
        } else {
            Post::softDelete($id);
            $back = 'thread/' . (int)$post['thread_id'];
        }
        if (!Request::isAjax()) {
            Session::flash('success', '已删除');
            \redirect($back);
        }
        $this->success(null, '已删除');
    }

    public function report(int $id): void
    {
        $this->requireLogin();
        $type = (string)Request::post('type', 'thread');
        $reason = (string)Request::post('reason', '');
        \App\Models\Report::create((int)$this->userId(), $type, $id, $reason, (string)Request::post('detail', ''));
        $this->success(null, '举报已提交，感谢你的反馈');
    }

    /**
     * 自动保存草稿
     */
    public function saveDraft(): void
    {
        $this->requireLogin();
        $uid = (int)$this->userId();
        $threadId = Request::int('thread_id');
        $forumId = Request::int('forum_id');
        $title = (string)Request::post('title', '');
        $content = (string)Request::post('content', '');
        if ($title === '' && $content === '') {
            $this->fail('内容为空');
        }
        $exists = Database::fetch(
            'SELECT id FROM ' . Database::table('drafts') . ' WHERE user_id = ? AND thread_id = ? AND forum_id = ?',
            [$uid, $threadId, $forumId]
        );
        if ($exists) {
            Database::update('drafts', ['title' => $title, 'content' => $content, 'tags' => (string)Request::post('tags', '')],
                'id = ?', ['id' => $exists['id']]);
            $this->success(['id' => $exists['id']], '草稿已更新');
        }
        $id = Database::insert('drafts', [
            'user_id' => $uid, 'thread_id' => $threadId, 'forum_id' => $forumId,
            'title' => $title, 'content' => $content, 'tags' => (string)Request::post('tags', ''),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $this->success(['id' => $id], '草稿已保存');
    }
}
