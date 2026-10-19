<div class="cb-card">
  <div class="cb-card__body cb-empty">
    <div class="icon">ℹ️</div>
    <h2 style="margin:12px 0 8px;font-size:18px"><?= h((string)($title ?? L('提示'))) ?></h2>
    <p class="text-sm"><?= h((string)($message ?? '')) ?></p>
    <a class="cb-btn primary mt-16" href="<?= h(url('')) ?>"><?= L('返回首页') ?></a>
  </div>
</div>
