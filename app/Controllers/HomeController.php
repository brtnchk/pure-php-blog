<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Category\CategoryService;
use App\Core\Controller;

final class HomeController extends Controller
{
    public function __construct(
        private CategoryService $categories,
    ) {}

    public function index(): string
    {
        return $this->render('home.tpl', [
            'sections' => $this->categories->buildHomeSections(3),
        ]);
    }
}
