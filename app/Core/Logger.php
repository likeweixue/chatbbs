<?php

declare(strict_types=1);

namespace App\Core;

/**
 * 文件日志
 */
class Logger
{
    private static function write(string $level, string $message, array $context = []): void
    {
        if (!is_dir(CB_LOG) && !@mkdir(CB_LOG, 0755, true)) {
            return;
        }
        $file = CB_LOG . '/' . date('Y-m-d') . '.log';
        $ctx = $context ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        $line = sprintf(
            "[%s] %s: %s%s | ip=%s url=%s\n",
            date('Y-m-d H:i:s'),
            strtoupper($level),
            $message,
            $ctx,
            client_ip(),
            (string)($_SERVER['REQUEST_URI'] ?? '')
        );
        @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }

    /**
     * 通用级别写入（供 error handler 等内部调用）
     */
    public static function log(string $level, string $message, array $context = []): void
    {
        self::write($level, $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::write('info', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::write('error', $message, $context);
    }

    public static function admin(string $action, string $target = ''): void
    {
        $uid = (int)(Auth::id() ?? 0);
        try {
            Database::insert('admin_logs', [
                'user_id' => $uid,
                'action' => mb_substr($action, 0, 100),
                // target 列为 varchar(255)，超长会 1406 报错（曾导致保存设置时报 "admin log failed"）
                'target' => mb_substr($target, 0, 250),
                'ip' => client_ip(),
                'ua' => substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 250),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            self::error('admin log failed: ' . $e->getMessage());
        }
    }

    public static function tail(int $lines = 200): array
    {
        $file = CB_LOG . '/' . date('Y-m-d') . '.log';
        if (!is_file($file)) {
            return [];
        }
        $all = file($file, FILE_IGNORE_NEW_LINES);
        return array_slice(array_reverse($all), 0, $lines);
    }
}
