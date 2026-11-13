<?php
$isEdit = $thread !== null;
$load_editor = true;
$currentTags = $current_tags ?? [];
$draft = $draft ?? null;
$tagNames = $isEdit ? implode(',', array_column($currentTags, 'name')) : (string)($draft['tags'] ?? '');
// 之前用 Markdown 保存的主题，编辑时自动回到 Markdown 模式并回填源码
$contentMd = $isEdit ? trim((string)($thread['content_md'] ?? '')) : '';
// 优先按入库时记录的格式还原；老数据没有 content_format 列时，退化为「有源码即 Markdown」
$contentFmt = $isEdit ? (string)($thread['content_format'] ?? '') : '';
$editMode = ($contentFmt === 'markdown' || ($contentFmt === '' && $contentMd !== '')) ? 'markdown' : 'rich';
$draftContent = $isEdit ? '' : (string)($draft['content'] ?? '');
$draftText = $draftContent !== '' ? \App\Services\Content::toText($draftContent) : '';
?>
<div class="cb-card cb-card--editor">
  <div class="cb-card__header"><?= $isEdit ? L('编辑主题') : L('发布主题') ?></div>
  <div class="cb-card__body">
    <?php if (!$isEdit && $draft): ?>
      <div class="cb-alert info" style="margin-bottom:12px">已恢复自动保存的草稿（<?= h(format_date((string)($draft['updated_at'] ?? $draft['created_at'] ?? ''))) ?>），继续编辑或直接发布即可。</div>
    <?php endif; ?>
    <form id="thread-form" class="cb-thread-form" method="post"
          action="<?= h($isEdit ? url('thread/' . $thread['id'] . '/edit') : url('thread/create')) ?>"
          data-thread-id="<?= $isEdit ? (int)$thread['id'] : 0 ?>">
      <?= csrf_field() ?>

      <div class="cb-form-item cb-form-item--compact">
        <select class="cb-select" id="f-forum" name="forum_id" required>
          <option value="" disabled <?= (int)$forum_id === 0 ? 'selected' : '' ?>>请选择你发帖的板块…</option>
          <?php foreach ($forums as $cat): ?>
            <optgroup label="<?= h($cat['name']) ?>">
              <?php foreach ($cat['children'] as $b): ?>
                <?php if (($b['can_post'] ?? false) === false && (int)$b['id'] !== (int)$forum_id) continue; ?>
                <option value="<?= (int)$b['id'] ?>" <?= (int)$b['id'] === (int)$forum_id ? 'selected' : '' ?>>
                  <?= h($b['name']) ?>
                </option>
              <?php endforeach; ?>
            </optgroup>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="cb-form-item cb-form-item--compact">
        <input class="cb-input" id="f-title" name="title" required maxlength="100"
               value="<?= h($thread['title'] ?? ($draft['title'] ?? old('title'))) ?>" placeholder="<?= L('请输入标题，一句话说明你的主题') ?>">
      </div>

      <div class="cb-form-item cb-form-item--editor">
        <div class="flex justify-between items-center" style="margin-bottom:6px">
          <label style="margin:0"><?= L('正文') ?></label>
          <div class="flex gap-8 items-center">
            <button class="cb-btn sm" id="btn-editor-fullscreen" type="button" title="<?= L('全屏编辑') ?>" aria-label="<?= L('全屏编辑') ?>"><?= cb_icon('expand') ?></button>
            <a class="cb-btn sm" id="btn-thread-mode" href="javascript:void(0)"><?= L('高级模式') ?></a>
          </div>
        </div>

        <!-- 简洁模式 -->
        <div id="thread-simple">
          <textarea class="cb-textarea" name="content" id="thread_text" rows="8"
                    placeholder="<?= L('写下正文内容…（Ctrl + Enter 发布）') ?>"><?= h($isEdit ? \App\Services\Content::toText((string)($thread['content'] ?? '')) : $draftText) ?></textarea>
        </div>

        <!-- 高级模式 -->
        <div id="thread-advanced" class="hide">
          <div id="thread-editor" data-editor data-input="content_html" data-mode="<?= h($editMode) ?>"
               data-placeholder="<?= L('支持标题、加粗、颜色、链接、代码、折叠、表格、图片、附件…') ?>"></div>
          <input type="hidden" name="content" id="content_html"
                 value="<?= h($contentMd !== '' ? $contentMd : (string)($thread['content'] ?? $draftContent)) ?>" disabled>
          <input type="hidden" name="content_format" id="content_html_format" value="<?= h($editMode) ?>">
        </div>
      </div>
      <script>
        (function () {
          var btn = document.getElementById('btn-thread-mode');
          var simple = document.getElementById('thread-simple');
          var adv = document.getElementById('thread-advanced');
          var text = document.getElementById('thread_text');
          var html = document.getElementById('content_html');
          if (!btn || !simple || !adv || !text || !html) return;
          // 默认高级模式（宽度与工具条更稳定）；编辑器初始化失败时自动退回简洁模式
          var advanced = true;
          function apply() {
            simple.classList.toggle('hide', advanced);
            adv.classList.toggle('hide', !advanced);
            btn.textContent = advanced ? '简洁模式' : '高级模式';
            var fmt = document.getElementById('content_html_format');
            if (fmt) {
              var ed = (document.getElementById('thread-editor') || {}).__cbEditor;
              fmt.value = advanced ? ((ed && ed.mode) || '<?= h($editMode) ?>') : 'rich';
            }
            if (advanced) {
              if (!html.value && text.value) {
                html.value = text.value.replace(/</g, '&lt;').replace(/\n/g, '<br>');
              }
              html.disabled = false; text.disabled = true;
              if (window.CBEditor) { window.CBEditor.el.innerHTML = html.value; window.CBEditor.sync(); }
            } else {
              text.value = html.value.replace(/<br\s*\/?>/gi, '\n').replace(/<[^>]+>/g, '');
              text.disabled = false; html.disabled = true;
            }
          }
          apply();
          btn.addEventListener('click', function () { advanced = !advanced; apply(); });
          window.addEventListener('load', function () {
            if (advanced && !document.querySelector('#thread-editor .cb-editor')) {
              advanced = false;
              apply();
            }
          });
        })();
      </script>

      <?php
      $tagInitial = trim((string)($tagNames ?: old('tags')));
      $tagValues = $tagInitial === '' ? [] : array_values(array_unique(array_filter(array_map('trim', explode(',', $tagInitial)))));
      $tagOptions = [];
      foreach (($tags ?? []) as $tg) {
          $tagOptions[] = ['name' => (string)$tg['name'], 'color' => (string)($tg['color'] ?? '#4D698E')];
      }
      $canCreateTag = (int)$allow_user_tag === 1 || is_admin();
      ?>
      <div class="cb-form-item cb-form-item--compact">
        <input type="hidden" name="announce" value="<?= (int)($announce ?? 0) ?>">
        <?php if (!empty($announce)): ?>
          <div class="cb-form-tip">当前为「板块公告」发布模式，发布后将置顶显示在本板块。</div>
        <?php endif; ?>
        <input type="hidden" id="f-tags" name="tags" value="<?= h(implode(',', $tagValues)) ?>">
        <div class="cb-tag-picker" data-tag-picker
             data-options="<?= h(json_encode($tagOptions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>"
             data-selected="<?= h(json_encode($tagValues, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>"
             data-max="<?= (int)$max_tags ?>" data-allow="<?= $canCreateTag ? '1' : '0' ?>">
          <div class="cb-tag-picker__chips" data-tag-chips></div>
          <input class="cb-input" id="f-tag-input" type="text" autocomplete="off"
                 placeholder="输入标签，选中建议或按回车创建，最多 <?= (int)$max_tags ?> 个">
          <div class="cb-tag-picker__menu hide" data-tag-menu></div>
        </div>
        <?php if (!$canCreateTag): ?>
          <div class="cb-form-tip"><?= L('仅可使用管理员已创建的标签') ?></div>
        <?php else: ?>
          <div class="cb-form-tip">输入时会显示已有标签；没有合适的就直接按回车创建。</div>
        <?php endif; ?>
      </div>

      <?php if (is_admin()): ?>
        <?php
        $titleColor = (string)($thread['title_color'] ?? '');
        $titlePick = preg_match('/^#[0-9a-fA-F]{6}$/', $titleColor) ? $titleColor : '#52c41a';
        ?>
        <div class="cb-form-item cb-form-item--compact">
          <div class="cb-color-field">
            <input type="color" id="f-title-color-pick" value="<?= h($titlePick) ?>" aria-label="<?= L('选择标题颜色') ?>">
            <input class="cb-input" id="f-title-color" name="title_color" value="<?= h($titleColor) ?>"
                   placeholder="<?= L('留空使用默认颜色，如 #52c41a') ?>">
            <button type="button" class="cb-btn sm" id="f-title-color-reset"><?= L('恢复默认') ?></button>
          </div>
          <div class="cb-form-tip"><?= L('仅管理员可设置，显示在首页、板块列表的标题文字上') ?></div>
        </div>
        <script>
          (function () {
            var pick = document.getElementById('f-title-color-pick');
            var text = document.getElementById('f-title-color');
            var reset = document.getElementById('f-title-color-reset');
            if (!pick || !text || !reset) return;
            pick.addEventListener('input', function () { text.value = pick.value; });
            text.addEventListener('input', function () {
              if (/^#[0-9a-fA-F]{6}$/.test(text.value.trim())) { pick.value = text.value.trim(); }
            });
            reset.addEventListener('click', function () { text.value = ''; text.focus(); });
          })();
        </script>
      <?php endif; ?>

      <div class="cb-form-item">
        <label class="cb-checkbox">
          <input type="checkbox" name="anonymous" value="1" <?= !empty($thread['anonymous']) ? 'checked' : '' ?>> <?= L('匿名发布') ?>
        </label>
      </div>

      <div class="cb-form-item">
        <label><?= L('付费面板') ?></label>
        <input class="cb-input" id="f-paid-price" name="paid_price" type="number" min="0" step="0.01"
               value="<?= h((string)($thread['paid_price'] ?? 0)) ?>" placeholder="价格（<?= h(currency_name()) ?>，0 为免费）" style="max-width:220px">
        <textarea class="cb-input" id="f-paid-content" name="paid_content" rows="4"
                  placeholder="付费后可见的内容：下载链接、提取码、附件说明等"><?= h((string)($thread['paid_content'] ?? '')) ?></textarea>
        <div class="cb-form-tip">发布后会在文章底部显示「下载面板」，读者付费后可见这里的内容。</div>
      </div>

      <div class="cb-form-item cb-form-item--compact">
        <select class="cb-select" id="f-license" name="license">
          <?php $curLicense = (string)($thread['license'] ?? ''); ?>
          <option value=""><?= L('版权许可证（可选）') ?></option>
          <?php foreach (\license_options() as $lk => $lo): ?>
            <option value="<?= h($lk) ?>" <?= $lk === $curLicense ? 'selected' : '' ?>><?= h(L($lo[0])) ?></option>
          <?php endforeach; ?>
        </select>
        <div class="cb-form-tip" id="license-tip"><?= h(L('选择别人可以如何使用你这篇帖子')) ?></div>
        <textarea class="cb-textarea hide" id="f-license-custom" name="license_custom" rows="2"
                  placeholder="<?= L('写上你的自定义版权说明，如：转载需注明出处并私信告知作者') ?>"><?= h((string)($thread['license_custom'] ?? '')) ?></textarea>
      </div>

      <div class="cb-form-actions flex gap-8 items-center">
        <button class="cb-btn primary lg" type="submit"><?= $isEdit ? L('保存修改') : L('发布主题') ?></button>
        <button class="cb-btn" id="btn-save-draft" type="button"><?= L('保存草稿') ?></button>
        <span class="text-xs text-muted" id="draft-tip"></span>
      </div>
    </form>
  </div>
</div>
<script>
/* 版权许可证：选择「自定义」时显示自定义输入框；预设切换时更新提示 */
(function () {
  var sel = document.getElementById('f-license');
  var custom = document.getElementById('f-license-custom');
  var tip = document.getElementById('license-tip');
  if (!sel || !custom) return;
  var tips = <?= json_encode(array_map(static fn($o) => L($o[1] !== '' ? $o[1] : $o[0]), \license_options()), JSON_UNESCAPED_UNICODE) ?>;
  function apply() {
    var v = sel.value;
    custom.classList.toggle('hide', v !== 'custom');
    if (tip && tips[v] !== undefined) tip.textContent = tips[v];
  }
  sel.addEventListener('change', apply);
  apply();
})();
</script>
<script>
/* 标签选择器：建议 + 回车创建 + 标签 chip 显示 */
(function () {
  var box = document.querySelector('[data-tag-picker]');
  if (!box) return;
  var hidden = document.getElementById('f-tags');
  var input = document.getElementById('f-tag-input');
  var chips = box.querySelector('[data-tag-chips]');
  var menu = box.querySelector('[data-tag-menu]');
  if (!hidden || !input || !chips || !menu) return;
  var options = [];
  var selected = [];
  try { options = JSON.parse(box.getAttribute('data-options') || '[]') || []; } catch (e) { options = []; }
  try { selected = JSON.parse(box.getAttribute('data-selected') || '[]') || []; } catch (e) { selected = []; }
  var max = parseInt(box.getAttribute('data-max') || '5', 10) || 5;
  var allow = box.getAttribute('data-allow') === '1';

  function find(name) {
    name = String(name || '').trim();
    for (var i = 0; i < options.length; i++) {
      if (String(options[i].name).toLowerCase() === name.toLowerCase()) return options[i];
    }
    return null;
  }
  function colorOf(name) {
    var o = find(name);
    return o && o.color ? o.color : '#4D698E';
  }
  function sync() {
    hidden.value = selected.join(',');
    hidden.dispatchEvent(new Event('input', { bubbles: true }));
  }
  function drawChips() {
    chips.innerHTML = '';
    selected.forEach(function (name) {
      var chip = document.createElement('span');
      chip.className = 'cb-tag-chip';
      chip.style.setProperty('--tag-color', colorOf(name));
      var label = document.createElement('span');
      label.textContent = name;
      var x = document.createElement('button');
      x.type = 'button';
      x.setAttribute('aria-label', '移除标签 ' + name);
      x.textContent = '×';
      x.addEventListener('click', function () {
        selected = selected.filter(function (v) { return v !== name; });
        sync(); drawChips(); drawMenu();
      });
      chip.appendChild(label);
      chip.appendChild(x);
      chips.appendChild(chip);
    });
    sync();
  }
  function drawMenu() {
    var q = (input.value || '').trim().toLowerCase();
    var list = options.filter(function (o) {
      return selected.indexOf(o.name) < 0 && (!q || String(o.name).toLowerCase().indexOf(q) >= 0);
    }).slice(0, 8);
    menu.innerHTML = '';
    list.forEach(function (o) {
      var b = document.createElement('button');
      b.type = 'button';
      b.className = 'cb-tag-suggest';
      var dot = document.createElement('span');
      dot.className = 'cb-tag-suggest__dot';
      dot.style.background = o.color || '#4D698E';
      var txt = document.createElement('span');
      txt.textContent = o.name;
      b.appendChild(dot); b.appendChild(txt);
      b.addEventListener('mousedown', function (e) { e.preventDefault(); add(o.name); });
      menu.appendChild(b);
    });
    if (q && allow && !find(q)) {
      var create = document.createElement('button');
      create.type = 'button';
      create.className = 'cb-tag-suggest cb-tag-suggest--create';
      create.textContent = '回车创建「' + input.value.trim() + '」';
      create.addEventListener('mousedown', function (e) { e.preventDefault(); add(input.value); });
      menu.appendChild(create);
    }
    if (!menu.children.length) {
      var tip = document.createElement('div');
      tip.className = 'cb-tag-empty';
      tip.textContent = q ? (allow ? '按回车创建这个标签' : '没有匹配的现有标签') : '输入标签名开始搜索';
      menu.appendChild(tip);
    }
    menu.classList.remove('hide');
  }
  function add(name) {
    name = String(name || '').trim().replace(/^#+/, '');
    if (!name) return;
    if (selected.length >= max) { input.value = ''; menu.classList.add('hide'); return; }
    var found = find(name);
    if (!found && !allow) {
      menu.innerHTML = '<div class="cb-tag-empty">只能使用已有标签</div>';
      menu.classList.remove('hide');
      return;
    }
    if (!found) {
      found = { name: name, color: '#4D698E' };
      options.push(found);
    }
    if (selected.indexOf(found.name) < 0) selected.push(found.name);
    input.value = '';
    drawChips();
    menu.classList.add('hide');
  }
  function commit() {
    var raw = (input.value || '').trim();
    if (!raw) return;
    var parts = raw.split(/[,，]/).map(function (x) { return x.trim(); }).filter(Boolean);
    if (parts.length > 1) {
      parts.forEach(add);
    } else {
      add(raw);
    }
    input.value = '';
  }
  input.addEventListener('focus', drawMenu);
  input.addEventListener('input', function () {
    if (input.value.indexOf(',') >= 0 || input.value.indexOf('，') >= 0) {
      // 允许直接粘贴逗号分隔的标签
      var raw = input.value;
      input.value = '';
      raw.split(/[,，]/).forEach(function (x) { add(x); });
      return;
    }
    drawMenu();
  });
  input.addEventListener('keydown', function (e) {
    if (e.key === 'Enter') { e.preventDefault(); commit(); }
    else if (e.key === ',' || e.key === '，') { e.preventDefault(); commit(); }
    else if (e.key === 'Backspace' && !input.value && selected.length) {
      selected.pop(); drawChips(); drawMenu();
    } else if (e.key === 'Escape') {
      menu.classList.add('hide');
    }
  });
  input.addEventListener('blur', function () { setTimeout(function () { menu.classList.add('hide'); }, 160); });
  drawChips();
  drawMenu();
  menu.classList.add('hide');
})();
</script>
<script>
/* 编辑器全屏：隐藏发帖元信息，只保留编辑器 */
(function () {
  var card = document.querySelector('.cb-card--editor');
  var btn = document.getElementById('btn-editor-fullscreen');
  if (!card || !btn) return;
  var exit = document.createElement('button');
  exit.type = 'button';
  exit.className = 'cb-editor-fs-exit';
  exit.title = '<?= L('退出全屏') ?>';
  exit.setAttribute('aria-label', '<?= L('退出全屏') ?>');
  exit.innerHTML = '<?= cb_icon('collapse') ?>';
  card.appendChild(exit);
  function setFull(on) {
    card.classList.toggle('is-fullscreen', on);
    document.body.classList.toggle('cb-editor-fullscreen', on);
    window.dispatchEvent(new Event('resize'));
    btn.title = on ? '<?= L('退出全屏') ?>' : '<?= L('全屏编辑') ?>';
    btn.setAttribute('aria-label', btn.title);
    btn.innerHTML = on ? '<?= cb_icon('collapse') ?>' : '<?= cb_icon('expand') ?>';
    exit.classList.toggle('show', on);
  }
  btn.addEventListener('click', function () { setFull(!card.classList.contains('is-fullscreen')); });
  exit.addEventListener('click', function () { setFull(false); });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && card.classList.contains('is-fullscreen')) { setFull(false); }
  });
})();
</script>
