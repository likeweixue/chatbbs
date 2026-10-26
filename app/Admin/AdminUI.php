<?php

declare(strict_types=1);

namespace App\Admin;

use App\Core\View;

/**
 * 后台 UI 构件：表格、表单、筛选
 */
class AdminUI
{
    /**
     * 数据表格
     * $columns: [['key'=>'id','label'=>'ID','width'=>'60px','render'=>fn], ...]
     */
    public static function table(array $config): string
    {
        $columns = $config['columns'] ?? [];
        $rows = $config['rows'] ?? [];
        $primary = $config['primary'] ?? 'id';
        $bulk = $config['bulk'] ?? [];
        $rowActions = $config['actions'] ?? [];

        $html = '';
        if ($bulk) {
            $html .= '<form method="post" id="bulk-form" action="' . h($config['bulk_action'] ?? '') . '">'
                . csrf_field()
                . '<div class="adm-toolbar">'
                . '<div class="flex gap-8 items-center">';
            if (!empty($config['bulk_extra'])) {
                $html .= $config['bulk_extra'];
            }
            foreach ($bulk as $b) {
                $html .= '<button class="adm-btn ' . h($b['class'] ?? '') . '" type="submit" name="act" value="' . h($b['value']) . '"'
                    . (($b['confirm'] ?? false) ? ' onclick="return confirm(\'' . h((string)$b['confirm']) . '\')"' : '')
                    . '>' . h($b['label']) . '</button>';
            }
            $html .= '</div></div>';
        }

        $html .= '<div class="adm-table-wrap"><table class="adm-table"><thead><tr>';
        if ($bulk) {
            $html .= '<th style="width:40px"><input type="checkbox" data-check-all></th>';
        }
        foreach ($columns as $c) {
            $html .= '<th' . (isset($c['width']) ? ' style="width:' . h((string)$c['width']) . '"' : '') . '>' . h($c['label']) . '</th>';
        }
        if ($rowActions) {
            $html .= '<th style="width:160px">操作</th>';
        }
        $html .= '</tr></thead><tbody>';

        if (!$rows) {
            $colCount = count($columns) + ($bulk ? 1 : 0) + ($rowActions ? 1 : 0);
            $html .= '<tr><td colspan="' . $colCount . '" class="adm-empty">暂无数据</td></tr>';
        }
        foreach ($rows as $row) {
            $html .= '<tr>';
            if ($bulk) {
                $html .= '<td><input type="checkbox" name="ids[]" value="' . (int)$row[$primary] . '"></td>';
            }
            foreach ($columns as $c) {
                $key = $c['key'];
                if (isset($c['render']) && is_callable($c['render'])) {
                    $val = $c['render']($row);
                } else {
                    $val = h((string)($row[$key] ?? ''));
                }
                $html .= '<td>' . $val . '</td>';
            }
            if ($rowActions) {
                $html .= '<td><div class="adm-row-actions">';
                foreach ($rowActions as $a) {
                    $url = str_replace('{id}', (string)$row[$primary], (string)($a['url'] ?? '#'));
                    if (($a['type'] ?? 'link') === 'post') {
                        $html .= '<form method="post" action="' . h($url) . '" style="display:inline">'
                            . csrf_field();
                        foreach (($a['hidden'] ?? []) as $hName => $rowKey) {
                            $html .= '<input type="hidden" name="' . h((string)$hName) . '" value="'
                                . h((string)($row[$rowKey] ?? '')) . '">';
                        }
                        $html .= '<button class="adm-link ' . h($a['class'] ?? '') . '" type="submit"'
                            . (($a['confirm'] ?? false) ? ' onclick="return confirm(\'' . h((string)$a['confirm']) . '\')"' : '')
                            . '>' . h($a['label']) . '</button></form>';
                    } else {
                        $html .= '<a class="adm-link" href="' . h($url) . '"'
                            . (($a['confirm'] ?? false) ? ' onclick="return confirm(\'' . h((string)$a['confirm']) . '\')"' : '')
                            . '>' . h($a['label']) . '</a>';
                    }
                }
                $html .= '</div></td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table></div>';

        if ($bulk) {
            $html .= '</form>';
        }
        return $html;
    }

    /**
     * 表单渲染
     * $fields: [['name','label','type','options','tip','value','placeholder']]
     */
    public static function form(array $config): string
    {
        $fields = $config['fields'] ?? [];
        $data = $config['data'] ?? [];
        // 含 image / file 类型字段时自动切换为 multipart，保证上传可用
        $needFile = false;
        foreach ($fields as $f) {
            if (in_array(($f['type'] ?? 'text'), ['image', 'file'], true)) {
                $needFile = true;
                break;
            }
        }
        $html = '<form method="post" action="' . h($config['action'] ?? '') . '"'
            . (($config['multipart'] ?? false) || $needFile ? ' enctype="multipart/form-data"' : '')
            . ' class="adm-form">';
        $html .= csrf_field();
        if (!empty($config['hidden'])) {
            foreach ($config['hidden'] as $k => $v) {
                $html .= '<input type="hidden" name="' . h((string)$k) . '" value="' . h((string)$v) . '">';
            }
        }
        foreach ($fields as $f) {
            $name = $f['name'];
            $value = $f['value'] ?? ($data[$name] ?? '');
            // 编辑器 / 多行文本占整行，避免挤在右侧变窄
            $block = in_array(($f['type'] ?? 'text'), ['editor', 'textarea', 'checkboxes'], true);
            $html .= '<div class="adm-form-item' . ($block ? ' adm-form-item--block' : '') . '">';
            $html .= '<label for="f-' . h($name) . '">' . h($f['label']) . '</label>';
            $html .= '<div class="adm-field">';
            $html .= self::field($f, $value, $data);
            if (!empty($f['tip'])) {
                $html .= '<div class="adm-form-tip">' . h($f['tip']) . '</div>';
            }
            $html .= '</div></div>';
        }
        $html .= '<div class="adm-form-actions">';
        $html .= '<button class="adm-btn primary" type="submit">' . h($config['submit'] ?? '保存') . '</button>';
        if (!empty($config['back'])) {
            $html .= '<a class="adm-btn" href="' . h($config['back']) . '">返回</a>';
        }
        $html .= '</div></form>';
        return $html;
    }

    private static function field(array $f, $value, array $data = []): string
    {
        $name = (string)$f['name'];
        $type = $f['type'] ?? 'text';
        $id = 'f-' . $name;
        $ph = isset($f['placeholder']) ? ' placeholder="' . h((string)$f['placeholder']) . '"' : '';
        switch ($type) {
            case 'textarea':
                $rows = isset($f['rows']) ? ' rows="' . max(2, (int)$f['rows']) . '"' : ' rows="4"';
                return '<textarea class="adm-input" id="' . $id . '" name="' . h($name) . '"' . $rows . $ph . '>' . h((string)$value) . '</textarea>';
            case 'select':
                $opts = '';
                foreach (($f['options'] ?? []) as $k => $v) {
                    $sel = (string)$value === (string)$k ? ' selected' : '';
                    $opts .= '<option value="' . h((string)$k) . '"' . $sel . '>' . h((string)$v) . '</option>';
                }
                return '<select class="adm-input" id="' . $id . '" name="' . h($name) . '" style="width:260px;max-width:100%">' . $opts . '</select>';
            case 'switch':
                $on = ((int)$value === 1);
                return '<label class="adm-switch"><input type="checkbox" name="' . h($name) . '" value="1"'
                    . ($on ? ' checked' : '') . '><span></span></label>';
            case 'switch_group':
                $out = '<div class="adm-switch-row-group">';
                foreach (($f['items'] ?? []) as $item) {
                    $itemName = (string)($item['name'] ?? '');
                    if ($itemName === '') {
                        continue;
                    }
                    $on = (int)($data[$itemName] ?? 0) === 1;
                    $out .= '<label class="adm-checkbox"><input type="checkbox" name="' . h($itemName) . '" value="1"'
                        . ($on ? ' checked' : '') . '> ' . h((string)($item['label'] ?? $itemName)) . '</label>';
                }
                return $out . '</div>';
            case 'number':
                return '<input class="adm-input" type="number" step="' . h((string)($f['step'] ?? '1')) . '" id="' . $id . '" name="' . h($name) . '" value="' . h((string)$value) . '"' . $ph . ' style="width:160px;max-width:100%">';
            case 'color':
                return '<input class="adm-input" type="color" id="' . $id . '" name="' . h($name) . '" value="' . h((string)($value ?: '#4D698E')) . '" style="width:64px">';
            case 'password':
                return '<input class="adm-input" type="password" id="' . $id . '" name="' . h($name) . '"' . $ph . ' autocomplete="new-password">';
            case 'checkboxes':
                $out = '<div class="adm-checkboxes">';
                $selected = is_array($value) ? $value : array_filter(array_map('trim', explode(',', (string)$value)));
                foreach (($f['options'] ?? []) as $k => $v) {
                    $chk = in_array((string)$k, array_map('strval', $selected), true) ? ' checked' : '';
                    $out .= '<label class="adm-checkbox"><input type="checkbox" name="' . h($name) . '[]" value="' . h((string)$k) . '"' . $chk . '> ' . h((string)$v) . '</label>';
                }
                return $out . '</div>';
            case 'editor':
                return '<div id="adm-editor-' . h($name) . '" data-editor data-input="' . h($name) . '" data-placeholder="请输入内容…"></div>'
                    . '<input type="hidden" name="' . h($name) . '" id="' . h($name) . '" value="' . h((string)$value) . '">';
            case 'image':
                return '<div class="adm-input-group">'
                    . '<input class="adm-input" type="text" id="' . $id . '" name="' . h($name) . '" value="' . h((string)$value) . '"' . $ph . '>'
                    . '<input class="adm-input adm-input-file" type="file" name="' . h($name) . '_file"'
                    . ' accept="' . h((string)($f['accept'] ?? 'image/*')) . '">'
                    . '</div>'
                    . (!empty($value)
                        ? '<div class="adm-form-tip"><img src="' . h((string)$value) . '" alt="" style="max-height:32px;max-width:120px;vertical-align:middle;margin-right:6px">当前预览</div>'
                        : '');
            case 'static':
                return '<div class="adm-static">' . h((string)$value) . '</div>';
            default:
                return '<input class="adm-input" type="text" id="' . $id . '" name="' . h($name) . '" value="' . h((string)$value) . '"' . $ph . '>';
        }
    }

    /**
     * 筛选条
     */
    public static function filter(array $config): string
    {
        $html = '<form class="adm-toolbar" method="get" action="' . h($config['action'] ?? '') . '"><div class="flex gap-8 items-center" style="flex-wrap:wrap">';
        foreach ($config['fields'] as $f) {
            $name = $f['name'];
            $value = $f['value'] ?? '';
            if (($f['type'] ?? 'text') === 'select') {
                $html .= '<select class="adm-input" style="width:auto" name="' . h($name) . '">';
                $html .= '<option value="">' . h($f['label']) . '</option>';
                foreach ($f['options'] as $k => $v) {
                    $sel = (string)$value === (string)$k ? ' selected' : '';
                    $html .= '<option value="' . h((string)$k) . '"' . $sel . '>' . h((string)$v) . '</option>';
                }
                $html .= '</select>';
            } else {
                $html .= '<input class="adm-input" style="width:auto" name="' . h($name) . '" value="' . h((string)$value) . '" placeholder="' . h($f['label']) . '">';
            }
        }
        $html .= '<button class="adm-btn primary" type="submit">筛选</button>';
        $html .= '<a class="adm-btn" href="' . h($config['action'] ?? '') . '">重置</a>';
        $html .= '</div></form>';
        return $html;
    }

    /**
     * 统计卡片
     */
    public static function statCards(array $items): string
    {
        $html = '<div class="adm-stats">';
        foreach ($items as $it) {
            $html .= '<div class="adm-stat">'
                . '<div class="adm-stat__label">' . h($it['label']) . '</div>'
                . '<div class="adm-stat__value" ' . (!empty($it['color']) ? 'style="color:' . h($it['color']) . '"' : '') . '>' . h((string)$it['value']) . '</div>'
                . (!empty($it['sub']) ? '<div class="adm-stat__sub">' . h($it['sub']) . '</div>' : '')
                . '</div>';
        }
        return $html . '</div>';
    }

    public static function pager(int $total, int $perPage, int $page, string $base): string
    {
        return View::paginate($total, $perPage, $page, $base);
    }
}
