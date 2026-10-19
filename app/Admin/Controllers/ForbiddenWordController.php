<?php

declare(strict_types=1);

namespace App\Admin\Controllers;

use App\Admin\AdminUI;
use App\Admin\BaseAdmin;
use App\Core\Database;
use App\Core\Request;
use App\Models\Forbidden;

/**
 * 违禁词库管理
 *
 * 与「敏感词」区分：敏感词只做替换 / 转审核，违禁词直接禁止发布。
 */
class ForbiddenWordController extends BaseAdmin
{
    public function index(): string
    {
        $rows = Database::fetchAll(
            'SELECT * FROM ' . Database::table('forbidden_words') . ' ORDER BY hits DESC, id DESC'
        );
        $total = count($rows);
        $enabledCount = 0;
        $totalHits = 0;
        foreach ($rows as $r) {
            $enabledCount += (int)$r['enabled'] === 1 ? 1 : 0;
            $totalHits += (int)$r['hits'];
        }

        $html = '<div class="adm-panel"><div class="adm-panel-head">违禁词库'
            . '<span class="adm-hint" style="margin-left:10px">共 ' . $total . ' 条 · 启用 ' . $enabledCount
            . ' 条 · 累计拦截 ' . $totalHits . ' 次</span></div><div class="adm-panel-body">';

        $html .= AdminUI::form([
            'action' => admin_url('forbidden-words/add'),
            'submit' => '批量添加',
            'fields' => [
                ['name' => 'words', 'label' => '添加违禁词', 'type' => 'textarea', 'rows' => 5,
                    'placeholder' => '每行一个，或用英文逗号分隔',
                    'tip' => '命中的词会直接阻止发布，并向用户提示具体是哪个词。已有词条会被跳过。'],
                ['name' => 'remark', 'label' => '备注（可选）', 'type' => 'text', 'placeholder' => '如：广告、违禁品'],
            ],
        ]);

        $html .= '<div style="margin:16px 0 8px;font-weight:600">已有词条</div>';
        if ($total === 0) {
            $html .= '<div class="adm-empty">还没有违禁词，先在上方添加几个吧。</div>';
        } else {
            $html .= '<table class="adm-table"><thead><tr>'
                . '<th style="width:60px">ID</th><th>违禁词</th><th>备注</th>'
                . '<th style="width:90px">拦截次数</th><th style="width:80px">状态</th>'
                . '<th style="width:150px">操作</th></tr></thead><tbody>';
            foreach ($rows as $r) {
                $on = (int)$r['enabled'] === 1;
                $html .= '<tr>'
                    . '<td>' . (int)$r['id'] . '</td>'
                    . '<td><strong>' . h((string)$r['word']) . '</strong></td>'
                    . '<td>' . h((string)$r['remark']) . '</td>'
                    . '<td>' . (int)$r['hits'] . '</td>'
                    . '<td>' . ($on ? '<span class="adm-tag green">启用</span>' : '<span class="adm-tag">停用</span>') . '</td>'
                    . '<td>'
                    . '<a class="adm-btn sm" href="' . h(admin_url('forbidden-words/toggle/' . (int)$r['id'])) . '">'
                    . ($on ? '停用' : '启用') . '</a> '
                    . '<a class="adm-btn sm danger" href="' . h(admin_url('forbidden-words/delete/' . (int)$r['id'])) . '"'
                    . ' onclick="return confirm(\'确定删除该违禁词？\')">删除</a>'
                    . '</td></tr>';
            }
            $html .= '</tbody></table>';
        }

        $html .= '</div></div>';

        return $this->pageView('违禁词库', 'forbidden_words', $html);
    }

    public function add(): void
    {
        $raw = (string)Request::post('words', '');
        $remark = trim((string)Request::post('remark', ''));
        // 兼容换行与中英文逗号
        $raw = str_replace(["\r", "\n", '，'], ["", ",", ','], $raw);
        $items = array_values(array_filter(array_map('trim', explode(',', $raw))));
        if (!$items) {
            $this->back('请先填写要添加的违禁词');
        }

        $added = 0;
        $skipped = 0;
        foreach ($items as $w) {
            if (mb_strlen($w) > 100) {
                continue;
            }
            $exists = Database::value(
                'SELECT id FROM ' . Database::table('forbidden_words') . ' WHERE word = ?',
                [$w]
            );
            if ($exists) {
                $skipped++;
                continue;
            }
            Database::insert('forbidden_words', [
                'word' => $w,
                'remark' => $remark,
                'enabled' => 1,
            ]);
            $added++;
        }
        Forbidden::clearCache();
        $this->log('添加违禁词', '新增 ' . $added . ' 条');
        $this->back('已添加 ' . $added . ' 条' . ($skipped ? '，跳过重复 ' . $skipped . ' 条' : ''));
    }

    public function toggle(int $id): void
    {
        $row = Database::fetch(
            'SELECT * FROM ' . Database::table('forbidden_words') . ' WHERE id = ?',
            [$id]
        );
        if (!$row) {
            $this->back('词条不存在');
        }
        Database::update(
            'forbidden_words',
            ['enabled' => (int)$row['enabled'] === 1 ? 0 : 1],
            'id = ?',
            ['id' => $id]
        );
        Forbidden::clearCache();
        $this->log('切换违禁词状态', (string)$row['word']);
        $this->back('已更新');
    }

    public function delete(int $id): void
    {
        $row = Database::fetch(
            'SELECT * FROM ' . Database::table('forbidden_words') . ' WHERE id = ?',
            [$id]
        );
        Database::delete('forbidden_words', 'id = ?', ['id' => $id]);
        Forbidden::clearCache();
        $this->log('删除违禁词', (string)($row['word'] ?? $id));
        $this->back('已删除');
    }
}
