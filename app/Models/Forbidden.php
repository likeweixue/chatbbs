<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Cache;
use App\Core\Database;
use App\Core\Settings;

/**
 * 违禁词库
 *
 * 命中即禁止发布（区别于「敏感词」—— 敏感词只是替换或转审核）。
 */
class Forbidden
{
    /**
     * 取全部启用的违禁词（带缓存）
     *
     * @return string[]
     */
    public static function words(): array
    {
        if (!self::ready()) {
            return [];
        }
        return Cache::remember('forbidden_words', 300, static function (): array {
            try {
                $rows = Database::fetchAll(
                    'SELECT word FROM ' . Database::table('forbidden_words')
                    . ' WHERE enabled = 1 AND word <> \'\' ORDER BY word ASC'
                );
            } catch (\Throwable $e) {
                return [];
            }
            return array_values(array_unique(array_filter(array_map('trim', array_column($rows, 'word')))));
        });
    }

    /**
     * 检测文本命中的违禁词
     *
     * @return array 命中的违禁词列表（原样返回词库中的大小写）
     */
    public static function check(?string $text): array
    {
        $text = (string)$text;
        if ($text === '') {
            return [];
        }
        $words = self::words();
        if (!$words) {
            return [];
        }
        // 去掉 HTML 标签后再匹配，避免把标签属性里的字符误判
        $plain = strip_tags($text);
        $lower = mb_strtolower($plain);
        $hits = [];
        foreach ($words as $w) {
            if ($w === '') {
                continue;
            }
            if (mb_stripos($lower, mb_strtolower($w)) !== false) {
                $hits[] = $w;
            }
        }
        return array_values(array_unique($hits));
    }

    /**
     * 命中后累加统计（用于后台了解哪些词最常触发）
     */
    public static function countHits(array $words): void
    {
        if (!$words || !self::ready()) {
            return;
        }
        try {
            foreach ($words as $w) {
                Database::query(
                    'UPDATE ' . Database::table('forbidden_words') . ' SET hits = hits + 1 WHERE word = ?',
                    [$w]
                );
            }
        } catch (\Throwable $e) {
            // 统计失败不影响主流程
        }
    }

    public static function clearCache(): void
    {
        Cache::forget('forbidden_words');
    }

    /**
     * 判断词库功能是否可用（未启用开关或表不存在时跳过）
     */
    public static function enabled(): bool
    {
        return (int)Settings::get('forbidden_words_enable', 1) === 1 && self::ready();
    }

    private static function ready(): bool
    {
        static $ok = null;
        if ($ok === null) {
            try {
                Database::fetch(
                    'SELECT COUNT(*) AS c FROM information_schema.TABLES
                     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
                    [Database::table('forbidden_words')]
                );
                $ok = true;
            } catch (\Throwable $e) {
                $ok = false;
            }
        }
        return $ok;
    }
}
