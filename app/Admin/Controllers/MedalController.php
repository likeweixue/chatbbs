<?php

declare(strict_types=1);

namespace App\Admin\Controllers;

use App\Admin\AdminUI;
use App\Admin\BaseAdmin;
use App\Core\Request;
use App\Models\Medal;

class MedalController extends BaseAdmin
{
    public function index(): string
    {
        $rows = Medal::all();
        $html = AdminUI::table([
            'primary' => 'id',
            'rows' => $rows,
            'columns' => [
                ['key' => 'id', 'label' => 'ID', 'width' => '60px'],
                ['key' => 'image', 'label' => '图片', 'width' => '70px', 'render' => fn($r) => !empty($r['image'])
                    ? '<img src="' . h((string)$r['image']) . '" alt="" style="width:32px;height:32px;object-fit:cover;border-radius:6px">' : '—'],
                ['key' => 'name', 'label' => '勋章名称', 'render' => fn($r) => '<b>' . h((string)$r['name']) . '</b>'],
                ['key' => 'description', 'label' => '说明'],
                ['key' => 'visible', 'label' => '显示', 'width' => '70px', 'render' => fn($r) => (int)$r['visible'] === 1 ? '<span class="adm-tag green">显示</span>' : '<span class="adm-tag gray">隐藏</span>'],
                ['key' => 'sort_order', 'label' => '排序', 'width' => '60px'],
            ],
            'actions' => [
                ['label' => '编辑', 'url' => admin_url('medals?edit={id}')],
                ['label' => '删除', 'url' => admin_url('medals/delete/{id}'), 'type' => 'post', 'class' => 'danger', 'confirm' => '确定删除该勋章？'],
            ],
        ]);
        $edit = null;
        $editId = Request::int('edit');
        if ($editId > 0) {
            $edit = Medal::find($editId);
        }
        $html .= '<div class="adm-panel"><div class="adm-panel-head">' . ($edit ? '编辑勋章' : '新增勋章') . '</div><div class="adm-panel-body">'
            . AdminUI::form([
                'action' => admin_url('medals/save'),
                'data' => $edit ?? [],
                'hidden' => ['id' => $edit['id'] ?? 0],
                'submit' => '保存勋章',
                'fields' => [
                    ['name' => 'name', 'label' => '勋章名称', 'placeholder' => '如：元老'],
                    ['name' => 'image', 'label' => '勋章图片', 'type' => 'image', 'tip' => '支持本地上传或图床地址'],
                    ['name' => 'description', 'label' => '说明'],
                    ['name' => 'sort_order', 'label' => '排序', 'type' => 'number'],
                    ['name' => 'visible', 'label' => '显示', 'type' => 'switch'],
                ],
            ]) . '</div></div>';
        return $this->pageView('勋章管理', 'medals', $html);
    }

    public function save(): void
    {
        $id = Request::int('id');
        $image = trim((string)Request::post('image', ''));
        $file = Request::file('image_file');
        if ($file && !empty($file['name']) && (int)($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            [$ok, $res] = \App\Core\Upload::save($file, 'medals', (int)(\App\Core\Auth::id() ?? 0));
            if (!$ok) {
                $this->back('图片上传失败：' . (string)$res, 'error');
            }
            $image = (string)$res['url'];
        }
        $name = $this->pure('name', 80);
        if ($name === '') {
            $this->back('勋章名称不能为空', 'error');
        }
        $data = [
            'name' => $name,
            'image' => mb_substr($image, 0, 500),
            'description' => $this->pure('description', 255),
            'sort_order' => Request::int('sort_order'),
            'visible' => Request::bool('visible') ? 1 : 0,
        ];
        if ($id > 0) {
            Medal::update($id, $data);
        } else {
            Medal::create($data);
        }
        $this->log('保存勋章', $name);
        $this->back('勋章已保存');
    }

    public function delete(int $id): void
    {
        \App\Core\Database::delete('user_medals', 'medal_id = ?', ['id' => $id]);
        Medal::delete($id);
        $this->log('删除勋章', 'ID:' . $id);
        $this->back('勋章已删除');
    }
}
