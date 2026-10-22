<?php
/**
 * 重置密码 —— 与登录 / 注册共用 Flarum 风格弹窗（UI 参考 Flarum，MIT License）
 */
?>
<form class="Form Form--centered" method="post" action="<?= h(url('reset')) ?>">
  <?= csrf_field() ?>
  <input type="hidden" name="token" value="<?= h((string)($token ?? '')) ?>">

  <div class="Modal-header">
    <h3 class="App-titleControl App-titleControl--text" id="modal-title"><?= L('重置密码') ?></h3>
  </div>

  <div class="Modal-body">
    <div class="Form-group">
      <label class="label" for="password"><?= L('新密码') ?></label>
      <input class="FormControl" id="password" name="password" type="password"
             placeholder="<?= L('新密码') ?>" aria-label="<?= L('新密码') ?>"
             required minlength="6" autofocus autocomplete="new-password">
    </div>

    <div class="Form-group">
      <label class="label" for="confirm_password"><?= L('确认新密码') ?></label>
      <input class="FormControl" id="confirm_password" name="confirm_password" type="password"
             placeholder="<?= L('确认新密码') ?>" aria-label="<?= L('确认新密码') ?>"
             required minlength="6" autocomplete="new-password">
    </div>

    <div class="Form-group">
      <button class="Button Button--primary Button--block" type="submit">
        <span class="Button-label"><span class="Button-labelText"><?= L('重置密码') ?></span></span>
      </button>
    </div>
  </div>

  <div class="Modal-footer">
    <p>
      <a class="Button Button--text Button--link" href="<?= h(url('login')) ?>">
        <span class="Button-label"><span class="Button-labelText"><?= L('返回登录') ?></span></span>
      </a>
    </p>
  </div>
</form>
