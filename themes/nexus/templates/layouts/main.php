<?php
$currentUser = $current_user ?? null;
$siteName = $site_name ?? config('site_name', 'ChatBBS');
$unread = $unread_notifications ?? 0;
$unreadMsg = $unread_messages ?? 0;
$menus = $menus ?? [];
$sidebarOn = ($sidebar_enable ?? 1) && (!isset($hide_sidebar) || !$hide_sidebar);
$side = $sidebar_side ?? 'right';

$logoType = (string)config('logo_type', 'text');
$logoImg = (string)config('site_logo', '');
$logoText = trim((string)config('logo_text', ''));
if ($logoText === '') {
    $logoText = $siteName;
}
$favicon = (string)config('site_favicon', '');
$slogan = (string)config('site_slogan', '');
if ($slogan === '') {
    $slogan = (string)config('site_description', '');
}

// 当前路径：用于导航高亮
$curPath = ltrim(trim((string)parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH), '/'), '/');
$reqQuery = (string)parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_QUERY);
$backPath = $curPath . ($reqQuery !== '' ? '?' . $reqQuery : '');
if (strlen($backPath) > 300) {
    $backPath = '';
}
$isActive = static function (string $murl) use ($curPath): bool {
    $u = trim($murl);
    if ($u === '' || str_starts_with($u, 'http')) { return false; }
    $u = ltrim($u, '/');
    if ($u === '') { return $curPath === '' || $curPath === 'index.php'; }
    return $curPath === $u || str_starts_with($curPath, $u . '/') || str_starts_with($curPath, $u . '?');
};
?>
<!DOCTYPE html>
<html lang="<?= h(\App\Core\Lang::current()) ?>" data-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= h(($title ?? '') !== '' ? $title . ' - ' . $siteName : $siteName) ?></title>
<meta name="description" content="<?= h(config('site_description', '')) ?>">
<meta name="keywords" content="<?= h(config('site_keywords', '')) ?>">
<link rel="icon" href="<?= h($favicon !== '' ? $favicon : asset('images/retro/favicon.svg')) ?>">
<link rel="stylesheet" href="<?= h(theme_asset('css/style.css') . '?v=' . CB_VERSION) ?>">
<?php
// 外观微调：NexusPHP 默认直角（0），后台可改
$radius = (int)config('theme_radius', 0);
$radius = max(0, min(24, $radius));
$radiusSm = (int)round($radius * 0.5);
$radiusLg = (int)round($radius * 1.5);
$primary = (string)config('theme_primary', '#1a4fa0');
if (!preg_match('/^#[0-9a-fA-F]{6}$/', $primary)) {
    $primary = '#1a4fa0';
}
$vars = "--radius-sm:{$radiusSm}px;--radius:{$radius}px;--radius-lg:{$radiusLg}px;"
    . "--color-primary:{$primary};--color-link:{$primary};--nx-head-bg:{$primary}";
?>
<style>:root{<?= $vars ?>}html[data-theme="dark"]{<?= $vars ?>}</style>
<script>
  window.CB_SITE_URL = '<?= h(site_base() . '/') ?>';
  window.CB_URL_MODE = '<?= h(cb_url_mode()) ?>';
  window.CB_API_BASE = '<?= h(cb_url_mode() === 'query' ? site_base() . '/?r=' : site_base() . '/') ?>';
  window.CB_PAGE_BASE = window.CB_API_BASE;
  window.CB_CSRF = '<?= h(csrf_token()) ?>';
  window.CB_REPLY_PREVIEW = <?= (int)config('reply_preview_count', 5) ?>;
  window.CB_LANG = <?= json_encode(\App\Core\Lang::jsMap(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
  window.cbT = function (s) {
    var m = window.CB_LANG;
    return (m && Object.prototype.hasOwnProperty.call(m, s)) ? m[s] : s;
  };
</script>
</head>
<body class="cb-body">

<div id="cb-header" class="nx-top">
<!-- ===== 顶部：Logo + 口号 ===== -->
<div class="nx-head-wrap">
  <div class="container nx-head">
    <a class="cb-logo" href="<?= h(url('')) ?>">
      <?php if ($logoType === 'image' && $logoImg !== ''): ?>
        <img src="<?= h($logoImg) ?>" alt="<?= h($siteName) ?>">
      <?php else: ?>
        <span class="cb-logo__mark"><?= cb_icon('quill') ?></span>
        <span class="cb-logo__text"><?= h($logoText) ?></span>
      <?php endif; ?>
    </a>
    <div class="nx-slogan"><?= h($slogan) ?></div>
    <div class="nx-head-right">
      <form class="cb-search" action="<?= h(url('search')) ?>" method="get">
        <input type="text" name="q" placeholder="<?= L('搜索…') ?>" autocomplete="off"
               value="<?= h(\App\Core\Request::get('q', '')) ?>">
      </form>
      <button class="cb-icon-btn" data-theme-toggle title="<?= L('切换暗色模式') ?>">
        <span class="ico-light"><?= cb_icon('sun') ?></span>
        <span class="ico-dark"><?= cb_icon('moon') ?></span>
      </button>
      <?php
      $langList = \App\Core\Lang::available();
      $curLang  = \App\Core\Lang::current();
      $langOn   = count($langList) > 1 && (int)config('lang_switch', 1) === 1;
      ?>
      <?php if ($langOn): ?>
      <div class="cb-dropdown">
        <button class="cb-icon-btn" id="btn-lang" title="Language / <?= L('语言') ?>" aria-haspopup="true"><?= cb_icon('globe') ?></button>
        <div class="cb-dropdown__menu cb-lang-menu" id="menu-lang">
          <div class="head"><span><?= L('语言') ?> / Language</span></div>
          <div class="list">
            <?php foreach ($langList as $code => $name): ?>
              <a class="item <?= $curLang === $code ? 'on' : '' ?>"
                 href="<?= h(url('lang/' . rawurlencode((string)$code)) . ($backPath !== '' ? '?back=' . rawurlencode($backPath) : '')) ?>">
                <span class="cb-lang-menu__code"><?= h(strtoupper(substr((string)$code, 0, 2))) ?></span>
                <span style="flex:1"><?= h($name) ?></span>
                <?php if ($curLang === $code): ?><span class="cb-lang-menu__tick">✓</span><?php endif; ?>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <?php endif; ?>
      <button class="cb-burger" id="cb-burger" type="button" aria-label="<?= L('展开菜单') ?>" aria-expanded="false">
        <span></span><span></span><span></span>
      </button>
    </div>
  </div>
</div>

<!-- ===== 导航条（nav_block） ===== -->
<div class="nx-nav-wrap">
  <div class="container">
    <nav class="cb-nav" id="cb-nav">
      <a href="<?= h(url('')) ?>" class="<?= ($curPath === '' || $curPath === 'index.php') ? 'is-selected' : '' ?>"><?= L('首页') ?></a>
      <?php foreach ($menus as $m): ?>
        <?php
        $murl = (string)$m['url'];
        $href = str_starts_with($murl, 'http') ? $murl : url(ltrim($murl, '/'));
        ?>
        <a href="<?= h($href) ?>" target="<?= h($m['target']) ?>" class="<?= $isActive($murl) ? 'is-selected' : '' ?>"><?= h($m['title']) ?></a>
      <?php endforeach; ?>
      <?php if (is_admin()): ?>
        <a href="<?= h(url('admin')) ?>"><?= L('管理后台') ?></a>
      <?php endif; ?>
    </nav>
  </div>
</div>
</div><!-- /#cb-header -->
<div class="cb-drawer-mask" id="cb-drawer-mask"></div>

<!-- ===== 用户信息条（info_block） ===== -->
<div class="container">
  <div class="cb-info-block">
    <div class="nx-info-left">
      <?php if ($currentUser): ?>
        <span><?= L('欢迎回来') ?>, <b><a href="<?= h(url('user/' . $currentUser['id'])) ?>"><?= h($currentUser['username'] ?? ($currentUser['name'] ?? 'user')) ?></a></b></span>
        <span class="sep">|</span><a href="<?= h(url('logout')) ?>"><?= L('退出') ?></a>
        <span class="sep">|</span><a href="<?= h(url('settings')) ?>"><?= L('设置') ?></a>
        <span class="sep">|</span><a href="<?= h(url('checkin')) ?>"><?= L('签到') ?></a>
        <span class="sep">|</span><a href="<?= h(url('notifications')) ?>"><?= L('通知') ?><?php if ($unread > 0): ?> <b class="text-danger"><?= (int)$unread ?></b><?php endif; ?></a>
        <span class="sep">|</span><a href="<?= h(url('chat')) ?>"><?= L('消息') ?><?php if ($unreadMsg > 0): ?> <b class="text-danger"><?= (int)$unreadMsg ?></b><?php endif; ?></a>
        <span class="sep">|</span><?= h(currency_name()) ?> <b><?= h(money((float)($currentUser['money'] ?? 0))) ?></b>
        <?php if (is_admin()): ?>
          <span class="sep">|</span><a href="<?= h(url('admin')) ?>"><?= L('管理后台') ?></a>
        <?php endif; ?>
      <?php else: ?>
        <span><?= L('欢迎来到') ?> <?= h($siteName) ?></span>
        <span class="sep">|</span><a href="<?= h(url('login')) ?>"><?= L('登录') ?></a>
        <span class="sep">|</span><a href="<?= h(url('register')) ?>"><?= L('注册') ?></a>
      <?php endif; ?>
    </div>
    <div class="nx-info-right">
      <?php if ($currentUser): ?>
        <div class="cb-dropdown">
          <button class="cb-icon-btn" id="btn-notifications" title="<?= L('通知') ?>">
            <?= cb_icon('bell') ?>
            <?php if ($unread > 0): ?><span class="cb-badge"><?= (int)$unread ?></span><?php endif; ?>
          </button>
          <div class="cb-dropdown__menu" id="menu-notifications">
            <div class="head"><span><?= L('通知') ?></span><a href="<?= h(url('notifications')) ?>" class="text-sm"><?= L('全部') ?></a></div>
            <div class="list"><div class="cb-empty" style="padding:24px"><?= L('加载中…') ?></div></div>
            <div class="foot">
              <form method="post" action="<?= h(url('notifications/read-all')) ?>" style="margin:0">
                <?= csrf_field() ?>
                <button class="cb-btn sm" type="submit"><?= L('全部标记为已读') ?></button>
              </form>
            </div>
          </div>
        </div>
        <a class="cb-icon-btn" id="btn-messages" href="<?= h(url('chat')) ?>" title="<?= L('聊天') ?>">
          <?= cb_icon('mail') ?>
          <?php if ($unreadMsg > 0): ?><span class="cb-badge"><?= (int)$unreadMsg ?></span><?php endif; ?>
        </a>
      <?php endif; ?>
      <?php if ($sidebarOn): ?>
        <button class="cb-icon-btn cb-sidebar-toggle-btn" id="btn-sidebar-toggle" title="<?= L('显示 / 隐藏侧边栏') ?>">
          <?= cb_icon('panel-right') ?>
        </button>
        <button class="cb-icon-btn" id="btn-sidebar-side" title="<?= L('切换侧边栏到左侧 / 右侧') ?>">
          <?= cb_icon('panel-left') ?>
        </button>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php $flashSuccess = flash('success'); $flashError = flash('error'); $flashErrors = flash('errors'); ?>
<div class="container">
  <?php if ($flashSuccess): ?><div class="cb-alert success" style="margin-top:10px"><?= h((string)$flashSuccess) ?></div><?php endif; ?>
  <?php if ($flashError): ?><div class="cb-alert error" style="margin-top:10px"><?= h((string)$flashError) ?></div><?php endif; ?>
  <?php if (is_array($flashErrors) && $flashErrors): ?>
    <div class="cb-alert error" style="margin-top:10px">
      <?php foreach ($flashErrors as $f => $m): ?><div><?= h((string)$m) ?></div><?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<main class="cb-main container <?= $side === 'left' ? 'sidebar-left' : '' ?><?= $sidebarOn ? '' : ' sidebar-hidden' ?>">
  <div class="cb-content">
    <?= $content ?? '' ?>
  </div>

  <?php if ($sidebarOn): ?>
    <?= \App\Core\View::partial('partials/sidebar', get_defined_vars()) ?>
  <?php endif; ?>
</main>

<footer class="cb-footer">
  <div class="cb-footer__inner">
    <div>
      <div>(c) <a href="<?= h(url('')) ?>"><?= h($siteName) ?></a> <?= date('Y') ?> · Powered by <b>ChatBBS</b> v<?= CB_VERSION ?></div>
      <div class="text-xs" style="margin-top:4px"><?= h(config('site_footer', '')) ?></div>
    </div>
    <div class="cb-footer__meta">
      <?php if (config('site_icp')): ?><span><?= h(config('site_icp')) ?></span><?php endif; ?>
      <a class="cb-footer__license" href="<?= h(url('license')) ?>"><?= cb_icon('quill') ?><?= L('授权') ?></a>
      <span><?= L('主题') ?>：NexusPHP 经典</span>
      <span><?= L('页面耗时') ?> <?= number_format((microtime(true) - ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true))) * 1000, 1) ?> ms</span>
    </div>
  </div>
</footer>

<script src="<?= h(theme_asset('js/app.js') . '?v=' . CB_VERSION) ?>"></script>
<?php
$needEditor = !empty($load_editor) || str_contains((string)($content ?? ''), 'data-editor');
?>
<?php if ($needEditor): ?>
<?php cb_icon_preload(cb_editor_icons()); ?>
<script src="<?= h(theme_asset('js/editor.js') . '?v=' . CB_VERSION) ?>"></script>
<?php endif; ?>
<?= config('footer_code', '') ?>
<!-- 图标 sprite：按需输出本页用到的 symbol（放在最后，此时所有图标已登记） -->
<?= cb_sprite() ?>
</body>
</html>
