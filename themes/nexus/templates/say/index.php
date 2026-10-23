<?php $load_editor = true; ?>
<?php if ($can_publish ?? false): ?>
  <div class="cb-card cb-card--editor">
    <div class="cb-card__header"><?= L('发布说说') ?></div>
    <div class="cb-card__body">
      <form method="post" action="<?= h(url('say/publish')) ?>" id="say-form">
        <?= csrf_field() ?>

        <!-- 简洁模式 -->
        <div id="say-simple">
          <textarea class="cb-textarea" name="content" id="say_text" rows="3"
                    placeholder="<?= L('这一刻的想法…（Ctrl + Enter 发布）') ?>"></textarea>
        </div>

        <!-- 高级模式 -->
        <div id="say-advanced" class="hide">
          <div id="say-editor" data-editor data-input="say_html" data-placeholder="<?= L('这一刻的想法…') ?>"></div>
          <input type="hidden" name="content" id="say_html" disabled>
        </div>

        <div class="mt-8 flex justify-between items-center">
          <a class="cb-btn sm" id="btn-say-mode" href="javascript:void(0)"><?= L('高级模式') ?></a>
          <button class="cb-btn primary" type="submit"><?= L('发布') ?></button>
        </div>
      </form>
      <script>
        (function () {
          var btn = document.getElementById('btn-say-mode');
          var simple = document.getElementById('say-simple');
          var adv = document.getElementById('say-advanced');
          var text = document.getElementById('say_text');
          var html = document.getElementById('say_html');
          if (!btn || !simple || !adv || !text || !html) return;
          var advanced = false;
          btn.addEventListener('click', function () {
            advanced = !advanced;
            simple.classList.toggle('hide', advanced);
            adv.classList.toggle('hide', !advanced);
            btn.textContent = advanced ? '简洁模式' : '高级模式';
            if (advanced) {
              html.value = text.value.replace(/</g, '&lt;').replace(/\n/g, '<br>');
              html.disabled = false; text.disabled = true;
              if (window.CBEditor) { window.CBEditor.el.innerHTML = html.value; window.CBEditor.sync(); }
            } else {
              text.value = html.value.replace(/<br\s*\/?>/gi, '\n').replace(/<[^>]+>/g, '');
              text.disabled = false; html.disabled = true;
            }
          });
        })();
      </script>
    </div>
  </div>
<?php endif; ?>

<?php foreach ($says as $s): ?>
  <?php $imgs = json_decode((string)($s['images'] ?: '[]'), true) ?: []; ?>
  <div class="cb-say" id="say-<?= (int)$s['id'] ?>">
    <div class="cb-say__head">
      <img class="cb-avatar cb-avatar-md" src="<?= h(avatar_url(['avatar' => $s['avatar'] ?? ''])) ?>" alt="">
      <div>
        <a href="<?= h(url('user/' . $s['user_id'])) ?>" style="font-weight:600;color:var(--color-text)"><?= h($s['nickname'] ?? $s['username']) ?></a>
        <div class="text-xs text-muted"><?= h(time_ago($s['created_at'])) ?></div>
      </div>
    </div>
    <div class="cb-say__content"><?= $s['content'] ?></div>
    <?php if ($imgs): ?>
      <div class="cb-say__images">
        <?php foreach ($imgs as $img): ?><img src="<?= h($img) ?>" alt="" loading="lazy"><?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div class="cb-say__actions">
      <?php if (($allow_like ?? 1)): ?>
        <button type="button" data-like data-type="say" data-id="<?= (int)$s['id'] ?>"
                data-guest="<?= $current_user ? 0 : 1 ?>"
                class="<?= in_array((int)$s['id'], ($liked ?? []), true) ? 'on' : '' ?>"
                style="border:none;background:none;cursor:pointer;color:inherit">
          <?= cb_icon('thumbs-up') ?> <span class="count"><?= (int)$s['likes'] ?></span>
        </button>
      <?php endif; ?>
      <span><?= cb_icon('comment') ?> <?= (int)$s['comments'] ?></span>
      <?php if ($current_user && (int)$s['user_id'] === (int)$current_user['id']): ?>
        <form method="post" action="<?= h(url('say/' . $s['id'] . '/delete')) ?>" style="display:inline">
          <?= csrf_field() ?>
          <button type="submit" style="border:none;background:none;cursor:pointer;color:inherit"><?= cb_icon('trash') ?> <?= L('删除') ?></button>
        </form>
      <?php endif; ?>
    </div>

    <?php $comments = \App\Models\Say::comments((int)$s['id']); ?>
    <?php if ($comments || ($allow_comment ?? 1)): ?>
      <div class="cb-say__comments">
        <?php foreach ($comments as $c): ?>
          <div class="cb-say__comment">
            <b><?= h($c['username']) ?>：</b><?= h($c['content']) ?>
            <span class="text-xs text-muted"><?= h(time_ago($c['created_at'])) ?></span>
            <?php if ($current_user && ((int)$c['user_id'] === (int)$current_user['id'] || is_admin())): ?>
              <form method="post" action="<?= h(url('say/comment/' . $c['id'] . '/delete')) ?>" style="display:inline">
                <?= csrf_field() ?>
                <button type="submit" class="text-xs" style="border:none;background:none;color:var(--color-danger);cursor:pointer"><?= L('删除') ?></button>
              </form>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>

        <?php if (($allow_comment ?? 1) && $current_user): ?>
          <form method="post" action="<?= h(url('say/' . $s['id'] . '/comment')) ?>" style="display:flex;gap:8px;margin-top:8px">
            <?= csrf_field() ?>
            <input class="cb-input" name="content" placeholder="<?= L('写评论…') ?>" maxlength="300" required>
            <button class="cb-btn sm primary" type="submit"><?= L('发送') ?></button>
          </form>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
<?php endforeach; ?>

<?php if (!$says): ?><div class="cb-empty"><div class="icon"><?= cb_icon('comment') ?></div><p><?= L('还没有人发说说') ?></p></div><?php endif; ?>
<?= $pager ?? '' ?>
