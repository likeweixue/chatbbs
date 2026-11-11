<div class="cb-card">
  <div class="cb-card__header"><?= L('写文章') ?></div>
  <div class="cb-card__body">
    <form method="post" action="<?= h(url('blog/create')) ?>">
      <?= csrf_field() ?>
      <div class="cb-form-item"><label><?= L('标题') ?></label><input class="cb-input" type="text" name="title" maxlength="200" required></div>
      <div class="cb-form-item"><label><?= L('分类') ?></label><input class="cb-input" type="text" name="category" list="blog-cats" placeholder="<?= L('输入或选择分类，可留空') ?>"><datalist id="blog-cats"><?php foreach (($categories ?? []) as $c): ?><option value="<?= h((string)$c['name']) ?>"><?php endforeach; ?></datalist></div>
      <div class="cb-form-item"><label><?= L('标签') ?></label><input class="cb-input" type="text" name="tags" placeholder="<?= L('多个用英文逗号分隔') ?>"></div>
      <div class="cb-form-item"><label><?= L('内容') ?></label><textarea class="cb-input" name="content_text" rows="14" required></textarea></div>
      <div class="cb-form-item"><button class="cb-btn cb-btn-newthread" type="submit"><?= L('发布') ?></button> <a class="cb-btn" href="<?= h(url('blog')) ?>"><?= L('返回') ?></a></div>
    </form>
  </div>
</div>
