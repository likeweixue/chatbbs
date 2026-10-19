<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Security;
use App\Core\Settings;
use App\Models\ChatGroup;
use App\Models\Lan;
use App\Models\Message;
use App\Models\Tag;
use App\Models\Thread;
use App\Models\User;
use App\Services\Stats;

/**
 * 聊天：单聊（原私信）+ 群聊 + 附近的人
 * 消息采用轮询拉取（零依赖，不引入 WebSocket / 长连接）
 */
class ChatController extends BaseController
{
    /* ==================== 页面 ==================== */

    public function index(): string
    {
        $this->requireLogin();
        $uid = $this->userId();
        $ip = client_ip();
        Lan::touchIp($uid, $ip);

        $with = Request::int('user');
        $groupId = Request::int('group');

        return $this->render('chat/index', [
            'title' => '聊天',
            'conversations' => Message::conversations($uid),
            'with' => $with > 0 ? User::find($with) : null,
            'messages' => $with > 0 ? Message::thread($uid, $with) : [],
            'groups' => ChatGroup::myGroups($uid),
            'group' => $groupId > 0 && ChatGroup::isMember($groupId, $uid) ? ChatGroup::find($groupId) : null,
            'group_messages' => [],
            'group_members' => [],
            'nearby' => Lan::nearby($uid, $ip),
            'open_groups' => Lan::openGroups($uid),
            'my_ip' => $ip,
            'my_segment' => Lan::segment($ip),
            'discover_on' => Lan::discoverOn($uid),
            'lan_enable' => Lan::enabled(),
            'poll_interval' => max(2000, (int)Settings::get('chat_poll_interval', 5000)),
            'hide_sidebar' => true,
            'stats_site' => Stats::forum(),
            'hot_tags' => Tag::hot(15),
            'new_users' => User::newest(8),
            'latest_threads' => Thread::latest(8),
        ]);
    }

    /**
     * 群聊会话页
     */
    public function group(int $id): string
    {
        $this->requireLogin();
        $uid = $this->userId();
        $group = ChatGroup::find($id);
        if (!$group || !ChatGroup::isMember($id, $uid)) {
            $this->fail('群不存在或你还不是成员');
        }
        ChatGroup::markRead($id, $uid);
        $ip = client_ip();
        Lan::touchIp($uid, $ip);

        return $this->render('chat/index', [
            'title' => '聊天 · ' . (string)$group['name'],
            'conversations' => Message::conversations($uid),
            'with' => null,
            'messages' => [],
            'groups' => ChatGroup::myGroups($uid),
            'group' => $group,
            'group_messages' => ChatGroup::messages($id, 0, 100),
            'group_members' => ChatGroup::members($id),
            'nearby' => Lan::nearby($uid, $ip),
            'open_groups' => Lan::openGroups($uid),
            'my_ip' => $ip,
            'my_segment' => Lan::segment($ip),
            'discover_on' => Lan::discoverOn($uid),
            'lan_enable' => Lan::enabled(),
            'poll_interval' => max(2000, (int)Settings::get('chat_poll_interval', 5000)),
            'hide_sidebar' => true,
            'stats_site' => Stats::forum(),
            'hot_tags' => Tag::hot(15),
            'new_users' => User::newest(8),
            'latest_threads' => Thread::latest(8),
        ]);
    }

    /* ==================== 动作 ==================== */

    /**
     * 发送消息：单聊（to_id）或群聊（group_id）
     */
    public function send(): void
    {
        $this->requireLogin();
        $uid = $this->userId();
        $content = trim(Security::text((string)Request::post('content', ''), 2000));
        if ($content === '') {
            $this->fail('消息不能为空');
        }

        $groupId = Request::int('group_id');
        if ($groupId > 0) {
            if (!ChatGroup::isMember($groupId, $uid)) {
                $this->fail('你不是该群成员');
            }
            if (!(int)Settings::get('chat_enable', 1)) {
                $this->fail('聊天功能已关闭');
            }
            $id = ChatGroup::send($groupId, $uid, $content);
            if (!$id) {
                $this->fail('发送失败');
            }
            ChatGroup::markRead($groupId, $uid);
            $this->success(['id' => $id, 'scope' => 'group', 'group_id' => $groupId], '已发送');
        }

        $toId = Request::int('to_id');
        if ($toId <= 0) {
            $this->fail('参数不完整');
        }
        if ($toId === $uid) {
            $this->fail('不能给自己发消息');
        }
        if (!User::find($toId)) {
            $this->fail('用户不存在');
        }
        $id = Message::send($uid, $toId, $content);
        if (!$id) {
            $this->fail('发送失败，对方可能已屏蔽你');
        }
        $this->success(['id' => $id, 'scope' => 'pm', 'to_id' => $toId], '已发送');
    }

    /**
     * 建群
     */
    public function createGroup(): void
    {
        $this->requireLogin();
        if (!(int)Settings::get('chat_enable', 1)) {
            $this->fail('聊天功能已关闭');
        }
        $uid = $this->userId();
        $name = trim(Security::text((string)Request::post('name', ''), 60));
        if ($name === '') {
            $this->fail('请填写群名称');
        }
        $notice = trim(Security::text((string)Request::post('notice', ''), 255));
        $isOpen = Request::bool('is_open') ? 1 : 0;

        $mine = ChatGroup::myGroups($uid);
        if (count($mine) >= max(1, (int)Settings::get('chat_group_max', 30))) {
            $this->fail('你创建的群已达上限');
        }
        $gid = ChatGroup::create($uid, $name, $notice, $isOpen);
        if (!$gid) {
            $this->fail('创建失败');
        }
        $this->success(['id' => $gid, 'url' => url('chat/group/' . $gid)], '群聊已创建');
    }

    public function joinGroup(int $id): void
    {
        $this->requireLogin();
        $uid = $this->userId();
        $group = ChatGroup::find($id);
        if (!$group) {
            $this->fail('群不存在');
        }
        if (ChatGroup::isMember($id, $uid)) {
            $this->success(['id' => $id, 'url' => url('chat/group/' . $id)], '你已在群里');
        }
        if (!(int)$group['is_open']) {
            $this->fail('该群未公开，无法直接加入');
        }
        if (!ChatGroup::addMember($id, $uid)) {
            $this->fail('加入失败');
        }
        $this->success(['id' => $id, 'url' => url('chat/group/' . $id)], '已加入群聊');
    }

    public function leaveGroup(int $id): void
    {
        $this->requireLogin();
        $uid = $this->userId();
        $group = ChatGroup::find($id);
        if ($group && (int)$group['owner_id'] === $uid) {
            $this->fail('群主不能退群，请先转让或解散');
        }
        ChatGroup::removeMember($id, $uid);
        $this->success([], '已退出群聊');
    }

    public function invite(int $id): void
    {
        $this->requireLogin();
        $uid = $this->userId();
        if (!ChatGroup::isMember($id, $uid)) {
            $this->fail('你不是该群成员');
        }
        $target = Request::int('user_id');
        if ($target <= 0 || !User::find($target)) {
            $this->fail('用户不存在');
        }
        if (!ChatGroup::addMember($id, $target)) {
            $this->fail('对方已在群里');
        }
        $this->success([], '已邀请');
    }

    /**
     * 切换「允许附近的人发现我」
     */
    public function toggleDiscover(): void
    {
        $this->requireLogin();
        $uid = $this->userId();
        $on = Request::bool('on');
        Lan::setDiscover($uid, $on);
        $this->success(['on' => $on], $on ? '已开启：附近的人可以发现你' : '已关闭：附近的人看不到你');
    }

    /* ==================== 轮询接口 ==================== */

    /**
     * GET /api/chat/poll?scope=pm|group&id=&after=
     * 返回 after 之后的新消息 + 当前未读总数
     */
    public function poll(): void
    {
        $this->requireLogin();
        $uid = $this->userId();
        $scope = (string)Request::get('scope', 'pm');
        $id = Request::int('id');
        $after = Request::int('after');

        $messages = [];
        if ($scope === 'group' && $id > 0 && ChatGroup::isMember($id, $uid)) {
            $messages = ChatGroup::messages($id, $after, 100);
            ChatGroup::markRead($id, $uid);
        } elseif ($scope === 'pm' && $id > 0) {
            $all = Message::thread($uid, $id, 100);
            foreach ($all as $m) {
                if ((int)$m['id'] > $after) {
                    $messages[] = $m;
                }
            }
        }

        $this->success([
            'messages' => array_values($messages),
            'unread_pm' => Message::unreadTotal($uid),
            'unread_group' => ChatGroup::unreadTotal($uid),
            'server_time' => date('Y-m-d H:i:s'),
        ]);
    }
}
