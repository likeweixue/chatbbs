<div class="cb-card">
  <div class="cb-card__header">
    <?= L('共享附件库') ?>
    <form method="get" action="<?= h(url('attachments')) ?>" class="flex gap-8">
      <input class="cb-input" name="kw" value="<?= h((string)\App\Core\Request::get('kw', '')) ?>" placeholder="<?= L('搜索文件名') ?>" style="width:180px">
      <button class="cb-btn sm" type="submit"><?= L('搜索') ?></button>
    </form>
  </div>
  <div class="cb-card__body">
    <div class="cb-att-grid">
      <?php foreach ($items as $a): ?>
        <div class="cb-att-card">
          <div class="cb-att-card__thumb">
            <?php if ((int)$a['is_image']): ?>
              <img src="<?= h(\App\Core\Upload::url($a['space'], $a['path'])) ?>" alt="">
            <?php else: ?>
              <span><?= h(strtoupper($a['ext'])) ?></span>
            <?php endif; ?>
          </div>
          <div class="cb-att-card__info">
            <div class="cb-att-card__name" title="<?= h($a['name']) ?>"><?= h($a['name']) ?></div>
            <div class="text-xs text-muted"><?= h(format_size($a['size'])) ?> · <?= (int)$a['downloads'] ?> 次下载</div>
            <a class="cb-btn sm mt-8" href="<?= h(url('attachment/' . $a['id'] . '/download')) ?>"><?= L('下载') ?></a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <?php if (!$items): ?><div class="cb-empty"><div class="icon">📁</div><p><?= L('暂无文件') ?></p></div><?php endif; ?>
  </div>
</div>
<?= $pager ?? '' ?>
