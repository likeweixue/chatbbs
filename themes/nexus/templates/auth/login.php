<h1 class="form-title"><?= L('登录') ?></h1>
<p class="gateway-sub"><?= h((string)config('site_name', 'ChatBBS')) ?> — 欢迎回来</p>
<form method="post" action="<?= h(url('login')) ?>">
  <?= csrf_field() ?>
  <input type="hidden" name="back" value="<?= h($back ?? '') ?>">

  <div class="cb-form-item">
    <label for="username"><?= L('账号') ?></label>
    <div class="cb-input-icon">
      <span class="ico">👤</span>
      <input class="cb-input" id="username" name="username" type="text" autocomplete="off" required autofocus
             placeholder="<?= L('用户名或邮箱') ?>" value="<?= h(old('username')) ?>">
    </div>
  </div>

  <div class="cb-form-item">
    <div style="display:flex;justify-content:space-between;align-items:baseline">
      <label for="password"><?= L('密码') ?></label>
      <?php if ((int)config('open_forgot', 1)): ?>
        <a class="text-xs" href="<?= h(url('forgot')) ?>"><?= L('忘记密码？') ?></a>
      <?php endif; ?>
    </div>
    <div class="cb-input-wrap cb-input-icon has-toggle">
      <span class="ico">🔒</span>
      <input class="cb-input" id="password" name="password" type="password" autocomplete="off" required>
      <button class="cb-pwd-toggle" type="button" data-toggle-password="password" title="显示 / 隐藏密码">👁</button>
    </div>
  </div>

  <div class="cb-form-item">
    <label class="cb-checkbox">
      <input type="checkbox" name="remember" value="1"> <?= L('保持登录会话') ?>
    </label>
  </div>

  <button class="cb-btn primary lg block" type="submit"><?= L('登录') ?></button>

  <?php if ((int)config('open_register', 1)): ?>
    <div class="gateway-extra"><?= L('还没有账号？') ?><a href="<?= h(url('register')) ?>"><?= L('立即注册') ?></a></div>
  <?php endif; ?>
</form>
