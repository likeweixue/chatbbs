<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Wiki
{
    public static function list(int $limit = 100): array
    {
        return Database::fetchAll(
            'SELECT w.*, u.nickname, u.username FROM ' . Database::table('wiki_pages') . ' w
             LEFT JOIN ' . Database::table('users') . ' u ON u.id = w.user_id
             WHERE w.status = 1 ORDER BY w.updated_at DESC LIMIT ' . max(1, min(500, $limit))
        );
    }

    public static function findBySlug(string $slug): ?array
    {
        return Database::fetch(
            'SELECT w.*, u.nickname, u.username, u.avatar FROM ' . Database::table('wiki_pages') . ' w
             LEFT JOIN ' . Database::table('users') . ' u ON u.id = w.user_id
             WHERE w.slug = ? AND w.status = 1',
            [$slug]
        );
    }

    public static function slugExists(string $slug): bool
    {
        return (int)Database::value('SELECT COUNT(*) FROM ' . Database::table('wiki_pages') . ' WHERE slug = ?', [$slug], 0) > 0;
    }

    public static function create(array $data): int
    {
        return Database::insert('wiki_pages', $data);
    }

    public static function update(int $id, array $data): int
    {
        return Database::update('wiki_pages', $data, 'id = ?', ['id' => $id]);
    }

    public static function delete(int $id): void
    {
        Database::delete('wiki_pages', 'id = ?', ['id' => $id]);
    }
}
