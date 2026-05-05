<?php declare(strict_types=1);

namespace App\Category;

use App\Article\ArticleService;

class CategoryService
{
    private const ARTICLES_PER_PAGE = 6;

    public function __construct(
        private CategoryRepositoryInterface $categories,
        private ArticleService $articles,
    ) {
    }

    public function buildHomeSections(int $articlesPerCategory): array
    {
        $categories = $this->categories->listWithArticles();

        if ($categories === []) {
            return [];
        }

        $ids = array_map(static fn ($c) => (int) $c['id'], $categories);
        $recent = $this->articles->topInCategories($ids, $articlesPerCategory);

        $sections = [];

        foreach ($categories as $category) {
            $sections[] = [
                'category' => $category,
                'articles' => $recent[(int) $category['id']] ?? [],
            ];
        }

        return $sections;
    }

    public function getCategoryView(string $slug, ?string $sort, ?string $page): ?array
    {
        $category = $this->categories->findBySlug($slug);

        if ($category === null) {
            return null;
        }

        $sort = $this->articles->normalizeSort($sort);
        $pageNum = max(1, (int) ($page ?? 1));

        $listing = $this->articles->listForCategory(
            (int) $category['id'],
            $sort,
            $pageNum,
            self::ARTICLES_PER_PAGE,
        );

        return [
            'category' => $category,
            'articles' => $listing['items'],
            'pagination' => [
                'page' => $listing['page'],
                'pages' => $listing['pages'],
                'total' => $listing['total'],
                'per_page' => $listing['per_page'],
            ],
            'sort' => $sort,
            'sort_date' => ArticleService::SORT_DATE,
            'sort_views' => ArticleService::SORT_VIEWS,
        ];
    }
}