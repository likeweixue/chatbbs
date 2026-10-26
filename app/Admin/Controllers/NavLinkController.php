<?php

declare(strict_types=1);

namespace App\Admin\Controllers;

use App\Admin\AdminUI;
use App\Admin\BaseAdmin;
use App\Core\Cache;
use App\Core\Request;
use App\Core\Settings;
use App\Models\NavLink;
use App\Services\LinkMeta;

/**
 * 友情 / 推荐链接导航页
 */
class NavLinkController extends BaseAdmin
{
    public function index(): string
    {
        $rows = NavLink::list(false);
        $html = '<div class="adm-alert info">导航页地址：<code>' . h(url((string)Settings::get('nav_slug', 'nav'))) . '</code>；在「菜单管理」或岛屿布局的侧栏菜单里添加这个地址即可出现在前台。</div>';
        $html .= AdminUI::table([
            'primary' => 'id',
            'rows' => $rows,
            'columns' => [
                ['key' => 'id', 'label' => 'ID', 'width' => '60px'],
                ['key' => 'title', 'label' => '名称', 'render' => fn($r) => '<b>' . h((string)$r['title']) . '</b>'],
                ['key' => 'url', 'label' => '网址', 'render' => fn($r) => '<a href="' . h((string)$r['url']) . '" target="_blank" rel="noopener">' . h(str_cut((string)$r['url'], 50)) . '</a>'],
                ['key' => 'description', 'label' => '简介', 'render' => fn($r) => h(str_cut((string)$r['description'], 40))],
                ['key' => 'visible', 'label' => '显示', 'width' => '70px', 'render' => fn($r) => (int)$r['visible'] === 1 ? '<span class="adm-tag green">显示</span>' : '<span class="adm-tag gray">隐藏</span>'],
                ['key' => 'sort_order', 'label' => '排序', 'width' => '60px'],
            ],
            'actions' => [
                ['label' => '编辑', 'url' => admin_url('nav-links?edit={id}')],
                ['label' => '删除', 'url' => admin_url('nav-links/delete/{id}'), 'type' => 'post', 'class' => 'danger', 'confirm' => '确定删除这个导航链接？'],
            ],
        ]);

        $edit = null;
        $editId = Request::int('edit');
        if ($editId > 0) {
            $edit = NavLink::find($editId);
        }
        $html .= '<div class="adm-panel"><div class="adm-panel-head">' . ($edit ? '编辑导航链接' : '添加导航链接') . '</div><div class="adm-panel-body">'
            . AdminUI::form([
                'action' => admin_url('nav-links/save'),
                'data' => $edit ?? [],
                'hidden' => ['id' => $edit['id'] ?? 0],
                'submit' => '保存链接',
                'back' => admin_url('nav-links'),
                'fields' => [
                    ['name' => 'url', 'label' => '网址', 'placeholder' => 'https://github.com', 'tip' => '只支持 http / https；保存时会自动抓取标题、简介和 favicon（可手动覆盖）'],
                    ['name' => 'title', 'label' => '名称', 'tip' => '留空自动抓取；抓不到时使用域名'],
                    ['name' => 'description', 'label' => '简介', 'type' => 'textarea', 'tip' => '留空自动抓取 meta description'],
                    ['name' => 'icon', 'label' => '图标地址', 'tip' => '留空自动抓取 favicon；也可以填图床地址'],
                    ['name' => 'sort_order', 'label' => '排序', 'type' => 'number', 'tip' => '越小越靠前'],
                    ['name' => 'visible', 'label' => '显示', 'type' => 'switch'],
                ],
            ]) . '</div></div>';

        $html .= '<div class="adm-panel"><div class="adm-panel-head">导航页设置</div><div class="adm-panel-body">'
            . AdminUI::form([
                'action' => admin_url('nav-links/settings'),
                'data' => Settings::all(),
                'submit' => '保存页面设置',
                'fields' => [
                    ['name' => 'nav_title', 'label' => '页面标题', 'placeholder' => '网站导航'],
                    ['name' => 'nav_slug', 'label' => '页面路径', 'tip' => '默认 nav，可自定义为 links / sites 等；保存后菜单里填写的地址也要同步修改'],
                    ['name' => 'nav_description', 'label' => '页面简介', 'type' => 'textarea'],
                ],
            ]) . '</div></div>';

        return $this->pageView('导航链接', 'nav_links', $html);
    }

    public function save(): void
    {
        $id = Request::int('id');
        $url = trim((string)Request::post('url', ''));
        if (!preg_match('#^https?://#i', $url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            $this->back('请填写有效的 http / https 地址', 'error');
        }
        $title = $this->pure('title', 100);
        $desc = $this->pure('description', 255);
        $icon = $this->pure('icon', 500);
        if ($title === '' || $desc === '' || $icon === '') {
            $meta = LinkMeta::fetch($url);
            if ($title === '') {
                $title = (string)($meta['title'] ?? '');
            }
            if ($desc === '') {
                $desc = (string)($meta['description'] ?? '');
            }
            if ($icon === '') {
                $icon = (string)($meta['icon'] ?? '');
            }
        }
        if ($title === '') {
            $host = parse_url($url, PHP_URL_HOST);
            $title = is_string($host) ? $host : $url;
        }
        $data = [
            'title' => mb_substr($title, 0, 100),
            'url' => $url,
            'description' => mb_substr($desc, 0, 255),
            'icon' => mb_substr($icon, 0, 500),
            'sort_order' => Request::int('sort_order'),
            'visible' => Request::bool('visible') ? 1 : 0,
        ];
        if ($id > 0) {
            NavLink::update($id, $data);
            $this->log('编辑导航链接', $title);
        } else {
            $data['created_at'] = date('Y-m-d H:i:s');
            NavLink::create($data);
            $this->log('新增导航链接', $title);
        }
        $this->back('导航链接已保存');
    }

    public function delete(int $id): void
    {
        NavLink::delete($id);
        $this->log('删除导航链接', 'ID:' . $id);
        $this->back('导航链接已删除');
    }

    public function saveSettings(): void
    {
        $slug = strtolower(trim((string)Request::post('nav_slug', 'nav')));
        if ($slug === '' || !preg_match('/^[a-z0-9\-_]+$/', $slug)) {
            $slug = 'nav';
        }
        Settings::setMany([
            'nav_title' => $this->pure('nav_title', 100) ?: '网站导航',
            'nav_slug' => $slug,
            'nav_description' => $this->pure('nav_description', 255),
        ]);
        Cache::forget('settings');
        $this->log('修改导航页设置', $slug);
        $this->back('导航页设置已保存');
    }
}
