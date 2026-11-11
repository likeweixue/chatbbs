<div class="cb-card">
  <div class="cb-card__header"><?= h((string)$post['title']) ?></div>
  <div class="cb-card__body">
    <div class="text-xs text-muted" style="margin-bottom:12px">
      <?= h((string)$post['username']) ?> · <?= h(format_date((string)$post['created_at'])) ?>
      <?php if (!empty($category)): ?> · <?= h((string)$category['name']) ?><?php endif; ?>
      · <?= (int)$post['views'] ?> <?= L('浏览') ?>
    </div>
    <div class="cb-blog-content"><?= $post['content'] ?></div>
    <?php if (trim((string)$post['tags']) !== ''): ?>
      <div style="margin-top:16px"><?php foreach (array_filter(array_map('trim', explode(',', (string)$post['tags']))) as $tag): ?><span class="cb-tag"><?= h($tag) ?></span><?php endforeach; ?></div>
    <?php endif; ?>
  </div>
</div>
