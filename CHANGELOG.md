# ChatBBS 修改记录

> 本文件随源码一起发布，位于站点根目录 `CHANGELOG.md`，升级后可直接查看。

## v1.0.0-RC5（2026-10-28）

**通知横幅**

- 从「站点设置」移动到「通知管理」，和全站通知放在一起。
- 新增横幅背景色、字体颜色；留空时继续使用主题色渐变和白色文字。
- 自动关闭默认 `0`（永久显示，只能手动关闭）；填 `3` 则 3 秒后自动向上收起。

**帖子与统计**

- 富文本帖子支持 `<video>` / `<source>`，复制博客里的视频直链可以正常播放；没有 `controls` 时自动补上。
- 帖子详情页不再显示首页筛选标签和「最新回复 (n)」横条。
- 「最新回复」排序改为：有回复的主题优先，再按最后回复时间；避免 0 回复的新帖压过有回复的帖子。
- 修复回复总数/板块回复数把「主题首帖」也算进去的问题；升级后自动重算一次，手动重建统计也会使用新规则。

**排行榜**

- 「列表筛选」新增「显示排行榜标签」开关。
- 开启后首页/板块筛选栏末尾出现「排行榜」，下拉可选：最多回复、最多点赞、最多浏览。
- 也可以在筛选标签表里手动新增「排行榜」类型。

**帖子快捷操作**

- 「⋯ → 移动板块 / 编辑标签」不再弹浏览器 prompt，改为站内弹窗。
- 弹窗显示当前板块、当前标签；移动板块用下拉选择，标签直接编辑，确认后保存并刷新。

**后台清理**

- 删除「启用侧边栏」设置，前台始终可以显示/隐藏侧栏。
- 删除功能开关里的「每帖最多标签数」，统一在标签管理页设置。
- 货币规则表单放进标准面板，保存按钮和「连续签到奖励预览」等宽。

## v1.0.0-RC4（2026-10-28）

**全局通知横幅**

- 后台「站点设置 → 全局通知横幅」：标题、正文、启用开关、自动关闭秒数（0 表示只能手动关闭）。
- 桌面端横幅显示在顶栏下方、侧栏和内容上方；移动端显示在汉堡抽屉面板内。
- 关闭动画为向上滑动收起；标题和正文都为空时不显示横幅。

**岛屿侧栏**

- 侧栏工具下方新增全宽「发布主题」按钮。
- 当前在帖子详情页时按钮变成「回复」，点击直接滚动到回复框。
- 桌面端在左侧栏，移动端在抽屉里的侧栏中。

**移动端抽屉**

- 修复汉堡抽屉只显示顶部一小段的问题（顶栏的 `backdrop-filter` 把固定定位抽屉限制成 56px 高）。
- 抽屉改为从左侧滑出并铺满整个视口高度。
- 岛屿侧栏整体移入抽屉，主内容区不再在顶部重复显示侧栏。
- 抽屉内保留发帖 / 回复按钮和侧栏菜单；侧栏的显示/隐藏、左右切换按钮在移动端隐藏。

## v1.0.0-RC3（2026-10-26）

**DIY 装饰图**

- 新增「羽化过渡」数值（0-300px）：顶部图片在底部渐隐，底部图片在顶部渐隐，和内容自然衔接。
- 底部图片改为页脚背景层：图片贴住页面底部，页脚文字、GitHub、RSS、授权链接显示在图片上方；顶部图片仍作为顶栏下方横幅。
- 后台「主题管理 → DIY 装饰图」调整透明度、羽化和位置。

**全局颜色与色调**

- 主题管理新增「全局颜色」：顶栏背景/文字/透明度、岛屿侧栏背景/文字/透明度、页脚背景/文字/透明度。
- 顶栏外观按钮改为「衣服」图标下拉，内置明暗模式 + 默认 / 绿色护眼纸 / 黄色复古 / 黑金 / 橙黄色调，访客选择记录在浏览器。
- 默认站点图标换成长方形「左短右长」的岛屿布局图标。

**侧栏与目录**

- 岛屿侧栏菜单从主题页面移到「论坛设置 → 侧边栏管理」：上面是默认双栏模块，下面是岛屿侧栏菜单。
- 岛屿布局的帖子页/编辑页侧栏新增「目录」按钮：点击后显示当前文章的标题树，支持 h1-h6 和 Markdown `#` 标题，点击跳转/定位。
- 首页等无文章页面不显示目录按钮。

**编辑器与发帖**

- 编辑器新增 BBCode 文本模式（Markdown 右侧），支持常用 `[b] [i] [u] [s] [h1] [quote] [code] [url] [img]`，保存时转换为排版 HTML。
- 发帖页把「所属板块 / 标题 / 标签 / 标题颜色 / 附件售价 / 许可证」的提示词放进输入框或下拉框，减少零散标签。

**帖子与标签**

- 帖子列表新增「⋯」更多菜单：普通用户可点赞；管理员可快速设置标题颜色、隐藏（下线不删除）、移动板块、编辑标签、跳转后台编辑。
- 标签管理新增「用户标签权限」面板：允许/禁止普通用户自建标签，设置每帖最多标签数。

## v1.0.0-RC2（2026-10-26）

**页面与后台**

- 标签页帖子列表去掉左右两侧竖线，只保留行间分隔线。
- 维护页面只渲染维护卡片，并显式隐藏顶栏、岛屿侧栏和页脚。
- 后台页面标题统一为白色卡片面板；站点设置、板块设置等表单放入面板，保存按钮改为底部吸顶，长表单也能随时看到保存按钮。
- 后台板块编辑新增「复制权限」：选择任意源板块/分区，保存时复制访问、发帖、回复、匿名、附件、等级、货币、用户组和版主权限。
- 板块编辑的 6 个权限开关合并为一行显示，减少表单零散感。

**主题与布局**

- 主题管理拆分为「默认布局设置」和「站点布局（岛屿）」两个面板；岛屿布局启用时，默认布局面板自动变灰并禁用。
- 新增「DIY 装饰图」：支持本地上传/图床，可设置在顶部、底部或全局背景，并调整透明度。
- 岛屿布局侧栏支持拖拽分隔线调整宽度（200-360px）、双击恢复、键盘微调；宽度/左右位置/显示隐藏都会记录到浏览器。
- 顶栏和侧栏都提供显示/隐藏、左右切换按钮；后台可配置默认值。

**导航与订阅**

- 新增「网站导航」页：后台添加推荐链接，自动抓取标题、简介和 favicon；支持自定义页面路径（默认 `nav`），可在菜单管理或岛屿侧栏菜单中添加链接。
- 新增全站 RSS 2.0 输出：`/rss`，支持 `?forum=ID` / `?tag=ID` 过滤；后台可配置默认板块/标签、条数。
- 页脚 GitHub 右侧新增 RSS 按钮；页面 head 自动加入 RSS alternate 链接。
- 新增 RSS 阅读页 `/reader`：后台按「名称|RSS 地址」添加订阅源，前台聚合展示，带 30 分钟缓存。

**Wiki / 勋章 / 认证**

- 新增 Wiki 词条：列表、详情、创建、编辑；登录用户可协作编辑，内容走富文本编辑器。
- 用户管理新增「勋章管理」：勋章图片支持本地上传或图床；编辑用户时可多选佩戴勋章，个人主页展示勋章。
- 认证管理新增认证图片（本地上传/图床），用户主页和帖子作者位置展示认证小图标。
- 新增数据表：`nav_links`、`medals`、`user_medals`、`wiki_pages`；`user_certifications` 增加 `image` 字段，升级时自动创建/补列。

## v1.0.0-RC1（2026-10-25）

**全新「岛屿布局」**

- 新增默认主题下的全宽应用壳布局：左侧导航贴住顶栏、左边缘和视口底部，右侧内容区紧邻，不再是一块块带缝隙的圆角卡片。
- 左侧菜单默认包含「帖子 → 所有的帖子」「板块分区 → 全部板块 + 分区/板块树」「标签 → 热门标签」；菜单顺序、标题、类型和自定义链接均可在后台配置。
- 帖子详情页顶部增加筛选 tabs，贴住内容区顶边和左右边；首页/板块页继续使用原有的列表 tabs。
- 原「默认布局」完全保留，可在后台随时切回，不影响任何旧模板。

**侧栏交互**

- 顶栏新增「显示 / 隐藏侧栏」和「切换侧栏位置」按钮；侧栏顶部也提供同样的快捷按钮。
- 侧栏支持左侧 / 右侧切换，访客选择记录在 `localStorage`。
- 侧栏与内容区之间新增拖拽分隔线：鼠标拖拽调整宽度，范围 200 - 360px，避免侧栏和内容区等宽；双击分隔线恢复后台默认宽度。
- 后台可设置侧栏默认位置、默认宽度（200 - 360px）和是否默认隐藏；访客本地偏好优先。
- 移动端自动改为上下布局，隐藏拖拽条和左右切换，板块 / 标签菜单默认折叠。

**后台**

- 「主题管理」新增「站点布局」面板：默认布局 / 岛屿布局切换、侧栏默认位置、默认宽度、默认隐藏、侧栏菜单配置。
- 原先散落在站点设置里的布局配置集中到主题管理。
- 岛屿布局参考 [OpenIsle](https://github.com/nagisa77/OpenIsle)（MIT License）的布局方向；新增 `THIRD_PARTY_NOTICES.md`，并在相关 PHP / HTML / CSS 文件中注明来源，代码为 ChatBBS 独立实现。

**版本命名**

- 从本版开始使用 RC 命名：`v1.0.0-RC1`，下一版为 `v1.0.0-RC2`。

## v1.0.0beta26（2026-10-24）

**移动端操作面板**

- 「更多操作」底部面板改为直角（`border-radius: 0`），与页面两侧完全贴边。

**编辑器**

- 修复长文编辑时工具栏会随页面滚走的问题：含编辑器的卡片改为 `overflow: visible`，工具栏可以稳定吸附在顶栏下方。
- 文字选中后的快速格式菜单由「正文 / 标题 2-4」扩展为「正文 / 标题 1-10」；顶部工具栏的标题级别也同步扩展到 10 级。
- 标题 7-10 使用 `h6 + cb-h7...cb-h10` 类实现更小字号，保存和前台展示均保留层级样式。

**头像与上传入口**

- 头像设置新增「使用图床图片链接」表单，支持 http / https 图片地址，直接写入 `users.avatar`，不占用本站存储；切换时会清理旧的本地头像。
- 全站文件上传入口统一为带「向上箭头」图标的选择按钮；前台 `input[type=file]` 与后台 `.adm-input[type=file]` 均已适配，头像上传也使用新样式。

**板块聚合**

- 后台聚合面板新增「每格帖子数」（1-10，默认 5）和「缓存刷新间隔（秒）」（0-3600，默认 60；0 表示每次访问重新查询）。
- 聚合数据增加文件缓存，保存聚合设置时自动清除缓存。

**标题颜色**

- 标题颜色现在会显示在板块页顶部聚合、侧边栏最新帖子、个人主页最近主题等位置，不再被固定文字颜色覆盖。

**个人主页留言板**

- 个人主页新增「留言板」面板：登录用户可留言（最多 300 字），留言会通知主页主人，主人 / 留言作者 / 管理员可删除。
- 主页主人可在留言板标题旁直接开关「允许留言」；账号设置 → 隐私设置里也增加同名开关。关闭后其他用户不能留言，已有留言仍保留。
- 新增 `guestbook` 表（`profile_user_id`、`user_id`、`content`、`status`、`created_at`）和 `users.guestbook_enable` 字段，升级时自动创建 / 补列。

## v1.0.0beta25（2026-10-24）

**帖子 / 板块图标**

- 帖子列表移除左侧的 `thread.gif` 状态图标，只保留用户头像，桌面端与移动端同步生效。
- 板块页移除 `forum_new.gif` / `forum_old.gif` 板块图标，列表与网格布局同步；后台已无用的「板块默认图标」「热门帖回复数阈值」「板块页显示全站最新」设置一并移除。

**板块页顶部聚合**

- 新增四格「聚合」面板，默认显示：最新帖子、最新回复、精华帖、公告；每格显示 5 条帖子链接与时间，可点「更多」进入对应筛选。
- 原有的底部「全站最新」卡片移除，由顶部聚合替代；关闭聚合后板块页只显示板块列表。
- 后台「板块管理」顶部新增「板块页顶部聚合」面板：总开关 + 四个格子的类型选择（最新帖子 / 最新回复 / 热门 / 精华帖 / 公告 / 推荐 / 避雷 / 不显示）。
- 公告类型自动匹配名称含「公告」或标识为 `notice` 的板块。

**后台板块管理**

- 分区 / 板块层级左侧增加 18px 缩进，子板块名称明显比分区靠右，层级更容易区分。
- 「排序」列新增 ↑ 上移 / ↓ 下移按钮；在同类（同一父级）内重新编号排序，支持 AJAX 保存后刷新。

**移动端顶栏**

- 「发布主题」入口移到移动端顶栏最右侧，图标模式；首页 / 板块页卡片头部的发布按钮在移动端隐藏，筛选栏恢复整行显示。
- 帖子详情页移动端左上角的汉堡菜单替换为「返回」箭头，点击返回所属板块。
- 桌面端顶栏保持原样，卡片头部仍显示文字版「发布主题」。

**移动端个人主页**

- 资料元信息（注册时间 / 最后活跃 / 金币）压缩成一行显示，并隐藏重复的 `@用户名`。
- 个人统计由两排改为固定七列单行，缩小字号与内边距。

**移动端帖子操作**

- 帖子底部操作区只保留「点赞 / 回复 / 更多」，其余推荐、避雷、举报、分享、编辑、后台管理收进「更多」底部面板。
- 点击「更多」从底部滑出两列操作面板，带遮罩、关闭按钮和 Escape 关闭；点击任一操作后自动收起。
- 操作按钮高度压缩，避免多行换行。

**移动端列表与筛选**

- 帖子列表固定 100px 高度，标题与预览各一行省略，长预览不再撑高列表。
- 筛选标签栏移除纵向滚动和滚动条，只保留左右滑动。
- 移动端板块表隐藏最后回复列、保留紧凑帖子数，标题和简介横向铺开。

## v1.0.0beta24（2026-10-23）

**认证弹窗细节**

- 登录 / 注册弹窗圆角由 14px 收到 4px，输入框、按钮、验证码图片同步改为 4px 直角风格。

**主题主色**

- 默认主题主色由 `#0b87fd` 调整为 `#4D698E`，深色悬停色 `#3C536F`、浅色底 `#EEF2F7` 同步调整。
- 升级 beta24 时会把仍为旧默认值 `#0b87fd` 的 `theme_primary` 自动改为 `#4D698E`；如果站点已自定义为其它颜色则保持不变。
- 安装页、邮件模板、后台颜色字段默认值、初始数据默认颜色同步更新。

**移动端列表**

- 首页 / 板块页帖子列表在移动端固定为 100px 高：标题、预览各占一行并省略超长内容，隐藏标签行与「最后回复」，长预览不会再撑高列表，整页高度整齐一致。
- 移动端内容区取消左右留白：`.cb-main` 横向内边距清零，列表卡片去掉左右边框和圆角，帖子区域左右铺满屏幕。
- 移动端「发布主题」按钮移到筛选栏右上角，隐藏文字、去掉背景，只保留一支线性笔图标；板块页同款按钮同步为图标模式。
- 板块页表格在移动端隐藏最后回复列、保留紧凑的帖子数，图标 + 板块名称 + 简介横向铺开，不再被窄列挤成竖排。
- 个人资料统计改为固定四列（7 项自动排成 4 + 3 两行），不再竖排。
- `nexus` 主题同步调整移动端板块列与个人统计列数。

## v1.0.0beta23（2026-10-23）

**修复：登录 / 注册页实际没有套用认证布局**

- 根因：`BaseController::render()` 只认第三个参数 `$layout`，而 `AuthController` 一直把 `layout => layouts/gateway` 放在 `$data` 里；两者叠加后认证页被错误套进 `layouts/main`，于是表单出现在顶栏下方左上角、失去弹窗样式。
- 现在 `$data['layout']` 会正确覆盖默认布局，登录 / 注册 / 找回密码 / 重置密码都使用独立的 `layouts/gateway` 弹窗页。

**界面：登录 / 注册弹窗重做**

- 弹窗固定居中：页面底色比卡片暗一档，白色卡片带柔和投影，和背景明确分层。
- 输入框改为「浅色色块 + 内阴影」的凹陷样式，默认不使用描边；聚焦时才出现主色光环。
- 登录：用户名 / 密码两个输入框 + 记住我 + 登录按钮，底部「忘记密码？」与「没有账号？去注册」。
- 注册：用户名 / 邮箱 / 密码 / 再输入一次密码四个输入框（`confirm_password` 校验同步恢复），底部「已经有账号？去登录」；验证码、用户协议仍按后台开关显示。
- 找回密码 / 重置密码页同步改为无标签、占位提示的极简样式。

**后台：布局与暗色切换**

- 后台顶栏的 🌙 按钮换成前台的线性太阳 / 月亮 SVG，跟随 `data-adm-theme` 自动切换，不再使用 emoji。
- 修复后台表单「标签 + 输入框 + 提示文字」挤在同一行导致的右侧不齐：表单字段统一包进 `.adm-field`，标签左对齐，提示文字固定在输入框下方，所有输入框右边缘对齐。
- 下拉框默认宽度收窄到 260px，数字框 160px，颜色选择器 64px；移动端自动占满宽度。

**功能：推荐 / 避雷改为首页筛选标签**

- 删除独立的 `/recommend` 推荐页（路由保留为跳转，旧链接自动跳到 `?tab=recommend` / `?tab=avoid`）。
- 后台「列表筛选」新增「推荐帖」「避雷帖」两种类型；升级 beta23 时会把这两个标签自动追加到现有筛选栏（只执行一次），新安装默认显示。
- 首页 / 板块页的「推荐」「避雷」标签直接按帖子收到的推荐（kind=1）/ 避雷（kind=-1）记录筛选，沿用帖子列表、分页、标题颜色等能力。
- 菜单表里遗留的「推荐」入口会自动隐藏；新安装的默认菜单不再包含推荐页。

**功能：帖子标题颜色**

- 管理员编辑主题时（含编辑其他用户的帖子），在标签设置下方新增「标题颜色」：颜色选择器 + 十六进制输入 + 恢复默认。
- 颜色保存到 `threads.title_color`，首页、板块列表与帖子详情标题自动应用；普通用户编辑自己的帖子时不会看到该字段，也不会覆盖已有颜色。

**图标：去掉拟物化 emoji**

- 点赞、推荐、避雷、举报、分享、编辑、后台管理等操作按钮去掉 👍 ⭐ ⚡ 🚩 🔗 ✏️ 🛠️，改用内置 24×24 线性 SVG（新增 thumbs-up / star / flag / bolt / edit / tools / trash / upload / package / folder / inbox / tag / comment / calendar / shield 等 symbol）。
- 同步替换说说、附件库、草稿、通知、聊天、空状态、404 等页面的装饰 emoji；通知类型图标改为同一套线性图标。
- 未引入 FontAwesome 等外部图标库，不增加额外网络请求；图标仍随 sprite 按需输出。

**其它**

- 版本号更新为 `1.0.0-beta.23`；`default` 与 `nexus` 两个主题同步更新。

## v1.0.0beta22（2026-10-23）

**界面：登录 / 注册进一步对齐 Flarum 的极简风格**

- 认证弹窗移除顶部「品牌 + 羽毛笔」区，打开后只剩标题、表单与底部链接；右上角保留关闭按钮，背景改为干净的纯色（不再有渐变光斑）。
- 登录页：用户名或邮箱 / 密码 / 记住我的登录状态 / 登录按钮，结构完全对齐 Flarum 的 `LogInModal`；去掉密码框里的 👁 显示切换，页面内不再出现任何 emoji 图标。
- 注册页：精简为 Flarum 的「用户名 / 邮箱 / 密码」三项，移除昵称、确认密码输入框；验证码与用户协议仍按后台开关决定是否显示。后端同步取消 `confirm_password` 必填校验，昵称留空时继续自动使用用户名。
- 重置密码页同步去掉 👁 图标，与登录 / 注册保持一致的弹窗形态。
- 亮色与暗色模式均重新校对间距、边框与页脚底色；`default` 与 `nexus` 两个主题同步更新。

**布局：侧边栏默认隐藏**

- 新增后台开关「站点设置 → 默认隐藏侧边栏」（默认开启）。首页、板块页、个人中心等所有页面默认只显示内容区，访客点顶栏的侧栏按钮即可展开；用户的手动选择仍记忆在浏览器 `localStorage` 中。
- 服务端直接输出折叠状态，避免首屏先闪一下完整侧栏；侧边栏被后台关闭时不再执行前端布局切换逻辑。

**清理：Logo、图标与发布按钮**

- 顶栏、认证弹窗、授权证书页移除羽毛笔 Logo 图标，只保留站点文字；后台「Logo 类型」文案同步改为「仅文字（推荐，简洁）」。
- 首页、板块页、侧栏的「发布主题」按钮去掉羽毛笔图标，只保留文字。
- 「发布主题」按钮由渐变 + 内阴影改为纯色扁平样式，悬停时仅加深主色，不再使用渐变。

**新增：GitHub 入口**

- 页脚新增 GitHub 标准猫图标 + 「GitHub」文字，链接到 <https://github.com/likeweixue/chatbbs>，新窗口打开；图标已加入内置 sprite，不增加额外请求。

**署名**

- 授权证书页开发署名更新为 WorkBuddy、likeweixue（麻昌生）与 DeepSeek。
- 后台「系统信息 → 关于 ChatBBS」新增「开发与维护（beta22 起）：DeepSeek」，并注明 beta21 及之前的架构、核心功能与界面由 WorkBuddy 与 likeweixue 完成。
- 版本号更新为 `1.0.0-beta.22`。

## v1.0.0beta21（2026-10-23）

**界面：登录 / 注册改为 Flarum 风格弹窗**

- 登录、注册、找回密码、重置密码四个页面统一为 Flarum 的 Modal 弹窗形态：居中卡片、圆角 10px、柔和投影、右上角关闭按钮返回首页、背景主色光斑。
- 表单结构对齐 Flarum 的 `LogInModal` / `SignUpModal`：`Modal-header`（标题）→ `Modal-body`（`Form-group` + `FormControl` 输入框）→ `Modal-footer`（忘记密码 / 立即注册 / 返回登录）。
- 登录页：用户名或邮箱、密码（可切换明文）、记住我的登录状态、整宽主色按钮、底部"忘记密码？"与"还没有账号？立即注册"。
- 注册页：用户名（带规则提示）、昵称、邮箱、密码、确认密码、验证码（点击刷新）、用户协议勾选（可展开全文）、注册按钮；内容较高时弹窗主体内部滚动，不撑破屏幕。
- 视觉跟随主题主色（后台「外观微调 → 主题主色」），支持暗色模式、移动端全宽、`prefers-reduced-motion` 关闭动画。
- 独立样式文件 `assets/css/auth-flarum.css`（约 377 行）只在认证页加载，**不增加其它页面体积**；`default` 与 `nexus` 两个主题均已包含。
- 新增 `THIRD-PARTY-NOTICES.md`：注明登录/注册界面在视觉上参考 Flarum（MIT License，Copyright (c) Flarum Foundation），未复制其源码，并附 MIT 全文；同时补充 NexusPHP 主题的致敬说明。

## v1.0.0beta20（2026-10-21）

**新增：NexusPHP 经典主题（`themes/nexus`）**

- 复刻 NexusPHP 默认模板的表格化观感：直角边框、深蓝渐变表头、白 / 浅灰交替行、鼠标悬停淡黄高亮、12-13px 紧凑排版。
- 顶部三段式结构：Logo + 口号区 → 深蓝导航条（当前页面自动高亮）→ 用户信息条（欢迎语、退出、设置、签到、通知、消息、货币余额、管理后台入口）。
- 论坛首页改为 NexusPHP 那种「分类表头 + 五列」大表：频道（图标 + 名称 + 描述 + 今日新帖）｜主题｜帖子｜最近回复（标题 + 时间 + 作者）｜版主（空缺显示"诚征中"）；分区行可折叠，状态记在 localStorage。
- 底部保留 NexusPHP 风格的运行信息：站点版权、主题名、页面耗时。
- 沿用你原有的 `static/images/forum/` 板块图标（新帖 / 无新帖 GIF），图标风格与 NexusPHP 一致。

**主题机制说明**

- 主题目录：`themes/nexus/`（34 个模板 + `assets/css/style.css` + 复用 `app.js` / `editor.js`）。
- 启用方式：后台「扩展管理 → 主题」中点击 nexus 行的「启用」；外观微调（圆角 / 主色）对该主题同样生效，且该主题默认圆角为 **0**（直角）。
- 兼容性：复制的模板里内联用到的 `--color-*` 变量已在主题内做别名映射到 NexusPHP 配色，不会出现边框 / 文字颜色丢失。

## v1.0.0beta19（2026-10-09）

**Bug 修复（重要）**

- 修复「发帖 / 编辑直接 500，日志 `Maximum execution time of 30 seconds exceeded @ Markdown.php:169`」：  
  `renderList()` 遇到「缩进但并非列表项」的续行时会既不消费当前行也不退出循环 —— 无限空转直到 PHP 超时。  
  典型触发场景就是本站的更新日志写法：编号列表下一行用 3 个空格缩进继续说明。  
  现在缩进续行会正确并入上一个 `<li>`；同时加了递归深度上限（6 层）与步数兜底，任何异常都不会再拖垮请求。
- 顺带修掉两处同源隐患：分割线正则 `(\*\s*){3,}` 是典型的灾难性回溯写法（长行会指数级回溯），  
  改为线性的 `\*{3,}\s*$`；主循环加总步数上限。
- （修复过程中实测出）有序列表分支的捕获组取错：`\d+[.)]` 未加括号时正文是第 1 组，  
  原写法取 `$m2[2]` 会拿到空串，导致有序列表正文丢失。

**性能**

- 图标 sprite 解析结果按文件指纹缓存：此前每个页面都要读一遍图标文件再跑一次全文件正则，现在只在文件变化时解析一次。
- 侧栏统计不再重复计算：未传全站统计时会把 `Stats::forum()` 跑两遍。
- 整页输出统一补 `loading="lazy" decoding="async"`：一屏几十个头像不再同步解码，首屏更快。
- 输出压缩兜底：Web 服务器没开 gzip 时由 PHP 压缩（下载 / 验证码等二进制响应自动跳过）。
- 自动补齐 6 个复合索引（老站升级自动加）：`threads(status,forum_id,last_reply_at)`、  
  `threads(status,created_at)`、`threads(user_id,status)`、`posts(thread_id,status,id)`、  
  `posts(user_id,status)`、`attachments(user_id,is_image)`——列表页与帖子页不再退化成 filesort。

**界面**

- 图标全部换成现代线性风格（24×24 网格、圆角端点、描边跟随 `currentColor`）：  
  43 个前台图标重绘，暗色模式与主题主色自动适配；图标文件体积 17.9KB → 10.2KB。
- 后台侧栏分组图标改为内联 SVG（沿用你给的那套风格：概览 / 用户 / 用户组 / 设置 / 扩展 / 工具），  
  颜色跟随侧栏文字、亮暗色模式自动适配，同时省掉 6 个图片请求。
- 站点默认 favicon 换成圆角方块 + 对话气泡的现代样式（后台可上传替换）。
- 你原先提供的像素 / GIF 资源（`static/images/forum/` 下的板块与帖子图标）原样保留，未改动。

**代码体检**

- 新增静态体检脚本（不随包发布）：全量扫描类引用解析 —— 已确认除本次修复的 `Logger` 外没有同类漏 `use` 的隐患；  
  路由 → 控制器方法、模板文件存在性、重复函数 / 方法定义全部通过。

## v1.0.0beta18（2026-10-09）

**Bug 修复**

- 修复后台「安全设置 → 设置超级密码」保存时报错 `Class "App\Admin\Controllers\Logger" not found`：  
  `SettingController` 使用了 `Logger::admin()` 但没有 `use App\Core\Logger;`（命中即 fatal）。已补导入，  
  并排查确认其余使用 `Logger` 的文件均为同命名空间或已写全限定名，无同类隐患。
- 修复发帖 / 编辑页侧栏「目录」面板始终空白：编辑器目录那段脚本被误嵌进了「阅读页目录」的 IIFE 内部，  
  而阅读页逻辑检测到页面上没有 `.cb-post .cb-content-html` 时会提前 `return`，导致发帖页的目录与  
  帖子页「分享」按钮逻辑根本没有机会执行（阅读页不受影响，所以看起来只有发帖页没目录）。  
  已将两段逻辑拆为各自独立的作用域。

**交互修正**

- 发帖 / 编辑页侧栏的「发布主题」按钮不再跳转到新建页（会丢掉正在写的内容）：  
  现在它直接提交当前表单 —— 新建页点击即发布，编辑页按钮文案变为「保存修改」，点击即保存。

## v1.0.0beta17（2026-10-09）

**Bug 修复**

1. **修复发帖相关报错链**：`Content::highlight()` 的正则使用 `#` 作分隔符，但字符类  
   `[a-zA-Z0-9+#-]` 中又出现了未转义的 `#`，导致每次发帖 / 编辑都触发  
   `preg_replace_callback(): Unknown modifier '-'`，代码块高亮失效。已改用 `~` 分隔符。
2. **致命错误兜底**：新增 `register_shutdown_function` 兜底，内存耗尽等真正 fatal  
   不再产生「空白 HTTP ERROR 500」，会写入 `data/logs` 日志（含文件与行号）并输出可读提示页，  
   方便日后定位问题。
3. 修复保存设置时偶发 `admin log failed: Data too long for column 'target'`  
   （操作日志 target 超长被截断后再写入）。
4. 修复个人主页侧边栏 `Undefined array key "users"` 警告（侧栏统计卡片改用全站统计）。
5. 修复附件下载 `Undefined array key 2` 警告（`Attachment::canDownload()` 统一返回三元组）。

**访问控制**

1. **「访问模式」真正生效**：之前 `site_access` 设置只保存不执行。现在  
   「仅登录用户可访问」会自动把游客跳转到登录页；「仅管理员可访问」对非管理员返回 403；  
   登录 / 注册等认证页始终放行，不会出现重定向死循环。

**后台增强**

1. **安全设置独立菜单**：从「站点设置」页面拆出，位于「论坛设置 → 安全设置」。  
   集中管理：后台登录验证码、**超级密码**（进后台二次验证，可设置 / 修改 / 关闭）、  
   **后台无操作自动退出**（默认 10 分钟，超时后需重新输入超级密码，不影响前台登录态；0 关闭）、  
   注册 / 邮箱 / 上传等安全相关选项。
2. **后台顶栏搜索框**：输入关键词（如 `smtp`、`密码`、`附件`）即时列出匹配的后台菜单  
   （含中文别名与常见英文关键词），回车或点击直达对应页面。
3. **修复后台开关胶囊被拉宽**：`.adm-form-item > label` 的 150px 规则误伤了开关组件，  
   开关恢复 38px 紧凑尺寸。

**新功能**

1. **帖子版权许可证**：发帖 / 编辑时可选版权协议（CC0 / CC BY / CC BY-SA / CC BY-ND /  
   CC BY-NC / CC BY-NC-SA / 禁止转载 / 自定义），自定义内容自由填写；帖子页标题下方展示  
   「© 版权：协议名：说明」。老站点升级自动补字段（Schema 自愈）。
2. **帖子页「分享」按钮**：一键复制「标题 / 链接 / 出自@站点名」三行格式到剪贴板。
3. **发帖页侧栏目录**：发帖 / 编辑页侧边栏改为「目录」面板，从编辑器内容实时提取  
   H1–H4 标题（富文本与 Markdown 均支持），点击定位到对应标题；阅读页目录逻辑不变。

## v1.0.0beta16（2026-10-09）

**界面多语言（中 / 英语言切换）**

1. 顶栏「明暗切换」按钮右侧新增 🌐 **语言切换下拉**（自绘复古地球图标 `i-globe`），  
   默认提供 **简体中文 / English**，切换后立即生效并自动回到当前所在页面。
2. 实现 `App\Core\Lang` 多语言内核：
   - 语言优先级：**Cookie（用户在顶栏的选择）> 登录用户个人设置 > 站点默认配置 > zh-CN**；  
     登录状态下切换会同步写入 `users.language`，下次登录自动沿用；游客也能切换（Cookie 保存一年）。
   - 词条采用「**简体中文原文作为键**」的设计：中文环境零字典、零开销直接返回原文，  
     切到英文时查表翻译；**未收录的词条优雅降级为中文原文**，界面永远不会出现空白。
   - 已完整覆盖前台界面文案：模板 351 处（34 个模板文件）+ 前端脚本 160 余处  
     （编辑器工具栏、弹窗、提示、下拉、拖拽区文案等）。
3. 前端同步支持：页面注入 `window.CB_LANG` 字典与 `cbT()` 函数（中文环境为空字典，不增加体积），  
   `app.js` / `editor.js` 全部界面文案接入。
4. 新增语言包 `config/lang/en-US.php`（460+ 词条）；新增路由 `/lang/{code}`  
   （严格校验回跳地址，仅允许站内相对路径，防止开放重定向）。
5. 后台「论坛设置 → 站点设置」的语言下拉新增 **English** 选项；  
   「功能开关」新增 **顶栏语言切换** 开关（默认开启，关闭后全站固定为设定语言）。
6. `<html lang>` 属性跟随当前语言变化（利于 SEO 与无障碍）。

> 重要说明：**只翻译界面名词**，用户发表的帖子内容、简介、聊天消息一律保持原语言不变。

---

## v1.0.0beta15（2026-10-09）

1. **修复全新安装 1064 报错**：`install/schema.sql` 的 users 表里 `lan_discover` 字段被误追加到索引区（且 `idx_status` 后缺逗号），导致新装第 4 步「设置管理员」后执行建表 SQL 直接失败。已把字段挪回字段区并全量扫描 40 张表逗号完整性。
2. **修复前台面板忽窄（根治）**：`body` 为 flex column（页脚置底）+ `.cb-main` 带 auto 外边距 → 主容器退化为 fit-content 宽度，内容窄的页面（发帖简洁模式、推荐、标签、签到、聊天/私信、通知）整卡塌缩成约 500px。`.cb-main` 补 `width: 100%`，左右 padding 恢复 16px；侧栏开关关闭时主布局补 `sidebar-hidden`，不再留 280px 空列。
3. **后台独立密码（二次验证）**：后台「论坛设置 → 站点设置 → 安全设置」新增「后台独立密码」开关与密码设置。开启后管理员账号登录成功还需输入一次独立密码才能进后台（`/admin` 全部页面），独立密码错误有次数限制（8 次 / 15 分钟）。即使账号密码泄露也进不了后台。忘记密码可在数据库 `settings` 表删除 `admin_pass_hash` 重置。
4. **后台侧栏白色化 + 暗色模式**：左侧菜单深色背景改为白色（文字/悬停/分割线全部变量化），后台顶栏新增 🌙 按钮一键切换暗色模式（记忆在 localStorage，登录页/密码页同样生效、无闪白）。
5. **编辑器颜色按钮改为马克笔**：工具栏与选中浮动条的「字体颜色 / 背景颜色」由易被挤压的 `<input type="color">` 色块改为 🖊 / 🖍 马克笔按钮（带当前颜色色条，点击弹出原生取色器）。
6. **修复附件库「加载失败」**：兼容模式（`?r=`）下编辑器把 `?type=image` 直接拼在 `?r=` 后面导致路由 404。已改为自适应拼接（base 含 `?` 时用 `&`）。同时 `Schema` 自愈补列清单新增 `attachments.space / is_image`、`posts.reply_to`，老站缺列会自动补齐。

**升级方式**：覆盖上传后直接访问即可；如从 beta14 之前的版本升级，建议进后台点一次「检查并修复表结构」。

## v1.0.0beta14 hotfix1（2026-10-07）

紧急修复：覆盖安装包升级到 beta14 后站点全站 500（`Table 'xxx.cb_chat_group_messages' doesn't exist`）。

**原因**：beta14 新增了群聊功能，但聊天三张表（`cb_chat_groups` / `cb_chat_group_members` / `cb_chat_group_messages`）必须由「后台 → 系统工具 → 数据库管理 → 检查并修复表结构」创建。这一步没跑之前，任何页面都会因为要取顶栏聊天角标而查询群消息表 → 表不存在 → 整站 500（包括后台自己，形成死锁）。

本补丁做了三件事，让以后升级不再依赖手动点这一步：

1. **失败降级**：`ChatGroup` / `Lan` 的所有查询都包了一层容错，表缺失时最多让聊天不可用，**不会再打断页面渲染**。
2. **缺表自动补齐**：检测到「表不存在 / 缺字段」时自动幂等执行一次建表 + 重试原来那条查询（每个请求最多一次）。
3. **版本自检**：新增 `app/Core/Schema.php`，引导阶段比对 `schema_version` 与当前版本号，不一致就自动补齐新增表与字段（群聊三表、违禁词表、`users.lan_discover`、`threads/posts.content_md`、`content_format`、`edited_*` 等），成功后写入版本号，不会每次请求都跑。

其他：

- 后台「检查并修复表结构」改为复用 `Schema::ensure()` 这一份定义，避免两处建表语句不一致。
- 自动建表的 SQL 里原本用双引号做字符串常量（`DEFAULT ""`），在开启 `ANSI_QUOTES` 的 MySQL 上会被当标识符而报错，已统一改成单引号。

## v1.0.0beta14（2026-10-07）

本版重点：**聊天子系统（方案 A：按 IP 网段判定「附近的人」+ 建群 + 轮询）**，并修复面板宽度飘忽、目录面板不显示、发帖页偏窄等 7 处 UI 问题。

### 新增：聊天（单聊 / 群聊 / 附近的人）

- 原「私信」升级为「聊天」页 `/chat`，左窄右宽（QQ / 微信布局）：左侧会话 / 群聊 / 附近三个面板，右侧消息区
- **建群**：群名、群公告、公开 / 私有；群主 / 管理员 / 成员三级；成员列表、退群、邀请
- **公开群**：附近的人可直接加入，群列表下方展示「可加入的公开群」
- **附近的人**（方案 A）：服务端按 IP 网段判定 —— IPv4 取 `/24`、IPv6 取 `/48`；先 `LIKE '网段%'` 粗筛再精确比对，避免 `192.168.1.x` 与 `192.168.10.x` 被误判为同网段
- **「别人发现我」开关**：聊天页头部可直接切换，也在「账号设置 → 隐私」里；后台可配新用户默认值
- **轮询拉消息**：`GET /api/chat/poll?scope=pm|group&id=&after=`，间隔后台可配（默认 5000ms，下限 2000ms）；页面切到后台时自动暂停（`document.hidden`）
- 顶栏聊天角标 = 单聊未读 + 群聊未读
- 后台「内容管理 → 群聊管理」可查看 / 解散群

### 修复：面板宽度飘忽不定（根因）

`.cb-main` 原来用 `flex` + `.cb-content{flex:1}`。flex 项的最终宽度会被子元素的**固有最小宽度**顶开或压缩（长标题、表格、编辑器工具栏），所以不同页面内容区宽度不一致，`flex:1` 并不是"固定比例"。

改为 **CSS Grid**：

```css
.cb-main { display: grid; grid-template-columns: minmax(0, 1fr) 280px; }
.cb-main.sidebar-hidden { grid-template-columns: minmax(0, 1fr); }
```

`minmax(0, 1fr)` 的列宽完全由容器决定，与内容无关，各页宽度从此一致。

### 修复：其余 6 处 UI 问题

| 问题                  | 原因                                                                                                     | 处理                                                                                  |
| ------------------- | ------------------------------------------------------------------------------------------------------ | ----------------------------------------------------------------------------------- |
| 帖子页没有「文章目录」         | 侧栏目录卡片有 `if (!empty($show_toc))` 判断，但控制器**从没传过** `show_toc`，等于永远不渲染                                    | `ThreadController::view()` 传 `show_toc`；目录升级为 Halo 风格：支持 h1 - h4、层级缩进、滚动高亮、点击偏移顶栏高度 |
| 帖子页侧栏模块太多           | 用户希望只留「发布主题 + 目录」                                                                                      | 帖子页跳过其余侧栏模块                                                                         |
| 发帖页偏窄               | 默认落在简洁模式                                                                                               | 默认改为**高级模式**（宽度与工具条更稳）；编辑器初始化失败时自动退回简洁模式                                            |
| 版块折叠按钮左右留白不一致       | `.cb-forum-toggle` 是 `position:absolute; right:12px`，而 `.more`（「N 个板块」）被 `space-between` 顶到最右，**两者重叠** | header 右侧预留 48px、标题 `flex:1` + 省略号；折叠状态写入 localStorage，刷新后不跳变                       |
| 浮动工具条「格式 / 字号」点了没反应 | 浮动条上 `mousedown` 统一 `preventDefault()`，**把原生 `<select>` 的下拉也拦掉了**                                      | 对 `<select>` 放行默认行为；同时在构建浮动条时就保存选区（点 select 会让编辑区失焦，等 change 再取选区已经丢了）              |
| 顶部「发布主题」按钮多余        | —                                                                                                      | 已移除（侧栏 / 板块页入口保留）                                                                   |

### 修复：上一版埋的两个坑

1. **`databaseFix()` 把表名写死成 `threads`** —— `ALTER TABLE ' . Database::table('threads')` 用了常量而非循环变量 `$table`，导致 `posts.content_md / content_format` 被加到 **threads** 表上（posts 的 Markdown 源码实际没保存）。已改为 `$table`。
2. `install/schema.sql` 未同步 `threads/posts.content_md`、`posts.idx_reply_to` —— 已补齐（新装站点直接可用）。

### 新增配置项

| 配置                     | 默认   | 说明               |
| ---------------------- | ---- | ---------------- |
| `chat_enable`          | 1    | 群聊总开关            |
| `chat_poll_interval`   | 5000 | 轮询间隔(ms)，下限 2000 |
| `chat_group_max`       | 30   | 每人最多加入群数         |
| `lan_enable`           | 1    | 「附近的人」总开关        |
| `lan_discover_default` | 0    | 新用户默认可被发现        |

### 新增文件

| 文件                                              | 说明                                |
| ----------------------------------------------- | --------------------------------- |
| `app/Controllers/ChatController.php`            | 聊天页 / 建群 / 加群 / 退群 / 邀请 / 开关 / 轮询 |
| `app/Models/ChatGroup.php`                      | 群、成员、群消息                          |
| `app/Models/Lan.php`                            | 网段判定与「附近的人」                       |
| `app/Admin/Controllers/ChatGroupController.php` | 后台群聊管理                            |
| `themes/default/templates/chat/index.php`       | 聊天页（含轮询 JS）                       |

### 变更文件

`app/routes.php`（聊天路由）、`app/admin_routes.php`、`app/Admin/BaseAdmin.php`（菜单）、`app/Core/Settings.php`、`app/Controllers/BaseController.php`（角标含群聊）、`app/Controllers/UserController.php`、`app/Controllers/AuthController.php`、`app/Admin/Controllers/SettingController.php`、`app/Admin/Controllers/SystemController.php`、`install/schema.sql`、`themes/default/templates/layouts/main.php`、`themes/default/templates/partials/sidebar.php`、`themes/default/templates/thread/edit.php`、`themes/default/templates/forum/index.php`、`themes/default/templates/user/settings.php`、`themes/default/assets/js/app.js`、`themes/default/assets/js/editor.js`、`themes/default/assets/css/style.css`

---

## v1.0.0beta13（2026-10-07）

本版重点：**修复 beta12 的体积 / 查询回流（页面「变卡」根因）**，并新增授权证书、注册协议、违禁词库、帖子目录四项功能。

### 修复：beta12 页面变卡（实测数据）

定位方式：直接拉取线上页面量化。**根因是我上一版把 42 个图标的 sprite 全量内联进每个页面，而每页实际只用到 7 - 8 个。**


| 页面 | 总 HTML | 内联 sprite | 占比 | 实际使用图标 |
| --- | --- | --- | --- | --- |
| 首页 | 38,418 B | 17,406 B | **45.3%** | 8 个 |
| 板块页 | 45,809 B | 17,406 B | **38.0%** | 7 个 |

修复后（同一份词表实测）：

| 场景 | 修改前 | 修改后 | 节省 |
| --- | --- | --- | --- |
| 普通页面 | 17,407 B | **4,131 B** | **76.3%** |
| 无图标页面 | 17,407 B | **0 B** | 100% |
| 编辑器页面 | 17,407 B | 15,366 B | 11.7% |

具体改动：
1. **sprite 按需输出**：`cb_icon()` 登记用到的图标，`cb_sprite()` 只输出这些 `<symbol>`。为避免遗漏 JS 动态生成的图标，新增 `cb_icon_preload()` + `cb_editor_icons()`，`cb_sprite()` 改为在 `</body>` 前调用（此时所有图标已登记完毕）。
2. **补齐 `posts.reply_to` 索引**：beta12 新增的楼中楼查询 `WHERE reply_to IN (...)` 缺索引，改为索引查找（schema + 后台「检查并修复表结构」自动补）。
3. **修掉编辑器未节流的滚动监听**：`window.addEventListener('scroll', ...)` 用 capture 且非 passive，每个滚动事件都调用 `positionImageBox()` + `hideFloatBar()` → 改为 **rAF 节流 + `passive: true`**，且无选中目标时立即返回，避免滚动时反复强制重排。

### 新增：授权证书页
- 路由 `/license`，页脚新增「授权」入口
- 自动识别**当前安装的域名**作为被授权方（不是 ChatBBS 官网），并展示：站点名、程序版本、授权时间
- 声明 ChatBBS 由 **WorkBuddy 与 likeweixue（麻昌生）** 开发，官网 chatbbs.cn，当前站点获得**免费授权**
- 支持 **中文 / English** 双语切换（样式参照 Discuz! 授权证书页）

### 新增：注册协议（后台可配）
- 后台「站点设置 → 注册协议」新增：
  - **注册时需同意协议**（开关）
  - **协议名称**（如《用户协议》）
  - **协议内容**（支持换行与常见 HTML）
- 注册页展示勾选框 + 可展开的协议全文；**未勾选无法注册**（服务端强制校验，绕过前端也拦得住）
- 与「安全设置」表单分开提交作用域，避免互相覆盖

### 新增：违禁词库（区别于敏感词）
- 后台「内容管理 → 违禁词库」，独立数据表 `forbidden_words`：支持**批量添加**（换行 / 逗号分隔）、逐条**启用 / 停用 / 删除**、记录**拦截命中次数**
- 与既有「敏感词」语义区分：**敏感词**只是替换或转审核，**违禁词命中即禁止发布**
- 拦截位置：发布主题 / 编辑主题 / 回复
- **提示具体是哪个词**：服务端返回命中的词列表；编辑器同时做**实时检测**，在编辑区下方出现红色提示条，命中的词用 `<mark>` **黄色高亮**，点击可定位到正文中该词
- 新增 `GET /api/forbidden-words`（仅登录可见），实时检测与服务端共用同一份词表

### 新增：帖子目录面板
- 帖子详情页侧边栏顶部现在只显示「发布主题」+「目录」两个面板
- 目录从**楼主正文**中自动提取 h2 / h3 / h4 生成，三级缩进
- 支持：点击平滑滚动、滚动时自动高亮当前小节、**折叠 / 展开**（标题少于 2 个时自动不显示）

### 新增：编辑器「居中」
- 富文本工具栏新增**左对齐 / 居中对齐 / 右对齐**一组按钮
- 选中文字的浮动工具条同样补齐这三个按钮
- `Security::$allowedStyles` 已放行 `text-align`，居中排版能正常保存

### 调整：私信改称「聊天」
- 顶栏、聊天页、用户主页、个人设置、后台菜单等前台文案统一改为「聊天 / 发消息」
- 数据表与后台审查功能沿用原有内部命名，不影响数据结构

### 调整：iPad / 平板布局
- iPad 横屏（901 - 1200px）：侧栏固定 260px，内容区宽度稳定，避免面板忽宽忽窄
- iPad 竖屏（768 - 900px）：正文独占一行，不再被 280px 侧栏挤窄

### 变更文件清单
| 文件 | 说明 |
| --- | --- |
| `app/Core/functions.php` | 图标按需登记 `cb_icon()`、`cb_icon_preload()`、`cb_editor_icons()`、按需 `cb_sprite()` |
| `app/Models/Forbidden.php` | 新增：违禁词模型（取词 / 检测 / 命中统计 / 缓存） |
| `app/Admin/Controllers/ForbiddenWordController.php` | 新增：违禁词库管理（批量添加 / 启停 / 删除） |
| `app/Admin/BaseAdmin.php` | 菜单新增「违禁词库」，私信改称聊天 |
| `app/Admin/AdminUI.php` | `textarea` 支持 `rows` |
| `app/Admin/Controllers/SystemController.php` | 自动建违禁词表、补 `reply_to` 索引 |
| `app/Admin/Controllers/SettingController.php` | 注册协议（独立 scope） |
| `app/Core/Settings.php` | 新增 `register_agreement*`、`forbidden_words_enable` |
| `app/Controllers/HomeController.php` | 新增 `license()` 授权证书页 |
| `app/Controllers/AuthController.php` | 注册协议校验、向模板传协议内容 |
| `app/Controllers/ThreadController.php` | 违禁词拦截 `guardForbidden()`（发布 / 编辑 / 回复） |
| `app/Controllers/ApiController.php` | 新增 `forbiddenWords()` |
| `app/routes.php` | 新增 `/license`、`/api/forbidden-words` |
| `app/admin_routes.php` | 新增违禁词库路由 |
| `install/schema.sql` | 新增 `forbidden_words` 表、`posts.idx_reply_to` 索引 |
| `themes/default/templates/license.php` | 新增：授权证书页 |
| `themes/default/templates/layouts/main.php` | sprite 移到页尾按需输出、页脚授权入口、聊天文案 |
| `themes/default/templates/layouts/gateway.php` | sprite 位置同步调整 |
| `themes/default/templates/partials/sidebar.php` | 目录面板 |
| `themes/default/templates/thread/view.php` | 开启目录面板 |
| `themes/default/templates/auth/register.php` | 注册协议勾选 + 展开 |
| `themes/default/assets/js/editor.js` | 对齐三按钮、滚动监听节流、违禁词实时检测与高亮 |
| `themes/default/assets/js/app.js` | 目录生成 / 滚动高亮 / 折叠 |
| `themes/default/assets/css/style.css` | 授权页、注册协议、违禁词提示、目录面板、iPad 布局 |

### 升级注意
升级后请到后台 **「系统工具 → 数据库管理 → 检查并修复表结构」** 点一次，它会自动：
1. 创建 `forbidden_words` 违禁词表
2. 补齐 `posts.idx_reply_to` 索引

### 校验
- 106 个 PHP 文件 `php -l`：0 错误（仅 2 条既有 Deprecated）
- `app.js` / `editor.js` / `admin.js` `node --check`：通过
- 重复方法检查：0 处
- sprite 按需输出实测：普通页 17,407 B → 4,131 B（省 76.3%）
- 图标清单比对：JS 动态用到的图标在 PHP 预载清单中**无遗漏**

---

## v1.0.0beta12（2026-10-04）

本版为「复古图标 + 编辑器增强 + 移动端抽屉」合集，共处理 16 项需求 / 缺陷。

### 新增：自绘复古图标（vBulletin 像素风）
- 新增 `static/images/retro/icons.svg`：**42 枚 16×16 自绘 SVG 图标**（无第三方图标库、无图片素材依赖）
  - 顶栏：`i-sun` / `i-moon` / `i-mail` / `i-bell` / `i-search` / `i-menu` / `i-close` / `i-panel-left` / `i-panel-right` / `i-user` / `i-home` / `i-logout`
  - 编辑器：`i-h` / `i-bold` / `i-italic` / `i-underline` / `i-strike` / `i-ul` / `i-ol` / `i-quote` / `i-hr` / `i-code` / `i-fold` / `i-table` / `i-link` / `i-image` / `i-attach` / `i-emoji` / `i-undo` / `i-redo` / `i-clear` / `i-md` / `i-preview` / `i-bgimage`
  - 排版：`i-align-left` / `i-align-center` / `i-align-right` / `i-float-left` / `i-float-none` / `i-attachment-file` / `i-reply` / **`i-quill`（草稿纸 + 羽毛笔）**
- 新增 `static/images/retro/favicon.svg` 作为内置站点图标（未自定义时自动使用）
- 图标配色走 CSS 变量（`--ico-out` / `--ico-paper` / `--ico-yel` 等），**暗色模式自动反色**
- 模板统一用 `cb_icon('名称')` 输出 `<svg><use href="#i-xxx">`，sprite 由 `cb_sprite()` 内联进页面（无额外请求）

### 新增：自定义货币名称 / 符号
- 后台「站点设置 → 货币规则」新增 **货币名称**（可填「软妹币」「美刀」「银两」等任意名称）与 **货币符号**（留空则用名称后缀，如 `10.00 软妹币`）
- 新增 `currency_name()` 助手函数；`money()` 改为「符号 + 数值」或「数值 + 货币名」
- 签到、附件售价、编辑器附件定价等所有展示位统一走 `currency_name()`

### 新增：站点 Logo 与 favicon 上传
- 后台「站点设置」新增：
  - **Logo 类型**：文字 / 图片
  - **Logo 文字**（类型为文字时展示，留空显示站点名）
  - **站点 Logo 图片**（直接上传，无需手填 URL）
  - **站点 favicon**（支持 `image/*` 与 `.ico`，未设置时用内置复古 favicon）
- `AdminUI::field()` 新增 `image` 类型（文本框 + 文件选择 + 当前图预览），含 `image` / `file` 字段的表单自动加 `enctype="multipart/form-data"`
- 登录 / 注册页（gateway 布局）同步支持

### 新增：编辑器 Markdown 双模式
- 编辑器工具栏新增模式切换条：**「富文本 / Markdown」**，默认取后台配置 `editor_default_mode`（默认富文本）
- 自研 Markdown 解析器 `app/Services/Markdown.php`（**零 Composer**）：
  - 支持围栏代码块、ATX 标题、分割线、引用、有序/无序列表（含缩进嵌套、类型自动分离）、表格（按首行分隔符自动对齐）、行内代码 / 图片 / 链接 / 加粗 / 斜体 / 删除线 / 裸链自动链接
  - **先转义再生成标签**，天然免疫 XSS
- 前后端共用同一套解析器：新增 `POST /api/markdown`，编辑器「切回富文本」与「预览」都走服务端渲染，保证编辑态与最终展示完全一致
- 内容入库同时保存 Markdown 源码（`content_md`）与格式（`content_format`），再次编辑时自动还原到对应模式

### 新增：编辑器选中浮动工具条（mini 快速设置）
- 在编辑区选中文字后自动浮出工具条：**标题级别、字号、加粗 / 斜体 / 下划线 / 删除线、字体颜色、背景色、清除格式、插入链接**
- 鼠标移开自动隐藏，点击编辑区外收起

### 新增：图片调整框（点击图片出现）
- 点击正文图片后显示虚线调整框：
  - **四角拖拽缩放**（等比例，实时写入 `width`）
  - 快捷比例 **25% / 50% / 75% / 100%**
  - **对齐**：左 / 中 / 右
  - **环绕**：文字环绕 / 独占一行
  - **作为背景图**：把图片转为容器 `background-image`，文字浮于其上（`Security` 已放行相关样式）
  - **删除图片**

### 新增：楼中楼回复折叠
- 每层楼下的「回复的回复」默认只显示 N 条，超出部分折叠，底部出现 **「展开其余 N 条回复」** 按钮（可再点收起）
- 后台「站点设置 → 功能开关」新增 **楼层回复默认展示条数**（`reply_preview_count`，默认 5）
- 楼层头部新增「回复」按钮，点击后自动定位到回复框并显示「正在回复 #X 楼」提示（可取消）

### 修复：移动端菜单（Flarum 风格抽屉）
- 原实现点击汉堡后 `<nav class="cb-nav">` 是**透明的**，且关闭按钮在**左侧**
- 改为 Flarum 风格**左侧抽屉**：
  - 汉堡按钮移到顶栏最左（`order:1`），Logo 居中
  - 抽屉 `position:fixed; left:0; transform:translateX(-102%)` 滑出，带不透明底色 + 阴影
  - 抽屉顶部为 `.cb-drawer__head`（站点名 + **右上角关闭按钮**）
  - 新增半透明遮罩，点击遮罩 / 关闭按钮 / Esc / 视口宽度 > 860px 均自动收起，打开时锁定 body 滚动
  - 遮罩 `z-index` 低于顶栏，保证汉堡按钮始终可点

### 修复：附件库与上传
1. **附件库「加载失败」**：前端请求 `api/my-images`，而路由只有 `api/my-files` → 404
   - 新增 `GET /api/my-images`（内部复用 `myFiles()`），同时前端改请求 `api/my-files?type=image`，错误文案改为「加载失败，请稍后重试」
2. **gz 等压缩包不能上传**：默认白名单缺少压缩包后缀
   - 新增 `Upload::RECOMMEND_EXT` 常量，补齐 `gz / tgz / tar / bz2 / xz / 7z / rar` 等；移除易混淆的 `conf / ini`
3. **zip 被当成图片渲染成裂图**：上传返回缺少文件类型信息，前端一律插 `<img>`
   - `uploadImage()` 返回新增 `size / ext / is_image / download`，前端据此插 `<img>` 或**附件下载卡片** `<a class="cb-attachment">`
   - `app.js` 新增 `fixAttachmentImages()` 兜底：历史数据里 `src` 命中非图片后缀的 `<img>` 就地替换为下载卡片

### 修复：普通表单直出 JSON
- 「全部已读」等操作返回裸 `{"ok":true,...}`。
- 根因：`BaseController::success()` / `fail()` 只回 JSON，而这些入口是普通 `<form method="post">`
- 统一在 `success()` / `fail()` 内按 `Request::isAjax()` 分流：**非 Ajax → 写入 flash 并回跳上一页**
- 同类问题一并修复：`ThreadController::delete()` / `deletePost()` 非 Ajax 时定向跳转，避免停留在已删除的详情页

### 修复：其它
- **工具栏随内容滚动**：`.cb-editor__toolbar` 改为 `position:sticky; top:var(--header-height)` 吸顶，横向可滚动（不再换行挤压）
- **页脚跑到屏幕中间**：`body.cb-body` 改为 `flex` 纵向布局 + `min-height:100vh`，`.cb-main{flex:1 0 auto}`，页脚常驻底部
- **图片排版样式被清洗**：`Security` 新增 30+ 条 CSS 属性白名单 `$allowedStyles`（`float` / `background-image` / `object-fit` / `z-index` / `position` 等），并新增 `cleanBackgroundImage()` 只放行 http(s) 与站内相对地址（剔除 `javascript:`）；`img` 允许 `style`、`a` 允许 `class` / `data-attachment`

### 变更文件清单
| 文件 | 说明 |
| --- | --- |
| `static/images/retro/icons.svg` | 新增：42 枚自绘复古图标 sprite |
| `static/images/retro/favicon.svg` | 新增：内置复古 favicon |
| `app/Services/Markdown.php` | 新增：自研 Markdown 解析器 |
| `app/Core/functions.php` | 新增 `cb_sprite()` / `cb_icon()` / `currency_name()`，`money()` 支持符号 |
| `app/Core/Settings.php` | 新增 `logo_type` / `logo_text` / `site_favicon` / `currency_symbol` / `reply_preview_count` / `editor_default_mode` |
| `app/Core/Upload.php` | 新增 `RECOMMEND_EXT` 常量（补齐压缩包后缀） |
| `app/Core/Security.php` | 样式白名单 `$allowedStyles`、`cleanBackgroundImage()`、标签属性白名单扩充 |
| `app/Services/Content.php` | `process()` 支持 `$format` 参数，返回 Markdown 源码 |
| `app/Controllers/ThreadController.php` | 楼中楼查询、`mdFields()` / `readContent()`、删除后跳转 |
| `app/Controllers/ApiController.php` | 新增 `myImages()` / `markdownPreview()`，上传返回扩充 |
| `app/Controllers/BaseController.php` | `success()` / `fail()` 非 Ajax 分流 |
| `app/routes.php` | 新增 `/api/my-images`、`/api/markdown` |
| `app/Admin/Controllers/SettingController.php` | Logo / favicon 上传、货币符号、楼中楼层数、编辑器默认模式 |
| `app/Admin/Controllers/SystemController.php` | 补齐 `content_md` / `content_format` 字段与上传后缀 |
| `app/Admin/AdminUI.php` | `image` 字段类型、自动 `enctype` |
| `app/Models/Checkin.php` | 货币名统一走 `currency_name()` |
| `themes/default/templates/layouts/main.php` | 头部重写：抽屉、复古图标、Logo 双模式、favicon |
| `themes/default/templates/layouts/gateway.php` | 同上（登录 / 注册页） |
| `themes/default/templates/partials/sidebar.php` | 「发布主题」按钮复古化 |
| `themes/default/templates/home/index.php` | 同上 |
| `themes/default/templates/forum/view.php` | 同上 |
| `themes/default/templates/thread/edit.php` | 编辑器双模式、`content_format` |
| `themes/default/templates/thread/view.php` | 楼中楼渲染、回复定位、模式字段 |
| `themes/default/assets/js/editor.js` | 重写：双模式、浮动工具条、图片调整框、复古图标、附件分支 |
| `themes/default/assets/js/app.js` | 抽屉交互、楼中楼展开、回复定位、附件图兜底、主题图标 |
| `themes/default/assets/css/style.css` | 抽屉、页脚置底、吸顶工具栏、浮动条、图片框、楼中楼、附件卡片 |
| `install/schema.sql` | 新增 `content_md` / `content_format` |

### 升级注意
升级完成后，请到后台 **「系统工具 → 数据库管理 → 检查并修复表结构」** 点一次：
1. 补齐 `threads.content_md` / `posts.content_md` / `content_format` 四个字段（不点也不会报错，只是 Markdown 模式无法保留源码，再次编辑会退化为富文本）
2. 自动补齐上传后缀白名单（老站点默认不含 `gz / tgz / tar / xz` 等压缩包）


### 校验
- 103 个 PHP 文件 `php -l`：0 语法错误
- `Security::cleanHtml()` / `cleanStyle()` 用例：`float`、背景图、`z-index` 保留；`javascript:` 与 `position:fixed` 被剔除
- Markdown 解析器用例（含 XSS、有序/无序列表分离、表格对齐）：通过
- `icons.svg` XML 合法性：42 个 symbol 校验通过
- `app.js` / `editor.js` `node --check`：通过

---

## v1.0.0beta11（2026-10-02）

本版为「vBulletin / Discuz 复古风格」主题改造合集。

### 新增：复古图标资源
- 内置图标目录 `static/images/forum/`：
  - `forum_new.gif` / `forum_old.gif`（板块有新帖 / 无新帖，29×30）
  - `thread_new.gif` / `thread.gif` / `thread_hot_new.gif` / `thread_hot.gif` / `thread_lock.gif`（帖子状态，20×20）
- 帖子列表与板块列表的状态图标全部改为这套复古 gif
  - 板块：后台设置了自定义图标则用自定义的，否则按「今日是否有新帖」自动切换 new / old
  - 帖子：按「24 小时内 = 新」+「回复数达阈值 = 热门」+「已关闭 = 锁」自动选择图标
- 新增 `thread_hot_replies` 配置（热门阈值，默认 20）
- 后台「站点设置」可自定义板块默认图标地址（留空用内置）

### 新增：板块页 Discuz 风格
- 板块总览（`/forums`）改为 Discuz 风格的分区表格：
  - 分区标题栏（可点击 −/+ **折叠 / 展开**）
  - 每个板块一行：**图标 | 名称 + 简介 + 版主 | 主题 / 回复（含今日新增） | 最后发表（标题 + 时间 + 作者）**
- **两种布局可切换**（后台「站点设置 → 板块页布局」）：
  - `list`：Discuz 风格列表（默认）
  - `grid`：网格紧凑排列，**每行板块数可配置**（2 - 6，默认 4）
- 后台新增：`forum_layout`、`forum_grid_cols`、`show_latest_card`（板块页底部"全站最新"卡片开关）、`thread_hot_replies`、`forum_icon_new/old`

### 新增：板块无限层级 + 批量移动
- `Forum::tree()` 重写为**递归实现**：分区下可建板块，板块下也能再建分区或板块，层级不限
  - 前台按层级缩进渲染子板块（最多缩进 3 级）
- 新增 `Forum::options()`（带缩进的父级下拉选项）与 `Forum::isDescendant()`（防止把父级移到自己的子级下）
- 后台板块编辑：「上级」现在可选择**任意**分区或板块（自动排除自身与自己的后代）
- 后台板块列表新增**批量移动**：勾选板块 → 选择目标 → 点「移动到 →」
- 批量删除时自动跳过仍有下级的板块，避免产生孤儿节点

### 新增：帖子编辑痕迹
- 新增字段 `threads.edited_at` / `edited_by` / `edited_count`（已写入 `install/schema.sql`）
- 用户在前台编辑帖子后，帖子正文**上方居中**显示「XXX 编辑于 2026-10-02 14:30」，未编辑过则不显示
- 后台「主题管理 → 编辑」新增**发布时间**与**最后编辑时间**输入框，可直接修正时间线
- 后台「数据库管理」新增**「检查并修复表结构」**按钮：升级后一键补齐新增字段（幂等，不影响已有数据）
- 编辑写入带容错：未修复表结构时自动回退，不会导致编辑 500

### 优化
- **后台菜单图标**换成 vBulletin 复古像素风（自绘 16×16 SVG，位于 `static/images/admin/`）：仪表盘、内容、用户、设置、扩展、系统、升级、列表筛选、侧边栏
- **后台开关按钮**宽度由 44×24 收窄为 38×22（用户反馈"有点宽"）
- **后台表单布局**：编辑器 / 多行文本 / 多选项改为**独占整行**（标签在上、控件在下），编辑器工具栏强制一排显示，正文区域不再被挤窄

### 变更文件清单
| 文件 | 说明 |
| --- | --- |
| `static/images/forum/*.gif` | 新增复古板块/帖子图标 |
| `static/images/admin/*.svg` | 新增后台像素风图标 |
| `app/Models/Forum.php` | `tree()` 递归、`options()`、`isDescendant()` |
| `app/Controllers/ThreadController.php` | 编辑痕迹记录、编辑人昵称、容错 |
| `app/Admin/Controllers/ContentController.php` | 无限层级父级、批量移动、时间字段 |
| `app/Admin/Controllers/SystemController.php` | `databaseFix()` 表结构修复 |
| `app/Admin/BaseAdmin.php` | 菜单 `icon_key`、`groupIcon()` |
| `app/Admin/AdminUI.php` | 表单独占行、`bulk_extra` |
| `app/Core/Settings.php` | 新增板块布局等相关配置 |
| `app/admin_routes.php` | 新增 `/database/fix` |
| `install/schema.sql` | 新增 `edited_at/edited_by/edited_count` |
| `themes/default/templates/forum/index.php` | Discuz 风格重写 + 递归渲染 |
| `themes/default/templates/partials/thread_list.php` | 复古状态图标 |
| `themes/default/templates/thread/view.php` | 编辑痕迹提示 |
| `themes/default/assets/css/style.css` | 板块表格 / 网格 / 子板块缩进 / 编辑提示 |
| `admin/assets/css/admin.css` | 开关收窄、图标、编辑器独占行 |
| `admin/templates/layout.php` | 菜单改用图片图标 |

### 升级注意
升级完成后，请到后台 **「系统工具 → 数据库管理 → 检查并修复表结构」** 点一次，补齐 `edited_at / edited_by / edited_count` 字段（不点也不会报错，只是编辑痕迹功能不生效）。

### 校验
- 102 个 PHP 文件语法解析：0 错误
- 重复方法检查：0 处
- `app.js` / `editor.js` JS 语法解析：通过

---

## v1.0.0beta10（2026-10-02）

### 修复
1. **编辑器插入图片 / 代码 / 折叠 / 表格 / 附件等总是跑到正文最前面**
   - 现象：写完一段文字、光标停在末尾，点工具栏插入后内容却出现在开头
   - 根因（两个叠加问题）：
     - 编辑器构建时会把原始的 `data-editor` 占位 div（`this.el`）隐藏，另建真正的可编辑区 `this.area`；而工具栏按钮里写的是 `self.el.focus()` —— 对**已隐藏的元素**调用 focus 无效，编辑区从未获得焦点
     - 点击按钮 / 打开弹窗会让编辑区失焦，选区（Selection）丢失，`document.execCommand('insertHTML')` 在没有选区时默认插到文档开头
   - 处理：
     - 新增 `Editor.prototype.saveRange()` / `restoreRange()`：保存并恢复光标与选区；没有有效选区时回退到**正文末尾**（而不是开头）
     - 工具栏按钮、字号/标题/颜色等控件改为 `mousedown` 时 `preventDefault()` 阻止焦点转移，并先 `saveRange()` 记住位置
     - `insertHTML()` 改用 `restoreRange()`，插入后把光标移到新内容之后，便于连续插入
     - 编辑区持续监听 `keyup / mouseup / touchend / focus` 记录光标位置
   - 影响：所有通过工具栏插入的内容（加粗、代码、折叠、表格、链接、图片、附件、表情、上传）现在都插在光标所在位置

### 变更文件清单
| 文件 | 说明 |
| --- | --- |
| `themes/default/assets/js/editor.js` | 选区保存/恢复、工具栏 `mousedown` 处理、`insertHTML` 修正、`savedRange` 初始化 |
| `app/bootstrap.php` | 版本号 → `1.0.0-beta.10` |

### 校验
- 102 个 PHP 文件语法解析：0 错误
- `app.js` / `editor.js` JS 语法解析：通过

---

## v1.0.0beta9（2026-10-02）

### 修复
1. **回复成功后页面直接显示 JSON、且不刷新**
   - 原因：回复接口一律返回 JSON，而回复表单是普通 POST 提交
   - 处理：`ThreadController::reply()` 判断请求类型 —— Ajax 返回 JSON；普通表单改为「提示 + 跳回帖子页并定位到新回复」。说说发布（`SayController::publish()`）同样处理
2. **后台「主题管理」500：`Cannot access offset of type string on string`**
   - 原因：`threads()` 方法里把表名变量 `$t`（字符串）当成查询结果数组用了（`$t['items']`）
   - 处理：改回查询结果变量 `$p`；并新增脚本全量扫描"表名变量被当数组使用"的同类隐患（0 处）
3. **多编辑器实例互相干扰**
   - 编辑器实例除 `window.CBEditor` 外，同时挂到元素 `el.__cbEditor`；草稿保存与正文空校验改为取"当前未被 disabled 的 content 字段"，简洁/高级模式切换后行为正确

### 新增
1. **后台「列表筛选」管理**（论坛设置 → 列表筛选）
   - 控制首页 / 板块页顶部标签：**勾选显示 + ↑↓ 排序 + 自定义标题 + 新增/删除**
   - 内置类型：最新发布、最新回复、热门、最多回复、精华帖、首页推荐、自定义链接
   - **帖子标记类型**：后台「帖子标记」里创建「公告 / 归档 / 过时」等标记后，这里可直接把它作为一个筛选分类（如"公告""归档""过时教程"）
   - 自定义链接：可指向任意站内板块或外链（如"教程分区"）
   - 兼容旧的 `?order=xxx` 地址
2. **编辑器「插入附件」按钮**（在插入图片右侧）
   - 打开附件库窗口：可按 **共享空间 / 私人空间 / 全部** 过滤，可按「仅图片」过滤
   - 上传：**任意格式**（受后台"允许的后缀"与"上传大小"设置约束），可拖拽，可选择上传到共享或私人空间
   - 图片附件插入为图片，其它附件插入为带 📎 的下载链接
   - 新增接口 `GET /api/my-files`；上传接口支持 `space` 参数
3. **说说发布框：简洁 / 高级双模式**
   - 默认简洁输入框，右上角「高级模式」切到富文本；删除了原来多余的 3 个图片 file input（图片改由编辑器插入）
   - 后端改为 `Content::process()`：纯文本自动换行，富文本正常解析
4. **发帖正文：简洁 / 高级双模式**（与回复一致）
   - 新帖默认简洁模式；编辑已有帖子时因内容是 HTML，默认进入高级模式
5. **后台「在线升级」**（系统工具 → 在线升级）
   - 上传 `.zip` 升级包 → **校验包结构**（必须含 `app/bootstrap.php`）并读取包内版本号 → 展示「包内版本 / 当前版本 / 根目录 / 文件数 / 大小」
   - 确认页强制提示备份，可勾选「升级前自动备份数据库」（自动落到 `data/backups/auto-upgrade-*.sql`）
   - 执行升级时**自动跳过 `config/`、`data/`、`install/`**，更新其余文件，完成后自动清缓存
   - 无 ZipArchive 扩展时给出手动升级步骤说明
6. **系统信息页「关于 ChatBBS」面板**：版本、技术栈、设计与开发（WorkBuddy）、产品与测试（likeweixue）、开源协议说明

### 优化
- **登录 / 注册页**：独立全屏居中卡片（无导航、无侧边栏，本就使用 gateway 布局），输入框加图标（账号 / 邮箱 / 昵称 / 密码），带"欢迎回来""加入 XXX"副标题，主按钮加粗全宽，移动端转为整屏白底表单

### 变更文件清单
| 文件 | 说明 |
| --- | --- |
| `app/Admin/Controllers/ContentController.php` | 修复主题管理变量误用 |
| `app/Controllers/ThreadController.php`、`SayController.php` | 非 Ajax 提交改提示 + 跳转 |
| `app/Controllers/BaseController.php` | 新增 `listTabs()` / `resolveTab()` |
| `app/Core/Settings.php` | 新增 `thread_tabs` 配置 |
| `app/Models/Thread.php` | 支持 `mark_id` / `digest` 筛选 |
| `app/Controllers/HomeController.php`、`ForumController.php` | 按配置解析筛选 |
| `app/Controllers/ApiController.php` | 新增 `myFiles()`；上传支持 space |
| `app/routes.php` | 新增 `/api/my-files` |
| `app/Admin/Controllers/SettingController.php` | 列表筛选管理页与保存 |
| `app/Admin/Controllers/SystemController.php` | 在线升级（校验 / 应用 / 自动备份）+ 关于面板 |
| `app/Admin/BaseAdmin.php` | 菜单新增列表筛选、在线升级；`teamInfo()` |
| `app/admin_routes.php` | 新增 list-tabs / upgrade 相关路由 |
| `themes/default/templates/home/index.php`、`forum/view.php` | 按配置渲染筛选栏 |
| `themes/default/templates/thread/edit.php` | 正文简洁/高级双模式 |
| `themes/default/templates/say/index.php` | 说说简洁/高级双模式、去掉 file input |
| `themes/default/templates/auth/login.php`、`register.php` | 表单图标与副标题 |
| `themes/default/assets/js/editor.js` | 附件弹窗、`__cbEditor`、upload 支持 space |
| `themes/default/assets/js/app.js` | 生效 content 取值、草稿与校验修正 |
| `themes/default/assets/css/style.css` | 附件库、gateway 表单、输入框图标样式 |

### 校验
- 102 个 PHP 文件语法解析：0 错误
- `app.js` / `editor.js` JS 语法解析：通过
- 「表名变量被当数组使用」隐患扫描：0 处

---

## v1.0.0beta8（2026-10-01）

本版为体验优化合集（帖子列表、编辑器、侧边栏、移动端）。

### 新增
1. **帖子列表简洁模式**
   - 后台「论坛设置 → 站点设置 → 帖子列表简洁模式」开关（默认关闭）
   - 开启后首页 / 板块页列表不再显示正文摘要，改为紧凑单行：头像 + 标题 + 作者/时间/最后回复人 + 回复数，行高由约 110px 降至 62px
   - 新增 `list_compact` 配置项；移动端简洁模式自动隐藏头像
2. **后台「侧边栏管理」**（论坛设置 → 侧边栏管理）
   - 8 个模块（关于本站 / 论坛统计 / 每日签到 / 日历 / 最新会员 / 最新帖子 / 热门标签 / 友情链接）
   - 支持勾选显示 + ↑↓ 调整上下顺序，前台按配置顺序渲染
3. **侧边栏"关于本站"面板**
   - 位于"发帖"按钮下方，展示站点标题、介绍文案、开发团队，以及主题/回复/会员/今日四项数据
   - 后台「站点设置」可配置：面板标题、介绍文案、团队署名（原 `recommend` 模块并入 `tags`）
4. **帖子页"精华"筛选**
   - 首页与板块页 tabs 新增「精华」分类（`?order=essence`），只显示管理员加精的帖子
5. **编辑器插入面板（Halo 风格弹窗）**
   - 原来的浏览器 `prompt` 全部替换为页面内弹窗面板：
     - **代码块**：可选语言（PHP/JS/HTML/CSS/Python/SQL/Shell/JSON）+ 代码输入框
     - **折叠内容**：标题 + 内容
     - **插入表格**：行数 / 列数 / 是否含表头
     - **插入链接**：显示文字 + 地址 + 当前窗口/新窗口
     - **插入图片**：三个标签页 —— 本地上传（点击或拖拽，支持多选）/ 图片链接 / 附件库（列出我的图片，点击即插入）
   - 新增接口 `GET /api/my-images` 返回当前用户的图片附件
   - 弹窗支持 Esc 关闭、点击遮罩关闭、Ctrl/Cmd + Enter 确认
6. **回复区：简洁模式 + 高级模式**
   - 默认显示纯文本快速回复框，右上角「高级模式」可切换到完整富文本编辑器，再点「简洁模式」切回
   - 切换时自动同步内容；同一时刻只提交一个 `content` 字段
   - 引用回复时自动切回简洁模式并预填引用内容
   - 后端 `Content::process()`：纯文本（不含标签）自动 `nl2br`，简洁回复的换行正常显示
   - 回复列表上方新增「最新回复 (n)」标题栏
7. **移动端适配**
   - 顶栏新增汉堡按钮，点击展开导航与用户操作区（≤860px）
   - 帖子详情、分页、编辑器工具栏、tabs、页脚等移动端样式优化；≤480px 进一步压缩间距

### 调整
- **tabs 顺序**：最新发布 → 最新回复 → 热门 → 最多回复 → 精华（最新回复不再默认高亮，改为默认展示全部排序）
- **搜索框**：移除放大镜图标；默认 132px，聚焦时向左延展至 260px（带过渡动画），移动端全宽
- **帖子页**：移除楼层左侧头像栏；楼层信息改为楼层头部显示「作者名 + N 楼 + 引用/删除」
- 侧边栏模块 key 调整：`recommend` → `tags`（后台保存后自动生效）

### 变更文件清单
| 文件 | 说明 |
| --- | --- |
| `app/Core/Settings.php` | 新增 `list_compact`、`about_title/text/team` 默认值 |
| `app/Admin/BaseAdmin.php` | 菜单新增「侧边栏管理」 |
| `app/Admin/Controllers/SettingController.php` | 侧边栏管理页与保存；站点设置新增开关与关于面板配置 |
| `app/admin_routes.php` | 新增 `/sidebar`、`/sidebar/save` |
| `app/Controllers/ApiController.php` | 新增 `myImages()` |
| `app/routes.php` | 新增 `GET /api/my-images` |
| `app/Models/Thread.php` | 排序支持 `essence` |
| `app/Controllers/HomeController.php`、`ForumController.php` | 支持 `order=essence` |
| `app/Services/Content.php` | 纯文本自动换行 |
| `themes/default/templates/partials/thread_list.php` | 简洁模式渲染 |
| `themes/default/templates/partials/sidebar.php` | 按配置顺序渲染 + 关于面板 |
| `themes/default/templates/thread/view.php` | 楼层去头像、回复双模式、回复标题栏 |
| `themes/default/templates/home/index.php`、`forum/view.php` | tabs 顺序与精华 |
| `themes/default/templates/layouts/main.php` | 汉堡按钮、搜索框去图标 |
| `themes/default/assets/js/editor.js` | 弹窗面板（代码/折叠/表格/链接/图片） |
| `themes/default/assets/js/app.js` | 移动端菜单 |
| `themes/default/assets/css/style.css` | 简洁列表、关于面板、搜索动画、弹窗、移动端适配 |

### 校验
- 102 个 PHP 文件语法解析：0 错误
- `app.js` / `editor.js` JS 语法解析：通过

---

## v1.0.0beta7（2026-10-01）

### 修复
1. **发布主题报 500：`SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters` —— 根因修复**
   - 原因：`Database::update()` 的 SET 部分使用命名参数（`:set_xxx`），而**全站 20 多处调用**的 WHERE 条件写的是位置参数 `?`（如 `Forum::syncStats()` 的 `'id = ?'`），PDO 不允许两者混用 → 只要触发"保存 / 更新"类操作就会 500（发帖后同步板块统计、保存设置、关注、签到等均受影响）
   - 处理（治本）：`Database::update()` 与 `Database::delete()` 现在会**自动把 WHERE 中的 `?` 按顺序转换为命名参数**，同时兼容 `['id' => $x]` 关联数组写法。所有调用无需修改，一次修复
   - 附带修复：此前"保存站点设置"等后台操作同样会触发该 500


2. **后台样式丢失（显示为无 CSS 的纯文字页面）**
   - 原因：`admin_asset()` 走了 `admin_url()`，在兼容模式（`?r=`）下 CSS 地址被错误生成为 `/admin/index.php?r=assets/css/admin.css` —— 返回的是 HTML 页面而非样式表，浏览器直接忽略
   - 处理：`admin_asset()` 改为始终输出真实文件路径 `/admin/assets/...`，并给后台 CSS/JS 加上 `?v=版本号` 防缓存（后台登录页同步修复）

### 调整
1. **帖子页排版**
   - 楼层作者栏（`.cb-post__side`）去掉"V1 新手"等级文字
   - 楼层头部的时间去掉（主楼与回复都不再重复显示；主楼信息保留"发布于 …"在 meta 行）
   - 主楼的"删除"按钮移动到主题 meta 行的**最右侧**（仅作者或管理员可见，带删除确认）
   - 移除页面底部重复动态加载 `editor.js` 的脚本（布局已按需自动加载）

2. **侧边栏顶部新增"发帖"按钮**
   - 位于"论坛统计"卡片上方，任何页面侧边栏都能一键发帖

### 变更文件清单
| 文件 | 说明 |
| --- | --- |
| `app/Core/Database.php` | `update()` / `delete()` 自动转换 WHERE 中的 `?` 为命名参数 |
| `app/Core/functions.php` | `admin_asset()` 输出真实文件路径 |
| `app/bootstrap.php` | 版本号 → `1.0.0-beta.7` |
| `admin/templates/layout.php`、`login.php` | 后台资源加版本号 |
| `themes/default/templates/thread/view.php` | 楼层排版调整、删除按钮位置 |
| `themes/default/templates/partials/sidebar.php` | 顶部新增发帖按钮 |

### 校验
- 102 个 PHP 文件语法解析：0 错误

---

## v1.0.0beta6（2026-10-01）

### 修复
1. **发帖 / 发说说的正文编辑器不显示（无法输入内容）—— 根因修复**
   - 现象：`<div id="thread-editor" data-editor>` 是一个空 div，没有工具栏也不能输入；填写标题后点"发布"没反应
   - 原因：编辑器脚本 `editor.js` 由布局中的 `$load_editor` 变量控制加载，但该变量是在**模板文件内部**定义的（`thread/edit.php`、`say/index.php`）。模板渲染发生在布局渲染**之前**，模板里的局部变量**不会回传**给布局，因此 `$load_editor` 永远是空值 → `editor.js` 从来没有被引入
   - 处理：`layouts/main.php` 改为检测页面内容中是否出现 `data-editor`：
     ```php
     $needEditor = !empty($load_editor) || str_contains((string)($content ?? ''), 'data-editor');
     ```
     只要页面里有编辑器占位元素就自动加载，不依赖模板变量传递（发帖页与说说页同时修复）

2. **点"发布"没反应**
   - `app.js` 增加提交前校验：正文为空时阻止提交并弹出提示"请先输入正文内容"，同时把焦点定位到编辑器，不再静默无响应

3. **兼容模式下前端接口地址仍有遗漏**
   - 除 `/api/*` 外，`/user/follow`、`/user/block`、`/user/friend`、`/recommend/toggle`、`/checkin/do`、`/draft/save` 等异步请求此前仍用 `SITE + '/xxx'` 拼接，兼容模式（未配伪静态）下会 404
   - 处理：新增 `window.CB_PAGE_BASE`；`app.js` 中接口请求统一走 `API` 前缀、页面跳转统一走 `PAGE` 前缀

### 变更文件清单
| 文件 | 说明 |
| --- | --- |
| `themes/default/templates/layouts/main.php` | 按页面内容自动加载 `editor.js`；输出 `CB_PAGE_BASE` |
| `themes/default/templates/layouts/gateway.php` | 输出 `CB_PAGE_BASE` |
| `themes/default/assets/js/app.js` | 正文空时提交拦截提示；接口/页面地址统一前缀 |
| `app/bootstrap.php` | 版本号 → `1.0.0-beta.6` |

### 校验
- 102 个 PHP 文件语法解析：0 错误
- `app.js` / `editor.js` JS 语法解析：通过

---

## v1.0.0beta5（2026-10-01）

### 修复
1. **发布主题页 500（点"发帖"就报错）—— 根因修复**
   - 原因：`app/routes.php` 中 `GET /thread/{id}` 定义在 `GET /thread/create` **之前**。beta3 修复占位符正则（`{id}` 默认 `[^/]+`）后，`/thread/create` 会被 `/thread/{id}` 抢先匹配，于是调用 `ThreadController::view('create')`，而 `view(int $id)` 在 `strict_types=1` 下收到非数字字符串 → `TypeError` → 500
   - 处理（治本）：
     - `Router::dispatch()` 改为**静态路由优先**：不含 `{占位符}` 的路由先匹配，再匹配动态路由。一次性解决所有同类冲突（如 `/user/settings` 被 `/user/{id}` 抢、`/users/money/save` 被 `/users/money/{id}` 抢等）
     - 新增 `Router::castParams()`：按控制器方法签名校正参数，声明为 `int` 的参数若不是数字 → 走 404 而不是 500（如访问 `/thread/abc`）
   - 影响：此前"点某些页面 500"的现象应全部消失

2. **`//api/counters` 404（双斜杠）**
   - 原因：浏览器缓存了旧版 `app.js`，旧代码用 `CB_SITE_URL + '/api/...'` 拼接，而 `CB_SITE_URL` 已带尾斜杠
   - 处理：CSS / JS 资源引用全部加上版本号 `?v=<CB_VERSION>`，升级后浏览器自动拉取新文件，不再受旧缓存影响

3. **500 错误难以排查**
   - `bootstrap.php` 的异常处理器：管理员登录状态下（或 `CB_DEBUG`）直接显示异常详情（消息 + 文件行号 + 调用堆栈），不再只显示"服务器内部错误"
   - 同时仍写入 `data/logs/`（后台「系统工具 → 系统日志」可查）

### 新增
1. **侧边栏折叠 / 位置切换**
   - 顶栏最右侧（"发帖"按钮右边）新增两个图标按钮：
     - `⇔` 显示 / 隐藏侧边栏
     - `⇆` 切换侧边栏到左侧 / 右侧
   - 状态记忆在浏览器 `localStorage`（`cb_sidebar_hidden` / `cb_sidebar_side`），各设备独立；后台「站点设置」的侧边栏开关与位置仍是全局默认值

2. **登录 / 注册页优化（Halo gateway 风格）**
   - 密码框新增"显示 / 隐藏密码"眼睛按钮（登录页 1 个、注册页 2 个）
   - 卡片内边距、标题、表单间距微调，更紧凑
   - 登录 / 注册 / 找回密码页本就使用独立的 `gateway` 布局（**不显示侧边栏**）
   - gateway 布局同样注入 `CB_API_BASE`，避免接口地址错误

### 变更文件清单
| 文件 | 说明 |
| --- | --- |
| `app/Core/Router.php` | 静态路由优先匹配；新增 `castParams()` 参数类型保护 |
| `app/bootstrap.php` | 管理员可见异常详情；版本号 → `1.0.0-beta.5` |
| `themes/default/templates/layouts/main.php` | 侧边栏折叠/位置按钮；资源加版本号 |
| `themes/default/templates/layouts/gateway.php` | 资源加版本号；注入 `CB_API_BASE` |
| `themes/default/assets/js/app.js` | 侧边栏折叠/位置逻辑；密码显示切换 |
| `themes/default/assets/css/style.css` | `.sidebar-hidden`、`.cb-input-wrap`、`.cb-pwd-toggle`、gateway 微调 |
| `themes/default/templates/auth/login.php` | 密码显示切换按钮 |
| `themes/default/templates/auth/register.php` | 密码显示切换按钮 |

### 校验
- 102 个 PHP 文件语法解析：0 错误

---

## v1.0.0beta4（2026-10-01）

### 修复
1. **站内链接仍然 404（`/login`、`/register`、`/thread/create`…）—— 彻底解决**
   - 原因：服务器（OpenResty/Nginx）未配置伪静态，请求没有交给 `index.php`；而 beta3 提供的"URL 模式"开关位于后台，用户因 `/admin` 同样 404 而**根本进不去后台设置**，形成死循环
   - 处理：新增 **自动探测模式 `url_mode = auto`（并设为默认）**
     - `cb_url_mode()` 根据当前请求判断：请求路径是真实文件 / 目录、或为 `/`、`*.php` → 判定伪静态未生效 → 自动生成 `?r=xxx` 兼容链接；否则判定伪静态生效 → 输出简洁地址
     - **无论服务器有没有配 rewrite，站内链接都不会再 404**
     - 后台「论坛设置 → 站点设置 → URL 模式」可手动锁定为 `rewrite`（已配伪静态）或 `query`（强制兼容）
   - 前端接口地址同样适配：新增 `window.CB_API_BASE`，`app.js` / `editor.js` 的点赞、通知、计数、上传图片等 4 处 `/api/xxx` 请求改用该前缀，兼容模式下自动变为 `/?r=api/xxx`

### 变更
- CHANGELOG 迁移到源码目录内（`CHANGELOG.md` 随站点一起发布，升级后可直接查看）

### 变更文件清单
| 文件 | 说明 |
| --- | --- |
| `app/Core/functions.php` | 新增 `cb_url_mode()` 自动探测；`url()` / `admin_url()` 统一使用 |
| `app/Core/Settings.php` | `url_mode` 默认值改为 `auto` |
| `app/Admin/Controllers/SettingController.php` | URL 模式增加"自动探测"选项与说明 |
| `themes/default/templates/layouts/main.php` | 输出 `window.CB_URL_MODE` / `window.CB_API_BASE` |
| `themes/default/assets/js/app.js` | 接口请求改用 `CB_API_BASE` |
| `themes/default/assets/js/editor.js` | 图片上传接口改用 `CB_API_BASE` |
| `app/bootstrap.php` | 版本号 → `1.0.0-beta.4` |

### 校验
- 102 个 PHP 文件语法解析：0 错误

---

## v1.0.0beta3（2026-10-01）

### 修复
1. **后台 `/admin` 500 致命错误**
   - 现象：`PHP Fatal error: Cannot redeclare App\Admin\BaseAdmin::guard()`
   - 原因：`app/Admin/BaseAdmin.php` 中存在两个同名 `guard()` 方法（新增的后台守卫与旧的 `Auth::requireAdmin()` 版本并存）
   - 处理：删除旧方法，仅保留"未登录跳后台登录页 + 无权限 403"的新实现；并新增全量脚本校验，确认所有类无重复方法

2. **站内链接全部 404（`/login`、`/register`、`/forums`、`/user` 等）**
   - 现象：OpenResty 日志 `open() "/.../index/register" failed (2: No such file or directory)`，即请求没有交给 `index.php`
   - 原因：服务器未配置伪静态（rewrite）
   - 处理（双保险）：
     - 新增 **URL 模式** 设置（后台「论坛设置 → 站点设置」）：`rewrite`（默认）/ `query`
     - 选择 `query` 时，站内链接自动输出 `?r=xxx` 形式，后台输出 `/admin/index.php?r=xxx`，**无需服务器配置伪静态也可正常使用**
     - `asset()`、`theme_asset()`、`avatar_url()` 始终走真实文件路径，不受 URL 模式影响
     - `nginx.example.conf` 顶部新增"最小化配置"3 段 location，并补充 OpenResty 说明

3. **安装页 `session_name(): Session name cannot be changed when a session is active`**（上一版修复，本版保留）
   - `install/index.php` 不再调用 `session_name()`，仅做兜底 `session_start()`

4. **错误处理过严**
   - `bootstrap.php` 的 error handler 分级：致命/可恢复错误仍抛异常；`E_WARNING / E_NOTICE / E_DEPRECATED` 只写 `data/logs/`，不再导致 500 白屏
   - 新增 `CB_STRICT_ERRORS` 常量，开发排错时可恢复严格模式
   - `Logger` 新增 `public static log()` 供 error handler 调用

### 新增
1. **外观微调：圆角控制**（后台「扩展管理 → 主题 → 外观微调」）
   - `theme_radius`：0 / 2 / 4 / 6 / 8（默认）/ 10 / 12 / 16 / 20 px，设为 **0 即为方方正正的直角风格**
   - 影响范围：卡片、按钮、输入框、标签、头像框、代码块、下拉面板等全部元素（主题 CSS 中的硬编码圆角已统一改为 `--radius-sm / --radius / --radius-lg` 变量）
   - `theme_primary`：主题主色（链接、按钮、标签主色调）
   - 前台 `layouts/main.php` 在 `<head>` 内联输出 CSS 变量，保存后立即生效

2. **首页 / 板块页：筛选条与帖子列表合并为同一张卡片**
   - 去掉原先"筛选条一个卡片 + 列表一个卡片"中间断开的结构
   - 新增 `.cb-card__header--tabs` + `.cb-tabs / .cb-tab` 标签式筛选（最新回复 / 最新发布 / 热门 / 最多回复），右侧保留"发布主题"按钮
   - `partials/thread_list.php` 新增 `$boxed` 参数，嵌入卡片时去掉自身边框与圆角

### 变更文件清单
| 文件 | 说明 |
| --- | --- |
| `app/Admin/BaseAdmin.php` | 删除重复的 `guard()` |
| `app/Core/functions.php` | `url()` / `admin_url()` 支持 query 模式；`asset()`、`theme_asset()`、`avatar_url()` 走真实路径 |
| `app/Core/Settings.php` | 新增默认项 `theme_radius`、`theme_primary`、`url_mode` |
| `app/Core/Logger.php` | 新增 `public static log()` |
| `app/bootstrap.php` | 版本号 → `1.0.0-beta.3`；error handler 分级 |
| `app/Admin/Controllers/ExtController.php` | 主题页新增"外观微调"表单与 `themeAppearance()` |
| `app/Admin/Controllers/SettingController.php` | 站点设置新增 `url_mode` 选项并保存 |
| `app/admin_routes.php` | 新增 `POST /themes/appearance` |
| `themes/default/assets/css/style.css` | 硬编码圆角改变量；新增 tabs 与 `--plain` 列表样式 |
| `themes/default/templates/layouts/main.php` | head 内联圆角/主色 CSS 变量 |
| `themes/default/templates/home/index.php` | 筛选条 + 列表合并为单卡片 |
| `themes/default/templates/forum/view.php` | 同上 |
| `themes/default/templates/partials/thread_list.php` | 新增 `$boxed` 参数 |
| `install/index.php` | 修复 session_name 报错 |
| `nginx.example.conf` | 新增"最小化配置"与 OpenResty 说明 |
| `README.md` | 新增 404 / 500 排查 FAQ |

### 校验
- 102 个 PHP 文件语法解析：0 错误
- 全量类重复方法检查：0 问题
- 路由 ↔ 控制器方法：全部匹配

---

## v1.0.0（2026-10-01）
- 首个完整版本：核心框架、34 张数据表、安装向导、前台全功能、后台 6 大分组 33 个管理页
