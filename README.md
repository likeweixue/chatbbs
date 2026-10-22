# ChatBBS 论坛系统 v1.0.0-beta.23

> 修改记录见本目录下的 `CHANGELOG.md`。

一个**零依赖、开箱即用**的现代化 PHP 社区论坛系统。原生 PHP 8 + MySQL 8 编写，不需要 Composer，上传即可安装，适合宝塔 / 1Panel / LNMP / LAMP 环境一键部署。

---

## 一、功能特性

### 用户端
- **用户系统**：注册（支持邮箱验证）、登录（记住我 / 失败锁定）、找回密码、个人主页、账号设置、头像上传
- **内容**：板块（分区 + 板块两级）、发帖、回复（楼层 / 引用）、编辑、草稿自动保存、软删除与回收站
- **互动**：点赞、帖子标记（精华 / 公告 / 活动等，自定义颜色图标）、置顶（本版 / 全局）、加精、关闭回复
- **扩展内容**：标签（含合并与热度）、说说（图片 + 评论 + 点赞）、推荐 / 避雷、@提及、Emoji、帖子标题颜色与加粗
- **社交**：好友、关注 / 粉丝、聊天、黑名单
- **成长体系**：等级（经验 + 发帖 + 签到等多维条件自动升级）、认证标识、签到（连续奖励 + 补签卡）、货币系统（发帖 / 回复 / 签到奖励、附件付费下载）
- **其它**：附件库（共享空间 / 私人空间，权限与付费下载）、全站搜索（标题全文索引 + 内容匹配）、日历、热门标签、友情链接、暗色模式、移动端适配

### 管理后台（`/admin`）
- **仪表盘**：核心数据概览、近 7 天趋势图、最新主题 / 会员、板块活跃排行、系统信息
- **内容管理**：板块、主题、回复、标签、帖子标记、说说、推荐、附件库、举报审核
- **用户管理**：用户列表（批量启用 / 禁用 / 禁言 / 删除、充值）、用户组（权限矩阵）、认证、等级、签到记录、货币流水、聊天审查
- **论坛设置**：站点设置、功能开关、货币规则、权限设置（组 × 权限勾选）、菜单、邮箱配置（含测试发信）、通知广播
- **扩展管理**：插件、主题、语言包（扫描目录 + 启用切换）
- **系统工具**：缓存管理、系统日志、操作日志、数据库备份（导出 / 下载 / 恢复 / 删除）、数据库优化、系统信息与目录权限检查

### 安全设计
- 密码 `password_hash` 哈希、记住我 Token（sha256 存储 + HttpOnly Cookie）
- 全站 CSRF Token 校验（`hash_equals` 常量时间比较）
- PDO 预处理语句，杜绝 SQL 注入
- 富文本白名单清洗（标签 / 属性 / 样式 / URL 协议过滤），输出转义
- 上传类型白名单、文件名随机化、目录隔离（shared / private / avatars / covers）
- 上传目录禁止执行 PHP（`.htaccess` + `php_flag engine off`）
- 私人空间附件走权限校验的下载入口，非本人 / 非管理员不可访问
- 后台登录验证码（可开关）、登录失败次数限制、敏感操作全部记入操作日志

---

## 二、环境要求

| 项目 | 要求 |
| --- | --- |
| PHP | **8.0 及以上**（推荐 8.1 / 8.2） |
| MySQL | **8.0 及以上**（推荐 8.0 / MariaDB 10.5+） |
| 扩展 | pdo_mysql（必需）、mbstring、json、fileinfo（推荐）、gd（验证码）、curl、openssl |
| Web | Nginx / Apache / IIS（需支持伪静态） |

---

## 三、安装步骤

### 宝塔面板
1. 创建站点与 MySQL 数据库，PHP 版本选择 **8.0+**
2. 删除站点根目录默认文件，把本项目所有文件（**含 `index.php`、`app/`、`install/`** 等）上传到站点根目录
3. 网站设置 → **伪静态**：Nginx 选 `rewrite`，填入 `nginx.example.conf` 中对应规则（或直接使用下面第五节的内容）
4. 设置网站**运行目录**为 `/`（不要设为 `/public`）
5. 浏览器访问 `http://你的域名/`，自动跳转安装向导，按 5 步完成：环境检测 → 数据库配置 → 管理员创建 → 安装完成
6. 安装完成后删除或保留 `install/` 目录均可（已生成 `data/install.lock`，再次访问会提示已安装）

### 1Panel / LNMP / LAMP
流程相同，只需保证：
- 站点根目录指向项目根目录
- 配置伪静态规则
- `data/`、`data/uploads/`、`data/cache/`、`data/logs/`、`config/` 目录可写（`chmod -R 755 data config`，属主设为 PHP-FPM 运行用户）

### 目录权限建议
```bash
chown -R www:www /网站目录
find /网站目录 -type d -exec chmod 755 {} \;
find /网站目录 -type f -exec chmod 644 {} \;
chmod -R 775 data config
```

---

## 四、后台入口

- 地址：`http://你的域名/admin`
- 账号：安装时创建的管理员账号
- 忘记密码：可在后台「用户管理 → 编辑用户 → 重置密码」，或用另一个管理员账号登录修改

---

## 五、伪静态规则

### Nginx
```nginx
location ^~ /admin/ {
    try_files $uri $uri/ /admin/index.php?$query_string;
}
location = /admin {
    rewrite ^ /admin/index.php last;
}
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

### Apache（已内置 `.htaccess`，需开启 `mod_rewrite`）
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^admin(/.*)?$ /admin/index.php [L,QSA]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ /index.php [L,QSA]
```

---

## 六、目录结构

```
chatbbs/
├── index.php              前台入口
├── admin/                 后台（入口 index.php + 模板 + 资源）
├── install/               安装向导（schema.sql / seed.sql）
├── app/
│   ├── bootstrap.php      引导：常量、自动加载、错误、会话、数据库
│   ├── routes.php         前台路由
│   ├── admin_routes.php   后台路由
│   ├── Core/              框架核心（DB / Auth / CSRF / Upload / Security / Mailer ...）
│   ├── Models/            数据模型
│   ├── Services/          业务服务（内容处理 / 等级 / 搜索 / 统计）
│   ├── Controllers/       前台控制器
│   └── Admin/             后台基类与控制器、UI 构件
├── themes/default/        默认主题（模板 + CSS + JS + 编辑器）
├── static/                公共静态资源
├── plugins/               插件目录（预留）
├── config/                配置文件（安装时生成，含 .htaccess 保护）
└── data/                  运行时数据（上传 / 缓存 / 日志 / 备份）
```

---

## 七、安全加固建议

1. **安装后**：确认 `config/` 目录不可被外部访问（Nginx 配置中已 `deny all`）
2. **上传目录**：确认 `data/uploads/` 下无法执行 PHP（已内置 `.htaccess`；Nginx 请使用示例配置）
3. **HTTPS**：生产环境建议开启 SSL，后台可配合 `session.cookie_secure`
4. **定期备份**：后台「系统工具 → 数据备份」支持导出与恢复
5. **敏感词**：后台「站点设置 → 敏感词过滤」配置后，发布内容会自动替换
6. **后台验证码**：「站点设置 → 安全设置」可开启后台登录验证码

---

## 八、常见问题

**Q：安装时提示目录不可写？**
A：把 `data/`、`config/` 目录属主改为 PHP-FPM 运行用户（宝塔一般为 `www`），权限设为 755/775。

**Q：首页能打开，但点登录/注册/板块/后台全部 404？**
A：**伪静态没生效**（Nginx/OpenResty 直接去找 `/login` 这个文件，找不到就 404）。
  - v1.0.0beta4 起内置**自动探测**：没配伪静态时站内链接会自动降级为 `?r=xxx` 形式，因此**不会再出现链接 404**，无需任何设置。
  - 若想要简洁地址：在站点 `server {}` 里加入 `nginx.example.conf` 顶部"最小化配置"的 3 段 `location`，`nginx -t && nginx -s reload`，然后到后台「论坛设置 → 站点设置 → URL 模式」选**伪静态**即可。
  - 后台入口也可始终用真实文件访问：`https://你的域名/admin/index.php`。

**Q：后台 `/admin` 报 500？**
A：查看 `data/logs/` 当天日志或 Nginx error_log。v1.0.0-beta.3 已修复 `BaseAdmin::guard()` 重复声明导致的 500。

**Q：上传的图片无法访问？**
A：检查 `data/uploads/` 是否可写，以及 Web 服务器是否允许访问该目录（不要整体 `deny /data/`）。

**Q：邮件发不出去？**
A：在「论坛设置 → 邮箱配置」填写 SMTP（QQ / 163 邮箱需使用**授权码**而非登录密码），保存后用「发送测试邮件」验证。

**Q：想换主题 / 插件？**
A：主题放入 `themes/目录名/`（含 `theme.json`），插件放入 `plugins/目录名/`（含 `plugin.json`），后台「扩展管理」中启用。

---

## 九、版本规划

- **v1.0（当前）**：完整论坛核心 + 完整后台管理
- **v1.1（规划）**：插件钩子体系、模板标签、API 开放接口、第三方登录
- **v1.2（规划）**：多语言完善、移动端主题、内容付费、勋章系统

---

ChatBBS · 用 PHP 写一个简单好用的论坛
