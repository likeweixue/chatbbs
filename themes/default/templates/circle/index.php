<div class="cb-card">
  <div class="cb-card__header"><?= L('圈子') ?></div>
  <div class="cb-card__body">
    <?php if (empty($circles)): ?>
      <div class="cb-empty" style="padding:24px"><?= L('还没有圈子') ?></div>
    <?php else: ?>
      <div class="cb-circle-grid">
        <?php foreach ($circles as $c): ?>
          <a class="cb-circle-card" href="<?= h(url('circle/' . $c['id'])) ?>">
            <div class="cb-circle-card__icon" style="background:<?= h((string)$c['color']) ?>">
              <?php if (trim((string)$c['icon']) !== '' && preg_match('#^https?://#i', (string)$c['icon'])): ?>
                <img src="<?= h((string)$c['icon']) ?>" alt="">
              <?php else: ?>
                <?= h(str_cut((string)$c['name'], 1)) ?>
              <?php endif; ?>
            </div>
            <div class="cb-circle-card__main">
              <b><?= h((string)$c['name']) ?></b>
              <span class="text-xs text-muted"><?= (int)$c['member_count'] ?> <?= L('成员') ?></span>
              <p class="text-sm"><?= h(str_cut((string)$c['description'], 60)) ?></p>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
