<?php $marks = \App\Models\Thread::marks(); ?>
<div class="cb-card">
  <div class="cb-card__body">
    <div class="flex gap-8">
      <a class="cb-btn sm <?= ($kind ?? 1) === 1 ? 'primary' : '' ?>" href="<?= h(url('recommend?kind=1')) ?>"><?= L('⭐ 推荐区') ?></a>
      <a class="cb-btn sm <?= ($kind ?? 1) === -1 ? 'primary' : '' ?>" href="<?= h(url('recommend?kind=-1')) ?>"><?= L('⚡ 避雷区') ?></a>
    </div>
    <div class="text-xs text-muted" style="margin-top:8px">
      <?php if (($kind ?? 1) === 1): ?>
        这里汇集了被其他用户推荐的优质内容。
      <?php else: ?>
        这里是被标记为"避雷"的内容，请注意甄别。
      <?php endif; ?>
    </div>
  </div>
</div>

<?= \App\Core\View::partial('partials/thread_list', ['threads' => $threads, 'marks' => $marks]) ?>
<?= $pager ?? '' ?>
