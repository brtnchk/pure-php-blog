<?php declare(strict_types=1);

use App\Core\Migration;

return new class implements Migration {
    public function up(PDO $db): void
    {
        $db->exec(
            'CREATE TABLE article_category (
                article_id  INT UNSIGNED NOT NULL,
                category_id INT UNSIGNED NOT NULL,
                PRIMARY KEY (article_id, category_id),
                KEY idx_ac_category (category_id),
                CONSTRAINT fk_ac_article  FOREIGN KEY (article_id)  REFERENCES articles   (id) ON DELETE CASCADE,
                CONSTRAINT fk_ac_category FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    public function down(PDO $db): void
    {
        $db->exec('DROP TABLE IF EXISTS article_category');
    }
};