<?php
$p = $profile;
$lv = $p['level'] ?? null;
$cert = $p['cert'] ?? null;
$grp = $p['group'] ?? null;
$prog = $level_progress ?? [];
$guestbook = $guestbook ?? [];
$guestbook_count = $guestbook_count ?? count($guestbook);
$guestbook_enabled = $guestbook_enabled ?? true;
?>
<div class="cb-profile-header">
  <img class="cb-avatar cb-avatar-lg" src="<?= h(avatar_url($p)) ?>" alt="">
  <div class="cb-profile-header__info">
    <div class="cb-profile-header__name">
      <?= h($p['nickname'] ?: $p['username']) ?>
      <?php if ($lv): ?>
        <span class="cb-badge-pill" style="background:var(--color-primary-light);color:var(--color-primary)"><?= h($lv['name']) ?></span>
      <?php endif; ?>
      <?php if ($cert): ?>
        <span class="cb-badge-pill" style="background:<?= h($cert['color'] . '1a') ?>;color:<?= h($cert['color']) ?>"><?= h($cert['name']) ?></span>
      <?php endif; ?>
      <?php if ($grp): ?>
        <span class="cb-badge-pill" style="background:<?= h($grp['color'] . '1a') ?>;color:<?= h($grp['color']) ?>"><?= h($grp['name']) ?></span>
      <?php endif; ?>
    </div>
    <div class="cb-profile-header__meta">
      <span>@<?= h($p['username']) ?></span>
      <span>注册 <?= h(format_date($p['created_at'], 'Y-m-d')) ?></span>
      <span>最后活跃 <?= h(time_ago($p['last_active_at'] ?? $p['created_at'])) ?></span>
      <span><?= h(money($stats['money'] ?? 0)) ?></span>
    </div>
    <?php if (!empty($p['signature'])): ?>
      <div class="text-sm text-muted" style="margin-top:8px">签名：<?= h($p['signature']) ?></div>
    <?php endif; ?>
    <?php if (!empty($p['bio'])): ?>
      <div class="text-sm" style="margin-top:6px"><?= h($p['bio']) ?></div>
    <?php endif; ?>

    <div class="cb-profile-stats">
      <div class="cb-stat-item"><b><?= (int)($stats['threads'] ?? 0) ?></b><span><?= L('主题') ?></span></div>
      <div class="cb-stat-item"><b><?= (int)($stats['posts'] ?? 0) ?></b><span><?= L('回复') ?></span></div>
      <div class="cb-stat-item"><b><?= (int)($stats['likes_received'] ?? 0) ?></b><span><?= L('获赞') ?></span></div>
      <div class="cb-stat-item"><b><?= (int)($stats['followers'] ?? 0) ?></b><span><?= L('粉丝') ?></span></div>
      <div class="cb-stat-item"><b><?= (int)($stats['following'] ?? 0) ?></b><span><?= L('关注') ?></span></div>
      <div class="cb-stat-item"><b><?= (int)($stats['friends'] ?? 0) ?></b><span><?= L('好友') ?></span></div>
      <div class="cb-stat-item"><b><?= (int)($stats['exp'] ?? 0) ?></b><span><?= L('经验') ?></span></div>
    </div>

    <?php if (!empty($prog['next'])): ?>
      <div style="margin-top:12px">
        <div class="text-xs text-muted">
          距离 <?= h($prog['next']['name']) ?> 还需 <?= (int)max(0, ($prog['next']['min_exp'] - (int)$p['exp'])) ?> 经验
        </div>
        <div style="height:6px;background:var(--color-border);border-radius:3px;margin-top:4px;overflow:hidden">
          <div style="height:100%;width:<?= (float)$prog['percent'] ?>%;background:var(--color-primary)"></div>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <div style="flex:none;display:flex;flex-direction:column;gap:8px">
    <?php if ($is_self): ?>
      <a class="cb-btn sm" href="<?= h(url('user/settings')) ?>"><?= L('账号设置') ?></a>
      <a class="cb-btn sm" href="<?= h(url('user/attachments')) ?>"><?= L('我的附件库') ?></a>
    <?php else: ?>
      <?php if ($current_user): ?>
        <button class="cb-btn sm" data-action="follow" data-user-id="<?= (int)$p['id'] ?>">
          <?= $is_following ? L('取消关注') : L('+ 关注') ?>
        </button>
        <a class="cb-btn sm primary" href="<?= h(url('pm?user=' . $p['id'])) ?>"><?= L('发消息') ?></a>
        <button class="cb-btn sm" data-action="<?= $is_friend ? 'unfriend' : 'friend' ?>" data-user-id="<?= (int)$p['id'] ?>">
          <?= $is_friend ? L('解除好友') : L('加为好友') ?>
        </button>
        <button class="cb-btn sm" data-action="block" data-user-id="<?= (int)$p['id'] ?>"
                data-confirm="1" data-confirm-text="确定屏蔽该用户？"><?= L('屏蔽') ?></button>
      <?php else: ?>
        <a class="cb-btn sm primary" href="<?= h(url('login')) ?>"><?= L('登录后互动') ?></a>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>

<div class="cb-card" id="guestbook">
  <div class="cb-card__header">
    <span><?= L('留言板') ?> <span class="more"><?= (int)$guestbook_count ?> <?= L('条') ?></span></span>
    <?php if ($is_self): ?>
      <form method="post" action="<?= h(url('user/settings/guestbook')) ?>" class="cb-guestbook-toggle">
        <?= csrf_field() ?>
        <label class="cb-checkbox">
          <input type="checkbox" name="guestbook_enable" value="1" <?= $guestbook_enabled ? 'checked' : '' ?>
                 onchange="this.form.submit()"> <?= L('允许留言') ?>
        </label>
      </form>
    <?php endif; ?>
  </div>
  <div class="cb-card__body">
    <?php if ($guestbook_enabled): ?>
      <?php if ($current_user): ?>
        <form method="post" action="<?= h(url('user/' . (int)$p['id'] . '/guestbook')) ?>" class="cb-guestbook-form">
          <?= csrf_field() ?>
          <textarea class="cb-textarea" name="content" rows="3" maxlength="300" required
                    placeholder="<?= L('写下你的留言…（最多 300 字）') ?>"></textarea>
          <div class="flex justify-between items-center" style="margin-top:8px">
            <span class="text-xs text-muted"><?= L('友善交流，留言会通知对方') ?></span>
            <button class="cb-btn primary" type="submit"><?= L('发表留言') ?></button>
          </div>
        </form>
      <?php else: ?>
        <div class="text-sm text-muted"><a href="<?= h(url('login')) ?>"><?= L('登录') ?></a> <?= L('后可以留言') ?></div>
      <?php endif; ?>
    <?php else: ?>
      <div class="cb-empty"><div class="icon"><?= cb_icon('comment') ?></div><p><?= L('该用户已关闭留言板') ?></p></div>
    <?php endif; ?>

    <?php if ($guestbook): ?>
      <div class="cb-guestbook-list">
        <?php foreach ($guestbook as $g): ?>
          <div class="cb-guestbook-item">
            <img class="cb-avatar cb-avatar-sm" src="<?= h(avatar_url(['avatar' => $g['avatar'] ?? ''])) ?>" alt="">
            <div class="cb-guestbook-item__main">
              <div class="cb-guestbook-item__meta">
                <a href="<?= h(url('user/' . (int)$g['user_id'])) ?>"><?= h($g['nickname'] ?: $g['username']) ?></a>
                <span><?= h(time_ago((string)$g['created_at'])) ?></span>
                <?php if ($current_user && ($is_self || is_admin() || (int)$g['user_id'] === (int)$current_user['id'])): ?>
                  <form method="post" action="<?= h(url('user/guestbook/' . (int)$g['id'] . '/delete')) ?>" style="display:inline;margin-left:auto">
                    <?= csrf_field() ?>
                    <button type="submit" class="cb-link-danger" onclick="return confirm('确定删除这条留言？')"><?= L('删除') ?></button>
                  </form>
                <?php endif; ?>
              </div>
              <div class="cb-guestbook-item__content"><?= nl2br(h((string)$g['content'])) ?></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="text-sm text-muted" style="margin-top:12px"><?= L('还没有留言，来抢沙发吧。') ?></div>
    <?php endif; ?>
  </div>
</div>

<div class="cb-card mt-16">
  <div class="cb-card__header"><?= L('最近主题') ?></div>
  <div class="cb-card__body">
    <?php foreach ($threads as $t): ?>
      <div class="cb-list-item">
        <a class="t" href="<?= h(url('thread/' . $t['id'])) ?>"<?php if (!empty($t['title_color'])): ?> style="color:<?= h((string)$t['title_color']) ?>"<?php endif; ?>><?= h(str_cut($t['title'], 30)) ?></a>
        <span class="text-xs text-muted"><?= h(time_ago($t['created_at'])) ?></span>
      </div>
    <?php endforeach; ?>
    <?php if (!$threads): ?><div class="text-sm text-muted"><?= L('暂无主题') ?></div><?php endif; ?>
  </div>
</div>

<div class="cb-card">
  <div class="cb-card__header"><?= L('最近回复') ?></div>
  <div class="cb-card__body">
    <?php foreach ($posts as $pp): ?>
      <div class="cb-list-item">
        <a class="t" href="<?= h(url('thread/' . $pp['thread_id'] . '#post-' . $pp['id'])) ?>" style="color:var(--color-text)">
          <?= h(str_cut($pp['content_text'], 40)) ?>
        </a>
        <span class="text-xs text-muted"><?= h(time_ago($pp['created_at'])) ?></span>
      </div>
    <?php endforeach; ?>
    <?php if (!$posts): ?><div class="text-sm text-muted"><?= L('暂无回复') ?></div><?php endif; ?>
  </div>
</div>

<?php if (!empty($says)): ?>
  <div class="cb-card">
    <div class="cb-card__header"><?= L('最近说说') ?></div>
    <div class="cb-card__body">
      <?php foreach ($says as $s): ?>
        <div class="cb-list-item">
          <span class="t"><?= h(str_cut($s['content'], 50)) ?></span>
          <span class="text-xs text-muted"><?= h(time_ago($s['created_at'])) ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>

<div class="cb-card">
  <div class="cb-card__header"><?= L('粉丝 / 关注') ?></div>
  <div class="cb-card__body">
    <div class="text-sm" style="margin-bottom:8px">粉丝（<?= count($followers) ?>）</div>
    <div class="cb-user-list">
      <?php foreach (array_slice($followers, 0, 20) as $f): ?>
        <a href="<?= h(url('user/' . $f['user_id'])) ?>" title="<?= h($f['nickname'] ?: $f['username']) ?>">
          <img class="cb-avatar" style="width:34px;height:34px;border-radius:8px" src="<?= h(avatar_url(['avatar' => $f['avatar'] ?? ''])) ?>" alt="">
        </a>
      <?php endforeach; ?>
      <?php if (!$followers): ?><span class="text-sm text-muted"><?= L('暂无') ?></span><?php endif; ?>
    </div>
    <div class="text-sm" style="margin:12px 0 8px">关注（<?= count($following) ?>）</div>
    <div class="cb-user-list">
      <?php foreach (array_slice($following, 0, 20) as $f): ?>
        <a href="<?= h(url('user/' . $f['follow_id'])) ?>" title="<?= h($f['nickname'] ?: $f['username']) ?>">
          <img class="cb-avatar" style="width:34px;height:34px;border-radius:8px" src="<?= h(avatar_url(['avatar' => $f['avatar'] ?? ''])) ?>" alt="">
        </a>
      <?php endforeach; ?>
      <?php if (!$following): ?><span class="text-sm text-muted"><?= L('暂无') ?></span><?php endif; ?>
    </div>
  </div>
</div>
