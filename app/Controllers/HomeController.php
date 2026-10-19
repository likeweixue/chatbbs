<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Settings;
use App\Models\Thread;
use App\Models\User;
use App\Services\Stats;

class HomeController extends BaseController
{
    public function index(): string
    {
        $perPage = max(5, (int)Settings::get('threads_per_page', 20));
        $page = max(1, (int)\App\Core\Request::int('page', 1));
        $tabRes = $this->resolveTab();
        $result = Thread::list([
            'page' => $page,
            'per_page' => $perPage,
            'order' => $tabRes['order'],
            'essence' => $tabRes['essence'],
            'mark_id' => $tabRes['mark_id'],
            'digest' => $tabRes['digest'],
        ]);
        $tabQuery = '?tab=' . rawurlencode($tabRes['tab']);
        $baseUrl = url($tabQuery);

        $says = [];
        if ((int)Settings::get('say_on_home', 1)) {
            $says = \App\Models\Say::list(1, 5)['items'];
        }

        return $this->render('home/index', [
            'title' => Settings::get('site_name', 'ChatBBS'),
            'threads' => $result['items'],
            'total' => $result['total'],
            'page' => $page,
            'per_page' => $perPage,
            'pager' => \App\Core\View::paginate($result['total'], $perPage, $page, $baseUrl),
            'order' => $tabRes['order'],
            'tab' => $tabRes['tab'],
            'list_tabs' => self::listTabs(),
            'stats' => Stats::forum(),
            'new_users' => User::newest(8),
            'active_users' => User::active(8),
            'hot_threads' => Thread::hot(8),
            'latest_threads' => Thread::latest(8),
            'says' => $says,
            'forums' => \App\Models\Forum::tree(),
            'hot_tags' => \App\Models\Tag::hot(15),
        ]);
    }

    /**
     * 授权证书页
     *
     * 自动识别当前安装的域名（不是 ChatBBS 官网自身），
     * 声明该副本已获得免费授权，风格参照 Discuz! 的授权证书页。
     */
    public function license(): string
    {
        $lang = (string)Request::get('lang', 'cn');
        $lang = in_array($lang, ['cn', 'en'], true) ? $lang : 'cn';

        // 被授权方：当前站点域名（自动获取）
        $host = (string)($_SERVER['HTTP_HOST'] ?? '');
        if ($host === '') {
            $host = parse_url((string)site_base(), PHP_URL_HOST) ?: '';
        }
        $host = preg_replace('/:\d+$/', '', (string)$host);
        if ($host === '') {
            $host = '本站';
        }

        return $this->render('license', [
            'title' => $lang === 'en' ? 'License Certificate' : '授权证书',
            'host' => $host,
            'lang' => $lang,
            'hide_sidebar' => true,
        ]);
    }

    /**
     * 切换前台界面语言（只切换界面名词，不影响帖子内容）
     */
    public function switchLang(string $code): void
    {
        $code = rawurldecode(trim((string)$code));
        \App\Core\Lang::persist($code);

        redirect($this->safeBack());
    }

    /**
     * 计算切换后要回到的地址：仅允许站内相对路径，防止开放重定向
     */
    private function safeBack(): string
    {
        $back = (string)Request::get('back', '');
        $decoded = rawurldecode($back);

        // 非法：带协议头、协议相对、含控制字符
        if (
            $back !== '' &&
            !preg_match('#^[a-z][a-z0-9+.\-]*://#i', $decoded) &&
            !str_starts_with($decoded, '//') &&
            !preg_match('/[\r\n\x00]/', $decoded)
        ) {
            return ltrim($back, '/');
        }

        // 退而求其次：同源 referer
        $ref = (string)Request::referer();
        $host = (string)($_SERVER['HTTP_HOST'] ?? '');
        if ($ref !== '' && $host !== '' && stripos($ref, $host) !== false) {
            $path = (string)parse_url($ref, PHP_URL_PATH);
            $q = (string)parse_url($ref, PHP_URL_QUERY);
            return ltrim($path, '/') . ($q !== '' ? '?' . $q : '');
        }

        return '';
    }
}
