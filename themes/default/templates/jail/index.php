<div class="cb-card">
  <div class="cb-card__header"><?= L('小黑屋') ?></div>
  <div class="cb-card__body">
    <?php if (empty($bans)): ?>
      <div class="cb-empty" style="padding:24px"><?= L('暂无封禁记录') ?></div>
    <?php else: ?>
      <div class="cb-table-wrap" style="overflow-x:auto">
        <table class="cb-table">
          <thead>
            <tr>
              <th><?= L('用户名') ?></th>
              <th><?= L('行为') ?></th>
              <th><?= L('备注') ?></th>
              <th><?= L('过期时间') ?></th>
              <th><?= L('操作时间') ?></th>
              <th><?= L('操作人') ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($bans as $b): ?>
              <tr>
                <td><?= h((string)$b['username']) ?></td>
                <td><?= $b['action'] === 'mute' ? '<span class="cb-tag">' . L('禁言') . '</span>' : '<span class="cb-tag">' . L('封禁') . '</span>' ?></td>
                <td><?= h((string)$b['note']) ?></td>
                <td><?= $b['expire_at'] ? h(time_ago($b['expire_at']) . ' · ' . format_date($b['expire_at'])) : L('永久') ?></td>
                <td><?= h(format_date($b['created_at'])) ?></td>
                <td><?= h((string)($b['operator_name'] !== '' ? $b['operator_name'] : '-')) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>
