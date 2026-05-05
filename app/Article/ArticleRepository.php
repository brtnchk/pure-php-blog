<?php declare(strict_types=1);

namespace App\Article;

use InvalidArgumentException;
use PDO;

final class ArticleRepository implements ArticleRepositoryInterface
{
    /**
     * Whitelist of allowed sort keys → ORDER BY fragments.
     * Lives here so the repo never trusts caller-supplied SQL strings.
     */
    private const ORDER_BY = [
        'date'  => 'a.published_at DESC, a.id DESC',
        'views' => 'a.views DESC, a.published_at DESC',
    ];

    public function __construct(
        private PDO $db,
    ) {
    }

    /** @return array<string, mixed>|null */
    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, title, slug, description, content, image, views, published_at
             FROM articles WHERE slug = :slug LIMIT 1'
        );
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch();

        return is_array($row) ? $row : null;
    }

    /** @return list<array<string, mixed>> */
    public function categoriesOf(int $articleId): array
    {
        $stmt = $this->db->prepare(
            'SELECT c.id, c.name, c.slug
             FROM categories c
             INNER JOIN article_category ac ON ac.category_id = c.id
             WHERE ac.article_id = :id
             ORDER BY c.name ASC'
        );
        $stmt->execute(['id' => $articleId]);

        /** @var list<array<string, mixed>> */
        return $stmt->fetchAll();
    }

    /**
     * @param list<int> $categoryIds
     * @return array<int, list<array<string, mixed>>>
     */
    public function recentByCategories(array $categoryIds, int $limit): array
    {
        if ($categoryIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));

        $sql = "SELECT * FROM (
                    SELECT a.id, a.title, a.slug, a.description, a.image, a.views, a.published_at,
                           ac.category_id,
                           ROW_NUMBER() OVER (PARTITION BY ac.category_id ORDER BY a.published_at DESC, a.id DESC) AS rn
                    FROM articles a
                    INNER JOIN article_category ac ON ac.article_id = a.id
                    WHERE ac.category_id IN ($placeholders)
                ) ranked
                WHERE rn <= ?
                ORDER BY category_id, published_at DESC";

        $params = array_merge(array_values($categoryIds), [$limit]);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $grouped = [];

        /** @var list<array<string, mixed>> $rows */
        $rows = $stmt->fetchAll();
        foreach ($rows as $row) {
            $grouped[(int) $row['category_id']][] = $row;
        }

        return $grouped;
    }

    public function countByCategory(int $categoryId): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM articles a
             INNER JOIN article_category ac ON ac.article_id = a.id
             WHERE ac.category_id = :cid'
        );

        $stmt->execute(['cid' => $categoryId]);

        return (int) $stmt->fetchColumn();
    }

    /** @return list<array<string, mixed>> */
    public function listByCategory(int $categoryId, string $sort, int $limit, int $offset): array
    {
        if (!isset(self::ORDER_BY[$sort])) {
            throw new InvalidArgumentException("Unknown sort key: {$sort}");
        }
        $orderBy = self::ORDER_BY[$sort];

        $sql = "SELECT a.id, a.title, a.slug, a.description, a.image, a.views, a.published_at
                FROM articles a
                INNER JOIN article_category ac ON ac.article_id = a.id
                WHERE ac.category_id = :cid
                ORDER BY $orderBy
                LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':cid', $categoryId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        /** @var list<array<string, mixed>> */
        return $stmt->fetchAll();
    }

    /** @return list<array<string, mixed>> */
    public function similar(int $articleId, int $limit): array
    {
        $sql = 'SELECT a.id, a.title, a.slug, a.description, a.image, a.views, a.published_at,
                       COUNT(DISTINCT ac2.category_id) AS shared_count
                FROM article_category ac1
                INNER JOIN article_category ac2 ON ac2.category_id = ac1.category_id
                                                AND ac2.article_id <> ac1.article_id
                INNER JOIN articles a ON a.id = ac2.article_id
                WHERE ac1.article_id = :id
                GROUP BY a.id, a.title, a.slug, a.description, a.image, a.views, a.published_at
                ORDER BY shared_count DESC, a.published_at DESC
                LIMIT :limit';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $articleId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        /** @var list<array<string, mixed>> */
        return $stmt->fetchAll();
    }

    public function incrementViews(int $articleId): void
    {
        $stmt = $this->db->prepare('UPDATE articles SET views = views + 1 WHERE id = :id');
        $stmt->execute(['id' => $articleId]);
    }
}