<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Settings;
use App\Models\Thread;

/**
 * 全站 RSS 2.0 输出。
 * 可通过 ?forum=ID / ?tag=ID 只订阅指定板块或标签；默认读取后台设置。
 */
class RssController extends BaseController
{
    public function index(): void
    {
        if ((int)Settings::get('rss_enable', 1) !== 1) {
            http_response_code(404);
            echo 'RSS is disabled.';
            exit;
        }
        $limit = max(1, min(100, (int)Settings::get('rss_limit', 20)));
        $forumId = max(0, (int)($_GET['forum'] ?? Settings::get('rss_forum', 0)));
        $tagId = max(0, (int)($_GET['tag'] ?? Settings::get('rss_tag', 0)));
        $result = Thread::list([
            'forum_id' => $forumId,
            'tag_id' => $tagId,
            'per_page' => $limit,
            'page' => 1,
            'order' => 'new',
        ]);

        $siteName = (string)Settings::get('site_name', 'ChatBBS');
        $title = trim((string)Settings::get('rss_title', ''));
        if ($title === '') {
            $title = $siteName . ' - 最新主题';
        }
        $base = site_base();
        $self = url('rss');
        $desc = (string)Settings::get('site_description', '');
        $channelTitle = $this->xml($title);
        $channelLink = $this->xml($base . '/');
        $channelDesc = $this->xml($desc !== '' ? $desc : $siteName);

        header('Content-Type: application/rss+xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">' . "\n";
        echo '<channel>' . "\n";
        echo '<title>' . $channelTitle . '</title>' . "\n";
        echo '<link>' . $channelLink . '</link>' . "\n";
        echo '<description>' . $channelDesc . '</description>' . "\n";
        echo '<language>zh-CN</language>' . "\n";
        echo '<lastBuildDate>' . date(DATE_RSS) . '</lastBuildDate>' . "\n";
        echo '<atom:link href="' . $this->xml($self) . '" rel="self" type="application/rss+xml"/>' . "\n";
        foreach ($result['items'] as $t) {
            $link = url('thread/' . (int)$t['id']);
            $body = trim(strip_tags((string)($t['content_text'] ?? '')));
            if (mb_strlen($body) > 300) {
                $body = mb_substr($body, 0, 300) . '…';
            }
            $pub = strtotime((string)$t['created_at']) ?: time();
            echo '<item>' . "\n";
            echo '<title>' . $this->xml((string)$t['title']) . '</title>' . "\n";
            echo '<link>' . $this->xml($link) . '</link>' . "\n";
            echo '<guid isPermaLink="true">' . $this->xml($link) . '</guid>' . "\n";
            echo '<pubDate>' . date(DATE_RSS, $pub) . '</pubDate>' . "\n";
            echo '<description>' . $this->xml($body) . '</description>' . "\n";
            echo '</item>' . "\n";
        }
        echo '</channel>' . "\n" . '</rss>';
        exit;
    }

    private function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
