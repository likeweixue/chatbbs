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

// 定位当前菜单分组与名称
$activeGroup = '';
$activeLabel = '管理后台';
foreach ($menu as $gk => $group) {
    foreach ($group['items'] as $it) {
        if ($it['key'] === $currentKey) {
            $activeGroup = $gk;
            $activeLabel = $it['label'];
            break 2;
        }
    }
}
if ($activeGroup === '' && isset($menu['dashboard'])) {
    $activeGroup = 'dashboard';
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
</head>
<body>
<div class="adm-wrap">
    <aside class="adm-side">
        <div class="adm-logo">
            <a href="<?= h(admin_url()) ?>" style="color:var(--adm-side-strong)">ChatBBS</a>
            <span>v<?= h((string)CB_VERSION) ?></span>
        </div>
        <nav class="adm-nav">
            <?php foreach ($menu as $gk => $group): ?>
                <?php $open = ($gk === $activeGroup) ? ' open' : ''; ?>
                <div class="adm-nav-group<?= $open ?>">
                    <div class="adm-nav-title" data-toggle-group>
                        <?php if (!empty($group['icon_key'])): ?>
                            <?= \App\Admin\BaseAdmin::groupIconSvg((string)$group['icon_key']) ?>
                        <?php else: ?>
                            <i class="adm-nav-icon"><?= $group['icon'] ?? '•' ?></i>
                        <?php endif; ?>
                        <span><?= h($group['label']) ?></span>
                        <i class="adm-nav-caret">▶</i>
                    </div>
                    <div class="adm-nav-items">
                        <?php foreach ($group['items'] as $it): ?>
                            <?php
                            $badge = 0;
                            if ($it['key'] === 'reports') {
                                $badge = $reports;
                            } elseif ($it['key'] === 'threads') {
                                $badge = $threads;
                            }
                            ?>
                            <a class="adm-nav-item<?= $it['key'] === $currentKey ? ' active' : '' ?>"
                               href="<?= h(admin_url((string)$it['url'])) ?>">
                                <?= h($it['label']) ?>
                                <?php if ($badge > 0): ?><span class="badge"><?= $badge ?></span><?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </nav>
    </aside>

    <div class="adm-main">
        <header class="adm-topbar">
            <div class="adm-crumb">
                后台 / <b><?= h($activeLabel) ?></b>
            </div>
            <div class="adm-search" id="adm-search">
                <input id="adm-search-input" type="search" placeholder="搜索设置：smtp / 密码 / 附件 …" autocomplete="off">
                <div class="adm-search-list hide" id="adm-search-list"></div>
            </div>
            <div class="spacer"></div>
            <button class="adm-btn sm adm-theme-toggle" id="adm-theme-toggle" type="button"
                    title="切换暗色 / 浅色模式" aria-label="切换暗色 / 浅色模式">
                <span class="ico-light"><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg></span>
                <span class="ico-dark"><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg></span>
            </button>
            <a class="adm-btn sm" href="<?= h(url('')) ?>" target="_blank">前台首页</a>
            <?php if ($reports > 0): ?>
                <a class="adm-btn sm warn" href="<?= h(admin_url('reports')) ?>">待审 <?= $reports ?></a>
            <?php endif; ?>
            <img class="adm-avatar" src="<?= h(avatar_url($adminUser)) ?>" alt="">
            <span class="adm-user"><?= h((string)($adminUser['username'] ?? '')) ?></span>
            <form method="post" action="<?= h(admin_url('logout')) ?>" style="display:inline">
                <?= csrf_field() ?>
                <button class="adm-btn sm" type="submit">退出</button>
            </form>
        </header>

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
<script>window.CB_ADMIN_CSRF = '<?= h(csrf_token()) ?>';</script>
<script src="<?= h(admin_asset('js/admin.js') . '?v=' . CB_VERSION) ?>"></script>
<script>
/* 顶栏设置搜索：输入关键词即列出匹配菜单，回车跳第一项 */
(function () {
    var box = document.getElementById('adm-search');
    var input = document.getElementById('adm-search-input');
    var list = document.getElementById('adm-search-list');
    if (!box || !input || !list) return;
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
            list.classList.add('hide'); input.blur();
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
