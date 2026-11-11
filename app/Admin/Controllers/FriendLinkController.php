<?php

declare(strict_types=1);

namespace App\Admin\Controllers;

use App\Admin\AdminUI;
use App\Admin\BaseAdmin;
use App\Core\Request;
use App\Core\Settings;

/**
 * 友情链接管理：统一维护，自动同步到单栏 / 双栏 / 三栏
 */
class FriendLinkController extends BaseAdmin
{
    public static function links(): array
    {
        $raw = (string)Settings::get('friend_links', '');
        $out = [];
        foreach (preg_split('/\r\n|\r|\n/', $raw) ?: [] as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $parts = preg_split('/\s*\|\s*/', $line);
            $name = trim((string)($parts[0] ?? ''));
            $url = trim((string)($parts[1] ?? ''));
            $desc = trim((string)($parts[2] ?? ''));
            if ($name === '' && $url === '') {
                continue;
            }
            $out[] = ['name' => $name !== '' ? $name : $url, 'url' => $url, 'desc' => $desc];
        }
        return $out;
    }

    private static function persist(array $links): void
    {
        $lines = [];
        foreach ($links as $l) {
            $name = trim((string)($l['name'] ?? ''));
            $url = trim((string)($l['url'] ?? ''));
            $desc = trim((string)($l['desc'] ?? ''));
            if ($name === '' && $url === '') {
                continue;
            }
            $lines[] = $name . '|' . $url . ($desc !== '' ? '|' . $desc : '');
        }
        Settings::set('friend_links', implode("\n", $lines));
        \App\Core\Cache::forget('settings');
    }

    public function index(): string
    {
        $links = self::links();
        $html = '<div class="adm-alert info">友情链接统一在这里维护，单栏、双栏、三栏布局会自动同步。</div>';
        $rows = [];
        foreach ($links as $i => $l) {
            $rows[] = ['id' => $i, 'name' => $l['name'], 'url' => $l['url'], 'desc' => $l['desc'] ?? ''];
        }
        $html .= AdminUI::table([
            'primary' => 'id',
            'rows' => $rows,
            'columns' => [
                ['key' => 'id', 'label' => '序号', 'width' => '60px', 'render' => fn($r) => (string)((int)$r['id'] + 1)],
                ['key' => 'name', 'label' => '名称'],
                ['key' => 'url', 'label' => '网址', 'render' => fn($r) => '<a href="' . h((string)$r['url']) . '" target="_blank" rel="noopener">' . h((string)$r['url']) . '</a>'],
                ['key' => 'desc', 'label' => '介绍', 'render' => fn($r) => h(str_cut((string)($r['desc'] ?? ''), 40))],
            ],
            'actions' => [
                ['label' => '删除', 'url' => admin_url('friend-links/delete/{id}'), 'type' => 'post', 'class' => 'danger', 'confirm' => '确定删除该友情链接？'],
            ],
        ]);

        $html .= '<div class="adm-panel"><div class="adm-panel-head">新增友情链接</div><div class="adm-panel-body">'
            . AdminUI::form([
                'action' => admin_url('friend-links/save'),
                'submit' => '添加',
                'fields' => [
                    ['name' => 'name', 'label' => '名称'],
                    ['name' => 'url', 'label' => '网址', 'tip' => '完整 http(s):// 地址'],
                    ['name' => 'desc', 'label' => '介绍', 'type' => 'textarea', 'rows' => 2, 'tip' => '显示在单栏友情链接面板里'],
                ],
            ]) . '</div></div>';

        return $this->pageView('友情链接', 'friend_links', $html);
    }

    public function save(): void
    {
        $name = $this->pure('name', 80);
        $url = $this->pure('url', 500);
        $desc = $this->pure('desc', 255);
        if ($name === '' || $url === '') {
            $this->back('名称和网址不能为空', 'error');
        }
        $links = self::links();
        $links[] = ['name' => $name, 'url' => $url, 'desc' => $desc];
        self::persist($links);
        $this->log('新增友情链接', $name);
        $this->back('友情链接已添加');
    }

    public function delete(int $id): void
    {
        $links = self::links();
        if (isset($links[$id])) {
            $name = $links[$id]['name'];
            unset($links[$id]);
            self::persist(array_values($links));
            $this->log('删除友情链接', $name);
        }
        $this->back('友情链接已删除');
    }
}
