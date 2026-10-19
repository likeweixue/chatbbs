<h1><?= L('找回密码') ?></h1>
<p class="text-sm text-muted" style="margin:-10px 0 20px"><?= L('输入注册时使用的邮箱，我们会发送重置链接。') ?></p>
<form method="post" action="<?= h(url('forgot')) ?>">
  <?= csrf_field() ?>
  <div class="cb-form-item">
    <label for="email">电子邮箱</label>
    <input class="cb-input" id="email" name="email" type="email" required autofocus autocomplete="off">
  </div>
  <button class="cb-btn primary lg block" type="submit"><?= L('发送重置链接') ?></button>
  <div class="gateway-extra"><a href="<?= h(url('login')) ?>"><?= L('返回登录') ?></a></div>
</form>
