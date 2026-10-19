<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Logger;
use App\Core\Request;
use App\Core\Response;
use App\Core\Upload;
use App\Models\Attachment;
use App\Models\Currency;

class AttachmentController extends BaseController
{
    /**
     * 附件下载（走权限校验，私人空间不直接暴露 URL）
     */
    public function download(int $id): void
    {
        $att = Attachment::find($id);
        if (!$att) {
            http_response_code(404);
            echo '附件不存在';
            exit;
        }
        $user = $this->user();
        $isAdmin = Auth::isAdmin();

        // 私人空间：仅本人与管理员
        if ((string)$att['space'] === 'private'
            && (!$user || ((int)$att['user_id'] !== (int)$user['id'] && !$isAdmin))) {
            http_response_code(403);
            echo '无权访问该文件';
            exit;
        }

        [$ok, $msg, $need] = Attachment::canDownload($att, $user);
        if (!$ok) {
            if (($need ?? '') === 'pay' && Request::isAjax()) {
                Response::fail($msg, ['pay' => true, 'fee' => (float)$att['fee'], 'id' => $id]);
            }
            if (($need ?? '') === 'pay' && $user) {
                \redirect('attachment/' . $id . '/buy');
            }
            http_response_code(403);
            echo h((string)$msg);
            exit;
        }

        $file = Upload::filePath((string)$att['space'], (string)$att['path']);
        if (!is_file($file)) {
            http_response_code(404);
            echo '文件已被清理';
            exit;
        }
        Attachment::recordDownload($id, (int)($user['id'] ?? 0), (float)$att['fee'] > 0 ? (float)$att['fee'] : 0);
        if ($att['space'] === 'private') {
            Logger::admin('下载私人附件', '#' . $id . ' ' . $att['name']);
        }
        Response::download($file, (string)$att['name']);
    }

    /**
     * 付费附件购买页
     */
    public function buyForm(int $id): string
    {
        $this->requireLogin();
        $att = Attachment::find($id);
        if (!$att) {
            \App\Core\Router::notFound();
            exit;
        }
        $user = $this->user();
        $paid = \App\Core\Database::value(
            'SELECT id FROM ' . \App\Core\Database::table('attachment_downloads')
            . ' WHERE attachment_id = ? AND user_id = ? AND paid > 0',
            [$id, $user['id']]
        );
        return $this->render('attachment/buy', [
            'title' => '购买附件',
            'att' => $att,
            'paid' => (bool)$paid,
            'balance' => Currency::balance((int)$user['id']),
        ]);
    }

    public function buy(int $id): void
    {
        $this->requireLogin();
        $att = Attachment::find($id);
        if (!$att) {
            $this->fail('附件不存在');
        }
        $fee = (float)$att['fee'];
        if ($fee <= 0) {
            $this->fail('该附件免费');
        }
        $uid = $this->userId();
        if (Currency::balance($uid) < $fee) {
            $this->fail('余额不足');
        }
        Currency::change($uid, -$fee, 'buy', '购买附件：' . $att['name'], $id);
        // 支付分成给作者
        Currency::change((int)$att['user_id'], $fee * 0.9, 'other', '附件售出：' . $att['name'], $id);
        Attachment::recordDownload($id, $uid, $fee);
        $this->success(['url' => url('attachment/' . $id . '/download')], '购买成功');
    }

    /**
     * 附件库浏览页（共享空间）
     */
    public function library(): string
    {
        $page = max(1, Request::int('page', 1));
        $result = Attachment::list(0, 'shared', $page, 24, (string)Request::get('kw', ''));
        return $this->render('attachment/library', [
            'title' => '附件库',
            'items' => $result['items'],
            'total' => $result['total'],
            'pager' => \App\Core\View::paginate($result['total'], 24, $page, url('attachments')),
        ]);
    }
}
