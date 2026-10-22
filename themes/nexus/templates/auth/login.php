<?php
/**
 * 登录表单 —— 视觉参考 Flarum 的 LogInModal（MIT License）
 * 结构：Modal-header / Modal-body(Form-group + FormControl) / Modal-footer
 * 站点原有逻辑（CSRF、back 回跳、记住我、忘记密码、注册入口）全部保留
 */
?>
<form class="Form Form--centered" method="post" action="<?= h(url('login')) ?>">
  <?= csrf_field() ?>
  <input type="hidden" name="back" value="<?= h($back ?? '') ?>">

  <div class="Modal-header">
    <h3 class="App-titleControl App-titleControl--text" id="modal-title"><?= L('登录') ?></h3>
  </div>

  <div class="Modal-body">
    <div class="Form-group">
      <input class="FormControl" id="username" name="username" type="text"
             placeholder="<?= L('用户名或邮箱') ?>" aria-label="<?= L('用户名或邮箱') ?>"
             autocomplete="username" required autofocus value="<?= h(old('username')) ?>">
    </div>

    <div class="Form-group fl-pwd">
      <input class="FormControl" id="password" name="password" type="password"
             placeholder="<?= L('密码') ?>" aria-label="<?= L('密码') ?>"
             autocomplete="current-password" required>
      <button class="fl-pwd__toggle cb-pwd-toggle" type="button" data-toggle-password="password"
              aria-label="<?= L('显示 / 隐藏密码') ?>" title="<?= L('显示 / 隐藏密码') ?>">👁</button>
    </div>

    <div class="Form-group">
      <label class="checkbox">
        <input type="checkbox" name="remember" value="1"> <?= L('记住我的登录状态') ?>
      </label>
    </div>

    <div class="Form-group">
      <button class="Button Button--primary Button--block" type="submit">
        <span class="Button-label"><span class="Button-labelText"><?= L('登录') ?></span></span>
      </button>
    </div>
  </div>

  <div class="Modal-footer">
    <?php if ((int)config('open_forgot', 1)): ?>
      <p class="LogInModal-forgotPassword">
        <a class="Button Button--text Button--link" href="<?= h(url('forgot')) ?>">
          <span class="Button-label"><span class="Button-labelText"><?= L('忘记密码？') ?></span></span>
        </a>
      </p>
    <?php endif; ?>
    <?php if ((int)config('open_register', 1)): ?>
      <p class="LogInModal-signUp"><?= L('还没有账号？') ?>
        <a class="Button Button--text Button--link" href="<?= h(url('register')) ?>">
          <span class="Button-label"><span class="Button-labelText"><?= L('立即注册') ?></span></span>
        </a>
      </p>
    <?php endif; ?>
  </div>
</form>
