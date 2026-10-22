<div class="cb-card">
  <div class="cb-card__header"><?= L('聊天') ?></div>
  <div class="cb-card__body" style="padding:0">
    <div class="cb-pm">
      <div class="cb-pm__list">
        <?php if (!$conversations): ?>
          <div class="cb-empty" style="padding:20px"><div class="text-sm"><?= L('暂无会话') ?></div></div>
        <?php endif; ?>
        <?php foreach ($conversations as $c): ?>
          <a class="cb-pm__item <?= ($with['id'] ?? 0) === (int)$c['user_id'] ? 'active' : '' ?>"
             href="<?= h(url('pm?user=' . $c['user_id'])) ?>">
            <img class="cb-avatar cb-avatar-sm" src="<?= h(avatar_url($c['user'])) ?>" alt="">
            <div style="flex:1;min-width:0">
              <div style="font-size:13px;font-weight:500;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                <?= h($c['user']['nickname'] ?? $c['user']['username'] ?? '') ?>
              </div>
              <div class="text-xs text-muted" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                <?= h(str_cut($c['last']['content'], 20)) ?>
              </div>
            </div>
            <?php if ($c['unread'] > 0): ?><span class="cb-badge"><?= (int)$c['unread'] ?></span><?php endif; ?>
          </a>
        <?php endforeach; ?>
      </div>

      <div class="cb-pm__body">
        <?php if ($with): ?>
          <div class="cb-pm__messages">
            <?php foreach ($messages as $msg): ?>
              <?php $mine = (int)$msg['from_id'] === (int)$current_user['id']; ?>
              <div class="cb-pm__msg <?= $mine ? 'mine' : '' ?>">
                <img class="cb-avatar cb-avatar-sm" src="<?= h(avatar_url(['avatar' => $mine ? ($current_user['avatar'] ?? '') : ($with['avatar'] ?? '')])) ?>" alt="">
                <div>
                  <div class="cb-pm__bubble"><?= h($msg['content']) ?></div>
                  <div class="text-xs text-muted" style="margin-top:4px"><?= h(format_date($msg['created_at'])) ?></div>
                </div>
              </div>
            <?php endforeach; ?>
            <?php if (!$messages): ?><div class="cb-empty"><p><?= L('还没有消息，打个招呼吧') ?></p></div><?php endif; ?>
          </div>
          <form class="cb-pm__composer" method="post" action="<?= h(url('pm/send')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="to_id" value="<?= (int)$with['id'] ?>">
            <input class="cb-input" name="content" placeholder="<?= L('输入消息…') ?>" maxlength="1000" required autocomplete="off">
            <button class="cb-btn primary" type="submit"><?= L('发送') ?></button>
          </form>
        <?php else: ?>
          <div class="cb-empty" style="margin:auto"><div class="icon"><?= cb_icon('mail') ?></div><p><?= L('选择一个会话开始聊天') ?></p></div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
