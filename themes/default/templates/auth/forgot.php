<?php
/**
 * 找回密码 —— 与登录 / 注册共用 Flarum 风格弹窗（UI 参考 Flarum，MIT License）
 */
?>
<form class="Form Form--centered" method="post" action="<?= h(url('forgot')) ?>">
  <?= csrf_field() ?>

  <div class="Modal-header">
    <h3 class="App-titleControl App-titleControl--text" id="modal-title"><?= L('找回密码') ?></h3>
  </div>

  <div class="Modal-body">
    <p class="Form-tip" style="margin:0 0 14px"><?= L('输入注册时使用的邮箱，我们会发送重置链接。') ?></p>

    <div class="Form-group">
      <label class="label" for="email"><?= L('邮箱') ?></label>
      <input class="FormControl" id="email" name="email" type="email"
             placeholder="<?= L('邮箱') ?>" aria-label="<?= L('邮箱') ?>"
             required autofocus autocomplete="email">
    </div>

    <div class="Form-group">
      <button class="Button Button--primary Button--block" type="submit">
        <span class="Button-label"><span class="Button-labelText"><?= L('发送重置链接') ?></span></span>
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
