<?php
/**
 * 认证页布局（登录 / 注册 / 找回密码）
 *
 * 视觉参考：Flarum（https://flarum.org）的 Modal 登录 / 注册弹窗
 * Flarum 采用 MIT License，Copyright (c) Flarum Foundation
 * 本项目仅借鉴其界面结构（Modal / Form-group / FormControl / Button 类名与布局），
 * 样式与实现均为 ChatBBS 自行编写。详见源码根目录 THIRD-PARTY-NOTICES.md
 */
$siteName = $site_name ?? config('site_name', 'ChatBBS');
$logoType = (string)config('logo_type', 'text');
$logoImg = (string)config('site_logo', '');
$logoText = trim((string)config('logo_text', ''));
if ($logoText === '') {
    $logoText = $siteName;
}
$favicon = (string)config('site_favicon', '');
?>
<!DOCTYPE html>
<html lang="<?= h(\App\Core\Lang::current()) ?>" data-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= h(($title ?? '') !== '' ? $title . ' - ' . $siteName : $siteName) ?></title>
<meta name="theme-color" content="#2d6ee5">
<link rel="icon" href="<?= h($favicon !== '' ? $favicon : asset('images/retro/favicon.svg')) ?>">
<link rel="stylesheet" href="<?= h(theme_asset('css/style.css') . '?v=' . CB_VERSION) ?>">
<link rel="stylesheet" href="<?= h(theme_asset('css/auth-flarum.css') . '?v=' . CB_VERSION) ?>">
<?php
// 认证弹窗主色：跟随后台「外观微调 → 主题主色」，未设置时用 Flarum 蓝
$flPrimary = (string)config('theme_primary', '#2d6ee5');
if (!preg_match('/^#[0-9a-fA-F]{6}$/', $flPrimary)) {
    $flPrimary = '#2d6ee5';
}
?>
<style>:root{--color-primary:<?= $flPrimary ?>;--color-link:<?= $flPrimary ?>}</style>
<script>
  window.CB_SITE_URL = '<?= h(site_base() . '/') ?>';
  window.CB_URL_MODE = '<?= h(cb_url_mode()) ?>';
  window.CB_API_BASE = '<?= h(cb_url_mode() === 'query' ? site_base() . '/?r=' : site_base() . '/') ?>';
  window.CB_PAGE_BASE = window.CB_API_BASE;
  window.CB_CSRF = '<?= h(csrf_token()) ?>';
</script>
</head>
<body class="gateway-body flarum-gate">

<div class="flarum-gate__bg" aria-hidden="true"></div>

<div class="Modal Modal--small flarum-modal" role="dialog" aria-modal="true" aria-labelledby="modal-title">
  <div class="Modal-content">

    <a class="Modal-close" href="<?= h(url('')) ?>" aria-label="<?= L('关闭') ?>" title="<?= L('关闭') ?>">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
    </a>

    <?php if ($logoType === 'image' && $logoImg !== ''): ?>
      <div class="flarum-brand"><img src="<?= h($logoImg) ?>" alt="<?= h($siteName) ?>"></div>
    <?php else: ?>
      <div class="flarum-brand">
        <span class="flarum-brand__mark"><?= cb_icon('quill') ?></span>
        <span class="flarum-brand__text"><?= h($logoText) ?></span>
      </div>
    <?php endif; ?>

    <?php $fs = flash('success'); $fe = flash('error'); $fes = flash('errors'); ?>
    <?php if ($fs || $fe || (is_array($fes) && $fes)): ?>
      <div class="flarum-alerts">
        <?php if ($fs): ?><div class="flarum-alert flarum-alert--success"><?= h((string)$fs) ?></div><?php endif; ?>
        <?php if ($fe): ?><div class="flarum-alert flarum-alert--error"><?= h((string)$fe) ?></div><?php endif; ?>
        <?php if (is_array($fes) && $fes): ?>
          <div class="flarum-alert flarum-alert--error">
            <?php foreach ($fes as $m): ?><div><?= h((string)$m) ?></div><?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <?= $content ?? '' ?>

  </div>
</div>

<div class="flarum-gate__foot">
  <a href="<?= h(url('')) ?>">← <?= L('返回') ?><?= h($siteName) ?></a>
</div>

<script src="<?= h(theme_asset('js/app.js') . '?v=' . CB_VERSION) ?>"></script>
<!-- 图标 sprite：按需输出本页用到的 symbol -->
<?= cb_sprite() ?>
</body>
</html>
