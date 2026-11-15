<div class="cb-card">
  <div class="cb-card__header">
    <?= L('最新发布') ?>
    <a class="cb-btn sm cb-btn-newthread" href="<?= h(url('blog/create')) ?>"><?= L('写文章') ?></a>
  </div>
  <div class="cb-card__body">
    <?php if (empty($latest)): ?>
      <div class="cb-empty" style="padding:24px"><?= L('还没有文章') ?></div>
    <?php else: ?>
      <?php foreach ($latest as $p): ?>
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

<div class="cb-card">
  <div class="cb-card__header"><?= L('分类') ?></div>
  <div class="cb-card__body">
    <?php if (empty($categories)): ?>
      <div class="cb-empty" style="padding:16px"><?= L('还没有分类') ?></div>
    <?php else: ?>
      <div class="cb-blog-cats">
        <?php foreach ($categories as $c): ?>
          <a class="cb-blog-cat" href="<?= h(url('blog?category=' . (int)$c['id'])) ?>">
            <span><?= h((string)$c['name']) ?></span><em><?= (int)$c['count'] ?></em>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<div class="cb-card">
  <div class="cb-card__header"><?= L('标签') ?></div>
  <div class="cb-card__body">
    <?php if (empty($tags)): ?>
      <div class="cb-empty" style="padding:16px"><?= L('还没有标签') ?></div>
    <?php else: ?>
      <div class="cb-blog-tags">
        <?php foreach ($tags as $t => $n): ?>
          <span class="cb-tag"><?= h($t) ?> <em><?= (int)$n ?></em></span>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<div class="cb-card">
  <div class="cb-card__header"><?= L('公告 / 介绍') ?></div>
  <div class="cb-card__body">
    <?php if (trim((string)$intro) !== ''): ?>
      <div class="cb-blog-intro"><?= nl2br(h((string)$intro)) ?></div>
    <?php else: ?>
      <div class="cb-empty" style="padding:16px"><?= L('还没有填写博客介绍') ?></div>
    <?php endif; ?>
  </div>
</div>
