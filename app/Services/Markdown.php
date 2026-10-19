<?php

declare(strict_types=1);

namespace App\Services;

/**
 * 轻量 Markdown 解析器（编辑器 Markdown 模式使用）
 *
 * 设计原则：
 * 1. 先对整个源文本做 HTML 转义，再生成标签 —— 因此 Markdown 模式下不会引入 XSS；
 * 2. 支持代码块 / 标题 / 列表 / 引用 / 表格 / 分割线 / 行内样式等常用语法；
 * 3. 不依赖第三方库，零 Composer。
 */
class Markdown
{
    /**
     * 行内代码占位符（避免内部再被加粗/链接等规则处理）
     *
     * @var array<string, string>
     */
    private static array $placeholders = [];

    public static function render(string $md): string
    {
        $md = str_replace(["\r\n", "\r"], "\n", $md ?? '');
        $md = str_replace("\t", '    ', $md);
        $lines = explode("\n", $md);
        $html = '';
        $para = [];
        $n = count($lines);
        $i = 0;

        $flush = function () use (&$html, &$para): void {
            if (!$para) {
                return;
            }
            $text = self::inline(implode("\n", $para));
            $html .= '<p>' . self::restoreCode($text) . "</p>\n";
            $para = [];
        };

        // 整体步数兜底：任何分支逻辑异常都不会把请求拖到 PHP 超时（曾经因此 500）
        $steps = 0;
        $maxSteps = max(20000, $n * 4);

        while ($i < $n) {
            if (++$steps > $maxSteps) {
                $flush();
                break;
            }
            $line = $lines[$i];
            $trim = trim($line);

            // 空行：结束当前段落
            if ($trim === '') {
                $flush();
                $i++;
                continue;
            }

            // 围栏代码块
            if (preg_match('/^(```|~~~)\s*([a-zA-Z0-9+#._-]*)\s*$/', $trim, $m)) {
                $flush();
                $lang = strtolower((string)$m[2]);
                $i++;
                $buf = [];
                while ($i < $n && trim($lines[$i]) !== $m[1]) {
                    $buf[] = $lines[$i];
                    $i++;
                }
                $i++; // 跳过结束围栏
                $cls = $lang !== '' ? ' class="language-' . htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') . '"' : '';
                $html .= '<pre class="cb-code' . ($lang ? ' lang-' . htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') : '') . '">'
                    . '<code' . $cls . '>' . htmlspecialchars(implode("\n", $buf), ENT_QUOTES, 'UTF-8') . '</code></pre>' . "\n";
                continue;
            }

            // 分割线
            // 注意：不要写成 (\*\s*){3,} —— 这种「分组重复 + 尾部空白」的写法在长行上会
            // 触发灾难性回溯（ReDoS），曾经把整页拖到 30 秒超时。写成 \*{3,}\s*$ 是线性的。
            if (preg_match('/^\*{3,}\s*$/', $trim) || preg_match('/^-{3,}\s*$/', $trim) || preg_match('/^_{3,}\s*$/', $trim)) {
                $flush();
                $html .= "<hr>\n";
                $i++;
                continue;
            }

            // 标题
            if (preg_match('/^(#{1,6})\s+(.*)$/', $trim, $m)) {
                $flush();
                $level = strlen($m[1]);
                $html .= '<h' . $level . '>' . self::restoreCode(self::inline(trim($m[2]))) . '</h' . $level . ">\n";
                $i++;
                continue;
            }

            // 引用
            if (str_starts_with($trim, '>')) {
                $flush();
                $buf = [];
                while ($i < $n) {
                    $l = trim($lines[$i]);
                    if (!str_starts_with($l, '>')) {
                        break;
                    }
                    $buf[] = preg_replace('/^>\s?/', '', $l) ?? '';
                    $i++;
                }
                $html .= '<blockquote>' . trim(self::render(implode("\n", $buf))) . "</blockquote>\n";
                continue;
            }

            // 列表（有序 / 无序，支持 2 空格缩进嵌套）
            if (preg_match('/^([-*+])\s+/', $trim) || preg_match('/^\d+[.)]\s+/', $trim)) {
                $flush();
                $html .= self::renderList($lines, $i, 0);
                continue;
            }

            // 表格
            if (str_contains($trim, '|') && $i + 1 < $n && preg_match('/^\|?[\s:\-|]+\|[\s:\-|]*$/', trim($lines[$i + 1]))) {
                $flush();
                $head = self::splitRow($trim);
                $align = array_map([self::class, 'alignOf'], self::splitRow(trim($lines[$i + 1])));
                $i += 2;
                $rows = [];
                while ($i < $n && trim($lines[$i]) !== '' && str_contains(trim($lines[$i]), '|')) {
                    $rows[] = self::splitRow(trim($lines[$i]));
                    $i++;
                }
                $html .= self::renderTable($head, $align, $rows);
                continue;
            }

            $para[] = $line;
            $i++;
        }
        $flush();

        return trim($html);
    }

    /**
     * 递归渲染列表（按缩进缩进一级）
     */
    private static function renderList(array $lines, int &$i, int $indent, int $depth = 0): string
    {
        $n = count($lines);
        // 按首行判断是有序还是无序列表
        $isOrdered = (bool)preg_match('/^\d+[.)]\s+/', trim((string)($lines[$i] ?? '')));
        $tag = $isOrdered ? 'ol' : 'ul';
        /** @var array<int, array{html:string, sub:string}> $items */
        $items = [];
        $guard = 0;
        $maxGuard = max(5000, $n * 2);

        while ($i < $n) {
            // 步数兜底：旧实现在「缩进的续行」上会既不消费也不退出 → 死循环 → 30 秒超时 500
            if (++$guard > $maxGuard) {
                break;
            }
            $raw = $lines[$i];
            $trim = trim($raw);

            if ($trim === '') {
                // 允许列表项之间夹一个空行
                if ($i + 1 < $n) {
                    $next = $lines[$i + 1];
                    $lead = strlen($next) - strlen(ltrim($next, ' '));
                    if ($lead >= $indent && preg_match('/^([-*+]|\d+[.)])\s+/', trim($next))) {
                        $i++;
                        continue;
                    }
                }
                break;
            }

            $lead = strlen($raw) - strlen(ltrim($raw, ' '));
            if ($lead < $indent) {
                break;
            }

            // 缩进更深：先判断它是「子列表」还是「上一项的续行」，
            // 二者都与当前列表是有序 / 无序无关，必须在类型判断之前处理
            if ($lead > $indent) {
                $isItem = (bool)preg_match('/^([-*+]|\d+[.)])\s+/', $trim);
                if ($isItem && $depth < 6) {
                    // 真正的子列表：递归处理（递归前先确认它是列表项，否则会空转而死循环）
                    $sub = self::renderList($lines, $i, $lead, $depth + 1);
                    if ($items) {
                        $items[count($items) - 1]['sub'] .= $sub;
                    } else {
                        $items[] = ['html' => '', 'sub' => $sub];
                    }
                    continue;
                }
                // 缩进但不是列表项 = 上一项的续行（如换行后对齐的说明文字）
                $text = self::restoreCode(self::inline($trim));
                if ($items) {
                    $items[count($items) - 1]['html'] .= "<br>\n" . $text;
                } else {
                    $items[] = ['html' => $text, 'sub' => ''];
                }
                $i++;
                continue;
            }

            // 同一缩进层级：有序 / 无序类型切换时结束当前列表，交给外层重新开一个
            if (((bool)preg_match('/^\d+[.)]\s+/', $trim)) !== $isOrdered) {
                break;
            }

            $text = null;
            if (preg_match('/^([-*+])\s+(.*)$/', $trim, $m)) {
                $text = $m[2];
            } elseif (preg_match('/^\d+[.)]\s+(.*)$/', $trim, $m2)) {
                // 注意：\d+ 未加括号，所以正文是第 1 个捕获组（旧代码写 $m2[2] 会拿到空串）
                $text = $m2[1];
            }
            if ($text === null) {
                break;
            }
            $items[] = ['html' => self::restoreCode(self::inline($text)), 'sub' => ''];
            $i++;
        }

        $html = '<' . $tag . ">\n";
        foreach ($items as $it) {
            $html .= '<li>' . $it['html'];
            if (trim($it['sub']) !== '') {
                $html .= "\n" . trim($it['sub']);
            }
            $html .= "</li>\n";
        }
        return $html . '</' . $tag . ">\n";
    }

    private static function renderTable(array $head, array $align, array $rows): string
    {
        $html = "<table>\n<thead><tr>";
        foreach ($head as $k => $cell) {
            $style = ($align[$k] ?? '') !== '' ? ' style="text-align:' . $align[$k] . '"' : '';
            $html .= '<th' . $style . '>' . self::restoreCode(self::inline($cell)) . '</th>';
        }
        $html .= "</tr></thead>\n<tbody>\n";
        foreach ($rows as $row) {
            $html .= '<tr>';
            foreach ($head as $k => $_) {
                $cell = $row[$k] ?? '';
                $style = ($align[$k] ?? '') !== '' ? ' style="text-align:' . $align[$k] . '"' : '';
                $html .= '<td' . $style . '>' . self::restoreCode(self::inline($cell)) . '</td>';
            }
            $html .= "</tr>\n";
        }
        return $html . "</tbody>\n</table>\n";
    }

    private static function splitRow(string $row): array
    {
        $row = trim($row, "| \t");
        return array_map(static fn($c) => trim($c), explode('|', $row));
    }

    private static function alignOf(string $cell): string
    {
        $cell = trim($cell);
        if (str_starts_with($cell, ':') && str_ends_with($cell, ':')) {
            return 'center';
        }
        if (str_ends_with($cell, ':')) {
            return 'right';
        }
        return '';
    }

    /**
     * 行内解析（入参为原始 md 文本，先转义再生成标签）
     */
    public static function inline(string $text): string
    {
        self::$placeholders = [];
        $s = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

        // 行内代码先占位
        $s = preg_replace_callback('/`([^`]+)`/', static function ($m) {
            $key = "\x01CBCODE" . count(self::$placeholders) . "\x01";
            self::$placeholders[$key] = '<code>' . $m[1] . '</code>';
            return $key;
        }, $s) ?? $s;

        // 图片
        $s = preg_replace_callback('/!\[([^\]]*)\]\(([^)\s]+)(?:\s+&quot;([^&]*)&quot;)?\)/', static function ($m) {
            $url = self::safeUrl(html_entity_decode($m[2], ENT_QUOTES, 'UTF-8'));
            if ($url === '') {
                return $m[0];
            }
            return '<img src="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" alt="' . $m[1] . '" loading="lazy">';
        }, $s) ?? $s;

        // 链接
        $s = preg_replace_callback('/\[([^\]]*)\]\(([^)\s]+)(?:\s+&quot;([^&]*)&quot;)?\)/', static function ($m) {
            $url = self::safeUrl(html_entity_decode($m[2], ENT_QUOTES, 'UTF-8'));
            if ($url === '') {
                return $m[0];
            }
            $title = isset($m[3]) && $m[3] !== '' ? ' title="' . $m[3] . '"' : '';
            return '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" rel="nofollow noopener"' . $title . '>' . $m[1] . '</a>';
        }, $s) ?? $s;

        $s = preg_replace('/\*\*([^*<]+)\*\*/', '<strong>$1</strong>', $s) ?? $s;
        $s = preg_replace('/__([^_<]+)__/', '<strong>$1</strong>', $s) ?? $s;
        $s = preg_replace('/~~([^~<]+)~~/', '<del>$1</del>', $s) ?? $s;
        $s = preg_replace('/(^|[^*\w])\*([^*\n<]+)\*/', '$1<em>$2</em>', $s) ?? $s;
        $s = preg_replace('/(^|[^_\w])_([^_\n<]+)_/', '$1<em>$2</em>', $s) ?? $s;

        // 自动链接（未被显式链接过的裸地址）
        $s = preg_replace_callback('#(?<!["\'=])(https?://[^\s<\)]+)#i', static function ($m) {
            return '<a href="' . $m[1] . '" rel="nofollow noopener" target="_blank">' . $m[1] . '</a>';
        }, $s) ?? $s;

        // 单换行转 <br>
        $s = str_replace("\n", "<br>\n", $s);

        return $s;
    }

    private static function restoreCode(string $html): string
    {
        if (!self::$placeholders) {
            return $html;
        }
        return str_replace(array_keys(self::$placeholders), array_values(self::$placeholders), $html);
    }

    private static function safeUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        if (str_starts_with($url, '/') || str_starts_with($url, '#')) {
            return $url;
        }
        return preg_match('#^https?://#i', $url) ? $url : '';
    }
}
