<?php $tab = $tab ?? 'profile'; $p = $profile; ?>
<div class="cb-card">
  <div class="cb-card__header"><?= L('账号设置') ?></div>
  <div class="cb-card__body">
    <div class="cb-tabs">
      <a class="<?= $tab === 'profile' ? 'active' : '' ?>" href="<?= h(url('user/settings?tab=profile')) ?>"><?= L('基本资料') ?></a>
      <a class="<?= $tab === 'avatar' ? 'active' : '' ?>" href="<?= h(url('user/settings?tab=avatar')) ?>"><?= L('头像') ?></a>
      <a class="<?= $tab === 'password' ? 'active' : '' ?>" href="<?= h(url('user/settings?tab=password')) ?>"><?= L('修改密码') ?></a>
      <a class="<?= $tab === 'privacy' ? 'active' : '' ?>" href="<?= h(url('user/settings?tab=privacy')) ?>"><?= L('隐私设置') ?></a>
      <a href="<?= h(url('user/' . $p['id'])) ?>"><?= L('我的主页') ?></a>
    </div>

    <?php if ($tab === 'profile'): ?>
      <form method="post" action="<?= h(url('user/settings/profile')) ?>">
        <?= csrf_field() ?>
        <div class="cb-form-item">
          <label><?= L('用户名') ?></label>
          <input class="cb-input" value="<?= h($p['username']) ?>" disabled>
          <div class="cb-form-tip"><?= L('用户名注册后不可修改') ?></div>
        </div>
        <div class="cb-form-item">
          <label for="nickname"><?= L('昵称') ?></label>
          <input class="cb-input" id="nickname" name="nickname" value="<?= h($p['nickname']) ?>" required>
        </div>
        <div class="cb-form-item">
          <label for="signature"><?= L('签名') ?></label>
          <input class="cb-input" id="signature" name="signature" maxlength="100" value="<?= h($p['signature']) ?>">
        </div>
        <div class="cb-form-item">
          <label for="bio"><?= L('个人简介') ?></label>
          <textarea class="cb-textarea" id="bio" name="bio" maxlength="300"><?= h($p['bio'] ?? '') ?></textarea>
        </div>
        <button class="cb-btn primary" type="submit"><?= L('保存资料') ?></button>
      </form>
    <?php endif; ?>

    <?php if ($tab === 'avatar'): ?>
      <div class="flex items-center gap-12">
        <img class="cb-avatar cb-avatar-lg" src="<?= h(avatar_url($p)) ?>" alt="" id="avatar-preview">
        <div>
          <form method="post" action="<?= h(url('user/settings/avatar')) ?>" enctype="multipart/form-data"
                id="avatar-form">
            <?= csrf_field() ?>
            <input type="file" name="avatar" accept="image/*" required
                   onchange="var f=this;var fd=new FormData(f.form);fetch(f.form.action,{method:'POST',body:fd,headers:{'X-Requested-With':'XMLHttpRequest'}}).then(r=>r.json()).then(r=>{if(r.ok){document.getElementById('avatar-preview').src=r.data.url;CB.toast(r.msg,'success');}else{CB.toast(r.msg,'error');}})">
          </form>
          <div class="cb-form-tip">支持 jpg / png / gif / webp，大小不超过 <?= h(config('upload_max_size')) ?>MB</div>
        </div>
      </div>
      <form method="post" action="<?= h(url('user/settings/avatar-url')) ?>" class="cb-avatar-url">
        <?= csrf_field() ?>
        <label for="avatar_url"><?= L('使用图床图片链接') ?></label>
        <div class="cb-avatar-url__row">
          <input class="cb-input" id="avatar_url" name="avatar_url" type="url" maxlength="240"
                 placeholder="https://example.com/avatar.png" required>
          <button class="cb-btn primary" type="submit"><?= L('使用链接') ?></button>
        </div>
        <div class="cb-form-tip"><?= L('直接填写 http / https 图片地址，不占用本站存储空间') ?></div>
      </form>
    <?php endif; ?>

    <?php if ($tab === 'password'): ?>
      <form method="post" action="<?= h(url('user/settings/password')) ?>">
        <?= csrf_field() ?>
        <div class="cb-form-item">
          <label for="old_password"><?= L('当前密码') ?></label>
          <input class="cb-input" id="old_password" name="old_password" type="password" required>
        </div>
        <div class="cb-form-item">
          <label for="new_password"><?= L('新密码') ?></label>
          <input class="cb-input" id="new_password" name="new_password" type="password" required minlength="6">
        </div>
        <div class="cb-form-item">
          <label for="confirm_password"><?= L('确认新密码') ?></label>
          <input class="cb-input" id="confirm_password" name="confirm_password" type="password" required minlength="6">
        </div>
        <button class="cb-btn primary" type="submit"><?= L('修改密码') ?></button>
      </form>
    <?php endif; ?>

    <?php if ($tab === 'privacy'): ?>
      <?php $pv = json_decode((string)($p['privacy'] ?: '{}'), true) ?: []; ?>
      <form method="post" action="<?= h(url('user/settings/profile')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="nickname" value="<?= h($p['nickname']) ?>">
        <input type="hidden" name="signature" value="<?= h($p['signature']) ?>">
        <input type="hidden" name="bio" value="<?= h($p['bio'] ?? '') ?>">
        <div class="cb-switch-row">
          <span><?= L('公开个人主页') ?></span>
          <label class="cb-checkbox"><input type="checkbox" name="privacy_profile" value="1" <?= ($pv['profile'] ?? 1) ? 'checked' : '' ?>> <?= L('允许他人查看') ?></label>
        </div>
        <div class="cb-switch-row">
          <span><?= L('公开我的帖子列表') ?></span>
          <label class="cb-checkbox"><input type="checkbox" name="privacy_thread" value="1" <?= ($pv['thread'] ?? 1) ? 'checked' : '' ?>> <?= L('允许他人查看') ?></label>
        </div>
        <div class="cb-switch-row">
          <span><?= L('接收陌生人消息') ?></span>
          <label class="cb-checkbox"><input type="checkbox" name="privacy_pm" value="1" <?= ($pv['pm'] ?? 1) ? 'checked' : '' ?>> <?= L('允许') ?></label>
        </div>
        <div class="cb-switch-row">
          <span><?= L('允许附近的人发现我') ?>
            <div class="text-xs text-muted"><?= L('开启后，与你同一网段（局域网）的用户可以在聊天页「附近的人」里看到你') ?></div>
          </span>
          <label class="cb-checkbox"><input type="checkbox" name="lan_discover" value="1" <?= !empty($lan_discover) ? 'checked' : '' ?>> <?= L('允许') ?></label>
        </div>
        <input type="hidden" name="guestbook_settings" value="1">
        <div class="cb-switch-row">
          <span><?= L('允许他人在我的主页留言') ?></span>
          <label class="cb-checkbox"><input type="checkbox" name="guestbook_enable" value="1" <?= (int)($p['guestbook_enable'] ?? 1) === 1 ? 'checked' : '' ?>> <?= L('允许') ?></label>
        </div>
        <div class="cb-switch-row">
          <span><?= L('暗色模式偏好') ?></span>
          <label class="cb-checkbox"><input type="checkbox" name="theme_dark" value="1" <?= (int)$p['theme_dark'] ? 'checked' : '' ?>> <?= L('默认暗色') ?></label>
        </div>
        <button class="cb-btn primary mt-16" type="submit"><?= L('保存设置') ?></button>
      </form>
    <?php endif; ?>
  </div>
</div>
