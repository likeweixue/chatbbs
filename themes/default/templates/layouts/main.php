<?php
$currentUser = $current_user ?? null;
$siteName = $site_name ?? config('site_name', 'ChatBBS');
$unread = $unread_notifications ?? 0;
$unreadMsg = $unread_messages ?? 0;
$menus = $menus ?? [];
$sidebarOn = ($sidebar_enable ?? 1) && (!isset($hide_sidebar) || !$hide_sidebar);
$side = $sidebar_side ?? 'right';
// 侧边栏默认折叠：未手动切换过的访客先看到完整内容区
$sidebarDefaultHidden = (int)config('sidebar_default_hidden', 1) === 1;
$sidebarCollapsed = !$sidebarOn || $sidebarDefaultHidden;
$island = (($site_layout ?? config('site_layout', 'classic')) === 'island' && \App\Core\View::theme() === 'default');
$islandSide = (string)config('island_sidebar_side', 'left') === 'right' ? 'right' : 'left';
$islandWidth = max(200, min(360, (int)config('island_sidebar_width', 240)));
$islandDefaultHidden = (int)config('island_sidebar_default_hidden', 0) === 1;
$diyImage = trim((string)config('diy_image', ''));
$diyPos = (string)config('diy_position', 'top');
$diyPos = in_array($diyPos, ['top', 'bottom', 'global'], true) ? $diyPos : 'top';
$diyOpacity = max(0, min(100, (int)config('diy_opacity', 20))) / 100;
$diyOpacityCss = number_format($diyOpacity, 2, '.', '');
$diyFeather = max(0, min(300, (int)config('diy_feather', 60)));
$theme = \App\Core\View::theme();
$mainClass = 'container cb-main';
if ($island) {
    $mainClass .= ' cb-main--island';
    if ($islandSide === 'right') {
        $mainClass .= ' island-side-right';
    }
    if ($islandDefaultHidden) {
        $mainClass .= ' island-nav-hidden';
    }
} else {
    if ($side === 'left') {
        $mainClass .= ' sidebar-left';
    }
    if ($sidebarCollapsed) {
        $mainClass .= ' sidebar-hidden';
    }
}

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
<meta name="description" content="<?= h(config('site_description', '')) ?>">
<meta name="keywords" content="<?= h(config('site_keywords', '')) ?>">
<link rel="icon" href="<?= h($favicon !== '' ? $favicon : asset('images/retro/favicon.svg')) ?>">
<?php if ((int)config('rss_enable', 1) === 1): ?>
<link rel="alternate" type="application/rss+xml" title="<?= h($siteName) ?> RSS" href="<?= h(url('rss')) ?>">
<?php endif; ?>
<link rel="stylesheet" href="<?= h(theme_asset('css/style.css') . '?v=' . CB_VERSION) ?>">
<?php
// 外观微调：圆角大小 / 主题主色（后台「扩展管理 → 主题 → 外观微调」设置）
$radius = (int)config('theme_radius', 8);
$radius = max(0, min(24, $radius));
$radiusSm = (int)round($radius * 0.5);
$radiusLg = (int)round($radius * 1.5);
$primary = (string)config('theme_primary', '#4D698E');
if (!preg_match('/^#[0-9a-fA-F]{6}$/', $primary)) {
    $primary = '#4D698E';
}
$headerBg = (string)config('theme_header_bg', '#ffffff');
$headerText = (string)config('theme_header_text', '#1f2329');
$headerOpacity = max(0, min(100, (int)config('theme_header_opacity', 100)));
$islandBg = (string)config('theme_island_bg', '#ffffff');
$islandText = (string)config('theme_island_text', '#1f2329');
$islandOpacity = max(0, min(100, (int)config('theme_island_opacity', 100)));
$footerBg = (string)config('theme_footer_bg', '#ffffff');
$footerText = (string)config('theme_footer_text', '#4e5969');
$footerOpacity = max(0, min(100, (int)config('theme_footer_opacity', 100)));
if (!preg_match('/^#[0-9a-fA-F]{6}$/', $headerBg)) { $headerBg = '#ffffff'; }
if (!preg_match('/^#[0-9a-fA-F]{6}$/', $headerText)) { $headerText = '#1f2329'; }
if (!preg_match('/^#[0-9a-fA-F]{6}$/', $islandBg)) { $islandBg = '#ffffff'; }
if (!preg_match('/^#[0-9a-fA-F]{6}$/', $islandText)) { $islandText = '#1f2329'; }
if (!preg_match('/^#[0-9a-fA-F]{6}$/', $footerBg)) { $footerBg = '#ffffff'; }
if (!preg_match('/^#[0-9a-fA-F]{6}$/', $footerText)) { $footerText = '#4e5969'; }
$vars = "--radius-sm:{$radiusSm}px;--radius:{$radius}px;--radius-lg:{$radiusLg}px;"
    . "--color-primary:{$primary};--color-link:{$primary};";
// 只在用户真正改过颜色时输出覆盖变量，避免默认白色把暗色模式顶栏/页脚刷白
if ($headerBg !== '#ffffff' || $headerText !== '#1f2329' || $headerOpacity !== 100) {
    $vars .= "--cb-header-bg:" . cb_hex_rgba($headerBg, $headerOpacity) . ";--cb-header-text:{$headerText};";
}
if ($islandBg !== '#ffffff' || $islandText !== '#1f2329' || $islandOpacity !== 100) {
    $vars .= "--cb-island-bg:" . cb_hex_rgba($islandBg, $islandOpacity) . ";--cb-island-text:{$islandText};";
}
if ($footerBg !== '#ffffff' || $footerText !== '#4e5969' || $footerOpacity !== 100) {
    $vars .= "--cb-footer-bg:" . cb_hex_rgba($footerBg, $footerOpacity) . ";--cb-footer-text:{$footerText};";
}
?>
<style>:root{<?= $vars ?>}html[data-theme="dark"]{<?= $vars ?>}</style>
<script>
  window.CB_SITE_URL = '<?= h(site_base() . '/') ?>';
  window.CB_URL_MODE = '<?= h(cb_url_mode()) ?>';
  /* 接口 / 页面地址前缀：兼容模式下为 /?r= ，伪静态模式下为 / */
  window.CB_API_BASE = '<?= h(cb_url_mode() === 'query' ? site_base() . '/?r=' : site_base() . '/') ?>';
  window.CB_PAGE_BASE = window.CB_API_BASE;
  window.CB_CSRF = '<?= h(csrf_token()) ?>';
  window.CB_REPLY_PREVIEW = <?= (int)config('reply_preview_count', 5) ?>;
  /* 界面文案字典：中文为空对象（直接返回原文），其它语言才有内容。
     只翻译界面名词，用户产生的内容不走这里。 */
  window.CB_LANG = <?= json_encode(\App\Core\Lang::jsMap(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
  window.cbT = function (s) {
    var m = window.CB_LANG;
    return (m && Object.prototype.hasOwnProperty.call(m, s)) ? m[s] : s;
  };
</script>
</head>
<body class="cb-body<?= $island ? ' cb-island' : '' ?><?= $diyImage !== '' ? ' cb-has-diy' : '' ?>">
<?php if ($diyImage !== '' && $diyPos === 'global'): ?>
<div class="cb-diy-global" style="background-image:url('<?= h($diyImage) ?>');opacity:<?= $diyOpacityCss ?>" aria-hidden="true"></div>
<?php endif; ?>

<header class="cb-header" id="cb-header">
  <div class="cb-header__inner">
    <?php if (!empty($back_url)): ?>
      <a class="cb-burger cb-back-btn" href="<?= h($back_url) ?>" aria-label="<?= L('返回') ?>" title="<?= L('返回') ?>">
        <?= cb_icon('arrow-left') ?>
      </a>
    <?php else: ?>
      <button class="cb-burger" id="cb-burger" type="button" aria-label="<?= L('展开菜单') ?>" aria-expanded="false">
        <span></span><span></span><span></span>
      </button>
    <?php endif; ?>

    <a class="cb-logo" href="<?= h(url('')) ?>">
      <?php if ($logoType === 'image' && $logoImg !== ''): ?>
        <img src="<?= h($logoImg) ?>" alt="<?= h($siteName) ?>">
      <?php else: ?>
        <span class="cb-logo__text"><?= h($logoText) ?></span>
      <?php endif; ?>
    </a>

    <div class="cb-header__main" id="cb-drawer">
      <div class="cb-drawer__head">
        <span class="cb-drawer__title"><?= h($siteName) ?></span>
        <button class="cb-drawer__close" id="cb-drawer-close" type="button" aria-label="<?= L('关闭菜单') ?>"><?= cb_icon('close') ?></button>
      </div>

      <nav class="cb-nav">
        <?php foreach ($menus as $m): ?>
          <a href="<?= h(str_starts_with((string)$m['url'], 'http') ? $m['url'] : url(ltrim((string)$m['url'], '/'))) ?>"
             target="<?= h($m['target']) ?>"><?= h($m['title']) ?></a>
        <?php endforeach; ?>
        <?php if (is_admin()): ?>
          <a href="<?= h(url('admin')) ?>"><?= L('管理后台') ?></a>
        <?php endif; ?>
      </nav>

      <div class="cb-header__right">
        <form class="cb-search" action="<?= h(url('search')) ?>" method="get">
          <input type="text" name="q" placeholder="<?= L('搜索…') ?>" autocomplete="off"
                 value="<?= h(\App\Core\Request::get('q', '')) ?>">
        </form>

        <div class="cb-dropdown cb-theme-menu">
          <button class="cb-icon-btn" id="btn-theme" type="button" title="<?= L('外观 / 色调') ?>" aria-haspopup="true">
            <?= cb_icon('shirt') ?>
          </button>
          <div class="cb-dropdown__menu" id="menu-theme">
            <div class="head"><span><?= cb_icon('shirt') ?> <?= L('外观 / 色调') ?></span></div>
            <div class="cb-theme-menu__list">
              <div class="cb-theme-menu__label"><?= L('明暗') ?></div>
              <button type="button" class="cb-theme-choice" data-theme-mode="light">☀ <?= L('明亮') ?></button>
              <button type="button" class="cb-theme-choice" data-theme-mode="dark">☾ <?= L('暗色') ?></button>
              <button type="button" class="cb-theme-choice" data-theme-mode="auto">◐ <?= L('跟随系统') ?></button>
              <div class="cb-theme-menu__label"><?= L('色调') ?></div>
              <button type="button" class="cb-theme-choice" data-palette="default"><?= L('默认') ?></button>
              <button type="button" class="cb-theme-choice" data-palette="green"><?= L('绿色护眼纸') ?></button>
              <button type="button" class="cb-theme-choice" data-palette="retro"><?= L('黄色复古') ?></button>
              <button type="button" class="cb-theme-choice" data-palette="blackgold"><?= L('黑金') ?></button>
              <button type="button" class="cb-theme-choice" data-palette="orange"><?= L('橙黄') ?></button>
            </div>
          </div>
        </div>

        <?php
        // 语言切换：下拉列出可用语言，切换后回到当前页
        $langList = \App\Core\Lang::available();
        $curLang  = \App\Core\Lang::current();
        $langOn   = count($langList) > 1 && (int)config('lang_switch', 1) === 1;
        $reqUri   = (string)($_SERVER['REQUEST_URI'] ?? '');
        $backPath = trim((string)parse_url($reqUri, PHP_URL_PATH), '/');
        $backQry  = (string)parse_url($reqUri, PHP_URL_QUERY);
        if ($backQry !== '') {
            $backPath .= '?' . $backQry;
        }
        if (strlen($backPath) > 300) {
            $backPath = '';
        }
        ?>
        <?php if ($langOn): ?>
        <div class="cb-dropdown">
          <button class="cb-icon-btn" id="btn-lang" title="Language / <?= L('语言') ?>" aria-haspopup="true"><?= cb_icon('globe') ?></button>
          <div class="cb-dropdown__menu cb-lang-menu" id="menu-lang">
            <div class="head"><span><?= cb_icon('globe') ?> <?= L('语言') ?> / Language</span></div>
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

          <a href="<?= h(url('user/' . $currentUser['id'])) ?>" title="<?= L('我的主页') ?>">
            <img class="cb-avatar cb-avatar-sm" src="<?= h(avatar_url($currentUser)) ?>" alt="">
          </a>
        <?php else: ?>
          <a class="cb-btn sm" href="<?= h(url('login')) ?>"><?= L('登录') ?></a>
          <a class="cb-btn sm primary" href="<?= h(url('register')) ?>"><?= L('注册') ?></a>
        <?php endif; ?>

        <?php if ($island): ?>
          <button class="cb-icon-btn" id="island-nav-toggle" data-island-hide type="button"
                  aria-expanded="<?= $islandDefaultHidden ? 'false' : 'true' ?>" title="<?= L('显示 / 隐藏侧栏') ?>" aria-label="<?= L('显示 / 隐藏侧栏') ?>">
            <?= cb_icon('panel-left') ?>
          </button>
          <button class="cb-icon-btn" id="island-nav-side" data-island-side type="button" title="<?= L('切换侧栏位置') ?>" aria-label="<?= L('切换侧栏位置') ?>">
            <?= cb_icon('panel-right') ?>
          </button>
        <?php endif; ?>

        <?php if ($sidebarOn && !$island): ?>
          <button class="cb-icon-btn cb-sidebar-toggle-btn <?= $sidebarCollapsed ? 'off' : '' ?>" id="btn-sidebar-toggle" title="<?= L('显示 / 隐藏侧边栏') ?>">
            <?= cb_icon('panel-right') ?>
          </button>
          <button class="cb-icon-btn" id="btn-sidebar-side" title="<?= L('切换侧边栏到左侧 / 右侧') ?>">
            <?= cb_icon('panel-left') ?>
          </button>
        <?php endif; ?>
      </div>
    </div>

    <a class="cb-header__publish" href="<?= h(url('thread/create')) ?>" aria-label="<?= L('发布主题') ?>" title="<?= L('发布主题') ?>">
      <?= cb_icon('edit') ?>
    </a>
  </div>
</header>
<div class="cb-drawer-mask" id="cb-drawer-mask"></div>

<?php if ($diyImage !== '' && $diyPos === 'top'): ?>
<div class="cb-diy-banner cb-diy-banner--top" aria-hidden="true" style="--diy-feather:<?= (int)$diyFeather ?>px"><img src="<?= h($diyImage) ?>" alt="" style="opacity:<?= $diyOpacityCss ?>"></div>
<?php endif; ?>

<?php $flashSuccess = flash('success'); $flashError = flash('error'); $flashErrors = flash('errors'); ?>
<div class="container">
  <?php if ($flashSuccess): ?><div class="cb-alert success" style="margin-top:16px"><?= h((string)$flashSuccess) ?></div><?php endif; ?>
  <?php if ($flashError): ?><div class="cb-alert error" style="margin-top:16px"><?= h((string)$flashError) ?></div><?php endif; ?>
  <?php if (is_array($flashErrors) && $flashErrors): ?>
    <div class="cb-alert error" style="margin-top:16px">
      <?php foreach ($flashErrors as $f => $m): ?><div><?= h((string)$m) ?></div><?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<main class="<?= h($mainClass) ?>"
      data-sidebar-default="<?= $sidebarDefaultHidden ? 'hidden' : 'visible' ?>"
      <?php if ($island): ?>style="--island-nav-width:<?= (int)$islandWidth ?>px"
      data-island-default-width="<?= (int)$islandWidth ?>"<?php endif; ?>>
  <?php if ($island): ?>
  <script>
    // 岛屿布局：优先恢复访客本地偏好，避免服务端默认值造成闪动
    (function () {
      var main = document.querySelector('.cb-main--island');
      if (!main) return;
      try {
        var side = localStorage.getItem('cb_island_side');
        if (side === 'right') { main.classList.add('island-side-right'); }
        else if (side === 'left') { main.classList.remove('island-side-right'); }
        var w = parseInt(localStorage.getItem('cb_island_width'), 10);
        if (w >= 200 && w <= 360) { main.style.setProperty('--island-nav-width', w + 'px'); }
        var h = localStorage.getItem('cb_island_hidden');
        if (h === '1') { main.classList.add('island-nav-hidden'); }
        else if (h === '0') { main.classList.remove('island-nav-hidden'); }
        var btn = document.getElementById('island-nav-toggle');
        if (btn) { btn.setAttribute('aria-expanded', main.classList.contains('island-nav-hidden') ? 'false' : 'true'); }
      } catch (e) {}
    })();
  </script>
  <?php endif; ?>
  <?php if ($sidebarOn && !$island): ?>
  <script>
    // 恢复访客上次手动选择的侧栏状态，避免服务端默认值与本地偏好不一致时闪动
    (function () {
      try {
        var main = document.querySelector('.cb-main');
        var v = localStorage.getItem('cb_sidebar_hidden');
        if (!main || v === null) { return; }
        main.classList.toggle('sidebar-hidden', v === '1');
        var btn = document.getElementById('btn-sidebar-toggle');
        if (btn) { btn.classList.toggle('off', v === '1'); }
      } catch (e) {}
    })();
  </script>
  <?php endif; ?>
  <?php if ($island): ?>
    <?= \App\Core\View::partial('partials/island_nav', get_defined_vars()) ?>
    <div class="cb-island-resizer" id="island-resizer" role="separator" aria-orientation="vertical"
         aria-label="<?= L('拖拽调整侧栏宽度') ?>" aria-valuemin="200" aria-valuemax="360"
         aria-valuenow="<?= (int)$islandWidth ?>" tabindex="0"></div>
  <?php endif; ?>

  <div class="cb-content">
    <?php if ($island && !empty($island_tabs)): ?>
      <?= \App\Core\View::partial('partials/island_tabs', ['tabs' => $island_tabs]) ?>
    <?php endif; ?>
    <?= $content ?? '' ?>
  </div>

  <?php if ($sidebarOn && !$island): ?>
    <?= \App\Core\View::partial('partials/sidebar', get_defined_vars()) ?>
  <?php endif; ?>
</main>

<footer class="cb-footer<?= ($diyImage !== '' && $diyPos === 'bottom') ? ' cb-footer--diy' : '' ?>"
        <?php if ($diyImage !== '' && $diyPos === 'bottom'): ?>style="--diy-image:url('<?= h($diyImage) ?>');--diy-opacity:<?= $diyOpacityCss ?>;--diy-feather:<?= (int)$diyFeather ?>px"<?php endif; ?>>
  <div class="cb-footer__inner">
    <div>
      <div><?= h($siteName) ?> · ChatBBS v<?= CB_VERSION ?></div>
      <div class="text-xs" style="margin-top:4px"><?= h(config('site_footer', '')) ?></div>
    </div>
    <div class="cb-footer__meta">
      <?php if (config('site_icp')): ?><span><?= h(config('site_icp')) ?></span><?php endif; ?>
      <a class="cb-footer__github" href="https://github.com/likeweixue/chatbbs" target="_blank" rel="noopener"
         title="GitHub 仓库" aria-label="GitHub 仓库"><?= cb_icon('github') ?><span>GitHub</span></a>
      <?php if ((int)config('rss_enable', 1) === 1): ?>
        <a class="cb-footer__github cb-footer__rss" href="<?= h(url('rss')) ?>" title="RSS 订阅" aria-label="RSS 订阅">
          <?= cb_icon('rss') ?><span>RSS</span>
        </a>
      <?php endif; ?>
      <a class="cb-footer__license" href="<?= h(url('license')) ?>"><?= L('授权') ?></a>
      <span>页面耗时 <?= number_format((microtime(true) - ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true))) * 1000, 1) ?> ms</span>
    </div>
  </div>
</footer>

<script src="<?= h(theme_asset('js/app.js') . '?v=' . CB_VERSION) ?>"></script>
<?php
// 编辑器脚本：页面内容里出现 data-editor 即加载（模板内定义的 $load_editor 不会回传到这里）
$needEditor = !empty($load_editor) || str_contains((string)($content ?? ''), 'data-editor');
?>
<?php if ($needEditor): ?>
<?php cb_icon_preload(cb_editor_icons()); ?>
<script src="<?= h(theme_asset('js/editor.js') . '?v=' . CB_VERSION) ?>"></script>
<?php endif; ?>
<?= config('footer_code', '') ?>
<?php if ($currentUser): ?><?php cb_icon_preload(['bell', 'thumbs-up', 'comment', 'reply', 'mail', 'shield', 'star', 'calendar', 'user']); ?><?php endif; ?>
<!-- 图标 sprite：按需输出本页用到的 symbol（放在最后，此时所有图标已登记） -->
<?= cb_sprite() ?>
</body>
</html>
