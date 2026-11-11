<div class="cb-card">
  <div class="cb-card__header"><?= L('发布商品') ?></div>
  <div class="cb-card__body">
    <form method="post" action="<?= h(url('shop/create')) ?>">
      <?= csrf_field() ?>
      <div class="cb-form-item">
        <label><?= L('商品名称') ?></label>
        <input class="cb-input" type="text" name="title" maxlength="120" required>
      </div>
      <div class="cb-form-item">
        <label><?= L('商品描述') ?></label>
        <textarea class="cb-input" name="description" rows="8"></textarea>
      </div>
      <div class="cb-form-item">
        <label><?= L('价格') ?></label>
        <input class="cb-input" type="number" name="price" step="0.01" min="0" value="0" style="width:160px">
      </div>
      <div class="cb-form-item">
        <label><?= L('库存') ?></label>
        <input class="cb-input" type="number" name="stock" min="0" value="0" style="width:160px">
      </div>
      <div class="cb-form-item">
        <label><?= L('商品图片') ?></label>
        <input class="cb-input" type="text" name="image" placeholder="图片地址，可留空">
      </div>
      <div class="cb-form-item">
        <button class="cb-btn cb-btn-newthread" type="submit"><?= L('发布') ?></button>
        <a class="cb-btn" href="<?= h(url('shops')) ?>"><?= L('返回') ?></a>
      </div>
    </form>
  </div>
</div>
