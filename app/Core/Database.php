<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

/**
 * 数据库 PDO 封装
 */
class Database
{
    private static ?PDO $pdo = null;
    private static array $cfg = [];
    public static int $queryCount = 0;

    public static function init(array $cfg): void
    {
        self::$cfg = $cfg;
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $cfg['host'] ?? '127.0.0.1',
            $cfg['port'] ?? '3306',
            $cfg['database'] ?? '',
            $cfg['charset'] ?? 'utf8mb4'
        );
        try {
            self::$pdo = new PDO($dsn, $cfg['username'] ?? 'root', $cfg['password'] ?? '', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES ' . ($cfg['charset'] ?? 'utf8mb4'),
            ]);
        } catch (PDOException $e) {
            throw new \RuntimeException('数据库连接失败：' . $e->getMessage());
        }
        $prefix = $cfg['prefix'] ?? 'cb_';
        defined('DB_PREFIX') || define('DB_PREFIX', $prefix);
    }

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            throw new \RuntimeException('数据库尚未初始化');
        }
        return self::$pdo;
    }

    public static function connected(): bool
    {
        return self::$pdo !== null;
    }

    public static function prefix(): string
    {
        return self::$cfg['prefix'] ?? 'cb_';
    }

    /**
     * 表名（自动加前缀）
     */
    public static function table(string $name): string
    {
        $prefix = self::prefix();
        if (strpos($name, $prefix) === 0) {
            return $name;
        }
        return $prefix . $name;
    }

    private static function prepare(string $sql): \PDOStatement
    {
        self::$queryCount++;
        return self::pdo()->prepare($sql);
    }

    public static function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = self::prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function fetch(string $sql, array $params = []): ?array
    {
        $stmt = self::query($sql, $params);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public static function fetchAll(string $sql, array $params = []): array
    {
        $stmt = self::query($sql, $params);
        return $stmt->fetchAll();
    }

    public static function value(string $sql, array $params = [], $default = null)
    {
        $stmt = self::query($sql, $params);
        $v = $stmt->fetchColumn();
        return $v === false ? $default : $v;
    }

    public static function count(string $table, string $where = '1=1', array $params = []): int
    {
        $sql = 'SELECT COUNT(*) FROM ' . self::table($table) . ' WHERE ' . $where;
        return (int)self::value($sql, $params, 0);
    }

    public static function insert(string $table, array $data): int
    {
        $cols = array_keys($data);
        $sql = 'INSERT INTO ' . self::table($table)
            . ' (`' . implode('`,`', $cols) . '`) VALUES ('
            . implode(',', array_map(fn($c) => ':' . $c, $cols)) . ')';
        $params = [];
        foreach ($data as $k => $v) {
            $params[':' . $k] = $v;
        }
        self::query($sql, $params);
        return (int)self::pdo()->lastInsertId();
    }

    public static function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $sets = [];
        $params = [];
        foreach ($data as $k => $v) {
            $sets[] = '`' . $k . '` = :set_' . $k;
            $params[':set_' . $k] = $v;
        }
        if (str_contains($where, '?')) {
            // WHERE 使用位置参数 ? 时自动转为命名参数，避免与 SET 的命名参数混用（HY093）
            $wp = array_values($whereParams);
            $i = 0;
            $where = preg_replace_callback('/\?/', static function () use ($wp, &$i, &$params) {
                $key = ':w' . $i;
                $params[$key] = $wp[$i] ?? null;
                $i++;
                return $key;
            }, $where);
        } else {
            foreach ($whereParams as $k => $v) {
                $params[':' . ltrim((string)$k, ':')] = $v;
            }
        }
        $sql = 'UPDATE ' . self::table($table) . ' SET ' . implode(',', $sets) . ' WHERE ' . $where;
        return self::query($sql, $params)->rowCount();
    }

    public static function delete(string $table, string $where, array $params = []): int
    {
        $bind = [];
        if (str_contains($where, '?')) {
            $wp = array_values($params);
            $i = 0;
            $where = preg_replace_callback('/\?/', static function () use ($wp, &$i, &$bind) {
                $key = ':w' . $i;
                $bind[$key] = $wp[$i] ?? null;
                $i++;
                return $key;
            }, $where);
        } else {
            foreach ($params as $k => $v) {
                $bind[':' . ltrim((string)$k, ':')] = $v;
            }
        }
        return self::query('DELETE FROM ' . self::table($table) . ' WHERE ' . $where, $bind)->rowCount();
    }

    public static function begin(): void
    {
        self::pdo()->beginTransaction();
    }

    public static function commit(): void
    {
        self::pdo()->commit();
    }

    public static function rollback(): void
    {
        if (self::pdo()->inTransaction()) {
            self::pdo()->rollBack();
        }
    }

    public static function execSqlFile(string $file): void
    {
        $sql = file_get_contents($file);
        $sql = preg_replace('/^--.*$/m', '', $sql);
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        foreach ($statements as $statement) {
            if ($statement === '') {
                continue;
            }
            self::pdo()->exec($statement);
        }
    }
}
