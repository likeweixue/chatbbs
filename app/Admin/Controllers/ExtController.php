<?php

declare(strict_types=1);

namespace App\Admin\Controllers;

use App\Admin\AdminUI;
use App\Admin\BaseAdmin;
use App\Core\Database;
use App\Core\Request;
use App\Core\Settings;

/**
 * 扩展管理：插件 / 主题 / 语言包
 */
class ExtController extends BaseAdmin
{
    /* ================== 插件 ================== */
    public function plugins(): string
    {
        $rows = Database::fetchAll('SELECT * FROM ' . Database::table('plugins') . ' ORDER BY id ASC');
        $installed = [];
        foreach ($rows as $r) {
            $installed[(string)$r['slug']] = $r;
        }
        // 扫描目录
        foreach (glob(CB_PLUGIN_DIR . '/*', GLOB_ONLYDIR) ?: [] as $dir) {
            $slug = basename($dir);
            if (isset($installed[$slug])) {
                continue;
            }
            $meta = self::readJson($dir . '/plugin.json');
            $rows[] = [
                'id' => 0,
                'name' => (string)($meta['name'] ?? $slug),
                'slug' => $slug,
                'version' => (string)($meta['version'] ?? '1.0.0'),
                'author' => (string)($meta['author'] ?? '-'),
                'description' => (string)($meta['description'] ?? '目录中发现，未安装'),
                'enabled' => 0,
                '_found' => true,
            ];
        }

        $html = '<div class="adm-alert info">插件目录：<code>' . h(CB_PLUGIN_DIR) . '</code>，将插件文件夹放入后点击"安装"即可识别；本版本插件仅登记与开关，具体行为由插件自身实现。</div>';
        $html .= AdminUI::table([
            'primary' => 'id',
            'rows' => $rows,
            'columns' => [
                ['key' => 'name', 'label' => '插件名称', 'render' => fn($r) => '<b>' . h((string)$r['name']) . '</b>'
                    . ' <span class="muted">v' . h((string)$r['version']) . '</span>'],
                ['key' => 'slug', 'label' => '标识', 'width' => '140px'],
                ['key' => 'author', 'label' => '作者', 'width' => '120px'],
                ['key' => 'description', 'label' => '说明', 'render' => fn($r) => h(str_cut((string)$r['description'], 50))],
                ['key' => 'enabled', 'label' => '状态', 'width' => '90px', 'render' => fn($r) => (int)$r['enabled'] === 1
                    ? '<span class="adm-tag green">已启用</span>' : '<span class="adm-tag gray">已禁用</span>'],
            ],
            'actions' => [
                ['label' => '安装', 'url' => admin_url('plugins/install/0'), 'type' => 'post', 'hidden' => ['slug' => 'slug']],
                ['label' => '启用', 'url' => admin_url('plugins/toggle/{id}/1'), 'type' => 'post'],
                ['label' => '禁用', 'url' => admin_url('plugins/toggle/{id}/0'), 'type' => 'post'],
                ['label' => '卸载', 'url' => admin_url('plugins/remove/{id}'), 'type' => 'post', 'class' => 'danger', 'confirm' => '确定卸载该插件？'],
            ],
        ]);
        return $this->pageView('插件管理', 'plugins', $html);
    }

    public function pluginInstall(int $id): void
    {
        // id 为 0 时按目录安装：暂以 slug 兜底
        $slug = trim((string)Request::post('slug', ''));
        if ($id <= 0 && $slug === '') {
            $this->back('缺少插件标识', 'error');
        }
        $dir = CB_PLUGIN_DIR . '/' . basename($slug);
        $meta = is_dir($dir) ? self::readJson($dir . '/plugin.json') : [];
        $exists = Database::value('SELECT id FROM ' . Database::table('plugins') . ' WHERE slug = ?', [$slug]);
        if ($exists) {
            $this->back('该插件已安装');
        }
        Database::insert('plugins', [
            'name' => (string)($meta['name'] ?? $slug),
            'slug' => $slug,
            'version' => (string)($meta['version'] ?? '1.0.0'),
            'author' => (string)($meta['author'] ?? ''),
            'description' => (string)($meta['description'] ?? ''),
            'enabled' => 0,
        ]);
        $this->log('安装插件', $slug);
        $this->back('插件已安装');
    }

    public function pluginToggle(int $id, int $enable): void
    {
        Database::update('plugins', ['enabled' => $enable > 0 ? 1 : 0], 'id = ?', ['i' => $id]);
        $this->log(($enable > 0 ? '启用' : '禁用') . '插件', 'ID:' . $id);
        $this->back('插件状态已更新');
    }

    public function pluginRemove(int $id): void
    {
        Database::delete('plugins', 'id = ?', ['i' => $id]);
        $this->log('卸载插件', 'ID:' . $id);
        $this->back('插件已卸载');
    }

    /* ================== 主题 ================== */
    public function themes(): string
    {
        $current = (string)Settings::get('theme', 'default');
        $rows = Database::fetchAll('SELECT * FROM ' . Database::table('themes') . ' ORDER BY id ASC');
        $known = [];
        foreach ($rows as $r) {
            $known[(string)$r['slug']] = $r;
        }
        foreach (glob(CB_THEME_DIR . '/*', GLOB_ONLYDIR) ?: [] as $dir) {
            $slug = basename($dir);
            if (isset($known[$slug])) {
                continue;
            }
            $meta = self::readJson($dir . '/theme.json');
            $rows[] = [
                'id' => 0,
                'name' => (string)($meta['name'] ?? $slug),
                'slug' => $slug,
                'version' => (string)($meta['version'] ?? '1.0.0'),
                'author' => (string)($meta['author'] ?? '-'),
                'description' => (string)($meta['description'] ?? '主题目录中发现'),
                'enabled' => $slug === $current ? 1 : 0,
            ];
        }

        $html = '<div class="adm-alert info">当前启用主题：<b>' . h($current) . '</b>；主题目录：<code>' . h(CB_THEME_DIR) . '</code></div>';
        $html .= AdminUI::table([
            'primary' => 'id',
            'rows' => $rows,
            'columns' => [
                ['key' => 'name', 'label' => '主题名称', 'render' => fn($r) => '<b>' . h((string)$r['name']) . '</b> <span class="muted">v' . h((string)$r['version']) . '</span>'],
                ['key' => 'slug', 'label' => '目录', 'width' => '140px'],
                ['key' => 'author', 'label' => '作者', 'width' => '120px'],
                ['key' => 'description', 'label' => '说明', 'render' => fn($r) => h(str_cut((string)$r['description'], 50))],
                ['key' => 'enabled', 'label' => '状态', 'width' => '100px', 'render' => fn($r) => ((int)$r['enabled'] === 1 || (string)$r['slug'] === (string)Settings::get('theme', 'default'))
                    ? '<span class="adm-tag green">使用中</span>' : '<span class="adm-tag gray">未启用</span>'],
            ],
            'actions' => [
                ['label' => '启用', 'url' => admin_url('themes/enable/{id}'), 'type' => 'post', 'confirm' => '确定启用该主题？',
                    'hidden' => ['slug' => 'slug']],
            ],
        ]);

        $html .= '<div class="adm-panel"><div class="adm-panel-head">外观微调（圆角 / 主色）</div><div class="adm-panel-body">'
            . AdminUI::form([
                'action' => admin_url('themes/appearance'),
                'data' => [
                    'theme_radius' => (string)Settings::get('theme_radius', '8'),
                    'theme_primary' => (string)Settings::get('theme_primary', '#4D698E'),
                ],
                'submit' => '保存外观设置',
                'fields' => [
                    ['name' => 'theme_radius', 'label' => '圆角大小', 'type' => 'select', 'options' => [
                        '0' => '0 px（完全直角）',
                        '2' => '2 px',
                        '4' => '4 px（偏方正）',
                        '6' => '6 px',
                        '8' => '8 px（默认）',
                        '10' => '10 px',
                        '12' => '12 px',
                        '16' => '16 px（偏圆润）',
                        '20' => '20 px',
                    ], 'tip' => '控制卡片、按钮、输入框等所有元素的圆角；设为 0 即为方方正正的直角风格'],
                    ['name' => 'theme_primary', 'label' => '主题主色', 'type' => 'color', 'tip' => '影响链接、按钮、标签等主色调'],
                ],
            ])
            . '<div class="adm-form-tip">提示：外观设置保存后前台立即生效；若浏览器有缓存，请强制刷新（Ctrl+F5）。</div>'
            . '</div></div>';

        return $this->pageView('主题管理', 'themes', $html);
    }

    /**
     * 保存外观微调（圆角 / 主色）
     */
    public function themeAppearance(): void
    {
        $radius = Request::int('theme_radius', 8);
        $radius = max(0, min(24, $radius));
        $primary = trim((string)Request::post('theme_primary', '#4D698E'));
        if (!preg_match('/^#[0-9a-fA-F]{6}$/i', $primary)) {
            $primary = '#4D698E';
        }
        Settings::setMany([
            'theme_radius' => (string)$radius,
            'theme_primary' => $primary,
        ]);
        \App\Core\Cache::forget('settings');
        $this->log('修改主题外观', '圆角:' . $radius . ' 主色:' . $primary);
        $this->back('外观设置已保存（圆角 ' . $radius . 'px）');
    }

    public function themeEnable(int $id): void
    {
        $row = (int)$id > 0
            ? Database::fetch('SELECT * FROM ' . Database::table('themes') . ' WHERE id = ?', [$id])
            : null;
        $slug = $row ? (string)$row['slug'] : trim((string)Request::post('slug', ''));
        if ($slug === '') {
            $this->back('缺少主题标识', 'error');
        }
        if (!is_dir(CB_THEME_DIR . '/' . basename($slug))) {
            $this->back('主题目录不存在：' . $slug, 'error');
        }
        Database::query('UPDATE ' . Database::table('themes') . ' SET enabled = 0');
        if ($row) {
            Database::update('themes', ['enabled' => 1], 'id = ?', ['i' => $id]);
        } else {
            $exists = Database::value('SELECT id FROM ' . Database::table('themes') . ' WHERE slug = ?', [$slug]);
            if ($exists) {
                Database::update('themes', ['enabled' => 1], 'id = ?', ['i' => (int)$exists]);
            }
        }
        Settings::set('theme', $slug);
        \App\Core\Cache::forget('settings');
        $this->log('启用主题', $slug);
        $this->back('主题已启用');
    }

    /* ================== 语言包 ================== */
    public function languages(): string
    {
        $current = (string)Settings::get('language', 'zh-CN');
        $rows = [];
        foreach (glob(CB_ROOT . '/config/lang/*.php') ?: [] as $file) {
            $code = basename($file, '.php');
            $pack = @require $file;
            $rows[] = [
                'id' => 0,
                'code' => $code,
                'name' => is_array($pack) ? (string)($pack['_name'] ?? $code) : $code,
                'count' => is_array($pack) ? count($pack) : 0,
                'enabled' => $code === $current ? 1 : 0,
            ];
        }
        if (!$rows) {
            $rows[] = ['id' => 0, 'code' => 'zh-CN', 'name' => '简体中文（内置）', 'count' => 0, 'enabled' => 1];
        }

        $html = '<div class="adm-alert info">语言包目录：<code>' . h(CB_ROOT . '/config/lang') . '</code>，新增语言包后在此切换。</div>';
        $html .= AdminUI::table([
            'primary' => 'id',
            'rows' => $rows,
            'columns' => [
                ['key' => 'name', 'label' => '语言'],
                ['key' => 'code', 'label' => '代码', 'width' => '140px'],
                ['key' => 'count', 'label' => '词条数', 'width' => '100px'],
                ['key' => 'enabled', 'label' => '状态', 'width' => '100px', 'render' => fn($r) => (int)$r['enabled'] === 1
                    ? '<span class="adm-tag green">使用中</span>' : '<span class="adm-tag gray">未启用</span>'],
            ],
            'actions' => [
                ['label' => '启用', 'url' => admin_url('languages/enable/{id}'), 'type' => 'post',
                    'hidden' => ['code' => 'code']],
            ],
        ]);
        return $this->pageView('语言包管理', 'languages', $html);
    }

    public function languageEnable(int $id): void
    {
        $code = trim((string)Request::post('code', ''));
        if ($code === '') {
            $this->back('缺少语言代码', 'error');
        }
        $code = basename($code);
        Settings::set('language', $code);
        \App\Core\Cache::forget('settings');
        $this->log('切换语言', $code);
        $this->back('语言已切换为 ' . $code);
    }

    private static function readJson(string $file): array
    {
        if (!is_file($file)) {
            return [];
        }
        $data = json_decode((string)file_get_contents($file), true);
        return is_array($data) ? $data : [];
    }
}
