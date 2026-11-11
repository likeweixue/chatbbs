<?php

declare(strict_types=1);

namespace App\Admin\Controllers;

use App\Admin\BaseAdmin;

/**
 * 商城（商品）管理
 */
class ShopController extends BaseAdmin
{
    public function index(): string
    {
        $html = '<div class="adm-panel"><div class="adm-panel-head">商品管理</div><div class="adm-panel-body">'
            . '<div class="adm-alert info">商城功能正在开发中，后续版本将支持发布商品、库存 / 价格、以及发布权限控制。</div>'
            . '</div></div>';
        return $this->pageView('商品管理', 'shops', $html);
    }
}
