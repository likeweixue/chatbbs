<?php
/**
 * 后台路由（相对 /admin 目录）
 */

declare(strict_types=1);

use App\Admin\BaseAdmin;
use App\Core\Router;

$C = '\\App\\Admin\\Controllers\\';

/* 登录 / 登出 / 验证码（无需登录） */
Router::get('/login', $C . 'AdminAuthController@form');
Router::post('/login', $C . 'AdminAuthController@login', ['csrf']);
Router::post('/logout', $C . 'AdminAuthController@logout', ['csrf']);
Router::get('/captcha', $C . 'AdminAuthController@captcha');

/* 后台独立密码（二次验证）：需已登录管理员，但不走完整 guard，避免验证死循环 */
Router::get('/password', $C . 'AdminAuthController@passwordForm');
Router::post('/password', $C . 'AdminAuthController@passwordVerify', ['csrf']);

/* 需要管理员权限的路由 */
$guard = [BaseAdmin::middleware()];

Router::group('', function () use ($C): void {

    /* ---------- 仪表盘 ---------- */
    Router::get('/', $C . 'DashboardController@index');

    /* ---------- 内容管理 ---------- */
    Router::get('/forums', $C . 'ContentController@forums');
    Router::get('/forums/create', $C . 'ContentController@forumForm');
    Router::get('/forums/edit/{id}', $C . 'ContentController@forumForm');
    Router::post('/forums/save', $C . 'ContentController@forumSave', ['csrf']);
    Router::post('/forums/delete/{id}', $C . 'ContentController@forumDelete', ['csrf']);
    Router::post('/forums/clear/{id}', $C . 'ContentController@forumClear', ['csrf']);
    Router::post('/forums/bulk', $C . 'ContentController@forumBulk', ['csrf']);
    Router::post('/forums/resync', $C . 'ContentController@forumResync', ['csrf']);
    Router::post('/forums/spotlight', $C . 'ContentController@forumSpotlightSave', ['csrf']);
    Router::post('/forums/sort', $C . 'ContentController@forumSort', ['csrf']);

    Router::get('/threads', $C . 'ContentController@threads');
    Router::get('/threads/edit/{id}', $C . 'ContentController@threadEdit');
    Router::post('/threads/save', $C . 'ContentController@threadSave', ['csrf']);
    Router::post('/threads/act/{id}/{act}', $C . 'ContentController@threadAct', ['csrf']);
    Router::post('/threads/bulk', $C . 'ContentController@threadBulk', ['csrf']);

    Router::get('/posts', $C . 'ContentController@posts');
    Router::post('/posts/delete/{id}', $C . 'ContentController@postDelete', ['csrf']);
    Router::post('/posts/bulk', $C . 'ContentController@postBulk', ['csrf']);

    Router::get('/tags', $C . 'ContentMoreController@tags');
    Router::get('/tags/edit/{id}', $C . 'ContentMoreController@tagEdit');
    Router::post('/tags/save', $C . 'ContentMoreController@tagSave', ['csrf']);
    Router::post('/tags/delete/{id}', $C . 'ContentMoreController@tagDelete', ['csrf']);
    Router::post('/tags/merge', $C . 'ContentMoreController@tagMerge', ['csrf']);
    Router::post('/tags/bulk', $C . 'ContentMoreController@tagBulk', ['csrf']);

    Router::get('/marks', $C . 'ContentMoreController@marks');
    Router::post('/marks/save', $C . 'ContentMoreController@markSave', ['csrf']);
    Router::post('/marks/delete/{id}', $C . 'ContentMoreController@markDelete', ['csrf']);

    Router::get('/says', $C . 'ContentMoreController@says');
    Router::post('/says/delete/{id}', $C . 'ContentMoreController@sayDelete', ['csrf']);
    Router::post('/says/bulk', $C . 'ContentMoreController@sayBulk', ['csrf']);

    Router::get('/recommends', $C . 'ContentMoreController@recommends');
    Router::post('/recommends/delete/{id}', $C . 'ContentMoreController@recommendDelete', ['csrf']);

    Router::get('/attachments', $C . 'ContentMoreController@attachments');
    Router::post('/attachments/delete/{id}', $C . 'ContentMoreController@attachmentDelete', ['csrf']);
    Router::post('/attachments/bulk', $C . 'ContentMoreController@attachmentBulk', ['csrf']);

    Router::get('/reports', $C . 'ContentMoreController@reports');
    Router::get('/reports/view/{id}', $C . 'ContentMoreController@reportView');
    Router::post('/reports/handle', $C . 'ContentMoreController@reportHandle', ['csrf']);
    Router::post('/reports/act/{id}/{status}', $C . 'ContentMoreController@reportAct', ['csrf']);

    /* 违禁词库 */
    Router::get('/forbidden-words', $C . 'ForbiddenWordController@index');
    Router::post('/forbidden-words/add', $C . 'ForbiddenWordController@add', ['csrf']);
    Router::get('/forbidden-words/toggle/{id}', $C . 'ForbiddenWordController@toggle');
    Router::get('/forbidden-words/delete/{id}', $C . 'ForbiddenWordController@delete');

    /* 群聊管理 */
    Router::get('/chat-groups', $C . 'ChatGroupController@index');
    Router::get('/chat-groups/delete/{id}', $C . 'ChatGroupController@delete');
    Router::post('/chat-groups/settings', $C . 'ChatGroupController@saveSettings', ['csrf']);

    /* ---------- 用户管理 ---------- */
    Router::get('/users', $C . 'UserAdminController@users');
    Router::get('/users/create', $C . 'UserAdminController@userForm');
    Router::get('/users/edit/{id}', $C . 'UserAdminController@userForm');
    Router::post('/users/save', $C . 'UserAdminController@userSave', ['csrf']);
    Router::post('/users/password', $C . 'UserAdminController@userPassword', ['csrf']);
    Router::get('/users/money/{id}', $C . 'UserAdminController@userMoney');
    Router::post('/users/money/save', $C . 'UserAdminController@userMoneySave', ['csrf']);
    Router::post('/users/delete/{id}', $C . 'UserAdminController@userDelete', ['csrf']);
    Router::post('/users/bulk', $C . 'UserAdminController@userBulk', ['csrf']);

    Router::get('/groups', $C . 'UserAdminController@groups');
    Router::post('/groups/save', $C . 'UserAdminController@groupSave', ['csrf']);
    Router::post('/groups/delete/{id}', $C . 'UserAdminController@groupDelete', ['csrf']);

    Router::get('/certs', $C . 'UserAdminController@certs');
    Router::post('/certs/save', $C . 'UserAdminController@certSave', ['csrf']);
    Router::post('/certs/delete/{id}', $C . 'UserAdminController@certDelete', ['csrf']);

    Router::get('/levels', $C . 'UserAdminController@levels');
    Router::post('/levels/save', $C . 'UserAdminController@levelSave', ['csrf']);
    Router::post('/levels/delete/{id}', $C . 'UserAdminController@levelDelete', ['csrf']);

    Router::get('/checkins', $C . 'UserAdminController@checkins');
    Router::get('/currency', $C . 'UserAdminController@currency');

    Router::get('/messages', $C . 'UserAdminController@messages');
    Router::post('/messages/delete/{id}', $C . 'UserAdminController@messageDelete', ['csrf']);
    Router::post('/messages/bulk', $C . 'UserAdminController@messageBulk', ['csrf']);

    /* ---------- 论坛设置 ---------- */
    Router::get('/settings', $C . 'SettingController@settings');
    Router::post('/settings/save', $C . 'SettingController@save', ['csrf']);
Router::post('/settings/rss', $C . 'SettingController@saveRss', ['csrf']);
Router::get('/nav-links', $C . 'NavLinkController@index');
Router::post('/nav-links/save', $C . 'NavLinkController@save', ['csrf']);
Router::post('/nav-links/delete/{id}', $C . 'NavLinkController@delete', ['csrf']);
Router::post('/nav-links/settings', $C . 'NavLinkController@saveSettings', ['csrf']);
    Router::get('/settings/features', $C . 'SettingController@features');
    Router::post('/settings/features/save', $C . 'SettingController@saveFeatures', ['csrf']);
    Router::get('/settings/currency', $C . 'SettingController@currency');
    Router::post('/settings/currency/save', $C . 'SettingController@saveCurrency', ['csrf']);
    Router::get('/settings/permissions', $C . 'SettingController@permissions');
    Router::post('/settings/permissions/save', $C . 'SettingController@savePermissions', ['csrf']);
    Router::get('/settings/security', $C . 'SettingController@security');
    Router::post('/settings/security/save', $C . 'SettingController@saveSecurity', ['csrf']);

    Router::get('/list-tabs', $C . 'SettingController@listTabs');
    Router::post('/list-tabs/save', $C . 'SettingController@saveListTabs', ['csrf']);

    Router::get('/sidebar', $C . 'SettingController@sidebar');
    Router::post('/sidebar/save', $C . 'SettingController@saveSidebar', ['csrf']);

    Router::get('/menus', $C . 'SettingController@menus');
    Router::post('/menus/save', $C . 'SettingController@menuSave', ['csrf']);
    Router::post('/menus/delete/{id}', $C . 'SettingController@menuDelete', ['csrf']);

    Router::get('/mail', $C . 'SettingController@mail');
    Router::post('/mail/save', $C . 'SettingController@mailSave', ['csrf']);
    Router::post('/mail/test', $C . 'SettingController@mailTest', ['csrf']);

    Router::get('/notifications', $C . 'SettingController@notifications');
    Router::post('/notifications/broadcast', $C . 'SettingController@broadcast', ['csrf']);
    Router::post('/notifications/delete/{id}', $C . 'SettingController@notifyDelete', ['csrf']);
    Router::post('/notifications/bulk', $C . 'SettingController@notifyBulk', ['csrf']);

    /* ---------- 扩展管理 ---------- */
    Router::get('/plugins', $C . 'ExtController@plugins');
    Router::post('/plugins/install/{id}', $C . 'ExtController@pluginInstall', ['csrf']);
    Router::post('/plugins/toggle/{id}/{enable}', $C . 'ExtController@pluginToggle', ['csrf']);
    Router::post('/plugins/remove/{id}', $C . 'ExtController@pluginRemove', ['csrf']);

    Router::get('/themes', $C . 'ExtController@themes');
    Router::post('/themes/enable/{id}', $C . 'ExtController@themeEnable', ['csrf']);
    Router::post('/themes/appearance', $C . 'ExtController@themeAppearance', ['csrf']);
Router::post('/themes/layout', $C . 'ExtController@themeLayoutSave', ['csrf']);
Router::post('/themes/default-layout', $C . 'ExtController@themeDefaultLayoutSave', ['csrf']);
Router::post('/themes/diy', $C . 'ExtController@themeDiySave', ['csrf']);
Router::get('/medals', $C . 'MedalController@index');
Router::post('/medals/save', $C . 'MedalController@save', ['csrf']);
Router::post('/medals/delete/{id}', $C . 'MedalController@delete', ['csrf']);

    Router::get('/languages', $C . 'ExtController@languages');
    Router::post('/languages/enable/{id}', $C . 'ExtController@languageEnable', ['csrf']);

    /* ---------- 系统工具 ---------- */
    Router::get('/cache', $C . 'SystemController@cache');
    Router::post('/cache/clear', $C . 'SystemController@cacheClear', ['csrf']);

    Router::get('/logs', $C . 'SystemController@logs');
    Router::post('/logs/clear', $C . 'SystemController@logsClear', ['csrf']);

    Router::get('/adminlogs', $C . 'SystemController@adminlogs');
    Router::post('/adminlogs/clear', $C . 'SystemController@adminlogsClear', ['csrf']);

    Router::get('/backup', $C . 'SystemController@backup');
    Router::post('/backup/create', $C . 'SystemController@backupCreate', ['csrf']);
    Router::get('/backup/download', $C . 'SystemController@backupDownload');
    Router::post('/backup/restore', $C . 'SystemController@backupRestore', ['csrf']);
    Router::post('/backup/delete', $C . 'SystemController@backupDelete', ['csrf']);

    Router::get('/database', $C . 'SystemController@database');
    Router::post('/database/optimize', $C . 'SystemController@databaseOptimize', ['csrf']);
    Router::post('/database/fix', $C . 'SystemController@databaseFix', ['csrf']);

    Router::get('/upgrade', $C . 'SystemController@upgrade');
    Router::post('/upgrade/check', $C . 'SystemController@upgradeCheck', ['csrf']);
    Router::post('/upgrade/apply', $C . 'SystemController@upgradeApply', ['csrf']);

    Router::get('/system', $C . 'SystemController@system');
}, $guard);
