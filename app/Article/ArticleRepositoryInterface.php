<?php

declare(strict_types=1);

namespace App\Article;

interface ArticleRepositoryInterface
{
    public function findBySlug(string $slug): ?array;

    /** @return array<int, array<string,mixed>> */
    public function categoriesOf(int $articleId): array;

    /**
     * @param array<int,int> $categoryIds
     * @return array<int, array<int, array<string,mixed>>>  keyed by category_id
     */
    public function recentByCategories(array $categoryIds, int $limit): array;

    public function countByCategory(int $categoryId): int;

    /** @return array<int, array<string,mixed>> */
    public function listByCategory(int $categoryId, string $sort, int $limit, int $offset): array;

    /** @return array<int, array<string,mixed>> */
    public function similar(int $articleId, int $limit): array;

    public function incrementViews(int $articleId): void;
}