<?php

declare(strict_types=1);

use App\Core\Seeder;
use App\Core\Slugifier;

return new class implements Seeder {
    private const HOURS_BETWEEN_POSTS = 2;

    public function run(PDO $db, array $context): array
    {
        $categoryIds = $context['categoryIds'] ?? null;

        if (!is_array($categoryIds) || $categoryIds === []) {
            throw new RuntimeException(
                "Article seeder requires \$context['categoryIds'] from a previous seeder.",
            );
        }

        $base = [
            ['title' => 'Введение в PHP 8.1: enum, readonly и never',  'cats' => ['programming']],
            ['title' => 'PSR-4 автозагрузка без фреймворка',            'cats' => ['programming']],
            ['title' => 'PDO: работа с MySQL по-человечески',           'cats' => ['programming', 'databases']],
            ['title' => 'Шаблонизатор Smarty: первый блог за час',      'cats' => ['programming', 'web-design']],
            ['title' => 'Индексы B-Tree простыми словами',              'cats' => ['databases']],
            ['title' => 'EXPLAIN, который понимает каждый',             'cats' => ['databases']],
            ['title' => 'JSON в MySQL: когда стоит, а когда нет',       'cats' => ['databases']],
            ['title' => 'Транзакции и уровни изоляции',                 'cats' => ['databases']],
            ['title' => 'Docker для разработчика на PHP',               'cats' => ['devops', 'programming']],
            ['title' => 'Nginx + PHP-FPM: конфигурация без боли',       'cats' => ['devops']],
            ['title' => 'CI/CD за полчаса в GitLab',                    'cats' => ['devops']],
            ['title' => 'Локальная разработка с docker-compose',        'cats' => ['devops']],
            ['title' => 'Современная вёрстка с Grid и Flex',            'cats' => ['web-design']],
            ['title' => 'SCSS: не просто переменные',                   'cats' => ['web-design']],
            ['title' => 'Доступность с нуля: aria и контраст',          'cats' => ['web-design']],
            ['title' => 'Типографика для веба',                         'cats' => ['web-design']],
        ];

        // Originals + 3 variant batches → ~64 articles, ~16 per category, 3 pages of 6.
        $rows = $base;
        for ($variant = 2; $variant <= 4; $variant++) {
            foreach ($base as $row) {
                $row['title'] .= ' — часть ' . $variant;
                $rows[] = $row;
            }
        }

        $db->exec('TRUNCATE TABLE article_category');
        $db->exec('TRUNCATE TABLE articles');

        $articleStmt = $db->prepare(
            'INSERT INTO articles (title, slug, description, content, image, views, published_at)
             VALUES (:title, :slug, :description, :content, :image, :views, :published_at)',
        );
        $linkStmt = $db->prepare(
            'INSERT INTO article_category (article_id, category_id)
             VALUES (:article_id, :category_id)',
        );

        foreach ($rows as $i => $row) {
            $slug = Slugifier::slugify($row['title']) . '-' . ($i + 1);

            $articleStmt->execute([
                'title' => $row['title'],
                'slug' => $slug,
                'description'  => 'Краткое описание: ' . $row['title'] . '.',
                'content' => $this->makeBody(),
                // Lorem Picsum returns the same photo for the same seed, so the
                // image is stable per article without committing any binaries.
                'image' => "https://picsum.photos/seed/{$slug}/1200/600",
                'views' => random_int(0, 500),
                'published_at' => date('Y-m-d H:i:s', time() - $i * self::HOURS_BETWEEN_POSTS * 3600),
            ]);

            $articleId = (int) $db->lastInsertId();

            foreach ($row['cats'] as $catSlug) {
                if (!isset($categoryIds[$catSlug])) {
                    throw new RuntimeException("Unknown category slug: {$catSlug}");
                }

                $linkStmt->execute([
                    'article_id' => $articleId,
                    'category_id' => $categoryIds[$catSlug],
                ]);
            }
        }

        echo '  + ' . count($rows) . " articles (with category links)\n";
        return [];
    }

    private function makeBody(): string
    {
        $lorem = 'Это демонстрационная статья. Она наполнена связным текстом, чтобы можно было '
               . 'проверить вёрстку, пагинацию и блоки похожих материалов. В реальной жизни здесь '
               . 'был бы содержательный материал, написанный автором.';

        return "<p>{$lorem}</p><p>{$lorem}</p><p>{$lorem}</p>";
    }
};
