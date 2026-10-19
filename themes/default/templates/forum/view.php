<?php $marks = \App\Models\Thread::marks(); ?>
<div class="cb-card">
  <div class="cb-card__body">
    <div class="flex items-center justify-between" style="gap:12px;flex-wrap:wrap">
      <div>
        <h1 style="margin:0;font-size:20px"><?= h($forum['name']) ?></h1>
        <p class="text-sm text-muted" style="margin:4px 0 0"><?= h($forum['description'] ?? '') ?></p>
      </div>
      <div class="flex gap-8">
        <?php if ($can_post ?? false): ?>
          <a class="cb-btn cb-btn-newthread" href="<?= h(url('thread/create?forum=' . $forum['id'])) ?>"><?= cb_icon('quill') ?>发布主题</a>
        <?php else: ?>
          <span class="text-sm text-muted"><?= L('本板块暂无发帖权限') ?></span>
        <?php endif; ?>
      </div>
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
      foreach ($tabs as $tb):
        $href = $tb['link']
          ? (string)$tb['url']
          : url('forum/' . $forum['id'] . '?tab=' . rawurlencode((string)$tb['key']));
        $active = !$tb['link'] && $curTab === (string)$tb['key'];
      ?>
        <a class="cb-tab <?= $active ? 'active' : '' ?>" href="<?= h($href) ?>"
           <?php if ($tb['link'] && !str_starts_with((string)$tb['url'], site_base())): ?>target="_blank" rel="noopener"<?php endif; ?>>
          <?= h($tb['title']) ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
  <?= \App\Core\View::partial('partials/thread_list', ['threads' => $threads, 'marks' => $marks, 'boxed' => false]) ?>
</div>

<?= $pager ?? '' ?>
