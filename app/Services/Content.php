<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Security;
use App\Models\Notification;

/**
 * 内容处理：清洗、@ 提及、附件绑定、敏感词
 */
class Content
{
    /**
     * 处理正文
     *
     * @param string $format rich（富文本，默认）/ markdown
     * @return array [html, text, md]  md 仅在 markdown 模式有值（用于再次编辑时还原源码）
     */
    public static function process(?string $raw, string $format = 'rich'): array
    {
        if ($format === 'markdown') {
            // Markdown：先对源码做敏感词处理，再由解析器转义后生成标签，天然免疫 XSS
            $md = Security::filterWords((string)$raw);
            $html = Markdown::render($md);
            return [$html, self::toText($html), $md];
        }

        $html = (string)$raw;
        // 纯文本（不含任何标签，如简洁模式回复）自动转换换行
        if (trim($html) !== '' && !preg_match('/<\/?[a-zA-Z][^>]*>/', $html)) {
            $html = nl2br(trim($html));
        }
        $html = Security::cleanHtml($html);
        $html = Security::filterWords($html);
        $text = self::toText($html);
        return [$html, $text, ''];
    }

    public static function toText(string $html): string
    {
        $text = str_ireplace(['<br>', '<br/>', '<br />', '</p>', '</div>', '</li>', '</h1>', '</h2>', '</h3>', '</h4>', '</h5>', '</h6>'], "\n", $html);
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace("/\n{3,}/", "\n\n", $text);
        return trim($text);
    }

    /**
     * 解析 @ 提及并发送通知
     */
    public static function mentions(string $text, int $fromId, string $type, int $targetId, string $link): int
    {
        if (!preg_match_all('/@([a-zA-Z0-9_\x{4e00}-\x{9fa5}]{2,20})/u', $text, $m)) {
            return 0;
        }
        $names = array_unique($m[1]);
        $n = 0;
        foreach ($names as $name) {
            $user = Database::fetch('SELECT id FROM ' . Database::table('users') . ' WHERE username = ? OR nickname = ? LIMIT 1', [$name, $name]);
            if (!$user) {
                continue;
            }
            Notification::send((int)$user['id'], 'mention', '有人提到了你', mb_substr($text, 0, 80), $link, $fromId);
            $n++;
        }
        return $n;
    }

    /**
     * 把 @xxx 转成可点击链接
     */
    public static function renderMentions(string $html): string
    {
        return preg_replace_callback('/@([a-zA-Z0-9_\x{4e00}-\x{9fa5}]{2,20})/u', function ($m) {
            $name = $m[1];
            return '<a class="mention" href="' . htmlspecialchars(url('user/' . rawurlencode($name))) . '">@' . htmlspecialchars($name) . '</a>';
        }, $html) ?? $html;
    }

    /**
     * 绑定内容中引用的附件到帖子
     */
    public static function bindAttachments(string $html, int $threadId, int $userId): void
    {
        if (preg_match_all('/data-upload-id="(\d+)"/', $html, $m)) {
            foreach (array_unique($m[1]) as $id) {
                Database::update('attachments', ['thread_id' => $threadId], 'id = ? AND user_id = ?', ['id' => (int)$id, 'uid' => $userId]);
            }
        }
    }

    /**
     * 图片懒加载
     */
    public static function lazyImages(string $html): string
    {
        return preg_replace_callback('/<img([^>]*)>/i', function ($m) {
            $attrs = $m[1];
            if (str_contains($attrs, 'loading=')) {
                return $m[0];
            }
            return '<img' . $attrs . ' loading="lazy">';
        }, $html) ?? $html;
    }

    /**
     * 摘要
     */
    public static function excerpt(?string $text, int $len = 120): string
    {
        $text = trim(strip_tags($text ?? ''));
        $text = preg_replace('/\s+/', ' ', $text) ?? '';
        return mb_strlen($text) > $len ? mb_substr($text, 0, $len) . '…' : $text;
    }

    /**
     * 简单代码高亮包装
     */
    public static function highlight(string $html): string
    {
        // 注意：分隔符用 ~，不能用 # —— 字符类里的 # 会被当成结束分隔符，
        // 导致 "Unknown modifier '-'"，发帖/编辑时正文代码块高亮直接失败
        return preg_replace_callback(
            '~<pre><code(?: class="language-([a-zA-Z0-9+#.-]+)")?>(.*?)</code></pre>~s',
            function ($m) {
                $lang = $m[1] ?? '';
                $code = html_entity_decode($m[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $code = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');
                return '<pre class="cb-code' . ($lang ? ' lang-' . htmlspecialchars($lang) : '') . '"><code>' . $code . '</code></pre>';
            },
            $html
        ) ?? $html;
    }
}
