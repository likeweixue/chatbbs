<div class="cb-card">
  <div class="cb-card__header">好友（<?= count($friends) ?>）</div>
  <div class="cb-card__body">
    <?php if (!$friends): ?><div class="text-sm text-muted"><?= L('还没有好友') ?></div><?php endif; ?>
    <?php foreach ($friends as $f): ?>
      <div class="cb-list-item">
        <img class="cb-avatar cb-avatar-sm" src="<?= h(avatar_url(['avatar' => $f['avatar'] ?? ''])) ?>" alt="">
        <a class="t" href="<?= h(url('user/' . $f['friend_id'])) ?>" style="color:var(--color-text)"><?= h($f['nickname'] ?: $f['username']) ?></a>
        <button class="cb-btn sm" data-action="unfriend" data-user-id="<?= (int)$f['friend_id'] ?>"><?= L('解除') ?></button>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<div class="cb-card">
  <div class="cb-card__header">关注（<?= count($following) ?>）</div>
  <div class="cb-card__body">
    <?php if (!$following): ?><div class="text-sm text-muted"><?= L('暂未关注任何人') ?></div><?php endif; ?>
    <?php foreach ($following as $f): ?>
      <div class="cb-list-item">
        <img class="cb-avatar cb-avatar-sm" src="<?= h(avatar_url(['avatar' => $f['avatar'] ?? ''])) ?>" alt="">
        <a class="t" href="<?= h(url('user/' . $f['follow_id'])) ?>" style="color:var(--color-text)"><?= h($f['nickname'] ?: $f['username']) ?></a>
        <button class="cb-btn sm" data-action="follow" data-user-id="<?= (int)$f['follow_id'] ?>"><?= L('取消关注') ?></button>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<div class="cb-card">
  <div class="cb-card__header">粉丝（<?= count($followers) ?>）</div>
  <div class="cb-card__body">
    <?php if (!$followers): ?><div class="text-sm text-muted"><?= L('还没有粉丝') ?></div><?php endif; ?>
    <?php foreach ($followers as $f): ?>
      <div class="cb-list-item">
        <img class="cb-avatar cb-avatar-sm" src="<?= h(avatar_url(['avatar' => $f['avatar'] ?? ''])) ?>" alt="">
        <a class="t" href="<?= h(url('user/' . $f['user_id'])) ?>" style="color:var(--color-text)"><?= h($f['nickname'] ?: $f['username']) ?></a>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<div class="cb-card">
  <div class="cb-card__header">黑名单（<?= count($blacklist) ?>）</div>
  <div class="cb-card__body">
    <?php if (!$blacklist): ?><div class="text-sm text-muted"><?= L('黑名单为空') ?></div><?php endif; ?>
    <?php foreach ($blacklist as $b): ?>
      <div class="cb-list-item">
        <img class="cb-avatar cb-avatar-sm" src="<?= h(avatar_url(['avatar' => $b['avatar'] ?? ''])) ?>" alt="">
        <span class="t"><?= h($b['nickname'] ?: $b['username']) ?></span>
        <button class="cb-btn sm" data-action="block" data-user-id="<?= (int)$b['block_id'] ?>"><?= L('解除') ?></button>
      </div>
    <?php endforeach; ?>
  </div>
</div>
