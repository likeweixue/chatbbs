<?php $marks = \App\Models\Thread::marks(); ?>
<div class="cb-card">
  <div class="cb-card__body">
    <h1 style="margin:0;font-size:19px">
      <span class="cb-tag" style="font-size:14px;padding:3px 12px;background:<?= h($tag['color'] . '1a') ?>;color:<?= h($tag['color']) ?>">#<?= h($tag['name']) ?></span>
      <?php if (!empty($tag['description'])): ?>
        <span class="text-sm text-muted" style="font-weight:400;margin-left:8px"><?= h($tag['description']) ?></span>
      <?php endif; ?>
    </h1>
    <div class="text-xs text-muted" style="margin-top:8px">共 <?= (int)$total ?> 个主题</div>
  </div>
</div>

<?= \App\Core\View::partial('partials/thread_list', ['threads' => $threads, 'marks' => $marks]) ?>
<?= $pager ?? '' ?>
