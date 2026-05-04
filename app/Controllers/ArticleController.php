<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Container;
use App\Core\Controller;

final class ArticleController extends Controller
{
    public function show(string $slug): string
    {
        $view = Container::instance()
            ->articleService()
            ->getArticleView($slug, 3);

        if ($view === null) {
            return $this->notFound();
        }

        return $this->render('article.tpl', $view);
    }
}