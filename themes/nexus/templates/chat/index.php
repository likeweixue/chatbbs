<?php
$uid = (int)($current_user['id'] ?? 0);
$currentGroup = $group ?? null;
$currentWith = $with ?? null;
$tab = $currentGroup ? 'group' : ($currentWith ? 'pm' : 'pm');
$lanOn = !empty($lan_enable);
$discoverOn = !empty($discover_on);
$pollMs = (int)($poll_interval ?? 5000);
$myIp = (string)($my_ip ?? '');
$mySeg = (string)($my_segment ?? '');
?>
<div class="cb-card">
  <div class="cb-card__header cb-card__header--tabs">
    <span style="font-weight:600"><?= L('聊天') ?></span>
    <span class="cb-chat__seg">
      <a class="cb-chat__tab <?= $tab === 'pm' ? 'on' : '' ?>" href="<?= h(url('chat')) ?>"><?= L('会话') ?></a>
      <a class="cb-chat__tab <?= $tab === 'group' ? 'on' : '' ?>" href="<?= h(url('chat?tab=group')) ?>"><?= L('群聊') ?></a>
      <?php if ($lanOn): ?>
        <a class="cb-chat__tab" href="<?= h(url('chat?tab=lan')) ?>"><?= L('附近的人') ?></a>
      <?php endif; ?>
    </span>
    <span class="spacer"></span>
    <?php if ($lanOn): ?>
      <label class="cb-chat__switch" title="<?= L('开启后，同一网段的其他用户可以在「附近的人」里看到你') ?>">
        <input type="checkbox" id="chk-discover" <?= $discoverOn ? 'checked' : '' ?>>
        <span><?= L('允许附近的人发现我') ?></span>
      </label>
    <?php endif; ?>
    <button class="cb-btn sm primary" id="btn-create-group" type="button"><?= L('+ 建群') ?></button>
  </div>

  <div class="cb-card__body" style="padding:0">
    <div class="cb-chat" id="cb-chat"
         data-uid="<?= $uid ?>"
         data-poll="<?= $pollMs ?>"
         data-scope="<?= $currentGroup ? 'group' : 'pm' ?>"
         data-id="<?= $currentGroup ? (int)$currentGroup['id'] : (int)($currentWith['id'] ?? 0) ?>">

      <!-- 左：列表（窄） -->
      <aside class="cb-chat__side">
        <div class="cb-chat__tabs" id="cb-chat-tabs">
          <button class="cb-chat__tab2 on" type="button" data-panel="pane-pm"><?= L('会话') ?></button>
          <button class="cb-chat__tab2" type="button" data-panel="pane-group"><?= L('群聊') ?></button>
          <?php if ($lanOn): ?>
            <button class="cb-chat__tab2" type="button" data-panel="pane-lan"><?= L('附近') ?></button>
          <?php endif; ?>
        </div>

        <div class="cb-chat__pane on" id="pane-pm">
          <?php if (!$conversations): ?>
            <div class="cb-empty" style="padding:20px"><div class="text-sm"><?= L('暂无会话') ?></div></div>
          <?php endif; ?>
          <?php foreach ($conversations as $c): ?>
            <a class="cb-chat__item <?= ($currentWith['id'] ?? 0) === (int)$c['user_id'] ? 'active' : '' ?>"
               href="<?= h(url('chat?user=' . $c['user_id'])) ?>">
              <img class="cb-avatar cb-avatar-sm" src="<?= h(avatar_url($c['user'])) ?>" alt="">
              <div class="cb-chat__item-main">
                <div class="cb-chat__item-name"><?= h($c['user']['nickname'] ?? $c['user']['username'] ?? '') ?></div>
                <div class="cb-chat__item-last"><?= h(str_cut($c['last']['content'], 18)) ?></div>
              </div>
              <?php if ($c['unread'] > 0): ?><span class="cb-badge"><?= (int)$c['unread'] ?></span><?php endif; ?>
            </a>
          <?php endforeach; ?>
        </div>

        <div class="cb-chat__pane" id="pane-group">
          <?php if (!$groups): ?>
            <div class="cb-empty" style="padding:20px">
              <div class="text-sm"><?= L('还没有群聊') ?></div>
              <button class="cb-btn sm" style="margin-top:8px" type="button" data-act="create-group"><?= L('创建一个') ?></button>
            </div>
          <?php endif; ?>
          <?php foreach ($groups as $g): ?>
            <a class="cb-chat__item <?= $currentGroup && (int)$currentGroup['id'] === (int)$g['id'] ? 'active' : '' ?>"
               href="<?= h(url('chat/group/' . $g['id'])) ?>">
              <span class="cb-chat__avatar-group"><?= h(mb_substr((string)$g['name'], 0, 1)) ?></span>
              <div class="cb-chat__item-main">
                <div class="cb-chat__item-name"><?= h($g['name']) ?> <span class="text-xs text-muted"><?= (int)$g['member_count'] ?> 人</span></div>
                <div class="cb-chat__item-last"><?= h(str_cut((string)($g['last']['content'] ?? L('还没有消息')), 18)) ?></div>
              </div>
              <?php if ((int)($g['unread'] ?? 0) > 0): ?><span class="cb-badge"><?= (int)$g['unread'] ?></span><?php endif; ?>
            </a>
          <?php endforeach; ?>
          <?php if ($open_groups): ?>
            <div class="cb-chat__subtitle"><?= L('可加入的公开群') ?></div>
            <?php foreach ($open_groups as $g): ?>
              <div class="cb-chat__item">
                <span class="cb-chat__avatar-group"><?= h(mb_substr((string)$g['name'], 0, 1)) ?></span>
                <div class="cb-chat__item-main">
                  <div class="cb-chat__item-name"><?= h($g['name']) ?></div>
                  <div class="cb-chat__item-last"><?= (int)$g['member_count'] ?> 人 · <?= h(str_cut((string)($g['notice'] ?: L('无群公告')), 16)) ?></div>
                </div>
                <button class="cb-btn sm" type="button" data-act="join-group" data-id="<?= (int)$g['id'] ?>"><?= L('加入') ?></button>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

        <?php if ($lanOn): ?>
          <div class="cb-chat__pane" id="pane-lan">
            <div class="cb-chat__lan-tip">
              <?= L('当前网段') ?> <b><?= h($mySeg !== '' ? $mySeg . '.0/24' : L('未知')) ?></b>
              <span class="text-xs text-muted">（<?= h($myIp) ?>）</span>
              <div class="text-xs text-muted" style="margin-top:4px">
                <?= L('按 IP 网段判定同一局域网；只有开启「发现我」的用户会出现在这里。') ?>
              </div>
            </div>
            <?php if (!$nearby): ?>
              <div class="cb-empty" style="padding:20px"><div class="text-sm"><?= L('附近暂时没有人') ?></div></div>
            <?php endif; ?>
            <?php foreach ($nearby as $u): ?>
              <a class="cb-chat__item" href="<?= h(url('chat?user=' . $u['id'])) ?>">
                <img class="cb-avatar cb-avatar-sm" src="<?= h(avatar_url($u)) ?>" alt="">
                <div class="cb-chat__item-main">
                  <div class="cb-chat__item-name"><?= h($u['nickname'] ?: $u['username']) ?></div>
                  <div class="cb-chat__item-last"><?= h(time_ago((string)($u['last_active_at'] ?: $u['last_login_at'] ?: date('Y-m-d H:i:s')))) ?>活跃</div>
                </div>
                <span class="cb-btn sm"><?= L('聊聊') ?></span>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </aside>

      <!-- 右：会话内容（宽） -->
      <section class="cb-chat__main" id="cb-chat-main">
        <?php if ($currentGroup): ?>
          <div class="cb-chat__head">
            <div>
              <b><?= h($currentGroup['name']) ?></b>
              <span class="text-xs text-muted" style="margin-left:8px"><?= (int)$currentGroup['member_count'] ?> 位成员</span>
            </div>
            <div class="cb-chat__head-actions">
              <button class="cb-btn sm" type="button" data-act="members"><?= L('成员') ?></button>
              <button class="cb-btn sm" type="button" data-act="leave-group" data-id="<?= (int)$currentGroup['id'] ?>"><?= L('退出群聊') ?></button>
            </div>
          </div>
          <?php if (!empty($currentGroup['notice'])): ?>
            <div class="cb-chat__notice"><?= h((string)$currentGroup['notice']) ?></div>
          <?php endif; ?>
          <div class="cb-chat__messages" id="cb-chat-messages" data-last="0">
            <?php foreach ($group_messages as $m):
              $mine = (int)$m['user_id'] === $uid;
              $last = max($last ?? 0, (int)$m['id']);
            ?>
              <div class="cb-chat__msg <?= $mine ? 'mine' : '' ?>" data-id="<?= (int)$m['id'] ?>">
                <img class="cb-avatar cb-avatar-sm" src="<?= h(avatar_url(['avatar' => $m['avatar'] ?? ''])) ?>" alt="">
                <div class="cb-chat__bubble-wrap">
                  <?php if (!$mine): ?>
                    <div class="cb-chat__msg-name"><?= h($m['nickname'] ?: $m['username'] ?: L('游客')) ?></div>
                  <?php endif; ?>
                  <div class="cb-chat__bubble"><?= h($m['content']) ?></div>
                  <div class="text-xs text-muted" style="margin-top:4px"><?= h(format_date($m['created_at'])) ?></div>
                </div>
              </div>
            <?php endforeach; ?>
            <?php if (!$group_messages): ?>
              <div class="cb-empty" style="margin:auto"><div class="icon"><?= cb_icon('comment') ?></div><p><?= L('群里还没有消息，说点什么吧') ?></p></div>
            <?php endif; ?>
          </div>
          <form class="cb-chat__composer" id="cb-chat-form" method="post" action="<?= h(url('chat/send')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="group_id" value="<?= (int)$currentGroup['id'] ?>">
            <input class="cb-input" name="content" placeholder="<?= L('输入消息…（Ctrl + Enter 发送）') ?>" maxlength="2000" autocomplete="off" required>
            <button class="cb-btn primary" type="submit"><?= L('发送') ?></button>
          </form>

        <?php elseif ($currentWith): ?>
          <div class="cb-chat__head">
            <div><b><?= h($currentWith['nickname'] ?: $currentWith['username']) ?></b></div>
            <div class="cb-chat__head-actions">
              <a class="cb-btn sm" href="<?= h(url('user/' . (int)$currentWith['id'])) ?>"><?= L('主页') ?></a>
            </div>
          </div>
          <div class="cb-chat__messages" id="cb-chat-messages" data-last="0">
            <?php foreach ($messages as $m):
              $mine = (int)$m['from_id'] === $uid;
              $last = max($last ?? 0, (int)$m['id']);
            ?>
              <div class="cb-chat__msg <?= $mine ? 'mine' : '' ?>" data-id="<?= (int)$m['id'] ?>">
                <img class="cb-avatar cb-avatar-sm" src="<?= h(avatar_url(['avatar' => $mine ? ($current_user['avatar'] ?? '') : ($currentWith['avatar'] ?? '')])) ?>" alt="">
                <div class="cb-chat__bubble-wrap">
                  <div class="cb-chat__bubble"><?= h($m['content']) ?></div>
                  <div class="text-xs text-muted" style="margin-top:4px"><?= h(format_date($m['created_at'])) ?></div>
                </div>
              </div>
            <?php endforeach; ?>
            <?php if (!$messages): ?>
              <div class="cb-empty" style="margin:auto"><div class="icon"><?= cb_icon('mail') ?></div><p><?= L('还没有消息，打个招呼吧') ?></p></div>
            <?php endif; ?>
          </div>
          <form class="cb-chat__composer" id="cb-chat-form" method="post" action="<?= h(url('chat/send')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="to_id" value="<?= (int)$currentWith['id'] ?>">
            <input class="cb-input" name="content" placeholder="<?= L('输入消息…（Ctrl + Enter 发送）') ?>" maxlength="1000" autocomplete="off" required>
            <button class="cb-btn primary" type="submit"><?= L('发送') ?></button>
          </form>

        <?php else: ?>
          <div class="cb-empty" style="margin:auto">
            <div class="icon"><?= cb_icon('comment') ?></div>
            <p><?= L('选择一个会话或群聊开始聊天') ?></p>
            <div class="text-xs text-muted" style="margin-top:8px"><?= L('也可以到「附近的人」找同网段的朋友') ?></div>
          </div>
        <?php endif; ?>
      </section>
    </div>
  </div>
</div>

<script>
  (function () {
    var root = document.getElementById('cb-chat');
    if (!root) return;
    var box = document.getElementById('cb-chat-messages');
    var form = document.getElementById('cb-chat-form');
    var uid = parseInt(root.getAttribute('data-uid'), 10) || 0;
    var scope = root.getAttribute('data-scope') || 'pm';
    var id = parseInt(root.getAttribute('data-id'), 10) || 0;
    var pollMs = parseInt(root.getAttribute('data-poll'), 10) || 5000;

    /* ---- 左侧面板切换 ---- */
    var tabs = document.getElementById('cb-chat-tabs');
    if (tabs) {
      tabs.addEventListener('click', function (e) {
        var b = e.target.closest('.cb-chat__tab2');
        if (!b) return;
        tabs.querySelectorAll('.cb-chat__tab2').forEach(function (x) { x.classList.remove('on'); });
        b.classList.add('on');
        root.querySelectorAll('.cb-chat__pane').forEach(function (p) { p.classList.remove('on'); });
        var pane = document.getElementById(b.getAttribute('data-panel'));
        if (pane) { pane.classList.add('on'); }
      });
      // URL 带 ?tab= 时自动切到对应面板
      var q = (location.search.match(/[?&]tab=([^&]+)/) || [])[1];
      if (q) {
        var map = { pm: 'pane-pm', group: 'pane-group', lan: 'pane-lan' };
        var target = tabs.querySelector('[data-panel="' + (map[q] || 'pane-pm') + '"]');
        if (target) { target.click(); }
      }
    }

    /* ---- 滚动到底 ---- */
    function toBottom() {
      if (!box) return;
      box.scrollTop = box.scrollHeight;
    }
    toBottom();

    var lastId = 0;
    if (box) {
      box.querySelectorAll('.cb-chat__msg').forEach(function (n) {
        lastId = Math.max(lastId, parseInt(n.getAttribute('data-id'), 10) || 0);
      });
    }

    /* ---- 轮询拉新消息（页面不可见时暂停，省请求） ---- */
    var timer = null;
    var busy = false;
    function poll() {
      if (!box || !id || busy || document.hidden) { return; }
      busy = true;
      fetch('<?= h(url('api/chat/poll')) ?>?scope=' + encodeURIComponent(scope) + '&id=' + id + '&after=' + lastId, {
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
        .then(function (r) { return r.json(); })
        .then(function (res) {
          busy = false;
          var list = (res && res.data && res.data.messages) ? res.data.messages : [];
          if (!list.length) { return; }
          var stick = box.scrollTop + box.clientHeight >= box.scrollHeight - 40;
          list.forEach(function (m) {
            lastId = Math.max(lastId, parseInt(m.id, 10) || 0);
            var mine = parseInt(m.user_id || m.from_id, 10) === uid;
            var wrap = document.createElement('div');
            wrap.className = 'cb-chat__msg' + (mine ? ' mine' : '');
            wrap.setAttribute('data-id', m.id);
            var who = '';
            if (!mine && (m.nickname || m.username)) {
              who = '<div class="cb-chat__msg-name">' + (m.nickname || m.username).replace(/</g, '&lt;') + '</div>';
            }
            wrap.innerHTML =
              '<img class="cb-avatar cb-avatar-sm" src="' + (m.avatar || '') + '" alt="">'
              + '<div class="cb-chat__bubble-wrap">' + who
              + '<div class="cb-chat__bubble">' + String(m.content || '').replace(/</g, '&lt;') + '</div>'
              + '<div class="text-xs text-muted" style="margin-top:4px">' + (m.created_at || '') + '</div></div>';
            box.appendChild(wrap);
          });
          if (stick) { toBottom(); }
        })
        .catch(function () { busy = false; });
    }
    if (id) {
      timer = setInterval(poll, pollMs);
      document.addEventListener('visibilitychange', function () {
        if (!document.hidden) { poll(); }
      });
    }

    /* ---- 发送（Ajax，不刷新页面） ---- */
    if (form) {
      var input = form.querySelector('input[name="content"]');
      form.addEventListener('submit', function (e) {
        e.preventDefault();
        if (!input) return;
        var val = input.value.trim();
        if (!val) return;
        var body = new FormData(form);
        body.set('content', val);
        fetch(form.getAttribute('action'), {
          method: 'POST', body: body, credentials: 'same-origin',
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
          .then(function (r) { return r.json(); })
          .then(function (res) {
            if (!res || !res.ok) { alert((res && res.msg) || '发送失败'); return; }
            input.value = '';
            input.focus();
            poll();
          })
          .catch(function () { alert('网络异常，请重试'); });
      });
      if (input) {
        input.addEventListener('keydown', function (e) {
          if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
            e.preventDefault();
            form.dispatchEvent(new Event('submit', { cancelable: true }));
          }
        });
      }
    }

    /* ---- 建群 ---- */
    function createGroup() {
      var name = prompt('群名称（最多 60 字）');
      if (!name) return;
      var notice = prompt('群公告（可留空）', '') || '';
      var open = confirm('设为公开群？附近的人可以直接加入。（取消 = 私有群）');
      var body = new FormData();
      body.append('name', name);
      body.append('notice', notice);
      if (open) { body.append('is_open', '1'); }
      body.append('_csrf', (document.querySelector('input[name="_csrf"]') || {}).value || '');
      fetch('<?= h(url('chat/create')) ?>', {
        method: 'POST', body: body, credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
        .then(function (r) { return r.json(); })
        .then(function (res) {
          if (!res || !res.ok) { alert((res && res.msg) || '创建失败'); return; }
          location.href = (res.data && res.data.url) || '<?= h(url('chat')) ?>';
        });
    }
    var cg = document.getElementById('btn-create-group');
    if (cg) { cg.addEventListener('click', createGroup); }

    document.addEventListener('click', function (e) {
      var el = e.target.closest('[data-act]');
      if (!el) return;
      var act = el.getAttribute('data-act');
      var gid = parseInt(el.getAttribute('data-id'), 10) || 0;
      var csrf = (document.querySelector('input[name="_csrf"]') || {}).value || '';

      function post(u, extra) {
        var body = new FormData();
        body.append('_csrf', csrf);
        if (extra) { Object.keys(extra).forEach(function (k) { body.append(k, extra[k]); }); }
        fetch(u, { method: 'POST', body: body, credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
          .then(function (r) { return r.json(); })
          .then(function (res) {
            if (!res || !res.ok) { alert((res && res.msg) || '操作失败'); return; }
            if (res.data && res.data.url) { location.href = res.data.url; } else { location.reload(); }
          });
      }

      if (act === 'create-group') { createGroup(); }
      else if (act === 'join-group') { post('<?= h(url('chat/join/')) ?>' + gid); }
      else if (act === 'leave-group') {
        if (confirm('确定退出该群？')) { post('<?= h(url('chat/leave/')) ?>' + gid); }
      } else if (act === 'members') {
        var names = <?= json_encode(array_map(static fn($m) => ($m['nickname'] ?: $m['username']) . ((int)$m['role'] === 2 ? L('（群主）') : ((int)$m['role'] === 1 ? L('（管理员）') : '')), $group_members ?? []), JSON_UNESCAPED_UNICODE) ?>;
        alert(names.length ? names.join('\n') : '暂无成员');
      }
    });

    /* ---- 附近的人开关 ---- */
    var chk = document.getElementById('chk-discover');
    if (chk) {
      chk.addEventListener('change', function () {
        var body = new FormData();
        body.append('_csrf', (document.querySelector('input[name="_csrf"]') || {}).value || '');
        if (chk.checked) { body.append('on', '1'); }
        fetch('<?= h(url('chat/discover')) ?>', {
          method: 'POST', body: body, credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
          .then(function (r) { return r.json(); })
          .then(function (res) { if (res && res.msg) { alert(res.msg); } });
      });
    }
  })();
</script>
