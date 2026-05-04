<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use InvalidArgumentException;

final class Article extends Model
{
    public const SORT_DATE  = 'date';
    public const SORT_VIEWS = 'views';

    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, title, slug, description, content, image, views, published_at
             FROM articles WHERE slug = :slug LIMIT 1'
        );
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * @return array<int, array<string,mixed>>
     */
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
        return $stmt->fetchAll();
    }

    /**
     * For each given category id return up to $limit recent articles.
     *
     * @param array<int,int> $categoryIds
     * @return array<int, array<int, array<string,mixed>>>  keyed by category_id
     */
    public function recentByCategories(array $categoryIds, int $limit = 3): array
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
        foreach ($stmt->fetchAll() as $row) {
            $grouped[(int) $row['category_id']][] = $row;
        }
        return $grouped;
    }

    /**
     * Articles in a category with sorting & pagination.
     *
     * @return array{items: array<int, array<string,mixed>>, total: int, pages: int, page: int, per_page: int}
     */
    public function listByCategory(int $categoryId, string $sort, int $page, int $perPage): array
    {
        $orderBy = match ($sort) {
            self::SORT_VIEWS => 'a.views DESC, a.published_at DESC',
            self::SORT_DATE  => 'a.published_at DESC, a.id DESC',
            default          => throw new InvalidArgumentException("Unknown sort: $sort"),
        };

        $countStmt = $this->db->prepare(
            'SELECT COUNT(*) FROM articles a
             INNER JOIN article_category ac ON ac.article_id = a.id
             WHERE ac.category_id = :cid'
        );
        $countStmt->execute(['cid' => $categoryId]);
        $total = (int) $countStmt->fetchColumn();

        $pages  = (int) max(1, ceil($total / $perPage));
        $page   = max(1, min($page, $pages));
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT a.id, a.title, a.slug, a.description, a.image, a.views, a.published_at
                FROM articles a
                INNER JOIN article_category ac ON ac.article_id = a.id
                WHERE ac.category_id = :cid
                ORDER BY $orderBy
                LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':cid', $categoryId, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return [
            'items'    => $stmt->fetchAll(),
            'total'    => $total,
            'pages'    => $pages,
            'page'     => $page,
            'per_page' => $perPage,
        ];
    }

    /**
     * Articles that share at least one category with the given article,
     * ranked by number of shared categories then recency.
     *
     * @return array<int, array<string,mixed>>
     */
    public function similar(int $articleId, int $limit = 3): array
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
        $stmt->bindValue(':id', $articleId, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function incrementViews(int $articleId): void
    {
        $stmt = $this->db->prepare('UPDATE articles SET views = views + 1 WHERE id = :id');
        $stmt->execute(['id' => $articleId]);
    }
}