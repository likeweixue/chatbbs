<?php
/**
 * ChatBBS 安装引导
 */

declare(strict_types=1);

define('CB_INSTALLING', true);
define('CB_DEBUG', true);
require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;

if (PHP_SAPI === 'cli') {
    exit("请在浏览器中运行安装程序\n");
}

// 会话已由 app/bootstrap.php 统一启动（chatbbs_session），
// 会话激活后不能再调用 session_name()，此处仅做兜底启动。
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}
if (!isset($_SESSION['inst']) || !is_array($_SESSION['inst'])) {
    $_SESSION['inst'] = [];
}

$step = (int)($_GET['step'] ?? 1);
if ($step < 1) {
    $step = 1;
}
$errors = [];
$success = '';

$lockFile = CB_DATA . '/install.lock';
if (is_file($lockFile) && $step !== 5) {
    render_install_page('已经安装完成', '<div class="notice notice-success">ChatBBS 已经安装完成。如需重新安装，请先删除 <code>data/install.lock</code> 文件。</div>
    <div class="actions"><a class="btn primary" href="' . guess_site_url() . '">进入站点</a></div>');
    exit;
}

/**
 * 环境检测
 */
function env_check(): array
{
    $items = [];
    $items[] = [
        'name' => 'PHP 版本',
        'require' => '>= 8.0',
        'current' => PHP_VERSION,
        'ok' => version_compare(PHP_VERSION, '8.0.0', '>='),
        'must' => true,
    ];
    $items[] = [
        'name' => 'PDO MySQL 扩展',
        'require' => '必须启用',
        'current' => extension_loaded('pdo_mysql') ? '已启用' : '未启用',
        'ok' => extension_loaded('pdo_mysql'),
        'must' => true,
    ];
    $items[] = [
        'name' => 'mbstring 扩展',
        'require' => '建议启用',
        'current' => extension_loaded('mbstring') ? '已启用' : '未启用',
        'ok' => extension_loaded('mbstring'),
        'must' => false,
    ];
    $items[] = [
        'name' => 'GD 扩展（验证码）',
        'require' => '建议启用',
        'current' => extension_loaded('gd') ? '已启用' : '未启用',
        'ok' => extension_loaded('gd'),
        'must' => false,
    ];
    $items[] = [
        'name' => 'curl 扩展',
        'require' => '建议启用',
        'current' => extension_loaded('curl') ? '已启用' : '未启用',
        'ok' => extension_loaded('curl'),
        'must' => false,
    ];
    $items[] = [
        'name' => '配置文件目录 config/',
        'require' => '可写',
        'current' => is_writable(CB_CONFIG) ? '可写' : '不可写',
        'ok' => is_writable(CB_CONFIG),
        'must' => true,
    ];
    $items[] = [
        'name' => '数据目录 data/',
        'require' => '可写',
        'current' => is_writable(CB_DATA) ? '可写' : '不可写',
        'ok' => is_writable(CB_DATA),
        'must' => true,
    ];
    $dirs = ['data/cache', 'data/logs', 'data/backups', 'data/uploads', 'data/uploads/shared', 'data/uploads/private', 'data/uploads/avatars', 'data/uploads/covers', 'data/uploads/temp'];
    foreach ($dirs as $d) {
        $full = CB_ROOT . '/' . $d;
        if (!is_dir($full)) {
            @mkdir($full, 0755, true);
        }
        $items[] = [
            'name' => $d,
            'require' => '可写',
            'current' => is_dir($full) && is_writable($full) ? '可写' : '不可写',
            'ok' => is_dir($full) && is_writable($full),
            'must' => true,
        ];
    }
    return $items;
}

function guess_site_url(): string
{
    $scheme = is_https() ? 'https' : 'http';
    $host = (string)($_SERVER['HTTP_HOST'] ?? 'localhost');
    $script = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
    $base = str_replace('/install/index.php', '', $script);
    return $scheme . '://' . $host . $base;
}

function write_config_file(string $file, array $data): bool
{
    $content = "<?php\n// ChatBBS 配置文件，由安装程序自动生成\nreturn " . var_export($data, true) . ";\n";
    return @file_put_contents($file, $content, LOCK_EX) !== false;
}

/**
 * 处理表单提交
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = (string)($_POST['act'] ?? '');

    if ($act === 'env') {
        $items = env_check();
        $blocked = array_filter($items, fn($i) => $i['must'] && !$i['ok']);
        if ($blocked) {
            $errors[] = '存在不满足的安装条件，请先修复后再继续。';
        } else {
            $_SESSION['inst']['env'] = true;
            header('Location: index.php?step=3');
            exit;
        }
    }

    if ($act === 'db') {
        $host = trim((string)($_POST['db_host'] ?? '127.0.0.1'));
        $port = trim((string)($_POST['db_port'] ?? '3306'));
        $name = trim((string)($_POST['db_name'] ?? ''));
        $user = trim((string)($_POST['db_user'] ?? ''));
        $pass = (string)($_POST['db_pass'] ?? '');
        $prefix = trim((string)($_POST['db_prefix'] ?? 'cb_'));
        if (!$name || !$user) {
            $errors[] = '请填写数据库名和用户名';
        }
        if ($prefix !== '' && !preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $prefix)) {
            $errors[] = '表前缀不合法';
        }
        if (!$errors) {
            try {
                $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
                $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$name}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
                $pdo->exec("USE `{$name}`");
                $_SESSION['inst']['db'] = [
                    'host' => $host, 'port' => $port, 'database' => $name,
                    'username' => $user, 'password' => $pass, 'prefix' => $prefix, 'charset' => 'utf8mb4',
                ];
                header('Location: index.php?step=4');
                exit;
            } catch (Throwable $e) {
                $errors[] = '数据库连接失败：' . $e->getMessage();
            }
        }
    }

    if ($act === 'admin') {
        $db = $_SESSION['inst']['db'] ?? null;
        if (!$db) {
            header('Location: index.php?step=3');
            exit;
        }
        $siteName = trim((string)($_POST['site_name'] ?? 'ChatBBS'));
        $siteUrl = trim((string)($_POST['site_url'] ?? guess_site_url()));
        $username = trim((string)($_POST['username'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $confirm = (string)($_POST['confirm_password'] ?? '');

        if (mb_strlen($username) < 3 || mb_strlen($username) > 20) {
            $errors[] = '管理员账号长度需在 3 - 20 个字符之间';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = '管理员邮箱格式不正确';
        }
        if (strlen($password) < 6) {
            $errors[] = '管理员密码至少 6 位';
        }
        if ($password !== $confirm) {
            $errors[] = '两次输入的密码不一致';
        }

        if (!$errors) {
            try {
                Database::init($db);
                $pdo = Database::pdo();

                // 建表
                foreach (['schema.sql', 'seed.sql'] as $file) {
                    $sql = file_get_contents(__DIR__ . '/' . $file);
                    $sql = str_replace('__PREFIX__', $db['prefix'], $sql);
                    $sql = preg_replace('/^--.*$/m', '', $sql);
                    foreach (array_filter(array_map('trim', explode(';', (string)$sql))) as $statement) {
                        if ($statement === '') {
                            continue;
                        }
                        $pdo->exec($statement);
                    }
                }

                // 清理并写入管理员
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $pdo->exec("DELETE FROM `{$db['prefix']}users`");
                Database::insert('users', [
                    'username' => $username,
                    'nickname' => $username,
                    'email' => $email,
                    'password' => $hash,
                    'group_id' => 1,
                    'is_admin' => 1,
                    'status' => 1,
                    'email_verified' => 1,
                    'level_id' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'last_active_at' => date('Y-m-d H:i:s'),
                ]);

                // 站点设置
                $seedSettings = [
                    'site_name' => $siteName,
                    'site_url' => rtrim($siteUrl, '/'),
                    'site_description' => '简洁轻量的现代化社区论坛系统',
                    'theme' => 'default',
                    'language' => 'zh-CN',
                ];
                foreach ($seedSettings as $k => $v) {
                    Database::insert('settings', ['key' => $k, 'value' => $v, 'group' => 'base']);
                }

                // 配置文件
                write_config_file(CB_CONFIG . '/database.php', $db);
                write_config_file(CB_CONFIG . '/site.php', [
                    'site_name' => $siteName,
                    'site_url' => rtrim($siteUrl, '/'),
                    'theme' => 'default',
                    'language' => 'zh-CN',
                    'debug' => false,
                ]);
                if (!is_file(CB_CONFIG . '/app.php')) {
                    write_config_file(CB_CONFIG . '/app.php', [
                        'debug' => false,
                        'timezone' => 'Asia/Shanghai',
                        'cache_time' => 300,
                    ]);
                }
                if (!is_file(CB_CONFIG . '/mail.php')) {
                    write_config_file(CB_CONFIG . '/mail.php', [
                        'host' => '', 'port' => 465, 'user' => '', 'pass' => '', 'ssl' => 1, 'from' => '', 'from_name' => $siteName,
                    ]);
                }

                // 上传目录安全
                foreach (['shared', 'private', 'avatars', 'covers', 'temp'] as $sp) {
                    $dir = CB_UPLOAD . '/' . $sp;
                    if (!is_dir($dir)) {
                        @mkdir($dir, 0755, true);
                    }
                    @file_put_contents($dir . '/.htaccess', "# ChatBBS\nphp_flag engine off\nOptions -Indexes\n<FilesMatch \"\\.(php|php3|php4|php5|php7|phtml|phar|pht)$\">\nDeny from all\n</FilesMatch>\n");
                }

                // 安装锁
                @file_put_contents($lockFile, date('Y-m-d H:i:s'));

                $_SESSION['inst']['done'] = [
                    'site_url' => rtrim($siteUrl, '/'),
                    'username' => $username,
                ];
                header('Location: index.php?step=5');
                exit;
            } catch (Throwable $e) {
                $errors[] = '安装失败：' . $e->getMessage();
            }
        }
    }
}

/**
 * 页面渲染
 */
function render_install_page(string $title, string $body, int $step = 0, array $errors = []): void
{
    $steps = [
        1 => '欢迎使用',
        2 => '环境检测',
        3 => '设置数据库',
        4 => '设置管理员',
        5 => '安装完成',
    ];
    $stepper = '';
    if ($step > 0) {
        $stepper .= '<ol class="steps">';
        foreach ($steps as $n => $label) {
            $cls = $n < $step ? 'done' : ($n === $step ? 'active' : '');
            $stepper .= '<li class="' . $cls . '"><span class="dot">' . ($n < $step ? '✓' : $n) . '</span><span>' . $label . '</span></li>';
        }
        $stepper .= '</ol>';
    }
    $errHtml = '';
    if ($errors) {
        $errHtml = '<div class="notice notice-error"><ul><li>' . implode('</li><li>', array_map('htmlspecialchars', $errors)) . '</li></ul></div>';
    }
    $logo = '<svg width="180" height="48" viewBox="0 0 200 48" xmlns="http://www.w3.org/2000/svg">
      <rect x="0" y="6" width="36" height="36" rx="10" fill="#0b87fd"/>
      <path d="M11 17h16M11 24h16M11 31h10" stroke="#fff" stroke-width="3" stroke-linecap="round"/>
      <text x="46" y="32" font-size="22" font-weight="700" fill="#1f2329" font-family="sans-serif">ChatBBS</text>
    </svg>';
    echo '<!DOCTYPE html><html lang="zh-CN"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>' . htmlspecialchars($title) . ' - ChatBBS 安装</title>
<style>' . install_css() . '</style>
</head><body class="gateway-page">
<div class="gateway-wrapper">
  <div class="halo-logo">' . $logo . '</div>
  <div class="halo-form-wrapper">
    <h1 class="form-title">' . htmlspecialchars($title) . '</h1>
    ' . $stepper . '
    ' . $errHtml . '
    ' . $body . '
  </div>
  <div class="install-footer">ChatBBS v' . CB_VERSION . ' · PHP + MySQL 社区论坛系统</div>
</div>
</body></html>';
    exit;
}

function install_css(): string
{
    return <<<'CSS'
:root{
  --color-primary:#0b87fd; --color-text:#1f2329; --color-text-sub:#4e5969;
  --color-border:#e5e6eb; --color-bg:#f7f8fa; --rounded-lg:8px;
}
*{box-sizing:border-box}
body.gateway-page{
  margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;
  background:linear-gradient(180deg,#f2f6fc 0%,#f7f8fa 100%);
  font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"PingFang SC","Microsoft YaHei",sans-serif;
  color:var(--color-text);-webkit-font-smoothing:antialiased;
}
.gateway-wrapper{width:100%;max-width:560px;padding:32px 16px}
.halo-logo{display:flex;justify-content:center;margin-bottom:24px}
.halo-form-wrapper{background:#fff;border-radius:14px;padding:32px;box-shadow:0 8px 32px rgba(20,30,50,.08);border:1px solid #eef0f4}
.form-title{margin:0 0 20px;font-size:22px;font-weight:600;text-align:center}
.steps{list-style:none;display:flex;padding:0;margin:0 0 24px;gap:4px}
.steps li{flex:1;text-align:center;font-size:12px;color:#a9aeb8;display:flex;flex-direction:column;align-items:center;gap:6px}
.steps li .dot{width:26px;height:26px;line-height:24px;border-radius:50%;border:1px solid var(--color-border);background:#fff;font-size:12px}
.steps li.active{color:var(--color-primary)}
.steps li.active .dot{border-color:var(--color-primary);background:var(--color-primary);color:#fff;font-weight:600}
.steps li.done{color:#52c41a}
.steps li.done .dot{border-color:#52c41a;background:#52c41a;color:#fff}
.form-item{margin-bottom:16px}
.form-item label{display:block;font-size:13px;color:var(--color-text-sub);margin-bottom:6px}
.form-item .form-input input,.form-item .form-input select{
  width:100%;height:40px;padding:0 12px;border:1px solid var(--color-border);border-radius:var(--rounded-lg);
  font-size:14px;outline:none;transition:border-color .15s;background:#fff;color:var(--color-text);
}
.form-item .form-input input:focus{border-color:var(--color-primary)}
.form-item .tip{font-size:12px;color:#a9aeb8;margin-top:4px}
button,.btn{
  display:inline-flex;align-items:center;justify-content:center;height:40px;padding:0 20px;border:none;
  border-radius:var(--rounded-lg);background:var(--color-primary);color:#fff;font-size:14px;cursor:pointer;
  text-decoration:none;transition:opacity .15s;width:100%;
}
button:hover{opacity:.9}
.btn.plain{background:#f2f3f5;color:var(--color-text)}
.actions{display:flex;gap:12px;margin-top:8px}
.notice{border-radius:var(--rounded-lg);padding:12px 16px;font-size:13px;margin-bottom:16px;line-height:1.7}
.notice-error{background:#fff1f0;border:1px solid #ffccc7;color:#cf1322}
.notice-error ul{margin:0;padding-left:18px}
.notice-success{background:#f6ffed;border:1px solid #b7eb8f;color:#389e0d}
.env-table{width:100%;border-collapse:collapse;font-size:13px;margin-bottom:16px}
.env-table th,.env-table td{border-bottom:1px solid var(--color-border);padding:10px 8px;text-align:left}
.env-table th{color:var(--color-text-sub);font-weight:500;background:#fafbfc}
.env-table td:last-child{text-align:right}
.tag{display:inline-block;padding:2px 8px;border-radius:10px;font-size:12px}
.tag.ok{background:#f6ffed;color:#389e0d}
.tag.no{background:#fff1f0;color:#cf1322}
.license{background:#fafbfc;border:1px solid var(--color-border);border-radius:var(--rounded-lg);padding:16px;
  font-size:13px;line-height:1.9;color:var(--color-text-sub);max-height:200px;overflow:auto;margin-bottom:16px}
.form-item-compact{display:flex;align-items:center;gap:8px;font-size:13px;color:var(--color-text-sub);margin-bottom:16px}
.form-item-compact input{width:16px;height:16px}
.install-footer{text-align:center;color:#a9aeb8;font-size:12px;margin-top:20px}
.result-list{list-style:none;padding:0;margin:0 0 16px;font-size:13px}
.result-list li{padding:8px 0;border-bottom:1px dashed var(--color-border);display:flex;justify-content:space-between}
code{background:#f2f3f5;padding:1px 6px;border-radius:4px;font-size:12px}
CSS;
}

// ---------------- 各步骤 ----------------
switch ($step) {
    case 1:
        $body = '<div class="license">
        <p><strong>ChatBBS 开源许可协议</strong></p>
        <p>1. 本软件为社区论坛系统，允许个人与企业在遵守本协议的前提下免费使用、修改与分发。</p>
        <p>2. 未经书面授权，不得删除或修改本软件中的版权声明与署名信息。</p>
        <p>3. 本软件按"原样"提供，作者不对使用过程中产生的任何直接或间接损失承担责任。</p>
        <p>4. 使用本软件搭建的站点，其内容与行为由站点运营者自行负责，请遵守所在地区的法律法规。</p>
        <p>5. 禁止利用本软件从事任何违法违规活动。</p>
        </div>
        <form method="post" action="index.php?step=2">
          <div class="form-item-compact"><input type="checkbox" id="agree" name="agree" checked><label for="agree">我已阅读并同意上述协议</label></div>
          <button type="submit">开始安装</button>
        </form>';
        render_install_page('欢迎使用 ChatBBS', $body, 1, $errors);
        break;

    case 2:
        $items = env_check();
        $rows = '';
        $allOk = true;
        foreach ($items as $i) {
            if ($i['must'] && !$i['ok']) {
                $allOk = false;
            }
            $rows .= '<tr><td>' . htmlspecialchars($i['name']) . '</td><td>' . htmlspecialchars($i['require'])
                . '</td><td>' . htmlspecialchars($i['current'])
                . '</td><td><span class="tag ' . ($i['ok'] ? 'ok' : 'no') . '">' . ($i['ok'] ? '正常' : '异常') . '</span></td></tr>';
        }
        $body = '<table class="env-table"><thead><tr><th>检查项</th><th>要求</th><th>当前</th><th>状态</th></tr></thead><tbody>' . $rows . '</tbody></table>';
        if (!$allOk) {
            $body .= '<div class="notice notice-error">存在必须满足的条件未通过，请修复后重新检测。</div>';
        }
        $body .= '<form method="post" action="index.php?step=2">
          <input type="hidden" name="act" value="env">
          <div class="actions"><a class="btn plain" href="index.php?step=1">上一步</a><button type="submit"' . ($allOk ? '' : ' disabled') . '>下一步</button></div>
        </form>';
        render_install_page('环境检测', $body, 2, $errors);
        break;

    case 3:
        $db = $_SESSION['inst']['db'] ?? [];
        $v = fn($k, $d = '') => htmlspecialchars((string)($db[$k] ?? $d));
        $body = '<form method="post" action="index.php?step=3">
        <input type="hidden" name="act" value="db">
        <div class="form-item"><label for="db_host">数据库服务器</label><div class="form-input">
          <input id="db_host" name="db_host" value="' . $v('host', '127.0.0.1') . '" required>
        </div><div class="tip">一般为 localhost / 127.0.0.1</div></div>
        <div class="form-item"><label for="db_port">数据库端口</label><div class="form-input">
          <input id="db_port" name="db_port" value="' . $v('port', '3306') . '" required></div></div>
        <div class="form-item"><label for="db_name">数据库名</label><div class="form-input">
          <input id="db_name" name="db_name" value="' . $v('database', 'chatbbs') . '" required></div><div class="tip">不存在时会自动创建</div></div>
        <div class="form-item"><label for="db_user">数据库用户名</label><div class="form-input">
          <input id="db_user" name="db_user" value="' . $v('username', 'root') . '" required></div></div>
        <div class="form-item"><label for="db_pass">数据库密码</label><div class="form-input">
          <input id="db_pass" name="db_pass" type="password" value="' . $v('password') . '"></div></div>
        <div class="form-item"><label for="db_prefix">数据表前缀</label><div class="form-input">
          <input id="db_prefix" name="db_prefix" value="' . $v('prefix', 'cb_') . '"></div></div>
        <div class="actions"><a class="btn plain" href="index.php?step=2">上一步</a><button type="submit">测试并继续</button></div>
        </form>';
        render_install_page('设置数据库', $body, 3, $errors);
        break;

    case 4:
        $body = '<form method="post" action="index.php?step=4">
        <input type="hidden" name="act" value="admin">
        <div class="form-item"><label for="site_name">站点名称</label><div class="form-input">
          <input id="site_name" name="site_name" value="ChatBBS" required></div></div>
        <div class="form-item"><label for="site_url">外部访问地址</label><div class="form-input">
          <input id="site_url" name="site_url" value="' . htmlspecialchars(guess_site_url()) . '" required></div></div>
        <div class="form-item"><label for="username">管理员账号</label><div class="form-input">
          <input id="username" name="username" required minlength="3" maxlength="20"></div></div>
        <div class="form-item"><label for="email">管理员邮箱</label><div class="form-input">
          <input id="email" name="email" type="email" required></div></div>
        <div class="form-item"><label for="password">管理员密码</label><div class="form-input">
          <input id="password" name="password" type="password" required minlength="6"></div></div>
        <div class="form-item"><label for="confirm_password">确认密码</label><div class="form-input">
          <input id="confirm_password" name="confirm_password" type="password" required minlength="6"></div></div>
        <div class="actions"><a class="btn plain" href="index.php?step=3">上一步</a><button type="submit">开始安装</button></div>
        </form>';
        render_install_page('设置管理员账号', $body, 4, $errors);
        break;

    case 5:
        $done = $_SESSION['inst']['done'] ?? null;
        if (!$done) {
            header('Location: index.php?step=1');
            exit;
        }
        $body = '<div class="notice notice-success">恭喜！ChatBBS 安装完成。</div>
        <ul class="result-list">
          <li><span>站点地址</span><span>' . htmlspecialchars((string)$done['site_url']) . '</span></li>
          <li><span>管理员账号</span><span>' . htmlspecialchars((string)$done['username']) . '</span></li>
          <li><span>后台入口</span><span>' . htmlspecialchars((string)$done['site_url']) . '/admin</span></li>
        </ul>
        <div class="notice" style="background:#fffbe6;border:1px solid #ffe58f;color:#ad6800">
          安全提示：建议删除或重命名 <code>install</code> 目录，防止被重复安装。
        </div>
        <div class="actions">
          <a class="btn plain" href="' . htmlspecialchars((string)$done['site_url']) . '">访问前台</a>
          <a class="btn" href="' . htmlspecialchars((string)$done['site_url']) . '/admin">进入后台</a>
        </div>';
        render_install_page('安装完成', $body, 5, $errors);
        break;

    default:
        header('Location: index.php?step=1');
        exit;
}
