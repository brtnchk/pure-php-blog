<?php declare(strict_types=1);

use App\Core\Seeder;

return new class implements Seeder {
    public function run(PDO $db, array $context): array
    {
        $rows = [
            ['name' => 'Программирование', 'slug' => 'programming', 'description' => 'Заметки о языках и инструментах разработки.'],
            ['name' => 'Базы данных', 'slug' => 'databases', 'description' => 'SQL, NoSQL, индексы, оптимизация запросов.'],
            ['name' => 'DevOps', 'slug' => 'devops', 'description' => 'Контейнеризация, CI/CD, инфраструктура.'],
            ['name' => 'Веб-дизайн', 'slug' => 'web-design', 'description' => 'UI/UX, CSS, типографика и доступность.'],
            ['name' => 'Без статей', 'slug' => 'empty-cat', 'description' => 'Пустая категория — должна не появляться на главной.'],
        ];

        $db->exec('TRUNCATE TABLE categories');

        $stmt = $db->prepare(
            'INSERT INTO categories (name, slug, description)
             VALUES (:name, :slug, :description)'
        );

        $idsBySlug = [];

        foreach ($rows as $row) {
            $stmt->execute($row);
            $idsBySlug[$row['slug']] = (int) $db->lastInsertId();
        }

        echo '  + ' . count($idsBySlug) . " categories\n";

        return ['categoryIds' => $idsBySlug];
    }
};