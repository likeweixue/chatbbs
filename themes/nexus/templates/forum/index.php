<?php
$defaultNew = trim((string)config('forum_icon_new', '')) ?: site_base() . '/static/images/forum/forum_new.gif';
$defaultOld = trim((string)config('forum_icon_old', '')) ?: site_base() . '/static/images/forum/forum_old.gif';

// 板块图标：自定义优先，否则按是否有新帖取默认图标
$iconOf = function (array $b) use ($defaultNew, $defaultOld): string {
    if (!empty($b['icon'])) {
        return (string)$b['icon'];
    }
    return ((int)($b['today_threads'] ?? 0) > 0) ? $defaultNew : $defaultOld;
};

/**
 * 递归渲染板块行（支持子级缩进）
 * 列：频道（图标 + 名称 + 描述） | 主题 | 帖子 | 最近回复 | 版主
 */
$renderRow = function (array $b, int $level) use (&$renderRow, $iconOf): string {
    ob_start();
    $indent = $level > 0 ? 'padding-left:' . (10 + $level * 16) . 'px' : '';
    ?>
    <tr class="rowfollow" data-cat-row="<?= (int)($b['__cat'] ?? 0) ?>">
      <td class="fl_ct" align="left">
        <table class="fl_inner" border="0" cellspacing="0" cellpadding="0"><tbody><tr>
          <td class="fl_icn" style="padding-right:10px">
            <a href="<?= h(url('forum/' . $b['id'])) ?>"><img src="<?= h($iconOf($b)) ?>" alt="<?= h($b['name']) ?>"></a>
          </td>
          <td class="embedded" style="<?= h($indent) ?>">
            <?php if ($level > 0): ?><span class="fl_sub_mark">└</span><?php endif; ?>
            <a class="fl_name" href="<?= h(url('forum/' . $b['id'])) ?>"><b><?= h($b['name']) ?></b></a>
            <?php if (!empty($b['description'])): ?><div class="fl_desc"><?= h($b['description']) ?></div><?php endif; ?>
            <?php if ((int)($b['today_threads'] ?? 0) > 0): ?>
              <span class="fl_today">今日 <?= (int)$b['today_threads'] ?></span>
            <?php endif; ?>
          </td>
        </tr></tbody></table>
      </td>
      <td class="fl_i" align="center" width="60"><?= (int)$b['threads'] ?></td>
      <td class="fl_i" align="center" width="60"><?= (int)$b['posts'] ?></td>
      <td class="fl_by" align="left" width="250">
        <?php if (!empty($b['last_thread_title'])): ?>
          <a href="<?= h(url('thread/' . (int)$b['last_thread_id'])) ?>" title="<?= h((string)$b['last_thread_title']) ?>"><?= h(str_cut((string)$b['last_thread_title'], 24)) ?></a>
          <div class="fl_by_meta">
            <?= h(format_date($b['last_post_at'] ?: ($b['last_thread_at'] ?? date('Y-m-d H:i:s')))) ?>
            <?php if (!empty($b['last_post_user'])): ?>
              <a href="<?= h(url('user/' . (int)($b['last_post_uid'] ?? 0))) ?>"><?= h($b['last_post_user']) ?></a>
            <?php endif; ?>
          </div>
        <?php else: ?>
          <span class="text-muted">N/A</span>
        <?php endif; ?>
      </td>
      <td class="fl_mod" align="left" width="120">
        <?php if (!empty($b['moderators'])): ?>
          <?= h($b['moderators']) ?>
        <?php else: ?>
          <span class="text-muted"><i><?= L('诚征中') ?></i></span>
        <?php endif; ?>
      </td>
    </tr>
    <?php
    foreach ($b['children'] ?? [] as $child) {
        if (($child['can_view'] ?? true) === false) { continue; }
        echo $renderRow($child, $level + 1);
    }
    return (string)ob_get_clean();
};
?>
<?php if (!empty($spotlight)): ?>
<div class="cb-spotlight">
  <?php foreach ($spotlight as $cell): ?>
    <div class="cb-spotlight__cell">
      <div class="cb-spotlight__head">
        <span><?= h($cell['title']) ?></span>
        <?php if (!empty($cell['more'])): ?><a href="<?= h($cell['more']) ?>"><?= L('更多') ?></a><?php endif; ?>
      </div>
      <ul>
        <?php foreach (($cell['threads'] ?? []) as $st): ?>
          <li>
            <a href="<?= h(url('thread/' . (int)$st['id'])) ?>" title="<?= h((string)$st['title']) ?>"<?php if (!empty($st['title_color'])): ?> style="color:<?= h((string)$st['title_color']) ?>"<?php endif; ?>><?= h(str_cut((string)$st['title'], 24)) ?></a>
            <time><?= h(time_ago((string)$st['created_at'])) ?></time>
          </li>
        <?php endforeach; ?>
        <?php if (empty($cell['threads'])): ?><li class="empty"><?= L('暂无') ?></li><?php endif; ?>
      </ul>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="cb-card">
  <div class="cb-card__header">
    <span><?= h($site_name ?? config('site_name', 'ChatBBS')) ?> <?= L('论坛') ?></span>
    <span class="more">共 <?= (int)($stats['forums'] ?? 0) ?> 个板块</span>
  </div>
  <div class="cb-card__body" style="padding:0">
    <p class="nx-forum-ops">
      <a href="<?= h(url('search')) ?>"><b><?= L('搜索') ?></b></a>
      | <a href="<?= h(url('thread/create')) ?>"><b><?= L('发布主题') ?></b></a>
      <?php if (is_admin()): ?>
        | <a href="<?= h(url('admin')) ?>"><b><?= L('管理后台') ?></b></a>
      <?php endif; ?>
    </p>

    <table class="cb-forum-table nx-forum" width="100%" border="0" cellspacing="0" cellpadding="0">
      <?php if (!$tree): ?>
        <tbody><tr><td class="cb-empty">还没有板块</td></tr></tbody>
      <?php endif; ?>
      <?php foreach ($tree as $cat): ?>
        <?php
        $catId = (int)$cat['id'];
        $boards = array_values(array_filter($cat['children'] ?? [], static fn($b) => ($b['can_view'] ?? true) !== false));
        $hasTop = ((int)($cat['type'] ?? 1) === 2); // 顶级即板块（无分区包裹）
        $rows = $hasTop ? [$cat] : $boards;
        if (!$rows) { continue; }
        foreach ($rows as $k => $b) { $rows[$k]['__cat'] = $catId; }
        ?>
        <tbody data-cat="<?= $catId ?>">
          <tr class="nx-cat-head">
            <td class="colhead" align="left" width="99%">
              <?php if (!$hasTop): ?>
                <button class="cb-forum-toggle" type="button" data-toggle-cat="<?= $catId ?>" aria-expanded="true" title="<?= L('收起 / 展开') ?>">−</button>
                <a class="nx-cat-name" href="<?= h(url('forum/' . $catId)) ?>"><?= h($cat['name']) ?></a>
              <?php else: ?>
                <?= L('板块总览') ?>
              <?php endif; ?>
            </td>
            <td class="colhead" align="center"><?= L('主题') ?></td>
            <td class="colhead" align="center"><?= L('帖子') ?></td>
            <td class="colhead" align="left"><?= L('最近回复') ?></td>
            <td class="colhead" align="left"><?= L('版主') ?></td>
          </tr>
          <?php foreach ($rows as $b) { echo $renderRow($b, 0); } ?>
        </tbody>
      <?php endforeach; ?>
    </table>
  </div>
</div>

  <div class="cb-card">
    <div class="cb-card__header"><?= L('数据') ?></div>
    <div class="cb-card__body nx-forum-stat">
      <?php
      $s = $stats ?? [];
      $todayPosts = (int)($s['today_posts'] ?? 0);
      ?>
      用户共发布 <b><?= (int)($s['posts'] ?? 0) ?></b> 个帖子、<b><?= (int)($s['threads'] ?? 0) ?></b> 个主题，
      <b class="text-danger"><?= $todayPosts ?></b> 个今日帖子。
    </div>
  </div>

<script>
  (function () {
    // 恢复上次折叠状态
    document.querySelectorAll('[data-toggle-cat]').forEach(function (btn) {
      var id = btn.getAttribute('data-toggle-cat');
      var box = document.querySelector('tbody[data-cat="' + id + '"]');
      if (!box) return;
      var saved = null;
      try { saved = localStorage.getItem('cb_cat_' + id); } catch (e) {}
      var hidden = saved === '1';
      box.querySelectorAll('[data-cat-row]').forEach(function (tr) { tr.style.display = hidden ? 'none' : ''; });
      btn.textContent = hidden ? '+' : '\u2212';
      btn.setAttribute('aria-expanded', hidden ? 'false' : 'true');
    });
  })();
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-toggle-cat]');
    if (!btn) return;
    var id = btn.getAttribute('data-toggle-cat');
    var box = document.querySelector('tbody[data-cat="' + id + '"]');
    if (!box) return;
    var hidden = btn.getAttribute('aria-expanded') === 'true';
    box.querySelectorAll('[data-cat-row]').forEach(function (tr) { tr.style.display = hidden ? 'none' : ''; });
    btn.textContent = hidden ? '+' : '\u2212';
    btn.setAttribute('aria-expanded', hidden ? 'false' : 'true');
    try { localStorage.setItem('cb_cat_' + id, hidden ? '1' : '0'); } catch (err) {}
  });
</script>
