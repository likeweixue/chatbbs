<?php
/**
 * 前台路由
 */

declare(strict_types=1);

use App\Core\Router;

Router::get('/', \App\Controllers\HomeController::class . '@index');

// 界面语言切换（只切换界面名词，不影响帖子内容）
Router::get('/lang/{code}', \App\Controllers\HomeController::class . '@switchLang');
// 授权证书（自动识别当前站点域名为被授权方）
Router::get('/license', \App\Controllers\HomeController::class . '@license');

// 认证
Router::get('/login', \App\Controllers\AuthController::class . '@loginForm');
Router::post('/login', \App\Controllers\AuthController::class . '@login', ['csrf']);
Router::get('/register', \App\Controllers\AuthController::class . '@registerForm');
Router::post('/register', \App\Controllers\AuthController::class . '@register', ['csrf']);
Router::get('/forgot', \App\Controllers\AuthController::class . '@forgotForm');
Router::post('/forgot', \App\Controllers\AuthController::class . '@sendReset', ['csrf']);
Router::get('/reset', \App\Controllers\AuthController::class . '@resetForm');
Router::post('/reset', \App\Controllers\AuthController::class . '@reset', ['csrf']);
Router::get('/verify', \App\Controllers\AuthController::class . '@verify');
Router::any('/logout', \App\Controllers\AuthController::class . '@logout');
Router::get('/captcha', \App\Controllers\AuthController::class . '@captcha');

// 板块
Router::get('/forums', \App\Controllers\ForumController::class . '@index');
Router::get('/forum/{id}', \App\Controllers\ForumController::class . '@view');
Router::get('/forum/{id}/{slug}', \App\Controllers\ForumController::class . '@view');

// 主题
Router::get('/thread/{id}', \App\Controllers\ThreadController::class . '@view');
Router::get('/thread/create', \App\Controllers\ThreadController::class . '@createForm', ['auth']);
Router::post('/thread/create', \App\Controllers\ThreadController::class . '@store', ['auth', 'csrf']);
Router::get('/thread/{id}/edit', \App\Controllers\ThreadController::class . '@editForm', ['auth']);
Router::post('/thread/{id}/edit', \App\Controllers\ThreadController::class . '@update', ['auth', 'csrf']);
Router::post('/thread/{id}/reply', \App\Controllers\ThreadController::class . '@reply', ['auth', 'csrf']);
Router::post('/thread/{id}/quick', \App\Controllers\ThreadController::class . '@quick', ['auth', 'csrf']);
Router::get('/thread/{id}/quick-options', \App\Controllers\ThreadController::class . '@quickOptions', ['auth']);
Router::post('/thread/{id}/delete', \App\Controllers\ThreadController::class . '@delete', ['auth', 'csrf']);
Router::post('/post/{id}/delete', \App\Controllers\ThreadController::class . '@deletePost', ['auth', 'csrf']);
Router::post('/thread/{id}/report', \App\Controllers\ThreadController::class . '@report', ['auth', 'csrf']);
Router::post('/draft/save', \App\Controllers\ThreadController::class . '@saveDraft', ['auth', 'csrf']);

// 说说
Router::get('/say', \App\Controllers\SayController::class . '@index');
Router::post('/say/publish', \App\Controllers\SayController::class . '@publish', ['auth', 'csrf']);
Router::post('/say/{id}/delete', \App\Controllers\SayController::class . '@delete', ['auth', 'csrf']);
Router::post('/say/{id}/comment', \App\Controllers\SayController::class . '@comment', ['auth', 'csrf']);
Router::post('/say/comment/{id}/delete', \App\Controllers\SayController::class . '@deleteComment', ['auth', 'csrf']);

// 推荐
Router::get('/recommend', \App\Controllers\RecommendController::class . '@index');
Router::post('/recommend/toggle', \App\Controllers\RecommendController::class . '@toggle', ['auth', 'csrf']);

// 标签
Router::get('/tags', \App\Controllers\TagController::class . '@index');
Router::get('/tag/{id}', \App\Controllers\TagController::class . '@view');

// 签到
Router::get('/checkin', \App\Controllers\CheckinController::class . '@index');
Router::post('/checkin/do', \App\Controllers\CheckinController::class . '@do', ['auth', 'csrf']);
Router::post('/checkin/makeup', \App\Controllers\CheckinController::class . '@makeup', ['auth', 'csrf']);
Router::post('/checkin/buy-card', \App\Controllers\CheckinController::class . '@buyCard', ['auth', 'csrf']);

// 聊天（单聊 / 群聊 / 附近的人）
Router::get('/chat', \App\Controllers\ChatController::class . '@index', ['auth']);
Router::get('/chat/group/{id}', \App\Controllers\ChatController::class . '@group', ['auth']);
Router::post('/chat/send', \App\Controllers\ChatController::class . '@send', ['auth', 'csrf']);
Router::post('/chat/create', \App\Controllers\ChatController::class . '@createGroup', ['auth', 'csrf']);
Router::post('/chat/join/{id}', \App\Controllers\ChatController::class . '@joinGroup', ['auth', 'csrf']);
Router::post('/chat/leave/{id}', \App\Controllers\ChatController::class . '@leaveGroup', ['auth', 'csrf']);
Router::post('/chat/invite/{id}', \App\Controllers\ChatController::class . '@invite', ['auth', 'csrf']);
Router::post('/chat/discover', \App\Controllers\ChatController::class . '@toggleDiscover', ['auth', 'csrf']);
Router::get('/api/chat/poll', \App\Controllers\ChatController::class . '@poll', ['auth']);

// 私信 / 社交（/pm 保留为 /chat 的别名，避免旧链接失效）
Router::get('/pm', \App\Controllers\ChatController::class . '@index', ['auth']);
Router::post('/pm/send', \App\Controllers\MessageController::class . '@send', ['auth', 'csrf']);
Router::post('/pm/{id}/delete', \App\Controllers\MessageController::class . '@deleteConversation', ['auth', 'csrf']);
Router::post('/user/follow', \App\Controllers\MessageController::class . '@follow', ['auth', 'csrf']);
Router::post('/user/friend', \App\Controllers\MessageController::class . '@friend', ['auth', 'csrf']);
Router::post('/user/unfriend', \App\Controllers\MessageController::class . '@unfriend', ['auth', 'csrf']);
Router::post('/user/block', \App\Controllers\MessageController::class . '@block', ['auth', 'csrf']);

// 通知
Router::get('/notifications', \App\Controllers\NotifyController::class . '@index', ['auth']);
Router::post('/notifications/read-all', \App\Controllers\NotifyController::class . '@readAll', ['auth', 'csrf']);
Router::post('/notifications/{id}/read', \App\Controllers\NotifyController::class . '@read', ['auth', 'csrf']);
Router::post('/notifications/{id}/delete', \App\Controllers\NotifyController::class . '@delete', ['auth', 'csrf']);

// 搜索
Router::get('/search', \App\Controllers\SearchController::class . '@index');

// 用户中心
Router::get('/user', \App\Controllers\UserController::class . '@home', ['auth']);
Router::get('/user/settings', \App\Controllers\UserController::class . '@settings', ['auth']);
Router::post('/user/settings/profile', \App\Controllers\UserController::class . '@updateProfile', ['auth', 'csrf']);
Router::post('/user/settings/password', \App\Controllers\UserController::class . '@updatePassword', ['auth', 'csrf']);
Router::post('/user/settings/avatar', \App\Controllers\UserController::class . '@updateAvatar', ['auth', 'csrf']);
Router::post('/user/settings/avatar-url', \App\Controllers\UserController::class . '@updateAvatarUrl', ['auth', 'csrf']);
Router::post('/user/settings/guestbook', \App\Controllers\UserController::class . '@updateGuestbookSetting', ['auth', 'csrf']);
Router::post('/user/{id}/guestbook', \App\Controllers\UserController::class . '@guestbookAdd', ['auth', 'csrf']);
Router::post('/user/guestbook/{id}/delete', \App\Controllers\UserController::class . '@guestbookDelete', ['auth', 'csrf']);
Router::get('/user/threads', \App\Controllers\UserController::class . '@threads', ['auth']);
Router::get('/user/drafts', \App\Controllers\UserController::class . '@drafts', ['auth']);
Router::post('/user/draft/{id}/delete', \App\Controllers\UserController::class . '@deleteDraft', ['auth', 'csrf']);
Router::get('/user/friends', \App\Controllers\UserController::class . '@friends', ['auth']);
Router::get('/user/attachments', \App\Controllers\UserController::class . '@attachments', ['auth']);
Router::post('/user/attachments/upload', \App\Controllers\UserController::class . '@uploadAttachment', ['auth', 'csrf']);
Router::post('/user/attachments/{id}/delete', \App\Controllers\UserController::class . '@deleteAttachment', ['auth', 'csrf']);
Router::get('/user/{id}', \App\Controllers\UserController::class . '@profile');
Router::get('/u/{id}', \App\Controllers\UserController::class . '@profile');

// 附件
Router::get('/attachments', \App\Controllers\AttachmentController::class . '@library');
Router::get('/attachment/{id}/download', \App\Controllers\AttachmentController::class . '@download');
Router::get('/attachment/{id}/buy', \App\Controllers\AttachmentController::class . '@buyForm', ['auth']);
Router::post('/attachment/{id}/buy', \App\Controllers\AttachmentController::class . '@buy', ['auth', 'csrf']);

// Wiki 词条
Router::get('/wiki', \App\Controllers\WikiController::class . '@index');
Router::get('/wiki/create', \App\Controllers\WikiController::class . '@createForm', ['auth']);
Router::post('/wiki/create', \App\Controllers\WikiController::class . '@store', ['auth', 'csrf']);
Router::get('/wiki/{slug}/edit', \App\Controllers\WikiController::class . '@editForm', ['auth']);
Router::post('/wiki/{slug}/edit', \App\Controllers\WikiController::class . '@update', ['auth', 'csrf']);
Router::get('/wiki/{slug}', \App\Controllers\WikiController::class . '@view');

// RSS 阅读
Router::get('/reader', \App\Controllers\ReaderController::class . '@index');

// 网站导航
Router::get('/nav', \App\Controllers\LinksController::class . '@index');
$navSlug = trim((string)\App\Core\Settings::get('nav_slug', 'nav'), '/');
if ($navSlug !== '' && $navSlug !== 'nav' && preg_match('/^[a-z0-9\-_]+$/', $navSlug)) {
    Router::get('/' . $navSlug, \App\Controllers\LinksController::class . '@index');
}

// RSS 订阅
Router::get('/rss', \App\Controllers\RssController::class . '@index');
Router::get('/rss.xml', \App\Controllers\RssController::class . '@index');

// 异步接口
Router::post('/api/like', \App\Controllers\ApiController::class . '@like', ['csrf']);
Router::get('/api/counters', \App\Controllers\ApiController::class . '@counters');
Router::get('/api/notifications', \App\Controllers\ApiController::class . '@notifications');
Router::get('/api/mentions', \App\Controllers\ApiController::class . '@mentions');
Router::post('/api/upload', \App\Controllers\ApiController::class . '@uploadImage', ['csrf']);
/* Markdown 渲染（编辑器「切换回富文本」/「预览排版」使用，前后端共用同一解析器） */
Router::post('/api/markdown', \App\Controllers\ApiController::class . '@markdownPreview', ['csrf']);
// 违禁词列表（编辑器实时检测用）
Router::get('/api/forbidden-words', \App\Controllers\ApiController::class . '@forbiddenWords');
Router::get('/api/my-files', \App\Controllers\ApiController::class . '@myFiles');
/* 兼容旧调用：编辑器早期版本请求的是 api/my-images（否则返回 404 → 附件库"加载失败"） */
Router::get('/api/my-images', \App\Controllers\ApiController::class . '@myImages');
