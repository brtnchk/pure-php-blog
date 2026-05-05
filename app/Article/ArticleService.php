<?php declare(strict_types=1);

namespace App\Article;

class ArticleService
{
    public const SORT_DATE  = 'date';
    public const SORT_VIEWS = 'views';

    private const ALLOWED_SORTS = [self::SORT_DATE, self::SORT_VIEWS];

    public function __construct(
        private ArticleRepositoryInterface $articles,
    ) {
    }

    public function normalizeSort(?string $sort): string
    {
        return in_array($sort, self::ALLOWED_SORTS, true) ? $sort : self::SORT_DATE;
    }

    public function topInCategories(array $categoryIds, int $limit): array
    {
        return $this->articles->recentByCategories($categoryIds, $limit);
    }

    public function listForCategory(int $categoryId, string $sort, int $page, int $perPage): array
    {
        $sort = $this->normalizeSort($sort);

        $total = $this->articles->countByCategory($categoryId);
        $pages = (int) max(1, ceil($total / $perPage));
        $page = max(1, min($page, $pages));
        $offset = ($page - 1) * $perPage;

        $items = $total > 0
            ? $this->articles->listByCategory($categoryId, $sort, $perPage, $offset)
            : [];

        return [
            'items' => $items,
            'total' => $total,
            'pages' => $pages,
            'page' => $page,
            'per_page' => $perPage,
        ];
    }

    public function getArticleView(string $slug, int $similarLimit = 3): ?array
    {
        $article = $this->articles->findBySlug($slug);

        if ($article === null) {
            return null;
        }

        $id = (int) $article['id'];
        $this->articles->incrementViews($id);
        $article['views'] = (int) $article['views'] + 1;

        return [
            'article' => $article,
            'categories' => $this->articles->categoriesOf($id),
            'similar' => $this->articles->similar($id, $similarLimit),
        ];
    }
}