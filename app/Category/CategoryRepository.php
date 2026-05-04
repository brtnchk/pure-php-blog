<?php

declare(strict_types=1);

namespace App\Category;

use PDO;

final class CategoryRepository
{
    public function __construct(private readonly PDO $db) {}

    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, name, slug, description FROM categories WHERE slug = :slug LIMIT 1'
        );
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * @return array<int, array{id:int,name:string,slug:string,description:?string}>
     */
    public function listWithArticles(): array
    {
        $sql = 'SELECT c.id, c.name, c.slug, c.description
                FROM categories c
                INNER JOIN article_category ac ON ac.category_id = c.id
                GROUP BY c.id, c.name, c.slug, c.description
                ORDER BY c.name ASC';
        return $this->db->query($sql)->fetchAll();
    }
}