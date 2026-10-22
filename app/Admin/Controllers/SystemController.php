<?php

declare(strict_types=1);

namespace App\Admin\Controllers;

use App\Admin\AdminUI;
use App\Admin\BaseAdmin;
use App\Core\Database;
use App\Core\Logger;
use App\Core\Request;
use App\Core\Response;
use App\Services\Stats;

/**
 * 系统工具：缓存 / 日志 / 操作日志 / 备份 / 数据库 / 系统信息
 */
class SystemController extends BaseAdmin
{
    /* ================== 缓存管理 ================== */
    public function cache(): string
    {
        $files = glob(CB_CACHE . '/cache_*.php') ?: [];
        $size = 0;
        foreach ($files as $f) {
            $size += (int)filesize($f);
        }
        $html = '<div class="adm-stats">'
            . '<div class="adm-stat"><div class="adm-stat__label">缓存文件</div><div class="adm-stat__value">' . count($files) . '</div></div>'
            . '<div class="adm-stat"><div class="adm-stat__label">占用空间</div><div class="adm-stat__value">' . format_size($size) . '</div></div>'
            . '<div class="adm-stat"><div class="adm-stat__label">缓存目录</div><div class="adm-stat__value" style="font-size:14px">' . h(CB_CACHE) . '</div></div>'
            . '</div>';

        $html .= '<div class="adm-panel"><div class="adm-panel-head">清理缓存</div><div class="adm-panel-body">'
            . '<form method="post" action="' . h(admin_url('cache/clear')) . '" class="flex gap-8 items-center" style="flex-wrap:wrap">'
            . csrf_field()
            . '<button class="adm-btn primary" type="submit" name="scope" value="all">清理全部缓存</button>'
            . '<button class="adm-btn" type="submit" name="scope" value="settings">仅清理站点设置</button>'
            . '<button class="adm-btn" type="submit" name="scope" value="stats">仅清理统计数据</button>'
            . '<button class="adm-btn" type="submit" name="scope" value="old">清理过期缓存</button>'
            . '</form>'
            . '<div class="adm-form-tip" style="margin-top:10px">修改设置或模板后如未生效，可清理缓存后重试。</div>'
            . '</div></div>';

        $html .= '<div class="adm-panel"><div class="adm-panel-head">缓存文件列表（最近 30 个）</div><div class="adm-panel-body tight">'
            . '<table class="adm-table"><thead><tr><th>文件</th><th style="width:100px">大小</th><th style="width:160px">修改时间</th></tr></thead><tbody>';
        usort($files, static fn($a, $b) => filemtime($b) <=> filemtime($a));
        foreach (array_slice($files, 0, 30) as $f) {
            $html .= '<tr><td>' . h(basename($f)) . '</td><td>' . h(format_size((float)filesize($f))) . '</td>'
                . '<td>' . h(format_date(filemtime($f))) . '</td></tr>';
        }
        $html .= $files ? '' : '<tr><td colspan="3" class="adm-empty">暂无缓存文件</td></tr>';
        $html .= '</tbody></table></div></div>';

        return $this->pageView('缓存管理', 'cache', $html);
    }

    public function cacheClear(): void
    {
        $scope = (string)Request::post('scope', 'all');
        $n = 0;
        if ($scope === 'settings') {
            \App\Core\Cache::forget('settings');
            $n = 1;
        } elseif ($scope === 'stats') {
            foreach (['stats_forum', 'trend_7', 'trend_30'] as $k) {
                \App\Core\Cache::forget($k);
            }
            Stats::clearCache();
            $n = 4;
        } elseif ($scope === 'old') {
            foreach (glob(CB_CACHE . '/cache_*.php') ?: [] as $f) {
                $data = @include $f;
                if (is_array($data) && isset($data['expire']) && $data['expire'] > 0 && $data['expire'] < time()) {
                    @unlink($f);
                    $n++;
                }
            }
        } else {
            $n = \App\Core\Cache::flush();
            \App\Core\Cache::forget('settings');
        }
        $this->log('清理缓存', $scope . ' 共 ' . $n . ' 项');
        $this->back('已清理 ' . $n . ' 个缓存项');
    }

    /* ================== 系统日志 ================== */
    public function logs(): string
    {
        $level = (string)Request::get('level', '');
        $where = '1=1';
        $params = [];
        if ($level !== '') {
            $where .= ' AND level = ?';
            $params[] = $level;
        }
        $p = $this->queryPage(
            'SELECT * FROM ' . Database::table('logs') . ' WHERE ' . $where . ' ORDER BY id DESC',
            'SELECT COUNT(*) FROM ' . Database::table('logs') . ' WHERE ' . $where,
            $params,
            30,
            $this->baseUrl('logs', ['level' => $level])
        );

        $html = '<div class="adm-toolbar" style="border:1px solid var(--adm-border);border-radius:6px">'
            . '<form method="get" action="' . h(admin_url('logs')) . '" class="flex gap-8 items-center">'
            . '<select class="adm-input" style="width:auto" name="level">'
            . '<option value="">全部级别</option>'
            . '<option value="info"' . ($level === 'info' ? ' selected' : '') . '>信息</option>'
            . '<option value="error"' . ($level === 'error' ? ' selected' : '') . '>错误</option>'
            . '</select><button class="adm-btn primary" type="submit">筛选</button></form>'
            . '<div class="spacer"></div>'
            . '<form method="post" action="' . h(admin_url('logs/clear')) . '" style="display:inline">'
            . csrf_field()
            . '<button class="adm-btn danger" type="submit" onclick="return confirm(\'确定清空全部系统日志？\')">清空日志</button></form>'
            . '</div>';

        $html .= AdminUI::table([
            'primary' => 'id',
            'rows' => $p['items'],
            'columns' => [
                ['key' => 'id', 'label' => 'ID', 'width' => '70px'],
                ['key' => 'level', 'label' => '级别', 'width' => '80px', 'render' => fn($r) => (string)$r['level'] === 'error'
                    ? '<span class="adm-tag red">错误</span>' : '<span class="adm-tag blue">信息</span>'],
                ['key' => 'message', 'label' => '内容', 'render' => fn($r) => h(str_cut((string)$r['message'], 90))],
                ['key' => 'ip', 'label' => 'IP', 'width' => '130px'],
                ['key' => 'url', 'label' => 'URL', 'width' => '220px', 'render' => fn($r) => h(str_cut((string)$r['url'], 40))],
                ['key' => 'created_at', 'label' => '时间', 'width' => '140px', 'render' => fn($r) => format_date($r['created_at'])],
            ],
        ]);
        $html .= '<div class="adm-pager">' . $p['pager'] . '</div>';

        $tail = \App\Core\Logger::tail(60);
        if ($tail) {
            $html .= '<div class="adm-panel"><div class="adm-panel-head">日志文件尾部（' . h(CB_LOG) . '）</div><div class="adm-panel-body">'
                . '<div class="adm-log">' . h(implode("\n", array_slice($tail, -60))) . '</div></div></div>';
        }

        return $this->pageView('系统日志（共 ' . $p['total'] . ' 条）', 'logs', $html);
    }

    public function logsClear(): void
    {
        Database::query('DELETE FROM ' . Database::table('logs'));
        $this->log('清空系统日志');
        $this->back('系统日志已清空');
    }

    /* ================== 操作日志 ================== */
    public function adminlogs(): string
    {
        $filters = ['kw' => (string)Request::get('kw', ''), 'uid' => Request::int('uid')];
        $at = Database::table('admin_logs');
        $ut = Database::table('users');
        $where = '1=1';
        $params = [];
        if ($filters['kw'] !== '') {
            $where .= ' AND (a.action LIKE ? OR a.target LIKE ?)';
            $params[] = '%' . $filters['kw'] . '%';
            $params[] = '%' . $filters['kw'] . '%';
        }
        if ($filters['uid'] > 0) {
            $where .= ' AND a.user_id = ?';
            $params[] = $filters['uid'];
        }
        $p = $this->queryPage(
            'SELECT a.*, u.username FROM ' . $at . ' a LEFT JOIN ' . $ut . ' u ON u.id = a.user_id
             WHERE ' . $where . ' ORDER BY a.id DESC',
            'SELECT COUNT(*) FROM ' . $at . ' a WHERE ' . $where,
            $params,
            30,
            $this->baseUrl('adminlogs', $filters)
        );

        $html = AdminUI::filter([
            'action' => admin_url('adminlogs'),
            'fields' => [
                ['name' => 'kw', 'label' => '操作/对象关键词', 'value' => $filters['kw']],
                ['name' => 'uid', 'label' => '管理员ID', 'value' => $filters['uid']],
            ],
        ]);
        $html .= AdminUI::table([
            'primary' => 'id',
            'rows' => $p['items'],
            'columns' => [
                ['key' => 'id', 'label' => 'ID', 'width' => '70px'],
                ['key' => 'username', 'label' => '管理员', 'width' => '130px'],
                ['key' => 'action', 'label' => '操作', 'width' => '200px'],
                ['key' => 'target', 'label' => '对象', 'render' => fn($r) => h(str_cut((string)$r['target'], 50))],
                ['key' => 'ip', 'label' => 'IP', 'width' => '130px'],
                ['key' => 'created_at', 'label' => '时间', 'width' => '140px', 'render' => fn($r) => format_date($r['created_at'])],
            ],
        ]);
        $html .= '<div class="adm-pager">' . $p['pager'] . '</div>';

        return $this->pageView('操作日志（共 ' . $p['total'] . ' 条）', 'adminlogs', $html, [
            ['label' => '清空日志', 'url' => admin_url('adminlogs/clear'), 'type' => 'post', 'class' => 'danger', 'confirm' => '确定清空全部操作日志？'],
        ]);
    }

    public function adminlogsClear(): void
    {
        Database::query('DELETE FROM ' . Database::table('admin_logs'));
        $this->log('清空操作日志');
        $this->back('操作日志已清空');
    }

    /* ================== 数据备份 ================== */
    public function backup(): string
    {
        $dir = CB_DATA . '/backups';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $files = glob($dir . '/*.sql') ?: [];
        usort($files, static fn($a, $b) => filemtime($b) <=> filemtime($a));

        $rows = [];
        foreach ($files as $f) {
            $rows[] = [
                'id' => 0,
                'name' => basename($f),
                'size' => (float)filesize($f),
                'time' => filemtime($f),
            ];
        }

        $html = '<div class="adm-panel"><div class="adm-panel-head">创建备份</div><div class="adm-panel-body">'
            . '<form method="post" action="' . h(admin_url('backup/create')) . '" class="flex gap-8 items-center">'
            . csrf_field()
            . '<button class="adm-btn primary" type="submit">立即备份数据库</button>'
            . '<span class="adm-form-tip" style="margin:0">备份文件保存在 data/backups/ 目录，建议定期下载到本地保存</span>'
            . '</form></div></div>';

        $html .= '<div class="adm-panel"><div class="adm-panel-head">备份列表</div><div class="adm-panel-body tight">'
            . '<table class="adm-table"><thead><tr><th>文件名</th><th style="width:110px">大小</th><th style="width:160px">时间</th><th style="width:200px">操作</th></tr></thead><tbody>';
        foreach ($rows as $r) {
            $html .= '<tr><td>' . h((string)$r['name']) . '</td>'
                . '<td>' . h(format_size($r['size'])) . '</td>'
                . '<td>' . h(format_date($r['time'])) . '</td>'
                . '<td><div class="adm-row-actions">'
                . '<a class="adm-link" href="' . h(admin_url('backup/download?file=' . urlencode((string)$r['name']))) . '">下载</a>'
                . '<form method="post" action="' . h(admin_url('backup/restore')) . '" style="display:inline">'
                . csrf_field() . '<input type="hidden" name="file" value="' . h((string)$r['name']) . '">'
                . '<button class="adm-link" type="submit" onclick="return confirm(\'确定用该备份恢复数据库？当前数据将被覆盖！\')">恢复</button></form>'
                . '<form method="post" action="' . h(admin_url('backup/delete')) . '" style="display:inline">'
                . csrf_field() . '<input type="hidden" name="file" value="' . h((string)$r['name']) . '">'
                . '<button class="adm-link danger" type="submit" onclick="return confirm(\'确定删除该备份？\')">删除</button></form>'
                . '</div></td></tr>';
        }
        $html .= $rows ? '' : '<tr><td colspan="4" class="adm-empty">暂无备份文件</td></tr>';
        $html .= '</tbody></table></div></div>';

        return $this->pageView('数据备份', 'backup', $html);
    }

    public function backupCreate(): void
    {
        $dir = CB_DATA . '/backups';
        if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
            $this->back('备份目录不可写', 'error');
        }
        $file = $dir . '/backup-' . date('Ymd-His') . '.sql';
        $pdo = Database::pdo();
        $out = "-- ChatBBS 数据库备份\n-- 时间：" . date('Y-m-d H:i:s') . "\n-- 版本：" . CB_VERSION . "\n\n";
        $out .= "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS = 0;\n\n";

        $tables = [];
        foreach ($pdo->query('SHOW TABLES') as $row) {
            $tables[] = array_values($row)[0];
        }
        foreach ($tables as $table) {
            $create = $pdo->query('SHOW CREATE TABLE `' . $table . '`')->fetch();
            $out .= "DROP TABLE IF EXISTS `" . $table . "`;\n";
            $out .= (string)($create[1] ?? '') . ";\n\n";

            $stmt = $pdo->query('SELECT * FROM `' . $table . '`');
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $cols = array_map(static fn($c) => '`' . $c . '`', array_keys($row));
                $vals = array_map(static function ($v) use ($pdo) {
                    if ($v === null) {
                        return 'NULL';
                    }
                    return $pdo->quote((string)$v);
                }, array_values($row));
                $out .= 'INSERT INTO `' . $table . '` (' . implode(',', $cols) . ') VALUES (' . implode(',', $vals) . ");\n";
            }
            $out .= "\n";
        }
        $out .= "SET FOREIGN_KEY_CHECKS = 1;\n";
        if (@file_put_contents($file, $out, LOCK_EX) === false) {
            $this->back('备份文件写入失败', 'error');
        }
        $this->log('创建数据库备份', basename($file));
        $this->back('备份完成：' . basename($file) . '（' . format_size((float)filesize($file)) . '）');
    }

    public function backupDownload(): void
    {
        $file = self::safeBackupFile((string)Request::get('file', ''));
        if ($file === '') {
            $this->back('备份文件不存在', 'error');
        }
        Response::download($file);
    }

    public function backupRestore(): void
    {
        $file = self::safeBackupFile((string)Request::post('file', ''));
        if ($file === '') {
            $this->back('备份文件不存在', 'error');
        }
        try {
            Database::execSqlFile($file);
        } catch (\Throwable $e) {
            $this->back('恢复失败：' . $e->getMessage(), 'error');
        }
        \App\Core\Cache::flush();
        $this->log('恢复数据库备份', basename($file));
        $this->back('数据库已从 ' . basename($file) . ' 恢复');
    }

    public function backupDelete(): void
    {
        $file = self::safeBackupFile((string)Request::post('file', ''));
        if ($file === '') {
            $this->back('备份文件不存在', 'error');
        }
        @unlink($file);
        $this->log('删除备份', basename($file));
        $this->back('备份已删除');
    }

    private static function safeBackupFile(string $name): string
    {
        $name = basename($name);
        if ($name === '' || !preg_match('/^[\w.\-]+\.sql$/', $name)) {
            return '';
        }
        $file = CB_DATA . '/backups/' . $name;
        return is_file($file) ? $file : '';
    }

    /* ================== 数据库管理 ================== */
    public function database(): string
    {
        $rows = Database::fetchAll('SHOW TABLE STATUS');
        $totalSize = 0;
        $totalRows = 0;
        foreach ($rows as $r) {
            $totalSize += (float)($r['Data_length'] ?? 0) + (float)($r['Index_length'] ?? 0);
            $totalRows += (int)($r['Rows'] ?? 0);
        }

        $html = '<div class="adm-stats">'
            . '<div class="adm-stat"><div class="adm-stat__label">数据表数量</div><div class="adm-stat__value">' . count($rows) . '</div></div>'
            . '<div class="adm-stat"><div class="adm-stat__label">总记录数</div><div class="adm-stat__value">' . $totalRows . '</div></div>'
            . '<div class="adm-stat"><div class="adm-stat__label">占用空间</div><div class="adm-stat__value">' . format_size($totalSize) . '</div></div>'
            . '<div class="adm-stat"><div class="adm-stat__label">表前缀</div><div class="adm-stat__value" style="font-size:16px">' . h(Database::prefix()) . '</div></div>'
            . '</div>';

        $html .= '<div class="adm-toolbar" style="border:1px solid var(--adm-border);border-radius:6px">'
            . '<div class="adm-form-tip" style="margin:0">定期优化表可整理碎片、提升查询效率。</div><div class="spacer"></div>'
            . '<form method="post" action="' . h(admin_url('database/fix')) . '" style="display:inline">'
            . csrf_field() . '<button class="adm-btn primary" type="submit">检查并修复表结构</button></form>'
            . '<form method="post" action="' . h(admin_url('database/optimize')) . '" style="display:inline">'
            . csrf_field() . '<button class="adm-btn" type="submit">优化所有表</button></form></div>'
            . '<div class="adm-form-tip" style="padding:0 12px 12px">升级版本后如提示缺少字段，点"检查并修复表结构"自动补齐（不会影响已有数据）。</div>';

        $html .= '<div class="adm-table-wrap"><table class="adm-table"><thead><tr>'
            . '<th>表名</th><th style="width:90px">引擎</th><th style="width:100px">记录数</th>'
            . '<th style="width:110px">数据大小</th><th style="width:110px">索引大小</th><th style="width:120px">字符集</th></tr></thead><tbody>';
        foreach ($rows as $r) {
            $html .= '<tr><td>' . h((string)$r['Name']) . '</td>'
                . '<td>' . h((string)($r['Engine'] ?? '-')) . '</td>'
                . '<td>' . (int)($r['Rows'] ?? 0) . '</td>'
                . '<td>' . h(format_size((float)($r['Data_length'] ?? 0))) . '</td>'
                . '<td>' . h(format_size((float)($r['Index_length'] ?? 0))) . '</td>'
                . '<td>' . h((string)($r['Collation'] ?? '-')) . '</td></tr>';
        }
        $html .= '</tbody></table></div>';

        return $this->pageView('数据库管理', 'database', $html);
    }

    /**
     * 检查并补齐新增字段（版本升级后结构同步，幂等）
     */
    public function databaseFix(): void
    {
        // 建表 / 补字段统一交给 App\Core\Schema（幂等，与「版本升级自检」共用同一份定义）
        \App\Core\Schema::ensure();
        $done = 0;
        // 补齐索引：楼中楼查询按 reply_to 过滤，缺索引会退化为范围内扫描
        foreach ([['posts', 'idx_reply_to', 'reply_to']] as [$table, $index, $column]) {
            $exists = Database::fetch(
                'SELECT COUNT(*) AS c FROM information_schema.STATISTICS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?',
                [Database::table($table), $index]
            );
            if ($exists && (int)$exists['c'] > 0) {
                continue;
            }
            try {
                Database::query('ALTER TABLE ' . Database::table($table) . ' ADD INDEX `' . $index . '` (`' . $column . '`)');
                $done++;
            } catch (\Throwable $e) {
                Logger::error('补索引失败：' . $table . '.' . $index . ' ' . $e->getMessage());
            }
        }
        // 顺带补齐上传后缀白名单（老版本默认不含 gz / tar.gz 等压缩包）
        $extMsg = '';
        $current = array_values(array_filter(array_map('trim', explode(',', strtolower((string)Settings::get('upload_allow_ext', ''))))));
        if (!$current) {
            Settings::set('upload_allow_ext', \App\Core\Upload::RECOMMEND_EXT);
            $extMsg = '；并已写入默认上传后缀白名单';
        } else {
            $recommend = array_values(array_filter(array_map('trim', explode(',', strtolower(\App\Core\Upload::RECOMMEND_EXT)))));
            $missing = array_values(array_diff($recommend, $current));
            if ($missing) {
                Settings::set('upload_allow_ext', implode(',', array_merge($current, $missing)));
                $extMsg = '；并补充了 ' . count($missing) . ' 个上传后缀（' . implode(',', array_slice($missing, 0, 8)) . '…）';
            }
        }
        \App\Core\Cache::forget('settings');
        $this->log('检查数据库结构', '补齐 ' . $done . ' 处' . $extMsg);
        $this->back('表结构检查完成' . ($done > 0 ? '，补齐 ' . $done . ' 处' : '，已是最新') . $extMsg);
    }

    public function databaseOptimize(): void
    {
        $rows = Database::fetchAll('SHOW TABLES');
        foreach ($rows as $r) {
            $table = array_values($r)[0];
            Database::query('OPTIMIZE TABLE `' . $table . '`');
        }
        $this->log('优化数据表');
        $this->back('数据表已优化');
    }

    /* ================== 在线升级 ================== */
    public function upgrade(): string
    {
        $dir = CB_DATA . '/upgrades';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $html = '<div class="adm-alert warn">'
            . '<b>升级前请务必备份！</b> 请先到「数据备份」下载一份数据库备份，'
            . '并确保服务器上的 <code>config/database.php</code>、<code>data/</code> 目录有可恢复的副本。'
            . '升级会覆盖程序文件（<b>不会</b>覆盖 <code>config/</code> 与 <code>data/</code>）。'
            . '</div>';

        $html .= '<div class="adm-panel"><div class="adm-panel-head">上传升级包（.zip）</div><div class="adm-panel-body">'
            . '<form method="post" action="' . h(admin_url('upgrade/check')) . '" enctype="multipart/form-data">'
            . csrf_field()
            . '<div class="adm-form-item"><label for="pkg">升级包</label>'
            . '<input class="adm-input" type="file" id="pkg" name="pkg" accept=".zip" required></div>'
            . '<div class="adm-form-tip">上传后系统会校验包结构并读取版本号，确认无误后再执行升级。</div>'
            . '<div class="adm-form-actions"><button class="adm-btn primary" type="submit">校验升级包</button></div>'
            . '</form></div></div>';

        if (!class_exists('ZipArchive')) {
            $html .= '<div class="adm-alert error">当前 PHP 未安装 ZipArchive 扩展，无法使用在线升级。'
                . '请改用手动方式：解压升级包后覆盖除 <code>config/</code>、<code>data/</code> 以外的文件。</div>';
        }

        $html .= '<div class="adm-panel"><div class="adm-panel-head">手动升级说明</div><div class="adm-panel-body">'
            . '<ol style="margin:0;padding-left:20px;line-height:2;font-size:13px">'
            . '<li>下载最新版 <code>chatbbs-vX.zip</code> 并解压，得到 <code>chatbbs/</code> 目录</li>'
            . '<li>备份数据库（后台「数据备份」）与 <code>config/</code> 目录</li>'
            . '<li>把解压后的文件覆盖到站点根目录，<b>跳过</b> <code>config/</code> 与 <code>data/</code></li>'
            . '<li>访问后台确认版本号已更新；若页面异常，到「缓存管理」清理缓存</li>'
            . '</ol></div></div>';

        return $this->pageView('在线升级', 'upgrade', $html, [
            ['label' => '先去备份数据库', 'url' => admin_url('backup')],
        ]);
    }

    /**
     * 校验上传的升级包，并暂存到 data/upgrades/
     */
    public function upgradeCheck(): void
    {
        $dir = CB_DATA . '/upgrades';
        if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
            $this->back('升级暂存目录不可写：' . $dir, 'error');
        }
        if (empty($_FILES['pkg']) || (int)$_FILES['pkg']['error'] !== UPLOAD_ERR_OK) {
            $this->back('上传失败，请选择 .zip 升级包', 'error');
        }
        $tmp = (string)$_FILES['pkg']['tmp_name'];
        $name = basename((string)$_FILES['pkg']['name']);
        if (!str_ends_with(strtolower($name), '.zip')) {
            $this->back('只支持 .zip 升级包', 'error');
        }

        $target = $dir . '/' . date('Ymd-His') . '-' . preg_replace('/[^\w.\-]/', '_', $name);
        if (!@move_uploaded_file($tmp, $target)) {
            $this->back('文件保存失败，请检查 data/ 目录权限', 'error');
        }

        $info = self::inspectPackage($target);
        if ($info === null) {
            @unlink($target);
            $this->back('校验失败：不是有效的 ChatBBS 升级包（需包含 app/bootstrap.php）', 'error');
        }

        $html = '<div class="adm-panel"><div class="adm-panel-head">升级包校验结果</div><div class="adm-panel-body">'
            . '<dl class="adm-kv">'
            . '<dt>文件名</dt><dd>' . h($name) . '</dd>'
            . '<dt>包内版本</dt><dd><b style="color:var(--adm-primary)">' . h($info['version']) . '</b></dd>'
            . '<dt>当前版本</dt><dd>' . h(CB_VERSION) . '</dd>'
            . '<dt>根目录</dt><dd>' . h($info['root']) . '</dd>'
            . '<dt>文件数量</dt><dd>' . (int)$info['files'] . '</dd>'
            . '<dt>包大小</dt><dd>' . h(format_size((float)filesize($target))) . '</dd>'
            . '</dl>';

        if ($info['version'] === CB_VERSION) {
            $html .= '<div class="adm-alert warn">该升级包版本与当前版本相同，仍可继续（用于修复文件）。</div>';
        }
        $html .= '<div class="adm-alert error" style="margin-top:12px">'
            . '<b>再次确认：</b>请先备份数据库。升级将覆盖程序文件，'
            . '会自动跳过 <code>config/</code> 与 <code>data/</code> 目录。</div>';

        $html .= '<form method="post" action="' . h(admin_url('upgrade/apply')) . '" style="margin-top:14px">'
            . csrf_field()
            . '<input type="hidden" name="pkg" value="' . h(basename($target)) . '">'
            . '<label class="adm-checkbox" style="margin-bottom:12px">'
            . '<input type="checkbox" name="backup" value="1" checked> 升级前自动备份数据库</label>'
            . '<div><button class="adm-btn danger" type="submit" onclick="return confirm(\'确定开始升级？\')">开始升级</button> '
            . '<a class="adm-btn" href="' . h(admin_url('upgrade')) . '">取消</a></div>'
            . '</form></div></div>';

        echo $this->render('page', [
            'title' => '校验升级包',
            'current_key' => 'upgrade',
            'html' => $html,
        ]);
    }

    /**
     * 执行升级：备份（可选）→ 解压覆盖
     */
    public function upgradeApply(): void
    {
        if (!class_exists('ZipArchive')) {
            $this->back('服务器缺少 ZipArchive 扩展，无法自动升级', 'error');
        }
        $pkg = basename((string)Request::post('pkg', ''));
        if (!preg_match('/^[\w.\-]+\.zip$/', $pkg)) {
            $this->back('升级包不存在', 'error');
        }
        $file = CB_DATA . '/upgrades/' . $pkg;
        if (!is_file($file)) {
            $this->back('升级包不存在（可能已过期，请重新上传）', 'error');
        }
        $info = self::inspectPackage($file);
        if ($info === null) {
            $this->back('升级包校验失败', 'error');
        }

        // 可选：升级前自动备份数据库
        if (Request::bool('backup')) {
            self::quickBackup();
        }

        $zip = new \ZipArchive();
        if ($zip->open($file) !== true) {
            $this->back('无法打开升级包', 'error');
        }

        $root = $info['root'];           // 包内 chatbbs 目录前缀
        $skip = ['config/', 'data/', 'install/'];
        $updated = 0;
        $failed = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if ($name === false) {
                continue;
            }
            $name = str_replace('\\', '/', $name);
            if ($root !== '' && !str_starts_with($name, $root)) {
                continue;
            }
            $rel = substr($name, strlen($root));
            if ($rel === '' || str_ends_with($rel, '/')) {
                continue;
            }
            // 跳过配置与数据目录
            $skipThis = false;
            foreach ($skip as $s) {
                if (str_starts_with($rel, $s)) {
                    $skipThis = true;
                    break;
                }
            }
            if ($skipThis) {
                continue;
            }
            $dest = CB_ROOT . '/' . $rel;
            $dir = dirname($dest);
            if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
                $failed[] = $rel;
                continue;
            }
            $data = $zip->getFromIndex($i);
            if ($data === false) {
                $failed[] = $rel;
                continue;
            }
            if (@file_put_contents($dest, $data, LOCK_EX) === false) {
                $failed[] = $rel;
                continue;
            }
            $updated++;
        }
        $zip->close();

        // 清缓存
        \App\Core\Cache::flush();
        \App\Core\Cache::forget('settings');

        $this->log('在线升级', '文件数:' . $updated . ' 目标版本:' . $info['version']);

        // 清理升级包
        @unlink($file);

        if ($updated === 0) {
            $this->back('没有更新任何文件，请检查包结构', 'error');
        }
        $msg = '升级完成，已更新 ' . $updated . ' 个文件（目标版本 ' . $info['version'] . '）';
        if ($failed) {
            $msg .= '；' . count($failed) . ' 个文件写入失败（可能权限不足）';
        }
        $this->back($msg);
    }

    /**
     * 读取升级包信息：定位 chatbbs 根目录、读取版本号
     */
    private static function inspectPackage(string $file): ?array
    {
        if (!class_exists('ZipArchive')) {
            return null;
        }
        $zip = new \ZipArchive();
        if ($zip->open($file) !== true) {
            return null;
        }
        $root = '';
        $bootIndex = -1;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = str_replace('\\', '/', (string)$zip->getNameIndex($i));
            if (str_ends_with($name, 'app/bootstrap.php')) {
                $bootIndex = $i;
                $root = substr($name, 0, strlen($name) - strlen('app/bootstrap.php'));
                break;
            }
        }
        if ($bootIndex < 0) {
            $zip->close();
            return null;
        }
        $version = '';
        $boot = (string)$zip->getFromIndex($bootIndex);
        if (preg_match("/define\(\s*'CB_VERSION'\s*,\s*'([^']+)'\s*\)/", $boot, $m)) {
            $version = $m[1];
        }
        $zip->close();
        return [
            'root' => $root,
            'version' => $version ?: '未知',
            'files' => $zip->numFiles,
        ];
    }

    /**
     * 快速数据库备份（升级前自动调用）
     */
    private static function quickBackup(): void
    {
        $dir = CB_DATA . '/backups';
        if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
            return;
        }
        $file = $dir . '/auto-upgrade-' . date('Ymd-His') . '.sql';
        try {
            $pdo = Database::pdo();
            $out = "-- ChatBBS 升级前自动备份\n-- 时间：" . date('Y-m-d H:i:s') . "\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS = 0;\n\n";
            foreach ($pdo->query('SHOW TABLES') as $row) {
                $table = array_values($row)[0];
                $create = $pdo->query('SHOW CREATE TABLE `' . $table . '`')->fetch();
                $out .= "DROP TABLE IF EXISTS `" . $table . "`;\n" . (string)($create[1] ?? '') . ";\n\n";
                $stmt = $pdo->query('SELECT * FROM `' . $table . '`');
                while ($r = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                    $cols = array_map(static fn($c) => '`' . $c . '`', array_keys($r));
                    $vals = array_map(static fn($v) => $v === null ? 'NULL' : $pdo->quote((string)$v), array_values($r));
                    $out .= 'INSERT INTO `' . $table . '` (' . implode(',', $cols) . ') VALUES (' . implode(',', $vals) . ");\n";
                }
                $out .= "\n";
            }
            $out .= "SET FOREIGN_KEY_CHECKS = 1;\n";
            @file_put_contents($file, $out, LOCK_EX);
        } catch (\Throwable $e) {
            Logger::error('升级前自动备份失败：' . $e->getMessage());
        }
    }

    /* ================== 系统信息 ================== */
    public function system(): string
    {
        $info = \App\Admin\Controllers\DashboardController::sysInfo();
        $dirs = [
            'data' => CB_DATA,
            'data/cache' => CB_CACHE,
            'data/uploads' => CB_UPLOAD,
            'data/logs' => CB_LOG,
            'data/backups' => CB_DATA . '/backups',
            'config' => CB_CONFIG,
            'themes' => CB_THEME_DIR,
            'plugins' => CB_PLUGIN_DIR,
        ];
        $dirRows = '';
        foreach ($dirs as $label => $path) {
            $ok = is_dir($path) && is_writable($path);
            $dirRows .= '<tr><td>' . h($label) . '</td><td>' . h($path) . '</td>'
                . '<td style="width:110px">' . ($ok ? '<span class="adm-tag green">可写</span>' : '<span class="adm-tag red">不可写</span>') . '</td></tr>';
        }

        $html = '<div class="adm-grid-2">';
        $html .= '<div class="adm-panel"><div class="adm-panel-head">运行环境</div><div class="adm-panel-body"><dl class="adm-kv">'
            . '<dt>ChatBBS 版本</dt><dd>' . h((string)CB_VERSION) . '</dd>'
            . '<dt>PHP 版本</dt><dd>' . h((string)$info['php']) . '</dd>'
            . '<dt>运行系统</dt><dd>' . h((string)$info['os']) . '</dd>'
            . '<dt>Web 服务</dt><dd>' . h((string)$info['software']) . '</dd>'
            . '<dt>数据库版本</dt><dd>' . h((string)$info['mysql']) . '</dd>'
            . '<dt>上传限制</dt><dd>' . h((string)$info['upload_max']) . '</dd>'
            . '<dt>POST 限制</dt><dd>' . h((string)ini_get('post_max_size')) . '</dd>'
            . '<dt>内存限制</dt><dd>' . h((string)$info['memory_limit']) . '</dd>'
            . '<dt>最大执行时间</dt><dd>' . h((string)ini_get('max_execution_time')) . ' 秒</dd>'
            . '<dt>服务器时间</dt><dd>' . h(date('Y-m-d H:i:s')) . '</dd>'
            . '<dt>附件占用</dt><dd>' . h(format_size((float)$info['upload_size'])) . '</dd>'
            . '</dl></div></div>';

        $html .= '<div class="adm-panel"><div class="adm-panel-head">目录权限</div><div class="adm-panel-body tight">'
            . '<table class="adm-table"><thead><tr><th>目录</th><th>路径</th><th>状态</th></tr></thead><tbody>'
            . $dirRows . '</tbody></table></div></div>';
        $html .= '</div>';

        $html .= '<div class="adm-panel"><div class="adm-panel-head">PHP 扩展检查</div><div class="adm-panel-body tight">'
            . '<table class="adm-table"><thead><tr><th>扩展</th><th style="width:120px">状态</th><th>说明</th></tr></thead><tbody>';
        $exts = [
            'pdo_mysql' => '数据库连接必需',
            'mbstring' => '多字节字符串处理',
            'gd' => '图片处理（验证码/缩略图）',
            'curl' => '远程请求',
            'openssl' => '加密与安全通信',
            'json' => 'JSON 处理',
            'fileinfo' => '文件类型识别',
            'zip' => '压缩包处理',
        ];
        foreach ($exts as $ext => $desc) {
            $loaded = extension_loaded($ext);
            $html .= '<tr><td>' . h($ext) . '</td><td>' . ($loaded
                    ? '<span class="adm-tag green">已启用</span>' : '<span class="adm-tag gray">未安装</span>') . '</td>'
                . '<td>' . h($desc) . '</td></tr>';
        }
        $html .= '</tbody></table></div></div>';

        // 开发团队 / 关于
        $team = \App\Admin\BaseAdmin::teamInfo();
        $html .= '<div class="adm-panel"><div class="adm-panel-head">关于 ChatBBS</div><div class="adm-panel-body">'
            . '<div style="text-align:center;padding:6px 0 14px">'
            . '<div style="font-size:22px;font-weight:700;letter-spacing:.5px">' . h($team['product']) . '</div>'
            . '<div style="font-size:13px;color:var(--adm-text-2);margin-top:6px">' . h($team['slogan']) . '</div>'
            . '</div>'
            . '<dl class="adm-kv">'
            . '<dt>当前版本</dt><dd>' . h(CB_VERSION) . '</dd>'
            . '<dt>技术栈</dt><dd>' . h($team['stack']) . '</dd>'
            . '<dt>架构与开发（beta21 及之前）</dt><dd>' . h($team['dev']) . '</dd>'
            . '<dt>开发与维护（beta22 起）</dt><dd>' . h($team['dev_current']) . '</dd>'
            . '<dt>产品与测试</dt><dd>' . h($team['product_owner']) . '</dd>'
            . '<dt>开源协议</dt><dd>' . h($team['license']) . '</dd>'
            . '</dl>'
            . '<div class="adm-form-tip" style="margin-top:12px">' . h($team['note']) . '</div>'
            . '</div></div>';

        return $this->pageView('系统信息', 'system', $html);
    }
}
