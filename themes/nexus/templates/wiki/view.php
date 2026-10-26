<div class="cb-card">
  <div class="cb-card__body">
    <div class="flex items-center justify-between" style="gap:12px;flex-wrap:wrap">
      <div>
        <h1 style="margin:0;font-size:22px"><?= h((string)$page['title']) ?></h1>
        <div class="text-xs text-muted" style="margin-top:6px">
          <?= h((string)($page['nickname'] ?: $page['username'] ?: '匿名')) ?> · 更新于 <?= h(time_ago((string)$page['updated_at'])) ?>
        </div>
      </div>
      <?php if ($current_user): ?>
        <a class="cb-btn sm" href="<?= h(url('wiki/' . $page['slug'] . '/edit')) ?>"><?= L('编辑词条') ?></a>
      <?php endif; ?>
    </div>
  </div>
</div>
<div class="cb-card"><div class="cb-card__body cb-content-html"><?= $page['content_html'] ?></div></div>
