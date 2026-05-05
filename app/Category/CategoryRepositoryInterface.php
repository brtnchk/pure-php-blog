<?php

declare(strict_types=1);

namespace App\Category;

interface CategoryRepositoryInterface
{
    /** @return array<string, mixed>|null */
    public function findBySlug(string $slug): ?array;

    /** @return list<array{id:int, name:string, slug:string, description:?string}> */
    public function listWithArticles(): array;
}