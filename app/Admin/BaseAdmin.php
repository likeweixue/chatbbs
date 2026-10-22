<?php

declare(strict_types=1);

namespace App\Admin;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Settings;
use App\Core\View;

abstract class BaseAdmin
{
    /**
     * 后台菜单（url 为后台相对路径，渲染时用 admin_url() 补全）
     */
    /**
     * 分组图标（static/images/admin/ 下的像素风格图标）
     */
    public static function groupIcon(string $key): string
    {
        return site_base() . '/static/images/admin/' . $key . '.svg';
    }

    /**
     * 分组图标（内联 SVG，现代线性风格）
     *
     * 用内联 <svg> 代替 <img>：颜色随侧栏文字 / 暗色模式自动变化，
     * 同时省掉每个分组一张图标的请求。
     */
    public static function groupIconSvg(string $key): string
    {
        static $paths = [
            'dashboard' => '<path d="M3 3v18h18"/><path d="M7 15l4-5 3 3 5-7"/>',
            'content' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 13h8M8 17h5"/>',
            'user' => '<circle cx="9" cy="8" r="3.5"/><path d="M2 21a7 7 0 0 1 14 0"/><path d="M16 3.5a3.5 3.5 0 0 1 0 7"/><path d="M18 21a7 7 0 0 0-3-5.7"/>',
            'setting' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.6h.09A1.65 1.65 0 0 0 10.6 3.09V3a2 2 0 1 1 4 0v.09A1.65 1.65 0 0 0 16.11 4.6a1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 20.43 9v.09a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>',
            'ext' => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/>',
            'system' => '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>',
        ];
        $body = $paths[$key] ?? '<circle cx="12" cy="12" r="9"/>';
        return '<svg class="adm-nav-icon adm-nav-icon--svg" viewBox="0 0 24 24" fill="none"'
            . ' stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"'
            . ' aria-hidden="true" focusable="false">' . $body . '</svg>';
    }

    public static array $menu = [
        'dashboard' => [
            'label' => '仪表盘', 'icon' => '📊', 'icon_key' => 'dashboard', 'url' => '',
            'items' => [
                ['label' => '全局概览', 'url' => '', 'key' => 'dashboard.index'],
            ],
        ],
        'content' => [
            'label' => '内容管理', 'icon' => '📝', 'icon_key' => 'content',
            'items' => [
                ['label' => '板块管理', 'url' => 'forums', 'key' => 'forums'],
                ['label' => '主题管理', 'url' => 'threads', 'key' => 'threads'],
                ['label' => '回复管理', 'url' => 'posts', 'key' => 'posts'],
                ['label' => '标签管理', 'url' => 'tags', 'key' => 'tags'],
                ['label' => '帖子标记', 'url' => 'marks', 'key' => 'marks'],
                ['label' => '说说管理', 'url' => 'says', 'key' => 'says'],
                ['label' => '推荐管理', 'url' => 'recommends', 'key' => 'recommends'],
                ['label' => '附件库', 'url' => 'attachments', 'key' => 'attachments'],
                ['label' => '举报审核', 'url' => 'reports', 'key' => 'reports'],
                ['label' => '违禁词库', 'url' => 'forbidden-words', 'key' => 'forbidden_words'],
                ['label' => '群聊管理', 'url' => 'chat-groups', 'key' => 'chat_groups'],
            ],
        ],
        'user' => [
            'label' => '用户管理', 'icon' => '👥', 'icon_key' => 'user',
            'items' => [
                ['label' => '用户列表', 'url' => 'users', 'key' => 'users'],
                ['label' => '用户组', 'url' => 'groups', 'key' => 'groups'],
                ['label' => '认证管理', 'url' => 'certs', 'key' => 'certs'],
                ['label' => '等级管理', 'url' => 'levels', 'key' => 'levels'],
                ['label' => '签到记录', 'url' => 'checkins', 'key' => 'checkins'],
                ['label' => '货币管理', 'url' => 'currency', 'key' => 'currency'],
                ['label' => '聊天管理', 'url' => 'messages', 'key' => 'messages'],
            ],
        ],
        'setting' => [
            'label' => '论坛设置', 'icon' => '⚙️', 'icon_key' => 'setting',
            'items' => [
                ['label' => '站点设置', 'url' => 'settings', 'key' => 'settings'],
                ['label' => '功能开关', 'url' => 'settings/features', 'key' => 'settings.features'],
                ['label' => '货币规则', 'url' => 'settings/currency', 'key' => 'settings.currency'],
                ['label' => '权限设置', 'url' => 'settings/permissions', 'key' => 'settings.permissions'],
                ['label' => '安全设置', 'url' => 'settings/security', 'key' => 'settings.security'],
                ['label' => '菜单管理', 'url' => 'menus', 'key' => 'menus'],
                ['label' => '侧边栏管理', 'url' => 'sidebar', 'key' => 'sidebar'],
                ['label' => '列表筛选', 'url' => 'list-tabs', 'key' => 'list_tabs'],
                ['label' => '邮箱配置', 'url' => 'mail', 'key' => 'mail'],
                ['label' => '通知管理', 'url' => 'notifications', 'key' => 'notifications'],
            ],
        ],
        'ext' => [
            'label' => '扩展管理', 'icon' => '🧩', 'icon_key' => 'ext',
            'items' => [
                ['label' => '插件', 'url' => 'plugins', 'key' => 'plugins'],
                ['label' => '主题', 'url' => 'themes', 'key' => 'themes'],
                ['label' => '语言包', 'url' => 'languages', 'key' => 'languages'],
            ],
        ],
        'system' => [
            'label' => '系统工具', 'icon' => '🛠️', 'icon_key' => 'system',
            'items' => [
                ['label' => '缓存管理', 'url' => 'cache', 'key' => 'cache'],
                ['label' => '系统日志', 'url' => 'logs', 'key' => 'logs'],
                ['label' => '操作日志', 'url' => 'adminlogs', 'key' => 'adminlogs'],
                ['label' => '数据备份', 'url' => 'backup', 'key' => 'backup'],
                ['label' => '数据库管理', 'url' => 'database', 'key' => 'database'],
                ['label' => '在线升级', 'url' => 'upgrade', 'key' => 'upgrade'],
                ['label' => '系统信息', 'url' => 'system', 'key' => 'system'],
            ],
        ],
    ];

    /**
     * 用户组权限项
     */
    public static function permissionList(): array
    {
        return [
            'thread.view' => '浏览主题',
            'thread.create' => '发布主题',
            'thread.reply' => '回复主题',
            'thread.edit_own' => '编辑自己的主题',
            'thread.delete_own' => '删除自己的主题',
            'thread.color' => '使用彩色标题',
            'thread.attachment' => '帖子中上传附件',
            'thread.top' => '置顶主题（版主权限）',
            'thread.essence' => '设置精华（版主权限）',
            'thread.move' => '移动主题（版主权限）',
            'thread.close' => '关闭主题（版主权限）',
            'say.create' => '发布说说',
            'say.comment' => '评论说说',
            'attachment.upload' => '上传附件',
            'attachment.download' => '下载附件',
            'pm.send' => '发送消息',
            'user.view_profile' => '查看他人资料',
            'search.use' => '使用搜索',
        ];
    }

    /**
     * 开发团队 / 关于信息
     */
    public static function teamInfo(): array
    {
        return [
            'product' => 'ChatBBS',
            'slogan' => '轻量、快速、零依赖的现代化社区论坛系统',
            'stack' => 'PHP 8 + MySQL 8，无 Composer 依赖，上传即用',
            'dev' => 'WorkBuddy（beta21 及之前：架构设计 · 核心开发）',
            'dev_current' => 'DeepSeek（beta22 起：核心开发 · 持续迭代）',
            'product_owner' => 'likeweixue（产品 · 体验 · 测试）',
            'license' => '自由使用与二次开发，保留署名即可',
            'note' => 'beta21 及之前的架构、核心功能与界面由 WorkBuddy 与 likeweixue 协作完成；从 beta22 起由 DeepSeek 持续开发与维护。',
        ];
    }

    /**
     * 顶栏搜索的关键词补充表（key => 额外可搜索的词，空格分隔）
     * 让「smtp」「密码」「附件」这类词能搜到对应菜单
     */
    public static function searchKeywords(): array
    {
        return [
            'dashboard.index' => '首页 概览 home',
            'forums' => '版块 分类 forum',
            'threads' => '帖子 主题管理 审核 thread post',
            'posts' => '回复 评论 回帖',
            'tags' => 'tag 标签管理',
            'marks' => '标记 前缀 徽章',
            'says' => '说说 动态 空间',
            'recommends' => '推荐 避雷 星标',
            'attachments' => '附件 上传 文件 库 attachment file',
            'reports' => '举报 审核 投诉',
            'forbidden_words' => '违禁词 敏感词 屏蔽 词库 过滤',
            'chat_groups' => '群聊 聊天室 群组 group chat',
            'users' => '会员 成员 user 会员列表',
            'groups' => '用户组 组 权限组',
            'certs' => '认证 大v 身份',
            'levels' => '等级 经验 level',
            'checkins' => '签到 打卡 日历',
            'currency' => '货币 金币 积分 奖励 钱 money',
            'messages' => '私信 聊天 消息 pm chat',
            'settings' => '站点 网站 名称 标题 logo favicon 备案 seo 访问模式 语言 description',
            'settings.features' => '功能 开关 说说 签到 推荐 编辑器 markdown 审核 标签数 聊天 附近的人',
            'settings.currency' => '货币规则 金币 积分 签到奖励 发帖奖励',
            'settings.permissions' => '权限 用户组 用户组权限 permission',
            'settings.security' => '安全 密码 超级密码 二级密码 独立密码 二次验证 验证码 注册 上传 锁定 安全设置 timeout 自动退出',
            'menus' => '菜单 导航 nav',
            'sidebar' => '侧边栏 模块 面板 sidebar',
            'list_tabs' => '筛选 列表 标签栏 tabs 分类',
            'mail' => '邮箱 smtp 邮件 email 发件 信箱',
            'notifications' => '通知 广播 公告 notice',
            'plugins' => '插件 plugin 扩展',
            'themes' => '主题模板 外观 theme skin',
            'languages' => '语言包 language i18n 翻译 english',
            'cache' => '缓存 cache',
            'logs' => '日志 错误 log',
            'adminlogs' => '操作日志 管理记录',
            'backup' => '备份 导出 恢复 backup',
            'database' => '数据库 sql 修复表 优化',
            'upgrade' => '升级 更新 版本 update version',
            'system' => '系统信息 环境 php 版本 info',
        ];
    }

    /**
     * 后台访问守卫：未登录跳后台登录页，无权限 403
     */
    public static function guard(): void
    {
        if (!Auth::check()) {
            $back = (string)($_SERVER['REQUEST_URI'] ?? '');
            Session::flash('error', '请先登录管理后台');
            header('Location: ' . admin_url('login' . ($back !== '' ? '?back=' . urlencode($back) : '')));
            exit;
        }
        if (!Auth::isAdmin()) {
            http_response_code(403);
            echo '<div style="max-width:640px;margin:80px auto;font-family:-apple-system,sans-serif;text-align:center">'
                . '<h1 style="font-size:20px">403 - 无访问权限</h1>'
                . '<p style="color:#888"><a href="' . h(url('')) . '">返回前台首页</a></p></div>';
            exit;
        }
        // 后台独立密码（二次验证）：开启后即使账号密码泄露，不知道独立密码也进不了后台
        $hash = (string)Settings::get('admin_pass_hash', '');
        $passOn = (int)Settings::get('admin_pass_enable', 0) === 1 && $hash !== '';
        if ($passOn && Session::get('admin_pass_ok') !== true) {
            $back = (string)($_SERVER['REQUEST_URI'] ?? '');
            header('Location: ' . admin_url('password' . ($back !== '' ? '?back=' . urlencode($back) : '')));
            exit;
        }
        // 后台无操作自动退出：N 分钟没有任何后台请求就要求重新验证独立密码（不影响前台登录态）
        // 未开启独立密码时不强制超时（否则没有可重新验证的凭据，只会徒增麻烦）
        if ($passOn) {
            $minutes = max(0, (int)Settings::get('admin_timeout', 10));
            $now = time();
            $last = (int)Session::get('admin_last_active', 0);
            if ($minutes > 0 && $last > 0 && $now - $last > $minutes * 60) {
                Session::set('admin_pass_ok', false);
                $back = (string)($_SERVER['REQUEST_URI'] ?? '');
                header('Location: ' . admin_url('password?timeout=1' . ($back !== '' ? '&back=' . urlencode($back) : '')));
                exit;
            }
            Session::set('admin_last_active', $now);
        }
    }

    /**
     * 后台中间件闭包（供 Router 使用）
     */
    public static function middleware(): callable
    {
        return static function () {
            self::guard();
            return true;
        };
    }

    protected function render(string $view, array $data = []): string
    {
        $file = CB_ROOT . '/admin/templates/' . $view . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException('后台模板不存在：' . $view);
        }
        $inner = View::renderFile($file, $data);
        $layoutFile = CB_ROOT . '/admin/templates/layout.php';
        return View::renderFile($layoutFile, array_merge($data, [
            'content' => $inner,
            'menu' => self::$menu,
            'current_key' => $data['current_key'] ?? '',
            'admin_user' => Auth::user(),
            'pending_reports' => (int)\App\Models\Report::pending(),
            'pending_threads' => (int)Database::value('SELECT COUNT(*) FROM ' . Database::table('threads') . ' WHERE status = 0', [], 0),
        ]));
    }

    protected function json(array $data): void
    {
        Response::json($data);
    }

    protected function success($data = null, string $msg = '操作成功'): void
    {
        Response::success($data, $msg);
    }

    protected function fail(string $msg = '操作失败'): void
    {
        Response::fail($msg);
    }

    protected function back(?string $msg = null, string $type = 'success'): void
    {
        if ($msg !== null) {
            Session::flash($type, $msg);
        }
        $ref = Request::referer();
        if ($ref === '') {
            $ref = url('admin');
        }
        header('Location: ' . $ref);
        exit;
    }

    protected function log(string $action, string $target = ''): void
    {
        \App\Core\Logger::admin($action, $target);
    }

    protected function page(int $default = 20): int
    {
        return max(1, Request::int('page', 1));
    }

    protected function ids(): array
    {
        $ids = Request::post('ids');
        return is_array($ids) ? array_map('intval', $ids) : [];
    }

    /**
     * 分页查询
     */
    protected function queryPage(string $selectSql, string $countSql, array $params, int $perPage = 20, string $base = ''): array
    {
        $page = $this->page();
        $total = (int)Database::value($countSql, $params, 0);
        $offset = ($page - 1) * $perPage;
        $items = Database::fetchAll($selectSql . ' LIMIT ' . $offset . ',' . $perPage, $params);
        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'pager' => View::paginate($total, $perPage, $page, $base),
        ];
    }

    /**
     * 构建带筛选参数的分页 base URL
     */
    protected function baseUrl(string $path, array $query): string
    {
        $query = array_filter($query, static fn($v) => $v !== '' && $v !== null);
        $url = admin_url($path);
        return $query ? $url . '?' . http_build_query($query) : $url;
    }

    /**
     * 后台安全纯文本（仅去标签，不做实体编码，避免双重转义）
     */
    protected function pure(string $key, int $maxLen = 0): string
    {
        $v = trim(strip_tags((string)Request::post($key, '')));
        $v = str_replace(["\0", "\r"], '', $v);
        return $maxLen > 0 ? mb_substr($v, 0, $maxLen) : $v;
    }

    /**
     * 统一渲染通用页面
     */
    protected function pageView(string $title, string $key, string $html, array $actions = []): string
    {
        return $this->render('page', [
            'title' => $title,
            'current_key' => $key,
            'html' => $html,
            'actions' => $actions,
        ]);
    }
}
