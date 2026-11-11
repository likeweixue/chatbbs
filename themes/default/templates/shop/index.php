<div class="cb-card">
  <div class="cb-card__header">
    <?= L('商城') ?>
    <?php if ($can_post): ?>
      <a class="cb-btn cb-btn-newthread" href="<?= h(url('shop/create')) ?>" style="margin-left:auto"><?= L('发布商品') ?></a>
    <?php endif; ?>
  </div>
  <div class="cb-card__body">
    <?php if (empty($items)): ?>
      <div class="cb-empty" style="padding:24px"><?= L('还没有商品') ?></div>
    <?php else: ?>
      <div class="cb-shop-grid">
        <?php foreach ($items as $it): ?>
          <a class="cb-shop-card" href="<?= h(url('shop/' . $it['id'])) ?>">
            <div class="cb-shop-card__img">
              <?php if (trim((string)$it['image']) !== ''): ?>
                <img src="<?= h((string)$it['image']) ?>" alt="">
              <?php else: ?>
                <span><?= L('商品') ?></span>
              <?php endif; ?>
            </div>
            <div class="cb-shop-card__main">
              <b><?= h((string)$it['title']) ?></b>
              <div class="cb-shop-card__price"><?= money((float)$it['price']) ?></div>
              <div class="text-xs text-muted"><?= L('库存') ?> <?= (int)$it['stock'] ?> · <?= h((string)$it['username']) ?></div>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
