<?php

declare(strict_types=1);

namespace App\Admin\Controllers;

use App\Admin\AdminUI;
use App\Admin\BaseAdmin;
use App\Core\Database;
use App\Core\Request;
use App\Core\Settings;
use App\Models\ShopItem;

/**
 * 商城（商品）管理
 */
class ShopController extends BaseAdmin
{
    public function index(): string
    {
        $rows = ShopItem::all(false);
        $editId = Request::int('edit');
        $edit = $editId > 0 ? ShopItem::find($editId) : null;

        $html = '<div class="adm-alert info">商城商品由管理员或授权用户发布；前台 /shops 可浏览。</div>';
        $html .= AdminUI::table([
            'primary' => 'id',
            'rows' => $rows,
            'columns' => [
                ['key' => 'id', 'label' => 'ID', 'width' => '60px'],
                ['key' => 'title', 'label' => '商品'],
                ['key' => 'price', 'label' => '价格', 'width' => '100px', 'render' => fn($r) => money((float)$r['price'])],
                ['key' => 'stock', 'label' => '库存', 'width' => '80px'],
                ['key' => 'username', 'label' => '发布者', 'width' => '120px'],
                ['key' => 'status', 'label' => '状态', 'width' => '80px', 'render' => fn($r) => (int)$r['status'] === 1
                    ? '<span class="adm-tag green">上架</span>' : '<span class="adm-tag gray">下架</span>'],
                ['key' => 'created_at', 'label' => '发布时间', 'width' => '140px', 'render' => fn($r) => format_date($r['created_at'])],
            ],
            'actions' => [
                ['label' => '编辑', 'url' => admin_url('shops?edit={id}')],
                ['label' => '删除', 'url' => admin_url('shops/delete/{id}'), 'type' => 'post', 'class' => 'danger', 'confirm' => '确定删除该商品？'],
            ],
        ]);

        $html .= '<div class="adm-panel"><div class="adm-panel-head">' . ($edit ? '编辑商品' : '新增商品') . '</div><div class="adm-panel-body">'
            . AdminUI::form([
                'action' => admin_url('shops/save'),
                'data' => $edit ?? [],
                'hidden' => ['id' => $edit['id'] ?? 0],
                'submit' => '保存商品',
                'fields' => [
                    ['name' => 'title', 'label' => '商品名称'],
                    ['name' => 'description', 'label' => '商品描述', 'type' => 'textarea'],
                    ['name' => 'price', 'label' => '价格', 'type' => 'number', 'step' => '0.01'],
                    ['name' => 'stock', 'label' => '库存', 'type' => 'number'],
                    ['name' => 'image', 'label' => '商品图片', 'type' => 'image'],
                    ['name' => 'status', 'label' => '上架', 'type' => 'switch'],
                ],
            ]) . '</div></div>';

        $html .= '<div class="adm-panel"><div class="adm-panel-head">商城设置</div><div class="adm-panel-body">'
            . AdminUI::form([
                'action' => admin_url('shops/settings'),
                'data' => Settings::all(),
                'submit' => '保存商城设置',
                'fields' => [
                    ['name' => 'shop_enable', 'label' => '启用商城', 'type' => 'switch'],
                    ['name' => 'shop_user_post', 'label' => '允许普通用户发布商品', 'type' => 'switch',
                        'tip' => '关闭时只有管理员可以发布商品'],
                ],
            ]) . '</div></div>';

        return $this->pageView('商品管理', 'shops', $html);
    }

    public function save(): void
    {
        $id = Request::int('id');
        $title = $this->pure('title', 120);
        if ($title === '') {
            $this->back('商品名称不能为空', 'error');
        }
        $data = [
            'title' => $title,
            'description' => $this->pure('description', 5000),
            'price' => max(0, (float)Request::input('price', 0)),
            'stock' => max(0, Request::int('stock', 0)),
            'image' => $this->pure('image', 500),
            'status' => Request::bool('status') ? 1 : 0,
        ];
        if ($id > 0) {
            ShopItem::update($id, $data);
            $this->log('编辑商品', $title);
        } else {
            $op = \App\Core\Auth::user();
            $data['user_id'] = (int)($op['id'] ?? 0);
            $data['username'] = (string)($op['username'] ?? '');
            $id = ShopItem::create($data);
            $this->log('新增商品', $title);
        }
        $this->back('商品已保存');
    }

    public function delete(int $id): void
    {
        ShopItem::delete($id);
        $this->log('删除商品', 'ID:' . $id);
        $this->back('商品已删除');
    }

    public function settings(): void
    {
        Settings::setMany([
            'shop_enable' => Request::bool('shop_enable') ? 1 : 0,
            'shop_user_post' => Request::bool('shop_user_post') ? 1 : 0,
        ]);
        \App\Core\Cache::forget('settings');
        $this->log('修改商城设置');
        $this->back('商城设置已保存');
    }
}
