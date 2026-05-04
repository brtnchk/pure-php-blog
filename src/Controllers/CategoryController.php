<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Article;
use App\Models\Category;

final class CategoryController extends Controller
{
    public function show(string $slug): string
    {
        $category = (new Category())->findBySlug($slug);
        if ($category === null) {
            return $this->notFound();
        }

        $config = require dirname(__DIR__) . '/Config/config.php';
        $perPage = (int) $config['pagination']['per_page'];

        $sort = $_GET['sort'] ?? Article::SORT_DATE;
        if (!in_array($sort, [Article::SORT_DATE, Article::SORT_VIEWS], true)) {
            $sort = Article::SORT_DATE;
        }

        $page = $this->intParam($_GET['page'] ?? null, 1, 1);

        $result = (new Article())->listByCategory((int) $category['id'], $sort, $page, $perPage);

        return $this->render('category.tpl', [
            'category'   => $category,
            'articles'   => $result['items'],
            'pagination' => [
                'page'     => $result['page'],
                'pages'    => $result['pages'],
                'total'    => $result['total'],
                'per_page' => $result['per_page'],
            ],
            'sort'       => $sort,
            'query'      => $_GET,
        ]);
    }
}