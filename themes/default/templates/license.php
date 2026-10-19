<?php
/**
 * 授权证书页
 *
 * 自动显示当前安装的域名为被授权方，声明该 ChatBBS 副本获得免费授权。
 * 风格参照 Discuz! 授权证书页。
 */
$lang = $lang ?? 'cn';
$host = trim((string)($host ?? ''));
if ($host === '') {
    $host = parse_url((string)site_base(), PHP_URL_HOST) ?: '';
}
$siteName = config('site_name', 'ChatBBS');
$official = 'https://chatbbs.cn';
$langUrl = static fn (string $l): string => h(url('license') . '?lang=' . $l);

if ($lang === 'en') {
    $cTitle = 'License Certificate';
    $cBody1 = 'Developed by <strong>WorkBuddy</strong> and <strong>likeweixue</strong> (Ma Changsheng).';
    $cBody2 = 'Official site: <a href="' . h($official) . '" target="_blank" rel="noopener">chatbbs.cn</a>';
    $cBody3 = 'On the basis of strict compliance with all applicable laws and regulations and the '
        . '<a href="' . h($official) . '/?r=license" target="_blank" rel="noopener">ChatBBS License Agreement</a>, '
        . 'your website <span class="cb-license__host">' . h($host) . '</span> is hereby granted a '
        . '<span class="cb-license__free">free license</span> to use ' . h($siteName) . '.';
    $cFoot = 'WorkBuddy &middot; likeweixue';
    $back = 'Back to ' . $siteName;
} else {
    $cTitle = L('授权证书');
    $cBody1 = 'ChatBBS 由 <strong>WorkBuddy</strong> 与 <strong>likeweixue</strong>（麻昌生）共同开发。';
    $cBody2 = '官方网站：<a href="' . h($official) . '" target="_blank" rel="noopener">chatbbs.cn</a>';
    $cBody3 = '在严格遵守国家各项法律法规及'
        . '<a href="' . h($official) . '/?r=license" target="_blank" rel="noopener">《ChatBBS 授权协议》</a>'
        . '的基础上，您的网站 <span class="cb-license__host">' . h($host) . '</span> 默认获得 '
        . '<span class="cb-license__free">免费授权</span>，即可以正常使用本程序搭建社区。';
    $cFoot = L('WorkBuddy &middot; likeweixue（麻昌生）');
    $back = L('返回 ') . $siteName;
}
?>
<div class="cb-license">
  <div class="cb-license__card">
    <div class="cb-license__logo">
      <span class="cb-logo__mark"><?= cb_icon('quill', 'cb-ico-lg') ?></span>
      <span class="cb-license__brand"><?= h($siteName) ?></span>
      <span class="cb-license__ver">v<?= CB_VERSION ?></span>
    </div>

    <h1 class="cb-license__title"><?= $cTitle ?></h1>

    <div class="cb-license__content">
      <p><?= $cBody1 ?></p>
      <p><?= $cBody2 ?></p>
      <p class="cb-license__grant"><?= $cBody3 ?></p>
    </div>

    <div class="cb-license__meta">
      <div><span><?= $lang === 'en' ? 'Licensee' : L('被授权站点') ?></span><strong><?= h($host) ?></strong></div>
      <div><span><?= $lang === 'en' ? 'Version' : L('程序版本') ?></span><strong><?= CB_VERSION ?></strong></div>
      <div><span><?= $lang === 'en' ? 'Grant time' : L('授权时间') ?></span><strong><?= date('Y-m-d') ?></strong></div>
    </div>

    <div class="cb-license__footer"><?= $cFoot ?></div>
  </div>

  <div class="cb-license__lang">
    <a href="<?= $langUrl('cn') ?>" class="<?= $lang === 'cn' ? 'on' : '' ?>"><?= L('中文') ?></a>
    <span>|</span>
    <a href="<?= $langUrl('en') ?>" class="<?= $lang === 'en' ? 'on' : '' ?>">English</a>
  </div>

  <div class="cb-license__back">
    <a href="<?= h(url('')) ?>">&larr; <?= h($back) ?></a>
  </div>
</div>
