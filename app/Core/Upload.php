<?php

declare(strict_types=1);

namespace App\Core;

/**
 * 文件上传（共享 / 私人 / 头像 / 封面 / 临时）
 */
class Upload
{
    public const SPACE_SHARED = 'shared';
    public const SPACE_PRIVATE = 'private';

    /**
     * 推荐的后缀白名单（含各类压缩包，如 gz / tar.gz / tgz）
     * 站点未自定义时使用；升级后也可通过后台「数据库管理 → 检查并修复表结构」自动补齐缺失后缀
     */
    public const RECOMMEND_EXT = 'jpg,jpeg,png,gif,webp,bmp,ico,zip,rar,7z,gz,tgz,tar,bz2,xz,'
        . 'pdf,txt,md,csv,json,xml,log,'
        . 'doc,docx,xls,xlsx,ppt,pptx,epub,'
        . 'mp4,mov,avi,mkv,webm,mp3,wav,flac,ogg,apk,psd';

    public static array $imageExt = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
    public static array $dangerExt = ['php', 'php3', 'php4', 'php5', 'php7', 'phtml', 'phar', 'pht', 'asp', 'aspx', 'jsp', 'exe', 'sh', 'cgi', 'pl', 'htaccess', 'ini'];

    /**
     * @param array  $file   $_FILES 单项
     * @param string $space  shared|private|avatars|covers|temp
     */
    public static function save(array $file, string $space = self::SPACE_SHARED, int $userId = 0): array
    {
        if (empty($file['name']) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return [false, self::errorMsg((int)($file['error'] ?? UPLOAD_ERR_NO_FILE))];
        }
        $ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
        if ($ext === '' || in_array($ext, self::$dangerExt, true)) {
            return [false, '不允许上传该类型文件'];
        }
        $allowCfg = strtolower((string)Settings::get('upload_allow_ext', ''));
        $allow = array_filter(array_map('trim', explode(',', $allowCfg)));
        if ($allow && !in_array($ext, $allow, true)) {
            return [false, '不支持的文件格式：' . $ext];
        }
        $maxMb = (float)Settings::get('upload_max_size', 5);
        $maxBytes = $maxMb * 1024 * 1024;
        if ((int)$file['size'] > $maxBytes) {
            return [false, '文件超过 ' . $maxMb . 'MB 限制'];
        }

        $dir = self::spaceDir($space);
        if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
            return [false, '上传目录不可写，请检查 data/uploads 权限'];
        }
        // 按年月分子目录
        $sub = date('Ym');
        $targetDir = rtrim($dir, '/') . '/' . $sub;
        if (!is_dir($targetDir) && !@mkdir($targetDir, 0755, true)) {
            return [false, '无法创建上传子目录'];
        }

        $basename = date('His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        $target = $targetDir . '/' . $basename;
        if (!move_uploaded_file((string)$file['tmp_name'], $target)) {
            return [false, '文件保存失败'];
        }
        // 安全：去掉执行位
        @chmod($target, 0644);
        self::writeGuard($dir);

        $isImage = in_array($ext, self::$imageExt, true);
        return [true, [
            'name' => basename((string)$file['name']),
            'path' => $sub . '/' . $basename,
            'space' => $space,
            'ext' => $ext,
            'size' => (int)$file['size'],
            'mime' => (string)($file['type'] ?: ''),
            'is_image' => $isImage ? 1 : 0,
            'url' => self::url($space, $sub . '/' . $basename),
        ]];
    }

    /**
     * 保存远程或 base64 图片
     */
    public static function saveRaw(string $binary, string $ext, string $space = self::SPACE_SHARED): array
    {
        $ext = strtolower($ext);
        if (in_array($ext, self::$dangerExt, true)) {
            return [false, '不允许的文件类型'];
        }
        $dir = rtrim(self::spaceDir($space), '/') . '/' . date('Ym');
        if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
            return [false, '上传目录不可写'];
        }
        $basename = date('His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        $target = $dir . '/' . $basename;
        if (@file_put_contents($target, $binary) === false) {
            return [false, '保存失败'];
        }
        @chmod($target, 0644);
        self::writeGuard(self::spaceDir($space));
        return [true, [
            'name' => $basename,
            'path' => date('Ym') . '/' . $basename,
            'space' => $space,
            'ext' => $ext,
            'size' => strlen($binary),
            'mime' => '',
            'is_image' => in_array($ext, self::$imageExt, true) ? 1 : 0,
            'url' => self::url($space, date('Ym') . '/' . $basename),
        ]];
    }

    public static function spaceDir(string $space): string
    {
        return CB_UPLOAD . '/' . $space;
    }

    public static function url(string $space, string $path): string
    {
        return url('data/uploads/' . $space . '/' . ltrim($path, '/'));
    }

    /**
     * 真实文件路径（私人空间不暴露 URL）
     */
    public static function filePath(string $space, string $path): string
    {
        $path = str_replace('..', '', $path);
        return self::spaceDir($space) . '/' . ltrim($path, '/');
    }

    public static function errorMsg(int $code): string
    {
        $map = [
            UPLOAD_ERR_INI_SIZE => '文件超过服务器限制',
            UPLOAD_ERR_FORM_SIZE => '文件超过表单限制',
            UPLOAD_ERR_PARTIAL => '文件仅部分上传',
            UPLOAD_ERR_NO_FILE => '没有文件被上传',
            UPLOAD_ERR_NO_TMP_DIR => '缺少临时目录',
            UPLOAD_ERR_CANT_WRITE => '写入磁盘失败',
            UPLOAD_ERR_EXTENSION => '上传被扩展阻止',
        ];
        return $map[$code] ?? '上传失败';
    }

    /**
     * 禁止上传目录执行 PHP
     */
    public static function writeGuard(string $dir): void
    {
        $ht = rtrim($dir, '/') . '/.htaccess';
        if (!is_file($ht)) {
            @file_put_contents($ht, "# ChatBBS 上传目录安全规则\n"
                . "php_flag engine off\n"
                . "<FilesMatch \"\\.(php|php3|php4|php5|php7|phtml|phar|pht|pl|cgi|asp|aspx|jsp)$\">\n"
                . "  Deny from all\n"
                . "</FilesMatch>\n"
                . "Options -Indexes\n");
        }
        $ini = rtrim($dir, '/') . '/.user.ini';
        if (!is_file($ini)) {
            @file_put_contents($ini, "engine = off\nopen_basedir =\n");
        }
    }

    public static function delete(string $space, string $path): bool
    {
        $file = self::filePath($space, $path);
        if (is_file($file)) {
            return @unlink($file);
        }
        return false;
    }
}
