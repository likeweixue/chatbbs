<?php
/**
 * 岛屿布局：帖子页顶部的筛选 tabs（链接回首页对应筛选）
 * 布局方向参考 OpenIsle（MIT License，https://github.com/nagisa77/OpenIsle）。
 */
$tabs = $tabs ?? [];
if (!$tabs) {
    return;
}
?>
<div class="cb-island-tabs">
  <div class="cb-tabs">
    <?php foreach ($tabs as $tb): ?>
      <?php
      $isLink = !empty($tb['link']);
      $href = $isLink ? (string)($tb['url'] ?? '') : url('?tab=' . rawurlencode((string)($tb['key'] ?? '')));
      $external = $isLink && !str_starts_with($href, site_base());
      ?>
      <a class="cb-tab" href="<?= h($href) ?>"
         <?php if ($external): ?>target="_blank" rel="noopener"<?php endif; ?>>
        <?= h((string)($tb['title'] ?? '')) ?>
      </a>
    <?php endforeach; ?>
  </div>
</div>
