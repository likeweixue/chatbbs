<?php

declare(strict_types=1);

namespace App\Core;

/**
 * HTTP 请求封装
 */
class Request
{
    public static function method(): string
    {
        return strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    }

    public static function isPost(): bool
    {
        return self::method() === 'POST';
    }

    public static function isAjax(): bool
    {
        $x = strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));
        return $x === 'xmlhttprequest'
            || str_contains(strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? '')), 'application/json');
    }

    public static function input(string $key, $default = null)
    {
        $v = $_POST[$key] ?? $_GET[$key] ?? $default;
        return is_string($v) ? trim($v) : $v;
    }

    public static function post(string $key, $default = null)
    {
        $v = $_POST[$key] ?? $default;
        return is_string($v) ? trim($v) : $v;
    }

    public static function get(string $key, $default = null)
    {
        $v = $_GET[$key] ?? $default;
        return is_string($v) ? trim($v) : $v;
    }

    public static function int(string $key, int $default = 0): int
    {
        return (int)self::input($key, $default);
    }

    public static function bool(string $key): bool
    {
        $v = self::input($key, '0');
        return in_array(strtolower((string)$v), ['1', 'on', 'true', 'yes'], true);
    }

    public static function raw(string $key, $default = ''): string
    {
        return (string)($_POST[$key] ?? $_GET[$key] ?? $default);
    }

    public static function has(string $key): bool
    {
        return isset($_POST[$key]) || isset($_GET[$key]);
    }

    public static function all(): array
    {
        return array_merge($_GET, $_POST);
    }

    public static function only(array $keys): array
    {
        $out = [];
        foreach ($keys as $k) {
            $out[$k] = self::input($k, '');
        }
        return $out;
    }

    public static function file(string $key): ?array
    {
        return $_FILES[$key] ?? null;
    }

    public static function old(string $key, $default = '')
    {
        $data = Session::get('_old_input', []);
        return is_array($data) ? ($data[$key] ?? $default) : $default;
    }

    public static function captureInput(): void
    {
        $data = array_merge($_GET, $_POST);
        unset($data['_csrf'], $data['password'], $data['password_confirmation'], $data['captcha']);
        Session::set('_old_input', $data);
    }

    public static function clearInput(): void
    {
        Session::forget('_old_input');
    }

    public static function path(): string
    {
        $uri = (string)($_SERVER['REQUEST_URI'] ?? '/');
        $pos = strpos($uri, '?');
        if ($pos !== false) {
            $uri = substr($uri, 0, $pos);
        }
        $uri = rawurldecode($uri);
        $base = self::basePath();
        if ($base !== '' && strncmp($uri, $base, strlen($base)) === 0) {
            $uri = substr($uri, strlen($base));
        }
        $uri = '/' . ltrim($uri, '/');
        return $uri === '' ? '/' : $uri;
    }

    public static function basePath(): string
    {
        $script = (string)($_SERVER['SCRIPT_NAME'] ?? '');
        $dir = str_replace('\\', '/', dirname($script));
        $dir = rtrim($dir, '/');
        if ($dir === '' || $dir === '.') {
            return '';
        }
        return $dir;
    }

    public static function baseUrl(): string
    {
        $scheme = is_https() ? 'https' : 'http';
        $host = (string)($_SERVER['HTTP_HOST'] ?? 'localhost');
        return $scheme . '://' . $host . self::basePath();
    }

    public static function referer(string $fallback = ''): string
    {
        return (string)($_SERVER['HTTP_REFERER'] ?? $fallback);
    }
}
