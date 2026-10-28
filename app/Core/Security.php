<?php

declare(strict_types=1);

namespace App\Core;

/**
 * 安全工具：XSS 过滤、敏感词、验证码
 */
class Security
{
    /**
     * 允许的 HTML 标签与属性（帖子正文白名单）
     */
    public static array $allowedTags = [
        'p' => ['style', 'class'],
        'br' => [],
        'hr' => ['class'],
        'strong' => [],
        'b' => [],
        'em' => [],
        'i' => [],
        'u' => [],
        's' => [],
        'del' => [],
        'h1' => ['style', 'class'],
        'h2' => ['style', 'class'],
        'h3' => ['style', 'class'],
        'h4' => ['style', 'class'],
        'h5' => ['style', 'class'],
        'h6' => ['style', 'class'],
        'ul' => ['class'],
        'ol' => ['class'],
        'li' => ['class'],
        'blockquote' => ['class'],
        'pre' => ['class'],
        'code' => ['class'],
        'a' => ['href', 'title', 'target', 'rel', 'class', 'data-attachment'],
        'img' => ['src', 'alt', 'title', 'width', 'height', 'loading', 'style', 'class'],
        'table' => ['class', 'border'],
        'thead' => [],
        'tbody' => [],
        'tr' => [],
        'th' => ['colspan', 'rowspan'],
        'td' => ['colspan', 'rowspan'],
        'span' => ['style', 'class'],
        'div' => ['style', 'class', 'data-upload-id', 'data-attachment'],
        'details' => ['class', 'open'],
        'summary' => [],
        'figure' => ['class'],
        'figcaption' => [],
        'sup' => [],
        'sub' => [],
        'mark' => [],
        'video' => ['src', 'poster', 'controls', 'width', 'height', 'style', 'class', 'preload', 'loop', 'muted', 'playsinline'],
        'source' => ['src', 'type'],
    ];

    /**
     * 富文本清洗：保留白名单标签，过滤脚本与事件属性
     */
    public static function cleanHtml(?string $html): string
    {
        $html = $html ?? '';
        if ($html === '') {
            return '';
        }
        // 去掉注释与危险块
        $html = preg_replace('/<!--.*?-->/is', '', $html);
        $html = preg_replace('#<\s*(script|style|iframe|object|embed|link|meta|base|form|input|button|textarea|select|svg|math)\b[^>]*>.*?<\s*/\s*\1\s*>#is', '', $html);
        $html = preg_replace('#<\s*(script|style|iframe|object|embed|link|meta|base|form|input|button|textarea|select|svg|math)\b[^>]*/?\s*>#is', '', $html);
        // 去除事件属性、javascript: 协议
        $html = preg_replace_callback('#<([a-zA-Z0-9]+)([^>]*)>#s', function ($m) {
            return self::cleanTag($m[1], $m[2]);
        }, $html);
        return trim($html);
    }

    private static function cleanTag(string $tag, string $attrs): string
    {
        $tag = strtolower($tag);
        if (!isset(self::$allowedTags[$tag])) {
            return in_array($tag, ['br', 'hr', 'img'], true) ? '' : '';
        }
        $allow = self::$allowedTags[$tag];
        $out = [];
        if (preg_match_all('/([a-zA-Z][a-zA-Z0-9_\-]*)\s*=\s*("([^"]*)"|\'([^\']*)\'|([^\s"\'>=`]+))/s', $attrs, $ms, PREG_SET_ORDER)) {
            foreach ($ms as $m) {
                $name = strtolower($m[1]);
                $value = $m[3] ?? $m[4] ?? $m[5] ?? '';
                if (!in_array($name, $allow, true)) {
                    continue;
                }
                if (str_starts_with($name, 'on')) {
                    continue;
                }
                $value = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
                $lower = strtolower(str_replace(["\t", "\r", "\n", ' '], '', $value));
                if (str_starts_with($lower, 'javascript:') || str_starts_with($lower, 'data:text/html') || str_starts_with($lower, 'vbscript:')) {
                    continue;
                }
                if ($name === 'href' || $name === 'src') {
                    if (!self::safeUrl($value)) {
                        continue;
                    }
                }
                if ($name === 'style') {
                    $value = self::cleanStyle($value);
                    if ($value === '') {
                        continue;
                    }
                }
                $out[] = $name . '="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"';
            }
        }
        if ($tag === 'a' && !in_array('rel', array_column(array_map(fn($a) => ['n' => explode('=', $a)[0]], $out), 'n'), true)) {
            $out[] = 'rel="nofollow noopener"';
        }
        if ($tag === 'video' && !in_array('controls', array_column(array_map(fn($a) => ['n' => explode('=', $a)[0]], $out), 'n'), true)) {
            $out[] = 'controls';
        }
        $void = in_array($tag, ['br', 'hr', 'img', 'source'], true);
        return '<' . $tag . ($out ? ' ' . implode(' ', $out) : '') . ($void ? ' />' : '>');
    }

    public static function safeUrl(string $url): bool
    {
        $url = trim($url);
        if ($url === '') {
            return false;
        }
        if (str_starts_with($url, '/') || str_starts_with($url, '#')) {
            return true;
        }
        if (str_starts_with($url, 'data:image/')) {
            return true;
        }
        return (bool)preg_match('#^https?://#i', $url);
    }

    /**
     * 允许的 CSS 属性（编辑器排版 / 图片排版使用）
     * 值为 true 表示放行；'int' 表示必须是整数；数组表示只允许列举的取值
     */
    public static array $allowedStyles = [
        'color' => true,
        'background-color' => true,
        'background' => true,
        'background-image' => 'url',
        'background-size' => true,
        'background-position' => true,
        'background-repeat' => true,
        'font-size' => true,
        'font-weight' => true,
        'font-family' => true,
        'font-style' => true,
        'text-align' => true,
        'text-decoration' => true,
        'text-indent' => true,
        'line-height' => true,
        'margin' => true,
        'margin-left' => true,
        'margin-right' => true,
        'margin-top' => true,
        'margin-bottom' => true,
        'padding' => true,
        'padding-left' => true,
        'padding-right' => true,
        'padding-top' => true,
        'padding-bottom' => true,
        'border' => true,
        'border-radius' => true,
        'width' => true,
        'max-width' => true,
        'height' => true,
        'max-height' => true,
        'min-height' => true,
        'display' => true,
        'float' => ['left', 'right', 'none', 'inherit'],
        'clear' => ['both', 'left', 'right', 'none'],
        'vertical-align' => true,
        'object-fit' => ['cover', 'contain', 'fill', 'scale-down', 'none'],
        'opacity' => true,
        'overflow' => ['hidden', 'auto', 'visible', 'scroll'],
        'position' => ['static', 'relative', 'absolute'],
        'left' => true,
        'right' => true,
        'top' => true,
        'bottom' => true,
        'z-index' => 'int',
    ];

    public static function cleanStyle(string $style): string
    {
        $parts = [];
        foreach (explode(';', $style) as $decl) {
            $decl = trim($decl);
            if ($decl === '') {
                continue;
            }
            [$prop, $val] = array_pad(explode(':', $decl, 2), 2, '');
            $prop = strtolower(trim($prop));
            $val = trim($val);
            if ($prop === '' || $val === '') {
                continue;
            }
            // 危险写法一律丢弃（除 background-image 单独校验 url()）
            if (preg_match('#(expression|javascript|@import|vbscript)#i', $val)) {
                continue;
            }
            $rule = self::$allowedStyles[$prop] ?? null;
            if ($rule === null) {
                continue;
            }
            if ($prop === 'background-image') {
                $cleanUrl = self::cleanBackgroundImage($val);
                if ($cleanUrl === '') {
                    continue;
                }
                $parts[] = $prop . ':' . $cleanUrl;
                continue;
            }
            if (preg_match('#url\s*\(#i', $val)) {
                continue;
            }
            if (is_array($rule) && !in_array(strtolower($val), $rule, true)) {
                continue;
            }
            if ($rule === 'int' && !preg_match('/^-?\d+$/', $val)) {
                continue;
            }
            $parts[] = $prop . ':' . preg_replace('#[<>"\';\\\\]#', '', $val);
        }
        return implode(';', $parts);
    }

    /**
     * 只允许 http(s) / 站内相对地址的 background-image
     */
    private static function cleanBackgroundImage(string $val): string
    {
        if (!preg_match('/^url\((?:"|\')?(.*?)(?:"|\')?\)$/is', trim($val), $m)) {
            return '';
        }
        $url = trim($m[1]);
        if ($url === '' || preg_match('#[<>"\';\\\\]#', $url)) {
            return '';
        }
        if (!self::safeUrl($url)) {
            return '';
        }
        return 'url(' . str_replace([')', '('], '', $url) . ')';
    }

    /**
     * 纯文本过滤
     */
    public static function text(?string $str, int $maxLen = 0): string
    {
        $str = strip_tags($str ?? '');
        $str = htmlspecialchars_decode($str, ENT_QUOTES);
        $str = htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        if ($maxLen > 0) {
            $str = mb_substr($str, 0, $maxLen);
        }
        return $str;
    }

    /**
     * 敏感词过滤
     */
    public static function filterWords(?string $content): string
    {
        $words = Settings::sensitiveWords();
        if (!$words || $content === null || $content === '') {
            return (string)$content;
        }
        return str_ireplace($words, '***', $content);
    }

    public static function hasSensitive(?string $content): bool
    {
        $words = Settings::sensitiveWords();
        if (!$words || !$content) {
            return false;
        }
        foreach ($words as $w) {
            if ($w !== '' && stripos($content, $w) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * 生成验证码
     */
    public static function captcha(string $key = 'common'): void
    {
        if (!extension_loaded('gd')) {
            // 无 GD 时输出纯文本验证码图片替代（返回 4 位数字文本）
            $code = (string)random_int(1000, 9999);
            Session::set('captcha_' . $key, $code);
            header('Content-Type: text/plain; charset=utf-8');
            echo $code;
            return;
        }
        $code = (string)random_int(1000, 9999);
        Session::set('captcha_' . $key, $code);
        $w = 100;
        $h = 36;
        $im = imagecreatetruecolor($w, $h);
        $bg = imagecolorallocate($im, 245, 247, 250);
        imagefilledrectangle($im, 0, 0, $w, $h, $bg);
        for ($i = 0; $i < 5; $i++) {
            $c = imagecolorallocate($im, random_int(180, 230), random_int(180, 230), random_int(180, 230));
            imageline($im, random_int(0, $w), random_int(0, $h), random_int(0, $w), random_int(0, $h), $c);
        }
        for ($i = 0; $i < 60; $i++) {
            $c = imagecolorallocate($im, random_int(150, 220), random_int(150, 220), random_int(150, 220));
            imagesetpixel($im, random_int(0, $w), random_int(0, $h), $c);
        }
        for ($i = 0; $i < 4; $i++) {
            $c = imagecolorallocate($im, random_int(20, 120), random_int(20, 120), random_int(20, 120));
            imagestring($im, 5, 12 + $i * 20, random_int(4, 14), $code[$i], $c);
        }
        header('Content-Type: image/png');
        header('Cache-Control: no-store');
        imagepng($im);
        imagedestroy($im);
    }

    public static function checkCaptcha(string $input, string $key = 'common'): bool
    {
        $stored = (string)Session::get('captcha_' . $key, '');
        Session::forget('captcha_' . $key);
        return $stored !== '' && strtolower($stored) === strtolower(trim($input));
    }

    public static function randomToken(int $len = 32): string
    {
        return bin2hex(random_bytes($len / 2));
    }
}
