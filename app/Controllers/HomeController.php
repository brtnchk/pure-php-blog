<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Container;
use App\Core\Controller;

final class HomeController extends Controller
{
    public function index(): string
    {
        $sections = Container::instance()
            ->categoryService()
            ->buildHomeSections(3);

        return $this->render('home.tpl', [
            'sections' => $sections,
        ]);
    }
}