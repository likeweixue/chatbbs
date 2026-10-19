<?php

declare(strict_types=1);

namespace App\Core;

/**
 * 文件缓存
 */
class Cache
{
    private static function file(string $key): string
    {
        return CB_CACHE . '/cache_' . md5($key) . '.php';
    }

    public static function get(string $key, $default = null)
    {
        $file = self::file($key);
        if (!is_file($file)) {
            return $default;
        }
        $data = @include $file;
        if (!is_array($data) || !isset($data['expire'], $data['value'])) {
            return $default;
        }
        if ($data['expire'] > 0 && $data['expire'] < time()) {
            @unlink($file);
            return $default;
        }
        return $data['value'];
    }

    public static function set(string $key, $value, int $ttl = 300): bool
    {
        if (!is_dir(CB_CACHE) && !@mkdir(CB_CACHE, 0755, true)) {
            return false;
        }
        $data = [
            'expire' => $ttl > 0 ? time() + $ttl : 0,
            'value' => $value,
        ];
        $content = '<?php return ' . var_export($data, true) . ';';
        return @file_put_contents(self::file($key), $content, LOCK_EX) !== false;
    }

    public static function forget(string $key): void
    {
        $file = self::file($key);
        if (is_file($file)) {
            @unlink($file);
        }
    }

    public static function flush(): int
    {
        $n = 0;
        foreach (glob(CB_CACHE . '/cache_*.php') ?: [] as $f) {
            @unlink($f);
            $n++;
        }
        return $n;
    }

    public static function remember(string $key, int $ttl, callable $cb)
    {
        $v = self::get($key);
        if ($v !== null) {
            return $v;
        }
        $v = $cb();
        self::set($key, $v, $ttl);
        return $v;
    }
}
