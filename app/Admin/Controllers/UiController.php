<?php

declare(strict_types=1);

namespace App\Admin\Controllers;

use App\Admin\AdminUI;
use App\Admin\BaseAdmin;
use App\Core\Request;
use App\Core\Settings;
use App\Services\Island;

/**
 * 界面设置：单栏 / 双栏 / 三栏三套布局互相独立的配置入口
 */
class UiController extends BaseAdmin
{
    /** 可选的侧边栏模块（与 SettingController::sidebarModules 保持一致） */
    public static function modules(): array
    {
        return SettingController::sidebarModules();
    }

    /* ---------------- 单栏 ---------------- */
    public function single(): string
    {
        $html = '<div class="adm-alert info">单栏布局没有侧边栏，只保留内容区。这里设置内容区的最大宽度。</div>';
        $html .= AdminUI::form([
            'action' => admin_url('interface/single/save'),
            'data' => Settings::all(),
            'submit' => '保存单栏设置',
            'fields' => [
                ['name' => 'single_width', 'label' => '单栏内容宽度(px)', 'type' => 'number',
                    'tip' => '留空或 0 使用默认 1200px；可设置 960 - 1800，例如 1400 让单栏更宽'],
                ['name' => 'single_stats_panel', 'label' => '显示论坛统计面板', 'type' => 'switch',
                    'tip' => '单栏底部显示论坛统计，默认开启'],
                ['name' => 'single_links_panel', 'label' => '显示友情链接面板', 'type' => 'switch',
                    'tip' => '单栏底部显示友情链接，默认开启'],
            ],
        ]);
        return $this->pageView('单栏设置', 'interface.single', $html);
    }

    public function saveSingle(): void
    {
        Settings::set('single_width', (string)max(0, min(1800, Request::int('single_width', 0))));
        Settings::set('single_stats_panel', Request::bool('single_stats_panel') ? 1 : 0);
        Settings::set('single_links_panel', Request::bool('single_links_panel') ? 1 : 0);
        \App\Core\Cache::forget('settings');
        $this->log('修改单栏设置');
        $this->back('单栏设置已保存');
    }

    /* ---------------- 双栏 ---------------- */
    public function double(): string
    {
        $html = AdminUI::form([
            'action' => admin_url('interface/double/save'),
            'data' => Settings::all(),
            'submit' => '保存双栏设置',
            'fields' => [
                ['name' => 'sidebar_side', 'label' => '侧边栏位置', 'type' => 'select', 'options' => [
                    'right' => '右侧', 'left' => '左侧',
                ]],
                ['name' => 'sidebar_default_hidden', 'label' => '默认隐藏侧边栏', 'type' => 'switch',
                    'tip' => '开启后访客首次进入先看到完整内容区，可点顶栏按钮展开'],
            ],
        ]);
        $html .= $this->moduleTable('sidebar_modules', '双栏侧边栏模块', 'sidebar-form', admin_url('interface/double/modules'));
        return $this->pageView('双栏设置', 'interface.double', $html);
    }

    public function saveDouble(): void
    {
        $side = (string)Request::post('sidebar_side', 'right') === 'left' ? 'left' : 'right';
        Settings::set('sidebar_side', $side);
        Settings::set('sidebar_default_hidden', Request::bool('sidebar_default_hidden') ? 1 : 0);
        \App\Core\Cache::forget('settings');
        $this->log('修改双栏设置');
        $this->back('双栏设置已保存');
    }

    public function saveDoubleModules(): void
    {
        $this->saveModules('sidebar_modules');
        \App\Core\Cache::forget('settings');
        $this->log('修改双栏侧边栏模块');
        $this->back('双栏侧边栏模块已保存');
    }

    /* ---------------- 三栏 ---------------- */
    public function triple(): string
    {
        $html = AdminUI::form([
            'action' => admin_url('interface/triple/save'),
            'data' => Settings::all(),
            'submit' => '保存三栏设置',
            'fields' => [
                ['name' => 'island_sidebar_side', 'label' => '侧栏默认位置', 'type' => 'select', 'options' => [
                    'left' => '左侧', 'right' => '右侧',
                ], 'tip' => '访客也可以在前台顶栏自行切换'],
                ['name' => 'island_sidebar_width', 'label' => '左侧导航栏宽度', 'type' => 'number',
                    'tip' => '200 - 360 px'],
                ['name' => 'island_sidebar_default_hidden', 'label' => '默认隐藏侧栏', 'type' => 'switch'],
            ],
        ]);

        // 左栏：工作台导航菜单（可视化表格）
        $builtins = ['posts' => '帖子', 'forums' => '板块分区', 'tags' => '标签'];
        $rows = [];
        $seen = [];
        $rawMenu = (string)Settings::get('island_menu', Island::DEFAULT_MENU);
        foreach (preg_split('/\r\n|\r|\n/', $rawMenu) ?: [] as $line) {
            $line = trim($line);
            if ($line === '') continue;
            $parts = array_map('trim', explode('|', $line));
            $type = strtolower((string)($parts[0] ?? ''));
            if (isset($builtins[$type])) {
                if (!isset($seen[$type])) {
                    $rows[] = ['type' => $type, 'name' => $builtins[$type], 'url' => '', 'on' => 1];
                    $seen[$type] = 1;
                }
            } elseif ($type === 'link') {
                $name = (string)($parts[1] ?? '');
                $url = (string)($parts[2] ?? '');
                if ($url !== '') {
                    $rows[] = ['type' => 'link', 'name' => $name !== '' ? $name : $url, 'url' => $url, 'on' => 1];
                    $seen['link:' . $url] = 1;
                }
            }
        }
        foreach ($builtins as $key => $label) {
            if (!isset($seen[$key])) {
                $rows[] = ['type' => $key, 'name' => $label, 'url' => '', 'on' => 0];
            }
        }
        foreach ([['网站导航', '/nav'], ['RSS', '/reader']] as [$n, $u]) {
            if (!isset($seen['link:' . $u])) {
                $rows[] = ['type' => 'link', 'name' => $n, 'url' => $u, 'on' => 0];
            }
        }

        $html .= '<div class="adm-panel"><div class="adm-panel-head">三栏左侧栏菜单</div><div class="adm-panel-body">'
            . '<form method="post" action="' . h(admin_url('interface/triple/menu')) . '" id="island-menu-form">'
            . csrf_field()
            . '<input type="hidden" name="island_menu" id="island-menu-value" value="">'
            . '<div class="adm-panel"><div class="adm-panel-body tight"><table class="adm-table"><thead><tr>'
            . '<th style="width:60px">显示</th><th>模块 / 链接</th><th style="width:180px">排序</th></tr></thead>'
            . '<tbody id="island-menu-list">';
        foreach ($rows as $r) {
            $type = $r['type'];
            $html .= '<tr data-type="' . h($type) . '" data-url="' . h((string)$r['url']) . '">'
                . '<td><input type="checkbox" class="im-on" ' . ((int)$r['on'] === 1 ? 'checked' : '') . '></td><td>';
            if ($type === 'link') {
                $html .= '<div class="flex gap-8" style="display:flex;gap:8px;flex-wrap:wrap">'
                    . '<input class="adm-input im-name" type="text" value="' . h((string)$r['name']) . '" placeholder="名称" style="max-width:180px">'
                    . '<input class="adm-input im-url" type="text" value="' . h((string)$r['url']) . '" placeholder="链接地址" style="max-width:280px">'
                    . '<button type="button" class="adm-link im-del" style="color:var(--adm-danger,#d93026)">删除</button></div>';
            } else {
                $html .= '<b>' . h((string)$r['name']) . '</b> <span class="muted">' . h($type) . '</span>';
            }
            $html .= '</td><td><div class="adm-row-actions">'
                . '<button type="button" class="adm-link im-up">↑ 上移</button>'
                . '<button type="button" class="adm-link im-down">↓ 下移</button>'
                . '</div></td></tr>';
        }
        $html .= '</tbody></table></div></div>'
            . '<div class="flex gap-8" style="display:flex;gap:8px;margin-top:12px;flex-wrap:wrap">'
            . '<button class="adm-btn primary" type="submit">保存左侧栏菜单</button>'
            . '<button class="adm-btn" type="button" id="island-link-add">+ 添加自定义链接</button>'
            . '</div></form></div></div>';

        $html .= '<script>
        (function () {
          var form = document.getElementById("island-menu-form");
          if (!form) return;
          var list = document.getElementById("island-menu-list");
          function repaint() {
            var out = [];
            list.querySelectorAll("tr").forEach(function (tr) {
              if (!tr.querySelector(".im-on").checked) return;
              var type = tr.getAttribute("data-type");
              if (type === "link") {
                var name = tr.querySelector(".im-name").value.trim();
                var url = tr.querySelector(".im-url").value.trim();
                if (url) out.push("link|" + (name || url) + "|" + url);
              } else {
                out.push(type);
              }
            });
            document.getElementById("island-menu-value").value = out.join("\n");
          }
          form.addEventListener("submit", repaint);
          list.addEventListener("click", function (e) {
            var up = e.target.closest(".im-up"), down = e.target.closest(".im-down"), del = e.target.closest(".im-del");
            var tr = (up || down || del) && (up || down || del).closest("tr");
            if (!tr) return;
            if (up) { var p = tr.previousElementSibling; if (p) list.insertBefore(tr, p); }
            if (down) { var n = tr.nextElementSibling; if (n) list.insertBefore(n, tr); }
            if (del) tr.remove();
          });
          document.getElementById("island-link-add").addEventListener("click", function () {
            var tr = document.createElement("tr");
            tr.setAttribute("data-type", "link");
            tr.setAttribute("data-url", "");
            tr.innerHTML = "<td><input type=\"checkbox\" class=\"im-on\" checked></td>"
              + "<td><div class=\"flex gap-8\" style=\"display:flex;gap:8px;flex-wrap:wrap\">"
              + "<input class=\"adm-input im-name\" type=\"text\" placeholder=\"名称\" style=\"max-width:180px\">"
              + "<input class=\"adm-input im-url\" type=\"text\" placeholder=\"链接地址\" style=\"max-width:280px\">"
              + "<button type=\"button\" class=\"adm-link im-del\" style=\"color:var(--adm-danger,#d93026)\">删除</button></div></td>"
              + "<td><div class=\"adm-row-actions\"><button type=\"button\" class=\"adm-link im-up\">↑ 上移</button>"
              + "<button type=\"button\" class=\"adm-link im-down\">↓ 下移</button></div></td>";
            list.appendChild(tr);
          });
        })();
        </script>';

        // 右栏：模块
        $html .= $this->moduleTable('island_sidebar_modules', '三栏侧边栏模块', 'island-sidebar-form', admin_url('interface/triple/modules'));

        return $this->pageView('三栏设置', 'interface.triple', $html);
    }

    public function saveTriple(): void
    {
        $side = (string)Request::post('island_sidebar_side', 'left') === 'right' ? 'right' : 'left';
        $width = max(200, min(360, Request::int('island_sidebar_width', 240)));
        Settings::setMany([
            'island_sidebar_side' => $side,
            'island_sidebar_width' => (string)$width,
            'island_sidebar_default_hidden' => Request::bool('island_sidebar_default_hidden') ? 1 : 0,
        ]);
        \App\Core\Cache::forget('settings');
        $this->log('修改三栏设置');
        $this->back('三栏设置已保存');
    }

    public function saveTripleModules(): void
    {
        $this->saveModules('island_sidebar_modules');
        \App\Core\Cache::forget('settings');
        $this->log('修改三栏侧边栏模块');
        $this->back('三栏侧边栏模块已保存');
    }

    public function saveTripleMenu(): void
    {
        $menu = trim(strip_tags((string)Request::post('island_menu', '')));
        if ($menu === '') {
            $menu = Island::DEFAULT_MENU;
        }
        Settings::set('island_menu', $menu);
        \App\Core\Cache::forget('settings');
        $this->log('修改三栏左栏菜单');
        $this->back('三栏左栏菜单已保存');
    }

    /* ---------------- 公共：模块表 ---------------- */
    private function saveModules(string $key): void
    {
        $modules = trim((string)Request::post('modules', ''));
        $valid = array_keys(self::modules());
        $list = array_values(array_intersect(
            array_filter(array_map('trim', explode(',', $modules))),
            $valid
        ));
        Settings::set($key, implode(',', $list));
    }

    private function moduleTable(string $key, string $title, string $formId, string $action): string
    {
        $all = self::modules();
        $enabled = array_values(array_filter(array_map('trim', explode(',', (string)Settings::get($key, '')))));
        $rows = [];
        foreach ($enabled as $k) {
            if (isset($all[$k])) {
                $rows[] = ['key' => $k, 'name' => $all[$k], 'on' => 1];
                unset($all[$k]);
            }
        }
        foreach ($all as $k => $name) {
            $rows[] = ['key' => $k, 'name' => $name, 'on' => 0];
        }

        $html = '<div class="adm-panel"><div class="adm-panel-head">' . h($title) . '</div><div class="adm-panel-body">'
            . '<form method="post" action="' . h($action) . '" id="' . h($formId) . '">'
            . csrf_field()
            . '<input type="hidden" name="modules" id="' . h($formId) . '-modules" value="">'
            . '<div class="adm-panel"><div class="adm-panel-body tight">'
            . '<table class="adm-table"><thead><tr><th style="width:60px">显示</th><th>模块</th><th style="width:180px">排序</th></tr></thead>'
            . '<tbody class="adm-module-list" data-input="' . h($formId) . '-modules">';
        foreach ($rows as $r) {
            $html .= '<tr data-key="' . h($r['key']) . '">'
                . '<td><input type="checkbox" class="sb-on" ' . ((int)$r['on'] === 1 ? 'checked' : '') . '></td>'
                . '<td><b>' . h($r['name']) . '</b> <span class="muted">' . h($r['key']) . '</span></td>'
                . '<td><div class="adm-row-actions">'
                . '<button type="button" class="adm-link sb-up">↑ 上移</button>'
                . '<button type="button" class="adm-link sb-down">↓ 下移</button>'
                . '</div></td></tr>';
        }
        $html .= '</tbody></table></div></div>'
            . '<div style="margin-top:12px"><button class="adm-btn primary" type="submit">保存侧边栏</button></div>'
            . '</form></div></div>';

        $html .= '<script>
        (function () {
          var form = document.getElementById(' . json_encode($formId) . ');
          if (!form) return;
          var list = form.querySelector(".adm-module-list");
          function move(tr, dir) {
            var target = dir < 0 ? tr.previousElementSibling : tr.nextElementSibling;
            if (!target) return;
            if (dir < 0) list.insertBefore(tr, target); else list.insertBefore(target, tr);
          }
          list.addEventListener("click", function (e) {
            var up = e.target.closest(".sb-up"), down = e.target.closest(".sb-down");
            if (!up && !down) return;
            var tr = (up || down).closest("tr");
            move(tr, up ? -1 : 1);
          });
          form.addEventListener("submit", function () {
            var out = [];
            list.querySelectorAll("tr").forEach(function (tr) {
              if (tr.querySelector(".sb-on").checked) out.push(tr.dataset.key);
            });
            form.querySelector("input[name=modules]").value = out.join(",");
          });
        })();
        </script>';
        return $html;
    }
}
