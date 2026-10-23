-- ChatBBS v1.0 数据库结构
-- 表前缀占位符：__PREFIX__

-- ----------------------------
-- 用户组
-- ----------------------------
CREATE TABLE IF NOT EXISTS `__PREFIX__user_groups` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL DEFAULT '',
  `slug` varchar(50) NOT NULL DEFAULT '',
  `description` varchar(255) NOT NULL DEFAULT '',
  `color` varchar(20) NOT NULL DEFAULT '#6b7280',
  `permissions` text,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- 等级
-- ----------------------------
CREATE TABLE IF NOT EXISTS `__PREFIX__user_levels` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL DEFAULT '',
  `icon` varchar(255) NOT NULL DEFAULT '',
  `min_exp` int NOT NULL DEFAULT 0,
  `need_threads` int NOT NULL DEFAULT 0,
  `need_posts` int NOT NULL DEFAULT 0,
  `need_likes` int NOT NULL DEFAULT 0,
  `need_days` int NOT NULL DEFAULT 0,
  `need_checkin` int NOT NULL DEFAULT 0,
  `auto_upgrade` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- 认证
-- ----------------------------
CREATE TABLE IF NOT EXISTS `__PREFIX__user_certifications` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL DEFAULT '',
  `icon` varchar(255) NOT NULL DEFAULT '',
  `color` varchar(20) NOT NULL DEFAULT '#4D698E',
  `description` varchar(255) NOT NULL DEFAULT '',
  `sort_order` int NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- 用户
-- ----------------------------
CREATE TABLE IF NOT EXISTS `__PREFIX__users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL DEFAULT '',
  `nickname` varchar(50) NOT NULL DEFAULT '',
  `email` varchar(120) NOT NULL DEFAULT '',
  `password` varchar(255) NOT NULL DEFAULT '',
  `avatar` varchar(255) NOT NULL DEFAULT '',
  `group_id` int unsigned NOT NULL DEFAULT 5,
  `level_id` int unsigned NOT NULL DEFAULT 1,
  `cert_id` int unsigned NOT NULL DEFAULT 0,
  `is_admin` tinyint(1) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '0待激活 1正常 2禁用',
  `email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `money` decimal(12,2) NOT NULL DEFAULT 0.00,
  `exp` int NOT NULL DEFAULT 0,
  `threads` int NOT NULL DEFAULT 0,
  `posts` int NOT NULL DEFAULT 0,
  `likes_received` int NOT NULL DEFAULT 0,
  `likes_given` int NOT NULL DEFAULT 0,
  `checkin_days` int NOT NULL DEFAULT 0,
  `checkin_continue` int NOT NULL DEFAULT 0,
  `online_time` int NOT NULL DEFAULT 0 COMMENT '在线时长(分钟)',
  `login_fail` int NOT NULL DEFAULT 0,
  `last_fail_at` datetime DEFAULT NULL,
  `signature` varchar(255) NOT NULL DEFAULT '',
  `bio` varchar(500) NOT NULL DEFAULT '',
  `theme_dark` tinyint(1) NOT NULL DEFAULT 0,
  `language` varchar(20) NOT NULL DEFAULT 'zh-CN',
  `privacy` varchar(255) NOT NULL DEFAULT '{"profile":1,"thread":1,"pm":1}',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_login_at` datetime DEFAULT NULL,
  `last_login_ip` varchar(45) NOT NULL DEFAULT '',
  `last_active_at` datetime DEFAULT NULL,
  `lan_discover` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否允许附近的人发现我',
  `guestbook_enable` tinyint(1) NOT NULL DEFAULT 1 COMMENT '允许他人在个人主页留言',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`),
  UNIQUE KEY `uk_email` (`email`),
  KEY `idx_group` (`group_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- 用户资料（扩展）
-- ----------------------------
CREATE TABLE IF NOT EXISTS `__PREFIX__user_profiles` (
  `user_id` int unsigned NOT NULL,
  `website` varchar(255) NOT NULL DEFAULT '',
  `location` varchar(100) NOT NULL DEFAULT '',
  `birthday` date DEFAULT NULL,
  `gender` tinyint(1) NOT NULL DEFAULT 0,
  `qq` varchar(30) NOT NULL DEFAULT '',
  `fields` text,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- 记住我 Token
-- ----------------------------
CREATE TABLE IF NOT EXISTS `__PREFIX__user_tokens` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `token` varchar(64) NOT NULL DEFAULT '',
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- 板块
-- ----------------------------
CREATE TABLE IF NOT EXISTS `__PREFIX__forums` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int unsigned NOT NULL DEFAULT 0,
  `name` varchar(80) NOT NULL DEFAULT '',
  `slug` varchar(80) NOT NULL DEFAULT '',
  `description` varchar(500) NOT NULL DEFAULT '',
  `icon` varchar(255) NOT NULL DEFAULT '',
  `color` varchar(20) NOT NULL DEFAULT '#4D698E',
  `cover` varchar(255) NOT NULL DEFAULT '',
  `type` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1分区 2板块',
  `threads` int NOT NULL DEFAULT 0,
  `posts` int NOT NULL DEFAULT 0,
  `today_threads` int NOT NULL DEFAULT 0,
  `last_thread_id` int NOT NULL DEFAULT 0,
  `last_thread_title` varchar(200) NOT NULL DEFAULT '',
  `last_post_at` datetime DEFAULT NULL,
  `last_post_user` varchar(50) NOT NULL DEFAULT '',
  `visible` tinyint(1) NOT NULL DEFAULT 1,
  `need_login` tinyint(1) NOT NULL DEFAULT 0,
  `allow_post` tinyint(1) NOT NULL DEFAULT 1,
  `allow_reply` tinyint(1) NOT NULL DEFAULT 1,
  `allow_anonymous` tinyint(1) NOT NULL DEFAULT 0,
  `allow_attachment` tinyint(1) NOT NULL DEFAULT 1,
  `need_level` int NOT NULL DEFAULT 0,
  `need_money` decimal(12,2) NOT NULL DEFAULT 0.00,
  `view_groups` varchar(255) NOT NULL DEFAULT '',
  `post_groups` varchar(255) NOT NULL DEFAULT '',
  `moderators` varchar(255) NOT NULL DEFAULT '',
  `sort_order` int NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_parent` (`parent_id`),
  KEY `idx_visible` (`visible`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `__PREFIX__forbidden_words` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `word` varchar(100) NOT NULL DEFAULT '',
  `remark` varchar(200) NOT NULL DEFAULT '',
  `hits` int unsigned NOT NULL DEFAULT 0 COMMENT '拦截命中次数',
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_word` (`word`),
  KEY `idx_enabled` (`enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- 主题 / 帖子
-- ----------------------------
CREATE TABLE IF NOT EXISTS `__PREFIX__threads` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `forum_id` int unsigned NOT NULL DEFAULT 0,
  `user_id` int unsigned NOT NULL DEFAULT 0,
  `username` varchar(50) NOT NULL DEFAULT '',
  `title` varchar(200) NOT NULL DEFAULT '',
  `content` mediumtext,
  `content_text` mediumtext,
  `content_md` mediumtext COMMENT 'Markdown 源码',
  `content_format` varchar(16) NOT NULL DEFAULT 'rich' COMMENT 'rich 富文本 / markdown',
  `license` varchar(20) NOT NULL DEFAULT '' COMMENT '版权许可证 key，空为未声明',
  `license_custom` varchar(500) NOT NULL DEFAULT '' COMMENT '自定义版权说明',
  `title_color` varchar(20) NOT NULL DEFAULT '',
  `title_bold` tinyint(1) NOT NULL DEFAULT 0,
  `mark_id` int NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '0待审核 1正常 2回收站',
  `is_top` tinyint(1) NOT NULL DEFAULT 0,
  `top_scope` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0本版 1全局',
  `topped_at` datetime DEFAULT NULL,
  `is_essence` tinyint(1) NOT NULL DEFAULT 0,
  `is_closed` tinyint(1) NOT NULL DEFAULT 0,
  `is_digest` tinyint(1) NOT NULL DEFAULT 0,
  `anonymous` tinyint(1) NOT NULL DEFAULT 0,
  `views` int NOT NULL DEFAULT 0,
  `replies` int NOT NULL DEFAULT 0,
  `likes` int NOT NULL DEFAULT 0,
  `recommends` int NOT NULL DEFAULT 0,
  `last_reply_at` datetime DEFAULT NULL,
  `last_reply_user_id` int NOT NULL DEFAULT 0,
  `last_reply_user` varchar(50) NOT NULL DEFAULT '',
  `attachment_fee` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `edited_at` datetime DEFAULT NULL COMMENT '最后编辑时间',
  `edited_by` int unsigned NOT NULL DEFAULT 0 COMMENT '最后编辑人',
  `edited_count` int NOT NULL DEFAULT 0 COMMENT '编辑次数',
  PRIMARY KEY (`id`),
  KEY `idx_forum` (`forum_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_top` (`is_top`),
  KEY `idx_last` (`last_reply_at`),
  KEY `idx_created` (`created_at`),
  FULLTEXT KEY `ft_title` (`title`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- 回复
-- ----------------------------
CREATE TABLE IF NOT EXISTS `__PREFIX__posts` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `thread_id` int unsigned NOT NULL DEFAULT 0,
  `user_id` int unsigned NOT NULL DEFAULT 0,
  `username` varchar(50) NOT NULL DEFAULT '',
  `content` mediumtext,
  `content_text` mediumtext,
  `content_md` mediumtext COMMENT 'Markdown 源码',
  `content_format` varchar(16) NOT NULL DEFAULT 'rich' COMMENT 'rich 富文本 / markdown',
  `floor` int NOT NULL DEFAULT 1,
  `is_first` tinyint(1) NOT NULL DEFAULT 0,
  `reply_to` int NOT NULL DEFAULT 0,
  `reply_to_user` varchar(50) NOT NULL DEFAULT '',
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `likes` int NOT NULL DEFAULT 0,
  `anonymous` tinyint(1) NOT NULL DEFAULT 0,
  `ip` varchar(45) NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_thread` (`thread_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_created` (`created_at`),
  KEY `idx_reply_to` (`reply_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- 草稿
-- ----------------------------
CREATE TABLE IF NOT EXISTS `__PREFIX__drafts` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL DEFAULT 0,
  `forum_id` int unsigned NOT NULL DEFAULT 0,
  `thread_id` int unsigned NOT NULL DEFAULT 0,
  `title` varchar(200) NOT NULL DEFAULT '',
  `content` mediumtext,
  `tags` varchar(255) NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- 点赞
-- ----------------------------
CREATE TABLE IF NOT EXISTS `__PREFIX__likes` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL DEFAULT 0,
  `target_type` varchar(20) NOT NULL DEFAULT 'thread',
  `target_id` int unsigned NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_like` (`user_id`,`target_type`,`target_id`),
  KEY `idx_target` (`target_type`,`target_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- 标签
-- ----------------------------
CREATE TABLE IF NOT EXISTS `__PREFIX__tags` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL DEFAULT '',
  `slug` varchar(80) NOT NULL DEFAULT '',
  `alias` varchar(255) NOT NULL DEFAULT '',
  `color` varchar(20) NOT NULL DEFAULT '#4D698E',
  `description` varchar(255) NOT NULL DEFAULT '',
  `icon` varchar(255) NOT NULL DEFAULT '',
  `threads` int NOT NULL DEFAULT 0,
  `hot` int NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '0待审 1通过',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `__PREFIX__thread_tags` (
  `thread_id` int unsigned NOT NULL,
  `tag_id` int unsigned NOT NULL,
  PRIMARY KEY (`thread_id`,`tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- 帖子标记
-- ----------------------------
CREATE TABLE IF NOT EXISTS `__PREFIX__thread_marks` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL DEFAULT '',
  `color` varchar(20) NOT NULL DEFAULT '#52c41a',
  `icon` varchar(50) NOT NULL DEFAULT '',
  `groups` varchar(255) NOT NULL DEFAULT '',
  `sort_order` int NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- 推荐
-- ----------------------------
CREATE TABLE IF NOT EXISTS `__PREFIX__recommendations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `thread_id` int unsigned NOT NULL DEFAULT 0,
  `user_id` int unsigned NOT NULL DEFAULT 0,
  `kind` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1推荐 -1避雷',
  `reason` varchar(255) NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_rec` (`thread_id`,`user_id`),
  KEY `idx_thread` (`thread_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- 说说
-- ----------------------------
CREATE TABLE IF NOT EXISTS `__PREFIX__says` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL DEFAULT 0,
  `content` text,
  `images` text,
  `likes` int NOT NULL DEFAULT 0,
  `comments` int NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `__PREFIX__say_comments` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `say_id` int unsigned NOT NULL DEFAULT 0,
  `user_id` int unsigned NOT NULL DEFAULT 0,
  `username` varchar(50) NOT NULL DEFAULT '',
  `content` varchar(500) NOT NULL DEFAULT '',
  `reply_to` int NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_say` (`say_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- 私信
-- ----------------------------
CREATE TABLE IF NOT EXISTS `__PREFIX__private_messages` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `from_id` int unsigned NOT NULL DEFAULT 0,
  `to_id` int unsigned NOT NULL DEFAULT 0,
  `content` text,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `from_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `to_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_from` (`from_id`),
  KEY `idx_to` (`to_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ----------------------------
-- 个人主页留言板
-- ----------------------------
CREATE TABLE IF NOT EXISTS `__PREFIX__guestbook` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `profile_user_id` int unsigned NOT NULL DEFAULT 0,
  `user_id` int unsigned NOT NULL DEFAULT 0,
  `content` varchar(500) NOT NULL DEFAULT '',
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_profile` (`profile_user_id`,`id`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- 好友 / 关注 / 黑名单
-- ----------------------------
CREATE TABLE IF NOT EXISTS `__PREFIX__friends` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL DEFAULT 0,
  `friend_id` int unsigned NOT NULL DEFAULT 0,
  `note` varchar(100) NOT NULL DEFAULT '',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '0待确认 1通过',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_friend` (`user_id`,`friend_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `__PREFIX__follows` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL DEFAULT 0,
  `follow_id` int unsigned NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_follow` (`user_id`,`follow_id`),
  KEY `idx_follow` (`follow_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `__PREFIX__blacklists` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL DEFAULT 0,
  `block_id` int unsigned NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_block` (`user_id`,`block_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- 通知
-- ----------------------------
CREATE TABLE IF NOT EXISTS `__PREFIX__notifications` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL DEFAULT 0,
  `from_id` int unsigned NOT NULL DEFAULT 0,
  `type` varchar(30) NOT NULL DEFAULT 'system',
  `title` varchar(200) NOT NULL DEFAULT '',
  `content` varchar(500) NOT NULL DEFAULT '',
  `link` varchar(255) NOT NULL DEFAULT '',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`,`is_read`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- 举报
-- ----------------------------
CREATE TABLE IF NOT EXISTS `__PREFIX__reports` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL DEFAULT 0,
  `target_type` varchar(20) NOT NULL DEFAULT 'thread',
  `target_id` int unsigned NOT NULL DEFAULT 0,
  `reason` varchar(255) NOT NULL DEFAULT '',
  `detail` text,
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0待处理 1已处理 2已驳回',
  `handle_user` int NOT NULL DEFAULT 0,
  `handle_note` varchar(255) NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `handled_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- 附件
-- ----------------------------
CREATE TABLE IF NOT EXISTS `__PREFIX__attachments` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL DEFAULT 0,
  `name` varchar(255) NOT NULL DEFAULT '',
  `path` varchar(255) NOT NULL DEFAULT '',
  `space` varchar(20) NOT NULL DEFAULT 'shared',
  `ext` varchar(20) NOT NULL DEFAULT '',
  `mime` varchar(120) NOT NULL DEFAULT '',
  `size` int unsigned NOT NULL DEFAULT 0,
  `is_image` tinyint(1) NOT NULL DEFAULT 0,
  `thread_id` int unsigned NOT NULL DEFAULT 0,
  `downloads` int NOT NULL DEFAULT 0,
  `fee` decimal(12,2) NOT NULL DEFAULT 0.00,
  `download_rule` varchar(20) NOT NULL DEFAULT 'free' COMMENT 'free login fee group',
  `download_groups` varchar(255) NOT NULL DEFAULT '',
  `download_limit` int NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_space` (`space`),
  KEY `idx_thread` (`thread_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `__PREFIX__attachment_downloads` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `attachment_id` int unsigned NOT NULL DEFAULT 0,
  `user_id` int unsigned NOT NULL DEFAULT 0,
  `paid` decimal(12,2) NOT NULL DEFAULT 0.00,
  `ip` varchar(45) NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_att` (`attachment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- 货币流水
-- ----------------------------
CREATE TABLE IF NOT EXISTS `__PREFIX__currency_logs` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL DEFAULT 0,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `balance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `type` varchar(30) NOT NULL DEFAULT 'other',
  `note` varchar(255) NOT NULL DEFAULT '',
  `related_id` int NOT NULL DEFAULT 0,
  `admin_id` int NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- 签到
-- ----------------------------
CREATE TABLE IF NOT EXISTS `__PREFIX__checkins` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL DEFAULT 0,
  `day` date NOT NULL,
  `reward` decimal(12,2) NOT NULL DEFAULT 0.00,
  `continue_days` int NOT NULL DEFAULT 0,
  `is_makeup` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_day` (`user_id`,`day`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- 补签卡
-- ----------------------------
CREATE TABLE IF NOT EXISTS `__PREFIX__checkin_cards` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL DEFAULT 0,
  `used_at` datetime DEFAULT NULL,
  `used_day` date DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- 设置
-- ----------------------------
CREATE TABLE IF NOT EXISTS `__PREFIX__settings` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(80) NOT NULL DEFAULT '',
  `value` text,
  `group` varchar(30) NOT NULL DEFAULT 'base',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- 菜单
-- ----------------------------
CREATE TABLE IF NOT EXISTS `__PREFIX__menus` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(80) NOT NULL DEFAULT '',
  `url` varchar(255) NOT NULL DEFAULT '',
  `icon` varchar(100) NOT NULL DEFAULT '',
  `target` varchar(20) NOT NULL DEFAULT '_self',
  `parent_id` int NOT NULL DEFAULT 0,
  `visible` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- 邮件配置
-- ----------------------------
CREATE TABLE IF NOT EXISTS `__PREFIX__email_configs` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL DEFAULT '',
  `host` varchar(120) NOT NULL DEFAULT '',
  `port` int NOT NULL DEFAULT 465,
  `user` varchar(120) NOT NULL DEFAULT '',
  `pass` varchar(255) NOT NULL DEFAULT '',
  `ssl` tinyint(1) NOT NULL DEFAULT 1,
  `from` varchar(120) NOT NULL DEFAULT '',
  `from_name` varchar(80) NOT NULL DEFAULT '',
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- 插件 / 主题
-- ----------------------------
CREATE TABLE IF NOT EXISTS `__PREFIX__plugins` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(80) NOT NULL DEFAULT '',
  `slug` varchar(80) NOT NULL DEFAULT '',
  `version` varchar(20) NOT NULL DEFAULT '1.0.0',
  `author` varchar(80) NOT NULL DEFAULT '',
  `description` varchar(255) NOT NULL DEFAULT '',
  `enabled` tinyint(1) NOT NULL DEFAULT 0,
  `config` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `__PREFIX__themes` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(80) NOT NULL DEFAULT '',
  `slug` varchar(80) NOT NULL DEFAULT '',
  `version` varchar(20) NOT NULL DEFAULT '1.0.0',
  `author` varchar(80) NOT NULL DEFAULT '',
  `description` varchar(255) NOT NULL DEFAULT '',
  `enabled` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- 日志
-- ----------------------------
CREATE TABLE IF NOT EXISTS `__PREFIX__admin_logs` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL DEFAULT 0,
  `action` varchar(120) NOT NULL DEFAULT '',
  `target` varchar(255) NOT NULL DEFAULT '',
  `ip` varchar(45) NOT NULL DEFAULT '',
  `ua` varchar(255) NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `__PREFIX__logs` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `level` varchar(20) NOT NULL DEFAULT 'info',
  `message` varchar(500) NOT NULL DEFAULT '',
  `ip` varchar(45) NOT NULL DEFAULT '',
  `url` varchar(255) NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `__PREFIX__search_logs` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `keyword` varchar(120) NOT NULL DEFAULT '',
  `user_id` int unsigned NOT NULL DEFAULT 0,
  `results` int NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_keyword` (`keyword`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- 聊天：群聊（局域网 / 附近的人）
-- ----------------------------
CREATE TABLE IF NOT EXISTS `__PREFIX__chat_groups` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(60) NOT NULL DEFAULT '',
  `avatar` varchar(255) NOT NULL DEFAULT '',
  `owner_id` int unsigned NOT NULL DEFAULT 0,
  `notice` varchar(255) NOT NULL DEFAULT '',
  `member_count` int unsigned NOT NULL DEFAULT 1,
  `is_open` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=公开，附近的人可直接加入',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_owner` (`owner_id`),
  KEY `idx_open` (`is_open`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `__PREFIX__chat_group_members` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `group_id` int unsigned NOT NULL DEFAULT 0,
  `user_id` int unsigned NOT NULL DEFAULT 0,
  `role` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0 成员 / 1 管理员 / 2 群主',
  `last_read_id` int unsigned NOT NULL DEFAULT 0,
  `joined_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_group_user` (`group_id`, `user_id`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `__PREFIX__chat_group_messages` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `group_id` int unsigned NOT NULL DEFAULT 0,
  `user_id` int unsigned NOT NULL DEFAULT 0,
  `content` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_group` (`group_id`, `id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
