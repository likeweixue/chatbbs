<?php

declare(strict_types=1);

namespace App\Core;

/**
 * 视图渲染（原生 PHP 模板）
 */
class View
{
    private static array $shared = [];
    private static ?string $theme = null;

    public static function theme(): string
    {
        if (self::$theme === null) {
            $t = (string)Settings::get('theme', 'default');
            if (!is_dir(CB_THEME_DIR . '/' . $t)) {
                $t = 'default';
            }
            self::$theme = $t;
        }
        return self::$theme;
    }

    public static function setTheme(string $theme): void
    {
        self::$theme = $theme;
    }

    public static function share(string $key, $value): void
    {
        self::$shared[$key] = $value;
    }

    public static function path(string $view): string
    {
        return CB_THEME_DIR . '/' . self::theme() . '/templates/' . $view . '.php';
    }

    public static function exists(string $view): bool
    {
        return is_file(self::path($view));
    }

    /**
     * 渲染模板（不套布局）
     */
    public static function make(string $view, array $data = []): string
    {
        $file = self::path($view);
        if (!is_file($file)) {
            throw new \RuntimeException('模板不存在：' . $view);
        }
        return self::renderFile($file, $data);
    }

    /**
     * 渲染并输出到布局
     */
    public static function render(string $view, array $data = []): string
    {
        $content = self::make($view, $data);
        $layout = $data['layout'] ?? 'layouts/main';
        if ($layout === false || $layout === null) {
            return $content;
        }
        if (!self::exists($layout)) {
            return $content;
        }
        return self::finalize(self::make($layout, array_merge($data, ['content' => $content])));
    }

    /**
     * 整页输出收尾：统一给 <img> 补懒加载与异步解码
     *
     * 头像 / 配图往往一屏就是几十张，全部同步解码会明显拖慢首屏渲染。
     * 只在「套了布局」的整页渲染上跑一次，避免局部模板重复处理。
     */
    public static function finalize(string $html): string
    {
        return preg_replace_callback(
            '/<img\b(?![^>]*\bloading=)([^>]*)>/i',
            static fn(array $m): string => '<img' . $m[1] . ' loading="lazy" decoding="async">',
            $html
        ) ?? $html;
    }

    public static function renderFile(string $file, array $data = []): string
    {
        $data = array_merge(self::$shared, $data);
        extract($data, EXTR_SKIP);
        ob_start();
        include $file;
        return (string)ob_get_clean();
    }

    /**
     * 局部模板
     */
    public static function partial(string $view, array $data = []): string
    {
        if (!self::exists($view)) {
            return '';
        }
        return self::make($view, $data);
    }

    /**
     * 分页 HTML
     */
    public static function paginate(int $total, int $perPage, int $page, string $baseUrl): string
    {
        $pages = (int)ceil($total / max(1, $perPage));
        if ($pages <= 1) {
            return '';
        }
        $page = max(1, min($page, $pages));
        $html = '<div class="cb-pagination">';
        $link = function ($p) use ($baseUrl) {
            return h(str_contains($baseUrl, '?') ? $baseUrl . '&page=' . $p : $baseUrl . '?page=' . $p);
        };
        if ($page > 1) {
            $html .= '<a class="page" href="' . $link($page - 1) . '">上一页</a>';
        }
        $start = max(1, $page - 2);
        $end = min($pages, $page + 2);
        if ($start > 1) {
            $html .= '<a class="page" href="' . $link(1) . '">1</a>';
            if ($start > 2) {
                $html .= '<span class="page dots">…</span>';
            }
        }
        for ($i = $start; $i <= $end; $i++) {
            $html .= '<a class="page' . ($i === $page ? ' active' : '') . '" href="' . $link($i) . '">' . $i . '</a>';
        }
        if ($end < $pages) {
            if ($end < $pages - 1) {
                $html .= '<span class="page dots">…</span>';
            }
            $html .= '<a class="page" href="' . $link($pages) . '">' . $pages . '</a>';
        }
        if ($page < $pages) {
            $html .= '<a class="page" href="' . $link($page + 1) . '">下一页</a>';
        }
        $html .= '<span class="page-info">共 ' . $total . ' 条 / ' . $pages . ' 页</span>';
        $html .= '</div>';
        return $html;
    }
}
