<div class="cb-card">
  <div class="cb-card__body" style="display:flex;gap:20px;flex-wrap:wrap">
    <div class="cb-shop-view__img">
      <?php if (trim((string)$item['image']) !== ''): ?>
        <img src="<?= h((string)$item['image']) ?>" alt="">
      <?php else: ?>
        <span><?= L('商品') ?></span>
      <?php endif; ?>
    </div>
    <div style="flex:1;min-width:220px">
      <h2 style="margin:0 0 8px"><?= h((string)$item['title']) ?></h2>
      <div class="cb-shop-view__price"><?= money((float)$item['price']) ?></div>
      <div class="text-sm text-muted" style="margin:6px 0"><?= L('库存') ?> <?= (int)$item['stock'] ?> · <?= L('发布者') ?> <?= h((string)$item['username']) ?></div>
      <div style="margin-top:14px;white-space:pre-wrap"><?= h((string)$item['description']) ?></div>
      <div style="margin-top:18px">
        <a class="cb-btn" href="<?= h(url('shops')) ?>"><?= L('返回商城') ?></a>
      </div>
    </div>
  </div>
</div>
