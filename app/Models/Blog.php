<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * 博客：用户文章与分类
 */
class Blog
{
    public static function posts(int $limit = 100, int $categoryId = 0, int $userId = 0): array
    {
        $where = 'status = 1';
        $params = [];
        if ($categoryId > 0) {
            $where .= ' AND category_id = ?';
            $params[] = $categoryId;
        }
        if ($userId > 0) {
            $where .= ' AND user_id = ?';
            $params[] = $userId;
        }
        return Database::fetchAll(
            'SELECT * FROM ' . Database::table('blog_posts') . ' WHERE ' . $where . ' ORDER BY id DESC LIMIT ' . max(1, min(500, $limit)),
            $params
        );
    }

    public static function allForAdmin(): array
    {
        return Database::fetchAll('SELECT * FROM ' . Database::table('blog_posts') . ' ORDER BY id DESC');
    }

    public static function find(int $id): ?array
    {
        return Database::fetch('SELECT * FROM ' . Database::table('blog_posts') . ' WHERE id = ?', [$id]);
    }

    public static function create(array $data): int
    {
        return Database::insert('blog_posts', array_merge(['created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')], $data));
    }

    public static function update(int $id, array $data): int
    {
        return Database::update('blog_posts', $data, 'id = ?', ['i' => $id]);
    }

    public static function delete(int $id): void
    {
        Database::delete('blog_posts', 'id = ?', ['i' => $id]);
    }

    public static function view(int $id): void
    {
        Database::query('UPDATE ' . Database::table('blog_posts') . ' SET views = views + 1 WHERE id = ?', [$id]);
    }

    public static function categories(int $userId = 0): array
    {
        $where = $userId > 0 ? ' WHERE user_id = ?' : '';
        return Database::fetchAll(
            'SELECT * FROM ' . Database::table('blog_categories') . $where . ' ORDER BY id ASC',
            $userId > 0 ? [$userId] : []
        );
    }

    /** 按名称获取或创建用户分类 */
    public static function categoryId(int $userId, string $name): int
    {
        $name = trim($name);
        if ($name === '') {
            return 0;
        }
        $row = Database::fetch(
            'SELECT id FROM ' . Database::table('blog_categories') . ' WHERE user_id = ? AND name = ?',
            [$userId, $name]
        );
        if ($row) {
            return (int)$row['id'];
        }
        $slug = 'bc' . substr(md5($userId . $name . microtime(true)), 0, 8);
        return Database::insert('blog_categories', [
            'user_id' => $userId,
            'name' => mb_substr($name, 0, 80),
            'slug' => $slug,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
