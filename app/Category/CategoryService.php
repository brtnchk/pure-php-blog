<?php

declare(strict_types=1);

namespace App\Category;

use App\Article\ArticleRepository;

final class CategoryService
{
    public function __construct(
        private readonly CategoryRepository $categories,
        private readonly ArticleRepository $articles,
    ) {}

    public function findBySlug(string $slug): ?array
    {
        return $this->categories->findBySlug($slug);
    }

    /**
     * Builds the home page sections: each non-empty category with up to N latest articles.
     *
     * @return array<int, array{category: array<string,mixed>, articles: array<int, array<string,mixed>>}>
     */
    public function buildHomeSections(int $articlesPerCategory): array
    {
        $categories = $this->categories->listWithArticles();
        if ($categories === []) {
            return [];
        }

        $ids    = array_map(static fn ($c) => (int) $c['id'], $categories);
        $recent = $this->articles->recentByCategories($ids, $articlesPerCategory);

        $sections = [];
        foreach ($categories as $category) {
            $sections[] = [
                'category' => $category,
                'articles' => $recent[(int) $category['id']] ?? [],
            ];
        }
        return $sections;
    }
}