<?php $marks = \App\Models\Thread::marks(); ?>
<div class="cb-card">
  <div class="cb-card__body">
    <form method="get" action="<?= h(url('search')) ?>" class="flex gap-8" style="flex-wrap:wrap">
      <input class="cb-input" name="q" value="<?= h((string)$keyword) ?>" placeholder="<?= L('输入关键词…') ?>" style="flex:1;min-width:200px">
      <select class="cb-select" name="type" style="max-width:120px">
        <option value="thread" <?= $type === 'thread' ? 'selected' : '' ?>><?= L('帖子') ?></option>
        <option value="user" <?= $type === 'user' ? 'selected' : '' ?>><?= L('用户') ?></option>
        <option value="tag" <?= $type === 'tag' ? 'selected' : '' ?>><?= L('标签') ?></option>
      </select>
      <select class="cb-select" name="forum" style="max-width:160px">
        <option value="0"><?= L('全站') ?></option>
        <?php foreach ($forums as $f): ?>
          <option value="<?= (int)$f['id'] ?>" <?= ($forum_id ?? 0) === (int)$f['id'] ? 'selected' : '' ?>><?= h($f['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <button class="cb-btn primary" type="submit"><?= L('搜索') ?></button>
    </form>
    <?php if ($hot_keywords): ?>
      <div style="margin-top:10px">
        <span class="text-xs text-muted"><?= L('热门搜索：') ?></span>
        <?php foreach ($hot_keywords as $hk): ?>
          <a class="cb-tag" href="<?= h(url('search?q=' . urlencode((string)$hk['keyword']))) ?>"><?= h($hk['keyword']) ?></a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php if ($keyword !== ''): ?>
  <div class="cb-card">
    <div class="cb-card__header">搜索结果（<?= (int)$result['total'] ?>）</div>
    <div class="cb-card__body" style="padding:0">
      <?php if ($type === 'user'): ?>
        <?php foreach ($result['items'] as $u): ?>
          <div class="cb-list-item">
            <img class="cb-avatar cb-avatar-sm" src="<?= h(avatar_url(['avatar' => $u['avatar'] ?? ''])) ?>" alt="">
            <a class="t" href="<?= h(url('user/' . $u['id'])) ?>" style="color:var(--color-text)"><?= h($u['nickname'] ?: $u['username']) ?></a>
            <span class="text-xs text-muted"><?= (int)$u['threads'] ?> 帖 / <?= (int)$u['posts'] ?> 回复</span>
          </div>
        <?php endforeach; ?>
      <?php elseif ($type === 'tag'): ?>
        <?php foreach ($result['items'] as $t): ?>
          <div class="cb-list-item">
            <a class="t" href="<?= h(url('tag/' . $t['id'])) ?>"><span class="cb-tag" style="background:<?= h($t['color'] . '1a') ?>;color:<?= h($t['color']) ?>"><?= h($t['name']) ?></span></a>
            <span class="text-xs text-muted"><?= (int)$t['threads'] ?> 个主题</span>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <?= \App\Core\View::partial('partials/thread_list', ['threads' => $result['items'], 'marks' => $marks]) ?>
      <?php endif; ?>
    </div>
  </div>
  <?= $pager ?? '' ?>
<?php endif; ?>
