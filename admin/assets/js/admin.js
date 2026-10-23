/**
 * ChatBBS 管理后台脚本
 */
(function () {
    'use strict';

    /* 侧栏分组折叠 */
    document.querySelectorAll('[data-toggle-group]').forEach(function (el) {
        el.addEventListener('click', function () {
            el.parentElement.classList.toggle('open');
        });
    });

    /* 全选 */
    document.querySelectorAll('[data-check-all]').forEach(function (box) {
        box.addEventListener('change', function () {
            var form = box.closest('form');
            if (!form) return;
            form.querySelectorAll('input[name="ids[]"]').forEach(function (i) {
                i.checked = box.checked;
            });
        });
    });

    /* 提交确认 */
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            if (!confirm(el.getAttribute('data-confirm'))) {
                e.preventDefault();
            }
        });
    });

    /* 简易富文本（后台编辑器） */
    document.querySelectorAll('[data-editor]').forEach(function (box) {
        var inputName = box.getAttribute('data-input');
        var hidden = document.getElementById(inputName);
        var bar = document.createElement('div');
        bar.className = 'adm-editor-toolbar';
        var btns = [
            ['bold', 'B'], ['italic', 'I'], ['underline', 'U'], ['strikeThrough', 'S'],
            ['insertUnorderedList', '• 列表'], ['insertOrderedList', '1. 列表'],
            ['formatBlock:h3', 'H3'], ['formatBlock:blockquote', '引用'],
            ['formatBlock:pre', '代码'], ['createLink', '链接'], ['removeFormat', '清格式']
        ];
        btns.forEach(function (b) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = b[1];
            btn.addEventListener('click', function () {
                box.focus();
                var cmd = b[0];
                if (cmd.indexOf('formatBlock:') === 0) {
                    document.execCommand('formatBlock', false, '<' + cmd.split(':')[1] + '>');
                } else if (cmd === 'createLink') {
                    var url = prompt('请输入链接地址', 'https://');
                    if (url) document.execCommand('createLink', false, url);
                } else {
                    document.execCommand(cmd, false, null);
                }
                sync();
            });
            bar.appendChild(btn);
        });
        box.parentNode.insertBefore(bar, box);
        box.setAttribute('contenteditable', 'true');
        if (hidden && hidden.value) box.innerHTML = hidden.value;
        box.setAttribute('placeholder', box.getAttribute('data-placeholder') || '');
        function sync() {
            if (hidden) hidden.value = box.innerHTML;
        }
        box.addEventListener('input', sync);
        box.addEventListener('blur', sync);
    });

    /* 删除确认（行内 form） */
    document.querySelectorAll('form.adm-inline-del').forEach(function (f) {
        f.addEventListener('submit', function (e) {
            if (!confirm('确定要执行该操作吗？')) e.preventDefault();
        });
    });

    /* 自动关闭提示 */
    var alerts = document.querySelectorAll('.adm-alert');
    if (alerts.length) {
        setTimeout(function () {
            alerts.forEach(function (a) {
                a.style.transition = 'opacity .4s';
                a.style.opacity = '0';
                setTimeout(function () { a.remove(); }, 400);
            });
        }, 4000);
    }
})();

/* ---- 暗色模式切换（记忆在 localStorage.adm_theme） ---- */
(function () {
    var btn = document.getElementById('adm-theme-toggle');
    if (!btn) return;
    function paint() {
        var dark = document.documentElement.dataset.admTheme === 'dark';
        btn.title = dark ? '切换到浅色模式' : '切换到暗色模式';
        btn.setAttribute('aria-label', btn.title);
    }
    paint();
    btn.addEventListener('click', function () {
        var dark = document.documentElement.dataset.admTheme === 'dark';
        if (dark) {
            delete document.documentElement.dataset.admTheme;
            try { localStorage.setItem('adm_theme', 'light'); } catch (e) {}
        } else {
            document.documentElement.dataset.admTheme = 'dark';
            try { localStorage.setItem('adm_theme', 'dark'); } catch (e) {}
        }
        paint();
    });
})();

/* ---- 板块排序：上移 / 下移 ---- */
(function () {
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-forum-sort]');
        if (!btn || btn.disabled) return;
        e.preventDefault();
        var body = '_csrf=' + encodeURIComponent(window.CB_ADMIN_CSRF || '')
            + '&id=' + encodeURIComponent(btn.dataset.id || '')
            + '&dir=' + encodeURIComponent(btn.dataset.dir || 'up');
        btn.disabled = true;
        fetch(btn.dataset.url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: body
        }).then(function (r) { return r.json(); }).then(function (res) {
            if (res.ok) {
                if (res.msg && res.msg.indexOf('已经') === 0) {
                    alert(res.msg);
                    btn.disabled = false;
                    return;
                }
                location.reload();
                return;
            }
            alert(res.msg || '操作失败');
            btn.disabled = false;
        }).catch(function () {
            alert('网络异常');
            btn.disabled = false;
        });
    });
})();
