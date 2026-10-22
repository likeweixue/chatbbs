<div class="cb-card">
  <div class="cb-card__header">
    <?= L('我的附件库') ?>
    <div class="flex gap-8">
      <a class="cb-btn sm <?= $space === 'shared' ? 'primary' : '' ?>" href="<?= h(url('user/attachments?space=shared')) ?>"><?= L('共享空间') ?></a>
      <a class="cb-btn sm <?= $space === 'private' ? 'primary' : '' ?>" href="<?= h(url('user/attachments?space=private')) ?>"><?= L('私人空间') ?></a>
    </div>
  </div>
  <div class="cb-card__body">
    <div class="text-xs text-muted" style="margin-bottom:12px">
      <?php if ($space === 'shared'): ?>
        共享空间的文件对所有论坛用户可见；私人空间仅自己与管理员可见，且不直接暴露下载地址。
      <?php else: ?>
        私人空间文件仅自己与管理员可见，下载需通过权限校验地址。
      <?php endif; ?>
      当前：共享 <?= (int)$usage['shared']['cnt'] ?> 个（<?= h(format_size($usage['shared']['size'])) ?>） ·
      私人 <?= (int)$usage['private']['cnt'] ?> 个（<?= h(format_size($usage['private']['size'])) ?>）
    </div>

    <div class="cb-upload-zone" data-upload-zone style="margin-bottom:16px">
      <div class="cb-upload-zone__ico"><?= cb_icon('upload') ?></div>
      <div>点击或拖拽文件到此处上传（最大 <?= h(config('upload_max_size')) ?>MB）</div>
      <form method="post" action="<?= h(url('user/attachments/upload')) ?>" enctype="multipart/form-data" id="att-form" style="display:none">
        <?= csrf_field() ?>
        <input type="hidden" name="space" value="<?= h($space) ?>">
        <input type="file" name="file" onchange="uploadFile(this)">
      </form>
    </div>

    <div class="cb-att-grid">
      <?php foreach ($items as $a): ?>
        <div class="cb-att-card">
          <div class="cb-att-card__thumb">
            <?php if ((int)$a['is_image']): ?>
              <img src="<?= h(\App\Core\Upload::url($a['space'], $a['path'])) ?>" alt="">
            <?php else: ?>
              <span><?= h(strtoupper($a['ext'])) ?></span>
            <?php endif; ?>
          </div>
          <div class="cb-att-card__info">
            <div class="cb-att-card__name" title="<?= h($a['name']) ?>"><?= h($a['name']) ?></div>
            <div class="text-xs text-muted"><?= h(format_size($a['size'])) ?> · <?= (int)$a['downloads'] ?> 次下载</div>
            <div class="flex gap-8" style="margin-top:6px">
              <a class="cb-btn sm" href="<?= h(url('attachment/' . $a['id'] . '/download')) ?>"><?= L('下载') ?></a>
              <form method="post" action="<?= h(url('user/attachments/' . $a['id'] . '/delete')) ?>" style="display:inline">
                <?= csrf_field() ?>
                <button class="cb-btn sm" type="submit" onclick="return confirm('确定删除？')"><?= L('删除') ?></button>
              </form>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <?php if (!$items): ?><div class="cb-empty"><div class="icon"><?= cb_icon('folder') ?></div><p><?= L('暂无文件') ?></p></div><?php endif; ?>
  </div>
</div>

<script>
  function uploadFile(input) {
    if (!input.files || !input.files[0]) return;
    var fd = new FormData(input.form);
    fetch(input.form.action, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (window.CB) CB.toast(res.msg, res.ok ? 'success' : 'error');
        if (res.ok) setTimeout(function () { location.reload(); }, 500);
      });
  }
</script>
