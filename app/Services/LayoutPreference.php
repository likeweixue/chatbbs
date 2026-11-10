<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Settings;

/**
 * 前台布局偏好：后台默认布局 + 用户自行切换（可关闭）
 */
class LayoutPreference
{
    /** 可选布局：classic 双栏 / single 单栏 / island 工作台三栏 */
    public static function options(): array
    {
        return [
            'classic' => '双栏',
            'single' => '单栏',
            'island' => '三栏（工作台）',
        ];
    }

    /** 是否允许访客自行切换 */
    public static function userSwitchable(): bool
    {
        return (int)Settings::get('layout_user_switch', 1) === 1;
    }

    /** 当前生效布局；用户手动选择优先（需开启权限） */
    public static function current(): string
    {
        $default = (string)Settings::get('site_layout', 'classic');
        if (!isset(self::options()[$default])) {
            $default = 'classic';
        }
        if (!self::userSwitchable()) {
            return $default;
        }
        $cookie = (string)($_COOKIE['cb_layout'] ?? '');
        if (isset(self::options()[$cookie])) {
            return $cookie;
        }
        return $default;
    }

    /** 写入用户选择的布局（cookie 保留 30 天） */
    public static function set(string $layout): bool
    {
        if (!isset(self::options()[$layout]) || !self::userSwitchable()) {
            return false;
        }
        setcookie('cb_layout', $layout, [
            'expires' => time() + 86400 * 30,
            'path' => '/',
            'samesite' => 'Lax',
            'secure' => is_https(),
        ]);
        $_COOKIE['cb_layout'] = $layout;
        return true;
    }
}
