<?php

declare(strict_types=1);

namespace App\Core;

/**
 * CSRF 防护
 */
class CSRF
{
    public static function token(): string
    {
        $t = Session::get('_csrf_token');
        if (!$t) {
            $t = bin2hex(random_bytes(32));
            Session::set('_csrf_token', $t);
        }
        return $t;
    }

    public static function check(?string $token = null): bool
    {
        $token = $token ?? (string)($_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));
        $stored = (string)Session::get('_csrf_token', '');
        if ($stored === '' || $token === '') {
            return false;
        }
        return hash_equals($stored, $token);
    }

    /**
     * 校验失败直接中断
     */
    public static function guard(): void
    {
        if (Request::isPost() && !self::check()) {
            http_response_code(419);
            if (Request::isAjax()) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'msg' => '页面已过期，请刷新后重试'], JSON_UNESCAPED_UNICODE);
            } else {
                echo '<div style="max-width:640px;margin:80px auto;font-family:sans-serif;text-align:center">'
                    . '<h1 style="font-size:20px">419 - 页面已过期</h1>'
                    . '<p style="color:#888"><a href="javascript:history.back()">返回上一页</a> 后重试</p></div>';
            }
            exit;
        }
    }
}
