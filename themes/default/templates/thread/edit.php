<?php
$isEdit = $thread !== null;
$load_editor = true;
$currentTags = $current_tags ?? [];
$tagNames = implode(',', array_column($currentTags, 'name'));
// 之前用 Markdown 保存的主题，编辑时自动回到 Markdown 模式并回填源码
$contentMd = $isEdit ? trim((string)($thread['content_md'] ?? '')) : '';
// 优先按入库时记录的格式还原；老数据没有 content_format 列时，退化为「有源码即 Markdown」
$contentFmt = $isEdit ? (string)($thread['content_format'] ?? '') : '';
$editMode = ($contentFmt === 'markdown' || ($contentFmt === '' && $contentMd !== '')) ? 'markdown' : 'rich';
?>
<div class="cb-card">
  <div class="cb-card__header"><?= $isEdit ? L('编辑主题') : L('发布主题') ?></div>
  <div class="cb-card__body">
    <form id="thread-form" method="post"
          action="<?= h($isEdit ? url('thread/' . $thread['id'] . '/edit') : url('thread/create')) ?>"
          data-thread-id="<?= $isEdit ? (int)$thread['id'] : 0 ?>">
      <?= csrf_field() ?>

      <div class="cb-form-item">
        <label for="f-forum"><?= L('所属板块') ?></label>
        <select class="cb-select" id="f-forum" name="forum_id" required>
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

      <div class="cb-form-item">
        <label for="f-title"><?= L('标题') ?></label>
        <input class="cb-input" id="f-title" name="title" required maxlength="100"
               value="<?= h($thread['title'] ?? old('title')) ?>" placeholder="<?= L('一句话说明你的主题') ?>">
      </div>

      <div class="cb-form-item">
        <div class="flex justify-between items-center" style="margin-bottom:6px">
          <label style="margin:0"><?= L('正文') ?></label>
          <a class="cb-btn sm" id="btn-thread-mode" href="javascript:void(0)"><?= L('高级模式') ?></a>
        </div>

        <!-- 简洁模式 -->
        <div id="thread-simple">
          <textarea class="cb-textarea" name="content" id="thread_text" rows="8"
                    placeholder="<?= L('写下正文内容…（Ctrl + Enter 发布）') ?>"><?= h($isEdit ? \App\Services\Content::toText((string)($thread['content'] ?? '')) : '') ?></textarea>
        </div>

        <!-- 高级模式 -->
        <div id="thread-advanced" class="hide">
          <div id="thread-editor" data-editor data-input="content_html" data-mode="<?= h($editMode) ?>"
               data-placeholder="<?= L('支持标题、加粗、颜色、链接、代码、折叠、表格、图片、附件…') ?>"></div>
          <input type="hidden" name="content" id="content_html"
                 value="<?= h($contentMd !== '' ? $contentMd : (string)($thread['content'] ?? '')) ?>" disabled>
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

      <div class="cb-form-item">
        <label for="f-tags">标签（逗号分隔，最多 <?= (int)$max_tags ?> 个）</label>
        <input class="cb-input" id="f-tags" name="tags" value="<?= h($tagNames ?: old('tags')) ?>" placeholder="<?= L('例如：讨论,求助') ?>">
        <?php if (!(int)$allow_user_tag && !is_admin()): ?>
          <div class="cb-form-tip"><?= L('仅可使用管理员已创建的标签') ?></div>
        <?php endif; ?>
        <div style="margin-top:6px">
          <?php foreach (($tags ?? []) as $tg): ?>
            <button type="button" class="cb-tag" style="border:none;cursor:pointer"
                    onclick="var i=document.getElementById('f-tags');var v=i.value.split(',').filter(Boolean);if(!v.includes('<?= h($tg['name']) ?>')){v.push('<?= h($tg['name']) ?>');i.value=v.join(',');}">
              + <?= h($tg['name']) ?>
            </button>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="cb-form-item">
        <label class="cb-checkbox">
          <input type="checkbox" name="anonymous" value="1" <?= !empty($thread['anonymous']) ? 'checked' : '' ?>> <?= L('匿名发布') ?>
        </label>
      </div>

      <div class="cb-form-item">
        <label for="f-fee">帖子附件售价（<?= h(currency_name()) ?>，0 为免费）</label>
        <input class="cb-input" id="f-fee" name="attachment_fee" type="number" min="0" step="0.01"
               value="<?= h((string)($thread['attachment_fee'] ?? 0)) ?>">
        <div class="cb-form-tip">设置后，本贴中上传的附件需付费才能下载</div>
      </div>

      <div class="cb-form-item">
        <label for="f-license"><?= L('版权许可证') ?></label>
        <select class="cb-select" id="f-license" name="license">
          <?php $curLicense = (string)($thread['license'] ?? ''); ?>
          <?php foreach (\license_options() as $lk => $lo): ?>
            <option value="<?= h($lk) ?>" <?= $lk === $curLicense ? 'selected' : '' ?>><?= h(L($lo[0])) ?></option>
          <?php endforeach; ?>
        </select>
        <div class="cb-form-tip" id="license-tip"><?= h(L('选择别人可以如何使用你这篇帖子')) ?></div>
        <textarea class="cb-textarea hide" id="f-license-custom" name="license_custom" rows="2"
                  placeholder="<?= L('写上你的自定义版权说明，如：转载需注明出处并私信告知作者') ?>"><?= h((string)($thread['license_custom'] ?? '')) ?></textarea>
      </div>

      <div class="flex gap-8 items-center">
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
