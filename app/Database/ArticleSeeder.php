<?php

declare(strict_types=1);

namespace App\Database;

use PDO;
use RuntimeException;

final class ArticleSeeder
{
    private const HOURS_BETWEEN_POSTS = 2;

    public function __construct(private readonly PDO $db) {}

    /**
     * @param array<int, array{title:string, cats:array<int,string>}> $rows
     * @param array<string,int> $categoryIdsBySlug
     * @return int  number of articles inserted
     */
    public function seed(array $rows, array $categoryIdsBySlug): int
    {
        $articleStmt = $this->db->prepare(
            'INSERT INTO articles (title, slug, description, content, image, views, published_at)
             VALUES (:title, :slug, :description, :content, :image, :views, :published_at)'
        );
        $linkStmt = $this->db->prepare(
            'INSERT INTO article_category (article_id, category_id)
             VALUES (:article_id, :category_id)'
        );

        $now      = time();
        $inserted = 0;

        foreach ($rows as $i => $row) {
            $articleStmt->execute([
                'title'        => $row['title'],
                'slug'         => Slugifier::slugify($row['title']) . '-' . ($i + 1),
                'description'  => 'Краткое описание: ' . $row['title'] . '.',
                'content'      => $this->makeContent(),
                'image'        => null,
                'views'        => random_int(0, 500),
                'published_at' => date('Y-m-d H:i:s', $now - $i * self::HOURS_BETWEEN_POSTS * 3600),
            ]);
            $articleId = (int) $this->db->lastInsertId();

            foreach ($row['cats'] as $catSlug) {
                if (!isset($categoryIdsBySlug[$catSlug])) {
                    throw new RuntimeException("Unknown category slug: {$catSlug}");
                }
                $linkStmt->execute([
                    'article_id'  => $articleId,
                    'category_id' => $categoryIdsBySlug[$catSlug],
                ]);
            }
            $inserted++;
        }

        return $inserted;
    }

    private function makeContent(): string
    {
        $lorem = 'Это демонстрационная статья. Она наполнена связным текстом, чтобы можно было '
               . 'проверить вёрстку, пагинацию и блоки похожих материалов. В реальной жизни здесь '
               . 'был бы содержательный материал, написанный автором.';
        return "<p>{$lorem}</p><p>{$lorem}</p><p>{$lorem}</p>";
    }
}