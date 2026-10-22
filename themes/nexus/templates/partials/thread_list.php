<?php
/** @var array $threads */
$threads = $threads ?? [];
// $boxed = false 时用于嵌入 .cb-card 内部（去掉自身边框和圆角）
$boxed = $boxed ?? true;
// $compact = true 时为简洁模式：不显示正文摘要，紧凑单行
$compact = $compact ?? ((int)config('list_compact', 0) === 1);
$cls = ($boxed ? '' : ' cb-thread-list--plain') . ($compact ? ' cb-thread-list--compact' : '');
// vBulletin 风格状态图标
$imgBase = site_base() . '/static/images/forum/';
$hotLine = max(10, (int)config('thread_hot_replies', 20)); // 达到该回复数算热门
$threadIcon = function (array $t) use ($imgBase, $hotLine): string {
    $hot = (int)($t['replies'] ?? 0) >= $hotLine || (int)($t['views'] ?? 0) >= $hotLine * 20;
    $new = (int)($t['created_at'] ? (time() - strtotime((string)$t['created_at'])) : 999999) < 86400;
    if ((int)($t['is_closed'] ?? 0) === 1) {
        return $imgBase . 'thread_lock.gif';
    }
    if ($hot) {
        return $imgBase . ($new ? 'thread_hot_new.gif' : 'thread_hot.gif');
    }
    return $imgBase . ($new ? 'thread_new.gif' : 'thread.gif');
};
?>
<div class="cb-thread-list<?= $cls ?>">
  <?php if (!$threads): ?>
    <div class="cb-empty">
      <div class="icon"><?= cb_icon('inbox') ?></div>
      <p><?= L('还没有内容，来发第一个帖子吧。') ?></p>
    </div>
  <?php elseif ($compact): ?>
    <?php foreach ($threads as $t): ?>
      <article class="cb-trow">
        <img class="cb-trow__icon" src="<?= h($threadIcon($t)) ?>" alt="" width="20" height="20">
        <a class="cb-trow__avatar" href="<?= h(url('user/' . $t['user_id'])) ?>">
          <img class="cb-avatar" src="<?= h(avatar_url(['avatar' => $t['avatar'] ?? ''])) ?>" alt="">
        </a>
        <div class="cb-trow__main">
          <div class="cb-trow__title">
            <?php if ((int)($t['is_top'] ?? 0)): ?><span class="cb-badge-pill top"><?= L('置顶') ?></span><?php endif; ?>
            <?php if ((int)($t['is_essence'] ?? 0)): ?><span class="cb-badge-pill essence"><?= L('精华') ?></span><?php endif; ?>
            <?php if ((int)($t['is_closed'] ?? 0)): ?><span class="cb-badge-pill closed"><?= L('已关闭') ?></span><?php endif; ?>
            <a href="<?= h(url('thread/' . $t['id'])) ?>"
               <?php if (!empty($t['title_color'])): ?>style="color:<?= h($t['title_color']) ?>"<?php endif; ?>
               title="<?= h($t['title']) ?>"><?= h($t['title']) ?></a>
          </div>
          <div class="cb-trow__meta">
            <a href="<?= h(url('user/' . $t['user_id'])) ?>"><?= h($t['nickname'] ?? $t['username'] ?? '') ?></a>
            <span><?= h(time_ago($t['created_at'])) ?></span>
            <?php if (!empty($t['last_reply_user'])): ?>
              <span class="arrow">←</span>
              <a href="<?= h(url('user/' . (int)$t['last_reply_user_id'])) ?>"><?= h($t['last_reply_user']) ?></a>
              <span><?= h(time_ago($t['last_reply_at'])) ?></span>
            <?php endif; ?>
          </div>
        </div>
        <a class="cb-trow__count" href="<?= h(url('thread/' . $t['id'])) ?>" title="回复 <?= (int)($t['replies'] ?? 0) ?>">
          <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"
               stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
          </svg>
          <span><?= (int)($t['replies'] ?? 0) ?></span>
        </a>
      </article>
    <?php endforeach; ?>
  <?php else: ?>
    <?php foreach ($threads as $t): ?>
      <div class="cb-thread">
        <div class="cb-thread__avatar">
          <img class="cb-thread__icon" src="<?= h($threadIcon($t)) ?>" alt="" width="20" height="20">
          <a href="<?= h(url('user/' . $t['user_id'])) ?>">
            <img class="cb-avatar cb-avatar-md" src="<?= h(avatar_url(['avatar' => $t['avatar'] ?? ''])) ?>" alt="">
          </a>
        </div>
        <div class="cb-thread__main">
          <h3 class="cb-thread__title">
            <?php if ((int)($t['is_top'] ?? 0)): ?><span class="cb-badge-pill top"><?= L('置顶') ?></span><?php endif; ?>
            <?php if ((int)($t['is_essence'] ?? 0)): ?><span class="cb-badge-pill essence"><?= L('精华') ?></span><?php endif; ?>
            <?php if ((int)($t['is_digest'] ?? 0)): ?><span class="cb-badge-pill digest"><?= L('推荐') ?></span><?php endif; ?>
            <?php if ((int)($t['is_closed'] ?? 0)): ?><span class="cb-badge-pill closed"><?= L('已关闭') ?></span><?php endif; ?>
            <?php if (!empty($t['mark_id'])):
              $mark = null;
              foreach (($marks ?? []) as $mk) { if ((int)$mk['id'] === (int)$t['mark_id']) { $mark = $mk; break; } }
              if (!$mark) {
                $mark = \App\Core\Database::fetch('SELECT * FROM ' . \App\Core\Database::table('thread_marks') . ' WHERE id = ?', [$t['mark_id']]);
              }
              if ($mark): ?>
                <span class="cb-badge-pill" style="background:<?= h($mark['color'] . '1a') ?>;color:<?= h($mark['color']) ?>">
                  <?= h($mark['name']) ?>
                </span>
              <?php endif; ?>
            <?php endif; ?>
            <a href="<?= h(url('thread/' . $t['id'])) ?>"
               <?php if (!empty($t['title_color'])): ?>style="color:<?= h($t['title_color']) ?>"<?php endif; ?>>
              <?= h($t['title']) ?>
            </a>
          </h3>

          <?php if (!empty($t['tags'])): ?>
            <div class="cb-thread__tags" style="margin-bottom:4px">
              <?php foreach ($t['tags'] as $tag): ?>
                <a class="cb-tag" href="<?= h(url('tag/' . $tag['id'])) ?>"
                   style="background:<?= h($tag['color'] . '1a') ?>;color:<?= h($tag['color']) ?>"><?= h($tag['name']) ?></a>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <?php if (!empty($t['content_text'])): ?>
            <div class="cb-thread__excerpt"><?= h(str_cut($t['content_text'], 90)) ?></div>
          <?php endif; ?>

          <div class="cb-thread__meta">
            <a href="<?= h(url('user/' . $t['user_id'])) ?>"><?= h($t['nickname'] ?? $t['username'] ?? '') ?></a>
            <?php if (!empty($t['forum_name'])): ?>
              <a href="<?= h(url('forum/' . $t['forum_id'])) ?>"><?= h($t['forum_name']) ?></a>
            <?php endif; ?>
            <span><?= h(time_ago($t['created_at'])) ?></span>
            <?php if (!empty($t['last_reply_user'])): ?>
              <span class="cb-thread__last">最后回复 <?= h($t['last_reply_user']) ?> · <?= h(time_ago($t['last_reply_at'])) ?></span>
            <?php endif; ?>
          </div>
        </div>
        <div class="cb-thread__stat">
          <b><?= (int)($t['replies'] ?? 0) ?></b><span><?= L('回复') ?></span>
        </div>
        <div class="cb-thread__stat">
          <b><?= (int)($t['views'] ?? 0) ?></b><span><?= L('浏览') ?></span>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
