<h1><?= L('重置密码') ?></h1>
<form method="post" action="<?= h(url('reset')) ?>">
  <?= csrf_field() ?>
  <input type="hidden" name="token" value="<?= h((string)($token ?? '')) ?>">
  <div class="cb-form-item">
    <label for="password"><?= L('新密码') ?></label>
    <input class="cb-input" id="password" name="password" type="password" required minlength="6" autofocus>
  </div>
  <div class="cb-form-item">
    <label for="confirm_password"><?= L('确认新密码') ?></label>
    <input class="cb-input" id="confirm_password" name="confirm_password" type="password" required minlength="6">
  </div>
  <button class="cb-btn primary lg block" type="submit"><?= L('重置密码') ?></button>
  <div class="gateway-extra"><a href="<?= h(url('login')) ?>"><?= L('返回登录') ?></a></div>
</form>
