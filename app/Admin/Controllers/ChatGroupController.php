<?php

declare(strict_types=1);

namespace App\Admin\Controllers;

use App\Admin\BaseAdmin;
use App\Core\Database;
use App\Core\Request;
use App\Core\Settings;
use App\Models\ChatGroup;

/**
 * 后台：群聊管理
 */
class ChatGroupController extends BaseAdmin
{
    public function index(): string
    {
        $rows = ChatGroup::adminList(100);
        $total = $msgTotal = 0;
        if (ChatGroup::installed()) {
            try {
                $total = (int)Database::value('SELECT COUNT(*) FROM ' . Database::table('chat_groups'), [], 0);
                $msgTotal = (int)Database::value('SELECT COUNT(*) FROM ' . Database::table('chat_group_messages'), [], 0);
            } catch (\Throwable $e) {
                // 忽略：表缺失时页面仍可打开
            }
        }

        $html = '<div class="adm-panel"><div class="adm-panel-head">群聊管理'
            . '<span class="adm-hint" style="margin-left:10px">共 ' . $total . ' 个群 · 累计 ' . $msgTotal . ' 条群消息</span>'
            . '</div><div class="adm-panel-body">';

        $html .= '<div class="adm-form-tip">'
            . '聊天与「附近的人」的开关在「站点设置 → 功能开关」里；'
            . '「附近的人」按 IP 网段判定（IPv4 取 /24），只有用户主动开启「允许附近的人发现我」后才会出现在列表里。'
            . '</div>';

        if (!$rows) {
            $html .= '<div class="adm-empty">还没有群聊。</div>';
        } else {
            $html .= '<table class="adm-table"><thead><tr>'
                . '<th style="width:60px">ID</th><th>群名称</th><th style="width:140px">群主</th>'
                . '<th style="width:80px">成员</th><th style="width:80px">类型</th>'
                . '<th style="width:140px">创建时间</th><th style="width:90px">操作</th>'
                . '</tr></thead><tbody>';
            foreach ($rows as $g) {
                $html .= '<tr>'
                    . '<td>' . (int)$g['id'] . '</td>'
                    . '<td><strong>' . h((string)$g['name']) . '</strong>'
                    . ($g['notice'] !== '' ? '<div class="adm-hint">' . h((string)$g['notice']) . '</div>' : '')
                    . '</td>'
                    . '<td>' . h((string)($g['nickname'] ?: $g['username'] ?: '-')) . '</td>'
                    . '<td>' . (int)$g['member_count'] . '</td>'
                    . '<td>' . ((int)$g['is_open'] === 1 ? '<span class="adm-tag green">公开</span>' : '<span class="adm-tag">私有</span>') . '</td>'
                    . '<td>' . h((string)$g['created_at']) . '</td>'
                    . '<td><a class="adm-btn sm danger" href="' . h(admin_url('chat-groups/delete/' . (int)$g['id'])) . '"'
                    . ' onclick="return confirm(\'确定解散该群？群消息会一并删除，不可恢复\')">解散</a></td>'
                    . '</tr>';
            }
            $html .= '</tbody></table>';
        }

        $html .= '</div></div>';
        return $this->pageView('群聊管理', 'chat_groups', $html);
    }

    public function delete(int $id): void
    {
        $row = ChatGroup::find($id);
        ChatGroup::deleteGroup($id);
        $this->log('解散群聊', (string)($row['name'] ?? $id));
        $this->back('群聊已解散');
    }

    /**
     * 聊天相关开关保存在「功能开关」表单里，这里兜底保存（防止两个表单互相覆盖）
     */
    public function saveSettings(): void
    {
        foreach (['chat_enable', 'lan_enable', 'lan_discover_default'] as $k) {
            Settings::set($k, Request::bool($k) ? 1 : 0);
        }
        $interval = Request::int('chat_poll_interval');
        Settings::set('chat_poll_interval', max(2000, min(30000, $interval > 0 ? $interval : 5000)));
        $max = Request::int('chat_group_max');
        Settings::set('chat_group_max', max(1, min(500, $max > 0 ? $max : 30)));
        \App\Core\Cache::forget('settings');
        $this->log('保存聊天设置');
        $this->back('聊天设置已保存');
    }
}
