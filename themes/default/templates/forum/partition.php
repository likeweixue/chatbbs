<div class="cb-card">
  <div class="cb-card__body">
    <div class="flex items-center justify-between" style="gap:12px;flex-wrap:wrap">
      <div>
        <h1 style="margin:0;font-size:20px;display:flex;align-items:center;gap:10px">
          <?= forum_icon_html($forum, 'cb-forum-head__icon') ?><span><?= h((string)$forum['name']) ?></span>
        </h1>
        <?php if (trim((string)$forum['description']) !== ''): ?>
          <p class="text-sm text-muted" style="margin:4px 0 0"><?= h((string)$forum['description']) ?></p>
        <?php endif; ?>
      </div>
    </div>
    <div class="text-xs text-muted" style="margin-top:10px"><?= count($boards) ?> 个板块</div>
  </div>
</div>

<div class="cb-card">
  <div class="cb-card__header"><?= L('板块') ?></div>
  <div class="cb-card__body" style="padding:0">
    <?php if (empty($boards)): ?>
      <div class="cb-empty" style="padding:24px"><?= L('该分区下还没有板块') ?></div>
    <?php else: ?>
      <table class="cb-forum-table">
        <tbody>
          <?php foreach ($boards as $b): ?>
            <?php if (($b['can_view'] ?? true) === false) { continue; } ?>
            <tr class="fl_row">
              <td class="fl_ct">
                <h2><?= forum_icon_html($b, 'cb-forum-row-icon') ?><a href="<?= h(url('forum/' . $b['id'])) ?>"><?= h((string)$b['name']) ?></a></h2>
                <?php if (trim((string)$b['description']) !== ''): ?>
                  <p class="fl_desc"><?= h((string)$b['description']) ?></p>
                <?php endif; ?>
              </td>
              <td class="fl_i">
                <span class="fl_num"><?= (int)$b['threads'] ?></span>
                <span class="fl_sub"> / <?= (int)$b['posts'] ?></span>
                <?php if ((int)($b['today_threads'] ?? 0) > 0): ?>
                  <div class="fl_today"><?= L('今日') ?> <?= (int)$b['today_threads'] ?></div>
                <?php endif; ?>
              </td>
              <td class="fl_by">
                <?php if (!empty($b['last_thread_title'])): ?>
                  <div><a href="<?= h(url('thread/' . (int)$b['last_thread_id'])) ?>"><?= h(str_cut((string)$b['last_thread_title'], 26)) ?></a></div>
                  <cite>
                    <?= h(format_date($b['last_post_at'] ?: date('Y-m-d H:i:s'))) ?>
                    <?php if (!empty($b['last_post_user'])): ?>
                      <a href="<?= h(url('user/' . (int)($b['last_post_uid'] ?? 0))) ?>"><?= h((string)$b['last_post_user']) ?></a>
                    <?php endif; ?>
                  </cite>
                <?php else: ?>
                  <span class="text-muted"><?= L('从未') ?></span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>
