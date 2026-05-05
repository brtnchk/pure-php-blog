<?php

declare(strict_types=1);

namespace App\Category;

use App\Article\ArticleService;

class CategoryService
{
    private const ARTICLES_PER_PAGE = 6;

    public function __construct(
        private CategoryRepositoryInterface $categories,
        private ArticleService $articles,
    ) {}

    /**
     * @return list<array{
     *   category: array{id:int, name:string, slug:string, description:?string},
     *   articles: list<array<string, mixed>>
     * }>
     */
    public function buildHomeSections(int $articlesPerCategory): array
    {
        $categories = $this->categories->listWithArticles();

        if ($categories === []) {
            return [];
        }

        $ids = array_map(static fn($c) => (int) $c['id'], $categories);
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

    /**
     * @return array{
     *   category: array<string, mixed>,
     *   articles: list<array<string, mixed>>,
     *   pagination: array{page:int, pages:int, total:int, per_page:int},
     *   sort: string,
     *   sort_date: string,
     *   sort_views: string
     * }|null
     */
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
