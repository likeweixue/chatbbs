<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Settings;
use App\Models\Like;
use App\Models\Notification;

/**
 * 异步接口：点赞 / 关注 / 推荐 / 通知计数
 */
class ApiController extends BaseController
{
    public function like(): void
    {
        $this->requireLoginAjax();
        $type = (string)Request::post('type', 'thread');
        if (!in_array($type, ['thread', 'post', 'say'], true)) {
            $this->fail('类型错误');
        }
        $id = Request::int('id');
        if ($id <= 0) {
            $this->fail('参数错误');
        }
        $res = Like::toggle($this->userId(), $type, $id);
        if ($res['liked'] && $type === 'thread') {
            $t = \App\Models\Thread::find($id);
            if ($t && (int)$t['user_id'] !== $this->userId()) {
                Notification::send((int)$t['user_id'], 'like', '你的帖子收到一个赞',
                    mb_substr((string)$t['title'], 0, 50), url('thread/' . $id), $this->userId());
            }
        }
        if ($res['liked']) {
            \App\Services\Level::addExp($this->userId(), 1);
        }
        $count = (int)\App\Core\Database::value(
            'SELECT likes FROM ' . \App\Core\Database::table($type === 'thread' ? 'threads' : ($type === 'post' ? 'posts' : 'says'))
            . ' WHERE id = ?', [$id], 0
        );
        $this->success(['liked' => $res['liked'], 'count' => $count], $res['msg']);
    }

    /**
     * 我的图片附件列表（编辑器"从附件库选择"使用）
     */
    /**
     * 我的附件列表（编辑器附件库）
     * space: shared / private / all（默认 all）
     * type: image / file / all（默认 all）
     */
    /**
     * 违禁词列表（供编辑器实时检测提示；未启用则返回空数组）
     */
    public function forbiddenWords(): void
    {
        if (!Auth::check()) {
            $this->fail('请先登录', null, 401);
        }
        if (!(int)Settings::get('forbidden_words_enable', 1)) {
            $this->success(['words' => []]);
        }
        $this->success(['words' => \App\Models\Forbidden::words()]);
    }

    public function myFiles(): void
    {
        if (!Auth::check()) {
            $this->fail('请先登录', null, 401);
        }
        $space = (string)Request::get('space', 'all');
        $type = (string)Request::get('type', 'all');
        $where = 'user_id = ?';
        $params = [$this->userId()];
        if (in_array($space, ['shared', 'private'], true)) {
            $where .= ' AND space = ?';
            $params[] = $space;
        }
        if ($type === 'image') {
            $where .= ' AND is_image = 1';
        }
        $rows = \App\Core\Database::fetchAll(
            'SELECT id, name, path, space, ext, size, is_image FROM ' . \App\Core\Database::table('attachments')
            . ' WHERE ' . $where . ' ORDER BY id DESC LIMIT 120',
            $params
        );
        $items = [];
        foreach ($rows as $r) {
            $items[] = [
                'id' => (int)$r['id'],
                'name' => (string)$r['name'],
                'ext' => (string)$r['ext'],
                'size' => (int)$r['size'],
                'space' => (string)$r['space'],
                'is_image' => (int)$r['is_image'],
                'url' => site_base() . '/data/uploads/' . $r['space'] . '/' . $r['path'],
                'download' => url('attachment/' . (int)$r['id'] . '/download'),
            ];
        }
        $this->success(['items' => $items]);
    }

    /**
     * 兼容别名：只返回图片（编辑器「插入图片 → 附件库」早期使用）
     */
    public function myImages(): void
    {
        $_GET['type'] = 'image';
        $this->myFiles();
    }

    public function counters(): void
    {
        $uid = $this->userId();
        $this->success([
            'notifications' => $uid ? Notification::unreadCount($uid) : 0,
            'messages' => $uid ? \App\Models\Message::unreadTotal($uid) : 0,
        ]);
    }

    public function uploadImage(): void
    {
        if (!Auth::check()) {
            $this->fail('请先登录', null, 401);
        }
        $file = Request::file('file') ?: Request::file('upload');
        if (!$file) {
            $this->fail('没有收到文件');
        }
        // 空间：shared（共享）/ private（私人）
        $space = (string)Request::post('space', 'shared') === 'private' ? 'private' : 'shared';
        [$ok, $res] = \App\Core\Upload::save($file, $space, $this->userId());
        if (!$ok) {
            $this->fail((string)$res);
        }
        $id = \App\Models\Attachment::create([
            'user_id' => $this->userId(),
            'name' => $res['name'],
            'path' => $res['path'],
            'space' => $space,
            'ext' => $res['ext'],
            'mime' => $res['mime'],
            'size' => $res['size'],
            'is_image' => $res['is_image'],
        ]);
        // 编辑器需要的返回格式（兼容 CKEditor / 自定义）
        // is_image=0 时前端必须插入"下载链接"而不是 <img>，否则会渲染成裂图
        $this->success([
            'id' => $id,
            'url' => $res['url'],
            'uploaded' => 1,
            'fileName' => $res['name'],
            'name' => $res['name'],
            'size' => (int)$res['size'],
            'ext' => (string)$res['ext'],
            'is_image' => (int)$res['is_image'],
            'download' => url('attachment/' . $id . '/download'),
        ], '上传成功');
    }

    /**
     * Markdown 源码渲染：
     * 编辑器切换到「富文本」或点击「预览排版」时使用，保证前台解析结果一致
     */
    public function markdownPreview(): void
    {
        $md = (string)Request::post('content', '');
        if (trim($md) === '') {
            $this->success(['html' => '']);
        }
        // Markdown 解析器已对内容做 HTML 转义，输出可直接插入编辑区
        $this->success(['html' => \App\Services\Markdown::render($md)]);
    }

    public function mentions(): void
    {
        $q = (string)Request::get('q', '');
        $users = \App\Models\User::find(0) ? [] : [];
        $list = \App\Core\Database::fetchAll(
            'SELECT id,username,nickname,avatar FROM ' . \App\Core\Database::table('users')
            . ' WHERE status = 1 AND (username LIKE ? OR nickname LIKE ?) LIMIT 10',
            ['%' . $q . '%', '%' . $q . '%']
        );
        $this->success(array_map(fn($u) => [
            'id' => (int)$u['id'],
            'username' => $u['username'],
            'nickname' => $u['nickname'],
            'avatar' => \avatar_url($u),
        ], $list));
    }

    public function notifications(): void
    {
        $uid = $this->userId();
        if (!$uid) {
            $this->success(['items' => [], 'count' => 0]);
        }
        $res = Notification::list($uid, 1, 8, true);
        $items = array_map(function ($n) {
            $n['icon'] = NotifyController::typeIcon((string)$n['type']);
            $n['time'] = \time_ago($n['created_at']);
            return $n;
        }, $res['items']);
        $this->success(['items' => $items, 'count' => Notification::unreadCount($uid)]);
    }

    private function requireLoginAjax(): void
    {
        if (!Auth::check()) {
            $this->fail('请先登录', ['login' => true], 401);
        }
    }
}
