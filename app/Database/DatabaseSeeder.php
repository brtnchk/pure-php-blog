<?php

declare(strict_types=1);

namespace App\Database;

use PDO;

/**
 * Orchestrates the full seeding pipeline:
 *   1) truncates dependent tables in the correct order;
 *   2) inserts categories and gets back a slug → id map;
 *   3) inserts articles + many-to-many links by category slug.
 */
final class DatabaseSeeder
{
    private const TABLES_TO_CLEAR = ['article_category', 'articles', 'categories'];

    /**
     * @param array<int, array{name:string, slug:string, description:?string}> $categories
     * @param array<int, array{title:string, cats:array<int,string>}> $articles
     */
    public function __construct(
        private readonly PDO $db,
        private readonly array $categories,
        private readonly array $articles,
    ) {}

    public function run(): void
    {
        $this->truncate();

        $categoryIds = (new CategorySeeder($this->db))->seed($this->categories);
        echo '  + ' . count($categoryIds) . " categories\n";

        $inserted = (new ArticleSeeder($this->db))->seed($this->articles, $categoryIds);
        echo "  + {$inserted} articles (with category links)\n";
    }

    private function truncate(): void
    {
        $this->db->exec('SET FOREIGN_KEY_CHECKS = 0');
        foreach (self::TABLES_TO_CLEAR as $table) {
            $this->db->exec("TRUNCATE TABLE {$table}");
        }
        $this->db->exec('SET FOREIGN_KEY_CHECKS = 1');
    }
}