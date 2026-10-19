<?php

declare(strict_types=1);

namespace App\Core;

/**
 * 简易路由
 * 支持 /user/{id} 形式的占位符
 */
class Router
{
    private static array $routes = [];
    private static string $groupPrefix = '';
    private static array $groupMiddleware = [];
    public static array $current = [];

    public static function add(string $method, string $uri, $action, array $middleware = []): void
    {
        $uri = self::$groupPrefix . $uri;
        $uri = '/' . trim($uri, '/');
        $uri = $uri === '/' ? '/' : rtrim($uri, '/');
        // {id} 默认匹配非斜杠片段，{id:\d+} 使用自定义规则
        $pattern = preg_replace_callback(
            '#\{(\w+)(?::([^}]+))?\}#',
            static function (array $m): string {
                $regex = isset($m[2]) && $m[2] !== '' ? $m[2] : '[^/]+';
                return '(?<' . $m[1] . '>' . str_replace('#', '\#', $regex) . ')';
            },
            $uri
        );
        $pattern = '#^' . str_replace('#', '\#', $pattern) . '$#';
        self::$routes[] = [
            'method' => strtoupper($method),
            'uri' => $uri,
            'regex' => $pattern,
            'action' => $action,
            'middleware' => array_merge(self::$groupMiddleware, $middleware),
        ];
    }

    public static function get(string $uri, $action, array $mw = []): void
    {
        self::add('GET', $uri, $action, $mw);
        self::add('HEAD', $uri, $action, $mw);
    }

    public static function post(string $uri, $action, array $mw = []): void
    {
        self::add('POST', $uri, $action, $mw);
    }

    public static function any(string $uri, $action, array $mw = []): void
    {
        foreach (['GET', 'POST', 'HEAD'] as $m) {
            self::add($m, $uri, $action, $mw);
        }
    }

    public static function group(string $prefix, callable $cb, array $mw = []): void
    {
        $oldPrefix = self::$groupPrefix;
        $oldMw = self::$groupMiddleware;
        self::$groupPrefix = $oldPrefix . '/' . trim($prefix, '/');
        self::$groupMiddleware = array_merge($oldMw, $mw);
        $cb();
        self::$groupPrefix = $oldPrefix;
        self::$groupMiddleware = $oldMw;
    }

    /**
     * 解析控制器动作
     */
    /**
     * 解析控制器动作（带占位符类型保护：声明为 int 的参数若非数字则视为 404）
     */
    private static function resolve($action, array $params): mixed
    {
        $params = self::castParams($action, $params);
        if (is_callable($action) && !is_string($action)) {
            return call_user_func_array($action, $params);
        }
        if (is_string($action)) {
            if (str_contains($action, '@')) {
                [$class, $method] = explode('@', $action, 2);
            } else {
                $class = $action;
                $method = '__invoke';
            }
            if (!class_exists($class)) {
                throw new \RuntimeException('控制器不存在：' . $class);
            }
            $controller = new $class();
            if (!method_exists($controller, $method)) {
                throw new \RuntimeException('方法不存在：' . $class . '::' . $method);
            }
            return call_user_func_array([$controller, $method], $params);
        }
        throw new \RuntimeException('无效的路由动作');
    }

    /**
     * 按控制器方法签名校正参数类型，非数字转 int 失败时抛 404 级异常
     */
    private static function castParams($action, array $params): array
    {
        if (!is_string($action)) {
            return $params;
        }
        $method = str_contains($action, '@') ? explode('@', $action, 2)[1] : '__invoke';
        $class = str_contains($action, '@') ? explode('@', $action, 2)[0] : $action;
        if (!class_exists($class) || !method_exists($class, $method)) {
            return $params;
        }
        try {
            $ref = new \ReflectionMethod($class, $method);
        } catch (\Throwable $e) {
            return $params;
        }
        foreach ($ref->getParameters() as $i => $p) {
            $name = $p->getName();
            if (!array_key_exists($name, $params)) {
                continue;
            }
            $type = $p->getType();
            if ($type instanceof \ReflectionNamedType && $type->getName() === 'int') {
                $value = (string)$params[$name];
                if (!preg_match('/^-?\d+$/', $value)) {
                    throw new \RuntimeException('参数 ' . $name . ' 必须为整数');
                }
                $params[$name] = (int)$value;
                unset($i);
            }
        }
        return $params;
    }

    public static function dispatch(string $path = null): void
    {
        $path = $path ?? Request::path();
        $method = Request::method();
        if ($method === 'HEAD') {
            $method = 'GET';
        }

        // 兼容 ?r=controller/action 形式
        $r = Request::get('r');
        if ($path === '/' && $r) {
            $path = '/' . ltrim((string)$r, '/');
            $path = preg_replace('#/+#', '/', $path);
        }

        // 静态路由（不含占位符）优先匹配，避免 /thread/create 被 /thread/{id} 抢先命中
        $ordered = [];
        foreach (self::$routes as $route) {
            if (!str_contains($route['uri'], '{')) {
                $ordered[] = $route;
            }
        }
        foreach (self::$routes as $route) {
            if (str_contains($route['uri'], '{')) {
                $ordered[] = $route;
            }
        }

        foreach ($ordered as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            if (preg_match($route['regex'], $path, $matches)) {
                $params = [];
                foreach ($matches as $k => $v) {
                    if (!is_int($k)) {
                        $params[$k] = $v;
                    }
                }
                self::$current = $route;
                foreach ($route['middleware'] as $mw) {
                    if ($mw === 'csrf') {
                        CSRF::guard();
                    } elseif ($mw === 'auth') {
                        Auth::requireLogin();
                    } elseif ($mw === 'admin') {
                        Auth::requireAdmin();
                    } elseif (is_callable($mw)) {
                        if ($mw() === false) {
                            return;
                        }
                    }
                }
                try {
                    $result = self::resolve($route['action'], $params);
                } catch (\RuntimeException $e) {
                    // 参数类型不匹配（如 /thread/abc）→ 按 404 处理
                    if (str_contains($e->getMessage(), '必须为整数')) {
                        self::notFound($path);
                        return;
                    }
                    throw $e;
                }
                Session::rotateFlash();
                if (is_string($result)) {
                    echo $result;
                }
                return;
            }
        }

        http_response_code(404);
        self::notFound($path);
    }

    public static function notFound(string $path = ''): void
    {
        http_response_code(404);
        $title = '404 - 页面不存在';
        if (class_exists(\App\Core\View::class)) {
            try {
                View::render('errors/404', ['title' => $title, 'path' => $path]);
                Session::rotateFlash();
                return;
            } catch (\Throwable $e) {
                // fallthrough
            }
        }
        echo '<div style="max-width:640px;margin:80px auto;font-family:sans-serif;text-align:center">'
            . '<h1 style="font-size:22px">404 - 页面不存在</h1>'
            . '<p style="color:#888"><a href="/">返回首页</a></p></div>';
        Session::rotateFlash();
    }

    public static function allRoutes(): array
    {
        return self::$routes;
    }
}
