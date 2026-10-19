<?php
/**
 * ChatBBS 前台入口
 */

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

use App\Core\Request;
use App\Core\Router;
use App\Core\Settings;
use App\Controllers\BaseController;

// 输出压缩兜底：如果 Web 服务器没有开 gzip / br，这里用 PHP 压缩一次，
// 页面体积通常能降到原来的 1/4，弱网下体感差别很大（已压缩时自动跳过）
// 附件下载 / 验证码等二进制输出不进压缩缓冲，否则整份文件会被攒在内存里
$cbUri = (string)($_SERVER['REQUEST_URI'] ?? '');
if (!headers_sent()
    && extension_loaded('zlib')
    && !ini_get('zlib.output_compression')
    && stripos((string)($_SERVER['HTTP_ACCEPT_ENCODING'] ?? ''), 'gzip') !== false
    && !preg_match('#/(download|attachment/|captcha|export|backup)#i', $cbUri)
) {
    @ob_start('ob_gzhandler');
}

// 未安装则跳转安装引导
if (!is_file(CB_DATA . '/install.lock')) {
    $scheme = is_https() ? 'https' : 'http';
    $host = (string)($_SERVER['HTTP_HOST'] ?? '');
    $base = rtrim(str_replace('/index.php', '', (string)($_SERVER['SCRIPT_NAME'] ?? '')), '/');
    header('Location: ' . $scheme . '://' . $host . $base . '/install/index.php');
    exit;
}

// 站点关闭 / 访问限制
if (!BaseController::siteGuard()) {
    exit;
}

require __DIR__ . '/app/routes.php';

// 无伪静态时直接访问 /index.php 也能进入首页
$path = Request::path();
if ($path === '' || $path === '/index.php') {
    $path = '/';
}
Router::dispatch($path);
