<?php

declare(strict_types=1);

namespace App\Services;

/**
 * 抓取外部链接的标题、简介和 favicon（仅管理员添加导航链接时使用）。
 */
class LinkMeta
{
    public static function fetch(string $url): array
    {
        $url = trim($url);
        if (!preg_match('#^https?://#i', $url)) {
            return ['title' => '', 'description' => '', 'icon' => ''];
        }
        $html = self::download($url);
        if ($html === '') {
            return ['title' => '', 'description' => '', 'icon' => self::fallbackIcon($url)];
        }
        $title = '';
        if (preg_match('#<title[^>]*>(.*?)</title>#is', $html, $m)) {
            $title = self::text($m[1], 100);
        }
        $desc = '';
        foreach ([
            '#<meta[^>]+name=["\']description["\'][^>]+content=["\']([^"\']*)["\']#is',
            '#<meta[^>]+content=["\']([^"\']*)["\'][^>]+name=["\']description["\']#is',
            '#<meta[^>]+property=["\']og:description["\'][^>]+content=["\']([^"\']*)["\']#is',
        ] as $re) {
            if (preg_match($re, $html, $m)) {
                $desc = self::text($m[1], 255);
                break;
            }
        }
        if ($desc === '' && preg_match('#<meta[^>]+name=["\']keywords["\'][^>]+content=["\']([^"\']*)["\']#is', $html, $m)) {
            $desc = self::text($m[1], 255);
        }
        $icon = '';
        if (preg_match_all('#<link\b[^>]*>#is', $html, $links)) {
            foreach ($links[0] as $tag) {
                if (!preg_match('#rel=["\'][^"\']*(?:icon|apple-touch-icon)[^"\']*["\']#is', $tag)) {
                    continue;
                }
                if (!preg_match('#href=["\']([^"\']+)["\']#is', $tag, $m)) {
                    continue;
                }
                $icon = html_entity_decode(trim($m[1]), ENT_QUOTES, 'UTF-8');
                if ($icon !== '') {
                    break;
                }
            }
        }
        $icon = $icon !== '' ? self::resolve($url, $icon) : self::fallbackIcon($url);
        return ['title' => $title, 'description' => $desc, 'icon' => $icon];
    }

    private static function download(string $url): string
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 3,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT => 8,
                CURLOPT_USERAGENT => 'ChatBBS LinkMeta/1.0',
                CURLOPT_SSL_VERIFYPEER => true,
            ]);
            $body = curl_exec($ch);
            $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if (is_string($body) && $code >= 200 && $code < 400) {
                return substr($body, 0, 800000);
            }
        }
        $ctx = stream_context_create([
            'http' => ['timeout' => 8, 'user_agent' => 'ChatBBS LinkMeta/1.0', 'follow_location' => 1, 'max_redirects' => 3],
            'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
        ]);
        $body = @file_get_contents($url, false, $ctx, 0, 800000);
        return is_string($body) ? $body : '';
    }

    private static function text(string $value, int $max): string
    {
        $value = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = trim(preg_replace('/\s+/u', ' ', $value) ?? '');
        return mb_substr($value, 0, $max);
    }

    private static function resolve(string $baseUrl, string $href): string
    {
        if (preg_match('#^https?://#i', $href)) {
            return $href;
        }
        $p = parse_url($baseUrl);
        if (!$p || empty($p['scheme']) || empty($p['host'])) {
            return $href;
        }
        $origin = $p['scheme'] . '://' . $p['host'] . (isset($p['port']) ? ':' . $p['port'] : '');
        if (str_starts_with($href, '//')) {
            return $p['scheme'] . ':' . $href;
        }
        if (str_starts_with($href, '/')) {
            return $origin . $href;
        }
        $path = (string)($p['path'] ?? '/');
        $dir = substr($path, 0, (int)strrpos($path, '/') + 1);
        return $origin . $dir . $href;
    }

    private static function fallbackIcon(string $url): string
    {
        $p = parse_url($url);
        if (!$p || empty($p['scheme']) || empty($p['host'])) {
            return '';
        }
        return $p['scheme'] . '://' . $p['host'] . '/favicon.ico';
    }
}
