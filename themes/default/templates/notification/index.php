<div class="cb-card">
  <div class="cb-card__header">
    <?= L('我的通知') ?>
    <div class="flex gap-8">
      <a class="cb-btn sm <?= $filter === 'all' ? 'primary' : '' ?>" href="<?= h(url('notifications')) ?>"><?= L('全部') ?></a>
      <a class="cb-btn sm <?= $filter === 'unread' ? 'primary' : '' ?>" href="<?= h(url('notifications?filter=unread')) ?>"><?= L('未读') ?></a>
      <form method="post" action="<?= h(url('notifications/read-all')) ?>" style="margin:0">
        <?= csrf_field() ?>
        <button class="cb-btn sm" type="submit"><?= L('一键已读') ?></button>
      </form>
    </div>
  </div>
  <div class="cb-card__body">
    <?php if (!$items): ?>
      <div class="cb-empty"><div class="icon"><?= cb_icon('bell') ?></div><p>暂无通知</p></div>
    <?php else: ?>
      <?php foreach ($items as $n): ?>
        <div class="cb-list-item" style="align-items:flex-start;<?= (int)$n['is_read'] ? '' : 'background:var(--color-primary-light)' ?>">
          <span class="cb-notify-ico"><?= \App\Controllers\NotifyController::typeIcon((string)$n['type']) ?></span>
          <div style="flex:1;min-width:0">
            <a href="<?= h($n['link'] ?: url('notifications')) ?>" style="color:var(--color-text);font-weight:500">
              <?= h($n['title']) ?>
            </a>
            <?php if ($n['content']): ?><div class="text-sm text-muted"><?= h($n['content']) ?></div><?php endif; ?>
            <div class="text-xs text-muted" style="margin-top:4px"><?= h(time_ago($n['created_at'])) ?></div>
          </div>
          <div class="flex gap-8">
            <?php if (!(int)$n['is_read']): ?>
              <form method="post" action="<?= h(url('notifications/' . $n['id'] . '/read')) ?>" style="display:inline">
                <?= csrf_field() ?><button class="cb-btn sm" type="submit"><?= L('已读') ?></button>
              </form>
            <?php endif; ?>
            <form method="post" action="<?= h(url('notifications/' . $n['id'] . '/delete')) ?>" style="display:inline">
              <?= csrf_field() ?><button class="cb-btn sm" type="submit"><?= L('删除') ?></button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>
<?= $pager ?? '' ?>
