<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Settings;
use App\Models\NavLink;

class LinksController extends BaseController
{
    public function index(): string
    {
        $links = NavLink::list(true);
        $title = trim((string)Settings::get('nav_title', '网站导航')) ?: '网站导航';
        return $this->render('links/index', [
            'title' => $title,
            'nav_title' => $title,
            'nav_description' => (string)Settings::get('nav_description', ''),
            'links' => $links,
        ]);
    }
}
