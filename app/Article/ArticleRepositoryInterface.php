<?php

declare(strict_types=1);

namespace App\Article;

interface ArticleRepositoryInterface
{
    /** @return array<string, mixed>|null */
    public function findBySlug(string $slug): ?array;

    /** @return list<array<string, mixed>> */
    public function categoriesOf(int $articleId): array;

    /**
     * @param list<int> $categoryIds
     * @return array<int, list<array<string, mixed>>>  keyed by category_id
     */
    public function recentByCategories(array $categoryIds, int $limit): array;

    public function countByCategory(int $categoryId): int;

    /** @return list<array<string, mixed>> */
    public function listByCategory(int $categoryId, string $sort, int $limit, int $offset): array;

    /** @return list<array<string, mixed>> */
    public function similar(int $articleId, int $limit): array;

    public function incrementViews(int $articleId): void;
}