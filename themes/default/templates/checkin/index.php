<?php
$m = (string)$month;
$ts = strtotime($m . '-01');
$dayCount = (int)date('t', $ts);
$firstDow = (int)date('w', $ts);
$firstDow = ($firstDow + 6) % 7;
?>
<div class="cb-checkin-hero">
  <h2><?= L('每日签到') ?></h2>
  <div style="opacity:.9"><?= L('连续签到可获得更多奖励') ?></div>
  <?php if ($current_user): ?>
    <button class="btn-checkin" id="btn-checkin" <?= $checked ? 'disabled' : '' ?>>
      <?= $checked ? L('今日已签') : L('签到') ?>
    </button>
    <div style="margin-top:14px">
      <?= L('已连续') ?> <b><?= (int)$continue_days ?></b> <?= L('天 · 累计') ?> <b><?= (int)$total_days ?></b> <?= L('天 · 补签卡') ?> <b><?= (int)$cards ?></b> 张
    </div>
  <?php else: ?>
    <a class="cb-btn lg" style="background:#fff;color:var(--color-primary);margin-top:16px" href="<?= h(url('login')) ?>"><?= L('登录后可签到') ?></a>
  <?php endif; ?>
</div>

<div class="cb-card mt-16">
  <div class="cb-card__header"><?= L('签到日历') ?></div>
  <div class="cb-card__body">
    <div class="cb-calendar">
      <div class="cb-calendar__head">
        <span><?= h($m) ?></span>
        <span class="text-xs text-muted">
          <span style="display:inline-block;width:10px;height:10px;background:var(--color-primary);border-radius:2px"></span> <?= L('已签到') ?>
          <span style="display:inline-block;width:10px;height:10px;background:var(--color-warning);border-radius:2px;margin-left:8px"></span> <?= L('补签') ?>
        </span>
      </div>
      <div class="cb-calendar__grid">
        <?php foreach ([L('一'), L('二'), L('三'), L('四'), L('五'), L('六'), L('日')] as $w): ?><div class="wd"><?= $w ?></div><?php endforeach; ?>
        <?php for ($i = 0; $i < $firstDow; $i++): ?><div class="d empty"></div><?php endfor; ?>
        <?php for ($d = 1; $d <= $dayCount; $d++):
          $key = str_pad((string)$d, 2, '0', STR_PAD_LEFT);
          $cls = 'd';
          if ($key === date('d') && $m === date('Y-m')) $cls .= ' today';
          if (isset($records[$key])) $cls .= $records[$key]['makeup'] ? ' makeup' : ' checked';
        ?>
          <div class="<?= $cls ?>"><?= $d ?></div>
        <?php endfor; ?>
      </div>
    </div>
  </div>
</div>

<div class="cb-card">
  <div class="cb-card__header"><?= L('未来 7 天奖励预览') ?></div>
  <div class="cb-card__body">
    <div class="cb-reward-list">
      <?php foreach ($preview as $p): ?>
        <div class="cb-reward-item">
          <b><?= (float)$p['reward'] ?></b>
          <span>第 <?= (int)$p['day'] ?> 天</span>
          <?php if ($p['bonus'] > 0): ?><div class="text-xs" style="color:var(--color-warning)">+<?= (float)$p['bonus'] ?></div><?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="text-xs text-muted" style="margin-top:10px">单位：<?= h(currency_name()) ?></div>
  </div>
</div>

<?php if ($current_user): ?>
  <div class="cb-card">
    <div class="cb-card__header"><?= L('补签卡') ?></div>
    <div class="cb-card__body">
      <div class="text-sm"><?= L('当前拥有') ?> <b><?= (int)$cards ?></b> 张补签卡（售价 <?= h(config('card_price', 20)) ?> <?= h(currency_name()) ?>）</div>
      <form method="post" action="<?= h(url('checkin/buy-card')) ?>" class="flex gap-8 items-center mt-8">
        <?= csrf_field() ?>
        <button class="cb-btn sm primary" type="submit"><?= L('购买补签卡') ?></button>
      </form>
      <form method="post" action="<?= h(url('checkin/makeup')) ?>" class="flex gap-8 items-center mt-8">
        <?= csrf_field() ?>
        <input class="cb-input" type="date" name="day" style="max-width:180px" required>
        <button class="cb-btn sm" type="submit"><?= L('使用补签卡') ?></button>
      </form>
    </div>
  </div>
<?php endif; ?>

<div class="cb-card">
  <div class="cb-card__header"><?= L('签到排行榜') ?></div>
  <div class="cb-card__body">
    <?php foreach ($rank as $i => $r): ?>
      <div class="cb-list-item">
        <span class="text-muted" style="width:20px"><?= $i + 1 ?></span>
        <img class="cb-avatar cb-avatar-sm" src="<?= h(avatar_url(['avatar' => $r['avatar'] ?? ''])) ?>" alt="">
        <a class="t" href="<?= h(url('user/' . $r['user_id'])) ?>" style="color:var(--color-text)"><?= h($r['nickname'] ?: $r['username']) ?></a>
        <span class="text-xs text-muted">累计 <?= (int)$r['days'] ?> 天 · 最长 <?= (int)$r['best'] ?> 天</span>
      </div>
    <?php endforeach; ?>
    <?php if (!$rank): ?><div class="text-sm text-muted"><?= L('暂无数据') ?></div><?php endif; ?>
  </div>
</div>
