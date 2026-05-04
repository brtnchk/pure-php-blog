<?php declare(strict_types=1);

namespace App\Category;

use App\Article\ArticleRepository;

final class CategoryService
{
    public function __construct(
        private CategoryRepository $categories,
        private ArticleRepository $articles,
    ) {
    }

    public function findBySlug(string $slug): ?array
    {
        return $this->categories->findBySlug($slug);
    }

    public function buildHomeSections(int $articlesPerCategory): array
    {
        $categories = $this->categories->listWithArticles();

        if ($categories === []) {
            return [];
        }

        $ids = array_map(static fn ($c) => (int) $c['id'], $categories);
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