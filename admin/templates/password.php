<?php
/**
 * 后台独立密码验证页（独立布局）
 */
$errors = $errors ?? [];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>安全验证 - <?= h((string)config('site_name', 'ChatBBS')) ?></title>
    <script>try { if (localStorage.getItem('adm_theme') === 'dark') { document.documentElement.dataset.admTheme = 'dark'; } } catch (e) {}</script>
    <link rel="stylesheet" href="<?= h(admin_asset('css/admin.css') . '?v=' . CB_VERSION) ?>">
</head>
<body>
<div class="adm-login-wrap">
    <div class="adm-login-box">
        <h1>🔒 安全验证</h1>
        <div class="sub"><?= (string)($_GET['timeout'] ?? '') !== '' ? '后台长时间未操作，请重新输入超级密码以继续' : '本后台已开启独立密码，请再次输入以继续' ?></div>

        <?php if (!empty($errors)): ?>
            <div class="adm-alert error"><?= h((string)implode('；', $errors)) ?></div>
        <?php endif; ?>
        <?php $lgErr = \App\Core\Session::flash('error'); ?>
        <?php if ($lgErr): ?><div class="adm-alert error"><?= h((string)$lgErr) ?></div><?php endif; ?>

        <form method="post" action="<?= h(admin_url('password')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="back" value="<?= h((string)($back ?? '')) ?>">
            <input class="adm-input" type="password" name="admin_password" placeholder="后台独立密码"
                   autocomplete="current-password" required autofocus>
            <button class="adm-btn primary" type="submit">进 入 后 台</button>
        </form>

        <div class="adm-login-foot">
            忘记独立密码？可在数据库 settings 表中删除 admin_pass_hash 重置
            &nbsp;·&nbsp; ChatBBS v<?= h((string)CB_VERSION) ?>
        </div>
    </div>
</div>
</body>
</html>
