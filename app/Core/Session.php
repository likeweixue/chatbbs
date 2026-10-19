<?php

declare(strict_types=1);

namespace App\Core;

/**
 * 会话封装
 */
class Session
{
    private static bool $started = false;

    public static function start(): void
    {
        if (self::$started || PHP_SAPI === 'cli') {
            return;
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;
            return;
        }
        $secure = is_https();
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'httponly' => true,
            'secure' => $secure,
            'samesite' => 'Lax',
        ]);
        session_name('chatbbs_session');
        @session_start();
        self::$started = true;

        if (empty($_SESSION['_flash_next'])) {
            $_SESSION['_flash_next'] = [];
        }
        // 老 flash 清理
        if (isset($_SESSION['_flash'])) {
            $_SESSION['_flash'] = [];
        }
    }

    public static function get(string $key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /**
     * 一次性闪存数据
     */
    public static function flash(string $key, $value = null)
    {
        if ($value !== null) {
            $_SESSION['_flash_next'][$key] = $value;
            return null;
        }
        $v = $_SESSION['_flash'][$key] ?? null;
        if ($v !== null) {
            unset($_SESSION['_flash'][$key]);
        }
        return $v;
    }

    /**
     * 请求结束时把 _flash_next 转成 _flash（由 shutdown 调用）
     */
    public static function rotateFlash(): void
    {
        if (!self::$started) {
            return;
        }
        $_SESSION['_flash'] = $_SESSION['_flash_next'] ?? [];
        $_SESSION['_flash_next'] = [];
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'] ?? '', $p['secure'] ?? false, $p['httponly'] ?? true);
        }
        @session_destroy();
    }
}
