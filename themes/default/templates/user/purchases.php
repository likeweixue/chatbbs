<div class="cb-card">
  <div class="cb-card__header"><?= L('我的购买') ?></div>
  <div class="cb-card__body">
    <?php if (empty($rows)): ?>
      <div class="cb-empty" style="padding:24px"><?= L('还没有购买记录') ?></div>
    <?php else: ?>
      <?php foreach ($rows as $r): ?>
        <div class="cb-list-item">
          <div class="t">
            <a href="<?= h(url('thread/' . (int)$r['thread_id'] . '#paid-panel')) ?>"><?= h((string)($r['title'] ?? '帖子已删除')) ?></a>
            <div class="text-xs text-muted"><?= h(format_date((string)$r['created_at'])) ?> · <?= money((float)$r['amount']) ?></div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>
