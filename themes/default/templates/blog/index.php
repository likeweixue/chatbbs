<div class="cb-card">
  <div class="cb-card__header">
    <?= L('博客') ?>
    <a class="cb-btn sm cb-btn-newthread" href="<?= h(url('blog/create')) ?>" style="margin-left:auto"><?= L('写文章') ?></a>
  </div>
  <div class="cb-card__body">
    <?php if (!empty($categories)): ?>
      <div class="cb-tabs" style="margin-bottom:12px">
        <a class="cb-tab <?= (int)$category_id === 0 ? 'active' : '' ?>" href="<?= h(url('blog')) ?>"><?= L('全部') ?></a>
        <?php foreach ($categories as $c): ?>
          <a class="cb-tab <?= (int)$category_id === (int)$c['id'] ? 'active' : '' ?>" href="<?= h(url('blog?category=' . (int)$c['id'])) ?>"><?= h((string)$c['name']) ?></a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    <?php if (empty($posts)): ?>
      <div class="cb-empty" style="padding:24px"><?= L('还没有文章') ?></div>
    <?php else: ?>
      <?php foreach ($posts as $p): ?>
        <div class="cb-list-item">
          <div class="t">
            <a href="<?= h(url('blog/' . $p['id'])) ?>"><?= h((string)$p['title']) ?></a>
            <div class="text-xs text-muted"><?= h((string)$p['username']) ?> · <?= h(time_ago((string)$p['created_at'])) ?> · <?= (int)$p['views'] ?> <?= L('浏览') ?></div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>
