<div class="cb-card">
  <div class="cb-card__header"><?= L('全部标签') ?> <span class="more">共 <?= count($tags) ?> 个</span></div>
  <div class="cb-card__body">
    <?php foreach ($tags as $t): ?>
      <a href="<?= h(url('tag/' . $t['id'])) ?>" style="display:inline-block;margin:0 8px 8px 0">
        <span class="cb-tag" style="font-size:13px;padding:4px 12px;background:<?= h($t['color'] . '1a') ?>;color:<?= h($t['color']) ?>">
          <?= h($t['name']) ?> · <?= (int)$t['threads'] ?>
        </span>
      </a>
    <?php endforeach; ?>
    <?php if (!$tags): ?><div class="cb-empty"><div class="icon">🏷️</div><p><?= L('暂无标签') ?></p></div><?php endif; ?>
  </div>
</div>
