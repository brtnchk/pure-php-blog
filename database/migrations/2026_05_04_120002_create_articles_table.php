<?php

declare(strict_types=1);

use App\Core\Migration;

return new class implements Migration {
    public function up(PDO $db): void
    {
        $db->exec(
            'CREATE TABLE articles (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                title VARCHAR(255) NOT NULL,
                slug VARCHAR(255) NOT NULL,
                description TEXT NOT NULL,
                content LONGTEXT NOT NULL,
                image VARCHAR(255) NULL,
                views INT UNSIGNED NOT NULL DEFAULT 0,
                published_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uniq_articles_slug (slug),
                KEY idx_articles_published_at (published_at),
                KEY idx_articles_views (views)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
        );
    }

    public function down(PDO $db): void
    {
        $db->exec('DROP TABLE IF EXISTS articles');
    }
};
