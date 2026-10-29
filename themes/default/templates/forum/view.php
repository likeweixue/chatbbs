<?php $marks = \App\Models\Thread::marks(); ?>
<div class="cb-card">
  <div class="cb-card__body">
    <div class="flex items-center justify-between" style="gap:12px;flex-wrap:wrap">
      <div>
        <h1 style="margin:0;font-size:20px"><?= h($forum['name']) ?></h1>
        <p class="text-sm text-muted" style="margin:4px 0 0"><?= h($forum['description'] ?? '') ?></p>
      </div>
      <?php if (!($can_post ?? false)): ?>
        <span class="text-sm text-muted"><?= L('本板块暂无发帖权限') ?></span>
      <?php endif; ?>
    </div>
    <div class="text-xs text-muted" style="margin-top:10px">
      <?= (int)$forum['threads'] ?> 主题 · <?= (int)$forum['posts'] ?> 回复
      <?php if ((int)$forum['need_level'] > 0): ?> · 需要等级 V<?= (int)$forum['need_level'] ?><?php endif; ?>
      <?php if ((float)$forum['need_money'] > 0): ?> · 需要 <?= (float)$forum['need_money'] ?> <?= h(currency_name()) ?><?php endif; ?>
      <?php if ((int)$forum['need_login']): ?> · 需登录后访问<?php endif; ?>
    </div>
  </div>
</div>

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
      $rankBase = url('forum/' . $forum['id'] . '?tab=rank&sort=');
      $rankSort = (string)\App\Core\Request::get('sort', 'replies');
      foreach ($tabs as $tb):
        $href = $tb['link']
          ? (string)$tb['url']
          : url(($tb['key'] === 'rank' ? '?tab=rank&sort=replies' : 'forum/' . $forum['id'] . '?tab=' . rawurlencode((string)$tb['key'])));
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
    <?php if ($can_post ?? false): ?>
      <div class="cb-header-actions">
        <a class="cb-btn cb-btn-newthread cb-newthread--action" href="<?= h(url('thread/create?forum=' . $forum['id'])) ?>">
          <span class="cb-newthread-ico"><?= cb_icon('edit') ?></span><span class="cb-newthread-label"><?= L('发布主题') ?></span>
        </a>
      </div>
    <?php endif; ?>
  </div>
  <?= \App\Core\View::partial('partials/thread_list', ['threads' => $threads, 'marks' => $marks, 'boxed' => false]) ?>
</div>

<?= $pager ?? '' ?>
