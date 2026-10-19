<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Settings;
use App\Models\Checkin;
use App\Models\Tag;
use App\Models\Thread;
use App\Models\User;
use App\Services\Stats;

class CheckinController extends BaseController
{
    public function index(): string
    {
        if (!(int)Settings::get('checkin_enable', 1)) {
            return $this->render('errors/notice', ['title' => '功能未开启', 'message' => '签到功能已关闭。']);
        }
        $user = $this->user();
        $uid = $user ? (int)$user['id'] : 0;
        return $this->render('checkin/index', [
            'title' => '签到',
            'checked' => $uid > 0 ? Checkin::today($uid) !== null : false,
            'month' => (string)Request::get('month', date('Y-m')),
            'records' => $uid > 0 ? Checkin::month($uid, (string)Request::get('month', date('Y-m'))) : [],
            'continue_days' => $uid > 0 ? Checkin::continueDays($uid) : 0,
            'total_days' => $uid > 0 ? Checkin::totalDays($uid) : 0,
            'cards' => $uid > 0 ? Checkin::cards($uid) : 0,
            'preview' => Checkin::preview($uid > 0 ? Checkin::continueDays($uid) : 0),
            'rank' => Checkin::rank(10),
            'stats_site' => Stats::forum(),
            'hot_tags' => Tag::hot(15),
            'new_users' => User::newest(8),
            'latest_threads' => Thread::latest(8),
        ]);
    }

    public function do(): void
    {
        $this->requireLogin();
        if (!(int)Settings::get('checkin_enable', 1)) {
            $this->fail('签到功能未开启');
        }
        if (!Auth::can('checkin.do')) {
            $this->fail('没有签到权限');
        }
        $res = Checkin::do($this->userId());
        if (!$res['ok']) {
            $this->fail($res['msg']);
        }
        \App\Services\Level::addExp($this->userId(), \App\Services\Level::EXP_CHECKIN);
        $this->success($res, $res['msg']);
    }

    public function makeup(): void
    {
        $this->requireLogin();
        $day = (string)Request::post('day', '');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $day)) {
            $this->fail('日期格式不正确');
        }
        $res = Checkin::makeup($this->userId(), $day);
        $res['ok'] ? $this->success($res, $res['msg']) : $this->fail($res['msg']);
    }

    /**
     * 购买补签卡
     */
    public function buyCard(): void
    {
        $this->requireLogin();
        $price = (float)Settings::get('card_price', 20);
        $uid = $this->userId();
        if (\App\Models\Currency::balance($uid) < $price) {
            $this->fail('余额不足');
        }
        \App\Models\Currency::change($uid, -$price, 'buy', '购买补签卡');
        \App\Core\Database::insert('checkin_cards', [
            'user_id' => $uid, 'created_at' => date('Y-m-d H:i:s'),
        ]);
        $this->success(null, '购买成功');
    }
}
