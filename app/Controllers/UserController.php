<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Session;
use App\Core\Settings;
use App\Core\Security;
use App\Core\Upload;
use App\Core\View;
use App\Models\Guestbook;
use App\Models\Message;
use App\Models\Notification;
use App\Models\Post;
use App\Models\Say;
use App\Models\Thread;
use App\Models\User;
use App\Services\Level;
use App\Services\Stats;

class UserController extends BaseController
{
    /**
     * 个人主页 /u/{id} 或 /user/{id}
     */
    public function profile(string $id): string
    {
        $user = is_numeric($id) ? User::find((int)$id) : User::findByUsername($id);
        if (!$user) {
            \App\Core\Router::notFound();
            exit;
        }
        $user = User::withMeta($user);
        $viewer = $this->user();
        $uid = (int)$user['id'];

        return $this->render('user/profile', [
            'title' => (string)$user['nickname'],
            'profile' => $user,
            'stats' => User::stats($uid),
            'level_progress' => Level::progress($user),
            'threads' => Thread::list(['user_id' => $uid, 'per_page' => 8])['items'],
            'posts' => Post::listByUser($uid, 8),
            'says' => Say::list(1, 5, $uid)['items'],
            'is_self' => $viewer && (int)$viewer['id'] === $uid,
            'is_following' => $viewer ? User::isFollowing((int)$viewer['id'], $uid) : false,
            'is_friend' => $viewer ? User::isFriend((int)$viewer['id'], $uid) : false,
            'followers' => Message::followers($uid),
            'following' => Message::following($uid),
            'stats_site' => Stats::forum(),
            'hot_tags' => \App\Models\Tag::hot(15),
            'new_users' => User::newest(8),
            'latest_threads' => Thread::latest(8),
            'guestbook' => Guestbook::list($uid, 30),
            'guestbook_count' => Guestbook::count($uid),
            'guestbook_enabled' => (int)($user['guestbook_enable'] ?? 1) === 1,
        ]);
    }

    /**
     * 用户中心首页
     */
    public function home(): string
    {
        $this->requireLogin();
        return \redirect('user/' . $this->userId());
    }

    public function settings(): string
    {
        $this->requireLogin();
        $user = User::withMeta(User::find($this->userId()));
        return $this->render('user/settings', [
            'title' => '账号设置',
            'profile' => $user,
            'tab' => (string)Request::get('tab', 'profile'),
            'lan_discover' => \App\Models\Lan::discoverOn((int)$user['id']),
            'stats_site' => Stats::forum(),
            'hot_tags' => \App\Models\Tag::hot(15),
            'new_users' => User::newest(8),
            'latest_threads' => Thread::latest(8),
        ]);
    }

    public function updateProfile(): void
    {
        $this->requireLogin();
        $uid = $this->userId();
        $data = [
            'nickname' => trim((string)Request::post('nickname', '')),
            'signature' => mb_substr(trim((string)Request::post('signature', '')), 0, 100),
            'bio' => mb_substr(trim((string)Request::post('bio', '')), 0, 300),
        ];
        if ($data['nickname'] === '') {
            $this->backWithErrors(['nickname' => '昵称不能为空']);
        }
        $privacy = json_encode([
            'profile' => Request::bool('privacy_profile') ? 1 : 0,
            'thread' => Request::bool('privacy_thread') ? 1 : 0,
            'pm' => Request::bool('privacy_pm') ? 1 : 0,
        ], JSON_UNESCAPED_UNICODE);
        $data['privacy'] = $privacy;
        $data['theme_dark'] = Request::bool('theme_dark') ? 1 : 0;
        if ((string)Request::post('guestbook_settings', '') !== '') {
            $data['guestbook_enable'] = Request::bool('guestbook_enable') ? 1 : 0;
        }
        User::update($uid, $data);
        // 「附近的人」开关（users.lan_discover，老站点可能还没这个字段）
        \App\Models\Lan::setDiscover($uid, Request::bool('lan_discover'));
        Session::flash('success', '资料已更新');
        $this->back();
    }

    public function updateAvatar(): void
    {
        $this->requireLogin();
        $uid = $this->userId();
        $file = Request::file('avatar');
        if (!$file) {
            $this->fail('请选择图片');
        }
        [$ok, $res] = Upload::save($file, 'avatars', $uid);
        if (!$ok) {
            $this->fail((string)$res);
        }
        $user = User::find($uid);
        if (!empty($user['avatar']) && !preg_match('#^https?://#i', (string)$user['avatar'])) {
            Upload::delete('avatars', (string)$user['avatar']);
        }
        User::update($uid, ['avatar' => $res['path']]);
        $this->success(['url' => Upload::url('avatars', $res['path'])], '头像已更新');
    }

    /**
     * 设置头像为图床 / 外链地址（不下载到服务器）
     */
    public function updateAvatarUrl(): void
    {
        $this->requireLogin();
        $uid = $this->userId();
        $url = trim((string)Request::post('avatar_url', ''));
        if ($url === '' || mb_strlen($url) > 240 || !preg_match('#^https?://#i', $url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            $this->backWithErrors(['avatar_url' => '请填写有效的 http / https 图片地址']);
        }
        $user = User::find($uid);
        if (!empty($user['avatar']) && !preg_match('#^https?://#i', (string)$user['avatar'])) {
            Upload::delete('avatars', (string)$user['avatar']);
        }
        User::update($uid, ['avatar' => $url]);
        Session::flash('success', '头像链接已更新');
        $this->back();
    }

    /**
     * 个人主页留言板开关（仅本人）
     */
    public function updateGuestbookSetting(): void
    {
        $this->requireLogin();
        User::update($this->userId(), [
            'guestbook_enable' => Request::bool('guestbook_enable') ? 1 : 0,
        ]);
        Session::flash('success', '留言板设置已更新');
        $this->back();
    }

    /**
     * 在个人主页留言
     */
    public function guestbookAdd(int $id): void
    {
        $this->requireLogin();
        $profileUser = User::find($id);
        if (!$profileUser) {
            $this->back('用户不存在');
        }
        $uid = $this->userId();
        $enabled = (int)($profileUser['guestbook_enable'] ?? 1) === 1;
        if (!$enabled && $uid !== $id && !Auth::isAdmin()) {
            $this->back('该用户已关闭留言板');
        }
        $content = trim((string)Request::post('content', ''));
        if ($content === '') {
            $this->back('留言内容不能为空');
        }
        if (mb_strlen($content) > 300) {
            $this->back('留言最多 300 字');
        }
        Guestbook::add($id, $uid, $content);
        if ($uid !== $id) {
            Notification::send(
                $id,
                'guestbook',
                '你的主页收到一条新留言',
                mb_substr($content, 0, 60),
                url('user/' . $id . '#guestbook'),
                $uid
            );
        }
        Session::flash('success', '留言已发布');
        \redirect('user/' . $id . '#guestbook');
    }

    /**
     * 删除留言：留言作者、主页主人或管理员
     */
    public function guestbookDelete(int $id): void
    {
        $this->requireLogin();
        $row = Guestbook::find($id);
        if (!$row) {
            $this->back('留言不存在');
        }
        $uid = $this->userId();
        if ($uid !== (int)$row['profile_user_id'] && $uid !== (int)$row['user_id'] && !Auth::isAdmin()) {
            $this->back('无权删除这条留言');
        }
        $profileId = (int)$row['profile_user_id'];
        Guestbook::delete($id);
        Session::flash('success', '留言已删除');
        \redirect('user/' . $profileId . '#guestbook');
    }

    public function updatePassword(): void
    {
        $this->requireLogin();
        $uid = $this->userId();
        $old = (string)Request::post('old_password', '');
        $new = (string)Request::post('new_password', '');
        $confirm = (string)Request::post('confirm_password', '');
        $user = User::find($uid);
        if (!password_verify($old, (string)$user['password'])) {
            $this->backWithErrors(['old_password' => '当前密码不正确']);
        }
        if (strlen($new) < 6) {
            $this->backWithErrors(['new_password' => '新密码至少 6 位']);
        }
        if ($new !== $confirm) {
            $this->backWithErrors(['confirm_password' => '两次输入不一致']);
        }
        User::update($uid, ['password' => password_hash($new, PASSWORD_DEFAULT)]);
        Session::flash('success', '密码修改成功，请重新登录');
        Auth::logout();
        \redirect('login');
    }

    /**
     * 我的帖子 / 回复 / 收藏 / 草稿
     */
    public function threads(): string
    {
        $this->requireLogin();
        $uid = $this->userId();
        $page = max(1, Request::int('page', 1));
        $result = Thread::list(['user_id' => $uid, 'page' => $page, 'per_page' => 20]);
        return $this->render('user/threads', [
            'title' => '我的帖子',
            'threads' => $result['items'],
            'pager' => View::paginate($result['total'], 20, $page, url('user/threads')),
            'stats_site' => Stats::forum(),
            'hot_tags' => \App\Models\Tag::hot(15),
            'new_users' => User::newest(8),
            'latest_threads' => Thread::latest(8),
        ]);
    }

    public function drafts(): string
    {
        $this->requireLogin();
        $uid = $this->userId();
        $items = Database::fetchAll(
            'SELECT * FROM ' . Database::table('drafts') . ' WHERE user_id = ? ORDER BY updated_at DESC',
            [$uid]
        );
        return $this->render('user/drafts', [
            'title' => '草稿箱',
            'drafts' => $items,
            'stats_site' => Stats::forum(),
            'hot_tags' => \App\Models\Tag::hot(15),
            'new_users' => User::newest(8),
            'latest_threads' => Thread::latest(8),
        ]);
    }

    public function deleteDraft(int $id): void
    {
        $this->requireLogin();
        Database::delete('drafts', 'id = ? AND user_id = ?', ['id' => $id, 'uid' => $this->userId()]);
        $this->success(null, '已删除');
    }

    public function friends(): string
    {
        $this->requireLogin();
        $uid = $this->userId();
        return $this->render('user/friends', [
            'title' => '好友与关注',
            'friends' => Message::friends($uid),
            'followers' => Message::followers($uid),
            'following' => Message::following($uid),
            'blacklist' => Message::blacklist($uid),
            'stats_site' => Stats::forum(),
            'hot_tags' => \App\Models\Tag::hot(15),
            'new_users' => User::newest(8),
            'latest_threads' => Thread::latest(8),
        ]);
    }

    public function attachments(): string
    {
        $this->requireLogin();
        $uid = $this->userId();
        $space = (string)Request::get('space', 'shared');
        $page = max(1, Request::int('page', 1));
        $result = \App\Models\Attachment::list($uid, $space === 'private' ? 'private' : 'shared', $page, 20, (string)Request::get('kw', ''));
        $usage = \App\Models\Attachment::usage($uid);
        return $this->render('user/attachments', [
            'title' => '我的附件库',
            'space' => $space,
            'items' => $result['items'],
            'total' => $result['total'],
            'usage' => $usage,
            'pager' => View::paginate($result['total'], 20, $page, url('user/attachments?space=' . $space)),
            'stats_site' => Stats::forum(),
            'hot_tags' => \App\Models\Tag::hot(15),
            'new_users' => User::newest(8),
            'latest_threads' => Thread::latest(8),
        ]);
    }

    public function uploadAttachment(): void
    {
        $this->requireLogin();
        if (!Auth::can('attachment.upload')) {
            $this->fail('没有上传权限');
        }
        $file = Request::file('file');
        if (!$file) {
            $this->fail('没有收到文件');
        }
        $space = (string)Request::post('space', 'shared');
        if (!in_array($space, ['shared', 'private'], true)) {
            $space = 'shared';
        }
        [$ok, $res] = Upload::save($file, $space, $this->userId());
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
            'download_rule' => (string)Request::post('download_rule', 'free'),
            'fee' => (float)Request::post('fee', 0),
        ]);
        $this->success(['id' => $id, 'url' => $res['url'], 'name' => $res['name'], 'is_image' => $res['is_image']], '上传成功');
    }

    public function deleteAttachment(int $id): void
    {
        $this->requireLogin();
        $att = \App\Models\Attachment::find($id);
        if (!$att) {
            $this->fail('附件不存在');
        }
        if ((int)$att['user_id'] !== $this->userId() && !Auth::isAdmin()) {
            $this->fail('无权删除');
        }
        \App\Models\Attachment::delete($id);
        $this->success(null, '已删除');
    }
}
