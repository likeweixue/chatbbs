<?php
/**
 * 后台通用页面容器
 * 变量：$title, $html, $actions, $breadcrumb
 */
$actions = $actions ?? [];
?>
<?php $hasPageHead = $actions; ?>
<?php if ($hasPageHead): ?>
<div class="adm-page-head">
    <h2><?= h((string)($title ?? '')) ?></h2>
    <div class="spacer"></div>
    <?php foreach ($actions as $act): ?>
        <?php if (($act['type'] ?? 'link') === 'post'): ?>
            <form method="post" action="<?= h((string)$act['url']) ?>" style="display:inline">
                <?= csrf_field() ?>
                <button class="adm-btn <?= h((string)($act['class'] ?? '')) ?>" type="submit"
                    <?= !empty($act['confirm']) ? ' onclick="return confirm(\'' . h((string)$act['confirm']) . '\')"' : '' ?>>
                    <?= h((string)$act['label']) ?>
                </button>
            </form>
        <?php else: ?>
            <a class="adm-btn <?= h((string)($act['class'] ?? '')) ?>" href="<?= h((string)$act['url']) ?>"
                <?= !empty($act['confirm']) ? ' onclick="return confirm(\'' . h((string)$act['confirm']) . '\')"' : '' ?>>
                <?= h((string)$act['label']) ?>
            </a>
        <?php endif; ?>
    <?php endforeach; ?>
</div>
<?php endif; ?>
<div class="adm-unified">
<?php
// 页面主题：无现成面板的表单 / 提示条自动包成带标题的面板
$body = (string)($html ?? '');
$pageTitle = (string)($title ?? '');
$trimmed = ltrim($body);
if (str_starts_with($trimmed, '<form')) {
    $body = '<div class="adm-panel" data-page-panel>'
        . '<div class="adm-panel-head">' . h($pageTitle !== '' ? $pageTitle : '设置') . '</div>'
        . '<div class="adm-panel-body">' . $body . '</div></div>';
} elseif (strpos($body, '<div class="adm-panel"') === false) {
    // 提示条 + 表单等没有现成面板的页面，整体包成带标题的面板
    $body = '<div class="adm-panel" data-page-panel>'
        . '<div class="adm-panel-head">' . h($pageTitle !== '' ? $pageTitle : '设置') . '</div>'
        . '<div class="adm-panel-body">' . $body . '</div></div>';
}
?>
<?= $body ?>
</div>
