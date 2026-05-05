<?php

declare(strict_types=1);

namespace App\Category;

interface CategoryRepositoryInterface
{
    public function findBySlug(string $slug): ?array;

    /** @return array<int, array{id:int,name:string,slug:string,description:?string}> */
    public function listWithArticles(): array;
}