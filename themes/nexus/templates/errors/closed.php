<!DOCTYPE html>
<html lang="zh-CN" data-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= L('站点维护中') ?></title>
<link rel="stylesheet" href="<?= h(theme_asset('css/style.css')) ?>">
</head>
<body class="gateway-body">
<div class="gateway-wrapper">
  <div class="gateway-card" style="text-align:center">
    <div style="color:var(--color-text-muted)"><svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg></div>
    <h1 style="margin:12px 0"><?= L('站点维护中') ?></h1>
    <p class="text-muted"><?= h(config('closed_reason', L('站点维护中，请稍后再来。'))) ?></p>
  </div>
</div>
</body>
</html>
