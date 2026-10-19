<?php
/** 仪表盘 */
$stats = $stats ?? [];
$trend = $trend ?? [];
$sys = $sys ?? [];
$maxTrend = 1;
foreach ($trend as $t) {
    $maxTrend = max($maxTrend, (int)$t['threads'] + (int)$t['posts']);
}
?>
<div class="adm-page-head"><h2>全局概览</h2><div class="spacer"></div>
    <a class="adm-btn sm" href="<?= h(admin_url('cache')) ?>">清理缓存</a>
    <a class="adm-btn sm primary" href="<?= h(admin_url('forums')) ?>">板块管理</a>
</div>

<div class="adm-stats">
    <div class="adm-stat"><div class="adm-stat__label">主题总数</div><div class="adm-stat__value"><?= (int)($stats['threads'] ?? 0) ?></div><div class="adm-stat__sub">今日 +<?= (int)($stats['today_threads'] ?? 0) ?></div></div>
    <div class="adm-stat"><div class="adm-stat__label">回复总数</div><div class="adm-stat__value"><?= (int)($stats['posts'] ?? 0) ?></div><div class="adm-stat__sub">今日 +<?= (int)($stats['today_posts'] ?? 0) ?></div></div>
    <div class="adm-stat"><div class="adm-stat__label">注册会员</div><div class="adm-stat__value"><?= (int)($stats['users'] ?? 0) ?></div><div class="adm-stat__sub">今日 +<?= (int)($stats['today_users'] ?? 0) ?></div></div>
    <div class="adm-stat"><div class="adm-stat__label">在线会员</div><div class="adm-stat__value" style="color:#27ae60"><?= (int)($stats['online_users'] ?? 0) ?></div><div class="adm-stat__sub">近 15 分钟活跃</div></div>
    <div class="adm-stat"><div class="adm-stat__label">板块数量</div><div class="adm-stat__value"><?= (int)($stats['forums'] ?? 0) ?></div></div>
    <div class="adm-stat"><div class="adm-stat__label">附件数量</div><div class="adm-stat__value"><?= (int)($stats['attachments'] ?? 0) ?></div></div>
    <div class="adm-stat"><div class="adm-stat__label">待审主题</div><div class="adm-stat__value" style="color:#f2994a"><?= (int)($stats['pending_threads'] ?? 0) ?></div><div class="adm-stat__sub"><a href="<?= h(admin_url('threads?status=0')) ?>">去审核</a></div></div>
    <div class="adm-stat"><div class="adm-stat__label">待处理举报</div><div class="adm-stat__value" style="color:#e34d4d"><?= (int)($stats['pending_reports'] ?? 0) ?></div><div class="adm-stat__sub"><a href="<?= h(admin_url('reports')) ?>">去处理</a></div></div>
</div>

<div class="adm-grid-2">
    <div class="adm-panel">
        <div class="adm-panel-head">近 7 天趋势（主题 + 回复）</div>
        <div class="adm-panel-body">
            <div class="adm-chart-bars">
                <?php foreach ($trend as $t): ?>
                    <?php $v = (int)$t['threads'] + (int)$t['posts']; ?>
                    <div class="bar-item" title="<?= h((string)$t['date']) ?> 主题<?= (int)$t['threads'] ?> / 回复<?= (int)$t['posts'] ?>">
                        <div class="bar-value"><?= $v ?></div>
                        <div class="bar" style="height:<?= (int)max(3, round($v / $maxTrend * 130)) ?>px"></div>
                        <div class="bar-label"><?= h((string)$t['label']) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="adm-panel">
        <div class="adm-panel-head">系统信息</div>
        <div class="adm-panel-body">
            <dl class="adm-kv">
                <dt>ChatBBS 版本</dt><dd><?= h((string)($sys['version'] ?? CB_VERSION)) ?></dd>
                <dt>PHP 版本</dt><dd><?= h((string)($sys['php'] ?? '')) ?></dd>
                <dt>MySQL 版本</dt><dd><?= h((string)($sys['mysql'] ?? '')) ?></dd>
                <dt>Web 服务</dt><dd><?= h((string)($sys['software'] ?? '')) ?></dd>
                <dt>上传限制</dt><dd><?= h((string)($sys['upload_max'] ?? '')) ?></dd>
                <dt>内存限制</dt><dd><?= h((string)($sys['memory_limit'] ?? '')) ?></dd>
                <dt>附件占用</dt><dd><?= h(format_size((float)($sys['upload_size'] ?? 0))) ?></dd>
                <dt>货币流通量</dt><dd><?= h(money((float)($stats['money_total'] ?? 0))) ?></dd>
            </dl>
        </div>
    </div>
</div>

<div class="adm-grid-2">
    <div class="adm-panel">
        <div class="adm-panel-head">最新主题<div class="spacer"></div><a class="adm-btn sm" href="<?= h(admin_url('threads')) ?>">更多</a></div>
        <div class="adm-panel-body tight">
            <table class="adm-table">
                <thead><tr><th>标题</th><th style="width:100px">作者</th><th style="width:80px">状态</th><th style="width:130px">时间</th></tr></thead>
                <tbody>
                <?php foreach (($latest_threads ?? []) as $t): ?>
                    <tr>
                        <td><a href="<?= h(url('thread/' . (int)$t['id'])) ?>" target="_blank"><?= h(str_cut((string)$t['title'], 34)) ?></a></td>
                        <td class="muted"><?= h((string)$t['username']) ?></td>
                        <td><?= (int)$t['status'] === 1 ? '<span class="adm-tag green">正常</span>' : ((int)$t['status'] === 0 ? '<span class="adm-tag orange">待审</span>' : '<span class="adm-tag red">已删</span>') ?></td>
                        <td class="muted"><?= h(format_date($t['created_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!($latest_threads ?? [])): ?><tr><td colspan="4" class="adm-empty">暂无数据</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="adm-panel">
        <div class="adm-panel-head">最新会员<div class="spacer"></div><a class="adm-btn sm" href="<?= h(admin_url('users')) ?>">更多</a></div>
        <div class="adm-panel-body tight">
            <table class="adm-table">
                <thead><tr><th>用户名</th><th>邮箱</th><th style="width:130px">注册时间</th></tr></thead>
                <tbody>
                <?php foreach (($latest_users ?? []) as $u): ?>
                    <tr>
                        <td><a href="<?= h(admin_url('users/edit/' . (int)$u['id'])) ?>"><?= h((string)$u['username']) ?></a></td>
                        <td class="muted"><?= h((string)$u['email']) ?></td>
                        <td class="muted"><?= h(format_date($u['created_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!($latest_users ?? [])): ?><tr><td colspan="3" class="adm-empty">暂无数据</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="adm-panel">
    <div class="adm-panel-head">板块活跃排行</div>
    <div class="adm-panel-body tight">
        <table class="adm-table">
            <thead><tr><th>板块</th><th style="width:120px">主题</th><th style="width:120px">回复</th></tr></thead>
            <tbody>
            <?php foreach (($hot_forums ?? []) as $f): ?>
                <tr>
                    <td><a href="<?= h(admin_url('forums/edit/' . (int)$f['id'])) ?>"><?= h((string)$f['name']) ?></a></td>
                    <td><?= (int)$f['threads'] ?></td>
                    <td><?= (int)$f['posts'] ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!($hot_forums ?? [])): ?><tr><td colspan="3" class="adm-empty">暂无数据</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
