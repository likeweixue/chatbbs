<div class="cb-card">
  <div class="cb-card__header"><?= L('创建圈子') ?></div>
  <div class="cb-card__body">
    <form method="post" action="<?= h(url('circle/create')) ?>">
      <?= csrf_field() ?>
      <div class="cb-form-item">
        <label><?= L('圈子名称') ?></label>
        <input class="cb-input" type="text" name="name" maxlength="80" required>
      </div>
      <div class="cb-form-item">
        <label><?= L('简介') ?></label>
        <textarea class="cb-input" name="description" rows="5"></textarea>
      </div>
      <div class="cb-form-item">
        <label><?= L('图标') ?></label>
        <input class="cb-input" type="text" name="icon" placeholder="图片地址或 emoji，可留空">
      </div>
      <div class="cb-form-item">
        <label><?= L('主题色') ?></label>
        <input class="cb-input" type="color" name="color" value="#4D698E" style="width:64px">
      </div>
      <div class="cb-form-item">
        <button class="cb-btn cb-btn-newthread" type="submit"><?= L('提交') ?></button>
        <a class="cb-btn" href="<?= h(url('circles')) ?>"><?= L('返回') ?></a>
      </div>
    </form>
  </div>
</div>
