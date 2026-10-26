<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class NavLink
{
    public static function list(bool $onlyVisible = false): array
    {
        $where = $onlyVisible ? 'WHERE visible = 1' : '';
        return Database::fetchAll(
            'SELECT * FROM ' . Database::table('nav_links') . ' ' . $where . ' ORDER BY sort_order ASC, id ASC'
        );
    }

    public static function find(int $id): ?array
    {
        return Database::fetch('SELECT * FROM ' . Database::table('nav_links') . ' WHERE id = ?', [$id]);
    }

    public static function create(array $data): int
    {
        return Database::insert('nav_links', $data);
    }

    public static function update(int $id, array $data): int
    {
        return Database::update('nav_links', $data, 'id = ?', ['id' => $id]);
    }

    public static function delete(int $id): void
    {
        Database::delete('nav_links', 'id = ?', ['id' => $id]);
    }
}
