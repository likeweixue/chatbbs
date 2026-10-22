<div class="cb-card">
  <div class="cb-card__header"><?= L('草稿箱') ?> <span class="more">共 <?= count($drafts) ?> 篇</span></div>
  <div class="cb-card__body">
    <?php if (!$drafts): ?>
      <div class="cb-empty"><div class="icon"><?= cb_icon('edit') ?></div><p><?= L('暂无草稿') ?></p></div>
    <?php else: ?>
      <?php foreach ($drafts as $d): ?>
        <div class="cb-list-item">
          <div style="flex:1;min-width:0">
            <a href="<?= h(url('thread/create?forum=' . $d['forum_id'])) ?>" style="color:var(--color-text);font-weight:500">
              <?= h($d['title'] ?: L('(无标题)')) ?>
            </a>
            <div class="text-xs text-muted"><?= h(str_cut($d['content'], 60)) ?></div>
            <div class="text-xs text-muted">更新于 <?= h(format_date($d['updated_at'])) ?></div>
          </div>
          <form method="post" action="<?= h(url('user/draft/' . $d['id'] . '/delete')) ?>" style="display:inline">
            <?= csrf_field() ?>
            <button class="cb-btn sm" type="submit" onclick="return confirm('删除该草稿？')"><?= L('删除') ?></button>
          </form>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>
