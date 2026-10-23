/* ChatBBS 编辑器：富文本 / Markdown 双模式（默认富文本） */
(function () {
  'use strict';

  var SITE = window.CB_SITE_URL || '';
  var API = window.CB_API_BASE || (SITE + '/');
  var CSRF = window.CB_CSRF || '';

  var EMOJIS = ['😀', '😂', '🤣', '😊', '😍', '🥰', '😘', '😎', '🤔', '😅', '😭', '😡', '👍', '👏', '🙏', '💪',
    '🎉', '🔥', '✨', '💡', '⭐', '❤️', '💔', '🌈', '🍺', '🍎', '🐶', '🐱', '🚀', '⚡', '✅', '❌',
    '❓', '❗', '💯', '🤝', '👀', '🧠', '📚', '💻', '📌', '📎', '🛠️', '⚙️', '📅', '📝'];

  /* 线性图标：<svg><use href="#i-xxx"/></svg>（viewBox 需与 sprite 一致，24x24） */
  function ico(name) {
    return '<svg class="cb-ico" viewBox="0 0 24 24" aria-hidden="true" focusable="false">'
      + '<use href="#i-' + name + '" xlink:href="#i-' + name + '"></use></svg>';
  }

  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  function fileSize(n) {
    var b = parseFloat(n) || 0;
    if (b <= 0) return '';
    return b >= 1048576 ? (b / 1048576).toFixed(1) + ' MB' : Math.round(b / 1024) + ' KB';
  }

  /* ================================================================== */
  function Editor(el, opts) {
    this.el = typeof el === 'string' ? document.getElementById(el) : el;
    this.opts = opts || {};
    this.valueEl = this.opts.input ? document.getElementById(this.opts.input) : null;
    /* rich（默认，站点自带富文本） / markdown */
    this.mode = this.opts.mode === 'markdown' ? 'markdown' : 'rich';
    this.htmlBuf = '';   // 富文本缓冲（切到 Markdown 再切回来时不丢内容）
    this.mdBuf = '';     // Markdown 缓冲
    this.savedRange = null;
    this.build();
    this.bind();
    /* 初始内容 */
    var initial = this.opts.initial || (this.valueEl ? this.valueEl.value : '') || '';
    if (initial) {
      if (this.mode === 'markdown') {
        this.mdBuf = initial;
        this.mdArea.value = initial;
      } else {
        this.htmlBuf = initial;
        this.area.innerHTML = initial;
      }
      this.sync();
    }
  }

  Editor.prototype.build = function () {
    var wrap = document.createElement('div');
    wrap.className = 'cb-editor cb-editor--' + this.mode;

    /* 工具栏 */
    this.tb = document.createElement('div');
    this.tb.className = 'cb-editor__toolbar';
    wrap.appendChild(this.tb);

    /* 编辑区容器 */
    var body = document.createElement('div');
    body.className = 'cb-editor__body';

    this.area = document.createElement('div');
    this.area.className = 'cb-editor__area cb-content-html';
    this.area.contentEditable = 'true';
    this.area.setAttribute('data-placeholder',
      this.opts.placeholder || cbT('写点什么吧…（支持 Ctrl+B 加粗、Ctrl+Enter 提交）'));

    this.mdArea = document.createElement('textarea');
    this.mdArea.className = 'cb-editor__md';
    this.mdArea.rows = 14;
    this.mdArea.setAttribute('placeholder',
      '在此编写 Markdown…\n# 标题\n**加粗** *斜体* `代码`\n- 列表\n> 引用\n```\n代码块\n```\n[链接](https://…) 或 ![图片](图片地址)');

    body.appendChild(this.area);
    body.appendChild(this.mdArea);
    wrap.appendChild(body);

    /* 底部状态栏 */
    var footer = document.createElement('div');
    footer.className = 'cb-editor__footer';
    footer.innerHTML = '<span class="cb-editor__hint">' + cbT('支持粘贴 / 拖拽图片自动上传') + '</span>'
      + '<span class="cb-editor__stat"><span id="cb-editor-count">' + cbT('0 字') + '</span></span>';
    wrap.appendChild(footer);
    this.footer = footer;

    this.el.parentNode.insertBefore(wrap, this.el);
    this.el.style.display = 'none';
    this.wrap = wrap;
    this.bodyWrap = body;
    this.counter = footer.querySelector('#cb-editor-count');
    this.hint = footer.querySelector('.cb-editor__hint');

    this.buildToolbar();
    this.applyMode();
  };

  /* ---------------- 工具栏 ---------------- */
  Editor.prototype.modeSwitch = function () {
    var self = this;
    var bar = document.createElement('div');
    bar.className = 'cb-editor__modes';
    bar.innerHTML =
      '<button type="button" class="cb-ed-mode" data-mode="rich" title="' + cbT('富文本模式（站点默认）') + '">' + ico('image') + cbT('富文本') + '</button>'
      + '<button type="button" class="cb-ed-mode" data-mode="markdown" title="' + cbT('Markdown 模式') + '">' + ico('md') + 'Markdown</button>';
    bar.addEventListener('mousedown', function (e) { e.preventDefault(); });
    bar.querySelectorAll('.cb-ed-mode').forEach(function (b) {
      b.addEventListener('click', function () { self.setMode(b.getAttribute('data-mode')); });
    });
    return bar;
  };

  Editor.prototype.buildToolbar = function () {
    var self = this;
    this.tb.innerHTML = '';

    var btns = this.mode === 'markdown' ? [
      { icon: 'h', title: cbT('标题'), fn: 'mdHeading' },
      { icon: 'bold', title: cbT('加粗'), fn: 'mdWrap', arg: '**' },
      { icon: 'italic', title: cbT('斜体'), fn: 'mdWrap', arg: '*' },
      { icon: 'strike', title: cbT('删除线'), fn: 'mdWrap', arg: '~~' },
      { icon: 'code', title: cbT('行内代码 / 代码块'), fn: 'mdCode' },
      { sep: true },
      { icon: 'quote', title: cbT('引用'), fn: 'mdPrefix', arg: '> ' },
      { icon: 'ul', title: cbT('无序列表'), fn: 'mdPrefix', arg: '- ' },
      { icon: 'ol', title: cbT('有序列表'), fn: 'mdPrefix', arg: '1. ' },
      { icon: 'hr', title: cbT('分割线'), fn: 'mdInsert', arg: '\n---\n' },
      { sep: true },
      { icon: 'table', title: cbT('插入表格'), fn: 'mdTable' },
      { icon: 'link', title: cbT('插入链接'), fn: 'mdLink' },
      { icon: 'image', title: cbT('插入图片'), fn: 'mdImage' },
      { icon: 'attach', title: cbT('插入附件'), fn: 'attachment' },
      { icon: 'preview', title: cbT('预览排版效果'), fn: 'mdPreview' }
    ] : [
      { icon: 'h', title: cbT('标题'), cmd: 'heading', arg: 'h2' },
      { icon: 'bold', title: cbT('加粗'), cmd: 'bold' },
      { icon: 'italic', title: cbT('斜体'), cmd: 'italic' },
      { icon: 'underline', title: cbT('下划线'), cmd: 'underline' },
      { icon: 'strike', title: cbT('删除线'), cmd: 'strikeThrough' },
      { sep: true },
      { icon: 'align-left', title: cbT('左对齐'), cmd: 'justifyLeft' },
      { icon: 'align-center', title: cbT('居中对齐'), cmd: 'justifyCenter' },
      { icon: 'align-right', title: cbT('右对齐'), cmd: 'justifyRight' },
      { sep: true },
      { icon: 'ul', title: cbT('无序列表'), cmd: 'insertUnorderedList' },
      { icon: 'ol', title: cbT('有序列表'), cmd: 'insertOrderedList' },
      { icon: 'quote', title: cbT('引用'), cmd: 'blockquote' },
      { icon: 'hr', title: cbT('分割线'), cmd: 'insertHorizontalRule' },
      { sep: true },
      { icon: 'code', title: cbT('代码块'), fn: 'codeblock' },
      { icon: 'fold', title: cbT('折叠内容'), fn: 'details' },
      { icon: 'table', title: cbT('插入表格'), fn: 'table' },
      { icon: 'link', title: cbT('插入链接'), fn: 'link' },
      { icon: 'image', title: cbT('插入图片'), fn: 'image' },
      { icon: 'attach', title: cbT('插入附件'), fn: 'attachment' },
      { icon: 'emoji', title: cbT('表情'), fn: 'emoji' },
      { sep: true },
      { icon: 'undo', title: cbT('撤销'), cmd: 'undo' },
      { icon: 'redo', title: cbT('恢复'), cmd: 'redo' },
      { icon: 'clear', title: cbT('清除格式'), cmd: 'removeFormat' }
    ];

    btns.forEach(function (b) {
      if (b.sep) {
        var s = document.createElement('span');
        s.className = 'sep';
        self.tb.appendChild(s);
        return;
      }
      var btn = document.createElement('button');
      btn.type = 'button';
      btn.title = b.title;
      btn.className = 'cb-ed-btn';
      btn.innerHTML = ico(b.icon);
      // mousedown 阻止默认行为，避免编辑区失焦丢失光标位置
      btn.addEventListener('mousedown', function (e) {
        e.preventDefault();
        if (self.mode === 'rich') { self.saveRange(); }
      });
      btn.addEventListener('click', function () {
        if (b.fn) { self[b.fn](b.arg); }
        else { self.restoreRange(); document.execCommand(b.cmd, false, b.arg || null); }
        self.sync();
      });
      self.tb.appendChild(btn);
    });

    if (this.mode === 'rich') {
      // 字号
      var size = document.createElement('select');
      size.className = 'cb-ed-sel';
      size.innerHTML = '<option value="">' + cbT('字号') + '</option>' + [1, 2, 3, 4, 5, 6, 7].map(function (i) {
        return '<option value="' + i + '">' + i + cbT(' 号') + '</option>';
      }).join('');
      size.addEventListener('mousedown', function () { self.saveRange(); });
      size.addEventListener('change', function () {
        if (!size.value) return;
        self.restoreRange();
        document.execCommand('fontSize', false, size.value);
        var fs = document.querySelectorAll('font[size="' + size.value + '"]');
        Array.prototype.forEach.call(fs, function (f) {
          var span = document.createElement('span');
          span.style.fontSize = ({ 1: '12px', 2: '14px', 3: '16px', 4: '18px', 5: '22px', 6: '26px', 7: '32px' })[size.value];
          span.innerHTML = f.innerHTML;
          f.parentNode.replaceChild(span, f);
        });
        size.value = '';
        self.sync();
      });
      this.tb.appendChild(size);

      // 标题级别
      var hSel = document.createElement('select');
      hSel.className = 'cb-ed-sel';
      hSel.innerHTML = '<option value="">' + cbT('标题级别') + '</option>'
        + [1,2,3,4,5,6,7,8,9,10].map(function (i) {
            return '<option value="h' + i + '">' + cbT('标题 ' + i) + '</option>';
          }).join('')
        + '<option value="p">' + cbT('正文') + '</option>';
      hSel.addEventListener('mousedown', function () { self.saveRange(); });
      hSel.addEventListener('change', function () {
        if (!hSel.value) return;
        self.restoreRange();
        self.applyHeading(hSel.value);
        hSel.value = '';
        self.sync();
      });
      this.tb.appendChild(hSel);

      // 字体颜色 / 背景颜色（马克笔按钮 + 隐藏取色器）
      [
        ['foreColor', cbT('字体颜色（马克笔）'), '#d93026', '🖊'],
        ['hiliteColor', cbT('背景颜色（荧光马克）'), '#fffbe6', '🖍'],
      ].forEach(function (c) {
        var wrap = document.createElement('span');
        wrap.className = 'cb-ed-colorbtn';
        wrap.title = c[1];
        wrap.style.setProperty('--cb-ed-c', c[2]);
        wrap.appendChild(document.createTextNode(c[3]));
        var ip = document.createElement('input');
        ip.type = 'color';
        ip.value = c[2];
        ip.setAttribute('aria-label', c[1]);
        wrap.appendChild(ip);
        wrap.addEventListener('mousedown', function (e) {
          self.saveRange();
          if (e.target !== ip) {
            // 点击马克笔主体：阻止编辑区失焦并打开原生取色器
            e.preventDefault();
            if (ip.showPicker) { try { ip.showPicker(); } catch (err) { ip.click(); } } else { ip.click(); }
          }
        });
        ip.addEventListener('change', function () {
          self.restoreRange();
          document.execCommand(c[0], false, ip.value);
          wrap.style.setProperty('--cb-ed-c', ip.value);
          self.sync();
        });
        self.tb.appendChild(wrap);
      });
    }

    this.tb.appendChild(this.modeSwitch());
    this.updateModeButtons();
  };

  Editor.prototype.updateModeButtons = function () {
    var self = this;
    if (!this.tb) return;
    this.tb.querySelectorAll('.cb-ed-mode').forEach(function (b) {
      b.classList.toggle('on', b.getAttribute('data-mode') === self.mode);
    });
  };

  /* ---------------- 模式切换 ---------------- */
  Editor.prototype.applyMode = function () {
    var rich = this.mode === 'rich';
    this.wrap.classList.toggle('cb-editor--rich', rich);
    this.wrap.classList.toggle('cb-editor--md', !rich);
    this.area.classList.toggle('hide', !rich);
    this.mdArea.classList.toggle('hide', rich);
    if (this.hint) {
      this.hint.textContent = rich
        ? cbT('支持粘贴 / 拖拽图片自动上传；选中文字或图片可使用快捷工具条')
        : cbT('Markdown 模式：保存后会按 Markdown 语法渲染');
    }
    this.sync();
  };

  Editor.prototype.setMode = function (mode) {
    mode = mode === 'markdown' ? 'markdown' : 'rich';
    if (mode === this.mode) return;
    if (mode === 'markdown') {
      // 富文本 → Markdown：优先用已有 md 源码，其次把当前 HTML 转成 Markdown
      if (!this.mdBuf) { this.mdBuf = this.htmlToMarkdown(this.area.innerHTML); }
      this.htmlBuf = this.area.innerHTML;
    } else {
      // Markdown → 富文本：优先还原原来的富文本内容
      this.mdBuf = this.mdArea.value;
      if (this.htmlBuf) {
        this.area.innerHTML = this.htmlBuf;
      } else if (this.mdBuf.trim()) {
        this.mdToHtml(this.mdBuf);
      }
    }
    this.mode = mode;
    this.buildToolbar();
    this.applyMode();
    if (mode === 'markdown') {
      this.mdArea.value = this.mdBuf;
      this.mdArea.focus();
    } else {
      this.area.focus();
    }
    this.clearImageSelection();
    this.hideFloatBar();
  };

  /* HTML → Markdown（尽力而为的简化转换，供切换模式使用） */
  Editor.prototype.htmlToMarkdown = function (html) {
    var box = document.createElement('div');
    box.innerHTML = html || '';
    var out = '';
    var self = this;
    function inline(node) {
      var s = '';
      Array.prototype.forEach.call(node.childNodes, function (n) {
        if (n.nodeType === 3) {
          s += n.nodeValue.replace(/\s+/g, ' ');
          return;
        }
        if (n.nodeType !== 1) return;
        var tag = n.tagName.toLowerCase();
        var inner = inline(n);
        if (tag === 'br') { s += '\n'; return; }
        if (/^h[1-6]$/.test(tag)) { s += '\n' + '#'.repeat(parseInt(tag[1], 10)) + ' ' + inner + '\n\n'; return; }
        if (tag === 'strong' || tag === 'b') { s += '**' + inner + '**'; return; }
        if (tag === 'em' || tag === 'i') { s += '*' + inner + '*'; return; }
        if (tag === 'del' || tag === 's') { s += '~~' + inner + '~~'; return; }
        if (tag === 'code') { s += '`' + n.textContent + '`'; return; }
        if (tag === 'a') { s += '[' + inner + '](' + (n.getAttribute('href') || '') + ')'; return; }
        if (tag === 'img') { s += '![' + (n.getAttribute('alt') || cbT('图片')) + '](' + (n.getAttribute('src') || '') + ')'; return; }
        if (tag === 'pre') { s += '\n```\n' + n.textContent + '\n```\n'; return; }
        if (tag === 'blockquote') { s += '\n> ' + inner.trim() + '\n'; return; }
        if (tag === 'li') { s += '\n- ' + inner.trim(); return; }
        s += inner;
      });
      return s;
    }
    Array.prototype.forEach.call(box.children, function (child) {
      var tag = child.tagName.toLowerCase();
      if (tag === 'pre') { out += '```\n' + (child.querySelector('code') || child).textContent + '\n```\n\n'; return; }
      if (tag === 'blockquote') { out += '> ' + inline(child).trim() + '\n\n'; return; }
      if (tag === 'ul' || tag === 'ol') {
        Array.prototype.forEach.call(child.querySelectorAll('li'), function (li, i) {
          if (li.parentNode.tagName.toLowerCase() !== tag) return;
          out += (tag === 'ol' ? (i + 1) + '. ' : '- ') + inline(li).trim() + '\n';
        });
        out += '\n';
        return;
      }
      if (tag === 'table') {
        Array.prototype.forEach.call(child.querySelectorAll('tr'), function (tr, ri) {
          var cells = [];
          Array.prototype.forEach.call(tr.children, function (td) { cells.push(inline(td).trim()); });
          out += '| ' + cells.join(' | ') + ' |\n';
          if (ri === 0) { out += '|' + cells.map(function () { return ' --- |'; }).join('') + '\n'; }
        });
        out += '\n';
        return;
      }
      if (tag === 'hr') { out += '---\n\n'; return; }
      out += inline(child).trim() + '\n\n';
    });
    return out.replace(/\n{3,}/g, '\n\n').trim();
  };

  /* Markdown → HTML（调用服务端同一套解析器，保证前后端渲染一致） */
  Editor.prototype.mdToHtml = function (md) {
    var self = this;
    var fd = new FormData();
    fd.append('content', md);
    if (CSRF) fd.append('_csrf', CSRF);
    fetch(API + 'api/markdown', {
      method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).then(function (r) { return r.json(); }).then(function (res) {
      if (res && res.ok && res.data && res.data.html) {
        self.htmlBuf = res.data.html;
        self.area.innerHTML = res.data.html;
        self.sync();
      }
    }).catch(function () { /* 忽略：用户仍可在 Markdown 模式继续编辑 */ });
  };

  Editor.prototype.mdPreview = function () {
    var md = this.mdArea.value;
    if (!md.trim()) { return; }
    var previewed = false;
    var self = this;
    var fd = new FormData();
    fd.append('content', md);
    if (CSRF) fd.append('_csrf', CSRF);
    fetch(API + 'api/markdown', {
      method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).then(function (r) { return r.json(); }).then(function (res) {
      previewed = true;
      self.dialog({
        title: cbT('Markdown 预览'),
        okText: cbT('关闭'),
        width: 760,
        render: function (body) {
          body.innerHTML = '<div class="cb-content-html cb-md-preview">'
            + (res && res.ok && res.data ? res.data.html : '<p class="text-muted">' + cbT('预览失败，请稍后重试') + '</p>')
            + '</div>';
        },
        onOk: function () { }
      });
    }).catch(function () { });
  };

  /* ---------------- Markdown 辅助 ---------------- */
  Editor.prototype.mdAreaEdit = function (fn) {
    var ta = this.mdArea;
    var start = ta.selectionStart, end = ta.selectionEnd;
    var val = ta.value;
    var res = fn(val.slice(start, end), val, start, end);
    ta.value = res.value;
    if (res.selStart !== undefined) {
      ta.selectionStart = res.selStart;
      ta.selectionEnd = res.selEnd !== undefined ? res.selEnd : res.selStart;
    }
    ta.focus();
    this.mdBuf = ta.value;
    this.sync();
  };

  Editor.prototype.mdWrap = function (mark) {
    this.mdAreaEdit(function (sel, val, s, e) {
      var text = sel || cbT('文字');
      return { value: val.slice(0, s) + mark + text + mark + val.slice(e), selStart: s + mark.length, selEnd: s + mark.length + text.length };
    });
  };

  Editor.prototype.mdHeading = function () {
    var self = this;
    this.dialog({
      title: cbT('插入标题'), okText: cbT('插入'),
      fields: [
        { name: 'level', label: cbT('级别'), type: 'select', value: '2', options: [1, 2, 3, 4, 5, 6].map(function (i) { return { value: String(i), label: 'H' + i }; }) },
        { name: 'text', label: cbT('标题文字'), value: cbT('标题'), autofocus: true }
      ],
      onOk: function (v) {
        var t = (v.text || cbT('标题')).trim();
        self.mdInsert('\n' + '#'.repeat(parseInt(v.level, 10) || 2) + ' ' + t + '\n');
      }
    });
  };

  Editor.prototype.mdPrefix = function (prefix) {
    this.mdAreaEdit(function (sel, val, s, e) {
      var lineStart = val.lastIndexOf('\n', s - 1) + 1;
      var lineEnd = val.indexOf('\n', Math.max(e, lineStart));
      if (lineEnd === -1) { lineEnd = val.length; }
      var block = val.slice(lineStart, lineEnd);
      var lines = block.split('\n').map(function (l, i) {
        var p = prefix === '1. ' ? (i + 1) + '. ' : prefix;
        return p + l.replace(/^(\s*)([-*+]\s|\d+[.)]\s|>\s)?/, '$1');
      }).join('\n');
      return { value: val.slice(0, lineStart) + lines + val.slice(lineEnd) };
    });
  };

  Editor.prototype.mdInsert = function (text) {
    var ins = (typeof text === 'string' || typeof text === 'number') ? String(text) : '';
    this.mdAreaEdit(function (sel, val, s, e) {
      return { value: val.slice(0, s) + ins + val.slice(e), selStart: s + ins.length };
    });
  };

  Editor.prototype.mdCode = function () {
    var ta = this.mdArea;
    var sel = ta.value.slice(ta.selectionStart, ta.selectionEnd);
    this.mdInsert(sel ? '\n```\n' + sel + '\n```\n' : '\n```\n代码\n```\n');
  };

  Editor.prototype.mdTable = function () {
    var self = this;
    this.dialog({
      title: cbT('插入表格'), okText: cbT('插入'),
      fields: [
        { name: 'rows', label: cbT('行数（不含表头）'), value: '3', autofocus: true },
        { name: 'cols', label: cbT('列数'), value: '3' }
      ],
      onOk: function (v) {
        var row = parseInt(v.rows, 10) || 3;
        var col = parseInt(v.cols, 10) || 3;
        var line = function (cells) { return '| ' + cells.join(' | ') + ' |'; };
        var head = [], sep = [];
        for (var i = 0; i < col; i++) { head.push(cbT('标题') + (i + 1)); sep.push('---'); }
        var md = '\n' + line(head) + '\n' + line(sep) + '\n';
        for (var r = 0; r < row; r++) {
          var cells = [];
          for (var c = 0; c < col; c++) { cells.push(cbT('内容')); }
          md += line(cells) + '\n';
        }
        self.mdInsert(md + '\n');
      }
    });
  };

  Editor.prototype.mdLink = function () {
    var self = this;
    var sel = this.mdArea.value.slice(this.mdArea.selectionStart, this.mdArea.selectionEnd);
    this.dialog({
      title: cbT('插入链接'), okText: cbT('插入'),
      fields: [
        { name: 'text', label: cbT('显示文字'), value: sel || cbT('链接文字'), autofocus: true },
        { name: 'url', label: cbT('链接地址'), value: 'https://' }
      ],
      onOk: function (v) {
        var url = (v.url || '').trim();
        if (!url || url === 'https://') { return; }
        self.mdInsert('[' + (v.text || url) + '](' + url + ')');
      }
    });
  };

  Editor.prototype.mdImage = function () {
    var self = this;
    var dlg = this.dialog({
      title: cbT('插入图片'), okText: cbT('插入'),
      render: function (body) {
        body.innerHTML =
          '<div class="cb-ed-tabs">'
          + '<button type="button" class="cb-ed-tab active" data-tab="upload">' + cbT('本地上传') + '</button>'
          + '<button type="button" class="cb-ed-tab" data-tab="url">' + cbT('图片链接') + '</button>'
          + '<button type="button" class="cb-ed-tab" data-tab="library">' + cbT('附件库') + '</button>'
          + '</div>'
          + '<div class="cb-ed-pane" data-pane="upload">'
          + '<div class="cb-ed-drop" data-drop>' + cbT('点击选择图片，或将图片拖拽到此处')
          + '<div class="text-xs text-muted" style="margin-top:6px">' + cbT('选中多张可一次性上传') + '</div></div>'
          + '<input type="file" data-file accept="image/*" multiple class="hide">'
          + '</div>'
          + '<div class="cb-ed-pane hide" data-pane="url">'
          + '<div class="cb-ed-field"><label>图片地址</label>'
          + '<input class="cb-input" data-fname="imgurl" id="cbed-imgurl" placeholder="https://example.com/a.png"></div>'
          + '<div class="cb-ed-field"><label>替代文字（可选）</label>'
          + '<input class="cb-input" data-fname="imgalt" placeholder="图片描述"></div>'
          + '</div>'
          + '<div class="cb-ed-pane hide" data-pane="library">'
          + '<div class="cb-ed-library" data-library><div class="cb-ed-library__tip">加载中…</div></div>'
          + '</div>';
        self.bindTabs(body);
        self.bindLibrary(body.querySelector('[data-library]'), function (url, name) {
          self.mdInsert('![' + (name || '图片') + '](' + url + ')\n');
          dlg.close();
        });
        var file = body.querySelector('[data-file]');
        var drop = body.querySelector('[data-drop]');
        drop.addEventListener('click', function () { file.click(); });
        file.addEventListener('change', function () {
          Array.prototype.forEach.call(file.files, function (f) { self.upload(f, null, true); });
          dlg.close();
        });
        ['dragover', 'dragenter'].forEach(function (ev) {
          drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.add('over'); });
        });
        ['dragleave', 'drop'].forEach(function (ev) {
          drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.remove('over'); });
        });
        drop.addEventListener('drop', function (e) {
          e.preventDefault();
          Array.prototype.forEach.call(e.dataTransfer.files, function (f) { self.upload(f, null, true); });
          dlg.close();
        });
      },
      onOk: function (v) {
        var u = (v.imgurl || '').trim();
        if (u) { self.mdInsert('![' + (v.imgalt || '图片') + '](' + u + ')\n'); }
      }
    });
  };

  Editor.prototype.bindTabs = function (body) {
    body.querySelectorAll('.cb-ed-tab').forEach(function (t) {
      t.addEventListener('click', function () {
        body.querySelectorAll('.cb-ed-tab').forEach(function (x) { x.classList.remove('active'); });
        t.classList.add('active');
        body.querySelectorAll('.cb-ed-pane').forEach(function (p) {
          p.classList.toggle('hide', p.getAttribute('data-pane') !== t.getAttribute('data-tab'));
        });
      });
    });
  };

  /* 附件库：只列图片时的渲染 + 选择回调 */
  Editor.prototype.bindLibrary = function (lib, pick) {
    var apiBase = window.CB_API_BASE || ((window.CB_SITE_URL || '') + '/');
    lib.innerHTML = '<div class="cb-ed-library__tip">' + cbT('加载中…') + '</div>';
    // 兼容 query 模式（CB_API_BASE 以 ?r= 结尾）：追加参数时用 & 而非 ?
    fetch(apiBase + 'api/my-files' + (apiBase.indexOf('?') >= 0 ? '&' : '?') + 'type=image', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        var items = (res && res.data && res.data.items) || [];
        if (!items.length) {
          lib.innerHTML = '<div class="cb-ed-library__tip">' + cbT('暂无图片，先上传一张吧') + '</div>';
          return;
        }
        lib.innerHTML = '';
        items.forEach(function (it) {
          var cell = document.createElement('button');
          cell.type = 'button';
          cell.className = 'cb-ed-library__item';
          cell.title = it.name || '';
          cell.innerHTML = '<img src="' + esc(it.url) + '" alt="" loading="lazy">';
          cell.addEventListener('click', function () { pick(it.url, it.name); });
          lib.appendChild(cell);
        });
      })
      .catch(function () {
        lib.innerHTML = '<div class="cb-ed-library__tip">' + cbT('加载失败，请稍后重试') + '</div>';
      });
  };

  /* ---------------- 事件绑定 ---------------- */
  Editor.prototype.bind = function () {
    var self = this;

    this.area.addEventListener('input', function () { self.sync(); self.scheduleForbiddenCheck(); });
    this.area.addEventListener('blur', function () { self.sync(); });
    this.mdArea.addEventListener('input', function () {
      self.mdBuf = self.mdArea.value; self.sync(); self.scheduleForbiddenCheck();
    });
    ['keyup', 'mouseup', 'touchend'].forEach(function (ev) {
      self.area.addEventListener(ev, function () {
        self.saveRange();
        self.detectFloatBar();
      });
    });
    this.area.addEventListener('focus', function () { if (!self.savedRange) { self.saveRange(); } });

    this.mdArea.addEventListener('blur', function () { self.mdBuf = self.mdArea.value; self.sync(); });

    /* 违禁词：初始化时拉取词表，之后输入时实时检测 */
    this.loadForbidden();

    /* Ctrl + Enter 提交 */
    function submit(e) {
      if (e.ctrlKey && e.key === 'Enter') {
        var form = self.el.closest('form');
        if (form) { e.preventDefault(); form.requestSubmit ? form.requestSubmit() : form.submit(); }
      }
    }
    this.area.addEventListener('keydown', submit);
    this.mdArea.addEventListener('keydown', submit);

    /* 粘贴 / 拖拽图片自动上传 */
    this.area.addEventListener('paste', function (e) {
      var items = (e.clipboardData || e.originalEvent.clipboardData || {}).items;
      if (!items) return;
      for (var i = 0; i < items.length; i++) {
        if (items[i].type.indexOf('image') === 0) {
          e.preventDefault();
          self.upload(items[i].getAsFile());
        }
      }
    });
    [this.area, this.mdArea].forEach(function (zone) {
      zone.addEventListener('drop', function (e) {
        var files = e.dataTransfer && e.dataTransfer.files;
        if (!files || !files.length) return;
        var hasFile = false;
        for (var i = 0; i < files.length; i++) {
          if (files[i].type.indexOf('image') === 0) {
            hasFile = true;
            self.upload(files[i], null, self.mode === 'markdown');
          }
        }
        if (hasFile) { e.preventDefault(); }
      });
      zone.addEventListener('dragover', function (e) { e.preventDefault(); });
    });

    /* 图片选中 → 尺寸 / 位置调整框 */
    this.area.addEventListener('click', function (e) {
      var img = e.target.closest ? e.target.closest('img') : null;
      if (img && self.area.contains(img)) { self.selectImage(img); }
    });
    this.area.addEventListener('scroll', function () { self.positionImageBox(); }, { passive: true });
    // 滚动 / 缩放时重定位图片框并收起浮动工具条。
    // 用 rAF 节流 + passive，且无选中目标时直接返回，避免每个滚动事件都触发布局计算。
    var pending = false;
    function schedule() {
      if (pending) { return; }
      pending = true;
      requestAnimationFrame(function () {
        pending = false;
        self.positionImageBox();
        self.hideFloatBar();
      });
    }
    window.addEventListener('scroll', schedule, { passive: true, capture: true });
    window.addEventListener('resize', schedule, { passive: true });
    document.addEventListener('mousedown', function (e) {
      if (self.imgBox && !self.imgBox.contains(e.target) && e.target !== self.activeImg) {
        self.clearImageSelection();
      }
      if (self.floatBar && !self.floatBar.contains(e.target)) { self.hideFloatBar(); }
    });
  };

  /* ---------------- 同步到表单字段 ---------------- */
  Editor.prototype.sync = function () {
    var html = this.mode === 'rich' ? this.area.innerHTML : '';
    var text = this.mode === 'rich' ? (this.area.textContent || '') : this.mdArea.value;
    if (this.mode === 'rich') {
      this.htmlBuf = html;
    } else {
      this.mdBuf = this.mdArea.value;
    }
    if (this.valueEl) { this.valueEl.value = this.mode === 'rich' ? html : this.mdArea.value; }
    var fmt = document.getElementById(this.opts.input ? this.opts.input + '_format' : '');
    if (fmt) { fmt.value = this.mode; }
    if (this.counter) { this.counter.textContent = text.length + cbT(' 字'); }
  };

  Editor.prototype.getHTML = function () {
    this.sync();
    return this.valueEl ? this.valueEl.value : this.area.innerHTML;
  };

  /* ---------------- 光标 / 选区 ---------------- */
  Editor.prototype.saveRange = function () {
    this.savedRange = null;
    if (!this.area) { return; }
    var sel = window.getSelection();
    if (!sel || !sel.rangeCount) { return; }
    var r = sel.getRangeAt(0);
    if (this.area.contains(r.commonAncestorContainer)) {
      this.savedRange = r.cloneRange();
    }
  };

  Editor.prototype.restoreRange = function () {
    if (!this.area) { return; }
    this.area.focus();
    var sel = window.getSelection();
    if (!sel) { return; }
    if (this.savedRange) {
      try {
        sel.removeAllRanges();
        sel.addRange(this.savedRange);
        return;
      } catch (e) {
        this.savedRange = null;
      }
    }
    var r = document.createRange();
    r.selectNodeContents(this.area);
    r.collapse(false);
    sel.removeAllRanges();
    sel.addRange(r);
  };

  Editor.prototype.insertHTML = function (html) {
    // Markdown 模式下把 HTML 转成 Markdown 再插入（引用、加粗等都能正确显示）
    if (this.mode === 'markdown') { this.mdInsert(this.htmlToMarkdown(html)); return; }
    this.restoreRange();
    document.execCommand('insertHTML', false, html);
    var sel = window.getSelection();
    if (sel && sel.rangeCount) {
      var r = sel.getRangeAt(0);
      r.collapse(false);
      sel.removeAllRanges();
      sel.addRange(r);
      this.saveRange();
    }
    this.sync();
  };

  /* ---------------- 浮动快捷工具条（选中文字） ---------------- */
  Editor.prototype.detectFloatBar = function () {
    if (this.mode !== 'rich') { this.hideFloatBar(); return; }
    var sel = window.getSelection();
    if (!sel || !sel.rangeCount || sel.isCollapsed) { this.hideFloatBar(); return; }
    var range = sel.getRangeAt(0);
    if (!this.area.contains(range.commonAncestorContainer)) { this.hideFloatBar(); return; }
    var rect = range.getBoundingClientRect();
    if (!rect || (!rect.width && !rect.height)) { this.hideFloatBar(); return; }
    this.buildFloatBar(rect);
  };

  /* ---------------- 违禁词实时检测 ---------------- */

  /**
   * 拉取违禁词表（每个编辑器实例只请求一次）
   */
  Editor.prototype.loadForbidden = function () {
    var self = this;
    if (!window.CB_API_BASE) { return; }
    fetch(window.CB_API_BASE + 'api/forbidden-words', { credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        var words = (res && res.data && res.data.words) || [];
        self.forbiddenWords = words;
        if (words.length) { self.checkForbidden(); }
      })
      .catch(function () { /* 忽略：服务端仍会在提交时兜底拦截 */ });
  };

  /** 输入防抖，避免每敲一个字符都全文扫描 */
  Editor.prototype.scheduleForbiddenCheck = function () {
    var self = this;
    if (!this.forbiddenWords || !this.forbiddenWords.length) { return; }
    if (this.forbiddenTimer) { clearTimeout(this.forbiddenTimer); }
    this.forbiddenTimer = setTimeout(function () { self.checkForbidden(); }, 400);
  };

  /**
   * 检测当前正文 / Markdown 源码，命中则显示提示条
   */
  Editor.prototype.checkForbidden = function () {
    var self = this;
    var words = this.forbiddenWords || [];
    var strip = this.forbiddenStrip;
    if (!words.length) {
      if (strip) { strip.className = 'cb-ed-forbidden hide'; }
      return;
    }
    var text = this.mode === 'markdown'
      ? (this.mdArea ? this.mdArea.value : '')
      : (this.area.textContent || '');
    var lower = text.toLowerCase();
    var hits = [];
    words.forEach(function (w) {
      if (w && lower.indexOf(w.toLowerCase()) !== -1) { hits.push(w); }
    });
    if (!strip) {
      strip = document.createElement('div');
      strip.className = 'cb-ed-forbidden';
      (this.footer || this.el).appendChild(strip);
      this.forbiddenStrip = strip;
    }
    if (!hits.length) {
      strip.className = 'cb-ed-forbidden hide';
      strip.innerHTML = '';
      return;
    }
    strip.className = 'cb-ed-forbidden';
    strip.innerHTML = '<span class="cb-ed-forbidden__ico">!</span>'
      + '<span>' + cbT('内容包含违禁词，发布会被拦截：') + '</span>'
      + hits.map(function (w) {
        return '<mark class="cb-ed-forbidden__word" data-word="' + w.replace(/"/g, '&quot;') + '">'
          + w.replace(/[<>&]/g, '') + '</mark>';
      }).join(' ');

    // 点击提示里的词 → 高亮并滚动到编辑器中该词附近
    strip.querySelectorAll('.cb-ed-forbidden__word').forEach(function (el) {
      el.style.cursor = 'pointer';
      el.title = cbT('点击定位');
      el.addEventListener('click', function () { self.scrollToWord(el.getAttribute('data-word')); });
    });
  };

  /** 尽量把命中的词滚动到可视区域（位于编辑区内的情形） */
  Editor.prototype.scrollToWord = function (word) {
    if (!word || this.mode === 'markdown' || !this.area) { return; }
    var nodes = this.area.querySelectorAll('p, div, li, h1, h2, h3, h4');
    var lower = word.toLowerCase();
    for (var i = 0; i < nodes.length; i++) {
      if ((nodes[i].textContent || '').toLowerCase().indexOf(lower) !== -1) {
        nodes[i].scrollIntoView({ block: 'center', behavior: 'smooth' });
        break;
      }
    }
  };

  Editor.prototype.buildFloatBar = function (rect) {
    var self = this;
    // 先把当前选区保存下来：后面点击 select / 颜色选择器都会让编辑区失焦，
    // 若等 change 事件再取选区就已经丢了，导致「格式 / 字号」选了没反应。
    this.saveRange();
    if (!this.floatBar) {
      var bar = document.createElement('div');
      bar.className = 'cb-ed-floatbar';
      bar.innerHTML =
        '<select class="cb-ed-sel cb-ed-fsel" data-act="format">'
        + '<option value="">' + cbT('格式') + '</option><option value="p">' + cbT('正文') + '</option>'
        + [1,2,3,4,5,6,7,8,9,10].map(function (i) {
            return '<option value="h' + i + '">' + cbT('标题 ' + i) + '</option>';
          }).join('')
        + '</select>'
        + '<select class="cb-ed-sel cb-ed-fsel" data-act="size">'
        + '<option value="">' + cbT('字号') + '</option>'
        + [1, 2, 3, 4, 5, 6, 7].map(function (i) { return '<option value="' + i + '">' + i + cbT(' 号') + '</option>'; }).join('')
        + '</select>'
        + '<span class="cb-ed-fsep"></span>'
        + '<button type="button" title="' + cbT('加粗') + '" data-cmd="bold">' + ico('bold') + '</button>'
        + '<button type="button" title="' + cbT('斜体') + '" data-cmd="italic">' + ico('italic') + '</button>'
        + '<button type="button" title="' + cbT('下划线') + '" data-cmd="underline">' + ico('underline') + '</button>'
        + '<button type="button" title="' + cbT('删除线') + '" data-cmd="strikeThrough">' + ico('strike') + '</button>'
        + '<span class="cb-ed-fsep"></span>'
        + '<button type="button" title="' + cbT('左对齐') + '" data-cmd="justifyLeft">' + ico('align-left') + '</button>'
        + '<button type="button" title="' + cbT('居中对齐') + '" data-cmd="justifyCenter">' + ico('align-center') + '</button>'
        + '<button type="button" title="' + cbT('右对齐') + '" data-cmd="justifyRight">' + ico('align-right') + '</button>'
        + '<span class="cb-ed-fsep"></span>'
        + '<span class="cb-ed-colorbtn" title="' + cbT('字体颜色（马克笔）') + '" data-color="foreColor" style="--cb-ed-c:#d93026">🖊<input type="color" value="#d93026" aria-label="' + cbT('字体颜色') + '"></span>'
        + '<span class="cb-ed-colorbtn" title="' + cbT('背景颜色（荧光马克）') + '" data-color="hiliteColor" style="--cb-ed-c:#fffbe6">🖍<input type="color" value="#fffbe6" aria-label="' + cbT('背景颜色') + '"></span>'
        + '<button type="button" title="' + cbT('清除格式') + '" data-cmd="removeFormat">' + ico('clear') + '</button>'
        + '<button type="button" title="' + cbT('插入链接') + '" data-act="link">' + ico('link') + '</button>';
      bar.addEventListener('mousedown', function (e) {
        // 原生下拉 / 颜色按钮需要默认行为才能展开；只对其余元素阻止默认，避免编辑区失焦
        if (e.target && (e.target.tagName === 'SELECT' || e.target.closest('select') || e.target.closest('.cb-ed-colorbtn'))) { return; }
        e.preventDefault();
      });
      bar.querySelectorAll('.cb-ed-colorbtn').forEach(function (w) {
        var ip = w.querySelector('input[type=color]');
        w.addEventListener('mousedown', function (e) {
          self.saveRange();
          if (e.target !== ip) {
            e.preventDefault();
            if (ip.showPicker) { try { ip.showPicker(); } catch (err) { ip.click(); } } else { ip.click(); }
          }
        });
        ip.addEventListener('change', function () {
          self.restoreRange();
          document.execCommand(w.getAttribute('data-color'), false, ip.value);
          w.style.setProperty('--cb-ed-c', ip.value);
          self.sync();
        });
      });
      bar.querySelectorAll('[data-cmd]').forEach(function (b) {
        b.addEventListener('click', function () {
          self.restoreRange();
          document.execCommand(b.getAttribute('data-cmd'), false, null);
          self.sync();
        });
      });
      bar.querySelectorAll('[data-act]').forEach(function (b) {
        var act = b.getAttribute('data-act');
        if (act === 'link') {
          b.addEventListener('click', function () { self.link(); });
          return;
        }
        b.addEventListener('change', function () {
          if (!b.value) return;
          self.restoreRange();
          if (act === 'format') {
            self.applyHeading(b.value);
          } else {
            self.applyFontSize(b.value);
          }
          b.value = '';
          self.sync();
        });
      });
      document.body.appendChild(bar);
      this.floatBar = bar;
    }
    this.floatBar.style.display = 'flex';
    var top = rect.top + window.scrollY - this.floatBar.offsetHeight - 8;
    var left = rect.left + window.scrollX + rect.width / 2;
    if (top < 0) { top = rect.bottom + window.scrollY + 8; }
    this.floatBar.style.top = top + 'px';
    this.floatBar.style.left = left + 'px';
  };

  Editor.prototype.applyFontSize = function (value) {
    document.execCommand('fontSize', false, value);
    var fs = document.querySelectorAll('font[size="' + value + '"]');
    Array.prototype.forEach.call(fs, function (f) {
      var span = document.createElement('span');
      span.style.fontSize = ({ 1: '12px', 2: '14px', 3: '16px', 4: '18px', 5: '22px', 6: '26px', 7: '32px' })[value];
      span.innerHTML = f.innerHTML;
      f.parentNode.replaceChild(span, f);
    });
  };

  /**
   * 应用标题级别：h1 - h6 使用标准标签，h7 - h10 复用 h6 并附加 cb-h7... 类，
   * 由内容区 CSS 控制字号（浏览器没有 h7 - h10 标签）。
   */
  Editor.prototype.applyHeading = function (value) {
    value = String(value || '').toLowerCase();
    if (value === 'p') {
      document.execCommand('formatBlock', false, 'p');
    } else {
      var level = parseInt(value.replace('h', ''), 10);
      if (!level || level < 1) return;
      var tag = Math.min(6, Math.max(1, level));
      document.execCommand('formatBlock', false, 'h' + tag);
      if (level > 6) {
        var sel = window.getSelection();
        var node = sel && sel.anchorNode;
        var el = node && (node.nodeType === 1 ? node : node.parentNode);
        var block = el && el.closest ? el.closest('h' + tag) : null;
        if (block) {
          for (var i = 7; i <= 10; i++) block.classList.remove('cb-h' + i);
          block.classList.add('cb-h' + level);
        }
      }
    }
    // 从 h7-h10 / h6 切回普通标题或正文时，清掉自定义级别类
    var s = window.getSelection();
    var n = s && s.anchorNode;
    var e = n && (n.nodeType === 1 ? n : n.parentNode);
    var blk = e && e.closest ? e.closest('h1,h2,h3,h4,h5,h6,p,div') : null;
    if (blk && !(blk.classList && blk.classList.contains('cb-h' + (parseInt(value.replace('h', ''), 10) || 0)))) {
      for (var j = 7; j <= 10; j++) blk.classList.remove('cb-h' + j);
    }
    this.sync();
  };

  Editor.prototype.hideFloatBar = function () {
    if (this.floatBar) { this.floatBar.style.display = 'none'; }
  };

  /* ---------------- 图片调整框 ---------------- */
  Editor.prototype.selectImage = function (img) {
    this.clearImageSelection();
    this.activeImg = img;
    img.classList.add('is-selected');

    var box = document.createElement('div');
    box.className = 'cb-ed-imgbox';
    box.innerHTML =
      '<span class="cb-ed-imghandle" data-h="nw"></span>'
      + '<span class="cb-ed-imghandle" data-h="ne"></span>'
      + '<span class="cb-ed-imghandle" data-h="sw"></span>'
      + '<span class="cb-ed-imghandle" data-h="se"></span>'
      + '<div class="cb-ed-imgbar">'
      + '<button type="button" data-w="25">25%</button>'
      + '<button type="button" data-w="50">50%</button>'
      + '<button type="button" data-w="75">75%</button>'
      + '<button type="button" data-w="100">100%</button>'
      + '<span class="cb-ed-fsep"></span>'
      + '<button type="button" data-al="left" title="' + cbT('左对齐') + '">' + ico('align-left') + '</button>'
      + '<button type="button" data-al="center" title="' + cbT('居中') + '">' + ico('align-center') + '</button>'
      + '<button type="button" data-al="right" title="' + cbT('右对齐') + '">' + ico('align-right') + '</button>'
      + '<span class="cb-ed-fsep"></span>'
      + '<button type="button" data-fl="left" title="' + cbT('文字环绕（左）') + '">' + ico('float-left') + '</button>'
      + '<button type="button" data-fl="none" title="' + cbT('取消环绕') + '">' + ico('float-none') + '</button>'
      + '<button type="button" data-fl="right" title="' + cbT('文字环绕（右）') + '">' + ico('panel-right') + '</button>'
      + '<span class="cb-ed-fsep"></span>'
      + '<button type="button" data-bg="1" title="' + cbT('作为背景图（文字浮于其上）') + '">' + ico('bgimage') + '</button>'
      + '<button type="button" data-del="1" title="' + cbT('删除图片') + '">' + ico('close') + '</button>'
      + '</div>';

    var self = this;
    box.addEventListener('mousedown', function (e) { e.preventDefault(); });
    box.querySelectorAll('[data-w]').forEach(function (b) {
      b.addEventListener('click', function () { self.imgWidth(b.getAttribute('data-w')); });
    });
    box.querySelectorAll('[data-al]').forEach(function (b) {
      b.addEventListener('click', function () { self.imgAlign(b.getAttribute('data-al')); });
    });
    box.querySelectorAll('[data-fl]').forEach(function (b) {
      b.addEventListener('click', function () { self.imgFloat(b.getAttribute('data-fl')); });
    });
    box.querySelector('[data-bg]').addEventListener('click', function () { self.imgBackground(); });
    box.querySelector('[data-del]').addEventListener('click', function () { self.imgDelete(); });

    /* 拖拽缩放 */
    box.querySelectorAll('.cb-ed-imghandle').forEach(function (h) {
      h.addEventListener('mousedown', function (e) {
        e.preventDefault();
        e.stopPropagation();
        self.startResize(img, h.getAttribute('data-h'), e);
      });
    });

    document.body.appendChild(box);
    this.imgBox = box;
    this.positionImageBox();
  };

  Editor.prototype.positionImageBox = function () {
    // 没有选中图片时不做任何计算，避免滚动时反复触发强制重排
    if (!this.imgBox || !this.activeImg) { return; }
    var img = this.activeImg;
    if (!document.body.contains(img)) { this.clearImageSelection(); return; }
    var rect = img.getBoundingClientRect();
    var box = this.imgBox;
    box.style.left = (rect.left + window.scrollX) + 'px';
    box.style.top = (rect.top + window.scrollY) + 'px';
    box.style.width = rect.width + 'px';
    box.style.height = rect.height + 'px';
  };

  Editor.prototype.clearImageSelection = function () {
    if (this.imgBox) { this.imgBox.remove(); this.imgBox = null; }
    if (this.activeImg) { this.activeImg.classList.remove('is-selected'); this.activeImg = null; }
  };

  Editor.prototype.imgWidth = function (pct) {
    if (!this.activeImg) { return; }
    if (pct === '100') {
      this.activeImg.style.maxWidth = '100%';
      this.activeImg.style.width = '';
    } else {
      this.activeImg.style.maxWidth = '';
      this.activeImg.style.width = pct + '%';
    }
    this.sync();
    this.positionImageBox();
  };

  Editor.prototype.imgAlign = function (al) {
    if (!this.activeImg) { return; }
    var img = this.activeImg;
    img.style.float = '';
    img.style.display = 'block';
    if (al === 'left') { img.style.margin = '6px auto 6px 0'; }
    else if (al === 'right') { img.style.margin = '6px 0 6px auto'; }
    else { img.style.margin = '6px auto'; }
    this.sync();
    this.positionImageBox();
  };

  Editor.prototype.imgFloat = function (fl) {
    if (!this.activeImg) { return; }
    var img = this.activeImg;
    img.style.maxWidth = '55%';
    if (fl === 'left') { img.style.float = 'left'; img.style.margin = '0 14px 8px 0'; }
    else if (fl === 'right') { img.style.float = 'right'; img.style.margin = '0 0 8px 14px'; }
    else { img.style.float = ''; img.style.margin = '6px auto'; }
    this.sync();
    this.positionImageBox();
  };

  Editor.prototype.imgDelete = function () {
    if (!this.activeImg) { return; }
    var img = this.activeImg;
    this.clearImageSelection();
    if (img.parentNode === this.area || (img.parentNode && img.parentNode.tagName === 'P' && !img.parentNode.textContent.trim())) {
      img.parentNode.removeChild(img);
    } else {
      img.parentNode.removeChild(img);
    }
    this.sync();
  };

  /**
   * 作为背景图：把图片所在的块级容器改成带背景图的区块，文字浮于其上
   */
  Editor.prototype.imgBackground = function () {
    if (!this.activeImg) { return; }
    var img = this.activeImg;
    var src = img.getAttribute('src') || '';
    if (!src) { return; }
    /* 找到最近的块级容器 */
    var blk = img.parentNode;
    while (blk && blk !== this.area && ['P', 'DIV', 'TD', 'LI', 'FIGURE'].indexOf(blk.tagName) === -1) {
      blk = blk.parentNode;
    }
    if (!blk || blk === this.area) { blk = img.parentNode || this.area; }

    var figure = document.createElement('div');
    figure.className = 'cb-bg-figure';
    figure.setAttribute('style',
      'position:relative;background-image:url(' + src.replace(/[()'"\\;]/g, '') + ');'
      + 'background-size:cover;background-position:center;padding:38px 22px;min-height:120px;overflow:hidden;border-radius:6px');

    var inner = document.createElement('div');
    inner.setAttribute('style', 'position:relative;z-index:1');
    while (blk.firstChild) {
      if (blk.firstChild === img) { blk.removeChild(blk.firstChild); continue; }
      inner.appendChild(blk.firstChild);
    }
    if (!inner.childNodes.length) { inner.innerHTML = '<p><br></p>'; }
    figure.appendChild(inner);
    blk.parentNode.insertBefore(figure, blk);
    blk.parentNode.removeChild(blk);
    this.clearImageSelection();
    this.sync();
  };

  Editor.prototype.startResize = function (img, handle, ev) {
    var self = this;
    var startX = ev.clientX, startY = ev.clientY;
    var startW = img.getBoundingClientRect().width;
    var right = handle.indexOf('e') > -1;
    function move(e) {
      var dx = e.clientX - startX;
      var dy = e.clientY - startY;
      var delta = Math.abs(dx) > Math.abs(dy) ? dx : dy;
      if (!right) { delta = -delta; }
      var w = Math.max(40, Math.round(startW + delta));
      img.style.maxWidth = '100%';
      img.style.width = w + 'px';
      self.positionImageBox();
    }
    function up() {
      document.removeEventListener('mousemove', move);
      document.removeEventListener('mouseup', up);
      self.sync();
      self.positionImageBox();
    }
    document.addEventListener('mousemove', move);
    document.addEventListener('mouseup', up);
  };

  /* ---------------- 通用弹窗 ---------------- */
  Editor.prototype.dialog = function (opts) {
    var mask = document.createElement('div');
    mask.className = 'cb-ed-mask';
    var box = document.createElement('div');
    box.className = 'cb-ed-dialog';
    if (opts.width) { box.style.maxWidth = opts.width + 'px'; }
    box.innerHTML = '<div class="cb-ed-dialog__head"><b>' + (opts.title || '') + '</b>'
      + '<button type="button" class="cb-ed-close" aria-label="' + cbT('关闭') + '">×</button></div>'
      + '<div class="cb-ed-dialog__body"></div>'
      + '<div class="cb-ed-dialog__foot">'
      + '<button type="button" class="cb-btn cb-ed-cancel">' + cbT('取消') + '</button>'
      + '<button type="button" class="cb-btn primary cb-ed-ok">' + (opts.okText || cbT('插入')) + '</button>'
      + '</div>';
    var body = box.querySelector('.cb-ed-dialog__body');
    if (opts.render) {
      opts.render(body);
    } else {
      (opts.fields || []).forEach(function (f) {
        var wrap = document.createElement('div');
        wrap.className = 'cb-ed-field';
        var id = 'cbed-' + f.name + '-' + Math.random().toString(36).slice(2, 7);
        wrap.innerHTML = '<label for="' + id + '">' + (f.label || '') + '</label>';
        var el;
        if (f.type === 'textarea') {
          el = document.createElement('textarea');
          el.rows = f.rows || 6;
        } else if (f.type === 'select') {
          el = document.createElement('select');
          (f.options || []).forEach(function (o) {
            var op = document.createElement('option');
            op.value = o.value; op.textContent = o.label;
            el.appendChild(op);
          });
        } else {
          el = document.createElement('input');
          el.type = f.type || 'text';
        }
        el.id = id;
        el.className = 'cb-input';
        el.setAttribute('data-fname', f.name);
        if (f.placeholder) el.placeholder = f.placeholder;
        if (f.value !== undefined && f.value !== null) el.value = f.value;
        wrap.appendChild(el);
        body.appendChild(wrap);
        if (f.autofocus) { setTimeout(function () { el.focus(); }, 30); }
      });
    }

    mask.appendChild(box);
    document.body.appendChild(mask);

    function close() { mask.remove(); document.removeEventListener('keydown', onKey); }
    function onKey(e) {
      if (e.key === 'Escape') close();
      if (e.key === 'Enter' && (e.metaKey || e.ctrlKey)) { box.querySelector('.cb-ed-ok').click(); }
    }
    document.addEventListener('keydown', onKey);
    mask.addEventListener('click', function (e) { if (e.target === mask) close(); });
    box.querySelector('.cb-ed-close').addEventListener('click', close);
    box.querySelector('.cb-ed-cancel').addEventListener('click', close);
    box.querySelector('.cb-ed-ok').addEventListener('click', function () {
      var values = {};
      body.querySelectorAll('[data-fname]').forEach(function (el) { values[el.getAttribute('data-fname')] = el.value; });
      if (opts.onOk && opts.onOk(values, body) === false) { return; }
      close();
    });
    return { mask: mask, body: body, close: close };
  };

  Editor.prototype.codeblock = function () {
    var self = this;
    this.dialog({
      title: cbT('插入代码块'),
      okText: cbT('插入'),
      fields: [
        {
          name: 'lang', label: cbT('语言（可选）'), type: 'select', value: '',
          options: [
            { value: '', label: cbT('纯文本') }, { value: 'php', label: 'PHP' },
            { value: 'javascript', label: 'JavaScript' }, { value: 'html', label: 'HTML' },
            { value: 'css', label: 'CSS' }, { value: 'python', label: 'Python' },
            { value: 'sql', label: 'SQL' }, { value: 'bash', label: 'Shell' },
            { value: 'json', label: 'JSON' }
          ]
        },
        { name: 'code', label: cbT('代码内容'), type: 'textarea', rows: 8, placeholder: cbT('在此粘贴代码…'), autofocus: true }
      ],
      onOk: function (v) {
        if (!v.code) { return; }
        var cls = v.lang ? ' class="language-' + esc(v.lang) + '"' : '';
        self.insertHTML('<pre><code' + cls + '>' + esc(v.code) + '</code></pre><p><br></p>');
      }
    });
  };

  Editor.prototype.details = function () {
    var self = this;
    this.dialog({
      title: cbT('插入折叠内容'),
      okText: cbT('插入'),
      fields: [
        { name: 'summary', label: cbT('折叠标题'), value: cbT('点击展开'), autofocus: true },
        { name: 'content', label: cbT('折叠内容'), type: 'textarea', rows: 6, placeholder: cbT('展开后显示的内容…') }
      ],
      onOk: function (v) {
        self.insertHTML('<details><summary>' + esc(v.summary || cbT('点击展开')) + '</summary><p>'
          + esc(v.content || cbT('隐藏内容')) + '</p></details><p><br></p>');
      }
    });
  };

  Editor.prototype.table = function () {
    var self = this;
    this.dialog({
      title: cbT('插入表格'),
      okText: cbT('插入'),
      fields: [
        { name: 'rows', label: cbT('行数（不含表头）'), value: '3', autofocus: true },
        { name: 'cols', label: cbT('列数'), value: '3' },
        { name: 'header', label: cbT('包含表头'), type: 'select', value: '1', options: [{ value: '1', label: cbT('是') }, { value: '0', label: cbT('否') }] }
      ],
      onOk: function (v) {
        var row = parseInt(v.rows, 10) || 3;
        var col = parseInt(v.cols, 10) || 3;
        var html = '<table>';
        if (v.header === '1') {
          html += '<thead><tr>' + '<th>' + cbT('标题') + '</th>'.repeat(col) + '</tr></thead>';
        }
        html += '<tbody>';
        for (var r = 0; r < row; r++) { html += '<tr>' + '<td>' + cbT('内容') + '</td>'.repeat(col) + '</tr>'; }
        html += '</tbody></table><p><br></p>';
        self.insertHTML(html);
      }
    });
  };

  Editor.prototype.link = function () {
    var self = this;
    var selText = '';
    try {
      var s = window.getSelection();
      selText = s ? s.toString() : '';
    } catch (e) { selText = ''; }
    this.dialog({
      title: cbT('插入链接'),
      okText: cbT('插入'),
      fields: [
        { name: 'text', label: cbT('显示文字'), value: selText, placeholder: cbT('留空则使用当前选中内容') },
        { name: 'url', label: cbT('链接地址'), value: 'https://', autofocus: true },
        { name: 'blank', label: cbT('打开方式'), type: 'select', value: '_self', options: [{ value: '_self', label: cbT('当前窗口') }, { value: '_blank', label: cbT('新窗口') }] }
      ],
      onOk: function (v) {
        var url = (v.url || '').trim();
        if (!url || url === 'https://') { return; }
        var html = '<a href="' + esc(url) + '"' + (v.blank === '_blank' ? ' target="_blank" rel="noopener"' : '') + '>'
          + esc(v.text || url) + '</a>';
        self.insertHTML(html);
      }
    });
  };

  Editor.prototype.image = function () {
    var self = this;
    var dlg = this.dialog({
      title: cbT('插入图片'),
      okText: cbT('插入'),
      render: function (body) {
        body.innerHTML =
          '<div class="cb-ed-tabs">'
          + '<button type="button" class="cb-ed-tab active" data-tab="upload">' + cbT('本地上传') + '</button>'
          + '<button type="button" class="cb-ed-tab" data-tab="url">' + cbT('图片链接') + '</button>'
          + '<button type="button" class="cb-ed-tab" data-tab="library">' + cbT('附件库') + '</button>'
          + '</div>'
          + '<div class="cb-ed-pane" data-pane="upload">'
          + '<div class="cb-ed-drop" data-drop>' + cbT('点击选择图片，或将图片拖拽到此处')
          + '<div class="text-xs text-muted" style="margin-top:6px">' + cbT('支持多选，上传后自动插入') + '</div></div>'
          + '<input type="file" data-file accept="image/*" multiple class="hide">'
          + '</div>'
          + '<div class="cb-ed-pane hide" data-pane="url">'
          + '<div class="cb-ed-field"><label>图片地址</label>'
          + '<input class="cb-input" data-fname="imgurl" placeholder="https://example.com/a.png"></div>'
          + '<div class="cb-ed-field"><label>替代文字（可选）</label>'
          + '<input class="cb-input" data-fname="imgalt" placeholder="图片描述"></div>'
          + '</div>'
          + '<div class="cb-ed-pane hide" data-pane="library">'
          + '<div class="cb-ed-library" data-library><div class="cb-ed-library__tip">加载中…</div></div>'
          + '</div>';
        self.bindTabs(body);
        self.bindLibrary(body.querySelector('[data-library]'), function (url, name) {
          self.insertHTML('<p><img src="' + esc(url) + '" alt="' + esc(name || '图片') + '" style="max-width:100%"></p>');
          dlg.close();
        });
        var file = body.querySelector('[data-file]');
        var drop = body.querySelector('[data-drop]');
        drop.addEventListener('click', function () { file.click(); });
        file.addEventListener('change', function () {
          Array.prototype.forEach.call(file.files, function (f) { self.upload(f); });
          dlg.close();
        });
        ['dragover', 'dragenter'].forEach(function (ev) {
          drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.add('over'); });
        });
        ['dragleave', 'drop'].forEach(function (ev) {
          drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.remove('over'); });
        });
        drop.addEventListener('drop', function (e) {
          e.preventDefault();
          Array.prototype.forEach.call(e.dataTransfer.files, function (f) { self.upload(f); });
          dlg.close();
        });
      },
      onOk: function (v) {
        var u = (v.imgurl || '').trim();
        if (u) {
          self.insertHTML('<p><img src="' + esc(u) + '" alt="' + esc(v.imgalt || '图片') + '" style="max-width:100%"></p>');
        }
      }
    });
  };

  Editor.prototype.attachment = function () {
    var self = this;
    var apiBase = window.CB_API_BASE || ((window.CB_SITE_URL || '') + '/');

    var dlg = this.dialog({
      title: '附件库',
      okText: '关闭',
      width: 640,
      render: function (body) {
        body.innerHTML =
          '<div class="cb-ed-filter">'
          + '<select data-space class="cb-input" style="width:auto">'
          + '<option value="shared">共享空间</option>'
          + '<option value="private">私人空间</option>'
          + '<option value="all">全部空间</option>'
          + '</select>'
          + '<select data-type class="cb-input" style="width:auto">'
          + '<option value="all">全部类型</option>'
          + '<option value="image">仅图片</option>'
          + '</select>'
          + '<div style="flex:1"></div>'
          + '<select data-to class="cb-input" style="width:auto">'
          + '<option value="shared">上传到共享空间</option>'
          + '<option value="private">上传到私人空间</option>'
          + '</select>'
          + '<button type="button" class="cb-btn sm primary" data-up>上传文件</button>'
          + '</div>'
          + '<div class="cb-ed-drop cb-ed-drop--sm" data-drop>' + cbT('点击上传任意格式文件，或拖拽到此处（支持 zip / gz / rar 等压缩包）') + '</div>'
          + '<input type="file" data-file class="hide">'
          + '<div class="cb-ed-files" data-list><div class="cb-ed-library__tip">加载中…</div></div>';

        var list = body.querySelector('[data-list]');
        var spaceSel = body.querySelector('[data-space]');
        var typeSel = body.querySelector('[data-type]');
        var toSp = body.querySelector('[data-to]');

        function load() {
          list.innerHTML = '<div class="cb-ed-library__tip">加载中…</div>';
          // 兼容 query 模式：CB_API_BASE 已含 ?r= 时用 & 追加参数
          var q = 'space=' + encodeURIComponent(spaceSel.value) + '&type=' + encodeURIComponent(typeSel.value);
          fetch(apiBase + 'api/my-files' + (apiBase.indexOf('?') >= 0 ? '&' : '?') + q,
            { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (res) {
              var items = (res && res.data && res.data.items) || [];
              if (!items.length) {
                list.innerHTML = '<div class="cb-ed-library__tip">暂无文件，先上传一个吧</div>';
                return;
              }
              list.innerHTML = '';
              items.forEach(function (it) {
                var row = document.createElement('div');
                row.className = 'cb-ed-file';
                row.innerHTML = '<span class="cb-ed-file__ico">' + (it.is_image ? ico('image') : ico('attachment-file')) + '</span>'
                  + '<span class="cb-ed-file__name" title="' + esc(it.name) + '">' + esc(it.name) + '</span>'
                  + '<span class="cb-ed-file__size">' + fileSize(it.size) + '</span>'
                  + '<button type="button" class="cb-btn sm cb-ed-file__ins">插入</button>';
                row.querySelector('.cb-ed-file__ins').addEventListener('click', function () {
                  if (self.mode === 'markdown') {
                    self.mdInsert(it.is_image
                      ? '![' + (it.name || '图片') + '](' + it.url + ')\n'
                      : '[' + (it.name || '附件') + '](' + (it.download || it.url) + ')\n');
                  } else {
                    self.insertHTML(it.is_image
                      ? '<p><img src="' + esc(it.url) + '" alt="' + esc(it.name) + '" style="max-width:100%"></p>'
                      : '<p><a class="cb-attachment" href="' + esc(it.download) + '" target="_blank" rel="noopener">📎 '
                      + esc(it.name) + '（' + fileSize(it.size) + '）</a></p>');
                  }
                  dlg.close();
                });
                list.appendChild(row);
              });
            })
            .catch(function () { list.innerHTML = '<div class="cb-ed-library__tip">加载失败，请稍后重试</div>'; });
        }

        spaceSel.addEventListener('change', load);
        typeSel.addEventListener('change', load);

        var file = body.querySelector('[data-file]');
        var drop = body.querySelector('[data-drop]');
        function doUpload(files) {
          if (!files || !files.length) { return; }
          Array.prototype.forEach.call(files, function (f) { self.upload(f, toSp.value); });
          setTimeout(function () { dlg.close(); }, 400);
        }
        drop.addEventListener('click', function () { file.click(); });
        body.querySelector('[data-up]').addEventListener('click', function () { file.click(); });
        file.addEventListener('change', function () { doUpload(file.files); });
        ['dragover', 'dragenter'].forEach(function (ev) {
          drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.add('over'); });
        });
        ['dragleave', 'drop'].forEach(function (ev) {
          drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.remove('over'); });
        });
        drop.addEventListener('drop', function (e) { doUpload(e.dataTransfer.files); });

        load();
      },
      onOk: function () { /* 仅关闭 */ }
    });
  };

  Editor.prototype.emoji = function () {
    var self = this;
    var panel = document.createElement('div');
    panel.className = 'cb-emoji-panel';
    panel.style.position = 'absolute';
    EMOJIS.forEach(function (e) {
      var b = document.createElement('button');
      b.type = 'button';
      b.textContent = e;
      b.addEventListener('mousedown', function (ev) {
        ev.preventDefault();
        self.insertHTML(e);
        panel.remove();
      });
      panel.appendChild(b);
    });
    var rect = this.wrap.getBoundingClientRect();
    panel.style.left = rect.left + 'px';
    panel.style.top = (rect.bottom + window.scrollY + 4) + 'px';
    document.body.appendChild(panel);
    setTimeout(function () {
      document.addEventListener('mousedown', function h() {
        panel.remove();
        document.removeEventListener('mousedown', h);
      });
    }, 50);
  };

  /**
   * 上传文件：
   * - 图片 → 插入 <img>
   * - 其他（zip / gz / pdf…）→ 插入下载链接，避免渲染成裂图
   * markdown=true 时插入 Markdown 语法
   */
  Editor.prototype.upload = function (file, space, markdown) {
    var self = this;
    var isMd = markdown === true || this.mode === 'markdown';
    var fd = new FormData();
    fd.append('file', file);
    if (CSRF) fd.append('_csrf', CSRF);
    fd.append('space', space === 'private' ? 'private' : 'shared');
    var tip = document.createElement('span');
    tip.textContent = cbT('上传中…');
    tip.style.marginLeft = '8px';
    this.wrap.querySelector('.cb-editor__footer').appendChild(tip);
    fetch(API + 'api/upload', {
      method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).then(function (r) { return r.json(); }).then(function (res) {
      tip.remove();
      if (!res || !res.ok) {
        alert((res && res.msg) || cbT('上传失败'));
        return;
      }
      var d = res.data || {};
      var size = fileSize(d.size);
      if (d.is_image) {
        if (isMd) {
          self.mdInsert('![' + (d.name || cbT('图片')) + '](' + d.url + ')\n');
        } else {
          self.insertHTML('<p><img src="' + esc(d.url) + '" alt="' + esc(d.name || cbT('图片')) + '"'
            + (d.id ? ' data-upload-id="' + parseInt(d.id, 10) + '"' : '') + ' style="max-width:100%"></p>');
        }
      } else {
        var download = d.download || (d.url || '');
        if (isMd) {
          self.mdInsert('[' + (d.name || cbT('附件')) + '](' + download + ')\n');
        } else {
          // 用纯文本前缀（正文清洗会移除内联 svg，保证编辑区与保存后显示一致）
          self.insertHTML('<p><a class="cb-attachment" href="' + esc(download) + '" target="_blank" rel="noopener"'
            + (d.id ? ' data-attachment="' + parseInt(d.id, 10) + '"' : '') + '>'
            + '📎 ' + esc(d.name || cbT('附件')) + (size ? '（' + size + '）' : '') + '</a></p>');
        }
      }
    }).catch(function () { tip.remove(); alert(cbT('上传失败')); });
  };

  /* ---------------- 自动初始化 ---------------- */
  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-editor]').forEach(function (el) {
      var inputId = el.dataset.input;
      var mode = el.dataset.mode || window.CB_EDITOR_MODE || 'rich';
      var ed = new Editor(el, {
        input: inputId,
        mode: mode,
        placeholder: el.dataset.placeholder || cbT('写点什么吧…'),
        initial: el.dataset.initial || (inputId && document.getElementById(inputId) ? document.getElementById(inputId).value : '')
      });
      el.__cbEditor = ed;
      if (!window.CBEditor) { window.CBEditor = ed; }
      if (inputId) {
        var form = el.closest('form');
        if (form) {
          form.addEventListener('submit', function () { ed.sync(); });
        }
      }
    });
  });

  window.CBEditorClass = Editor;
})();
