<?php
$marks = \App\Models\Thread::marks();
$layout = (string)config('forum_layout', 'list');
$cols = max(2, min(6, (int)config('forum_grid_cols', 4)));
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
 * 递归渲染板块行（支持无限层级，子级缩进）
 */
$renderRow = function (array $b, int $level) use (&$renderRow, $iconOf): string {
    ob_start();
    $indent = $level > 0 ? ' fl_sub fl_sub-' . min($level, 3) : '';
    ?>
    <tr class="fl_row<?= $indent ?>">
      <td class="fl_icn">
        <a href="<?= h(url('forum/' . $b['id'])) ?>">
          <img src="<?= h($iconOf($b)) ?>" alt="<?= h($b['name']) ?>">
        </a>
      </td>
      <td class="fl_ct">
        <?php if ($level > 0): ?><span class="fl_sub_mark">└</span><?php endif; ?>
        <h2><a href="<?= h(url('forum/' . $b['id'])) ?>"><?= h($b['name']) ?></a></h2>
        <?php if (!empty($b['description'])): ?>
          <p class="fl_desc"><?= h($b['description']) ?></p>
        <?php endif; ?>
        <?php if (!empty($b['moderators'])): ?>
          <p class="fl_mod">版主：<?= h($b['moderators']) ?></p>
        <?php endif; ?>
      </td>
      <td class="fl_i">
        <span class="fl_num"><?= (int)$b['threads'] ?></span>
        <span class="fl_sub"> / <?= (int)$b['posts'] ?></span>
        <?php if ((int)($b['today_threads'] ?? 0) > 0): ?>
          <div class="fl_today">今日 <?= (int)$b['today_threads'] ?></div>
        <?php endif; ?>
      </td>
      <td class="fl_by">
        <?php if (!empty($b['last_thread_title'])): ?>
          <div>
            <a href="<?= h(url('thread/' . (int)$b['last_thread_id'])) ?>"><?= h(str_cut((string)$b['last_thread_title'], 26)) ?></a>
          </div>
          <cite>
            <?= h(format_date($b['last_post_at'] ?: ($b['last_thread_at'] ?? date('Y-m-d H:i:s')))) ?>
            <?php if (!empty($b['last_post_user'])): ?>
              <a href="<?= h(url('user/' . (int)($b['last_post_uid'] ?? 0))) ?>"><?= h($b['last_post_user']) ?></a>
            <?php endif; ?>
          </cite>
        <?php else: ?>
          <span class="text-muted"><?= L('从未') ?></span>
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
<?php foreach ($tree as $cat): ?>
  <?php
  $boards = array_values(array_filter($cat['children'] ?? [], static fn($b) => ($b['can_view'] ?? true) !== false));
  $hasTop = ((int)($cat['type'] ?? 1) === 2); // 顶级就是板块（无分区包裹）
  if (!$boards && !$hasTop) { continue; }
  $rows = $hasTop ? [$cat] : $boards;
  ?>
  <div class="cb-card cb-forum-block">
    <?php if (!$hasTop): ?>
      <div class="cb-card__header cb-forum-block__head">
        <a class="cb-forum-block__title" href="<?= h(url('forum/' . $cat['id'])) ?>"><?= h($cat['name']) ?></a>
        <span class="more"><?= count($boards) ?> 个板块</span>
        <button class="cb-forum-toggle" type="button" title="<?= L('收起 / 展开') ?>" data-toggle-cat="<?= (int)$cat['id'] ?>">−</button>
      </div>
    <?php else: ?>
      <div class="cb-card__header"><?= L('板块总览') ?>
        <span class="more">共 <?= (int)($stats['forums'] ?? 0) ?> 个板块</span>
      </div>
    <?php endif; ?>

    <div class="cb-card__body cb-forum-block__body" id="cat-<?= (int)$cat['id'] ?>">
      <?php if ($layout === 'grid'): ?>
        <div class="cb-forum-grid" style="--cols:<?= $cols ?>">
          <?php foreach ($rows as $b): ?>
            <div class="cb-forum-cell">
              <div class="cb-forum-cell__icon">
                <a href="<?= h(url('forum/' . $b['id'])) ?>">
                  <img src="<?= h($iconOf($b)) ?>" alt="<?= h($b['name']) ?>">
                </a>
              </div>
              <dl>
                <dt><a href="<?= h(url('forum/' . $b['id'])) ?>"><?= h($b['name']) ?></a></dt>
                <dd><em>主题 <?= (int)$b['threads'] ?></em> · <em>回复 <?= (int)$b['posts'] ?></em></dd>
                <dd class="cb-forum-cell__last">
                  <?php if (!empty($b['last_thread_title'])): ?>
                    <a href="<?= h(url('thread/' . (int)$b['last_thread_id'])) ?>">
                      最后发表：<?= h(time_ago($b['last_post_at'] ?: ($b['last_thread_at'] ?? date('Y-m-d H:i:s')))) ?>
                    </a>
                  <?php else: ?>
                    <span class="text-muted"><?= L('从未') ?></span>
                  <?php endif; ?>
                </dd>
              </dl>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <table class="cb-forum-table" cellspacing="0" cellpadding="0">
          <tbody>
          <?php foreach ($rows as $i => $b) { echo $renderRow($b, 0); } ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>
<?php endforeach; ?>

<?php if (!$tree): ?>
  <div class="cb-card"><div class="cb-card__body"><div class="cb-empty"><div class="icon">📭</div><p><?= L('还没有板块') ?></p></div></div></div>
<?php endif; ?>

<?php if ((int)config('show_latest_card', 1)): ?>
  <div class="cb-card">
    <div class="cb-card__header"><?= L('全站最新') ?></div>
    <div class="cb-card__body" style="padding:0">
      <?= \App\Core\View::partial('partials/thread_list', [
        'threads' => \App\Models\Thread::list(['per_page' => 8, 'order' => 'new'])['items'],
        'marks' => $marks,
      ]) ?>
    </div>
  </div>
<?php endif; ?>

<script>
  (function () {
    // 恢复上次折叠状态，避免刷新后左右两侧高度突变
    document.querySelectorAll('[data-toggle-cat]').forEach(function (btn) {
      var id = btn.getAttribute('data-toggle-cat');
      var body = document.getElementById('cat-' + id);
      if (!body) return;
      var saved = null;
      try { saved = localStorage.getItem('cb_cat_' + id); } catch (e) {}
      var hidden = saved === '1';
      body.classList.toggle('is-collapsed', hidden);
      btn.textContent = hidden ? '+' : '\u2212';
      btn.setAttribute('aria-expanded', hidden ? 'false' : 'true');
      var card = btn.closest('.cb-forum-block');
      if (card) { card.classList.toggle('is-collapsed', hidden); }
    });
  })();
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-toggle-cat]');
    if (!btn) return;
    var id = btn.getAttribute('data-toggle-cat');
    var body = document.getElementById('cat-' + id);
    if (!body) return;
    var hidden = body.classList.toggle('is-collapsed');
    btn.textContent = hidden ? '+' : '\u2212';
    btn.setAttribute('aria-expanded', hidden ? 'false' : 'true');
    var card = btn.closest('.cb-forum-block');
    if (card) { card.classList.toggle('is-collapsed', hidden); }
    try { localStorage.setItem('cb_cat_' + id, hidden ? '1' : '0'); } catch (e) {}
  });
</script>
