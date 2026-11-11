<div class="cb-card">
  <div class="cb-card__header"><?= L('在圈子发帖') ?> · <?= h((string)$circle['name']) ?></div>
  <div class="cb-card__body">
    <form method="post" action="<?= h(url('circle/' . $circle['id'] . '/post')) ?>">
      <?= csrf_field() ?>
      <div class="cb-form-item">
        <label><?= L('标题') ?></label>
        <input class="cb-input" type="text" name="title" maxlength="100" required>
      </div>
      <div class="cb-form-item">
        <label><?= L('内容') ?></label>
        <textarea class="cb-input" name="content" rows="10" required></textarea>
      </div>
      <div class="cb-form-item">
        <button class="cb-btn cb-btn-newthread" type="submit"><?= L('发布') ?></button>
        <a class="cb-btn" href="<?= h(url('circle/' . $circle['id'])) ?>"><?= L('返回') ?></a>
      </div>
    </form>
  </div>
</div>
