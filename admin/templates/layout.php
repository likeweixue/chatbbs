<?php
/**
 * 后台布局：左侧菜单 + 右侧内容
 * 变量：$content, $menu, $current_key, $admin_user, $pending_reports, $pending_threads
 */
$menu = $menu ?? [];
$currentKey = (string)($current_key ?? '');
$pageTitle = (string)($title ?? '管理后台');
$adminUser = $admin_user ?? \App\Core\Auth::user();
$reports = (int)($pending_reports ?? 0);
$threads = (int)($pending_threads ?? 0);
$admPrimary = strtoupper(trim((string)config('theme_primary', '#4D698E')));
if (!preg_match('/^#[0-9A-F]{6}$/', $admPrimary)) { $admPrimary = '#4D698E'; }
$admR = hexdec(substr($admPrimary, 1, 2));
$admG = hexdec(substr($admPrimary, 3, 2));
$admB = hexdec(substr($admPrimary, 5, 2));
$admPrimaryDark = sprintf('#%02x%02x%02x', (int)round($admR * .82), (int)round($admG * .82), (int)round($admB * .82));
$admRadius = max(0, min(8, (int)config('admin_radius', 0)));

// 定位当前菜单分组与名称
$activeGroup = '';
$activeLabel = '管理后台';
$activeGroupLabel = '';
foreach ($menu as $gk => $group) {
    foreach ($group['items'] as $it) {
        if ($it['key'] === $currentKey) {
            $activeGroup = $gk;
            $activeLabel = $it['label'];
            $activeGroupLabel = (string)$group['label'];
            break 2;
        }
    }
}
if ($activeGroup === '' && isset($menu['dashboard'])) {
    $activeGroup = 'dashboard';
    $activeGroupLabel = (string)($menu['dashboard']['label'] ?? '仪表盘');
}

// 顶栏搜索索引：菜单 + 关键词补充表
$admSearchIndex = [];
$kwMap = \App\Admin\BaseAdmin::searchKeywords();
foreach ($menu as $gk => $group) {
    foreach ($group['items'] as $it) {
        $admSearchIndex[] = [
            'label' => (string)$it['label'],
            'group' => (string)$group['label'],
            'url' => admin_url((string)$it['url']),
            'kw' => mb_strtolower((string)$it['label'] . ' ' . $group['label'] . ' ' . ($kwMap[$it['key']] ?? '')),
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title><?= h($pageTitle) ?> - <?= h((string)config('site_name', 'ChatBBS')) ?> 管理后台</title>
    <script>try { if (localStorage.getItem('adm_theme') === 'dark') { document.documentElement.dataset.admTheme = 'dark'; } } catch (e) {}</script>
    <link rel="stylesheet" href="<?= h(admin_asset('css/admin.css') . '?v=' . CB_VERSION) ?>">
    <style>:root{--adm-primary:<?= h($admPrimary) ?>;--adm-primary-dark:<?= h($admPrimaryDark) ?>;--adm-side-active:<?= h($admPrimary) ?>;--adm-radius:<?= (int)$admRadius ?>px;}</style>
</head>
<body>
<div class="adm-wrap">
    <aside class="adm-side adm-side--context">
        <nav class="adm-context-nav">
            <?php foreach (($menu[$activeGroup]['items'] ?? []) as $it): ?>
                <?php
                $badge = 0;
                if ($it['key'] === 'reports') { $badge = $reports; }
                elseif ($it['key'] === 'threads') { $badge = $threads; }
                ?>
                <a class="adm-context-item<?= $it['key'] === $currentKey ? ' active' : '' ?>"
                   href="<?= h(admin_url((string)$it['url'])) ?>">
                    <span><?= h((string)$it['label']) ?></span>
                    <?php if ($badge > 0): ?><span class="badge"><?= $badge ?></span><?php endif; ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </aside>
    <div class="adm-main">
        <header class="adm-topbar">
            <a class="adm-logo" href="<?= h(admin_url()) ?>">
                <span>ChatBBS</span><em>管理后台</em>
            </a>
            <nav class="adm-mainmenu" aria-label="后台主菜单">
                <span class="adm-mainmenu__label">主菜单</span>
                <?php foreach ($menu as $gk => $group): ?>
                    <?php $isActive = ($gk === $activeGroup); ?>
                    <?php $groupFirst = (string)($group['items'][0]['url'] ?? ''); ?>
                    <div class="adm-mainmenu__group<?= $isActive ? ' active' : '' ?>">
                        <a class="adm-mainmenu__btn" href="<?= h(admin_url($groupFirst)) ?>">
                            <?= h((string)$group['label']) ?>
                        </a>
                    </div>
                <?php endforeach; ?>
            </nav>
            <div class="spacer"></div>
            <div class="adm-search" id="adm-search">
                <button type="button" class="adm-search-toggle" id="adm-search-toggle" aria-label="搜索后台设置" title="搜索后台设置">
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="11" cy="11" r="7"/><path d="m16.5 16.5 4 4"/></svg>
                </button>
                <input id="adm-search-input" type="search" placeholder="搜索设置：smtp / 密码 / 附件 …" autocomplete="off">
                <div class="adm-search-list hide" id="adm-search-list"></div>
                <button type="button" class="adm-search-close" id="adm-search-close" aria-label="关闭搜索" title="关闭搜索">×</button>
            </div>
            <button class="adm-btn sm adm-theme-toggle" id="adm-theme-toggle" type="button"
                    title="切换暗色 / 浅色模式" aria-label="切换暗色 / 浅色模式">
                <span class="ico-light"><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg></span>
                <span class="ico-dark"><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg></span>
            </button>
            <a class="adm-btn sm" href="<?= h(url('')) ?>" target="_blank">前台首页</a>
            <?php if ($reports > 0): ?>
                <a class="adm-btn sm warn" href="<?= h(admin_url('reports')) ?>">待审 <?= $reports ?></a>
            <?php endif; ?>
            <?php if ($threads > 0): ?>
                <a class="adm-btn sm warn" href="<?= h(admin_url('threads')) ?>">主题待审 <?= $threads ?></a>
            <?php endif; ?>
            <img class="adm-avatar" src="<?= h(avatar_url($adminUser)) ?>" alt="">
            <span class="adm-user"><?= h((string)($adminUser['username'] ?? '')) ?></span>
            <form method="post" action="<?= h(admin_url('logout')) ?>" style="display:inline">
                <?= csrf_field() ?>
                <button class="adm-btn sm" type="submit">退出</button>
            </form>
        </header>
        <div class="adm-search-backdrop" id="adm-search-backdrop"></div>
        <div class="adm-subbar">
            <div class="adm-crumb">
                <a href="<?= h(admin_url()) ?>">后台</a>
                <?php if ($activeGroupLabel !== ''): ?><span>/</span><a href="<?= h(admin_url((string)($menu[$activeGroup]['items'][0]['url'] ?? ''))) ?>"><?= h($activeGroupLabel) ?></a><?php endif; ?>
                <span>/</span><b><?= h($activeLabel) ?></b>
            </div>
        </div>

        <div class="adm-content">
            <?php
            $admFlash = [
                'success' => \App\Core\Session::flash('success'),
                'error' => \App\Core\Session::flash('error'),
                'info' => \App\Core\Session::flash('info'),
            ];
            ?>
            <?php foreach ($admFlash as $ftype => $fmsg): ?>
                <?php if ($fmsg): ?>
                    <div class="adm-alert <?= h($ftype) ?>"><?= h((string)$fmsg) ?></div>
                <?php endif; ?>
            <?php endforeach; ?>

            <?= $content ?>
        </div>
    </div>
</div>
<script>
/* 后台主菜单：点击展开下拉，点击外部 / Esc 关闭 */
(function () {
  var groups = document.querySelectorAll('[data-menu-group]');
  function closeAll(except) {
    groups.forEach(function (g) {
      if (g === except) return;
      g.classList.remove('open');
      var b = g.querySelector('[data-menu-toggle]');
      if (b) b.setAttribute('aria-expanded', 'false');
    });
  }
  groups.forEach(function (g) {
    var btn = g.querySelector('[data-menu-toggle]');
    if (!btn) return;
    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      var open = !g.classList.contains('open');
      closeAll(g);
      g.classList.toggle('open', open);
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  });
  document.addEventListener('click', function (e) {
    if (!e.target.closest('[data-menu-group]')) closeAll(null);
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeAll(null);
  });
})();
</script>
<script>window.CB_ADMIN_CSRF = '<?= h(csrf_token()) ?>';</script>
<script src="<?= h(admin_asset('js/admin.js') . '?v=' . CB_VERSION) ?>"></script>
<script>
/* 顶栏设置搜索：输入关键词即列出匹配菜单，回车跳第一项 */
(function () {
    var box = document.getElementById('adm-search');
    var input = document.getElementById('adm-search-input');
    var list = document.getElementById('adm-search-list');
    var toggle = document.getElementById('adm-search-toggle');
    if (!box || !input || !list) return;
    var closeSearch = function () {};
    if (toggle) {
      var closeTimer = null;
      var topbar = box.closest('.adm-topbar');
      var backdrop = document.getElementById('adm-search-backdrop');
      var closeBtn = document.getElementById('adm-search-close');
      function clearClose() { if (closeTimer) { clearTimeout(closeTimer); closeTimer = null; } }
      closeSearch = function () {
        clearClose();
        if (!box.classList.contains('is-open') || box.classList.contains('is-closing')) return;
        box.classList.add('is-closing');
        list.classList.add('hide');
        toggle.setAttribute('aria-expanded', 'false');
        setTimeout(function () {
          box.classList.remove('is-open', 'is-closing');
          if (topbar) topbar.classList.remove('is-searching');
          if (backdrop) backdrop.classList.remove('show');
        }, 180);
      };
      function scheduleClose() {
        clearClose();
        closeTimer = setTimeout(closeSearch, 280);
      }
      toggle.addEventListener('click', function (e) {
        e.stopPropagation();
        var open = !box.classList.contains('is-open');
        box.classList.remove('is-closing');
        box.classList.toggle('is-open', open);
        if (topbar) topbar.classList.toggle('is-searching', open);
        if (backdrop) backdrop.classList.toggle('show', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (open) {
          setTimeout(function () { input.focus(); render(input.value); }, 30);
        } else {
          closeSearch();
        }
      });
      if (closeBtn) closeBtn.addEventListener('click', closeSearch);
      if (backdrop) backdrop.addEventListener('click', closeSearch);
      box.addEventListener('mouseenter', clearClose);
      box.addEventListener('mouseleave', scheduleClose);
      list.addEventListener('mouseenter', clearClose);
      if (topbar) {
        topbar.addEventListener('mouseenter', clearClose);
        topbar.addEventListener('mouseleave', function () { if (box.classList.contains('is-open')) scheduleClose(); });
      }
      document.addEventListener('click', function (e) {
        if (!box.contains(e.target) && !box.classList.contains('is-open')) return;
        if (!box.contains(e.target)) closeSearch();
      });
    }
    var INDEX = <?= json_encode($admSearchIndex, JSON_UNESCAPED_UNICODE) ?>;

    function render(kw) {
        kw = kw.trim().toLowerCase();
        if (!kw) { list.classList.add('hide'); list.innerHTML = ''; return; }
        var hits = [];
        for (var i = 0; i < INDEX.length && hits.length < 8; i++) {
            var it = INDEX[i];
            if (it.kw.indexOf(kw) !== -1) hits.push(it);
        }
        if (!hits.length) {
            list.innerHTML = '<div class="adm-search-empty">没有匹配的设置</div>';
        } else {
            list.innerHTML = hits.map(function (it) {
                return '<a class="adm-search-item" href="' + it.url + '">'
                    + '<b>' + it.label + '</b><span>' + it.group + '</span></a>';
            }).join('');
        }
        list.classList.remove('hide');
    }

    input.addEventListener('input', function () { render(input.value); });
    input.addEventListener('focus', function () { if (input.value.trim()) render(input.value); });
    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            var first = list.querySelector('.adm-search-item');
            if (first) { location.href = first.getAttribute('href'); }
        } else if (e.key === 'Escape') {
            closeSearch();
            input.blur();
        }
    });
    list.addEventListener('mousedown', function (e) {
        var a = e.target.closest('.adm-search-item');
        if (a) { e.preventDefault(); location.href = a.getAttribute('href'); }
    });
    document.addEventListener('click', function (e) {
        if (!box.contains(e.target)) list.classList.add('hide');
    });
})();
</script>
</body>
</html>
