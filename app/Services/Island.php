<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Settings;
use App\Models\Forum;
use App\Models\Tag;

/**
 * 岛屿布局（左侧菜单 + 内容区）的菜单解析与数据。
 *
 * 这不是新主题，而是默认主题下的一种布局样式；通过后台「主题管理 → 站点布局」切换，
 * 关闭后完全回到原来的布局，不影响默认模板。
 *
 * 布局方向参考 OpenIsle（MIT License，https://github.com/nagisa77/OpenIsle），
 * ChatBBS 的模板 / 样式 / 脚本为独立实现；详见根目录 THIRD_PARTY_NOTICES.md。
 */
class Island
{
    public const DEFAULT_MENU = "posts|帖子\nforums|板块分区\ntags|标签";

    public static function enabled(): bool
    {
        return (string)Settings::get('site_layout', 'classic') === 'island';
    }

    /**
     * 左侧菜单项：
     *   posts|帖子
     *   forums|板块分区
     *   tags|标签
     *   link|友情链接|https://example.com
     *   divider
     *
     * 支持用 | 分隔；类型不区分大小写；空行忽略。全部无效时回退默认菜单。
     */
    public static function menu(): array
    {
        $raw = trim((string)Settings::get('island_menu', ''));
        $lines = $raw === '' ? [] : preg_split('/\r\n|\r|\n/', $raw);
        $out = [];
        foreach ($lines as $line) {
            $line = trim((string)$line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            $parts = array_map('trim', explode('|', $line));
            $type = strtolower((string)($parts[0] ?? ''));
            if (!in_array($type, ['posts', 'forums', 'tags', 'link', 'divider'], true)) {
                continue;
            }
            $title = (string)($parts[1] ?? '');
            $url = (string)($parts[2] ?? '');
            if ($title === '') {
                $title = match ($type) {
                    'posts' => '帖子',
                    'forums' => '板块分区',
                    'tags' => '标签',
                    'divider' => '',
                    default => '链接',
                };
            }
            if ($type === 'link' && $url === '') {
                continue;
            }
            $out[] = ['type' => $type, 'title' => $title, 'url' => $url];
        }
        if (!$out) {
            foreach (preg_split('/\r\n|\r|\n/', self::DEFAULT_MENU) ?: [] as $line) {
                [$type, $title] = explode('|', $line, 2);
                $out[] = ['type' => $type, 'title' => $title, 'url' => ''];
            }
        }
        return $out;
    }

    /** @return array<int, array<string, mixed>> */
    public static function forums(): array
    {
        return Forum::tree(true);
    }

    /** @return array<int, array<string, mixed>> */
    public static function tags(): array
    {
        return Tag::hot(20);
    }

    /**
     * 左侧菜单每一项的类型名称（后台提示用）
     */
    public static function typeLabels(): array
    {
        return [
            'posts' => '帖子入口',
            'forums' => '板块分区',
            'tags' => '标签墙',
            'link' => '自定义链接',
            'divider' => '分隔线',
        ];
    }
}
