<?php declare(strict_types=1);

namespace App\Controllers;

use App\Article\ArticleService;
use App\Core\Controller;

final class ArticleController extends Controller
{
    public function __construct(
        private ArticleService $articles,
    ) {
    }

    public function show(string $slug): string
    {
        $view = $this->articles->getArticleView($slug, 3);

        if ($view === null) {
            return $this->notFound();
        }

        return $this->render('article.tpl', $view);
    }
}