<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Settings;
use App\Services\FeedReader;

class ReaderController extends BaseController
{
    public function index(): string
    {
        if ((int)Settings::get('reader_enable', 1) !== 1) {
            http_response_code(404);
            echo 'Reader is disabled.';
            exit;
        }
        $limit = max(1, min(50, (int)Settings::get('reader_limit', 20)));
        $sources = [];
        foreach (FeedReader::sources() as $source) {
            $sources[] = [
                'name' => $source['name'],
                'url' => $source['url'],
                'items' => FeedReader::fetch($source['url'], $limit),
            ];
        }
        return $this->render('reader/index', [
            'title' => (string)Settings::get('reader_title', 'RSS 阅读'),
            'reader_title' => (string)Settings::get('reader_title', 'RSS 阅读'),
            'sources' => $sources,
        ]);
    }
}
