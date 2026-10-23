<?php
/**
 * 全局助手函数
 */

declare(strict_types=1);

use App\Core\Auth;
use App\Core\Settings;
use App\Core\Request;

if (!function_exists('h')) {
    /**
     * HTML 转义
     */
    function h(?string $str): string
    {
        return htmlspecialchars($str ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('e')) {
    function e(?string $str): string
    {
        return h($str);
    }
}

if (!function_exists('config')) {
    /**
     * 读取站点配置
     */
    function config(string $key = null, $default = null)
    {
        return Settings::get($key, $default);
    }
}

if (!function_exists('cb_url_mode')) {
    /**
     * 当前应使用的 URL 模式
     * auto：探测本次请求是否经由伪静态进入，是则用 rewrite，否则用 query（保证永远不 404）
     */
    function cb_url_mode(): string
    {
        $mode = (string)config('url_mode', 'auto');
        if ($mode === 'rewrite' || $mode === 'query') {
            return $mode;
        }
        $uri = (string)parse_url((string)($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
        $script = (string)($_SERVER['SCRIPT_NAME'] ?? '');
        if ($uri === '' || $uri === '/' || str_contains($uri, '.php')) {
            return 'query';
        }
        if ($script !== '' && rtrim($uri, '/') === rtrim($script, '/')) {
            return 'query';
        }
        if (is_file(CB_ROOT . $uri) || is_dir(CB_ROOT . $uri)) {
            return 'query';
        }
        // 非真实文件路径且不是 php 文件 → 说明请求交给了 index.php，伪静态生效
        return 'rewrite';
    }
}

if (!function_exists('url')) {
    /**
     * 生成站内 URL
     * url_mode = query 时输出 ?r=xxx（服务器未配置伪静态时的兼容模式）
     */
    function url(string $path = ''): string
    {
        $path = ltrim($path, '/');
        // 后台地址交给 admin_url 处理
        if ($path === 'admin' || str_starts_with($path, 'admin/')) {
            return admin_url(substr($path, 5));
        }
        $base = site_base();
        if ($path === '') {
            return $base . '/';
        }
        if (cb_url_mode() === 'query') {
            $query = '';
            $pos = strpos($path, '?');
            if ($pos !== false) {
                $query = substr($path, $pos + 1);
                $path = substr($path, 0, $pos);
            }
            if ($path === '') {
                return $base . '/' . ($query !== '' ? '?' . $query : '');
            }
            $u = $base . '/?r=' . $path;
            return $query !== '' ? $u . '&' . $query : $u;
        }
        return $base . '/' . $path;
    }
}

if (!function_exists('asset')) {
    /**
     * 静态资源地址（真实文件路径，不受 URL 模式影响）
     */
    function asset(string $path): string
    {
        return site_base() . '/static/' . ltrim($path, '/');
    }
}

if (!function_exists('site_base')) {
    /**
     * 站点根地址（在后台目录下也能正确推导）
     */
    function site_base(): string
    {
        $base = rtrim((string)config('site_url', ''), '/');
        if ($base !== '') {
            return $base;
        }
        $bp = App\Core\Request::basePath();
        $bp = preg_replace('#/admin$#', '', $bp);
        $scheme = is_https() ? 'https' : 'http';
        return $scheme . '://' . ((string)($_SERVER['HTTP_HOST'] ?? 'localhost')) . rtrim((string)$bp, '/');
    }
}

if (!function_exists('admin_url')) {
    /**
     * 后台地址（query 模式下输出 /admin/index.php?r=xxx 以兼容未配置伪静态的服务器）
     */
    function admin_url(string $path = ''): string
    {
        $path = ltrim($path, '/');
        if (cb_url_mode() === 'query') {
            $query = '';
            $pos = strpos($path, '?');
            if ($pos !== false) {
                $query = substr($path, $pos + 1);
                $path = substr($path, 0, $pos);
            }
            $u = site_base() . '/admin/index.php' . ($path === '' ? '' : '?r=' . $path);
            return $query !== '' ? $u . ($path === '' ? '?' : '&') . $query : $u;
        }
        return site_base() . '/admin' . ($path === '' ? '' : '/' . $path);
    }
}

if (!function_exists('admin_asset')) {
    /**
     * 后台静态资源（真实文件路径，不走 ?r= 兼容模式，否则 CSS/JS 会被当成页面返回）
     */
    function admin_asset(string $path): string
    {
        return site_base() . '/admin/assets/' . ltrim($path, '/');
    }
}

if (!function_exists('theme_asset')) {
    function theme_asset(string $path): string
    {
        $theme = (string)config('theme', 'default');
        return site_base() . '/themes/' . $theme . '/assets/' . ltrim($path, '/');
    }
}

if (!function_exists('redirect')) {
    function redirect(string $to): void
    {
        if (strpos($to, 'http') !== 0) {
            $to = url($to);
        }
        header('Location: ' . $to);
        exit;
    }
}

if (!function_exists('user')) {
    function user(): ?array
    {
        return Auth::user();
    }
}

if (!function_exists('uid')) {
    function uid(): int
    {
        return (int)(Auth::id() ?? 0);
    }
}

if (!function_exists('is_admin')) {
    function is_admin(): bool
    {
        return Auth::isAdmin();
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . h(App\Core\CSRF::token()) . '">';
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return App\Core\CSRF::token();
    }
}

if (!function_exists('old')) {
    function old(string $key, $default = '')
    {
        return Request::old($key, $default);
    }
}

if (!function_exists('time_ago')) {
    /**
     * 人类可读时间
     */
    function time_ago($time): string
    {
        if (is_string($time)) {
            $time = strtotime($time);
        }
        $diff = time() - (int)$time;
        if ($diff < 60) {
            return '刚刚';
        }
        if ($diff < 3600) {
            return floor($diff / 60) . ' 分钟前';
        }
        if ($diff < 86400) {
            return floor($diff / 3600) . ' 小时前';
        }
        if ($diff < 2592000) {
            return floor($diff / 86400) . ' 天前';
        }
        if ($diff < 31536000) {
            return floor($diff / 2592000) . ' 个月前';
        }
        return floor($diff / 31536000) . ' 年前';
    }
}

if (!function_exists('format_date')) {
    function format_date($time, string $format = 'Y-m-d H:i'): string
    {
        if (is_string($time)) {
            $time = strtotime($time);
        }
        return date($format, (int)$time);
    }
}

if (!function_exists('format_size')) {
    function format_size($bytes): string
    {
        $bytes = (float)$bytes;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}

if (!function_exists('str_cut')) {
    function str_cut(?string $str, int $len = 100, string $suffix = '...'): string
    {
        $str = trim(strip_tags($str ?? ''));
        if (mb_strlen($str) <= $len) {
            return $str;
        }
        return mb_substr($str, 0, $len) . $suffix;
    }
}

if (!function_exists('avatar_url')) {
    /**
     * 用户头像地址
     */
    function avatar_url($user): string
    {
        $avatar = '';
        if (is_array($user)) {
            $avatar = (string)($user['avatar'] ?? '');
        } else {
            $avatar = (string)$user;
        }
        if ($avatar !== '') {
            if (preg_match('#^https?://#i', $avatar)) {
                return $avatar;
            }
            return site_base() . '/data/uploads/avatars/' . $avatar;
        }
        return site_base() . '/static/images/default-avatar.svg';
    }
}

if (!function_exists('money')) {
    function money($amount): string
    {
        $num = number_format((float)$amount, 2);
        $symbol = trim((string)config('currency_symbol', ''));
        return $symbol !== '' ? $symbol . $num : $num . ' ' . currency_name();
    }
}

if (!function_exists('cb_icon')) {
    /**
     * 输出一个复古图标 <svg><use href="#i-xxx"/></svg>
     *
     * 同时把图标名登记进本次请求的使用清单；cb_sprite() 据此只输出真正
     * 用到的 symbol。42 个图标全量内联会让每个页面平白多出约 14KB。
     */
    function cb_icon(string $name, string $class = ''): string
    {
        $name = (string)preg_replace('/[^a-z0-9\-]/i', '', $name);
        if ($name !== '') {
            if (!isset($GLOBALS['_cb_icons']) || !is_array($GLOBALS['_cb_icons'])) {
                $GLOBALS['_cb_icons'] = [];
            }
            $GLOBALS['_cb_icons']['i-' . $name] = true;
        }
        $cls = trim('cb-ico ' . $class);
        $use = '#i-' . $name;
        // 图标集为 24x24 网格（viewBox 与 sprite 保持一致，否则图形会被裁切）
        return '<svg class="' . h($cls) . '" viewBox="0 0 24 24" aria-hidden="true" focusable="false">'
            . '<use href="' . h($use) . '" xlink:href="' . h($use) . '"></use></svg>';
    }
}

if (!function_exists('cb_icon_preload')) {
    /**
     * 预登记一批图标：供 JS 运行时动态生成的图标使用（如编辑器工具栏）
     */
    function cb_icon_preload(array $names): void
    {
        if (!isset($GLOBALS['_cb_icons']) || !is_array($GLOBALS['_cb_icons'])) {
            $GLOBALS['_cb_icons'] = [];
        }
        foreach ($names as $n) {
            $n = (string)preg_replace('/[^a-z0-9\-]/i', '', (string)$n);
            if ($n !== '') {
                $GLOBALS['_cb_icons']['i-' . $n] = true;
            }
        }
    }
}

if (!function_exists('cb_sprite')) {
    /**
     * 输出 <symbol> sprite —— 只含本页实际用到的图标
     *
     * 必须在页面末尾调用（此时所有 cb_icon() 都已完成登记）。
     * 现代浏览器中 <use> 引用文档后方才定义的 <symbol> 会正常渲染。
     */
    function cb_sprite(): string
    {
        static $symbols = null;
        if ($symbols === null) {
            $symbols = [];
            $file = CB_ROOT . '/static/images/retro/icons.svg';
            if (is_file($file)) {
                // 解析结果按文件内容指纹缓存：避免每个请求都读一遍 18KB 的 sprite 再跑正则
                $sig = md5($file . '|' . (string)@filemtime($file) . '|' . (string)@filesize($file));
                $parsed = \App\Core\Cache::remember('cb_icons_' . $sig, 86400, static function () use ($file): array {
                    $out = [];
                    $src = (string)@file_get_contents($file);
                    if (preg_match_all('/<symbol\b[^>]*id="([^"]+)"[^>]*>.*?<\/symbol>/s', $src, $m, PREG_SET_ORDER)) {
                        foreach ($m as $g) {
                            $out[$g[1]] = $g[0];
                        }
                    }
                    return $out;
                });
                $symbols = is_array($parsed) ? $parsed : [];
            }
        }
        $used = $GLOBALS['_cb_icons'] ?? [];
        if (!is_array($used) || !$used || !$symbols) {
            return '';
        }
        $out = '';
        foreach ($symbols as $id => $markup) {
            if (isset($used[$id])) {
                $out .= $markup;
            }
        }
        return $out === ''
            ? ''
            : '<svg xmlns="http://www.w3.org/2000/svg" style="display:none" aria-hidden="true">' . $out . '</svg>';
    }
}

if (!function_exists('cb_editor_icons')) {
    /**
     * 编辑器 JS 运行时可能用到的图标清单（加载 editor.js 时预登记）
     */
    function cb_editor_icons(): array
    {
        return [
            'h', 'bold', 'italic', 'underline', 'strike', 'ul', 'ol', 'quote', 'hr',
            'code', 'fold', 'table', 'link', 'image', 'attach', 'emoji', 'undo', 'redo',
            'clear', 'md', 'preview', 'bgimage',
            'align-left', 'align-center', 'align-right', 'float-left', 'float-none',
            'attachment-file', 'close', 'panel-right',
        ];
    }
}

if (!function_exists('currency_name')) {
    /**
     * 站点自定义货币名称（后台「货币规则」可改，如：软妹币、美刀、金币）
     */
    function currency_name(): string
    {
        $name = trim((string)config('currency_name', '金币'));
        return $name !== '' ? $name : '金币';
    }
}

if (!function_exists('license_options')) {
    /**
     * 版权许可证预设：key => [名称, 说明]
     * 自定义版权（license=custom）的内容存 threads.license_custom
     */
    function license_options(): array
    {
        return [
            '' => ['默认（未声明）', '转载前请先联系作者'],
            'cc0' => ['CC0 1.0 公共领域', '可自由转载、修改与商用，无需署名'],
            'cc-by' => ['CC BY 4.0 署名', '可转载、修改与商用，需注明作者与出处'],
            'cc-by-sa' => ['CC BY-SA 4.0 署名-相同方式共享', '可转载与修改，需署名并以相同协议共享'],
            'cc-by-nd' => ['CC BY-ND 4.0 署名-禁止演绎', '可转载，需署名，但不得修改原文'],
            'cc-by-nc' => ['CC BY-NC 4.0 署名-非商业', '可转载与修改，需署名，不得用于商业用途'],
            'cc-by-nc-sa' => ['CC BY-NC-SA 4.0 署名-非商业-相同方式共享', '可转载与修改，需署名，非商业且相同协议共享'],
            'all' => ['禁止转载', '版权归作者所有，未经许可不得转载'],
            'custom' => ['自定义', ''],
        ];
    }
}

if (!function_exists('license_text')) {
    /**
     * 帖子的版权说明文案（选了预设返回「名称：说明」，自定义返回其内容）
     */
    function license_text(array $thread): string
    {
        $key = (string)($thread['license'] ?? '');
        if ($key === '') {
            return '';
        }
        $opts = license_options();
        if ($key === 'custom') {
            return trim((string)($thread['license_custom'] ?? ''));
        }
        return ($opts[$key][0] ?? '') . '：' . ($opts[$key][1] ?? '');
    }
}

if (!function_exists('is_https')) {
    function is_https(): bool
    {
        if (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') {
            return true;
        }
        if ((($_SERVER['SERVER_PORT'] ?? '') == 443)) {
            return true;
        }
        if (strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https') {
            return true;
        }
        return false;
    }
}

if (!function_exists('client_ip')) {
    function client_ip(): string
    {
        $keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        foreach ($keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = trim(explode(',', (string)$_SERVER[$key])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        return '0.0.0.0';
    }
}

if (!function_exists('lang')) {
    /**
     * 界面文案翻译（键为中文原文）
     *
     * 中文环境直接返回原文（零字典开销）；其它语言查语言包，
     * 未收录的词条优雅降级为中文原文，界面永不出现空白。
     * 注意：只翻译界面文案，切勿用于用户产生的内容。
     */
    function lang(string $key, array $replace = []): string
    {
        return \App\Core\Lang::t($key, $replace);
    }
}

if (!function_exists('L')) {
    /** lang() 的简短别名，便于模板中书写 */
    function L(string $key, array $replace = []): string
    {
        return \App\Core\Lang::t($key, $replace);
    }
}

if (!function_exists('flash')) {
    function flash(string $key, $value = null)
    {
        return App\Core\Session::flash($key, $value);
    }
}

if (!function_exists('is_mobile')) {
    function is_mobile(): bool
    {
        $ua = strtolower((string)($_SERVER['HTTP_USER_AGENT'] ?? ''));
        return (bool)preg_match('/(android|iphone|ipad|ipod|mobile|windows phone)/i', $ua);
    }
}
