<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Security;
use App\Core\Session;
use App\Core\Settings;
use App\Core\Validator;
use App\Core\Mailer;
use App\Models\Currency;
use App\Models\User;

class AuthController extends BaseController
{
    public function loginForm(): string
    {
        if (Auth::check()) {
            \redirect('');
        }
        return $this->render('auth/login', [
            'title' => '登录',
            'layout' => 'layouts/gateway',
            'back' => (string)Request::get('back', ''),
        ]);
    }

    public function login(): void
    {
        $account = (string)Request::post('username', '');
        $password = (string)Request::post('password', '');
        $remember = Request::bool('remember');
        $back = (string)Request::post('back', '');

        $v = Validator::make(['username' => $account, 'password' => $password], [
            'username' => 'required|min:2',
            'password' => 'required|min:6',
        ]);
        if ($v->fails()) {
            $this->backWithErrors($v->errors(), $v->firstError());
        }

        [$ok, $msg] = Auth::attempt($account, $password, $remember);
        if (!$ok) {
            $this->backWithErrors(['password' => $msg], $msg);
        }

        Session::flash('success', '欢迎回来');
        \redirect($back !== '' ? ltrim($back, '/') : '');
    }

    public function registerForm(): string
    {
        if (Auth::check()) {
            \redirect('');
        }
        if (!(int)Settings::get('open_register', 1)) {
            return $this->render('errors/notice', [
                'title' => '注册已关闭',
                'message' => '站点已关闭新用户注册，请联系管理员。',
                'layout' => 'layouts/gateway',
            ]);
        }
        return $this->render('auth/register', [
            'title' => '注册',
            'layout' => 'layouts/gateway',
            'register_agreement' => (int)Settings::get('register_agreement', 0),
            'register_agreement_title' => (string)Settings::get('register_agreement_title', '《用户协议》'),
            'register_agreement_text' => (string)Settings::get('register_agreement_text', ''),
        ]);
    }

    public function register(): void
    {
        if (!(int)Settings::get('open_register', 1)) {
            $this->back('注册已关闭');
        }
        $data = [
            'username' => (string)Request::post('username', ''),
            'nickname' => (string)Request::post('nickname', ''),
            'email' => (string)Request::post('email', ''),
            'password' => (string)Request::post('password', ''),
        ];
        // 注册表单为 Flarum 风格的用户名 / 邮箱 / 密码 / 确认密码四项；
        // 昵称留空时由下方逻辑自动使用用户名。
        $confirm = (string)Request::post('confirm_password', '');
        $v = Validator::make($data + ['confirm_password' => $confirm], [
            'username' => 'required|min:3|max:20|alpha_num',
            'email' => 'required|email',
            'password' => 'required|min:6|max:64',
            'confirm_password' => 'required|same:password',
        ]);
        if ($v->fails()) {
            $this->backWithErrors($v->errors(), $v->firstError());
        }
        if (User::findByUsername($data['username'])) {
            $this->backWithErrors(['username' => '用户名已被占用']);
        }
        if (User::findByEmail($data['email'])) {
            $this->backWithErrors(['email' => '邮箱已被注册']);
        }
        // 注册协议：开启后必须勾选同意，否则不允许注册
        if ((int)Settings::get('register_agreement', 0) && !Request::bool('agree_terms')) {
            $this->backWithErrors(['agree_terms' => '请先阅读并同意' . (string)Settings::get('register_agreement_title', '《用户协议》')]);
        }
        if ((int)Settings::get('register_captcha', 1)) {
            if (!Security::checkCaptcha((string)Request::post('captcha', ''), 'register')) {
                $this->backWithErrors(['captcha' => '验证码不正确']);
            }
        }

        $needVerify = (int)Settings::get('email_verify', 0);
        $id = User::create([
            'username' => $data['username'],
            'nickname' => $data['nickname'] !== '' ? $data['nickname'] : $data['username'],
            'email' => $data['email'],
            'password' => password_hash($data['password'], PASSWORD_DEFAULT),
            'status' => $needVerify ? 0 : 1,
            'email_verified' => $needVerify ? 0 : 1,
        ]);

        // 新用户「附近的人」可见性默认值（后台可配；字段缺失时静默忽略）
        if ((int)Settings::get('lan_discover_default', 0) === 1) {
            try {
                Database::update('users', ['lan_discover' => 1], 'id = ?', ['id' => $id]);
            } catch (\Throwable $e) {
                // 老站点还没补上字段，忽略即可
            }
        }
        Currency::reward($id, 'register');

        if ($needVerify) {
            $token = Security::randomToken(24);
            Database::update('users', ['privacy' => json_encode(['verify_token' => $token])], 'id = ?', ['id' => $id]);
            $link = url('verify?token=' . $token);
            $this->sendMail($data['email'], '请激活你的账号', '点击下面的链接激活账号：<p><a href="' . $link . '">' . $link . '</a></p>');
            Session::flash('success', '注册成功，请查收邮件激活账号');
            \redirect('login');
        }

        $user = User::find($id);
        Auth::login($user);
        Session::flash('success', '注册成功，欢迎加入！');
        \redirect('');
    }

    public function verify(): void
    {
        $token = (string)Request::get('token', '');
        $row = Database::fetch("SELECT * FROM " . Database::table('users') . " WHERE privacy LIKE ? LIMIT 1", ['%"verify_token":' . json_encode($token) . '%']);
        if (!$row) {
            // 宽松匹配
            $all = Database::fetchAll('SELECT id,privacy FROM ' . Database::table('users') . ' WHERE status = 0');
            foreach ($all as $r) {
                $p = json_decode((string)$r['privacy'], true);
                if (($p['verify_token'] ?? '') === $token) {
                    $row = $r;
                    break;
                }
            }
        }
        if (!$row) {
            Session::flash('error', '激活链接无效或已过期');
            \redirect('login');
        }
        Database::update('users', ['status' => 1, 'email_verified' => 1, 'privacy' => '{"profile":1,"thread":1,"pm":1}'], 'id = ?', ['id' => $row['id']]);
        Session::flash('success', '账号激活成功，请登录');
        \redirect('login');
    }

    public function forgotForm(): string
    {
        return $this->render('auth/forgot', ['title' => '找回密码', 'layout' => 'layouts/gateway']);
    }

    public function sendReset(): void
    {
        if (!(int)Settings::get('open_forgot', 1)) {
            $this->back('站点未开启找回密码功能');
        }
        $email = (string)Request::post('email', '');
        $user = User::findByEmail($email);
        if (!$user) {
            $this->backWithErrors(['email' => '该邮箱未注册']);
        }
        $token = Security::randomToken(24);
        Database::update('users', [
            'privacy' => json_encode(['reset_token' => $token, 'reset_at' => time()]),
        ], 'id = ?', ['id' => $user['id']]);
        $link = url('reset?token=' . $token);
        $this->sendMail($email, '重置密码', '点击下面的链接重置密码（30 分钟内有效）：<p><a href="' . $link . '">' . $link . '</a></p>');
        Session::flash('success', '重置链接已发送到邮箱');
        \redirect('login');
    }

    public function resetForm(): string
    {
        $token = (string)Request::get('token', '');
        return $this->render('auth/reset', ['title' => '重置密码', 'layout' => 'layouts/gateway', 'token' => $token]);
    }

    public function reset(): void
    {
        $token = (string)Request::post('token', '');
        $password = (string)Request::post('password', '');
        $confirm = (string)Request::post('confirm_password', '');
        if (strlen($password) < 6) {
            $this->backWithErrors(['password' => '密码至少 6 位']);
        }
        if ($password !== $confirm) {
            $this->backWithErrors(['confirm_password' => '两次输入不一致']);
        }
        $user = null;
        foreach (Database::fetchAll('SELECT id,privacy FROM ' . Database::table('users')) as $r) {
            $p = json_decode((string)$r['privacy'], true);
            if (($p['reset_token'] ?? '') === $token && (int)($p['reset_at'] ?? 0) > time() - 1800) {
                $user = $r;
                break;
            }
        }
        if (!$user) {
            $this->back('链接无效或已过期');
        }
        Database::update('users', [
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'privacy' => '{"profile":1,"thread":1,"pm":1}',
        ], 'id = ?', ['id' => $user['id']]);
        Session::flash('success', '密码重置成功，请登录');
        \redirect('login');
    }

    public function logout(): void
    {
        Auth::logout();
        Session::flash('success', '已退出登录');
        \redirect('');
    }

    public function captcha(): void
    {
        Security::captcha((string)Request::get('k', 'common'));
        exit;
    }

    private function sendMail(string $to, string $subject, string $bodyHtml): void
    {
        $mailer = new Mailer();
        $html = Mailer::template($subject, $bodyHtml);
        $mailer->send($to, $subject, $html);
    }
}
