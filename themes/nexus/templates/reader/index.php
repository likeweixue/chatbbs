<div class="cb-card">
  <div class="cb-card__body">
    <h1 style="margin:0;font-size:21px"><?= h($reader_title) ?></h1>
    <p class="text-sm text-muted" style="margin:6px 0 0"><?= L('管理员订阅的外部资讯') ?></p>
  </div>
</div>
<?php if (!$sources): ?>
  <div class="cb-card"><div class="cb-card__body"><div class="cb-empty"><?= L('还没有配置 RSS 订阅源') ?></div></div></div>
<?php endif; ?>
<?php foreach ($sources as $source): ?>
  <div class="cb-card">
    <div class="cb-card__header"><?= h($source['name']) ?> <a class="more" href="<?= h($source['url']) ?>" target="_blank" rel="noopener"><?= L('访问源站') ?></a></div>
    <div class="cb-card__body">
      <?php if (!$source['items']): ?>
        <div class="text-sm text-muted"><?= L('暂时无法读取该订阅源') ?></div>
      <?php else: ?>
        <div class="cb-reader-list">
          <?php foreach ($source['items'] as $item): ?>
            <a class="cb-reader-item" href="<?= h($item['link']) ?>" target="_blank" rel="noopener">
              <b><?= h($item['title']) ?></b>
              <?php if (!empty($item['description'])): ?><span><?= h($item['description']) ?></span><?php endif; ?>
              <?php if (!empty($item['date'])): ?><em><?= h($item['date']) ?></em><?php endif; ?>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
<?php endforeach; ?>
