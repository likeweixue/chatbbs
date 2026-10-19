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
    <div style="font-size:48px">🛠️</div>
    <h1 style="margin:12px 0"><?= L('站点维护中') ?></h1>
    <p class="text-muted"><?= h(config('closed_reason', L('站点维护中，请稍后再来。'))) ?></p>
  </div>
</div>
</body>
</html>
