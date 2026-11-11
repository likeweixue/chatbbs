<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Settings;
use App\Models\ShopItem;

/**
 * 前台商城
 */
class ShopController extends BaseController
{
    public function index(): string
    {
        if ((int)Settings::get('shop_enable', 1) !== 1) {
            return $this->render('errors/notice', ['title' => '商城未开启', 'message' => '商城功能已关闭。']);
        }
        $items = ShopItem::all();
        return $this->render('shop/index', [
            'title' => L('商城'),
            'items' => $items,
            'can_post' => $this->canPost(),
        ]);
    }

    public function view(int $id): string
    {
        $item = ShopItem::find($id);
        if (!$item || (int)$item['status'] !== 1) {
            \App\Core\Router::notFound();
            exit;
        }
        return $this->render('shop/view', [
            'title' => (string)$item['title'],
            'item' => $item,
        ]);
    }

    public function createForm(): string
    {
        $this->requireLogin();
        if (!$this->canPost()) {
            $this->back('你没有发布商品的权限');
        }
        return $this->render('shop/create', ['title' => '发布商品']);
    }

    public function store(): void
    {
        $this->requireLogin();
        if (!$this->canPost()) {
            $this->back('你没有发布商品的权限');
        }
        $title = trim((string)Request::post('title', ''));
        $desc = trim((string)Request::post('description', ''));
        if ($title === '' || mb_strlen($title) > 120) {
            $this->back('商品名称不能为空且不超过 120 字');
        }
        $user = $this->user();
        $id = ShopItem::create([
            'user_id' => (int)$user['id'],
            'username' => (string)$user['username'],
            'title' => $title,
            'description' => mb_substr($desc, 0, 5000),
            'price' => max(0, (float)Request::post('price', 0)),
            'stock' => max(0, Request::int('stock', 0)),
            'image' => trim((string)Request::post('image', '')),
            'status' => 1,
        ]);
        \App\Core\Session::flash('success', '商品已发布');
        \redirect('shop/' . $id);
    }

    private function canPost(): bool
    {
        if (Auth::isAdmin()) {
            return true;
        }
        return Auth::check() && (int)Settings::get('shop_user_post', 0) === 1;
    }
}
