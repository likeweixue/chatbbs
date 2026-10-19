<?php
/**
 * 后台登录页（独立布局）
 */
$errors = $errors ?? [];
$needCaptcha = !empty($captcha);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>后台登录 - <?= h((string)config('site_name', 'ChatBBS')) ?></title>
    <script>try { if (localStorage.getItem('adm_theme') === 'dark') { document.documentElement.dataset.admTheme = 'dark'; } } catch (e) {}</script>
    <link rel="stylesheet" href="<?= h(admin_asset('css/admin.css') . '?v=' . CB_VERSION) ?>">
</head>
<body>
<div class="adm-login-wrap">
    <div class="adm-login-box">
        <h1>ChatBBS 管理后台</h1>
        <div class="sub"><?= h((string)config('site_name', 'ChatBBS')) ?></div>

        <?php if (!empty($errors)): ?>
            <div class="adm-alert error"><?= h((string)implode('；', $errors)) ?></div>
        <?php endif; ?>
        <?php $lgErr = \App\Core\Session::flash('error'); ?>
        <?php if ($lgErr): ?><div class="adm-alert error"><?= h((string)$lgErr) ?></div><?php endif; ?>

        <form method="post" action="<?= h(admin_url('login')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="back" value="<?= h((string)($back ?? '')) ?>">
            <input class="adm-input" type="text" name="account" placeholder="用户名 / 邮箱" value="<?= h((string)($account ?? '')) ?>" autocomplete="username" required autofocus>
            <input class="adm-input" type="password" name="password" placeholder="登录密码" autocomplete="current-password" required>
            <?php if ($needCaptcha): ?>
                <div class="captcha-row">
                    <input class="adm-input" type="text" name="captcha" placeholder="验证码" autocomplete="off" required>
                    <img src="<?= h(admin_url('captcha')) ?>" alt="验证码" title="点击刷新"
                         onclick="this.src='<?= h(admin_url('captcha')) ?>?t='+Date.now()">
                </div>
            <?php endif; ?>
            <button class="adm-btn primary" type="submit">登 录</button>
        </form>

        <div class="adm-login-foot">
            <a href="<?= h(url('')) ?>">← 返回前台</a>
            &nbsp;·&nbsp; ChatBBS v<?= h((string)CB_VERSION) ?>
        </div>
    </div>
</div>
</body>
</html>
