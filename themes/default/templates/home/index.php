<?php $marks = \App\Models\Thread::marks(); ?>
<?php if (!empty($says) && (int)config('say_on_home', 1)): ?>
  <div class="cb-card">
    <div class="cb-card__header"><?= L('最新说说') ?> <a class="more" href="<?= h(url('say')) ?>"><?= L('更多') ?></a></div>
    <div class="cb-card__body">
      <?php foreach ($says as $s): ?>
        <div style="padding:8px 0;border-bottom:1px dashed var(--color-border)">
          <a href="<?= h(url('user/' . $s['user_id'])) ?>" style="color:var(--color-primary)"><?= h($s['nickname'] ?? $s['username']) ?></a><?= L('：') ?>
          <span class="text-sm"><?= h(str_cut($s['content'], 80)) ?></span>
          <span class="text-xs text-muted"><?= h(time_ago($s['created_at'])) ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>

<div class="cb-card">
  <div class="cb-card__header cb-card__header--tabs">
    <div class="cb-tabs">
      <?php
      $tabs = $list_tabs ?? [
        ['key' => 'new', 'title' => L('最新发布'), 'link' => false],
        ['key' => 'default', 'title' => L('最新回复'), 'link' => false],
        ['key' => 'hot', 'title' => L('热门'), 'link' => false],
        ['key' => 'essence', 'title' => L('精华'), 'link' => false],
      ];
      $curTab = (string)($tab ?? '');
      $rankBase = url('?tab=rank&sort=');
      $rankSort = (string)\App\Core\Request::get('sort', 'replies');
      foreach ($tabs as $tb):
        $href = $tb['link']
          ? (string)$tb['url']
          : url(($tb['key'] === 'rank' ? '?tab=rank&sort=replies' : '?tab=' . rawurlencode((string)$tb['key'])));
        $active = !$tb['link'] && $curTab === (string)$tb['key'];
        if (($tb['key'] ?? '') === 'rank'):
      ?>
        <div class="cb-tab-rank" data-tab-rank>
          <button type="button" class="cb-tab <?= $active ? 'active' : '' ?>"><?= h($tb['title']) ?> <span class="cb-tab-rank__caret">▾</span></button>
          <div class="cb-tab-rank__menu">
            <a class="<?= $rankSort === 'replies' ? 'on' : '' ?>" href="<?= h($rankBase . 'replies') ?>"><?= L('最多回复') ?></a>
            <a class="<?= $rankSort === 'likes' ? 'on' : '' ?>" href="<?= h($rankBase . 'likes') ?>"><?= L('最多点赞') ?></a>
            <a class="<?= $rankSort === 'views' ? 'on' : '' ?>" href="<?= h($rankBase . 'views') ?>"><?= L('最多浏览') ?></a>
          </div>
        </div>
      <?php else: ?>
        <a class="cb-tab <?= $active ? 'active' : '' ?>" href="<?= h($href) ?>"
           <?php if ($tb['link'] && !str_starts_with((string)$tb['url'], site_base())): ?>target="_blank" rel="noopener"<?php endif; ?>>
          <?= h($tb['title']) ?>
        </a>
      <?php endif; endforeach; ?>
    </div>
    <div class="cb-header-actions">
      <a class="cb-btn cb-btn-newthread cb-newthread--action" href="<?= h(url('thread/create')) ?>">
        <span class="cb-newthread-ico"><?= cb_icon('edit') ?></span><span class="cb-newthread-label"><?= L('发布主题') ?></span>
      </a>
    </div>
  </div>
  <?= \App\Core\View::partial('partials/thread_list', ['threads' => $threads, 'marks' => $marks, 'boxed' => false]) ?>
</div>

<?= $pager ?? '' ?>
