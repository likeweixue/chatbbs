<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\User;

/**
 * 认证
 */
class Auth
{
    private static ?array $cached = null;

    public static function id(): ?int
    {
        $u = self::user();
        return $u ? (int)$u['id'] : null;
    }

    public static function user(): ?array
    {
        if (self::$cached !== null) {
            return self::$cached['user'] ?? null;
        }
        self::$cached = [];
        $id = (int)Session::get('uid', 0);
        if ($id <= 0) {
            // 记住我
            $token = (string)($_COOKIE['chatbbs_remember'] ?? '');
            if ($token !== '') {
                self::loginByToken($token);
            }
            return self::$cached['user'] ?? null;
        }
        $user = User::find($id);
        if (!$user || (int)$user['status'] !== 1) {
            Session::forget('uid');
            return null;
        }
        self::$cached['user'] = $user;
        return $user;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function guest(): bool
    {
        return !self::check();
    }

    public static function isAdmin(): bool
    {
        $u = self::user();
        if (!$u) {
            return false;
        }
        return in_array((int)$u['group_id'], [1, 2], true) || (int)($u['is_admin'] ?? 0) === 1;
    }

    public static function isModerator(): bool
    {
        $u = self::user();
        if (!$u) {
            return false;
        }
        return in_array((int)$u['group_id'], [1, 2, 3], true);
    }

    public static function attempt(string $account, string $password, bool $remember = false): array
    {
        $user = User::findByAccount($account);
        if (!$user) {
            return [false, '账号不存在'];
        }
        $limit = (int)Settings::get('login_fail_limit', 5);
        if ($limit > 0 && (int)$user['login_fail'] >= $limit && (time() - strtotime((string)$user['last_fail_at'])) < 900) {
            return [false, '登录失败次数过多，请 15 分钟后再试'];
        }
        if (!password_verify($password, (string)$user['password'])) {
            Database::query(
                'UPDATE ' . Database::table('users') . ' SET login_fail = login_fail + 1, last_fail_at = ? WHERE id = ?',
                [date('Y-m-d H:i:s'), $user['id']]
            );
            return [false, '账号或密码错误'];
        }
        if ((int)$user['status'] === 0) {
            return [false, '账号尚未激活，请查收激活邮件'];
        }
        if ((int)$user['status'] === 2) {
            return [false, '账号已被禁用，请联系管理员'];
        }
        self::login($user, $remember);
        return [true, '登录成功'];
    }

    public static function login(array $user, bool $remember = false): void
    {
        Session::set('uid', (int)$user['id']);
        self::$cached = ['user' => $user];
        Database::update('users', [
            'last_login_at' => date('Y-m-d H:i:s'),
            'last_login_ip' => client_ip(),
            'login_fail' => 0,
        ], 'id = ?', ['id' => $user['id']]);

        if ($remember) {
            $token = bin2hex(random_bytes(32));
            Database::insert('user_tokens', [
                'user_id' => $user['id'],
                'token' => hash('sha256', $token),
                'expires_at' => date('Y-m-d H:i:s', time() + 86400 * 30),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            setcookie('chatbbs_remember', $token, time() + 86400 * 30, '/', '', is_https(), true);
        }
    }

    private static function loginByToken(string $token): void
    {
        try {
            $row = Database::fetch(
                'SELECT * FROM ' . Database::table('user_tokens') . ' WHERE token = ? AND expires_at > NOW()',
                [hash('sha256', $token)]
            );
            if (!$row) {
                return;
            }
            $user = User::find((int)$row['user_id']);
            if ($user && (int)$user['status'] === 1) {
                Session::set('uid', (int)$user['id']);
                self::$cached = ['user' => $user];
            }
        } catch (\Throwable $e) {
            Logger::error('remember token failed: ' . $e->getMessage());
        }
    }

    public static function logout(): void
    {
        $uid = (int)Session::get('uid', 0);
        if ($uid > 0) {
            try {
                Database::delete('user_tokens', 'user_id = ?', ['uid' => $uid]);
            } catch (\Throwable $e) {
                // ignore
            }
        }
        Session::forget('uid');
        self::$cached = [];
        if (isset($_COOKIE['chatbbs_remember'])) {
            setcookie('chatbbs_remember', '', time() - 3600, '/', '', is_https(), true);
        }
    }

    /**
     * 强制登录
     */
    public static function requireLogin(?string $redirect = null): void
    {
        if (!self::check()) {
            if (Request::isAjax()) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'msg' => '请先登录', 'login' => true], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $back = $redirect ?? $_SERVER['REQUEST_URI'] ?? '';
            Session::flash('error', '请先登录后再操作');
            redirect('login?back=' . urlencode((string)$back));
        }
    }

    public static function requireAdmin(): void
    {
        if (!self::check()) {
            redirect('login?back=' . urlencode('/admin'));
        }
        if (!self::isAdmin()) {
            http_response_code(403);
            echo '<div style="max-width:640px;margin:80px auto;font-family:sans-serif;text-align:center">'
                . '<h1 style="font-size:20px">403 - 无访问权限</h1>'
                . '<p style="color:#888"><a href="' . h(url('')) . '">返回首页</a></p></div>';
            exit;
        }
    }

    /**
     * 权限判断
     */
    public static function can(string $permission): bool
    {
        $u = self::user();
        if (!$u) {
            $perms = self::groupPerms(4); // 游客组
            return in_array($permission, $perms, true);
        }
        if (self::isAdmin()) {
            return true;
        }
        return in_array($permission, self::groupPerms((int)$u['group_id']), true);
    }

    public static function groupPerms(int $groupId): array
    {
        static $cache = [];
        if (isset($cache[$groupId])) {
            return $cache[$groupId];
        }
        try {
            $row = Database::fetch('SELECT permissions FROM ' . Database::table('user_groups') . ' WHERE id = ?', [$groupId]);
        } catch (\Throwable $e) {
            $row = null;
        }
        $perms = [];
        if ($row && !empty($row['permissions'])) {
            $decoded = json_decode((string)$row['permissions'], true);
            $perms = is_array($decoded) ? $decoded : [];
        }
        $cache[$groupId] = $perms;
        return $perms;
    }
}
