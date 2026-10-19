<?php
/**
 * 后台通用页面容器
 * 变量：$title, $html, $actions, $breadcrumb
 */
$actions = $actions ?? [];
?>
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
<?= $html ?? '' ?>
