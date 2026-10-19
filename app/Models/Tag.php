<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Tag
{
    public static function all(int $limit = 0): array
    {
        $sql = 'SELECT * FROM ' . Database::table('tags') . ' WHERE status = 1 ORDER BY hot DESC, threads DESC, id DESC';
        if ($limit > 0) {
            $sql .= ' LIMIT ' . (int)$limit;
        }
        return Database::fetchAll($sql);
    }

    public static function find(int $id): ?array
    {
        return Database::fetch('SELECT * FROM ' . Database::table('tags') . ' WHERE id = ?', [$id]);
    }

    public static function findByName(string $name): ?array
    {
        return Database::fetch('SELECT * FROM ' . Database::table('tags') . ' WHERE name = ?', [$name]);
    }

    public static function create(string $name, array $extra = []): int
    {
        $slug = urlencode(mb_substr($name, 0, 40));
        return Database::insert('tags', array_merge([
            'name' => $name,
            'slug' => $slug,
            'status' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ], $extra));
    }

    public static function update(int $id, array $data): int
    {
        return Database::update('tags', $data, 'id = ?', ['id' => $id]);
    }

    public static function delete(int $id): int
    {
        Database::delete('thread_tags', 'tag_id = ?', ['tid' => $id]);
        return Database::delete('tags', 'id = ?', ['id' => $id]);
    }

    /**
     * 解析用户输入的标签（支持逗号分隔），自动创建不存在的
     */
    public static function resolve(array $names, bool $allowCreate = false): array
    {
        $ids = [];
        foreach ($names as $name) {
            $name = trim((string)$name);
            if ($name === '') {
                continue;
            }
            $tag = self::findByName($name);
            if (!$tag) {
                if (!$allowCreate) {
                    continue;
                }
                $id = self::create($name, ['status' => 1]);
            } else {
                $id = (int)$tag['id'];
            }
            if (!in_array($id, $ids, true)) {
                $ids[] = $id;
            }
        }
        return $ids;
    }

    /**
     * 标签合并：把 source 合并到 target
     */
    public static function merge(int $sourceId, int $targetId): void
    {
        $rows = Database::fetchAll('SELECT thread_id FROM ' . Database::table('thread_tags') . ' WHERE tag_id = ?', [$sourceId]);
        foreach ($rows as $r) {
            $exists = Database::value(
                'SELECT 1 FROM ' . Database::table('thread_tags') . ' WHERE thread_id = ? AND tag_id = ?',
                [$r['thread_id'], $targetId]
            );
            if (!$exists) {
                Database::insert('thread_tags', ['thread_id' => $r['thread_id'], 'tag_id' => $targetId]);
            }
        }
        self::delete($sourceId);
    }

    public static function hot(int $limit = 20): array
    {
        return Database::fetchAll(
            'SELECT * FROM ' . Database::table('tags') . ' WHERE status = 1 ORDER BY threads DESC, hot DESC LIMIT ' . (int)$limit
        );
    }
}
