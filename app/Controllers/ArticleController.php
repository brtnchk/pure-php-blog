<?php declare(strict_types=1);

namespace App\Controllers;

use App\Article\ArticleService;
use App\Core\Controller;
use Smarty\Exception;

final class ArticleController extends Controller
{
    public function __construct(
        private ArticleService $articles,
    ) {
    }

    /** @throws Exception */
    public function show(string $slug): string
    {
        $view = $this->articles->getArticleView($slug);

        if ($view === null) {
            return $this->notFound();
        }

        return $this->render('article.tpl', $view);
    }
}