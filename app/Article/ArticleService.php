<?php declare(strict_types=1);

namespace App\Article;

final class ArticleService
{
    public const SORT_DATE  = 'date';
    public const SORT_VIEWS = 'views';

    private const ORDER_BY = [
        self::SORT_DATE  => 'a.published_at DESC, a.id DESC',
        self::SORT_VIEWS => 'a.views DESC, a.published_at DESC',
    ];

    public function __construct(
        private ArticleRepository $articles,
    ) {
    }

    public function normalizeSort(?string $sort): string
    {
        return isset(self::ORDER_BY[$sort]) ? $sort : self::SORT_DATE;
    }

    public function listForCategory(int $categoryId, string $sort, int $page, int $perPage): array
    {
        $sort = $this->normalizeSort($sort);
        $orderBy = self::ORDER_BY[$sort];

        $total = $this->articles->countByCategory($categoryId);
        $pages = (int) max(1, ceil($total / $perPage));
        $page = max(1, min($page, $pages));
        $offset = ($page - 1) * $perPage;

        $items = $total > 0
            ? $this->articles->listByCategory($categoryId, $orderBy, $perPage, $offset)
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