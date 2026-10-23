<?php

declare(strict_types=1);

namespace App\Core;

/**
 * 版本升级后的表结构自愈
 *
 * 覆盖安装后：每次版本号变化会自动补齐新增的表 / 字段（幂等，成功与否都记录版本号），
 * 避免出现「文件更新了但表没建 → 访问整站 500」的情况。
 * 后台「系统工具 → 数据库管理 → 检查并修复表结构」仍然可以手动再跑一次。
 */
class Schema
{
    /** 当前是否需要同步的判断键（存在 settings 表） */
    private const KEY = 'schema_version';

    /**
     * 版本号不一致时自动补齐一次结构
     */
    public static function maybeSync(): void
    {
        try {
            if (!Database::connected()) {
                return;
            }
            $done = (string)Settings::get(self::KEY, '');
            if ($done === CB_VERSION) {
                return;
            }
            self::ensure();
            self::upgradeSettings();
            Settings::set(self::KEY, CB_VERSION);
            Cache::forget('settings');
        } catch (\Throwable $e) {
            Logger::error('表结构自检失败：' . $e->getMessage());
        }
    }

    /**
     * 版本升级时的一次性设置迁移
     *
     * beta23：推荐 / 避雷页面合并为首页筛选标签，把两个标签追加到已有「列表筛选」配置里。
     * beta24：默认主题主色由 #0b87fd 调整为 #4D698E（仅替换仍是旧默认值的站点）。
     */
    private static function upgradeSettings(): void
    {
        if ((int)Settings::get('migration_beta23_tabs', 0) !== 1) {
            $items = json_decode((string)Settings::get('thread_tabs', ''), true);
            if (is_array($items)) {
                $has = [];
                foreach ($items as $it) {
                    if (is_array($it)) {
                        $has[(string)($it['t'] ?? '')] = true;
                    }
                }
                $changed = false;
                if (!isset($has['recommend'])) {
                    $items[] = ['t' => 'recommend', 'title' => '推荐', 'url' => '', 'on' => 1];
                    $changed = true;
                }
                if (!isset($has['avoid'])) {
                    $items[] = ['t' => 'avoid', 'title' => '避雷', 'url' => '', 'on' => 1];
                    $changed = true;
                }
                if ($changed) {
                    Settings::set('thread_tabs', json_encode(array_values($items), JSON_UNESCAPED_UNICODE));
                }
            }
            Settings::set('migration_beta23_tabs', 1);
        }

        if ((int)Settings::get('migration_beta24_primary', 0) !== 1) {
            $primary = strtoupper(trim((string)Settings::get('theme_primary', '')));
            if ($primary === '' || $primary === '#0B87FD') {
                Settings::set('theme_primary', '#4D698E');
            }
            Settings::set('migration_beta24_primary', 1);
        }
    }

    /**
     * 幂等补齐：缺表建表、缺字段加字段
     */
    public static function ensure(): void
    {
        foreach (self::tables() as $table => $body) {
            try {
                Database::query(
                    'CREATE TABLE IF NOT EXISTS ' . Database::table($table) . ' (' . $body . ')'
                    . ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci'
                );
            } catch (\Throwable $e) {
                Logger::error('自动建表失败：' . $table . ' ' . $e->getMessage());
            }
        }
        foreach (self::columns() as [$table, $col, $def]) {
            try {
                $exists = Database::fetch(
                    'SELECT COUNT(*) AS c FROM information_schema.COLUMNS
                     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
                    [Database::table($table), $col]
                );
                if ($exists && (int)$exists['c'] > 0) {
                    continue;
                }
                Database::query('ALTER TABLE ' . Database::table($table) . ' ADD COLUMN `' . $col . '` ' . $def);
            } catch (\Throwable $e) {
                Logger::error('自动补字段失败：' . $table . '.' . $col . ' ' . $e->getMessage());
            }
        }

        // 补列完成后再补索引：部分索引依赖新增字段（如 attachments.is_image）
        foreach (self::indexes() as [$table, $name, $cols]) {
            try {
                $has = Database::fetch(
                    'SELECT COUNT(*) AS c FROM information_schema.STATISTICS
                     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?',
                    [Database::table($table), $name]
                );
                if ($has && (int)$has['c'] > 0) {
                    continue;
                }
                Database::query(
                    'ALTER TABLE ' . Database::table($table) . ' ADD INDEX `' . $name . '` (' . $cols . ')'
                );
            } catch (\Throwable $e) {
                Logger::error('自动补索引失败：' . $table . '.' . $name . ' ' . $e->getMessage());
            }
        }

    }

    /**
     * 新增表的建表语句
     *
     * @return array<string, string>
     */
    private static function tables(): array
    {
        return [
            // 违禁词库
            'forbidden_words' => '`id` int unsigned NOT NULL AUTO_INCREMENT,
                  `word` varchar(100) NOT NULL DEFAULT \'\',
                  `remark` varchar(200) NOT NULL DEFAULT \'\',
                  `hits` int unsigned NOT NULL DEFAULT 0 COMMENT \'拦截命中次数\',
                  `enabled` tinyint(1) NOT NULL DEFAULT 1,
                  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `uk_word` (`word`),
                  KEY `idx_enabled` (`enabled`)',
            // 个人主页留言板
            'guestbook' => '`id` int unsigned NOT NULL AUTO_INCREMENT,
                  `profile_user_id` int unsigned NOT NULL DEFAULT 0,
                  `user_id` int unsigned NOT NULL DEFAULT 0,
                  `content` varchar(500) NOT NULL DEFAULT \'\',
                  `status` tinyint(1) NOT NULL DEFAULT 1,
                  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  PRIMARY KEY (`id`),
                  KEY `idx_profile` (`profile_user_id`,`id`),
                  KEY `idx_user` (`user_id`)',
            // 群聊
            'chat_groups' => '`id` int unsigned NOT NULL AUTO_INCREMENT,
                  `name` varchar(60) NOT NULL DEFAULT \'\',
                  `avatar` varchar(255) NOT NULL DEFAULT \'\',
                  `owner_id` int unsigned NOT NULL DEFAULT 0,
                  `notice` varchar(255) NOT NULL DEFAULT \'\',
                  `member_count` int unsigned NOT NULL DEFAULT 1,
                  `is_open` tinyint(1) NOT NULL DEFAULT 1,
                  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                  PRIMARY KEY (`id`), KEY `idx_owner` (`owner_id`), KEY `idx_open` (`is_open`)',
            'chat_group_members' => '`id` int unsigned NOT NULL AUTO_INCREMENT,
                  `group_id` int unsigned NOT NULL DEFAULT 0,
                  `user_id` int unsigned NOT NULL DEFAULT 0,
                  `role` tinyint(1) NOT NULL DEFAULT 0,
                  `last_read_id` int unsigned NOT NULL DEFAULT 0,
                  `joined_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  PRIMARY KEY (`id`), UNIQUE KEY `uk_group_user` (`group_id`,`user_id`), KEY `idx_user` (`user_id`)',
            'chat_group_messages' => '`id` int unsigned NOT NULL AUTO_INCREMENT,
                  `group_id` int unsigned NOT NULL DEFAULT 0,
                  `user_id` int unsigned NOT NULL DEFAULT 0,
                  `content` text,
                  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  PRIMARY KEY (`id`), KEY `idx_group` (`group_id`,`id`)',
        ];
    }

    /**
     * 性能索引：[表名, 索引名, 列清单]
     *
     * 列表页 / 帖子页 / 侧栏的查询全部走这几个复合条件，单列索引在小数据量下还能凑合，
     * 数据一多就会退化成 filesort。老站升级时自动补齐（幂等）。
     *
     * @return array<int, array{0:string,1:string,2:string}>
     */
    private static function indexes(): array
    {
        return [
            ['threads', 'idx_status_forum_last', '`status`,`forum_id`,`last_reply_at`,`id`'],
            ['threads', 'idx_status_created', '`status`,`created_at`,`id`'],
            ['threads', 'idx_user_status', '`user_id`,`status`,`created_at`'],
            ['posts', 'idx_thread_status_id', '`thread_id`,`status`,`id`'],
            ['posts', 'idx_user_status', '`user_id`,`status`,`created_at`'],
            ['attachments', 'idx_user_image', '`user_id`,`is_image`,`id`'],
        ];
    }

    /**
     * 新增字段：[表名, 字段名, 定义]
     *
     * @return array<int, array{0:string,1:string,2:string}>
     */
    private static function columns(): array
    {
        return [
            // 帖子标题颜色 / 加粗（后台与前台管理员编辑页使用）
            ['threads', 'title_color', 'varchar(20) NOT NULL DEFAULT \'\' COMMENT \'标题颜色\''],
            ['threads', 'title_bold', 'tinyint(1) NOT NULL DEFAULT 0 COMMENT \'标题加粗\''],
            ['threads', 'edited_at', 'datetime DEFAULT NULL COMMENT \'最后编辑时间\''],
            ['threads', 'edited_by', 'int unsigned NOT NULL DEFAULT 0 COMMENT \'最后编辑人\''],
            ['threads', 'edited_count', 'int NOT NULL DEFAULT 0 COMMENT \'编辑次数\''],
            ['threads', 'content_md', 'mediumtext COMMENT \'Markdown 源码\''],
            ['posts', 'content_md', 'mediumtext COMMENT \'Markdown 源码\''],
            ['threads', 'content_format', 'varchar(16) NOT NULL DEFAULT \'rich\' COMMENT \'rich 富文本 / markdown\''],
            ['posts', 'content_format', 'varchar(16) NOT NULL DEFAULT \'rich\' COMMENT \'rich 富文本 / markdown\''],
            ['users', 'lan_discover', 'tinyint(1) NOT NULL DEFAULT 0 COMMENT \'允许附近的人发现我\''],
            ['users', 'guestbook_enable', 'tinyint(1) NOT NULL DEFAULT 1 COMMENT \'允许他人在个人主页留言\''],
            // 附件空间：shared 共享 / private 私人（编辑器附件库与共享空间依赖，缺列会导致「加载失败」）
            ['attachments', 'space', 'varchar(20) NOT NULL DEFAULT \'shared\' COMMENT \'shared 共享 / private 私人\''],
            ['attachments', 'is_image', 'tinyint(1) NOT NULL DEFAULT 0 COMMENT \'是否图片\''],
            // 帖子回复楼中楼
            ['posts', 'reply_to', 'int NOT NULL DEFAULT 0 COMMENT \'楼中楼：回复的目标楼层 ID\''],
            // 帖子版权许可证（license_options() 预设 key；custom 时内容存 license_custom）
            ['threads', 'license', 'varchar(20) NOT NULL DEFAULT \'\' COMMENT \'版权许可证 key，空为未声明\''],
            ['threads', 'license_custom', 'varchar(500) NOT NULL DEFAULT \'\' COMMENT \'自定义版权说明\''],
        ];
    }
}
