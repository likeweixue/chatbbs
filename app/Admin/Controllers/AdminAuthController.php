<?php

declare(strict_types=1);

namespace App\Admin\Controllers;

use App\Admin\BaseAdmin;
use App\Core\Auth;
use App\Core\Cache;
use App\Core\Database;
use App\Core\Logger;
use App\Core\Request;
use App\Core\Security;
use App\Core\Session;
use App\Core\Settings;
use App\Core\View;

/**
 * 后台登录 / 登出 / 验证码
 */
class AdminAuthController extends BaseAdmin
{
    private function loginView(array $errors = [], string $account = ''): string
    {
        return View::renderFile(CB_ROOT . '/admin/templates/login.php', [
            'errors' => $errors,
            'account' => $account,
            'captcha' => (int)Settings::get('admin_captcha', '0') === 1,
            'back' => (string)Request::input('back', ''),
        ]);
    }

    public function form(): string
    {
        if (Auth::check() && Auth::isAdmin()) {
            header('Location: ' . admin_url());
            exit;
        }
        return $this->loginView();
    }

    public function login(): void
    {
        $account = (string)Request::post('account', '');
        $password = Request::raw('password');
        $captcha = (string)Request::post('captcha', '');
        $back = (string)Request::post('back', '');
        $ip = client_ip();
        $errors = [];

        // IP 维度失败限制
        $failKey = 'admin_fail_' . md5($ip);
        $fails = (int)Cache::get($failKey, 0);
        if ($fails >= 8) {
            $errors[] = '尝试次数过多，请 15 分钟后再试';
        }

        if (!$errors && (int)Settings::get('admin_captcha', '0') === 1) {
            if (!Security::checkCaptcha($captcha, 'admin')) {
                $errors[] = '验证码错误或已过期';
            }
        }

        if (!$errors) {
            [$ok, $msg] = Auth::attempt($account, $password, Request::bool('remember'));
            if (!$ok) {
                $errors[] = $msg;
                Cache::set($failKey, $fails + 1, 900);
            } elseif (!Auth::isAdmin()) {
                Auth::logout();
                $errors[] = '该账号没有后台管理权限';
                Cache::set($failKey, $fails + 1, 900);
            }
        }

        if ($errors) {
            echo $this->loginView($errors, $account);
            return;
        }

        Cache::forget($failKey);
        Logger::admin('后台登录', 'IP:' . $ip);
        Database::update('users', ['last_login_ip' => $ip], 'id = ?', ['id' => Auth::id()]);

        $target = $back;
        if ($target === '' || str_contains($target, 'admin/login')) {
            $target = admin_url();
        }
        if (!str_starts_with($target, 'http')) {
            $target = site_base() . '/' . ltrim($target, '/');
        }
        header('Location: ' . $target);
        exit;
    }

    public function logout(): void
    {
        Logger::admin('退出后台');
        Auth::logout();
        Session::flash('success', '已安全退出');
        header('Location: ' . admin_url('login'));
        exit;
    }

    public function captcha(): void
    {
        Security::captcha('admin');
    }

    /* ==================== 后台独立密码（二次验证） ==================== */

    /**
     * 独立密码输入页：已登录管理员但尚未通过独立密码验证时展示
     */
    public function passwordForm(): string
    {
        if (!Auth::check() || !Auth::isAdmin()) {
            header('Location: ' . admin_url('login'));
            exit;
        }
        // 未开启或还没设置独立密码时不需要验证
        $hash = (string)Settings::get('admin_pass_hash', '');
        if ((int)Settings::get('admin_pass_enable', 0) !== 1 || $hash === '' || Session::get('admin_pass_ok') === true) {
            header('Location: ' . admin_url());
            exit;
        }
        return View::renderFile(CB_ROOT . '/admin/templates/password.php', [
            'errors' => [],
            'back' => (string)Request::input('back', ''),
        ]);
    }

    public function passwordVerify(): void
    {
        if (!Auth::check() || !Auth::isAdmin()) {
            header('Location: ' . admin_url('login'));
            exit;
        }
        $hash = (string)Settings::get('admin_pass_hash', '');
        if ((int)Settings::get('admin_pass_enable', 0) !== 1 || $hash === '') {
            header('Location: ' . admin_url());
            exit;
        }

        $password = Request::raw('admin_password');
        $back = (string)Request::post('back', '');
        $ip = client_ip();
        $failKey = 'admin_pass_fail_' . md5($ip);
        $fails = (int)Cache::get($failKey, 0);
        if ($fails >= 8) {
            echo View::renderFile(CB_ROOT . '/admin/templates/password.php', [
                'errors' => ['尝试次数过多，请 15 分钟后再试'],
                'back' => $back,
            ]);
            return;
        }

        if (!is_string($password) || $password === '' || !password_verify($password, $hash)) {
            Cache::set($failKey, $fails + 1, 900);
            Logger::admin('后台独立密码验证失败', 'IP:' . $ip);
            echo View::renderFile(CB_ROOT . '/admin/templates/password.php', [
                'errors' => ['独立密码错误'],
                'back' => $back,
            ]);
            return;
        }

        Cache::forget($failKey);
        Session::set('admin_pass_ok', true);
        Logger::admin('后台独立密码验证通过', 'IP:' . $ip);

        $target = $back;
        if ($target === '' || str_contains($target, 'admin/password')) {
            $target = admin_url();
        }
        if (!str_starts_with($target, 'http')) {
            $target = site_base() . '/' . ltrim($target, '/');
        }
        header('Location: ' . $target);
        exit;
    }
}
