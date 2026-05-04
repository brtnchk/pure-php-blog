<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Article;

final class ArticleController extends Controller
{
    public function show(string $slug): string
    {
        $model   = new Article();
        $article = $model->findBySlug($slug);
        if ($article === null) {
            return $this->notFound();
        }

        $model->incrementViews((int) $article['id']);
        $article['views'] = (int) $article['views'] + 1;

        $categories = $model->categoriesOf((int) $article['id']);
        $similar    = $model->similar((int) $article['id'], 3);

        return $this->render('article.tpl', [
            'article'    => $article,
            'categories' => $categories,
            'similar'    => $similar,
        ]);
    }
}