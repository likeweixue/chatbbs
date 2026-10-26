<?php $load_editor = true; ?>
<div class="cb-card cb-card--editor">
  <div class="cb-card__body">
    <form method="post" action="<?= h($page ? url('wiki/' . $page['slug'] . '/edit') : url('wiki/create')) ?>">
      <?= csrf_field() ?>
      <div class="cb-form-item">
        <label for="wiki-title"><?= L('标题') ?></label>
        <input class="cb-input" id="wiki-title" name="title" maxlength="200" required value="<?= h((string)($page['title'] ?? '')) ?>">
      </div>
      <div class="cb-form-item">
        <label for="wiki-slug"><?= L('路径') ?></label>
        <input class="cb-input" id="wiki-slug" name="slug" maxlength="100" value="<?= h((string)($page['slug'] ?? '')) ?>"
               placeholder="留空自动生成">
        <div class="cb-form-tip"><?= L('只能使用英文、数字、- 和 _；例如 php-basics') ?></div>
      </div>
      <div class="cb-form-item">
        <label><?= L('内容') ?></label>
        <div data-editor data-input="content_html" data-placeholder="<?= L('写下词条内容…') ?>"></div>
        <input type="hidden" id="content_html" name="content_html" value="<?= h((string)($page['content_html'] ?? '')) ?>">
      </div>
      <button class="cb-btn primary" type="submit"><?= h($page ? L('保存词条') : L('创建词条')) ?></button>
      <a class="cb-btn" href="<?= h($page ? url('wiki/' . $page['slug']) : url('wiki')) ?>"><?= L('取消') ?></a>
    </form>
  </div>
</div>
