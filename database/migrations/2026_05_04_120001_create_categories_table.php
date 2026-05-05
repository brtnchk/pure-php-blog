<?php declare(strict_types=1);

use App\Core\Migration;

return new class implements Migration {
    public function up(PDO $db): void
    {
        $db->exec(
            'CREATE TABLE categories (
                id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
                name        VARCHAR(255) NOT NULL,
                slug        VARCHAR(255) NOT NULL,
                description TEXT NULL,
                created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uniq_categories_slug (slug)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    public function down(PDO $db): void
    {
        $db->exec('DROP TABLE IF EXISTS categories');
    }
};