<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * 商城商品
 */
class ShopItem
{
    public static function all(bool $onlyVisible = true): array
    {
        $where = $onlyVisible ? ' WHERE status = 1' : '';
        return Database::fetchAll(
            'SELECT * FROM ' . Database::table('shop_items') . $where . ' ORDER BY id DESC'
        );
    }

    public static function find(int $id): ?array
    {
        return Database::fetch('SELECT * FROM ' . Database::table('shop_items') . ' WHERE id = ?', [$id]);
    }

    public static function create(array $data): int
    {
        return Database::insert('shop_items', array_merge(['created_at' => date('Y-m-d H:i:s')], $data));
    }

    public static function update(int $id, array $data): int
    {
        return Database::update('shop_items', $data, 'id = ?', ['i' => $id]);
    }

    public static function delete(int $id): void
    {
        Database::delete('shop_items', 'id = ?', ['i' => $id]);
    }
}
