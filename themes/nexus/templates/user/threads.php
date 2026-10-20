<?php $marks = \App\Models\Thread::marks(); ?>
<div class="cb-card">
  <div class="cb-card__header"><?= L('我的帖子') ?></div>
  <div class="cb-card__body" style="padding:0">
    <?= \App\Core\View::partial('partials/thread_list', ['threads' => $threads, 'marks' => $marks]) ?>
  </div>
</div>
<?= $pager ?? '' ?>
