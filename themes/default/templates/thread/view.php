<?php
$marks = \App\Models\Thread::marks();
$threadUser = $thread['user'] ?? [];
$canManage = $is_moderator ?? false;
$isOwner = ($current_user['id'] ?? 0) === (int)$thread['user_id'];
$subpostsAll = $subposts ?? [];
$replyPreview = max(1, (int)($reply_preview ?? 5));
// 帖子详情页在侧边栏展示「发布主题 + 目录」两个面板
$show_toc = true;
?>
<div class="cb-thread-detail">
  <div class="cb-thread-detail__head">
    <h1 class="cb-thread-detail__title">
      <?php if ((int)$thread['is_top']): ?><span class="cb-badge-pill top"><?= L('置顶') ?></span><?php endif; ?>
      <?php if ((int)$thread['is_essence']): ?><span class="cb-badge-pill essence"><?= L('精华') ?></span><?php endif; ?>
      <?php if ((int)$thread['is_closed']): ?><span class="cb-badge-pill closed"><?= L('已关闭') ?></span><?php endif; ?>
      <?php if (!empty($thread['mark'])): ?>
        <span class="cb-badge-pill" style="background:<?= h($thread['mark']['color'] . '1a') ?>;color:<?= h($thread['mark']['color']) ?>">
          <?= h($thread['mark']['name']) ?>
        </span>
      <?php endif; ?>
      <span <?php if (!empty($thread['title_color'])): ?>style="color:<?= h($thread['title_color']) ?>"<?php endif; ?>><?= h($thread['title']) ?></span>
    </h1>
    <div class="cb-thread-detail__meta">
      <a href="<?= h(url('user/' . $thread['user_id'])) ?>">
        <img class="cb-avatar cb-avatar-sm" src="<?= h(avatar_url($threadUser)) ?>" alt="">
        <?= h($threadUser['nickname'] ?? $thread['username']) ?>
      </a>
      <?php if (!empty($threadUser['level']['name'])): ?>
        <span class="cb-badge-pill" style="background:var(--color-primary-light);color:var(--color-primary)"><?= h($threadUser['level']['name']) ?></span>
      <?php endif; ?>
      <?php if (!empty($threadUser['cert'])): ?>
        <span class="cb-badge-pill" style="background:<?= h($threadUser['cert']['color'] . '1a') ?>;color:<?= h($threadUser['cert']['color']) ?>">
          <?= h($threadUser['cert']['name']) ?>
        </span>
      <?php endif; ?>
      <span>发布于 <?= h(format_date($thread['created_at'])) ?></span>
      <span><?= (int)$thread['views'] ?> 浏览</span>
      <span><?= (int)$thread['replies'] ?> 回复</span>
      <span><?= (int)$thread['likes'] ?> 赞</span>
      <a href="<?= h(url('forum/' . $thread['forum_id'])) ?>"><?= h($thread['forum']['name'] ?? '') ?></a>
      <?php if ($isOwner || $canManage): ?>
        <form method="post" action="<?= h(url('thread/' . (int)$thread['id'] . '/delete')) ?>"
              style="margin-left:auto" onsubmit="return confirm('确定删除该主题？删除后不可恢复。')">
          <?= csrf_field() ?>
          <button type="submit" class="cb-btn sm"><?= L('删除') ?></button>
        </form>
      <?php endif; ?>
    </div>

    <?php if (!empty($thread['tags'])): ?>
      <div style="margin-top:10px">
        <?php foreach ($thread['tags'] as $tag): ?>
          <a class="cb-tag" href="<?= h(url('tag/' . $tag['id'])) ?>" style="background:<?= h($tag['color'] . '1a') ?>;color:<?= h($tag['color']) ?>"><?= h($tag['name']) ?></a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php $licText = license_text($thread); ?>
    <?php if ($licText !== ''): ?>
      <div class="cb-license" style="margin-top:10px;font-size:12px;color:var(--color-text-2,#6b7280)">
        © <?= L('版权') ?>：<?= h($licText) ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="cb-postlist__bar">
    <b><?= L('最新回复') ?></b> <span class="muted">(<?= (int)$thread['replies'] ?>)</span>
  </div>

  <?php foreach ($posts as $i => $p): ?>
    <?php
    $pu = [
      'avatar' => $p['avatar'] ?? '',
      'nickname' => $p['nickname'] ?? $p['username'],
    ];
    ?>
    <div class="cb-post" id="post-<?= (int)$p['id'] ?>">
      <div class="cb-post__main">
        <?php if (!(int)($p['is_first'] ?? 0)): ?>
          <div class="cb-post__head">
            <a class="cb-post__author" href="<?= h(url('user/' . $p['user_id'])) ?>"><?= h($pu['nickname']) ?></a>
            <span class="cb-post__floor"><?= (int)$p['floor'] ?> 楼</span>
            <button type="button" class="cb-btn sm" data-quote="<?= h(str_cut($p['content_text'], 60)) ?>"><?= L('引用') ?></button>
            <button type="button" class="cb-btn sm" data-reply-to="<?= (int)$p['id'] ?>"><?= L('回复') ?></button>
            <?php if ($canManage || ($current_user['id'] ?? 0) === (int)$p['user_id']): ?>
              <form method="post" action="<?= h(url('post/' . $p['id'] . '/delete')) ?>" style="display:inline"
                    onsubmit="return confirm('确定删除这条回复？')">
                <?= csrf_field() ?>
                <button type="submit" class="cb-btn sm"><?= L('删除') ?></button>
              </form>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <?php if ((int)($p['is_first'] ?? 0) && !empty($thread['edited_at'])): ?>
          <div class="cb-post__edited">
            <?= h((string)($thread['edited_username'] ?? L('用户'))) ?> 编辑于 <?= h(format_date($thread['edited_at'])) ?>
          </div>
        <?php endif; ?>

        <div class="cb-content-html"><?= $p['content'] ?></div>

        <?php if (!(int)($p['is_first'] ?? 0)): ?>
          <div class="cb-post__actions">
            <button type="button" data-like data-type="post" data-id="<?= (int)$p['id'] ?>"
                    data-guest="<?= $current_user ? 0 : 1 ?>"
                    class="<?= in_array((int)$p['id'], $liked_posts ?? [], true) ? 'on' : '' ?>">
              <span class="icon">👍</span> <?= L('赞') ?> <span class="count"><?= (int)$p['likes'] ?></span>
            </button>
          </div>
        <?php endif; ?>

        <?php if (!empty($subpostsAll[$p['id']])): ?>
          <?php $subs = $subpostsAll[$p['id']]; $hidden = count($subs) - $replyPreview; ?>
          <div class="cb-subposts" data-count="<?= count($subs) ?>">
            <?php foreach ($subs as $k => $sp): ?>
              <?php
              $su = ['avatar' => $sp['avatar'] ?? '', 'nickname' => $sp['nickname'] ?? $sp['username']];
              ?>
              <div class="cb-subpost <?= $k >= $replyPreview ? 'is-extra hide' : '' ?>">
                <a class="cb-subpost__user" href="<?= h(url('user/' . (int)$sp['user_id'])) ?>">
                  <img class="cb-avatar cb-avatar-sm" src="<?= h(avatar_url($su)) ?>" alt="">
                  <?= h($su['nickname']) ?>
                </a>
                <span class="cb-subpost__meta"><?= h(time_ago($sp['created_at'])) ?></span>
                <div class="cb-content-html cb-subpost__body"><?= $sp['content'] ?></div>
              </div>
            <?php endforeach; ?>
            <?php if ($hidden > 0): ?>
              <button type="button" class="cb-subposts__more" data-more>
                展开其余 <?= (int)$hidden ?> 条回复
              </button>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>

  <?php if (!(int)($posts[0]['is_first'] ?? 1)): ?>
    <div class="cb-post">
      <div class="cb-post__main">
        <?php if (!empty($thread['edited_at'])): ?>
          <div class="cb-post__edited">
            <?= h((string)($thread['edited_username'] ?? L('用户'))) ?> 编辑于 <?= h(format_date($thread['edited_at'])) ?>
          </div>
        <?php endif; ?>
        <div class="cb-content-html"><?= $thread['content'] ?></div>
      </div>
    </div>
  <?php endif; ?>
</div>

<div class="cb-card mt-16">
  <div class="cb-card__body">
    <div class="flex gap-12" style="flex-wrap:wrap;align-items:center">
      <button type="button" class="cb-btn <?= ($liked ?? false) ? 'primary' : '' ?>"
              data-like data-type="thread" data-id="<?= (int)$thread['id'] ?>" data-guest="<?= $current_user ? 0 : 1 ?>">
        <span class="icon">👍</span> <?= ($liked ?? false) ? L('已赞') : L('点赞') ?> <span class="count"><?= (int)$thread['likes'] ?></span>
      </button>

      <?php if ((int)config('recommend_enable', 1)): ?>
        <button type="button" class="cb-btn" data-recommend="1" data-id="<?= (int)$thread['id'] ?>">
          <?= L('⭐ 推荐') ?> <span><?= (int)$thread['recommends'] ?></span>
        </button>
        <button type="button" class="cb-btn" data-recommend="-1" data-id="<?= (int)$thread['id'] ?>"><?= L('⚡ 避雷') ?></button>
      <?php endif; ?>

      <?php if ($current_user): ?>
        <button type="button" class="cb-btn" onclick="document.getElementById('report-box').classList.toggle('hide')"><?= L('🚩 举报') ?></button>
      <?php endif; ?>

      <button type="button" class="cb-btn" id="cb-share-btn"
              data-title="<?= h((string)$thread['title']) ?>"
              data-link="<?= h(url('thread/' . (int)$thread['id'])) ?>"
              data-site="<?= h((string)config('site_name', 'ChatBBS')) ?>">🔗 <?= L('分享') ?></button>

      <?php if ($isOwner || $canManage): ?>
        <a class="cb-btn" href="<?= h(url('thread/' . $thread['id'] . '/edit')) ?>"><?= L('✏️ 编辑') ?></a>
      <?php endif; ?>

      <?php if ($canManage): ?>
        <a class="cb-btn" href="<?= h(url('admin/threads?keyword=' . urlencode((string)$thread['title']))) ?>"><?= L('🛠️ 后台管理') ?></a>
      <?php endif; ?>
    </div>

    <?php if ($current_user): ?>
      <div id="report-box" class="hide mt-16">
        <form method="post" action="<?= h(url('thread/' . $thread['id'] . '/report')) ?>">
          <?= csrf_field() ?>
          <div class="cb-form-item">
            <label><?= L('举报原因') ?></label>
            <select class="cb-select" name="reason" required>
              <option value="垃圾广告"><?= L('垃圾广告') ?></option>
              <option value="色情低俗"><?= L('色情低俗') ?></option>
              <option value="人身攻击"><?= L('人身攻击') ?></option>
              <option value="违法违规"><?= L('违法违规') ?></option>
              <option value="其他"><?= L('其他') ?></option>
            </select>
          </div>
          <div class="cb-form-item">
            <textarea class="cb-textarea" name="detail" placeholder="<?= L('补充说明（可选）') ?>"></textarea>
          </div>
          <button class="cb-btn danger" type="submit"><?= L('提交举报') ?></button>
        </form>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php if ($can_reply ?? false): ?>
  <div class="cb-card" id="reply-card">
    <div class="cb-card__header">
      <?= L('快速回复') ?>
      <a class="cb-btn sm" id="btn-reply-mode" href="javascript:void(0)"><?= L('高级模式') ?></a>
    </div>
    <div class="cb-card__body">
      <form id="reply-form" method="post" action="<?= h(url('thread/' . $thread['id'] . '/reply')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="reply_to" id="reply_to" value="0">

        <!-- 简洁模式 -->
        <div id="reply-simple">
          <textarea class="cb-textarea" name="content" id="reply_text" rows="4"
                    placeholder="<?= L('友善地写下你的回复…（Ctrl + Enter 提交）') ?>"></textarea>
        </div>

        <!-- 高级模式（富文本 / Markdown 可切换） -->
        <div id="reply-advanced" class="hide">
          <div id="reply-content" data-editor data-input="reply_html" data-placeholder="<?= L('写下你的回复…') ?>"></div>
          <input type="hidden" name="content" id="reply_html" disabled>
          <input type="hidden" name="content_format" id="reply_html_format" value="rich">
        </div>

        <div class="mt-8 flex justify-between items-center">
          <span class="text-xs text-muted">
            <span id="reply-to-tip" class="hide">正在回复指定楼层 · <a href="javascript:void(0)" id="reply-to-cancel"><?= L('取消') ?></a></span>
          </span>
          <button class="cb-btn primary" type="submit"><?= L('发表回复') ?></button>
        </div>
      </form>
    </div>
  </div>
  <script>
    (function () {
      var btn = document.getElementById('btn-reply-mode');
      var simple = document.getElementById('reply-simple');
      var adv = document.getElementById('reply-advanced');
      var text = document.getElementById('reply_text');
      var html = document.getElementById('reply_html');
      if (!btn || !simple || !adv || !text || !html) return;
      var advanced = false;
      function setFormat() {
        var fmt = document.getElementById('reply_html_format');
        if (!fmt) return;
        var ed = (document.getElementById('reply-content') || {}).__cbEditor;
        fmt.value = advanced ? ((ed && ed.mode) || 'rich') : 'rich';
      }
      btn.addEventListener('click', function () {
        advanced = !advanced;
        setFormat();
        simple.classList.toggle('hide', advanced);
        adv.classList.toggle('hide', !advanced);
        btn.textContent = advanced ? '简洁模式' : '高级模式';
        // 同一时刻只提交一个 content 字段
        if (advanced) {
          html.value = text.value.replace(/</g, '&lt;').replace(/\n/g, '<br>');
          html.disabled = false;
          text.disabled = true;
        } else {
          text.value = html.value.replace(/<br\s*\/?>/gi, '\n').replace(/<[^>]+>/g, '');
          text.disabled = false;
          html.disabled = true;
        }
      });
      // 引用回复时切回简洁模式并填入内容
      document.addEventListener('click', function (e) {
        var q = e.target.closest('[data-quote]');
        if (!q) return;
        if (advanced) { btn.click(); }
        var box = document.getElementById('reply_text');
        if (box) {
          box.value = '> ' + q.dataset.quote + '\n' + box.value;
          box.focus();
        }
        document.getElementById('reply-card').scrollIntoView({ behavior: 'smooth', block: 'center' });
      });
    })();
  </script>
<?php elseif ((int)$thread['is_closed']): ?>
  <div class="cb-alert info"><?= L('该主题已关闭，无法回复。') ?></div>
<?php elseif (!$current_user): ?>
  <div class="cb-alert info">请 <a href="<?= h(url('login?back=' . urlencode('thread/' . $thread['id']))) ?>"><?= L('登录') ?></a> 后参与讨论。</div>
<?php endif; ?>

<?= $pager ?? '' ?>

