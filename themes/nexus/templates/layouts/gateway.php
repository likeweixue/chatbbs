<?php
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
<html lang="zh-CN" data-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= h(($title ?? '') !== '' ? $title . ' - ' . $siteName : $siteName) ?></title>
<link rel="icon" href="<?= h($favicon !== '' ? $favicon : asset('images/retro/favicon.svg')) ?>">
<link rel="stylesheet" href="<?= h(theme_asset('css/style.css') . '?v=' . CB_VERSION) ?>">
<script>
  window.CB_SITE_URL = '<?= h(site_base() . '/') ?>';
  window.CB_URL_MODE = '<?= h(cb_url_mode()) ?>';
  window.CB_API_BASE = '<?= h(cb_url_mode() === 'query' ? site_base() . '/?r=' : site_base() . '/') ?>';
  window.CB_PAGE_BASE = window.CB_API_BASE;
  window.CB_CSRF = '<?= h(csrf_token()) ?>';
</script>
</head>
<body class="gateway-body">
<div class="gateway-wrapper">
  <div class="gateway-logo">
    <a href="<?= h(url('')) ?>">
      <?php if ($logoType === 'image' && $logoImg !== ''): ?>
        <img src="<?= h($logoImg) ?>" alt="<?= h($siteName) ?>" style="height:44px;max-width:260px;object-fit:contain">
      <?php else: ?>
        <span class="cb-logo"><span class="cb-logo__mark"><?= cb_icon('quill', 'cb-ico-lg') ?></span>
        <span class="cb-logo__text" style="font-size:26px"><?= h($logoText) ?></span></span>
      <?php endif; ?>
    </a>
  </div>

  <div class="gateway-card">
    <?php $fs = flash('success'); $fe = flash('error'); $fes = flash('errors'); ?>
    <?php if ($fs): ?><div class="cb-alert success"><?= h((string)$fs) ?></div><?php endif; ?>
    <?php if ($fe): ?><div class="cb-alert error"><?= h((string)$fe) ?></div><?php endif; ?>
    <?php if (is_array($fes) && $fes): ?>
      <div class="cb-alert error"><?php foreach ($fes as $m): ?><div><?= h((string)$m) ?></div><?php endforeach; ?></div>
    <?php endif; ?>
    <?= $content ?? '' ?>
  </div>

  <div class="gateway-back">
    <a href="<?= h(url('')) ?>" style="color:var(--color-text-muted)">← 返回<?= h($siteName) ?></a>
  </div>
</div>
<script src="<?= h(theme_asset('js/app.js') . '?v=' . CB_VERSION) ?>"></script>
<!-- 图标 sprite：按需输出本页用到的 symbol -->
<?= cb_sprite() ?>
</body>
</html>
