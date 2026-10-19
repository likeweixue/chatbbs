<?php

declare(strict_types=1);

namespace App\Core;

/**
 * 响应封装
 */
class Response
{
    public static function json(array $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function success($data = null, string $msg = '操作成功'): void
    {
        self::json(['ok' => true, 'msg' => $msg, 'data' => $data]);
    }

    public static function fail(string $msg = '操作失败', $data = null, int $code = 200): void
    {
        self::json(['ok' => false, 'msg' => $msg, 'data' => $data], $code);
    }

    public static function back(string $msg = '', string $type = 'error'): void
    {
        if ($msg !== '') {
            Session::flash($type, $msg);
        }
        $ref = Request::referer();
        if ($ref === '') {
            $ref = Request::baseUrl() . '/';
        }
        header('Location: ' . $ref);
        exit;
    }

    public static function download(string $file, string $name = '', bool $deleteAfter = false): void
    {
        if (!is_file($file)) {
            http_response_code(404);
            echo '文件不存在';
            exit;
        }
        $name = $name !== '' ? $name : basename($file);
        $name = preg_replace('/[\r\n"<>]/', '', $name);
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $name . '"; filename*=UTF-8\'\'' . rawurlencode($name));
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: ' . filesize($file));
        header('X-Content-Type-Options: nosniff');
        while (ob_get_level()) {
            ob_end_clean();
        }
        readfile($file);
        if ($deleteAfter) {
            @unlink($file);
        }
        exit;
    }

    public static function noCache(): void
    {
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');
    }
}
