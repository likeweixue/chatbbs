/* ChatBBS 前端通用脚本 */
(function () {
  'use strict';

  var SITE = window.CB_SITE_URL || '';
  var API = window.CB_API_BASE || (SITE + '/');
  var PAGE = window.CB_PAGE_BASE || API;
  var CSRF = window.CB_CSRF || '';

  /* ---------- 主题（暗色模式） ---------- */
  var Theme = {
    key: 'cb_theme',
    get: function () { return localStorage.getItem(this.key) || 'auto'; },
    apply: function (mode) {
      if (mode === 'auto') {
        var dark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
        document.documentElement.setAttribute('data-theme', dark ? 'dark' : 'light');
      } else {
        document.documentElement.setAttribute('data-theme', mode);
      }
      localStorage.setItem(this.key, mode);
    },
    toggle: function () {
      var cur = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
      this.apply(cur);
      return cur;
    },
    init: function () {
      this.apply(this.get());
      var self = this;
      if (window.matchMedia) {
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function () {
          if (self.get() === 'auto') self.apply('auto');
        });
      }
    }
  };
  window.CBTheme = Theme;
  Theme.init();

  /* ---------- AJAX ---------- */
  function request(url, data, opts) {
    opts = opts || {};
    var body = new FormData();
    for (var k in data) {
      if (Object.prototype.hasOwnProperty.call(data, k)) body.append(k, data[k]);
    }
    if (!opts.noCsrf && CSRF) body.append('_csrf', CSRF);
    return fetch(url, {
      method: opts.method || 'POST',
      body: body,
      headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': CSRF }
    }).then(function (r) { return r.json(); });
  }
  window.CB = { request: request, site: SITE, csrf: CSRF };

  function toast(msg, type) {
    var box = document.getElementById('cb-toast');
    if (!box) {
      box = document.createElement('div');
      box.id = 'cb-toast';
      box.style.cssText = 'position:fixed;top:20px;left:50%;transform:translateX(-50%);z-index:9999;display:flex;flex-direction:column;gap:8px;';
      document.body.appendChild(box);
    }
    var el = document.createElement('div');
    el.className = 'cb-alert ' + (type || 'info');
    el.style.cssText = 'box-shadow:0 6px 20px rgba(0,0,0,.12);min-width:200px;text-align:center;';
    el.textContent = msg;
    box.appendChild(el);
    setTimeout(function () { el.remove(); }, 2600);
  }
  window.CB.toast = toast;

  function confirmBox(msg) { return window.confirm(msg || cbT('确定执行此操作？')); }
  window.CB.confirm = confirmBox;

  /* ---------- 点赞 ---------- */
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-like]');
    if (!btn) return;
    e.preventDefault();
    if (btn.dataset.guest === '1') { location.href = PAGE + 'login'; return; }
    var type = btn.dataset.type || 'thread';
    var id = btn.dataset.id;
    btn.disabled = true;
    request(API + 'api/like', { type: type, id: id }).then(function (res) {
      btn.disabled = false;
      if (!res.ok) { toast(res.msg, 'error'); if (res.data && res.data.login) location.href = PAGE + 'login'; return; }
      btn.classList.toggle('on', res.data.liked);
      var c = btn.querySelector('.count');
      if (c) c.textContent = res.data.count;
      toast(res.msg, 'success');
    }).catch(function () { btn.disabled = false; toast(cbT('网络异常'), 'error'); });
  });

  /* ---------- 关注 / 拉黑 ---------- */
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-action]');
    if (!btn) return;
    e.preventDefault();
    var action = btn.dataset.action;
    var userId = btn.dataset.userId;
    var urlMap = {
      follow: '/user/follow', block: '/user/block',
      friend: '/user/friend', unfriend: '/user/unfriend'
    };
    if (!urlMap[action]) return;
    if (btn.dataset.confirm === '1' && !confirmBox(btn.dataset.confirmText || cbT('确定吗？'))) return;
    btn.disabled = true;
    request(API + String(urlMap[action]).replace(/^\//, ''), { user_id: userId }).then(function (res) {
      btn.disabled = false;
      toast(res.msg, res.ok ? 'success' : 'error');
      if (res.ok) setTimeout(function () { location.reload(); }, 500);
    });
  });

  /* ---------- 推荐 / 避雷 ---------- */
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-recommend]');
    if (!btn) return;
    e.preventDefault();
    request(API + 'recommend/toggle', { thread_id: btn.dataset.id, kind: btn.dataset.recommend })
      .then(function (res) {
        toast(res.msg, res.ok ? 'success' : 'error');
        if (res.ok) setTimeout(function () { location.reload(); }, 400);
      });
  });

  /* ---------- 签到 ---------- */
  var ciBtn = document.getElementById('btn-checkin');
  if (ciBtn) {
    ciBtn.addEventListener('click', function () {
      ciBtn.disabled = true;
      request(API + 'checkin/do', {}).then(function (res) {
        toast(res.msg, res.ok ? 'success' : 'error');
        if (res.ok) setTimeout(function () { location.reload(); }, 800);
        else ciBtn.disabled = false;
      });
    });
  }

  /* ---------- 通知下拉 ---------- */
  var bellBtn = document.getElementById('btn-notifications');
  var bellMenu = document.getElementById('menu-notifications');
  if (bellBtn && bellMenu) {
    bellBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      bellMenu.classList.toggle('show');
      if (bellMenu.classList.contains('show')) loadNotifications();
    });
    document.addEventListener('click', function (e) {
      if (!bellMenu.contains(e.target)) bellMenu.classList.remove('show');
    });
  }

  /* ---------- 语言切换下拉 ---------- */
  var langBtn = document.getElementById('btn-lang');
  var langMenu = document.getElementById('menu-lang');
  if (langBtn && langMenu) {
    langBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      langMenu.classList.toggle('show');
    });
    document.addEventListener('click', function (e) {
      if (!langMenu.contains(e.target) && !langBtn.contains(e.target)) {
        langMenu.classList.remove('show');
      }
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') langMenu.classList.remove('show');
    });
  }

  function loadNotifications() {
    var list = bellMenu.querySelector('.list');
    fetch(API + 'api/notifications', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (!res.ok) return;
        var items = res.data.items || [];
        if (!items.length) {
          list.innerHTML = '<div class="cb-empty" style="padding:24px">' + cbT('暂无新通知') + '</div>';
          return;
        }
        list.innerHTML = items.map(function (n) {
          return '<a class="item" href="' + (n.link || PAGE + 'notifications') + '">' +
            '<span class="cb-notify-ico">' + (n.icon || '') + '</span>' +
            '<span style="flex:1;min-width:0"><div class="t">' + escapeHtml(n.title) + '</div>' +
            '<div class="c">' + escapeHtml(n.content || '') + ' · ' + n.time + '</div></span></a>';
        }).join('');
        var badge = document.querySelector('#btn-notifications .cb-badge');
        if (badge && res.data.count) badge.textContent = res.data.count;
      });
  }

  function escapeHtml(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }
  window.CB.escapeHtml = escapeHtml;

  /* ---------- 未读计数轮询 ---------- */
  function pollCounters() {
    fetch(API + 'api/counters', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (!res.ok) return;
        var n = res.data.notifications, m = res.data.messages;
        var b1 = document.querySelector('#btn-notifications .cb-badge');
        if (b1) { n > 0 ? (b1.textContent = n, b1.style.display = '') : (b1.style.display = 'none'); }
        var b2 = document.querySelector('#btn-messages .cb-badge');
        if (b2) { m > 0 ? (b2.textContent = m, b2.style.display = '') : (b2.style.display = 'none'); }
      }).catch(function () { });
  }
  if (document.querySelector('#btn-notifications')) {
    pollCounters();
    setInterval(pollCounters, 60000);
  }

  /* ---------- 主题切换按钮（图标由 CSS 按当前主题自动切换） ---------- */
  document.addEventListener('click', function (e) {
    var t = e.target.closest('[data-theme-toggle]');
    if (!t) return;
    e.preventDefault();
    Theme.toggle();
  });

  /* ---------- 图片灯箱 ---------- */
  document.addEventListener('click', function (e) {
    var img = e.target.closest('.cb-say__images img, .cb-content-html img');
    if (!img || img.dataset.lightbox === '0') return;
    e.preventDefault();
    var overlay = document.createElement('div');
    overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.85);z-index:9999;display:flex;align-items:center;justify-content:center;cursor:zoom-out;';
    var i = document.createElement('img');
    i.src = img.src;
    i.style.cssText = 'max-width:92%;max-height:92%;border-radius:8px;';
    overlay.appendChild(i);
    overlay.addEventListener('click', function () { overlay.remove(); });
    document.body.appendChild(overlay);
  });

  /* ---------- 发帖页自动保存草稿 ---------- */
  var editorForm = document.getElementById('thread-form');
  if (editorForm) {
    var timer = null;
    var draftTip = document.getElementById('draft-tip');
    /* 取当前生效的 content 字段值（简洁/高级模式自动切换 name 与 disabled） */
    function activeContent(form) {
      var val = '';
      (form || document).querySelectorAll('[name=content]').forEach(function (f) {
        if (!f.disabled) { val = f.value || ''; }
      });
      return val;
    }

    function saveDraft() {
      var fd = {
        forum_id: (editorForm.querySelector('[name=forum_id]') || {}).value || 0,
        thread_id: editorForm.dataset.threadId || 0,
        title: (editorForm.querySelector('[name=title]') || {}).value || '',
        content: activeContent(editorForm),
        tags: (editorForm.querySelector('[name=tags]') || {}).value || ''
      };
      if (!fd.title && !fd.content) return;
      request(API + 'draft/save', fd).then(function (res) {
        if (draftTip) {
          draftTip.textContent = res.ok ? cbT('草稿已保存 ') + new Date().toLocaleTimeString() : res.msg;
        }
      });
    }
    editorForm.addEventListener('input', function () {
      clearTimeout(timer);
      timer = setTimeout(saveDraft, 3000);
    });
    var draftBtn = document.getElementById('btn-save-draft');
    if (draftBtn) draftBtn.addEventListener('click', function (e) { e.preventDefault(); saveDraft(); });

    /* 提交前校验正文，避免"点了没反应" */
    editorForm.addEventListener('submit', function (e) {
      var raw = activeContent(editorForm);
      var text = String(raw).replace(/<[^>]*>/g, '').replace(/&nbsp;/g, ' ').trim();
      if (!text) {
        e.preventDefault();
        toast(cbT('请先输入正文内容'), 'error');
        var edEl = editorForm.querySelector('[data-editor]');
        var ed = edEl && edEl.__cbEditor;
        var focusEl = ed ? ed.el : editorForm.querySelector('[name=content]');
        if (focusEl) { focusEl.focus(); }
      }
    });
  }

  /* ---------- 回复框快捷插入 ---------- */
  document.addEventListener('click', function (e) {
    var q = e.target.closest('[data-quote]');
    if (!q) return;
    e.preventDefault();
    var target = document.getElementById('reply-content');
    if (!target) return;
    if (window.CBEditor) {
      window.CBEditor.insertHTML('<blockquote>' + escapeHtml(q.dataset.quote) + '</blockquote><p><br></p>');
    } else {
      target.value += '\n> ' + q.dataset.quote + '\n';
    }
    target.scrollIntoView({ behavior: 'smooth', block: 'center' });
  });

  /* ---------- 移动端抽屉菜单（Flarum 风格：左侧滑出 + 遮罩） ---------- */
  (function () {
    var burger = document.getElementById('cb-burger');
    var header = document.getElementById('cb-header');
    var mask = document.getElementById('cb-drawer-mask');
    var closeBtn = document.getElementById('cb-drawer-close');
    if (!burger || !header) { return; }

    function setOpen(open) {
      header.classList.toggle('open', open);
      burger.setAttribute('aria-expanded', open ? 'true' : 'false');
      document.body.classList.toggle('cb-drawer-open', open);
    }

    burger.addEventListener('click', function () {
      setOpen(!header.classList.contains('open'));
    });
    if (mask) {
      mask.addEventListener('click', function () { setOpen(false); });
    }
    if (closeBtn) {
      closeBtn.addEventListener('click', function () { setOpen(false); });
    }
    // 点击菜单项后收起（后台链接等新窗口打开的除外）
    header.querySelectorAll('.cb-nav a').forEach(function (a) {
      a.addEventListener('click', function () { setOpen(false); });
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && header.classList.contains('open')) { setOpen(false); }
    });
    // 视口变宽时自动收起，避免桌面端残留抽屉状态
    window.addEventListener('resize', function () {
      if (window.innerWidth > 860 && header.classList.contains('open')) { setOpen(false); }
    });
  })();

  /* ---------- 楼中楼：展开 / 收起更多回复 ---------- */
  document.querySelectorAll('[data-more]').forEach(function (btn) {
    if (!btn.getAttribute('data-label')) {
      btn.setAttribute('data-label', btn.textContent.trim());
    }
  });
  document.addEventListener('click', function (e) {
    var more = e.target.closest('[data-more]');
    if (!more) return;
    var box = more.closest('.cb-subposts');
    if (!box) return;
    var opened = !box.classList.contains('expanded');
    box.classList.toggle('expanded', opened);
    box.querySelectorAll('.is-extra').forEach(function (el) { el.classList.toggle('hide', !opened); });
    more.textContent = opened ? cbT('收起回复') : (more.getAttribute('data-label') || cbT('展开更多回复'));
  });

  /* ---------- 楼中楼回复：设置 reply_to ---------- */
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-reply-to]');
    if (!btn) return;
    e.preventDefault();
    var form = document.getElementById('reply-form');
    if (!form) return;
    var input = form.querySelector('[name=reply_to]');
    if (input) { input.value = btn.getAttribute('data-reply-to'); }
    var tip = document.getElementById('reply-to-tip');
    if (tip) { tip.classList.remove('hide'); }
    var card = document.getElementById('reply-card');
    if (card) { card.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
    var ed = (document.getElementById('reply-content') || {}).__cbEditor;
    if (ed && ed.mode === 'rich' && ed.area) { ed.area.focus(); return; }
    var ta = document.getElementById('reply_text');
    if (ta) { ta.focus(); }
  });
  var cancelReplyTo = document.getElementById('reply-to-cancel');
  if (cancelReplyTo) {
    cancelReplyTo.addEventListener('click', function () {
      var form = document.getElementById('reply-form');
      if (form) {
        var input = form.querySelector('[name=reply_to]');
        if (input) { input.value = 0; }
      }
      var tip = document.getElementById('reply-to-tip');
      if (tip) { tip.classList.add('hide'); }
    });
  }

  /* ---------- 修复历史数据：压缩包等被当成 <img> 插入的附件 ---------- */
  var NON_IMAGE_EXT = /\.(zip|rar|7z|gz|tgz|tar|bz2|xz|pdf|doc|docx|xls|xlsx|ppt|pptx|mp4|mov|avi|mkv|mp3|wav|flac|txt|md|csv|json|xml|apk|epub|psd)(\?|#|$)/i;
  function fixAttachmentImages(root) {
    (root || document).querySelectorAll('.cb-content-html img').forEach(function (img) {
      var src = img.getAttribute('src') || '';
      if (!src || !NON_IMAGE_EXT.test(src)) { return; }
      var link = document.createElement('a');
      link.className = 'cb-attachment';
      link.href = src;
      link.target = '_blank';
      link.setAttribute('rel', 'noopener');
      link.textContent = img.getAttribute('alt') || cbT('下载附件');
      if (img.parentNode) { img.parentNode.replaceChild(link, img); }
    });
  }
  fixAttachmentImages(document);

  /* ---------- 密码显示 / 隐藏 ---------- */
  document.querySelectorAll('[data-toggle-password]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var el = document.getElementById(btn.getAttribute('data-toggle-password'));
      if (!el) { return; }
      el.type = el.type === 'password' ? 'text' : 'password';
      btn.textContent = el.type === 'password' ? '👁' : '🙈';
    });
  });

  /* ---------- 侧边栏：显示/隐藏 + 左右位置（记忆到 localStorage） ---------- */
  (function () {
    var main = document.querySelector('.cb-main');
    var toggle = document.getElementById('btn-sidebar-toggle');
    // 侧边栏被后台关闭时没有切换按钮：保留服务端输出的布局类名，不做任何改动
    if (!main || !toggle) { return; }
    var KEY_HIDDEN = 'cb_sidebar_hidden';
    var KEY_SIDE = 'cb_sidebar_side';
    var defaultSide = main.classList.contains('sidebar-left') ? 'left' : 'right';
    // 服务端通过 data-sidebar-default 告诉前端「默认隐藏 / 默认展开」
    var defaultHidden = main.getAttribute('data-sidebar-default') !== 'visible';

    function isHidden() {
      var stored = localStorage.getItem(KEY_HIDDEN);
      return stored === null ? defaultHidden : stored === '1';
    }

    function apply() {
      var hidden = isHidden();
      var side = localStorage.getItem(KEY_SIDE) || defaultSide;
      main.classList.toggle('sidebar-hidden', hidden);
      main.classList.toggle('sidebar-left', side === 'left');
      toggle.classList.toggle('off', hidden);
    }

    toggle.addEventListener('click', function () {
      localStorage.setItem(KEY_HIDDEN, isHidden() ? '0' : '1');
      apply();
    });
    var sideBtn = document.getElementById('btn-sidebar-side');
    if (sideBtn) {
      sideBtn.addEventListener('click', function () {
        var side = localStorage.getItem(KEY_SIDE) || defaultSide;
        localStorage.setItem(KEY_SIDE, side === 'left' ? 'right' : 'left');
        apply();
      });
    }
    apply();
  })();

  /* ---------- 全选 / 批量 ---------- */
  var checkAll = document.querySelector('[data-check-all]');
  if (checkAll) {
    checkAll.addEventListener('change', function () {
      document.querySelectorAll('input[name="ids[]"]').forEach(function (c) { c.checked = checkAll.checked; });
    });
  }

  /* ---------- 附件上传（拖拽） ---------- */
  document.querySelectorAll('[data-upload-zone]').forEach(function (zone) {
    var input = zone.querySelector('input[type=file]');
    if (!input) return;
    zone.addEventListener('click', function () { input.click(); });
    zone.addEventListener('dragover', function (e) { e.preventDefault(); zone.style.borderColor = 'var(--color-primary)'; });
    zone.addEventListener('dragleave', function () { zone.style.borderColor = ''; });
    zone.addEventListener('drop', function (e) {
      e.preventDefault();
      zone.style.borderColor = '';
      if (e.dataTransfer.files.length) {
        input.files = e.dataTransfer.files;
        input.dispatchEvent(new Event('change'));
      }
    });
  });

  /* ---------- 帖子正文目录面板（Halo 风格：缩进层级 + 滚动高亮） ---------- */
  (function () {
    var card = document.getElementById('cb-toc-card');
    var list = document.getElementById('cb-toc-list');
    if (!card || !list) return;

    // 只从楼主（第一篇）的正文中提取标题
    var firstPost = document.querySelector('.cb-post .cb-content-html');
    if (!firstPost) return;
    var heads = firstPost.querySelectorAll('h1, h2, h3, h4');
    if (!heads.length) return;

    var items = [];
    Array.prototype.forEach.call(heads, function (h, i) {
      if (!h.id) { h.id = 'cb-toc-h-' + i; }
      var text = (h.textContent || '').trim() || (cbT('标题 ') + (i + 1));
      items.push({ id: h.id, text: text, level: parseInt(h.tagName.substring(1), 10) });
    });

    function esc(t) { return t.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;'); }

    card.hidden = false;
    list.innerHTML = items.map(function (it) {
      return '<a class="cb-toc__item lv' + it.level + '" href="#' + it.id + '" data-target="' + it.id
        + '" title="' + esc(it.text) + '">' + esc(it.text) + '</a>';
    }).join('');

    function headerH() {
      var h = document.querySelector('.cb-header');
      return h ? h.offsetHeight + 12 : 76;
    }

    list.addEventListener('click', function (e) {
      var a = e.target.closest('.cb-toc__item');
      if (!a) return;
      var target = document.getElementById(a.getAttribute('data-target'));
      if (!target) return;
      e.preventDefault();
      var top = target.getBoundingClientRect().top + window.pageYOffset - headerH();
      window.scrollTo({ top: top, behavior: 'smooth' });
      history.replaceState(null, '', '#' + target.id);
      mark(target.id);
    });

    var toggle = document.getElementById('cb-toc-toggle');
    if (toggle) {
      toggle.addEventListener('click', function () {
        var folded = card.classList.toggle('folded');
        toggle.textContent = folded ? '+' : '\u2212';
      });
    }

    function mark(activeId) {
      list.querySelectorAll('.cb-toc__item').forEach(function (a) {
        a.classList.toggle('on', a.getAttribute('data-target') === activeId);
      });
    }

    // 滚动高亮：rAF 节流，避免每个滚动事件都触发布局计算
    var pending = false;
    function update() {
      if (pending) return;
      pending = true;
      requestAnimationFrame(function () {
        pending = false;
        var active = items.length ? items[0].id : null;
        for (var i = 0; i < items.length; i++) {
          var el = document.getElementById(items[i].id);
          if (!el) continue;
          if (el.getBoundingClientRect().top - headerH() <= 8) { active = items[i].id; } else { break; }
        }
        mark(active);
      });
    }
    window.addEventListener('scroll', update, { passive: true });
    update();
  })();

  /* ---------- 发帖 / 编辑页：从编辑器内容实时提取标题生成目录（富文本 + Markdown） ---------- */
  (function () {
    // 阅读页的目录由上面的逻辑处理
    if (document.querySelector('.cb-post .cb-content-html')) return;
    var card = document.getElementById('cb-toc-card');
    var list = document.getElementById('cb-toc-list');
    if (!card || !list) return;
    if (!document.querySelector('[data-editor]')) return;

    function mdHeads(text) {
      var out = [];
      (text || '').split('\n').forEach(function (line) {
        var m = line.match(/^(#{1,4})\s+(.+?)\s*$/);
        if (m) out.push({ text: m[2], level: m[1].length });
      });
      return out;
    }

    function richHeads() {
      var ed = window.CBEditor;
      if (!ed || !ed.area) return [];
      var out = [];
      ed.area.querySelectorAll('h1, h2, h3, h4').forEach(function (h) {
        var text = (h.textContent || '').trim();
        if (text) out.push({ el: h, text: text, level: parseInt(h.tagName.substring(1), 10) });
      });
      return out;
    }

    function build() {
      var ed = window.CBEditor;
      if (!ed) { card.hidden = true; return; }
      var items = ed.mode === 'markdown'
        ? mdHeads(ed.mdArea ? ed.mdArea.value : '')
        : richHeads();
      if (!items.length) { card.hidden = true; list.innerHTML = ''; return; }
      function esc(t) { return t.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;'); }
      card.hidden = false;
      list.innerHTML = items.map(function (it, i) {
        return '<a class="cb-toc__item lv' + it.level + '" href="javascript:void(0)" data-i="' + i
          + '" title="' + esc(it.text) + '">' + esc(it.text) + '</a>';
      }).join('');
    }

    list.addEventListener('click', function (e) {
      var a = e.target.closest('.cb-toc__item');
      if (!a) return;
      var ed = window.CBEditor;
      if (!ed) return;
      if (ed.mode === 'rich' && ed.area) {
        var h = ed.area.querySelectorAll('h1, h2, h3, h4')[parseInt(a.getAttribute('data-i'), 10)];
        if (h) {
          h.scrollIntoView({ behavior: 'smooth', block: 'center' });
          if (h.focus) h.focus({ preventScroll: true });
        }
      } else if (ed.mdArea) {
        // Markdown 模式没有可定位的元素，滚到编辑器即可
        ed.mdArea.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
    });

    var timer = null;
    function schedule() {
      if (timer) clearTimeout(timer);
      timer = setTimeout(build, 300);
    }

    // 编辑器可能在 app.js 之后才初始化，轮询等待一次
    var tries = 0;
    (function wait() {
      if (window.CBEditor) { build(); bind(); return; }
      if (tries++ > 40) return; // 最多等 4 秒
      setTimeout(wait, 100);
    })();

    function bind() {
      var ed = window.CBEditor;
      if (!ed) return;
      if (ed.area) ed.area.addEventListener('input', schedule);
      if (ed.mdArea) ed.mdArea.addEventListener('input', schedule);
      // 富文本 ↔ Markdown 切换后重建
      document.addEventListener('click', function (e) {
        if (e.target.closest('.cb-ed-mode')) setTimeout(build, 80);
      });
    }
  })();

  /* ---------- 帖子页「分享」按钮：复制 标题 / 链接 / 出自@站点 ---------- */
  (function () {
    var btn = document.getElementById('cb-share-btn');
    if (!btn) return;
    btn.addEventListener('click', function () {
      var text = (btn.getAttribute('data-title') || '') + '\n'
        + (btn.getAttribute('data-link') || location.href) + '\n'
        + (cbT('出自@') + (btn.getAttribute('data-site') || 'ChatBBS'));
      function done() {
        var old = btn.innerHTML;
        btn.innerHTML = cbT('已复制，去粘贴吧');
        setTimeout(function () { btn.innerHTML = old; }, 1800);
      }
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(done, function () { fallback(text, done); });
      } else {
        fallback(text, done);
      }
      function fallback(t, cb) {
        var ta = document.createElement('textarea');
        ta.value = t;
        ta.style.position = 'fixed';
        ta.style.opacity = '0';
        document.body.appendChild(ta);
        ta.select();
        try { document.execCommand('copy'); cb(); } catch (e) { prompt(cbT('请手动复制：'), t); }
        document.body.removeChild(ta);
      }
    });
  })();

})();

  /* ---------- 移动端帖子操作：更多（底部面板） ---------- */
  (function () {
    var bar = document.querySelector('.cb-thread-actions');
    if (!bar) return;
    var more = bar.querySelector('.cb-thread-actions__more');
    var close = bar.querySelector('.cb-thread-actions__close');
    var mask = document.getElementById('cb-actions-mask');
    if (!more) return;

    function setOpen(open) {
      bar.classList.toggle('is-open', open);
      if (mask) mask.classList.toggle('is-open', open);
      document.body.classList.toggle('cb-sheet-open', open);
      more.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    more.addEventListener('click', function () {
      setOpen(!bar.classList.contains('is-open'));
    });
    if (close) close.addEventListener('click', function () { setOpen(false); });
    if (mask) mask.addEventListener('click', function () { setOpen(false); });
    bar.addEventListener('click', function (e) {
      if (e.target.closest('.cb-thread-actions__more') || e.target.closest('.cb-thread-actions__close')) return;
      if (e.target.closest('.cb-btn') || e.target.closest('a')) setOpen(false);
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') setOpen(false);
    });
  })();

/* 岛屿布局：移动端默认收起板块 / 标签菜单组 */
(function () {
  var groups = document.querySelectorAll('.cb-island-nav details[data-island-group]');
  if (!groups.length) return;
  try {
    if (window.matchMedia('(max-width: 860px)').matches) {
      Array.prototype.forEach.call(groups, function (g) { g.open = false; });
    }
  } catch (e) {}
})();
