<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Core\Settings;

abstract class BaseController
{
    /**
     * 渲染前台模板
     */
    protected function render(string $view, array $data = [], ?string $layout = 'layouts/main'): string
    {
        $data = array_merge($this->globalData(), $data);
        // 兼容控制器在 $data['layout'] 中指定布局的写法（登录 / 注册页使用独立的认证布局）
        if (array_key_exists('layout', $data)) {
            $layout = $data['layout'];
        }
        if ($layout === null) {
            return View::make($view, $data);
        }
        return View::render($view, array_merge($data, ['layout' => $layout]));
    }

    protected function globalData(): array
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }
        $user = Auth::user();
        if ($user) {
            $user = \App\Models\User::withMeta($user);
        }
        $cached = [
            'site_name' => Settings::get('site_name', 'ChatBBS'),
            'current_user' => $user,
            'unread_notifications' => $user ? \App\Models\Notification::unreadCount((int)$user['id']) : 0,
            // 顶栏「聊天」角标 = 单聊未读 + 群聊未读
            'unread_messages' => $user ? (\App\Models\Message::unreadTotal((int)$user['id']) + self::chatUnread((int)$user['id'])) : 0,
            'menus' => self::menus(),
            'sidebar_enable' => (int)Settings::get('sidebar_enable', 1),
            'sidebar_side' => Settings::get('sidebar_side', 'right'),
        ];
        return $cached;
    }

    /**
     * 群聊未读：聊天表缺失时降级为 0，避免整站 500
     */
    protected static function chatUnread(int $uid): int
    {
        if (!\App\Models\ChatGroup::installed()) {
            return 0;
        }
        try {
            return \App\Models\ChatGroup::unreadTotal($uid);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * 列表筛选栏：读取后台「列表筛选」配置
     *
     * @return array [ ['key','title','url'] ]  url 为空表示"当前页的筛选参数"
     */
    protected static function listTabs(string $basePath = ''): array
    {
        $raw = (string)Settings::get('thread_tabs', '');
        $items = json_decode($raw, true);
        if (!is_array($items)) {
            $items = [
                ['t' => 'new', 'title' => '最新发布', 'on' => 1],
                ['t' => 'default', 'title' => '最新回复', 'on' => 1],
                ['t' => 'hot', 'title' => '热门', 'on' => 1],
                ['t' => 'essence', 'title' => '精华', 'on' => 1],
                ['t' => 'recommend', 'title' => '推荐', 'on' => 1],
                ['t' => 'avoid', 'title' => '避雷', 'on' => 1],
            ];
        }
        $out = [];
        foreach ($items as $it) {
            if ((int)($it['on'] ?? 1) !== 1) {
                continue;
            }
            $t = (string)($it['t'] ?? '');
            $title = (string)($it['title'] ?? '');
            if ($title === '') {
                continue;
            }
            if ($t === 'link') {
                $url = (string)($it['url'] ?? '');
                if (str_starts_with($url, '/')) {
                    $url = site_base() . $url;
                }
                $out[] = ['key' => 'link:' . $url, 'title' => $title, 'url' => $url, 'link' => true];
            } else {
                $out[] = ['key' => $t, 'title' => $title, 'url' => '', 'link' => false];
            }
        }
        return $out;
    }

    /**
     * 解析当前筛选参数，返回 Thread::list 所需的查询选项
     *
     * @return array ['tab' => string, 'order' => string, 'essence' => int, 'mark_id' => int, 'digest' => int, 'recommend_kind' => int]
     */
    protected function resolveTab(): array
    {
        $tabs = self::listTabs();
        $tab = (string)Request::get('tab', '');
        $legacy = (string)Request::get('order', '');

        $res = ['tab' => $tab, 'order' => 'default', 'essence' => 0, 'mark_id' => 0, 'digest' => 0, 'recommend_kind' => 0];

        // 兼容旧的 ?order=xxx
        if ($tab === '' && in_array($legacy, ['default', 'new', 'hot', 'replies', 'essence', 'recommend', 'avoid'], true)) {
            $tab = $legacy;
        }
        if ($tab === '' && $tabs) {
            $tab = (string)$tabs[0]['key'];
        }

        if (str_starts_with($tab, 'mark:')) {
            $res['mark_id'] = (int)substr($tab, 5);
        } elseif ($tab === 'essence') {
            $res['essence'] = 1;
            $res['order'] = 'default';
        } elseif ($tab === 'digest') {
            $res['digest'] = 1;
            $res['order'] = 'default';
        } elseif ($tab === 'recommend') {
            $res['recommend_kind'] = 1;
            $res['order'] = 'default';
        } elseif ($tab === 'avoid') {
            $res['recommend_kind'] = -1;
            $res['order'] = 'default';
        } elseif (in_array($tab, ['new', 'hot', 'replies', 'default'], true)) {
            $res['order'] = $tab;
        }
        $res['tab'] = $tab;
        return $res;
    }

    protected static function menus(): array
    {
        static $menus = null;
        if ($menus !== null) {
            return $menus;
        }
        try {
            $menus = \App\Core\Database::fetchAll(
                'SELECT * FROM ' . \App\Core\Database::table('menus') . ' WHERE visible = 1 AND parent_id = 0 ORDER BY sort_order, id'
            );
        } catch (\Throwable $e) {
            $menus = [];
        }
        // 「推荐」已合并为首页筛选标签，隐藏菜单表里遗留的旧推荐页入口
        $menus = array_values(array_filter($menus, static function ($m): bool {
            $u = (string)($m['url'] ?? '');
            return !(preg_match('#(^|/)recommend($|[/?])#i', $u) || str_contains($u, 'r=recommend'));
        }));
        return $menus;
    }

    protected function json(array $data): void
    {
        Response::json($data);
    }

    /**
     * 成功响应：
     * - Ajax 请求返回 JSON；
     * - 普通表单提交（如通知页的"全部已读"、帖子页的"删除"）改为提示后跳回，
     *   避免浏览器直接把 JSON 打印到页面上。
     */
    protected function success($data = null, string $msg = '操作成功'): void
    {
        if (!Request::isAjax()) {
            Session::flash('success', $msg);
            Response::back();
        }
        Response::success($data, $msg);
    }

    protected function fail(string $msg = '操作失败', $data = null): void
    {
        if (!Request::isAjax()) {
            Session::flash('error', $msg);
            Response::back();
        }
        Response::fail($msg, $data);
    }


    protected function back(string $msg = '', string $type = 'error'): void
    {
        Response::back($msg, $type);
    }

    protected function requireLogin(): void
    {
        Auth::requireLogin();
    }

    protected function user(): ?array
    {
        return Auth::user();
    }

    protected function userId(): int
    {
        return (int)(Auth::id() ?? 0);
    }

    /**
     * 校验失败：保留输入并返回
     */
    protected function backWithErrors(array $errors, string $msg = ''): void
    {
        Request::captureInput();
        Session::flash('errors', $errors);
        if ($msg !== '') {
            Session::flash('error', $msg);
        }
        $this->back();
    }

    /**
     * 站点关闭 / 访问限制检查
     */
    public static function siteGuard(): bool
    {
        $closed = (int)Settings::get('site_closed', 0);
        if ($closed && !Auth::isAdmin()) {
            echo View::render('errors/closed', ['title' => '站点维护中']);
            return false;
        }
        // 访问模式：all_rw 开放浏览与发帖 / all_r_login_w 开放浏览登录可发帖
        //          login_rw 仅登录用户可访问 / admin_only 仅管理员可访问
        $mode = (string)Settings::get('site_access', 'all_rw');
        if (in_array($mode, ['login_rw', 'admin_only'], true)) {
            $uri = (string)($_SERVER['REQUEST_URI'] ?? '/');
            $r = (string)($_GET['r'] ?? '');
            // 登录 / 注册等认证页永远放行，否则会出现重定向死循环
            $path = rtrim((string)(parse_url($uri, PHP_URL_PATH) ?: '/'), '/');
            $probe = strtolower($path . ($r !== '' ? '/' . strtolower($r) : ''));
            $isAuthPage = false;
            foreach (['login', 'register', 'forgot', 'reset', 'captcha', 'verify', 'logout'] as $k) {
                if (str_contains($probe, $k)) {
                    $isAuthPage = true;
                    break;
                }
            }
            if (!$isAuthPage) {
                if (!Auth::check()) {
                    header('Location: ' . url('login?back=' . urlencode($uri)));
                    exit;
                }
                if ($mode === 'admin_only' && !Auth::isAdmin()) {
                    http_response_code(403);
                    echo View::render('errors/notice', ['title' => '无权访问', 'message' => '本站当前仅管理员可访问。']);
                    return false;
                }
            }
        }
        return true;
    }
}
