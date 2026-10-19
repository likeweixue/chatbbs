<?php
/**
 * ChatBBS 管理后台入口
 */

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

use App\Core\Request;
use App\Core\Router;
use App\Core\Session;

// 未安装则跳转安装向导
if (!is_file(CB_DATA . '/install.lock')) {
    header('Location: ' . site_base() . '/install/index.php');
    exit;
}

// 安全响应头
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');

try {
    require CB_APP . '/admin_routes.php';

    // 无伪静态时直接访问 /admin/index.php 也能进入仪表盘
    $path = Request::path();
    if ($path === '' || $path === '/index.php') {
        $path = '/';
    }
    Router::dispatch($path);
} catch (\Throwable $e) {
    \App\Core\Logger::error('后台异常：' . $e->getMessage(), [
        'file' => $e->getFile(), 'line' => $e->getLine(),
    ]);
    if ((CB_DEBUG ?? false) === true) {
        throw $e;
    }
    http_response_code(500);
    echo '<div style="max-width:720px;margin:60px auto;font-family:-apple-system,sans-serif">'
        . '<div class="adm-alert error" style="padding:14px;border:1px solid #f5c6c6;background:#fdecec;color:#c0392b;border-radius:6px">'
        . '<b>后台运行出错</b><br>' . h($e->getMessage())
        . '<br><small>' . h($e->getFile()) . ':' . (int)$e->getLine() . '</small>'
        . '</div></div>';
}
Session::rotateFlash();
