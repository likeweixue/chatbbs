<div class="cb-card cb-circle-head">
  <div class="cb-card__body" style="display:flex;gap:16px;align-items:center;flex-wrap:wrap">
    <div class="cb-circle-card__icon" style="background:<?= h((string)$circle['color']) ?>">
      <?php if (trim((string)$circle['icon']) !== '' && preg_match('#^https?://#i', (string)$circle['icon'])): ?>
        <img src="<?= h((string)$circle['icon']) ?>" alt="">
      <?php else: ?>
        <?= h(str_cut((string)$circle['name'], 1)) ?>
      <?php endif; ?>
    </div>
    <div style="flex:1;min-width:200px">
      <h2 style="margin:0 0 4px"><?= h((string)$circle['name']) ?></h2>
      <div class="text-sm text-muted"><?= (int)$circle['member_count'] ?> <?= L('成员') ?></div>
      <?php if (trim((string)$circle['description']) !== ''): ?>
        <p class="text-sm" style="margin:6px 0 0"><?= h((string)$circle['description']) ?></p>
      <?php endif; ?>
    </div>
    <div class="flex gap-8" style="display:flex;gap:8px">
      <?php if ($is_member): ?>
        <?php if ($can_post): ?>
          <a class="cb-btn cb-btn-newthread" href="<?= h(url('circle/' . $circle['id'] . '/post')) ?>"><?= L('发帖') ?></a>
        <?php endif; ?>
        <form method="post" action="<?= h(url('circle/' . $circle['id'] . '/leave')) ?>" style="margin:0">
          <?= csrf_field() ?>
          <button class="cb-btn" type="submit"><?= L('退出圈子') ?></button>
        </form>
      <?php else: ?>
        <form method="post" action="<?= h(url('circle/' . $circle['id'] . '/join')) ?>" style="margin:0">
          <?= csrf_field() ?>
          <button class="cb-btn cb-btn-newthread" type="submit"><?= L('加入圈子') ?></button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="cb-card">
  <div class="cb-card__header"><?= L('圈内帖子') ?></div>
  <?= \App\Core\View::partial('partials/thread_list', ['threads' => $threads, 'marks' => \App\Models\Thread::marks(), 'boxed' => false]) ?>
</div>
<?= $pager ?? '' ?>
