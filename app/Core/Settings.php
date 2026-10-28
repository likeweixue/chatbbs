<?php

declare(strict_types=1);

namespace App\Core;

/**
 * 站点设置：从 settings 表读取并缓存
 */
class Settings
{
    private static array $items = [];
    private static bool $loaded = false;

    public static array $defaults = [
        'site_name' => 'ChatBBS',
        'site_url' => '',
        'site_description' => '简洁轻量的现代化社区论坛系统',
        'site_keywords' => '论坛,社区,ChatBBS',
        'site_logo' => '',
        // Logo 展示方式：text（仅站点文字）/ image（自定义 Logo 图片）
        'logo_type' => 'text',
        'logo_text' => '',
        // 站点图标（favicon），留空使用内置复古图标
        'site_favicon' => '',
        'site_icp' => '',
        'site_footer' => '',
        'site_access' => 'all_rw',
        'site_closed' => '0',
        'closed_reason' => '站点维护中，请稍后再来。',
        'open_register' => '1',
        'email_verify' => '0',
        'open_forgot' => '1',
        'theme' => 'default',
        'language' => 'zh-CN',
        // 前台顶栏是否显示语言切换（关闭则全站固定为上方默认语言）
        'lang_switch' => '1',
        'sidebar_enable' => '1',
        // 侧边栏默认折叠：未手动切换过的访客先看到完整内容区，需要时点顶栏按钮展开
        'sidebar_default_hidden' => '1',
        'sidebar_side' => 'right',
        'sidebar_modules' => 'stats,calendar,newest_users,newest_threads,checkin,recommend,links',
        // 楼层下「楼中楼」回复默认展示条数，超出部分点击「展开更多回复」
        'reply_preview_count' => '5',
        // 聊天（单聊 / 群聊 / 附近的人）
        'chat_enable' => '1',
        'chat_poll_interval' => '5000',
        'chat_group_max' => '30',
        'lan_enable' => '1',
        'lan_discover_default' => '0',
        // 编辑器默认模式：rich（富文本）/ markdown
        'editor_default_mode' => 'rich',
        'threads_per_page' => '20',
        'posts_per_page' => '15',
        'currency_name' => '金币',
        'currency_unit' => '枚',
        // 货币符号（留空则显示"数字 + 货币名称"，如 Y12.00 或 12.00 软妹币）
        'currency_symbol' => '',
        'reward_thread' => '5',
        'reward_post' => '2',
        'reward_checkin' => '3',
        'reward_checkin_extra' => '1',
        'reward_register' => '50',
        'allow_user_tag' => '0',
        'max_tags' => '5',
        'thread_audit' => '0',
        'say_enable' => '1',
        'say_audit' => '0',
        'say_allow_image' => '1',
        'say_allow_comment' => '1',
        'say_allow_like' => '1',
        'say_on_home' => '1',
        'say_reward' => '1',
        'recommend_enable' => '1',
        'checkin_enable' => '1',
        'upload_max_size' => '5',
        'upload_allow_ext' => \App\Core\Upload::RECOMMEND_EXT,
        'sensitive_words' => '',
        // 违禁词库开关（词库内容见「内容管理 → 违禁词库」，命中即禁止发布）
        'forbidden_words_enable' => '1',
        'admin_captcha' => '0',
        'login_fail_limit' => '5',
        'register_captcha' => '1',
        // 后台独立密码：开启后，管理员账号登录成功还需再输入一次独立密码才能进后台
        'admin_pass_enable' => '0',
        'admin_pass_hash' => '',
        // 后台无操作自动退出（分钟），0 关闭；需开启超级密码才生效
        'admin_timeout' => '10',
        // 注册必须勾选同意《用户协议》：0 关闭 / 1 开启
        'register_agreement' => '0',
        // 用户协议：勾选开关开启后，注册页展示并要求勾选才能注册
        'register_agreement_title' => '《用户协议》',
        'register_agreement_text' => '',
        'smtp_host' => '',
        'smtp_port' => '465',
        'smtp_user' => '',
        'smtp_pass' => '',
        'smtp_ssl' => '1',
        'smtp_from' => '',
        'smtp_from_name' => '',
        'friend_links' => '',
        'home_seo_title' => '',
        'allow_guest_view' => '1',
        // 外观微调
        'theme_radius' => '8',
        'theme_primary' => '#4D698E',
        // URL 模式：auto（自动探测，默认，未配置伪静态也不会 404）
        //            rewrite（伪静态，需先在服务器配置 rewrite 规则）
        //            query（强制 ?r= 兼容模式）
        'url_mode' => 'auto',
        // 列表简洁模式：1 为紧凑单行（不显示正文摘要），0 为标准卡片
        'list_compact' => '0',
        // 板块页布局：list（Discuz 风格列表）/ grid（网格，一行多个）
        'forum_layout' => 'list',
        // 网格布局每行显示的板块数（2 - 6）
        'forum_grid_cols' => '4',
        // 全局通知横幅
        'notice_banner_enable' => '1',
        'notice_banner_title' => '',
        'notice_banner_text' => '',
        'notice_banner_bg' => '',
        'notice_banner_color' => '',
        'notice_banner_auto_close' => '0',
        // 主题 DIY 装饰图
        'diy_image' => '',
        'diy_position' => 'top',
        'diy_opacity' => '20',
        'diy_feather' => '60',
        // 全局颜色（顶栏 / 岛屿侧栏 / 页脚）
        'theme_header_bg' => '#ffffff',
        'theme_header_text' => '#1f2329',
        'theme_header_opacity' => '100',
        'theme_island_bg' => '#ffffff',
        'theme_island_text' => '#1f2329',
        'theme_island_opacity' => '100',
        'theme_footer_bg' => '#ffffff',
        'theme_footer_text' => '#4e5969',
        'theme_footer_opacity' => '100',
        // 网站导航页
        'nav_title' => '网站导航',
        'nav_slug' => 'nav',
        'nav_description' => '收集一些值得访问的站点，欢迎推荐。',
        // RSS 订阅：总开关 / 条数 / 默认板块 / 默认标签
        'rss_enable' => '1',
        'rss_limit' => '20',
        'rss_forum' => '0',
        'rss_tag' => '0',
        'rss_title' => '',
        'reader_enable' => '1',
        'reader_title' => 'RSS 阅读',
        'reader_limit' => '20',
        'reader_sources' => '',
        // 前台布局：classic 经典 / island 岛屿（左侧菜单 + 内容区，预览）
        'site_layout' => 'classic',
        // 岛屿布局左侧菜单：每行「类型|标题|链接」，类型 posts/forums/tags/link/divider
        'island_menu' => "posts|帖子\nforums|板块分区\ntags|标签",
        'island_sidebar_side' => 'left',
        'island_sidebar_width' => '240',
        'island_sidebar_default_hidden' => '0',
        // 板块页顶部聚合"链路"：总开关 + 四个格子类型（JSON）
        'forum_spotlight_enable' => '1',
        'forum_spotlight_items' => '[{"t":"new","title":"最新帖子"},{"t":"reply","title":"最新回复"},{"t":"essence","title":"精华帖"},{"t":"announce","title":"公告"}]',
        'forum_spotlight_limit' => '5',
        'forum_spotlight_ttl' => '60',
        // 列表筛选栏（首页 / 板块页顶部标签）：JSON 数组
        // [{t:'类型', title:'标题', url:'链接（仅 link 类型）', on:1|0}]
        // 类型：new(最新发布) default(最新回复) hot(热门) replies(最多回复) essence(精华)
        //       recommend(推荐帖) avoid(避雷帖) digest(首页推荐) mark:{id}(按帖子标记筛选) link(自定义链接)
        'rank_tabs_enable' => '0',
        'thread_tabs' => '[{"t":"new","title":"最新发布","on":1},{"t":"default","title":"最新回复","on":1},{"t":"hot","title":"热门","on":1},{"t":"replies","title":"最多回复","on":0},{"t":"essence","title":"精华","on":1},{"t":"recommend","title":"推荐","on":1},{"t":"avoid","title":"避雷","on":1}]',
        // 侧边栏"关于本站"面板（留空则不显示）
        'about_title' => 'ChatBBS',
        'about_text' => '轻量、快速、无依赖的现代化社区论坛系统，让每一次交流都简单自然。',
        'about_team' => 'WorkBuddy · likeweixue',
    ];

    public static function load(bool $force = false): void
    {
        if (self::$loaded && !$force) {
            return;
        }
        self::$items = self::$defaults;
        if (!Database::connected()) {
            self::$loaded = true;
            return;
        }
        $cache = Cache::get('settings');
        if ($cache && !$force) {
            self::$items = array_merge(self::$items, $cache);
            self::$loaded = true;
            return;
        }
        try {
            $rows = Database::fetchAll('SELECT `key`,`value` FROM ' . Database::table('settings'));
            $data = [];
            foreach ($rows as $row) {
                $data[$row['key']] = $row['value'];
            }
            self::$items = array_merge(self::$items, $data);
            Cache::set('settings', $data, 3600);
        } catch (\Throwable $e) {
            Logger::error('settings load failed: ' . $e->getMessage());
        }
        self::$loaded = true;
    }

    public static function get(?string $key = null, $default = null)
    {
        self::load();
        if ($key === null) {
            return self::$items;
        }
        return self::$items[$key] ?? $default;
    }

    public static function set(string $key, $value): void
    {
        self::load();
        self::$items[$key] = $value;
        $exists = Database::value(
            'SELECT id FROM ' . Database::table('settings') . ' WHERE `key` = ?',
            [$key]
        );
        if ($exists) {
            Database::update('settings', ['value' => (string)$value], '`key` = ?', ['k' => $key]);
        } else {
            Database::insert('settings', ['key' => $key, 'value' => (string)$value]);
        }
        Cache::forget('settings');
    }

    public static function setMany(array $data): void
    {
        foreach ($data as $k => $v) {
            self::set($k, $v);
        }
        Cache::forget('settings');
    }

    public static function all(): array
    {
        self::load();
        return self::$items;
    }

    public static function sensitiveWords(): array
    {
        $raw = (string)self::get('sensitive_words', '');
        $raw = str_replace(["\r", "\n", '，'], ["", "\n", ','], $raw);
        $words = array_filter(array_map('trim', preg_split('/[,\n]/', $raw)));
        return array_values($words);
    }
}
