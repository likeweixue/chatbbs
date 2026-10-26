<?php

declare(strict_types=1);

namespace App\Admin\Controllers;

use App\Admin\AdminUI;
use App\Admin\BaseAdmin;
use App\Core\Database;
use App\Core\Logger;
use App\Core\Mailer;
use App\Core\Request;
use App\Core\Security;
use App\Core\Session;
use App\Core\Settings;
use App\Models\Forum;
use App\Models\Notification;
use App\Models\User;

/**
 * 论坛设置：站点 / 功能 / 货币 / 权限 / 菜单 / 邮箱 / 通知
 */
class SettingController extends BaseAdmin
{
    /* ================== 站点设置 ================== */
    public function settings(): string
    {
        $themes = self::scanThemes();
        $html = '<div class="adm-panel"><div class="adm-panel-head">站点设置</div><div class="adm-panel-body">'
            . AdminUI::form([
            'action' => admin_url('settings/save'),
            'data' => Settings::all(),
            'hidden' => ['scope' => 'base'],
            'submit' => '保存设置',
            'fields' => [
                ['name' => 'site_name', 'label' => '站点名称'],
                ['name' => 'site_url', 'label' => '站点地址', 'tip' => '留空自动识别，如 https://bbs.example.com（结尾不要带 /）'],
                ['name' => 'site_description', 'label' => '站点描述', 'type' => 'textarea'],
                ['name' => 'site_keywords', 'label' => '关键词', 'tip' => '多个用英文逗号分隔'],
                ['name' => 'home_seo_title', 'label' => '首页 SEO 标题', 'tip' => '留空使用站点名称'],
                ['name' => 'logo_type', 'label' => 'Logo 类型', 'type' => 'select', 'options' => [
                    'text' => '仅文字（推荐，简洁）',
                    'image' => '自定义 Logo 图片',
                ], 'tip' => '选择"自定义 Logo 图片"时，下方上传或填写的图片会替换站点名称位置'],
                ['name' => 'logo_text', 'label' => 'Logo 文字', 'tip' => '留空使用站点名称；配合上方可自定义成任意字样'],
                ['name' => 'site_logo', 'label' => 'Logo 图片', 'type' => 'image', 'tip' => '可直接填写图片地址，也可以选择本地文件上传（上传后自动填入）'],
                ['name' => 'site_favicon', 'label' => '站点图标（Favicon）', 'type' => 'image',
                    'accept' => 'image/*,.ico', 'tip' => '建议 32×32 或 64×64 的 png / ico；留空使用内置复古图标'],
                ['name' => 'site_icp', 'label' => 'ICP 备案号'],
                ['name' => 'site_footer', 'label' => '页脚信息', 'type' => 'textarea', 'tip' => '支持 HTML'],
                ['name' => 'theme', 'label' => '前台主题', 'type' => 'select', 'options' => $themes],
                ['name' => 'url_mode', 'label' => 'URL 模式', 'type' => 'select', 'options' => [
                    'auto' => '自动探测（推荐，不会 404）',
                    'rewrite' => '伪静态（需服务器已配置 rewrite 规则）',
                    'query' => '强制兼容模式（?r=xxx）',
                ], 'tip' => '自动探测：服务器配了伪静态就用简洁地址，没配就自动降级为兼容模式，永远不会出现链接 404。若已按 README 配置好 Nginx/Apache 规则，可手动指定"伪静态"以获得统一美观的地址'],
                ['name' => 'language', 'label' => '语言', 'type' => 'select', 'options' => ['zh-CN' => '简体中文', 'en-US' => 'English']],
                ['name' => 'site_access', 'label' => '访问模式', 'type' => 'select', 'options' => [
                    'all_rw' => '开放浏览与发帖',
                    'all_r_login_w' => '开放浏览，登录后可发帖',
                    'login_rw' => '仅登录用户可访问',
                    'admin_only' => '仅管理员可访问',
                ]],
                ['name' => 'allow_guest_view', 'label' => '游客可浏览', 'type' => 'switch'],
                ['name' => 'site_closed', 'label' => '关闭站点', 'type' => 'switch', 'tip' => '开启后前台显示维护提示'],
                ['name' => 'closed_reason', 'label' => '关闭原因', 'type' => 'textarea'],
                ['name' => 'threads_per_page', 'label' => '每页主题数', 'type' => 'number'],
                ['name' => 'posts_per_page', 'label' => '每页回复数', 'type' => 'number'],
                ['name' => 'sidebar_enable', 'label' => '启用侧边栏', 'type' => 'switch'],
                ['name' => 'sidebar_default_hidden', 'label' => '默认隐藏侧边栏', 'type' => 'switch',
                    'tip' => '开启后，首页、板块页与个人中心默认只显示内容区；访客可点顶栏的侧栏按钮展开，选择会记录在浏览器中'],
                ['name' => 'lang_switch', 'label' => '顶栏语言切换', 'type' => 'switch',
                    'tip' => '开启后前台顶栏显示 🌐 语言切换下拉；关闭则全站固定使用设定的「语言」'],
                ['name' => 'sidebar_side', 'label' => '侧边栏位置', 'type' => 'select', 'options' => ['right' => '右侧', 'left' => '左侧']],
                ['name' => 'list_compact', 'label' => '帖子列表简洁模式', 'type' => 'switch',
                    'tip' => '开启后首页/板块页的帖子列表不再显示正文摘要，改为紧凑单行显示，列表更清爽'],
                ['name' => 'forum_layout', 'label' => '板块页布局', 'type' => 'select', 'options' => [
                    'list' => '列表（Discuz 风格，简介 + 最后发表）',
                    'grid' => '网格（一行多个，紧凑）',
                ], 'tip' => '板块总览页（/forums）的展示方式'],
                ['name' => 'forum_grid_cols', 'label' => '网格每行板块数', 'type' => 'number', 'tip' => '仅"网格"布局生效，建议 3 - 5'],
                ['name' => 'friend_links', 'label' => '友情链接', 'type' => 'textarea', 'tip' => '每行一条，格式：名称|网址'],
                ['name' => 'sensitive_words', 'label' => '敏感词过滤', 'type' => 'textarea', 'tip' => '多个用英文逗号分隔，命中内容将被替换'],
            ],
        ]) . '</div></div>';

        $html .= '<div class="adm-panel"><div class="adm-panel-head">注册协议</div><div class="adm-panel-body">'
            . AdminUI::form([
                'action' => admin_url('settings/save'),
                'data' => Settings::all(),
                'hidden' => ['scope' => 'agreement'],
                'submit' => '保存注册协议',
                'fields' => [
                    ['name' => 'register_agreement', 'label' => '注册时需同意协议', 'type' => 'switch',
                        'tip' => '开启后，注册页会展示用户协议，未勾选则无法注册'],
                    ['name' => 'register_agreement_title', 'label' => '协议名称', 'type' => 'text',
                        'tip' => '展示在注册页勾选框的链接文字上，如《用户协议》'],
                    ['name' => 'register_agreement_text', 'label' => '协议内容', 'type' => 'textarea', 'rows' => 14,
                        'tip' => '支持换行与常见 HTML（加粗、列表、链接等）；留空则注册页只显示勾选框不出弹窗'],
                ],
            ]) . '</div></div>';

        $forumOpts = [0 => '全部板块'] + Forum::options(false);
        $tagOpts = [0 => '全部标签'];
        foreach (Database::fetchAll('SELECT id, name FROM ' . Database::table('tags') . ' ORDER BY threads DESC, id ASC LIMIT 100') as $tg) {
            $tagOpts[(int)$tg['id']] = (string)$tg['name'];
        }
        $html .= '<div class="adm-panel"><div class="adm-panel-head">RSS 订阅</div><div class="adm-panel-body">'
            . AdminUI::form([
                'action' => admin_url('settings/rss'),
                'data' => Settings::all(),
                'submit' => '保存 RSS 设置',
                'fields' => [
                    ['name' => 'rss_enable', 'label' => '启用 RSS', 'type' => 'switch'],
                    ['name' => 'rss_title', 'label' => 'RSS 标题', 'tip' => '留空使用「站点名称 - 最新主题」'],
                    ['name' => 'rss_limit', 'label' => '每次输出条数', 'type' => 'number', 'tip' => '1 - 100，默认 20'],
                    ['name' => 'rss_forum', 'label' => '默认板块', 'type' => 'select', 'options' => $forumOpts,
                        'tip' => '只推送该板块的主题；也可以在订阅地址加 ?forum=ID 临时指定'],
                    ['name' => 'rss_tag', 'label' => '默认标签', 'type' => 'select', 'options' => $tagOpts,
                        'tip' => '只推送该标签的主题；也可以在订阅地址加 ?tag=ID 临时指定'],
                    ['name' => 'reader_enable', 'label' => '启用 RSS 阅读', 'type' => 'switch'],
                    ['name' => 'reader_title', 'label' => '阅读页标题', 'type' => 'text'],
                    ['name' => 'reader_limit', 'label' => '每个源显示条数', 'type' => 'number', 'tip' => '1 - 50'],
                    ['name' => 'reader_sources', 'label' => '订阅源', 'type' => 'textarea', 'tip' => '每行一个：名称|RSS地址；例如：少数派|https://sspai.com/feed'],
                ],
            ])
            . '<div class="adm-form-tip">阅读页：<code>' . h(url('reader')) . '</code><br>订阅地址：<code>' . h(url('rss')) . '</code><br>按板块：<code>' . h(url('rss?forum=1')) . '</code>　按标签：<code>' . h(url('rss?tag=1')) . '</code></div>'
            . '</div></div>';

        return $this->pageView('站点设置', 'settings', $html);
    }

    /**
     * 保存 RSS 设置
     */
    public function saveRss(): void
    {
        Settings::setMany([
            'rss_enable' => Request::bool('rss_enable') ? 1 : 0,
            'rss_title' => $this->pure('rss_title', 100),
            'rss_limit' => (string)max(1, min(100, Request::int('rss_limit', 20))),
            'rss_forum' => (string)max(0, Request::int('rss_forum')),
            'rss_tag' => (string)max(0, Request::int('rss_tag')),
            'reader_enable' => Request::bool('reader_enable') ? 1 : 0,
            'reader_title' => $this->pure('reader_title', 100) ?: 'RSS 阅读',
            'reader_limit' => (string)max(1, min(50, Request::int('reader_limit', 20))),
            'reader_sources' => $this->pure('reader_sources', 10000),
        ]);
        \App\Core\Cache::forget('settings');
        $this->log('修改 RSS 设置');
        $this->back('RSS 设置已保存');
    }

    /* ================== 安全设置（独立菜单） ================== */
    public function security(): string
    {
        $html = '<div class="adm-alert info">这里集中管理后台登录安全：超级密码（后台独立密码）、无操作自动退出、注册与上传限制等。</div>';
        $html .= AdminUI::form([
            'action' => admin_url('settings/security/save'),
            'data' => Settings::all(),
            'submit' => '保存安全设置',
            'fields' => [
                ['name' => 'admin_captcha', 'label' => '后台登录验证码', 'type' => 'switch'],
                ['name' => 'admin_pass_enable', 'label' => '超级密码（进后台二次验证）', 'type' => 'switch',
                    'tip' => '开启后，管理员账号登录成功还需输入一次下方设置的超级密码才能进入后台；即使账号密码泄露也进不了后台'],
                ['name' => 'admin_pass_new', 'label' => '设置超级密码', 'type' => 'password',
                    'tip' => '输入新密码即修改；留空保持原密码不变。若尚未设置过密码，开启开关后必须在此填一次。忘记密码可在数据库 settings 表删除 admin_pass_hash 重置'],
                ['name' => 'admin_pass_confirm', 'label' => '确认超级密码', 'type' => 'password'],
                ['name' => 'admin_timeout', 'label' => '后台无操作退出(分钟)', 'type' => 'number',
                    'tip' => '后台超过该分钟数没有任何操作，就要求重新输入超级密码才能继续（不影响前台登录状态）；填 0 关闭。需先开启超级密码才生效'],
                ['name' => 'login_fail_limit', 'label' => '登录失败上限', 'type' => 'number', 'tip' => '超过后锁定 15 分钟，0 不限制'],
                ['name' => 'register_captcha', 'label' => '前台注册验证码', 'type' => 'switch'],
                ['name' => 'open_register', 'label' => '开放注册', 'type' => 'switch'],
                ['name' => 'email_verify', 'label' => '邮箱验证', 'type' => 'switch'],
                ['name' => 'open_forgot', 'label' => '开放找回密码', 'type' => 'switch'],
                ['name' => 'upload_max_size', 'label' => '上传大小限制(MB)', 'type' => 'number'],
                ['name' => 'upload_allow_ext', 'label' => '允许的后缀', 'type' => 'textarea', 'tip' => '英文逗号分隔，如 jpg,png,zip'],
            ],
        ]);
        return $this->pageView('安全设置', 'settings.security', $html);
    }

    public function saveSecurity(): void
    {
        $data = [];
        foreach (['upload_max_size', 'login_fail_limit'] as $k) {
            $data[$k] = Request::int($k);
        }
        $data['admin_timeout'] = max(0, min(1440, Request::int('admin_timeout', 10)));
        $data['upload_allow_ext'] = $this->pure('upload_allow_ext', 500);
        foreach (['admin_captcha', 'register_captcha', 'open_register', 'email_verify', 'open_forgot'] as $k) {
            $data[$k] = Request::bool($k) ? 1 : 0;
        }
        // 超级密码（后台独立密码）
        $data['admin_pass_enable'] = Request::bool('admin_pass_enable') ? 1 : 0;
        $new = (string)Request::raw('admin_pass_new');
        $confirm = (string)Request::raw('admin_pass_confirm');
        $oldHash = (string)Settings::get('admin_pass_hash', '');
        if ($new !== '' || $confirm !== '') {
            if ($new !== $confirm) {
                // 密码不一致时不改动开关，避免误锁
                Session::flash('error', '两次输入的超级密码不一致，其他设置已保存');
                unset($data['admin_pass_enable']);
            } elseif (mb_strlen($new) < 6) {
                Session::flash('error', '超级密码至少 6 位，其他设置已保存');
                unset($data['admin_pass_enable']);
            } else {
                $data['admin_pass_hash'] = password_hash($new, PASSWORD_DEFAULT);
                Session::set('admin_pass_ok', true); // 设置成功后本会话免再次验证
                Session::set('admin_last_active', time());
                Logger::admin('设置后台超级密码');
            }
        } elseif ($data['admin_pass_enable'] === 1 && $oldHash === '') {
            Session::flash('error', '已开启超级密码但未设置密码，请填写并重新保存');
            $data['admin_pass_enable'] = 0;
        }
        Settings::setMany($data);
        \App\Core\Cache::forget('settings');
        $this->log('修改安全设置', implode(',', array_keys($data)));
        $this->back('安全设置已保存');
    }

    public function save(): void
    {
        $scope = (string)Request::post('scope', 'base');
        $data = [];
        if ($scope === 'agreement') {
            $data['register_agreement'] = Request::bool('register_agreement') ? 1 : 0;
            $data['register_agreement_title'] = $this->pure('register_agreement_title', 60);
            if ($data['register_agreement_title'] === '') {
                $data['register_agreement_title'] = '《用户协议》';
            }
            $data['register_agreement_text'] = Security::cleanHtml(Request::raw('register_agreement_text'));
        } elseif ($scope === 'security') {
            // 兼容旧版站点设置页提交的安全设置表单：转发到新的保存逻辑
            $this->saveSecurity();
            return;
        } else {
            foreach (['threads_per_page', 'posts_per_page'] as $k) {
                $data[$k] = max(1, Request::int($k, 20));
            }
            foreach (['site_name', 'site_url', 'site_description', 'site_keywords', 'home_seo_title',
                'logo_type', 'logo_text', 'site_icp', 'theme', 'language', 'site_access',
                'closed_reason', 'sidebar_side', 'friend_links', 'sensitive_words', 'url_mode',
                'forum_layout', 'forum_grid_cols',
            ] as $k) {
                $data[$k] = $this->pure($k, 0);
            }
            $data['site_footer'] = Security::cleanHtml(Request::raw('site_footer'));
            foreach (['allow_guest_view', 'site_closed', 'sidebar_enable', 'sidebar_default_hidden', 'list_compact', 'lang_switch'] as $k) {
                $data[$k] = Request::bool($k) ? 1 : 0;
            }
            // Logo / 站点图标上传（保存到 data/uploads/site 下）
            foreach (['site_logo' => 'site_logo_file', 'site_favicon' => 'site_favicon_file'] as $key => $fileKey) {
                $file = Request::file($fileKey);
                if (!$file || empty($file['name']) || (int)($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                    continue;
                }
                [$ok, $res] = \App\Core\Upload::save($file, 'site', (int)(\App\Core\Auth::id() ?? 0));
                if ($ok) {
                    $data[$key] = (string)$res['url'];
                } else {
                    \App\Core\Session::flash('error', (string)$res);
                }
            }
            // 未被本次上传覆盖时保留原值
            if (!isset($data['site_logo'])) {
                $data['site_logo'] = $this->pure('site_logo', 255);
            }
            if (!isset($data['site_favicon'])) {
                $data['site_favicon'] = $this->pure('site_favicon', 255);
            }
        }
        Settings::setMany($data);
        \App\Core\Cache::forget('settings');
        $this->log('修改站点设置(' . $scope . ')', implode(',', array_keys($data)));
        $this->back('设置已保存');
    }


    /* ================== 功能开关 ================== */
    public function features(): string
    {
        $html = AdminUI::form([
            'action' => admin_url('settings/features/save'),
            'data' => Settings::all(),
            'submit' => '保存功能开关',
            'fields' => [
                ['name' => 'thread_audit', 'label' => '主题需审核', 'type' => 'switch'],
                ['name' => 'allow_user_tag', 'label' => '允许用户自建标签', 'type' => 'switch'],
                ['name' => 'max_tags', 'label' => '每帖最多标签数', 'type' => 'number'],
                ['name' => 'say_enable', 'label' => '启用说说', 'type' => 'switch'],
                ['name' => 'say_audit', 'label' => '说说需审核', 'type' => 'switch'],
                ['name' => 'say_allow_image', 'label' => '说说支持图片', 'type' => 'switch'],
                ['name' => 'say_allow_comment', 'label' => '说说支持评论', 'type' => 'switch'],
                ['name' => 'say_allow_like', 'label' => '说说支持点赞', 'type' => 'switch'],
                ['name' => 'say_on_home', 'label' => '首页展示说说', 'type' => 'switch'],
                ['name' => 'recommend_enable', 'label' => '启用推荐/避雷', 'type' => 'switch'],
                ['name' => 'checkin_enable', 'label' => '启用签到', 'type' => 'switch'],
                ['name' => 'reply_preview_count', 'label' => '楼层回复默认展示条数', 'type' => 'number',
                    'tip' => '帖子详情页每层楼下的"楼中楼"回复默认显示几条，超出的部分由"展开其余 N 条回复"按钮展开（例如填 5，则第 6 条起需要展开）'],
                ['name' => 'editor_default_mode', 'label' => '编辑器默认模式', 'type' => 'select',
                    'options' => ['rich' => '富文本（站点自带，默认）', 'markdown' => 'Markdown'],
                    'tip' => '用户仍可在编辑器右上角自行切换富文本 / Markdown'],
                ['name' => 'chat_enable', 'label' => '启用聊天（群聊）', 'type' => 'switch',
                    'tip' => '关闭后用户仍可单聊，但不能建群 / 加群'],
                ['name' => 'lan_enable', 'label' => '启用「附近的人」', 'type' => 'switch',
                    'tip' => '按 IP 网段判定同一局域网（IPv4 取 /24）；关闭后聊天页不再显示「附近的人」'],
                ['name' => 'lan_discover_default', 'label' => '新用户默认可被发现', 'type' => 'switch',
                    'tip' => '开启后新注册用户默认出现在「附近的人」里（用户可自行关闭）'],
                ['name' => 'chat_poll_interval', 'label' => '聊天轮询间隔(ms)', 'type' => 'number',
                    'tip' => '客户端拉取新消息的间隔，默认 5000 毫秒；越小越实时、请求越多（下限 2000）'],
                ['name' => 'chat_group_max', 'label' => '每人最多加入群数', 'type' => 'number'],
            ],
        ]);
        return $this->pageView('功能开关', 'settings.features', $html);
    }

    public function saveFeatures(): void
    {
        $switches = [
            'thread_audit', 'allow_user_tag', 'say_enable', 'say_audit', 'say_allow_image',
            'say_allow_comment', 'say_allow_like', 'say_on_home', 'recommend_enable', 'checkin_enable',
            'chat_enable', 'lan_enable', 'lan_discover_default',
        ];
        $data = [];
        foreach ($switches as $k) {
            $data[$k] = Request::bool($k) ? 1 : 0;
        }
        $data['max_tags'] = max(0, Request::int('max_tags', 5));
        $data['reply_preview_count'] = max(1, Request::int('reply_preview_count', 5));
        $data['editor_default_mode'] = Request::post('editor_default_mode', 'rich') === 'markdown' ? 'markdown' : 'rich';
        $data['chat_poll_interval'] = max(2000, min(30000, Request::int('chat_poll_interval', 5000) ?: 5000));
        $data['chat_group_max'] = max(1, min(500, Request::int('chat_group_max', 30) ?: 30));
        Settings::setMany($data);
        \App\Core\Cache::forget('settings');
        $this->log('修改功能开关');
        $this->back('功能开关已保存');
    }

    /* ================== 货币规则 ================== */
    public function currency(): string
    {
        $html = AdminUI::form([
            'action' => admin_url('settings/currency/save'),
            'data' => Settings::all(),
            'submit' => '保存货币规则',
            'fields' => [
                ['name' => 'currency_name', 'label' => '货币名称',
                    'tip' => '可自定义成任意叫法，例如：金币、积分、软妹币、美刀、银两…（全站「我的余额 / 发帖奖励 / 附件售价」等处统一显示）'],
                ['name' => 'currency_symbol', 'label' => '货币符号（可选）',
                    'tip' => '留空显示「数字 + 货币名称」（如 12.00 软妹币）；填写后显示「符号 + 数字」（如 ¥12.00）'],
                ['name' => 'currency_unit', 'label' => '货币单位', 'tip' => '如：枚、分'],
                ['name' => 'reward_thread', 'label' => '发帖奖励', 'type' => 'number', 'step' => '0.01'],
                ['name' => 'reward_post', 'label' => '回复奖励', 'type' => 'number', 'step' => '0.01'],
                ['name' => 'reward_checkin', 'label' => '签到基础奖励', 'type' => 'number', 'step' => '0.01'],
                ['name' => 'reward_checkin_extra', 'label' => '签到递增奖励', 'type' => 'number', 'step' => '0.01',
                    'tip' => '每连续 7 天额外增加的奖励'],
                ['name' => 'reward_register', 'label' => '注册赠送', 'type' => 'number', 'step' => '0.01'],
                ['name' => 'say_reward', 'label' => '发布说说奖励', 'type' => 'number', 'step' => '0.01'],
            ],
        ]);
        $html .= '<div class="adm-panel"><div class="adm-panel-head">连续签到奖励预览</div><div class="adm-panel-body tight">'
            . '<table class="adm-table"><thead><tr><th>连续天数</th><th>当日奖励</th><th>额外奖励</th><th>合计</th></tr></thead><tbody>';
        foreach ([1, 3, 7, 15, 30] as $c) {
            $base = (float)Settings::get('reward_checkin', 3);
            $extra = (float)Settings::get('reward_checkin_extra', 1);
            $bonus = $c >= 30 ? 20 : ($c >= 7 ? 10 : ($c >= 3 ? 5 : 0));
            $total = $base + floor($c / 7) * $extra + $bonus;
            $html .= '<tr><td>第 ' . $c . ' 天</td><td>' . ($base + floor($c / 7) * $extra) . '</td>'
                . '<td>' . ($bonus ? '<span class="adm-tag orange">+' . $bonus . '</span>' : '-') . '</td>'
                . '<td><b>' . $total . '</b></td></tr>';
        }
        $html .= '</tbody></table></div></div>';
        return $this->pageView('货币规则', 'settings.currency', $html);
    }

    public function saveCurrency(): void
    {
        $data = [
            'currency_name' => $this->pure('currency_name', 20),
            'currency_symbol' => $this->pure('currency_symbol', 8),
            'currency_unit' => $this->pure('currency_unit', 20),
            'reward_thread' => (float)Request::input('reward_thread', 0),
            'reward_post' => (float)Request::input('reward_post', 0),
            'reward_checkin' => (float)Request::input('reward_checkin', 0),
            'reward_checkin_extra' => (float)Request::input('reward_checkin_extra', 0),
            'reward_register' => (float)Request::input('reward_register', 0),
            'say_reward' => (float)Request::input('say_reward', 0),
        ];
        Settings::setMany($data);
        \App\Core\Cache::forget('settings');
        $this->log('修改货币规则');
        $this->back('货币规则已保存');
    }

    /* ================== 权限设置 ================== */
    public function permissions(): string
    {
        $groups = User::groups();
        $perms = self::permissionList();

        $html = '<form method="post" action="' . h(admin_url('settings/permissions/save')) . '">';
        $html .= csrf_field();
        $html .= '<div class="adm-table-wrap"><table class="adm-table"><thead><tr><th style="width:200px">权限</th>';
        foreach ($groups as $g) {
            $html .= '<th style="text-align:center">' . h((string)$g['name']) . '</th>';
        }
        $html .= '</tr></thead><tbody>';
        foreach ($perms as $key => $label) {
            $html .= '<tr><td>' . h($label) . '<div class="muted" style="font-size:12px">' . h($key) . '</div></td>';
            foreach ($groups as $g) {
                $has = in_array($key, \App\Core\Auth::groupPerms((int)$g['id']), true);
                $checked = $has || (int)$g['id'] <= 2 ? ' checked' : '';
                $html .= '<td style="text-align:center"><input type="checkbox" name="perm[' . (int)$g['id'] . '][]" value="' . h($key) . '"' . $checked . '></td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table></div>';
        $html .= '<div class="adm-form-actions" style="padding-left:0">'
            . '<button class="adm-btn primary" type="submit">保存权限</button></div></form>';

        return $this->pageView('权限设置', 'settings.permissions', $html);
    }

    public function savePermissions(): void
    {
        $perm = Request::post('perm');
        if (!is_array($perm)) {
            $this->back('没有可保存的权限', 'error');
        }
        foreach ($perm as $gid => $list) {
            $list = is_array($list) ? array_values(array_map('strval', $list)) : [];
            Database::update(
                'user_groups',
                ['permissions' => json_encode($list, JSON_UNESCAPED_UNICODE)],
                'id = ?',
                ['i' => (int)$gid]
            );
        }
        \App\Core\Cache::flush();
        $this->log('修改用户组权限');
        $this->back('权限已保存');
    }

    /* ================== 菜单管理 ================== */
    public function menus(): string
    {
        $rows = Database::fetchAll('SELECT * FROM ' . Database::table('menus') . ' ORDER BY sort_order ASC, id ASC');
        $html = AdminUI::table([
            'primary' => 'id',
            'rows' => $rows,
            'columns' => [
                ['key' => 'id', 'label' => 'ID', 'width' => '60px'],
                ['key' => 'title', 'label' => '菜单名称'],
                ['key' => 'url', 'label' => '链接地址', 'width' => '240px'],
                ['key' => 'icon', 'label' => '图标', 'width' => '80px'],
                ['key' => 'target', 'label' => '打开方式', 'width' => '90px'],
                ['key' => 'visible', 'label' => '显示', 'width' => '70px', 'render' => fn($r) => (int)$r['visible'] === 1
                    ? '<span class="adm-tag green">是</span>' : '<span class="adm-tag gray">否</span>'],
                ['key' => 'sort_order', 'label' => '排序', 'width' => '70px'],
            ],
            'actions' => [
                ['label' => '编辑', 'url' => admin_url('menus?edit={id}')],
                ['label' => '删除', 'url' => admin_url('menus/delete/{id}'), 'type' => 'post', 'class' => 'danger', 'confirm' => '确定删除该菜单？'],
            ],
        ]);
        $edit = null;
        $editId = Request::int('edit');
        if ($editId > 0) {
            $edit = Database::fetch('SELECT * FROM ' . Database::table('menus') . ' WHERE id = ?', [$editId]) ?: null;
        }
        $html .= '<div class="adm-panel"><div class="adm-panel-head">' . ($edit ? '编辑菜单' : '新增菜单') . '</div><div class="adm-panel-body">'
            . AdminUI::form([
                'action' => admin_url('menus/save'),
                'data' => $edit ?? [],
                'hidden' => ['id' => $edit['id'] ?? 0],
                'submit' => '保存',
                'fields' => [
                    ['name' => 'title', 'label' => '菜单名称'],
                    ['name' => 'url', 'label' => '链接地址', 'tip' => '站内以 / 开头，外链需完整 http(s)://'],
                    ['name' => 'icon', 'label' => '图标', 'tip' => 'emoji 或图标 class'],
                    ['name' => 'target', 'label' => '打开方式', 'type' => 'select', 'options' => ['_self' => '当前窗口', '_blank' => '新窗口']],
                    ['name' => 'visible', 'label' => '显示', 'type' => 'switch'],
                    ['name' => 'sort_order', 'label' => '排序', 'type' => 'number'],
                ],
            ]) . '</div></div>';
        return $this->pageView('菜单管理', 'menus', $html);
    }

    public function menuSave(): void
    {
        $id = Request::int('id');
        $data = [
            'title' => $this->pure('title', 80),
            'url' => $this->pure('url', 255),
            'icon' => $this->pure('icon', 100),
            'target' => Request::post('target', '_self') === '_blank' ? '_blank' : '_self',
            'visible' => Request::bool('visible') ? 1 : 0,
            'sort_order' => Request::int('sort_order'),
        ];
        if ($data['title'] === '') {
            $this->back('菜单名称不能为空', 'error');
        }
        if ($id > 0) {
            Database::update('menus', $data, 'id = ?', ['i' => $id]);
        } else {
            Database::insert('menus', $data);
        }
        $this->log('保存菜单', $data['title']);
        $this->back('菜单已保存');
    }

    public function menuDelete(int $id): void
    {
        Database::delete('menus', 'id = ?', ['i' => $id]);
        $this->log('删除菜单', 'ID:' . $id);
        $this->back('菜单已删除');
    }

    /* ================== 列表筛选管理 ================== */
    /**
     * 可选的内置筛选类型
     */
    public static function tabTypes(): array
    {
        $types = [
            'new' => '最新发布（按发布时间）',
            'default' => '最新回复（按最后回复）',
            'hot' => '热门（按浏览量）',
            'replies' => '最多回复',
            'essence' => '精华帖',
            'recommend' => '推荐帖（被推荐过的帖子）',
            'avoid' => '避雷帖（被标记避雷的帖子）',
            'digest' => '首页推荐帖',
            'link' => '自定义链接（跳转到任意地址）',
        ];
        foreach (Database::fetchAll('SELECT id, name FROM ' . Database::table('thread_marks') . ' ORDER BY sort_order, id') as $m) {
            $types['mark:' . (int)$m['id']] = '帖子标记：' . (string)$m['name'];
        }
        return $types;
    }

    private static function parseTabs(): array
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
        return array_values(array_filter($items, static fn($i) => is_array($i)));
    }

    public function listTabs(): string
    {
        $items = self::parseTabs();
        $types = self::tabTypes();

        $html = '<div class="adm-alert info">'
            . '控制首页与板块页顶部的筛选标签：可勾选显示、用 ↑↓ 调整顺序、修改标题，也可以新增自定义分类。'
            . '<br>「帖子标记」类型请在「内容管理 → 帖子标记」里先创建标记（例如：公告、归档、过时），这里就能把它作为筛选分类。'
            . '<br>「推荐帖 / 避雷帖」按帖子收到的推荐 / 避雷记录筛选，已替代原来独立的推荐页面。'
            . '</div>';

        $html .= '<form method="post" action="' . h(admin_url('list-tabs/save')) . '" id="tabs-form">';
        $html .= csrf_field();
        $html .= '<input type="hidden" name="tabs" id="tabs-data" value="">';
        $html .= '<div class="adm-panel"><div class="adm-panel-head">筛选标签</div><div class="adm-panel-body tight">'
            . '<table class="adm-table"><thead><tr><th style="width:56px">显示</th><th style="width:200px">标题</th>'
            . '<th>分类类型</th><th style="width:220px">跳转链接（仅自定义链接）</th><th style="width:170px">排序</th>'
            . '<th style="width:70px">删除</th></tr></thead><tbody id="tabs-list">';
        foreach ($items as $it) {
            $t = (string)($it['t'] ?? '');
            $opts = '';
            foreach ($types as $k => $label) {
                $sel = $k === $t ? ' selected' : '';
                $opts .= '<option value="' . h($k) . '"' . $sel . '>' . h($label) . '</option>';
            }
            $html .= '<tr>'
                . '<td><input type="checkbox" class="tb-on"' . ((int)($it['on'] ?? 1) === 1 ? ' checked' : '') . '></td>'
                . '<td><input class="adm-input tb-title" value="' . h((string)($it['title'] ?? '')) . '"></td>'
                . '<td><select class="adm-input tb-type">' . $opts . '</select></td>'
                . '<td><input class="adm-input tb-url" value="' . h((string)($it['url'] ?? '')) . '" placeholder="如 /forum/7 或 https://..."></td>'
                . '<td><div class="adm-row-actions">'
                . '<button type="button" class="adm-link tb-up">↑ 上移</button>'
                . '<button type="button" class="adm-link tb-down">↓ 下移</button></div></td>'
                . '<td><button type="button" class="adm-link danger tb-del">删除</button></td>'
                . '</tr>';
        }
        $html .= '</tbody></table></div></div>';
        $html .= '<div class="adm-toolbar" style="border:1px solid var(--adm-border);border-radius:6px">'
            . '<button type="button" class="adm-btn" id="tb-add">+ 新增一行</button>'
            . '<div class="spacer"></div>'
            . '<button class="adm-btn primary" type="submit">保存筛选栏</button>'
            . '</div></form>';

        $html .= '<script>
        (function () {
          var list = document.getElementById("tabs-list");
          if (!list) return;
          function row(item) {
            var tr = document.createElement("tr");
            tr.innerHTML = "<td><input type=\'checkbox\' class=\'tb-on\'"
              + ((item && item.on === 0) ? "" : " checked") + "></td>"
              + "<td><input class=\'adm-input tb-title\' value=\'" + ((item && item.title) || "") + "\'></td>"
              + "<td>" + document.querySelector("#tabs-list select.tb-type").outerHTML + "</td>"
              + "<td><input class=\'adm-input tb-url\' value=\'" + ((item && item.url) || "") + "\'></td>"
              + "<td><div class=\'adm-row-actions\'>"
              + "<button type=\'button\' class=\'adm-link tb-up\'>↑ 上移</button>"
              + "<button type=\'button\' class=\'adm-link tb-down\'>↓ 下移</button></div></td>"
              + "<td><button type=\'button\' class=\'adm-link danger tb-del\'>删除</button></td>";
            if (item && item.t) {
              tr.querySelector(".tb-type").value = item.t;
            }
            return tr;
          }
          document.getElementById("tb-add").addEventListener("click", function () {
            list.appendChild(row({ title: "新分类", t: "new", on: 1 }));
          });
          list.addEventListener("click", function (e) {
            var tr = e.target.closest("tr");
            if (!tr) return;
            if (e.target.closest(".tb-del")) { tr.remove(); return; }
            var up = e.target.closest(".tb-up"), down = e.target.closest(".tb-down");
            if (!up && !down) return;
            var target = up ? tr.previousElementSibling : tr.nextElementSibling;
            if (!target) return;
            if (up) list.insertBefore(tr, target); else list.insertBefore(target, tr);
          });
          document.getElementById("tabs-form").addEventListener("submit", function () {
            var out = [];
            list.querySelectorAll("tr").forEach(function (tr) {
              out.push({
                t: tr.querySelector(".tb-type").value,
                title: tr.querySelector(".tb-title").value.trim(),
                url: tr.querySelector(".tb-url").value.trim(),
                on: tr.querySelector(".tb-on").checked ? 1 : 0
              });
            });
            document.getElementById("tabs-data").value = JSON.stringify(out);
          });
        })();
        </script>';

        return $this->pageView('列表筛选管理', 'list_tabs', $html);
    }

    public function saveListTabs(): void
    {
        $raw = (string)Request::post('tabs', '');
        $items = json_decode($raw, true);
        if (!is_array($items)) {
            $this->back('数据格式错误', 'error');
        }
        $valid = array_keys(self::tabTypes());
        $out = [];
        foreach ($items as $it) {
            if (!is_array($it)) {
                continue;
            }
            $t = (string)($it['t'] ?? '');
            $title = trim((string)($it['title'] ?? ''));
            if ($title === '' || !in_array($t, $valid, true)) {
                continue;
            }
            $out[] = [
                't' => $t,
                'title' => $title,
                'url' => trim((string)($it['url'] ?? '')),
                'on' => !empty($it['on']) ? 1 : 0,
            ];
        }
        Settings::set('thread_tabs', json_encode($out, JSON_UNESCAPED_UNICODE));
        \App\Core\Cache::forget('settings');
        $this->log('修改列表筛选栏', (string)count($out) . ' 项');
        $this->back('筛选栏已保存');
    }

    /* ================== 侧边栏管理 ================== */
    /**
     * 侧边栏可用模块
     */
    public static function sidebarModules(): array
    {
        return [
            'about' => '关于本站',
            'stats' => '论坛统计',
            'checkin' => '每日签到',
            'calendar' => '日历',
            'newest_users' => '最新会员',
            'newest_threads' => '最新帖子',
            'tags' => '热门标签',
            'links' => '友情链接',
        ];
    }

    public function sidebar(): string
    {
        $all = self::sidebarModules();
        $enabled = array_values(array_filter(array_map('trim', explode(',', (string)Settings::get('sidebar_modules', '')))));
        // 未在配置中的模块默认靠后且未启用
        $rows = [];
        foreach ($enabled as $key) {
            if (isset($all[$key])) {
                $rows[] = ['key' => $key, 'name' => $all[$key], 'on' => 1];
                unset($all[$key]);
            }
        }
        foreach ($all as $key => $name) {
            $rows[] = ['key' => $key, 'name' => $name, 'on' => 0];
        }

        $html = '<div class="adm-alert info">勾选需要显示的模块，并用 ↑ ↓ 调整上下顺序；保存后前台侧边栏按此顺序渲染。</div>';
        $html .= '<form method="post" action="' . h(admin_url('sidebar/save')) . '" id="sidebar-form">';
        $html .= csrf_field();
        $html .= '<input type="hidden" name="modules" id="sidebar-modules" value="">';
        $html .= '<div class="adm-panel"><div class="adm-panel-body tight">'
            . '<table class="adm-table"><thead><tr><th style="width:60px">显示</th><th>模块</th><th style="width:180px">排序</th></tr></thead><tbody id="sidebar-list">';
        foreach ($rows as $r) {
            $html .= '<tr data-key="' . h($r['key']) . '">'
                . '<td><input type="checkbox" class="sb-on" ' . ((int)$r['on'] === 1 ? 'checked' : '') . '></td>'
                . '<td><b>' . h($r['name']) . '</b> <span class="muted">' . h($r['key']) . '</span></td>'
                . '<td><div class="adm-row-actions">'
                . '<button type="button" class="adm-link sb-up">↑ 上移</button>'
                . '<button type="button" class="adm-link sb-down">↓ 下移</button>'
                . '</div></td></tr>';
        }
        $html .= '</tbody></table></div></div>';
        $html .= '<div style="margin-top:12px"><button class="adm-btn primary" type="submit">保存侧边栏</button></div>';
        $html .= '</form>';

        $html .= '<script>
        (function () {
          var list = document.getElementById("sidebar-list");
          if (!list) return;
          function move(tr, dir) {
            if (!tr) return;
            var target = dir < 0 ? tr.previousElementSibling : tr.nextElementSibling;
            if (!target) return;
            if (dir < 0) list.insertBefore(tr, target); else list.insertBefore(target, tr);
          }
          list.addEventListener("click", function (e) {
            var up = e.target.closest(".sb-up"), down = e.target.closest(".sb-down");
            if (!up && !down) return;
            var tr = (up || down).closest("tr");
            move(tr, up ? -1 : 1);
          });
          document.getElementById("sidebar-form").addEventListener("submit", function () {
            var out = [];
            list.querySelectorAll("tr").forEach(function (tr) {
              if (tr.querySelector(".sb-on").checked) out.push(tr.dataset.key);
            });
            document.getElementById("sidebar-modules").value = out.join(",");
          });
        })();
        </script>';

        return $this->pageView('侧边栏管理', 'sidebar', $html);
    }

    public function saveSidebar(): void
    {
        $modules = trim((string)Request::post('modules', ''));
        $valid = array_keys(self::sidebarModules());
        $list = array_values(array_intersect(
            array_filter(array_map('trim', explode(',', $modules))),
            $valid
        ));
        Settings::set('sidebar_modules', implode(',', $list));
        \App\Core\Cache::forget('settings');
        $this->log('修改侧边栏模块', $modules);
        $this->back('侧边栏已保存');
    }

    /* ================== 邮箱配置 ================== */
    public function mail(): string
    {
        $html = AdminUI::form([
            'action' => admin_url('mail/save'),
            'data' => Settings::all(),
            'submit' => '保存邮箱配置',
            'fields' => [
                ['name' => 'smtp_host', 'label' => 'SMTP 服务器', 'placeholder' => 'smtp.qq.com'],
                ['name' => 'smtp_port', 'label' => '端口', 'type' => 'number', 'tip' => 'SSL 一般 465，TLS 一般 587'],
                ['name' => 'smtp_user', 'label' => '邮箱账号'],
                ['name' => 'smtp_pass', 'label' => '邮箱密码/授权码', 'type' => 'password'],
                ['name' => 'smtp_ssl', 'label' => '启用 SSL', 'type' => 'switch'],
                ['name' => 'smtp_from', 'label' => '发件地址', 'tip' => '留空同邮箱账号'],
                ['name' => 'smtp_from_name', 'label' => '发件人名称'],
            ],
        ]);

        $html .= '<div class="adm-panel"><div class="adm-panel-head">发送测试邮件</div><div class="adm-panel-body">'
            . AdminUI::form([
                'action' => admin_url('mail/test'),
                'submit' => '发送测试',
                'fields' => [['name' => 'to', 'label' => '收件邮箱', 'placeholder' => 'test@example.com']],
            ]) . '</div></div>';

        return $this->pageView('邮箱配置', 'mail', $html);
    }

    public function mailSave(): void
    {
        $data = [
            'smtp_host' => $this->pure('smtp_host', 120),
            'smtp_port' => Request::int('smtp_port', 465),
            'smtp_user' => $this->pure('smtp_user', 120),
            'smtp_ssl' => Request::bool('smtp_ssl') ? 1 : 0,
            'smtp_from' => $this->pure('smtp_from', 120),
            'smtp_from_name' => $this->pure('smtp_from_name', 80),
        ];
        $pass = Request::raw('smtp_pass');
        if ($pass !== '') {
            $data['smtp_pass'] = $pass;
        }
        Settings::setMany($data);
        \App\Core\Cache::forget('settings');
        $this->log('修改邮箱配置');
        $this->back('邮箱配置已保存');
    }

    public function mailTest(): void
    {
        $to = trim(strip_tags((string)Request::post('to', '')));
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $this->back('请填写正确的收件邮箱', 'error');
        }
        $mailer = new Mailer();
        if (!$mailer->configured()) {
            $this->back('请先填写并保存 SMTP 配置', 'error');
        }
        $res = $mailer->send($to, 'ChatBBS 邮件测试', Mailer::template('邮件测试', '<p>这是一封来自 ChatBBS 的测试邮件，收到即表示配置正确。</p>'));
        $ok = (bool)($res[0] ?? false);
        $this->log('发送测试邮件', $to);
        $this->back($ok ? '测试邮件已发送，请查收' : '发送失败：' . (string)($res[1] ?? '未知错误'), $ok ? 'success' : 'error');
    }

    /* ================== 通知管理 ================== */
    public function notifications(): string
    {
        $nt = Database::table('notifications');
        $p = $this->queryPage(
            'SELECT n.*, u.username FROM ' . $nt . ' n LEFT JOIN ' . Database::table('users') . ' u ON u.id = n.user_id
             ORDER BY n.id DESC',
            'SELECT COUNT(*) FROM ' . $nt,
            [],
            20,
            admin_url('notifications')
        );
        $html = '<div class="adm-panel"><div class="adm-panel-head">发布全站通知</div><div class="adm-panel-body">'
            . AdminUI::form([
                'action' => admin_url('notifications/broadcast'),
                'submit' => '发布通知',
                'fields' => [
                    ['name' => 'title', 'label' => '通知标题'],
                    ['name' => 'content', 'label' => '通知内容', 'type' => 'textarea'],
                    ['name' => 'link', 'label' => '跳转链接', 'tip' => '留空则不跳转，站内以 / 开头'],
                ],
            ]) . '</div></div>';

        $html .= AdminUI::table([
            'primary' => 'id',
            'rows' => $p['items'],
            'bulk_action' => admin_url('notifications/bulk'),
            'bulk' => [['label' => '删除', 'value' => 'delete', 'class' => 'danger', 'confirm' => '确定删除选中通知？']],
            'columns' => [
                ['key' => 'id', 'label' => 'ID', 'width' => '60px'],
                ['key' => 'username', 'label' => '接收人', 'width' => '130px'],
                ['key' => 'type', 'label' => '类型', 'width' => '90px'],
                ['key' => 'title', 'label' => '标题', 'render' => fn($r) => h(str_cut((string)$r['title'], 40))],
                ['key' => 'is_read', 'label' => '状态', 'width' => '80px', 'render' => fn($r) => (int)$r['is_read'] === 1
                    ? '<span class="adm-tag gray">已读</span>' : '<span class="adm-tag green">未读</span>'],
                ['key' => 'created_at', 'label' => '时间', 'width' => '140px', 'render' => fn($r) => format_date($r['created_at'])],
            ],
            'actions' => [
                ['label' => '删除', 'url' => admin_url('notifications/delete/{id}'), 'type' => 'post', 'class' => 'danger', 'confirm' => '确定删除？'],
            ],
        ]);
        $html .= '<div class="adm-pager">' . $p['pager'] . '</div>';
        return $this->pageView('通知管理', 'notifications', $html);
    }

    public function broadcast(): void
    {
        $title = $this->pure('title', 200);
        $content = Security::cleanHtml(Request::raw('content'));
        $link = $this->pure('link', 255);
        if ($title === '') {
            $this->back('通知标题不能为空', 'error');
        }
        $n = Notification::broadcast($title, $content, $link);
        $this->log('发布全站通知', $title . '（' . $n . ' 人）');
        $this->back('通知已发布，共 ' . $n . ' 位用户');
    }

    public function notifyDelete(int $id): void
    {
        Database::delete('notifications', 'id = ?', ['i' => $id]);
        $this->back('通知已删除');
    }

    public function notifyBulk(): void
    {
        $ids = $this->ids();
        if (!$ids) {
            $this->back('请先选择通知', 'error');
        }
        if ((string)Request::post('act', '') === 'delete') {
            $in = implode(',', array_map('intval', $ids));
            Database::query('DELETE FROM ' . Database::table('notifications') . ' WHERE id IN (' . $in . ')');
        }
        $this->back('操作完成');
    }

    /* ================== 辅助 ================== */
    public static function scanThemes(): array
    {
        $out = [];
        foreach (glob(CB_THEME_DIR . '/*', GLOB_ONLYDIR) ?: [] as $dir) {
            $slug = basename($dir);
            $out[$slug] = $slug;
        }
        return $out ?: ['default' => 'default'];
    }
}
