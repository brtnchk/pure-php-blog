<?php

declare(strict_types=1);

namespace App\Core;

use App\Article\ArticleRepository;
use App\Article\ArticleService;
use App\Category\CategoryRepository;
use App\Category\CategoryService;
use PDO;
use RuntimeException;

/**
 * Tiny service locator. Composition root for the app — here we wire
 * repositories into services. Anything that needs ArticleService etc. asks
 * the container instead of constructing collaborators by hand.
 */
final class Container
{
    private static ?self $instance = null;

    /** @var array<string,object> */
    private array $instances = [];

    private function __construct(private readonly PDO $pdo) {}

    public static function boot(PDO $pdo): self
    {
        self::$instance = new self($pdo);
        return self::$instance;
    }

    public static function instance(): self
    {
        if (self::$instance === null) {
            throw new RuntimeException('Container has not been booted.');
        }
        return self::$instance;
    }

    public function articleRepository(): ArticleRepository
    {
        return $this->instances[ArticleRepository::class]
            ??= new ArticleRepository($this->pdo);
    }

    public function categoryRepository(): CategoryRepository
    {
        return $this->instances[CategoryRepository::class]
            ??= new CategoryRepository($this->pdo);
    }

    public function articleService(): ArticleService
    {
        return $this->instances[ArticleService::class]
            ??= new ArticleService($this->articleRepository());
    }

    public function categoryService(): CategoryService
    {
        return $this->instances[CategoryService::class]
            ??= new CategoryService($this->categoryRepository(), $this->articleRepository());
    }
}