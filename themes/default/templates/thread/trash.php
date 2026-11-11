<div class="cb-card">
  <div class="cb-card__header"><?= L('回收站') ?></div>
  <div class="cb-card__body">
    <?php if (empty($threads)): ?>
      <div class="cb-empty" style="padding:24px"><?= L('回收站是空的') ?></div>
    <?php else: ?>
      <?php foreach ($threads as $t): ?>
        <div class="cb-list-item">
          <div class="t">
            <a href="<?= h(url('thread/' . $t['id'])) ?>"><?= h($t['title']) ?></a>
            <div class="text-xs text-muted"><?= h($t['forum_name'] ?? '') ?> · <?= h($t['username'] ?? '') ?> · <?= h(time_ago($t['created_at'])) ?></div>
          </div>
          <form method="post" action="<?= h(url('trash/restore/' . $t['id'])) ?>" style="margin:0;display:inline">
            <?= csrf_field() ?>
            <button class="cb-btn sm" type="submit"><?= L('恢复') ?></button>
          </form>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>
