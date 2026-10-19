<?php

declare(strict_types=1);

namespace App\Core;

/**
 * 多语言
 *
 * 设计要点：
 *  - 词条采用「简体中文原文」作为键。中文环境直接返回原文（零字典、零开销），
 *    切换到英文时查映射表翻译；未收录的词条优雅降级为中文原文，不会显示成空白。
 *  - 语言优先级：Cookie（用户在顶栏的选择） > 用户个人设置 > 站点默认设置 > zh-CN
 *  - 只翻译界面文案，**从不翻译用户产生的内容**（帖子、简介、消息正文等）。
 */
class Lang
{
    /** Cookie 键名 */
    public const COOKIE = 'cb_lang';

    /** 兜底语言 */
    public const FALLBACK = 'zh-CN';

    /** 当前语言（请求内缓存） */
    private static ?string $current = null;

    /** 已加载的语言包缓存 */
    private static array $packs = [];

    /**
     * 内置可用语言（语言包文件不存在时也能用）
     * @return array<string, string> code => 名称
     */
    public static function available(): array
    {
        $list = [
            'zh-CN' => '简体中文',
            'en-US' => 'English',
        ];
        // 语言包目录里的扩展语言包
        $dir = CB_ROOT . '/config/lang';
        foreach (glob($dir . '/*.php') ?: [] as $file) {
            $code = basename($file, '.php');
            if (isset($list[$code])) {
                continue;
            }
            $pack = self::pack($code);
            $list[$code] = (string)($pack['_name'] ?? $code);
        }
        return $list;
    }

    public static function isAvailable(string $code): bool
    {
        return $code !== '' && array_key_exists($code, self::available());
    }

    /**
     * 当前语言代码
     */
    public static function current(): string
    {
        if (self::$current !== null) {
            return self::$current;
        }
        $code = '';

        // 1) 用户在顶栏的语言切换（Cookie，游客也生效）
        $cookie = (string)($_COOKIE[self::COOKIE] ?? '');
        if (self::isAvailable($cookie)) {
            $code = $cookie;
        }

        // 2) 登录用户的个人语言设置
        if ($code === '') {
            try {
                $user = class_exists('\\App\\Core\\Auth', true) ? Auth::user() : null;
                if (is_array($user) && isset($user['language'])) {
                    $v = (string)$user['language'];
                    if (self::isAvailable($v)) {
                        $code = $v;
                    }
                }
            } catch (\Throwable $e) {
                // 会话 / 数据库不可用时忽略
            }
        }

        // 3) 站点默认语言
        if ($code === '') {
            try {
                $v = (string)Settings::get('language', self::FALLBACK);
                if (self::isAvailable($v)) {
                    $code = $v;
                }
            } catch (\Throwable $e) {
                // 忽略
            }
        }

        self::$current = $code !== '' ? $code : self::FALLBACK;
        return self::$current;
    }

    /**
     * 切换当前语言（仅本次请求生效，用于预览）
     */
    public static function use(string $code): void
    {
        self::$current = self::isAvailable($code) ? $code : self::FALLBACK;
    }

    /**
     * 持久化切换语言：写 Cookie + （已登录时）写入用户资料
     */
    public static function persist(string $code): bool
    {
        if (!self::isAvailable($code)) {
            return false;
        }
        self::$current = $code;

        // Cookie：一年
        $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        @setcookie(self::COOKIE, $code, [
            'expires'  => time() + 86400 * 365,
            'path'     => '/',
            'secure'   => $secure,
            'httponly' => false,
            'samesite' => 'Lax',
        ]);
        $_COOKIE[self::COOKIE] = $code;

        // 登录用户同步写回资料
        try {
            $user = Auth::user();
            if (is_array($user) && !empty($user['id'])) {
                Database::update('users', ['language' => $code], 'id = ?', [(int)$user['id']]);
            }
        } catch (\Throwable $e) {
            // users.language 列缺失时忽略
        }
        return true;
    }

    /**
     * 读取语言包
     */
    public static function pack(string $code): array
    {
        if (isset(self::$packs[$code])) {
            return self::$packs[$code];
        }
        $file = CB_ROOT . '/config/lang/' . basename($code) . '.php';
        $pack = [];
        if (is_file($file)) {
            $loaded = require $file;
            if (is_array($loaded)) {
                $pack = $loaded;
            }
        }
        return self::$packs[$code] = $pack;
    }

    /**
     * 翻译（键为中文原文；未命中则返回原文，保证界面永不出空白）
     *
     * @param array<string, scalar> $replace :name 占位符替换
     */
    public static function t(string $text, array $replace = []): string
    {
        $text = str_replace("\r\n", "\n", $text);
        // 纯空白或纯符号无需翻译
        if (trim($text) === '') {
            return $text;
        }
        $pack = self::pack(self::current());
        $out = $pack[$text] ?? $text;
        if (!is_string($out)) {
            $out = $text;
        }
        foreach ($replace as $k => $v) {
            $out = str_replace(':' . $k, (string)$v, (string)$out);
        }
        return $out;
    }

    /**
     * 是否处于默认中文（用于省掉前端字典输出）
     */
    public static function isDefault(): bool
    {
        return self::current() === 'zh-CN';
    }

    /**
     * 供前端 JS 使用的字典（中文时返回空数组）
     *
     * @return array<string, string>
     */
    public static function jsMap(): array
    {
        if (self::isDefault()) {
            return [];
        }
        $out = [];
        foreach (self::pack(self::current()) as $k => $v) {
            if ($k === '' || $k[0] === '_') {
                continue; // 保留键：_name / _code
            }
            if (is_string($v) && $v !== '') {
                $out[$k] = $v;
            }
        }
        return $out;
    }
}
