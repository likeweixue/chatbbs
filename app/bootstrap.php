<?php
/**
 * ChatBBS 引导文件
 * 负责：常量定义、自动加载、错误处理、配置读取、会话、数据库连接
 */

declare(strict_types=1);

define('CB_VERSION', '1.0.0-beta.20');
define('CB_ROOT', dirname(__DIR__));
define('CB_APP', CB_ROOT . '/app');
define('CB_CONFIG', CB_ROOT . '/config');
define('CB_DATA', CB_ROOT . '/data');
define('CB_UPLOAD', CB_DATA . '/uploads');
define('CB_CACHE', CB_DATA . '/cache');
define('CB_LOG', CB_DATA . '/logs');
define('CB_THEME_DIR', CB_ROOT . '/themes');
define('CB_PLUGIN_DIR', CB_ROOT . '/plugins');

/**
 * 简易 PSR-4 风格自动加载
 */
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $path = CB_APP . '/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($path)) {
        require $path;
    }
});

require CB_APP . '/Core/functions.php';

/**
 * 错误处理
 */
if (!defined('CB_DEBUG')) {
    define('CB_DEBUG', false);
}
if (CB_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
}
set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    // 致命 / 可恢复错误仍然中断执行
    $fatal = E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR | E_PARSE;
    if (($severity & $fatal) !== 0) {
        throw new ErrorException($message, 0, $severity, $file, $line);
    }
    // 严格模式（开发排错时在入口 define('CB_STRICT_ERRORS', true)）：警告也中断
    if (defined('CB_STRICT_ERRORS') && CB_STRICT_ERRORS) {
        throw new ErrorException($message, 0, $severity, $file, $line);
    }
    // 生产环境：警告 / 提醒 / 废弃提示只写日志，不影响页面
    $levelText = match (true) {
        ($severity & (E_WARNING | E_USER_WARNING)) !== 0 => 'warning',
        ($severity & (E_NOTICE | E_USER_NOTICE)) !== 0 => 'notice',
        ($severity & (E_DEPRECATED | E_USER_DEPRECATED)) !== 0 => 'deprecated',
        default => 'error',
    };
    @App\Core\Logger::log($levelText, $message, ['file' => $file, 'line' => $line]);
    return true;
});
set_exception_handler(function ($e) {
    App\Core\Logger::error('uncaught: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    if (PHP_SAPI === 'cli') {
        echo $e;
        return;
    }
    http_response_code(500);
    // 管理员（或调试模式）直接看到异常详情，方便排查 500
    $isAdmin = false;
    try {
        $isAdmin = App\Core\Auth::check() && App\Core\Auth::isAdmin();
    } catch (Throwable $ignore) {
        $isAdmin = false;
    }
    if (CB_DEBUG || $isAdmin) {
        echo '<div style="max-width:860px;margin:40px auto;padding:20px;font-family:-apple-system,sans-serif;'
            . 'background:#fff;border:1px solid #f5c6c6;border-radius:8px">'
            . '<h2 style="margin:0 0 12px;font-size:18px;color:#c0392b">500 - 服务器内部错误</h2>'
            . '<div style="font-size:14px;margin-bottom:10px"><b>' . htmlspecialchars($e->getMessage()) . '</b></div>'
            . '<div style="font-size:12px;color:#6b7280;margin-bottom:12px">'
            . htmlspecialchars($e->getFile()) . ':' . (int)$e->getLine() . '</div>'
            . '<pre style="background:#1e2229;color:#d5dae2;padding:14px;border-radius:6px;font-size:12px;'
            . 'overflow:auto;max-height:340px;white-space:pre-wrap">'
            . htmlspecialchars((string)$e->getTraceAsString()) . '</pre>'
            . '</div>';
    } else {
        echo '<div style="max-width:640px;margin:80px auto;font-family:sans-serif;text-align:center">'
            . '<h1 style="font-size:20px">500 - 服务器内部错误</h1>'
            . '<p style="color:#888">发生了一些问题，请稍后再试。</p></div>';
    }
    exit;
});
/**
 * 致命错误兜底：内存耗尽 / 超时等真正的 fatal 错误不会被上面的处理器捕获，
 * 之前会输出空 body → 浏览器只显示「HTTP ERROR 500」且日志无痕迹。
 * 这里统一记录到日志并输出可读的 500 页面，方便定位问题。
 */
register_shutdown_function(function () {
    $e = error_get_last();
    if (!$e || !($e['type'] & (E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR | E_PARSE))) {
        return;
    }
    @App\Core\Logger::error('fatal: ' . $e['message'] . ' @ ' . $e['file'] . ':' . $e['line']);
    if (PHP_SAPI === 'cli') {
        return;
    }
    // 先丢弃缓冲里的半截页面（开启 gzip 缓冲后这里可能是渲染了一半的 HTML），
    // 否则用户看到的会是「页面断在半中间」而不是错误提示
    while (ob_get_level() > 0) {
        @ob_end_clean();
    }
    if (headers_sent()) {
        return;
    }
    http_response_code(500);
    echo '<div style="max-width:640px;margin:80px auto;font-family:sans-serif;text-align:center">'
        . '<h1 style="font-size:20px">500 - 服务器内部错误</h1>'
        . '<p style="color:#888">发生了一些问题，请稍后再试（详细原因已写入 data/logs 日志）。</p></div>';
});

/**
 * 时区
 */
date_default_timezone_set('Asia/Shanghai');

/**
 * 站点配置（安装后由 config/site.php 覆盖）
 */
$GLOBALS['cb_config'] = [];
if (is_file(CB_CONFIG . '/database.php')) {
    $db = require CB_CONFIG . '/database.php';
    $GLOBALS['cb_db_config'] = is_array($db) ? $db : null;
}
if (is_file(CB_CONFIG . '/site.php')) {
    $site = require CB_CONFIG . '/site.php';
    if (is_array($site)) {
        $GLOBALS['cb_config'] = $site;
    }
}

/**
 * 会话
 */
App\Core\Session::start();

/**
 * 数据库连接（安装前跳过）
 */
if (!empty($GLOBALS['cb_db_config']) && is_file(CB_DATA . '/install.lock')) {
    try {
        App\Core\Database::init($GLOBALS['cb_db_config']);
        App\Core\Settings::load();
        // 版本升级后自动补齐新增表 / 字段（幂等，失败只记日志不影响访问）
        App\Core\Schema::maybeSync();
    } catch (Throwable $e) {
        App\Core\Logger::error('db connect failed: ' . $e->getMessage());
        if (!defined('CB_INSTALLING')) {
            http_response_code(500);
            echo '<div style="max-width:640px;margin:80px auto;font-family:sans-serif;text-align:center">'
                . '<h1 style="font-size:20px">数据库连接失败</h1>'
                . '<p style="color:#888">请检查 config/database.php 配置。</p></div>';
            exit;
        }
    }
}
