<?php
/**
 * 岛屿布局：左侧菜单
 * 布局方向参考 OpenIsle（MIT License，https://github.com/nagisa77/OpenIsle）。
 * 本文件的 PHP / HTML / CSS 由 ChatBBS 独立实现，详见 THIRD_PARTY_NOTICES.md。
 * 数据来自 BaseController::globalData()：$island_menu / $island_forums / $island_tags
 */
$island_menu = $island_menu ?? [];
if (!$island_menu) {
    $island_menu = \App\Services\Island::menu();
}
$island_forums = $island_forums ?? [];
$island_tags = $island_tags ?? [];

$currentTagId = isset($tag) && is_array($tag) ? (int)($tag['id'] ?? 0) : 0;
$isThread = isset($thread);
$isForum = isset($forum) && !$isThread;
$activeForumId = $isThread ? (int)($thread['forum_id'] ?? 0) : ($isForum ? (int)($forum['id'] ?? 0) : 0);

// 判断当前是否首页：兼容 ?r= 和伪静态
$route = trim((string)($_GET['r'] ?? ''), '/');
if ($route === '') {
    $path = trim((string)parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH), '/');
    $basePath = trim((string)parse_url(site_base(), PHP_URL_PATH), '/');
    if ($basePath !== '' && str_starts_with($path, $basePath)) {
        $path = trim(substr($path, strlen($basePath)), '/');
    }
    $route = $path === 'index.php' ? '' : $path;
}
$isHome = $route === '';

$typeIcon = [
    'posts' => 'comment',
    'forums' => 'folder',
    'tags' => 'tag',
    'link' => 'link',
];
?>
<aside class="cb-island-nav" aria-label="<?= L('论坛导航') ?>">
  <div class="cb-island-nav__tools">
    <span class="cb-island-nav__title"><?= L('论坛导航') ?></span>
    <?php if (!empty($show_toc)): ?>
      <button type="button" class="cb-island-tool" id="island-toc-toggle" data-island-toc title="<?= L('目录') ?>" aria-label="<?= L('目录') ?>">
        <?= cb_icon('ul') ?>
      </button>
    <?php endif; ?>
    <button type="button" class="cb-island-tool" data-island-hide title="<?= L('隐藏侧栏') ?>" aria-label="<?= L('隐藏侧栏') ?>">
      <?= cb_icon('panel-left') ?>
    </button>
    <button type="button" class="cb-island-tool" data-island-side title="<?= L('切换侧栏位置') ?>" aria-label="<?= L('切换侧栏位置') ?>">
      <?= cb_icon('panel-right') ?>
    </button>
  </div>
  <?php if (!empty($show_toc)): ?>
    <div class="cb-island-toc hide" id="island-toc">
      <div class="cb-island-toc__head"><?= L('目录') ?></div>
      <div class="cb-island-toc__list" id="island-toc-list"></div>
    </div>
  <?php endif; ?>
  <div class="cb-island-nav__inner">
    <?php foreach ($island_menu as $item): ?>
      <?php $type = (string)($item['type'] ?? ''); ?>
      <?php if ($type === 'divider'): ?>
        <hr class="cb-island-divider">
        <?php continue; ?>
      <?php endif; ?>
      <?php if (!isset($typeIcon[$type])): ?>
        <?php continue; ?>
      <?php endif; ?>
      <?php $collapsible = in_array($type, ['forums', 'tags'], true); ?>
      <<?= $collapsible ? 'details' : 'section' ?> class="cb-island-group cb-island-group--<?= h($type) ?>"<?= $collapsible ? ' open data-island-group' : '' ?>>
        <<?= $collapsible ? 'summary' : 'div' ?> class="cb-island-group__title">
          <?= cb_icon($typeIcon[$type]) ?>
          <span><?= h((string)($item['title'] ?? '')) ?></span>
          <?php if ($collapsible): ?><span class="cb-island-caret" aria-hidden="true"></span><?php endif; ?>
        </<?= $collapsible ? 'summary' : 'div' ?>>

        <?php if ($type === 'posts'): ?>
          <a class="cb-island-link <?= ($isHome || $isThread) ? 'active' : '' ?>" href="<?= h(url('')) ?>">
            <?= cb_icon('comment') ?>
            <span><?= L('所有的帖子') ?></span>
          </a>
        <?php elseif ($type === 'forums'): ?>
          <a class="cb-island-link <?= $route === 'forums' ? 'active' : '' ?>" href="<?= h(url('forums')) ?>">
            <?= cb_icon('folder') ?>
            <span><?= L('全部板块') ?></span>
          </a>
          <?php if (!$island_forums): ?>
            <div class="cb-island-empty"><?= L('暂无板块') ?></div>
          <?php endif; ?>
          <?php foreach ($island_forums as $node): ?>
            <?php
            $isBoard = (int)($node['type'] ?? 2) === 2;
            $nodeId = (int)($node['id'] ?? 0);
            ?>
            <?php if ($isBoard): ?>
              <a class="cb-island-link cb-island-forum-link <?= $activeForumId === $nodeId ? 'active' : '' ?>"
                 href="<?= h(url('forum/' . $nodeId)) ?>">
                <span class="cb-island-forum-dot" style="background:<?= h((string)($node['color'] ?? '#4D698E')) ?>"></span>
                <span><?= h((string)$node['name']) ?></span>
              </a>
            <?php else: ?>
              <div class="cb-island-forum-parent"><?= h((string)$node['name']) ?></div>
            <?php endif; ?>
            <?php foreach (($node['children'] ?? []) as $child): ?>
              <?php $childId = (int)($child['id'] ?? 0); ?>
              <a class="cb-island-link cb-island-forum-link cb-island-forum-link--child <?= $activeForumId === $childId ? 'active' : '' ?>"
                 href="<?= h(url('forum/' . $childId)) ?>">
                <span class="cb-island-forum-dot" style="background:<?= h((string)($child['color'] ?? '#4D698E')) ?>"></span>
                <span><?= h((string)$child['name']) ?></span>
              </a>
            <?php endforeach; ?>
          <?php endforeach; ?>
        <?php elseif ($type === 'tags'): ?>
          <?php if (!$island_tags): ?>
            <div class="cb-island-empty"><?= L('暂无标签') ?></div>
          <?php else: ?>
            <div class="cb-island-tags">
              <?php foreach ($island_tags as $itemTag): ?>
                <?php $tagId = (int)($itemTag['id'] ?? 0); ?>
                <a class="cb-island-tag <?= $currentTagId === $tagId ? 'active' : '' ?>"
                   href="<?= h(url('tag/' . $tagId)) ?>"
                   style="--tag-color:<?= h((string)($itemTag['color'] ?? '#4D698E')) ?>">
                  <span>#<?= h((string)$itemTag['name']) ?></span>
                  <em><?= (int)($itemTag['threads'] ?? 0) ?></em>
                </a>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        <?php elseif ($type === 'link'): ?>
          <?php
          $linkUrl = (string)($item['url'] ?? '');
          $external = str_starts_with($linkUrl, 'http://') || str_starts_with($linkUrl, 'https://');
          ?>
          <a class="cb-island-link" href="<?= h($external ? $linkUrl : url(ltrim($linkUrl, '/'))) ?>"
             <?php if ($external): ?>target="_blank" rel="noopener"<?php endif; ?>>
            <?= cb_icon('link') ?>
            <span><?= h((string)($item['title'] ?? '')) ?></span>
          </a>
        <?php endif; ?>
      </<?= $collapsible ? 'details' : 'section' ?>>
    <?php endforeach; ?>
  </div>
</aside>
