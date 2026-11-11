<div class="cb-card">
  <div class="cb-card__body">
    <div class="flex items-center justify-between" style="gap:12px;flex-wrap:wrap">
      <div>
        <h1 style="margin:0;font-size:21px"><?= L('Wiki 词条') ?></h1>
        <p class="text-sm text-muted" style="margin:6px 0 0"><?= L('社区共同维护的知识库') ?></p>
      </div>
      <form method="get" action="<?= h(url('wiki')) ?>" class="flex gap-8" style="display:flex;gap:8px;align-items:center">
        <input class="cb-input" type="search" name="q" value="<?= h((string)($q ?? '')) ?>" placeholder="<?= L('搜索词条，如 ChatBBS') ?>" style="width:240px">
        <button class="cb-btn" type="submit"><?= L('搜索') ?></button>
      </form>
      <a class="cb-btn primary" href="<?= h(url('wiki/create')) ?>"><?= L('创建词条') ?></a>
    </div>
  </div>
</div>
<?php if (!$pages): ?>
  <div class="cb-card"><div class="cb-card__body"><div class="cb-empty">
    <?php if (!empty($q)): ?>
      <?= L('没有找到「') . h((string)$q) . L('」相关词条') ?>
      <div style="margin-top:12px"><a class="cb-btn primary" href="<?= h(url('wiki/create?title=' . rawurlencode((string)$q))) ?>"><?= L('创建「') . h((string)$q) . L('」词条') ?></a></div>
    <?php else: ?>
      <?= L('还没有词条，来创建第一条吧') ?>
    <?php endif; ?>
  </div></div></div>
<?php else: ?>
  <div class="cb-wiki-grid">
    <?php foreach ($pages as $page): ?>
      <a class="cb-wiki-card" href="<?= h(url('wiki/' . $page['slug'])) ?>">
        <b><?= h((string)$page['title']) ?></b>
        <span><?= h(str_cut((string)$page['content_text'], 90)) ?></span>
        <em><?= h((string)($page['nickname'] ?: $page['username'] ?: '匿名')) ?> · <?= h(time_ago((string)$page['updated_at'])) ?></em>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
