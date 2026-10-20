<h1 class="form-title"><?= L('注册') ?></h1>
<p class="gateway-sub">加入 <?= h((string)config('site_name', 'ChatBBS')) ?></p>
<form method="post" action="<?= h(url('register')) ?>">
  <?= csrf_field() ?>

  <div class="cb-form-item">
    <label for="username"><?= L('用户名') ?></label>
    <div class="cb-input-icon">
      <span class="ico">👤</span>
      <input class="cb-input" id="username" name="username" type="text" required minlength="3" maxlength="20"
             autocomplete="off" value="<?= h(old('username')) ?>">
    </div>
    <div class="cb-form-tip"><?= L('3 - 20 位字母、数字或下划线，注册后不可修改') ?></div>
  </div>

  <div class="cb-form-item">
    <label for="nickname"><?= L('昵称') ?></label>
    <div class="cb-input-icon">
      <span class="ico">🏷️</span>
      <input class="cb-input" id="nickname" name="nickname" type="text" autocomplete="off"
             placeholder="<?= L('留空则与用户名相同') ?>" value="<?= h(old('nickname')) ?>">
    </div>
  </div>

  <div class="cb-form-item">
    <label for="email">电子邮箱</label>
    <div class="cb-input-icon">
      <span class="ico">✉️</span>
      <input class="cb-input" id="email" name="email" type="email" required autocomplete="off" value="<?= h(old('email')) ?>">
    </div>
  </div>

  <div class="cb-form-item">
    <label for="password"><?= L('密码') ?></label>
    <div class="cb-input-wrap cb-input-icon has-toggle">
      <span class="ico">🔒</span>
      <input class="cb-input" id="password" name="password" type="password" required minlength="6" autocomplete="off">
      <button class="cb-pwd-toggle" type="button" data-toggle-password="password" title="显示 / 隐藏密码">👁</button>
    </div>
    <div class="cb-form-tip"><?= L('至少 6 位') ?></div>
  </div>

  <div class="cb-form-item">
    <label for="confirm_password"><?= L('确认密码') ?></label>
    <div class="cb-input-wrap cb-input-icon has-toggle">
      <span class="ico">🔒</span>
      <input class="cb-input" id="confirm_password" name="confirm_password" type="password" required minlength="6" autocomplete="off">
      <button class="cb-pwd-toggle" type="button" data-toggle-password="confirm_password" title="显示 / 隐藏密码">👁</button>
    </div>
  </div>

  <?php if ((int)config('register_captcha', 1)): ?>
    <div class="cb-form-item">
      <label for="captcha"><?= L('验证码') ?></label>
      <div style="display:flex;gap:8px">
        <input class="cb-input" id="captcha" name="captcha" type="text" required autocomplete="off" maxlength="4">
        <img src="<?= h(url('captcha?k=register')) ?>" alt="<?= L('验证码') ?>" style="height:38px;border-radius:6px;cursor:pointer"
             onclick="this.src='<?= h(url('captcha?k=register')) ?>&t='+Date.now()">
      </div>
    </div>
  <?php endif; ?>

  <?php if ((int)config('register_agreement', 0)): ?>
    <div class="cb-form-item">
      <?php $agreeTitle = trim((string)config('register_agreement_title', L('《用户协议》'))) ?: L('《用户协议》'); ?>
      <label class="cb-checkbox cb-agree">
        <input type="checkbox" name="agree_terms" value="1" required <?= old('agree_terms') ? 'checked' : '' ?>>
        <?= L('我已阅读并同意') ?>
        <a href="javascript:void(0)" class="cb-agree__link" data-agree-open><?= h($agreeTitle) ?></a>
      </label>
      <?php if (trim((string)config('register_agreement_text', '')) !== ''): ?>
        <div class="cb-agree__box hide" id="agree-box"><?= (string)config('register_agreement_text', '') ?></div>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <button class="cb-btn primary lg block" type="submit"><?= L('注册') ?></button>

  <div class="gateway-extra"><?= L('已有账号？') ?><a href="<?= h(url('login')) ?>"><?= L('立即登录') ?></a></div>
</form>

<?php if ((int)config('register_agreement', 0)): ?>
<script>
  // 用户协议：点击协议名展开 / 收起全文
  (function () {
    var link = document.querySelector('[data-agree-open]');
    var box = document.getElementById('agree-box');
    if (!link) return;
    link.addEventListener('click', function () {
      if (!box) { return; }
      box.classList.toggle('hide');
    });
  })();
</script>
<?php endif; ?>
