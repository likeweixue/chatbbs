<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Cache;

/**
 * 极简 RSS / Atom 读取器（后台配置订阅源，前台聚合展示）。
 */
class FeedReader
{
    public static function sources(): array
    {
        $raw = trim((string)\App\Core\Settings::get('reader_sources', ''));
        if ($raw === '') {
            return [];
        }
        $out = [];
        foreach (preg_split('/\r\n|\r|\n/', $raw) ?: [] as $line) {
            $parts = array_map('trim', explode('|', $line, 2));
            $url = (string)($parts[1] ?? '');
            if (!preg_match('#^https?://#i', $url)) {
                continue;
            }
            $out[] = ['name' => (string)($parts[0] ?? parse_url($url, PHP_URL_HOST)), 'url' => $url];
        }
        return $out;
    }

    public static function fetch(string $url, int $limit = 20): array
    {
        $limit = max(1, min(50, $limit));
        $key = 'feed_' . md5($url) . '_' . $limit;
        return Cache::remember($key, 1800, function () use ($url, $limit) {
            if (!function_exists('simplexml_load_string')) {
                return [];
            }
            $body = self::download($url);
            if ($body === '') {
                return [];
            }
            $xml = @simplexml_load_string($body, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOWARNING | LIBXML_NOERROR);
            if (!$xml) {
                return [];
            }
            $items = [];
            $nodes = isset($xml->channel->item) ? $xml->channel->item : ($xml->entry ?? []);
            foreach ($nodes as $node) {
                $title = trim(strip_tags((string)($node->title ?? '')));
                $link = (string)($node->link ?? '');
                if ($link === '' && isset($node->link['href'])) {
                    $link = (string)$node->link['href'];
                }
                $desc = (string)($node->description ?? ($node->summary ?? ($node->content ?? '')));
                $desc = trim(strip_tags(html_entity_decode($desc, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
                $date = (string)($node->pubDate ?? ($node->updated ?? ($node->published ?? '')));
                if ($title === '' || $link === '') {
                    continue;
                }
                $items[] = [
                    'title' => mb_substr($title, 0, 200),
                    'link' => $link,
                    'description' => mb_substr(preg_replace('/\s+/u', ' ', $desc) ?? '', 0, 300),
                    'date' => $date,
                ];
                if (count($items) >= $limit) {
                    break;
                }
            }
            return $items;
        });
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
                CURLOPT_USERAGENT => 'ChatBBS FeedReader/1.0',
            ]);
            $body = curl_exec($ch);
            $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            return is_string($body) && $code >= 200 && $code < 400 ? $body : '';
        }
        $ctx = stream_context_create(['http' => ['timeout' => 8, 'user_agent' => 'ChatBBS FeedReader/1.0', 'follow_location' => 1]]);
        $body = @file_get_contents($url, false, $ctx);
        return is_string($body) ? $body : '';
    }
}
