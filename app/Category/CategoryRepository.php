<?php

declare(strict_types=1);

namespace App\Category;

use PDO;
use PDOStatement;
use RuntimeException;

final class CategoryRepository implements CategoryRepositoryInterface
{
    public function __construct(
        private PDO $db,
    ) {}

    /** @return array<string, mixed>|null */
    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, name, slug, description FROM categories WHERE slug = :slug LIMIT 1',
        );
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch();

        return is_array($row) ? $row : null;
    }

    /** @return list<array{id:int, name:string, slug:string, description:?string}> */
    public function listWithArticles(): array
    {
        $sql = 'SELECT c.id, c.name, c.slug, c.description
                FROM categories c
                INNER JOIN article_category ac ON ac.category_id = c.id
                GROUP BY c.id, c.name, c.slug, c.description
                ORDER BY c.name ASC';

        $stmt = $this->db->query($sql);
        if (!$stmt instanceof PDOStatement) {
            // Unreachable with PDO::ERRMODE_EXCEPTION, but PHPStan needs the assert.
            throw new RuntimeException('Failed to execute categories query.');
        }

        /** @var list<array{id:int, name:string, slug:string, description:?string}> */
        return $stmt->fetchAll();
    }
}
