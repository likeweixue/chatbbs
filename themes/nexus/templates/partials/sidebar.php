<?php
/**
 * 侧边栏：按后台「侧边栏管理」配置的顺序渲染模块
 */
$defaultMods = 'about,stats,checkin,calendar,newest_users,newest_threads,tags,links';
$rawMods = trim((string)config('sidebar_modules', ''));
$mods = array_values(array_filter(array_map('trim', explode(',', $rawMods !== '' ? $rawMods : $defaultMods))));

// 侧栏「关于 / 统计」卡片始终展示全站统计：个人主页等页面传入的 $stats 是用户维度，缺少全站字段
// 注意顺序：先算全站统计，再让 $stats 复用它 —— 反过来的话未传 $stats_site 时会重复统计两次
$siteStats = $stats_site ?? \App\Services\Stats::forum();
$stats = $stats ?? $siteStats;
$newUsers = $new_users ?? \App\Models\User::newest(8);
$latest = $latest_threads ?? \App\Models\Thread::latest(8);
$hotTags = $hot_tags ?? \App\Models\Tag::hot(15);
$currentUser = $current_user ?? null;
$month = date('Y-m');
$dayCount = (int)date('t');
$firstDow = (int)date('w', strtotime(date('Y-m-01')));
$firstDow = ($firstDow + 6) % 7; // 周一为一周起点
$checkinDays = $currentUser ? \App\Models\Checkin::month((int)$currentUser['id'], $month) : [];
$calThreads = \App\Services\Stats::calendar($month);
$links = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string)config('friend_links', ''))));
$aboutTitle = trim((string)config('about_title', 'ChatBBS'));
$aboutText = trim((string)config('about_text', ''));
$aboutTeam = trim((string)config('about_team', ''));
?>
<?php
// 发帖 / 编辑页：侧栏按钮改为「提交当前表单」，不再跳转到新建页丢内容
$editingPage = (string)($editing_page ?? '');
?>
<aside class="cb-sidebar">
  <div class="cb-card" style="padding:12px">
    <?php if ($editingPage !== ''): ?>
      <button type="submit" form="thread-form" id="cb-side-publish" class="cb-btn cb-btn-newthread block">
        <?= $editingPage === 'edit' ? L('保存修改') : L('发布主题') ?>
      </button>
    <?php else: ?>
      <a class="cb-btn cb-btn-newthread block" href="<?= h(url('thread/create')) ?>"><?= L('发布主题') ?></a>
    <?php endif; ?>
  </div>

  <?php if (!empty($show_toc)): ?>
    <!-- 目录面板：由 app.js 从楼主正文中提取标题生成 -->
    <div class="cb-card cb-toc" id="cb-toc-card" hidden>
      <div class="cb-card__header">
        <?= L('目录') ?>
        <button class="cb-toc__toggle" id="cb-toc-toggle" type="button" title="<?= L('折叠 / 展开') ?>">−</button>
      </div>
      <div class="cb-card__body">
        <nav class="cb-toc__list" id="cb-toc-list"></nav>
      </div>
    </div>
  <?php endif; ?>

  <?php if (empty($show_toc)): ?>
  <?php foreach ($mods as $mod): ?>
    <?php if ($mod === 'about' && $aboutTitle !== ''): ?>
      <div class="cb-card cb-card--about">
        <div class="cb-card__body" style="text-align:center">
          <div class="about-title"><?= h($aboutTitle) ?></div>
          <?php if ($aboutText !== ''): ?>
            <p class="about-text"><?= h($aboutText) ?></p>
          <?php endif; ?>
          <?php if ($aboutTeam !== ''): ?>
            <div class="about-team">— <?= h($aboutTeam) ?> —</div>
          <?php endif; ?>
        </div>
        <div class="cb-card__footer about-foot">
          <div><b><?= (int)$siteStats['threads'] ?></b><span><?= L('主题') ?></span></div>
          <div><b><?= (int)$siteStats['posts'] ?></b><span><?= L('回复') ?></span></div>
          <div><b><?= (int)($siteStats['users'] ?? 0) ?></b><span><?= L('会员') ?></span></div>
          <div><b><?= (int)($siteStats['today_threads'] ?? 0) ?></b><span><?= L('今日') ?></span></div>
        </div>
      </div>

    <?php elseif ($mod === 'stats'): ?>
      <div class="cb-card">
        <div class="cb-card__header"><?= L('论坛统计') ?></div>
        <div class="cb-card__body">
          <div class="cb-stat-grid">
            <div class="cb-stat-item"><b><?= (int)$siteStats['threads'] ?></b><span><?= L('帖子') ?></span></div>
            <div class="cb-stat-item"><b><?= (int)$siteStats['posts'] ?></b><span><?= L('回复') ?></span></div>
            <div class="cb-stat-item"><b><?= (int)($siteStats['users'] ?? 0) ?></b><span><?= L('会员') ?></span></div>
          </div>
          <div class="text-xs text-muted" style="margin-top:10px">
            今日新增：<?= (int)($siteStats['today_threads'] ?? 0) ?> 帖 / <?= (int)($siteStats['today_posts'] ?? 0) ?> 回复 / <?= (int)($siteStats['today_users'] ?? 0) ?> 会员
          </div>
        </div>
      </div>

    <?php elseif ($mod === 'checkin'): ?>
      <div class="cb-card">
        <div class="cb-card__header"><?= L('每日签到') ?></div>
        <div class="cb-card__body" style="text-align:center">
          <?php if ($currentUser): ?>
            <?php $today = \App\Models\Checkin::today((int)$currentUser['id']); ?>
            <div class="text-sm" style="margin-bottom:10px">
              <?= L('已连续签到') ?> <b style="color:var(--color-primary)"><?= (int)\App\Models\Checkin::continueDays((int)$currentUser['id']) ?></b> <?= L('天') ?>
            </div>
            <a class="cb-btn primary block" href="<?= h(url('checkin')) ?>">
              <?= $today ? L('今日已签到') : L('立即签到') ?>
            </a>
            <div class="text-xs text-muted" style="margin-top:8px">
              当前余额 <?= h(money(\App\Models\Currency::balance((int)$currentUser['id']))) ?>
            </div>
          <?php else: ?>
            <a class="cb-btn primary block" href="<?= h(url('login')) ?>"><?= L('登录后签到') ?></a>
          <?php endif; ?>
        </div>
      </div>

    <?php elseif ($mod === 'calendar'): ?>
      <div class="cb-card">
        <div class="cb-card__header"><?= L('日历') ?></div>
        <div class="cb-card__body">
          <div class="cb-calendar">
            <div class="cb-calendar__head">
              <span class="text-sm"><?= h($month) ?></span>
              <span class="text-xs text-muted"><?= L('标记签到 / 发帖') ?></span>
            </div>
            <div class="cb-calendar__grid">
              <?php foreach ([L('一'), L('二'), L('三'), L('四'), L('五'), L('六'), L('日')] as $w): ?>
                <div class="wd"><?= $w ?></div>
              <?php endforeach; ?>
              <?php for ($i = 0; $i < $firstDow; $i++): ?><div class="d empty"></div><?php endfor; ?>
              <?php for ($d = 1; $d <= $dayCount; $d++):
                $key = str_pad((string)$d, 2, '0', STR_PAD_LEFT);
                $dateKey = $month . '-' . $key;
                $cls = 'd';
                if ($key === date('d')) $cls .= ' today';
                if (isset($checkinDays[$key])) $cls .= $checkinDays[$key]['makeup'] ? ' makeup' : ' checked';
                $cnt = $calThreads[$dateKey] ?? 0;
              ?>
                <div class="<?= $cls ?>" title="<?= h($dateKey) ?><?= $cnt ? ' · ' . $cnt . L(' 帖') : '' ?>"><?= $d ?></div>
              <?php endfor; ?>
            </div>
          </div>
        </div>
      </div>

    <?php elseif ($mod === 'newest_users'): ?>
      <div class="cb-card">
        <div class="cb-card__header"><?= L('最新会员') ?></div>
        <div class="cb-card__body">
          <div class="cb-user-list">
            <?php foreach ($newUsers as $u): ?>
              <a href="<?= h(url('user/' . $u['id'])) ?>" title="<?= h($u['nickname'] ?: $u['username']) ?>">
                <img class="cb-avatar" style="width:38px;height:38px" src="<?= h(avatar_url($u)) ?>" alt="">
              </a>
            <?php endforeach; ?>
            <?php if (!$newUsers): ?><span class="text-sm text-muted"><?= L('暂无') ?></span><?php endif; ?>
          </div>
        </div>
      </div>

    <?php elseif ($mod === 'newest_threads'): ?>
      <div class="cb-card">
        <div class="cb-card__header"><?= L('最新帖子') ?></div>
        <div class="cb-card__body">
          <?php foreach ($latest as $t): ?>
            <div class="cb-list-item">
              <a class="t" href="<?= h(url('thread/' . $t['id'])) ?>"<?php if (!empty($t['title_color'])): ?> style="color:<?= h((string)$t['title_color']) ?>"<?php endif; ?>><?= h(str_cut($t['title'], 22)) ?></a>
              <span class="text-xs text-muted nowrap"><?= h(time_ago($t['created_at'])) ?></span>
            </div>
          <?php endforeach; ?>
          <?php if (!$latest): ?><div class="text-sm text-muted"><?= L('暂无') ?></div><?php endif; ?>
        </div>
      </div>

    <?php elseif ($mod === 'tags'): ?>
      <div class="cb-card">
        <div class="cb-card__header"><?= L('热门标签') ?> <a class="more" href="<?= h(url('tags')) ?>"><?= L('全部') ?></a></div>
        <div class="cb-card__body">
          <?php foreach ($hotTags as $tag): ?>
            <a class="cb-tag" href="<?= h(url('tag/' . $tag['id'])) ?>" style="background:<?= h($tag['color'] . '1a') ?>;color:<?= h($tag['color']) ?>">
              <?= h($tag['name']) ?><?php if ((int)$tag['threads'] > 0): ?> · <?= (int)$tag['threads'] ?><?php endif; ?>
            </a>
          <?php endforeach; ?>
          <?php if (!$hotTags): ?><span class="text-sm text-muted"><?= L('暂无标签') ?></span><?php endif; ?>
        </div>
      </div>

    <?php elseif ($mod === 'links' && $links): ?>
      <div class="cb-card">
        <div class="cb-card__header"><?= L('友情链接') ?></div>
        <div class="cb-card__body">
          <?php foreach ($links as $link):
            $parts = preg_split('/\s*\|\s*|\s*,\s*/', $link, 2);
            $u = $parts[0] ?? ''; $n = $parts[1] ?? $u;
            if (strpos($u, 'http') !== 0) $u = 'https://' . $u;
          ?>
            <div class="cb-list-item"><a class="t" href="<?= h($u) ?>" target="_blank" rel="nofollow noopener"><?= h($n) ?></a></div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
  <?php endforeach; ?>
  <?php endif; ?>
</aside>
