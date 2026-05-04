<?php

declare(strict_types=1);

namespace App\Database;

use PDO;

final class CategorySeeder
{
    public function __construct(private readonly PDO $db) {}

    /**
     * @param array<int, array{name:string, slug:string, description:?string}> $rows
     * @return array<string, int>  slug → id
     */
    public function seed(array $rows): array
    {
        $stmt = $this->db->prepare(
            'INSERT INTO categories (name, slug, description)
             VALUES (:name, :slug, :description)'
        );

        $idsBySlug = [];
        foreach ($rows as $row) {
            $stmt->execute($row);
            $idsBySlug[$row['slug']] = (int) $this->db->lastInsertId();
        }
        return $idsBySlug;
    }
}