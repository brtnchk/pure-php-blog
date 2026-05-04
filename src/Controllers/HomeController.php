<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Article;
use App\Models\Category;

final class HomeController extends Controller
{
    public function index(): string
    {
        $categories = (new Category())->listWithArticles();

        $ids = array_map(static fn ($c) => (int) $c['id'], $categories);
        $recent = (new Article())->recentByCategories($ids, 3);

        $sections = [];
        foreach ($categories as $c) {
            $sections[] = [
                'category' => $c,
                'articles' => $recent[(int) $c['id']] ?? [],
            ];
        }

        return $this->render('home.tpl', [
            'sections' => $sections,
        ]);
    }
}