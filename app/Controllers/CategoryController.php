<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Article\ArticleService;
use App\Category\CategoryService;
use App\Core\Controller;

final class CategoryController extends Controller
{
    public function __construct(
        private readonly CategoryService $categories,
        private readonly ArticleService $articles,
    ) {}

    public function show(string $slug): string
    {
        $category = $this->categories->findBySlug($slug);
        if ($category === null) {
            return $this->notFound();
        }

        $sort = $this->articles->normalizeSort($_GET['sort'] ?? null);
        $page = $this->intParam($_GET['page'] ?? null, 1, 1);

        $config  = require dirname(__DIR__) . '/Config/config.php';
        $perPage = (int) $config['pagination']['per_page'];

        $listing = $this->articles->listForCategory((int) $category['id'], $sort, $page, $perPage);

        return $this->render('category.tpl', [
            'category'   => $category,
            'articles'   => $listing['items'],
            'pagination' => [
                'page'     => $listing['page'],
                'pages'    => $listing['pages'],
                'total'    => $listing['total'],
                'per_page' => $listing['per_page'],
            ],
            'sort'       => $sort,
            'sort_date'  => ArticleService::SORT_DATE,
            'sort_views' => ArticleService::SORT_VIEWS,
        ]);
    }
}