<div class="cb-card">
  <div class="cb-card__body cb-empty">
    <div class="icon">🔍</div>
    <h2 style="margin:12px 0 8px;font-size:18px"><?= L('404 - 页面不存在') ?></h2>
    <p class="text-sm"><?= L('你访问的地址') ?> <code><?= h((string)($path ?? '')) ?></code> <?= L('没有找到。') ?></p>
    <a class="cb-btn primary mt-16" href="<?= h(url('')) ?>"><?= L('返回首页') ?></a>
  </div>
</div>
