<div class="cb-card">
  <div class="cb-card__header"><?= L('购买附件') ?></div>
  <div class="cb-card__body">
    <div class="flex gap-12 items-center">
      <div><?= cb_icon('package', 'cb-ico-xl') ?></div>
      <div>
        <div style="font-weight:600"><?= h($att['name']) ?></div>
        <div class="text-xs text-muted"><?= h(format_size($att['size'])) ?> · <?= (int)$att['downloads'] ?> 次下载</div>
      </div>
    </div>

    <div class="cb-alert info mt-16">
      该附件为付费内容，需支付 <b><?= h((float)$att['fee']) ?> <?= h(currency_name()) ?></b> 后才能下载。
      当前余额：<b><?= h((float)$balance) ?></b>
    </div>

    <?php if ($paid): ?>
      <a class="cb-btn primary block" href="<?= h(url('attachment/' . $att['id'] . '/download')) ?>"><?= L('已购买，去下载') ?></a>
    <?php elseif ((float)$balance >= (float)$att['fee']): ?>
      <form method="post" action="<?= h(url('attachment/' . $att['id'] . '/buy')) ?>">
        <?= csrf_field() ?>
        <button class="cb-btn primary block" type="submit"><?= L('确认支付并下载') ?></button>
      </form>
    <?php else: ?>
      <button class="cb-btn block" disabled>余额不足，请先赚取<?= h(currency_name()) ?></button>
    <?php endif; ?>
  </div>
</div>
