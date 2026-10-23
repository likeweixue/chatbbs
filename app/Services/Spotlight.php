<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Cache;
use App\Core\Database;
use App\Core\Settings;
use App\Models\Thread;

/**
 * 板块页顶部「聚合」：把最新 / 回复 / 热门 / 精华 / 公告等帖子各取几条，拼成几个格子。
 */
class Spotlight
{
    /**
     * 可选类型（后台下拉与前台标题共用）
     */
    public static function types(): array
    {
        return [
            'new' => '最新帖子',
            'reply' => '最新回复',
            'hot' => '热门',
            'essence' => '精华帖',
            'announce' => '公告',
            'recommend' => '推荐',
            'avoid' => '避雷',
        ];
    }

    public static function defaults(): array
    {
        return [
            ['t' => 'new', 'title' => '最新帖子'],
            ['t' => 'reply', 'title' => '最新回复'],
            ['t' => 'essence', 'title' => '精华帖'],
            ['t' => 'announce', 'title' => '公告'],
        ];
    }

    /**
     * 读取后台配置（即使总开关关闭也返回，供后台表单回填）
     */
    public static function items(): array
    {
        $raw = json_decode((string)Settings::get('forum_spotlight_items', ''), true);
        if (!is_array($raw)) {
            $raw = self::defaults();
        }
        $types = self::types();
        $out = [];
        foreach ($raw as $it) {
            if (!is_array($it)) {
                continue;
            }
            $t = (string)($it['t'] ?? '');
            if (!isset($types[$t])) {
                continue;
            }
            $title = trim((string)($it['title'] ?? ''));
            $out[] = ['t' => $t, 'title' => $title !== '' ? $title : $types[$t]];
        }
        return $out;
    }

    /**
     * 生成前台格子（总开关关闭时返回空数组）
     */
    public static function cells(int $limit = 0): array
    {
        if (!(int)Settings::get('forum_spotlight_enable', 1)) {
            return [];
        }
        if ($limit <= 0) {
            $limit = (int)Settings::get('forum_spotlight_limit', 5);
        }
        $limit = max(1, min(10, $limit));
        $ttl = max(0, min(3600, (int)Settings::get('forum_spotlight_ttl', 60)));
        $build = static function () use ($limit): array {
            return self::buildCells($limit);
        };
        return $ttl > 0 ? Cache::remember('forum_spotlight_cells', $ttl, $build) : $build();
    }

    /**
     * 实际查询四个格子的数据
     */
    private static function buildCells(int $limit): array
    {
        $out = [];
        foreach (self::items() as $item) {
            $opt = ['per_page' => max(1, min(10, $limit)), 'order' => 'default'];
            switch ($item['t']) {
                case 'new':
                    $opt['order'] = 'new';
                    break;
                case 'reply':
                    $opt['order'] = 'default';
                    break;
                case 'hot':
                    $opt['order'] = 'hot';
                    break;
                case 'essence':
                    $opt['essence'] = 1;
                    break;
                case 'announce':
                    $opt['announce'] = 1;
                    $opt['order'] = 'new';
                    break;
                case 'recommend':
                    $opt['recommend_kind'] = 1;
                    break;
                case 'avoid':
                    $opt['recommend_kind'] = -1;
                    break;
            }
            $result = Thread::list($opt);
            $item['threads'] = $result['items'];
            $item['more'] = self::moreUrl($item['t']);
            $out[] = $item;
        }
        return $out;
    }

    public static function moreUrl(string $type): string
    {
        if ($type === 'announce') {
            $id = (int)Database::value(
                'SELECT id FROM ' . Database::table('forums') . ' WHERE slug = ? OR name LIKE ? ORDER BY id LIMIT 1',
                ['notice', '%公告%'],
                0
            );
            return $id > 0 ? url('forum/' . $id) : '';
        }
        $map = [
            'new' => '?tab=new',
            'reply' => '?tab=default',
            'hot' => '?tab=hot',
            'essence' => '?tab=essence',
            'recommend' => '?tab=recommend',
            'avoid' => '?tab=avoid',
        ];
        return isset($map[$type]) ? url($map[$type]) : '';
    }
}
