<?php

declare(strict_types=1);

namespace App\Admin\Controllers;

use App\Admin\BaseAdmin;

/**
 * 圈子（兴趣小组）管理
 */
class CircleController extends BaseAdmin
{
    public function index(): string
    {
        $html = '<div class="adm-panel"><div class="adm-panel-head">圈子管理</div><div class="adm-panel-body">'
            . '<div class="adm-alert info">圈子功能正在开发中，后续版本将支持创建兴趣小组、加入 / 退出、圈内发帖等。</div>'
            . '</div></div>';
        return $this->pageView('圈子管理', 'circles', $html);
    }
}
