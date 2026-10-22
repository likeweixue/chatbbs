<?php
/**
 * 注册表单 —— 视觉参考 Flarum 的 SignUpModal（MIT License）
 * 保留站点全部注册项：用户名 / 昵称 / 邮箱 / 密码 / 确认密码 / 验证码 / 用户协议
 */
?>
<form class="Form Form--centered" method="post" action="<?= h(url('register')) ?>">
  <?= csrf_field() ?>

  <div class="Modal-header">
    <h3 class="App-titleControl App-titleControl--text" id="modal-title"><?= L('注册') ?></h3>
  </div>

  <div class="Modal-body fl-scroll">

    <div class="Form-group">
      <label class="label" for="username"><?= L('用户名') ?></label>
      <input class="FormControl" id="username" name="username" type="text"
             placeholder="<?= L('用户名') ?>" aria-label="<?= L('用户名') ?>"
             required minlength="3" maxlength="20" autocomplete="username"
             value="<?= h(old('username')) ?>">
      <div class="Form-tip"><?= L('3 - 20 位字母、数字或下划线，注册后不可修改') ?></div>
    </div>

    <div class="Form-group">
      <label class="label" for="nickname"><?= L('昵称') ?></label>
      <input class="FormControl" id="nickname" name="nickname" type="text"
             placeholder="<?= L('留空则与用户名相同') ?>" aria-label="<?= L('昵称') ?>"
             autocomplete="nickname" value="<?= h(old('nickname')) ?>">
    </div>

    <div class="Form-group">
      <label class="label" for="email"><?= L('邮箱') ?></label>
      <input class="FormControl" id="email" name="email" type="email"
             placeholder="<?= L('邮箱') ?>" aria-label="<?= L('邮箱') ?>"
             required autocomplete="email" value="<?= h(old('email')) ?>">
    </div>

    <div class="Form-group">
      <label class="label" for="password"><?= L('密码') ?></label>
      <div class="fl-pwd">
        <input class="FormControl" id="password" name="password" type="password"
               placeholder="<?= L('密码') ?>" aria-label="<?= L('密码') ?>"
               required minlength="6" autocomplete="new-password">
        <button class="fl-pwd__toggle cb-pwd-toggle" type="button" data-toggle-password="password"
                aria-label="<?= L('显示 / 隐藏密码') ?>" title="<?= L('显示 / 隐藏密码') ?>">👁</button>
      </div>
      <div class="Form-tip"><?= L('至少 6 位') ?></div>
    </div>

    <div class="Form-group">
      <label class="label" for="confirm_password"><?= L('确认密码') ?></label>
      <div class="fl-pwd">
        <input class="FormControl" id="confirm_password" name="confirm_password" type="password"
               placeholder="<?= L('确认密码') ?>" aria-label="<?= L('确认密码') ?>"
               required minlength="6" autocomplete="new-password">
        <button class="fl-pwd__toggle cb-pwd-toggle" type="button" data-toggle-password="confirm_password"
                aria-label="<?= L('显示 / 隐藏密码') ?>" title="<?= L('显示 / 隐藏密码') ?>">👁</button>
      </div>
    </div>

    <?php if ((int)config('register_captcha', 1)): ?>
      <div class="Form-group">
        <label class="label" for="captcha"><?= L('验证码') ?></label>
        <div class="fl-captcha">
          <input class="FormControl" id="captcha" name="captcha" type="text"
                 placeholder="<?= L('验证码') ?>" aria-label="<?= L('验证码') ?>"
                 required autocomplete="off" maxlength="4">
          <img src="<?= h(url('captcha?k=register')) ?>" alt="<?= L('验证码') ?>" title="<?= L('点击刷新') ?>"
               onclick="this.src='<?= h(url('captcha?k=register')) ?>&t='+Date.now()">
        </div>
      </div>
    <?php endif; ?>

    <?php if ((int)config('register_agreement', 0)): ?>
      <div class="Form-group">
        <?php $agreeTitle = trim((string)config('register_agreement_title', L('《用户协议》'))) ?: L('《用户协议》'); ?>
        <label class="checkbox">
          <input type="checkbox" name="agree_terms" value="1" required <?= old('agree_terms') ? 'checked' : '' ?>>
          <span><?= L('我已阅读并同意') ?>
            <a href="javascript:void(0)" class="fl-link" data-agree-open><?= h($agreeTitle) ?></a></span>
        </label>
        <?php if (trim((string)config('register_agreement_text', '')) !== ''): ?>
          <div class="fl-agree hide" id="agree-box"><?= (string)config('register_agreement_text', '') ?></div>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <div class="Form-group">
      <button class="Button Button--primary Button--block" type="submit">
        <span class="Button-label"><span class="Button-labelText"><?= L('注册') ?></span></span>
      </button>
    </div>
  </div>

  <div class="Modal-footer">
    <p class="SignUpModal-logIn"><?= L('已有账号？') ?>
      <a class="Button Button--text Button--link" href="<?= h(url('login')) ?>">
        <span class="Button-label"><span class="Button-labelText"><?= L('登录') ?></span></span>
      </a>
    </p>
  </div>
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
