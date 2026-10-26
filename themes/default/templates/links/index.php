<div class="cb-card">
  <div class="cb-card__body">
    <h1 style="margin:0;font-size:21px"><?= h($nav_title ?? '网站导航') ?></h1>
    <?php if (!empty($nav_description)): ?>
      <p class="text-sm text-muted" style="margin:8px 0 0"><?= h($nav_description) ?></p>
    <?php endif; ?>
  </div>
</div>

<?php if (!$links): ?>
  <div class="cb-card"><div class="cb-card__body"><div class="cb-empty"><?= L('还没有推荐链接') ?></div></div></div>
<?php else: ?>
  <div class="cb-links-grid">
    <?php foreach ($links as $link): ?>
      <a class="cb-link-card" href="<?= h((string)$link['url']) ?>" target="_blank" rel="noopener">
        <span class="cb-link-card__icon">
          <?php if (!empty($link['icon'])): ?>
            <img src="<?= h((string)$link['icon']) ?>" alt="" loading="lazy" onerror="this.style.display='none';this.parentNode.textContent='↗'">
          <?php else: ?>
            ↗
          <?php endif; ?>
        </span>
        <span class="cb-link-card__body">
          <b><?= h((string)$link['title']) ?></b>
          <?php if (!empty($link['description'])): ?><span><?= h((string)$link['description']) ?></span><?php endif; ?>
          <em><?= h((string)parse_url((string)$link['url'], PHP_URL_HOST)) ?></em>
        </span>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
