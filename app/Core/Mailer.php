<?php

declare(strict_types=1);

namespace App\Core;

/**
 * SMTP 发信（无第三方依赖）
 */
class Mailer
{
    private array $cfg = [];
    private $socket = null;
    private string $log = '';

    public function __construct(array $cfg = [])
    {
        $this->cfg = $cfg ?: [
            'host' => (string)Settings::get('smtp_host', ''),
            'port' => (int)Settings::get('smtp_port', 465),
            'user' => (string)Settings::get('smtp_user', ''),
            'pass' => (string)Settings::get('smtp_pass', ''),
            'ssl' => (bool)Settings::get('smtp_ssl', 1),
            'from' => (string)Settings::get('smtp_from', ''),
            'from_name' => (string)Settings::get('smtp_from_name', ''),
        ];
        if ($this->cfg['from'] === '') {
            $this->cfg['from'] = (string)$this->cfg['user'];
        }
        if ($this->cfg['from_name'] === '') {
            $this->cfg['from_name'] = (string)Settings::get('site_name', 'ChatBBS');
        }
    }

    public function configured(): bool
    {
        return $this->cfg['host'] !== '' && $this->cfg['user'] !== '';
    }

    /**
     * @return array [bool, string]
     */
    public function send(string $to, string $subject, string $body): array
    {
        $siteName = (string)Settings::get('site_name', 'ChatBBS');
        $subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $from = $this->cfg['from'];
        $fromName = '=?UTF-8?B?' . base64_encode((string)$this->cfg['from_name']) . '?=';

        $headers = [
            'Date' => date('r'),
            'From' => $fromName . ' <' . $from . '>',
            'To' => '<' . $to . '>',
            'Subject' => $subject,
            'MIME-Version' => '1.0',
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Transfer-Encoding' => 'base64',
            'X-Mailer' => 'ChatBBS ' . CB_VERSION,
        ];
        $headerStr = '';
        foreach ($headers as $k => $v) {
            $headerStr .= $k . ': ' . $v . "\r\n";
        }
        $data = $headerStr . "\r\n" . rtrim(chunk_split(base64_encode($body), 76, "\r\n"));

        if (!$this->configured()) {
            // 降级使用 mail()
            $ok = @mail($to, $subject, $body, $headerStr);
            return [$ok, $ok ? '已通过 mail() 发送' : 'mail() 发送失败，请配置 SMTP'];
        }

        try {
            $this->connect();
            $this->cmd('EHLO ' . $this->serverName(), 250);
            $this->auth();
            $this->cmd('MAIL FROM: <' . $from . '>', 250);
            $this->cmd('RCPT TO: <' . $to . '>', [250, 251]);
            $this->cmd('DATA', 354);
            $this->cmd($data . "\r\n.", 250);
            $this->cmd('QUIT', 221, false);
            if (is_resource($this->socket)) {
                @fclose($this->socket);
            }
            return [true, '发送成功'];
        } catch (\Throwable $e) {
            if (is_resource($this->socket)) {
                @fclose($this->socket);
            }
            Logger::error('mail send failed: ' . $e->getMessage() . ' | log: ' . $this->log);
            return [false, '发送失败：' . $e->getMessage()];
        }
    }

    private function connect(): void
    {
        $host = (string)$this->cfg['host'];
        $port = (int)$this->cfg['port'];
        $ssl = (bool)$this->cfg['ssl'];
        $remote = ($ssl ? 'ssl://' : '') . $host . ':' . $port;
        $this->socket = @stream_socket_client($remote, $errno, $errstr, 15, STREAM_CLIENT_CONNECT);
        if (!$this->socket) {
            throw new \RuntimeException('无法连接 SMTP 服务器：' . $errstr);
        }
        stream_set_timeout($this->socket, 15);
        $this->read();
    }

    private function auth(): void
    {
        $this->cmd('AUTH LOGIN', 334);
        $this->cmd(base64_encode((string)$this->cfg['user']), 334);
        $reply = $this->cmd(base64_encode((string)$this->cfg['pass']), [235, 334]);
        if ($reply === 334) {
            throw new \RuntimeException('SMTP 账号或密码错误');
        }
    }

    private function serverName(): string
    {
        $host = (string)($_SERVER['HTTP_HOST'] ?? 'localhost');
        $host = preg_replace('/:\d+$/', '', $host);
        return $host ?: 'localhost';
    }

    private function cmd(string $cmd, $expect, bool $check = true)
    {
        if (!is_resource($this->socket)) {
            throw new \RuntimeException('SMTP 连接已断开');
        }
        fwrite($this->socket, $cmd . "\r\n");
        $resp = $this->read();
        if (!$check) {
            return 0;
        }
        $code = (int)substr(trim((string)$resp), 0, 3);
        $expects = is_array($expect) ? $expect : [$expect];
        if (!in_array($code, $expects, true)) {
            throw new \RuntimeException('SMTP 响应异常 (' . $code . ')：' . trim((string)$resp));
        }
        return $code;
    }

    private function read(): string
    {
        $out = '';
        while (is_resource($this->socket) && !feof($this->socket)) {
            $line = fgets($this->socket, 1024);
            if ($line === false) {
                break;
            }
            $out .= $line;
            if (strlen($line) < 4 || $line[3] === ' ' || $line[3] === "\r" || $line[3] === "\n") {
                break;
            }
        }
        $this->log .= $out;
        return $out;
    }

    /**
     * 通用邮件模板
     */
    public static function template(string $title, string $contentHtml): string
    {
        $siteName = h((string)Settings::get('site_name', 'ChatBBS'));
        $siteUrl = h(url(''));
        return <<<HTML
<div style="background:#f5f6f8;padding:24px;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,'PingFang SC','Microsoft YaHei',sans-serif">
  <div style="max-width:600px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;border:1px solid #eceff3">
    <div style="background:linear-gradient(90deg,#0050b5,#0b87fd);padding:20px 24px;color:#fff;font-size:18px;font-weight:600">{$siteName}</div>
    <div style="padding:24px">
      <h2 style="margin:0 0 16px;font-size:18px;color:#1f2329">{$title}</h2>
      <div style="font-size:14px;line-height:1.8;color:#4e5969">{$contentHtml}</div>
    </div>
    <div style="padding:16px 24px;background:#fafbfc;color:#8a919f;font-size:12px;border-top:1px solid #eceff3">
      本邮件由系统自动发送，请勿直接回复。<br>
      <a href="{$siteUrl}" style="color:#0b87fd;text-decoration:none">{$siteName}</a>
    </div>
  </div>
</div>
HTML;
    }
}
