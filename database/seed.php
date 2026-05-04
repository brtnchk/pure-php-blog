<?php declare(strict_types=1);

use App\Core\Database;

$root = dirname(__DIR__);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from the command line.\n");
    exit(1);
}

require $root . '/vendor/autoload.php';

$config = require $root . '/app/Config/config.php';
$pdo    = Database::connection($config['db']);

echo "Seeding database '{$config['db']['name']}' on {$config['db']['host']}...\n";

$pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
$pdo->exec('TRUNCATE TABLE article_category');
$pdo->exec('TRUNCATE TABLE articles');
$pdo->exec('TRUNCATE TABLE categories');
$pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

$categories = [
    ['name' => 'Программирование', 'slug' => 'programming',   'description' => 'Заметки о языках и инструментах разработки.'],
    ['name' => 'Базы данных',       'slug' => 'databases',     'description' => 'SQL, NoSQL, индексы, оптимизация запросов.'],
    ['name' => 'DevOps',            'slug' => 'devops',        'description' => 'Контейнеризация, CI/CD, инфраструктура.'],
    ['name' => 'Веб-дизайн',        'slug' => 'web-design',    'description' => 'UI/UX, CSS, типографика и доступность.'],
    ['name' => 'Без статей',        'slug' => 'empty-cat',     'description' => 'Пустая категория — должна не появляться на главной.'],
];

$catStmt = $pdo->prepare(
    'INSERT INTO categories (name, slug, description) VALUES (:name, :slug, :description)'
);

$catIds = [];
foreach ($categories as $c) {
    $catStmt->execute($c);
    $catIds[$c['slug']] = (int) $pdo->lastInsertId();
}
echo "  + " . count($categories) . " categories\n";

$articleSamples = [
    ['title' => 'Введение в PHP 8.1: enum, readonly и never',          'cats' => ['programming']],
    ['title' => 'PSR-4 автозагрузка без фреймворка',                   'cats' => ['programming']],
    ['title' => 'PDO: работа с MySQL по-человечески',                  'cats' => ['programming', 'databases']],
    ['title' => 'Шаблонизатор Smarty: первый блог за час',             'cats' => ['programming', 'web-design']],
    ['title' => 'Индексы B-Tree простыми словами',                     'cats' => ['databases']],
    ['title' => 'EXPLAIN, который понимает каждый',                    'cats' => ['databases']],
    ['title' => 'JSON в MySQL: когда стоит, а когда нет',              'cats' => ['databases']],
    ['title' => 'Транзакции и уровни изоляции',                        'cats' => ['databases']],
    ['title' => 'Docker для разработчика на PHP',                      'cats' => ['devops', 'programming']],
    ['title' => 'Nginx + PHP-FPM: конфигурация без боли',              'cats' => ['devops']],
    ['title' => 'CI/CD за полчаса в GitLab',                           'cats' => ['devops']],
    ['title' => 'Локальная разработка с docker-compose',               'cats' => ['devops']],
    ['title' => 'Современная вёрстка с Grid и Flex',                   'cats' => ['web-design']],
    ['title' => 'SCSS: не просто переменные',                          'cats' => ['web-design']],
    ['title' => 'Доступность с нуля: aria и контраст',                 'cats' => ['web-design']],
    ['title' => 'Типографика для веба',                                'cats' => ['web-design']],
];

$lorem = "Это демонстрационная статья. Она наполнена связным текстом, чтобы можно было проверить вёрстку, "
       . "пагинацию и блоки похожих материалов. В реальной жизни здесь был бы содержательный материал, "
       . "написанный автором.";

$body = "<p>$lorem</p><p>$lorem</p><p>$lorem</p>";

$artStmt = $pdo->prepare(
    'INSERT INTO articles (title, slug, description, content, image, views, published_at)
     VALUES (:title, :slug, :description, :content, :image, :views, :published_at)'
);
$pivotStmt = $pdo->prepare(
    'INSERT INTO article_category (article_id, category_id) VALUES (:article_id, :category_id)'
);

$slugify = static function (string $title): string {
    $map = [
        'а'=>'a','б'=>'b','в'=>'v','г'=>'g','д'=>'d','е'=>'e','ё'=>'e','ж'=>'zh',
        'з'=>'z','и'=>'i','й'=>'y','к'=>'k','л'=>'l','м'=>'m','н'=>'n','о'=>'o',
        'п'=>'p','р'=>'r','с'=>'s','т'=>'t','у'=>'u','ф'=>'f','х'=>'h','ц'=>'c',
        'ч'=>'ch','ш'=>'sh','щ'=>'sch','ъ'=>'','ы'=>'y','ь'=>'','э'=>'e','ю'=>'yu','я'=>'ya',
    ];
    $title = mb_strtolower($title);
    $title = strtr($title, $map);
    $title = preg_replace('/[^a-z0-9]+/u', '-', $title);
    return trim($title, '-');
};

$now = time();
$inserted = 0;
foreach ($articleSamples as $i => $sample) {
    $slug = $slugify($sample['title']) . '-' . ($i + 1);
    $publishedAt = date('Y-m-d H:i:s', $now - $i * 7200);
    $artStmt->execute([
        'title'        => $sample['title'],
        'slug'         => $slug,
        'description'  => 'Краткое описание: ' . $sample['title'] . '.',
        'content'      => $body,
        'image'        => null,
        'views'        => random_int(0, 500),
        'published_at' => $publishedAt,
    ]);
    $articleId = (int) $pdo->lastInsertId();

    foreach ($sample['cats'] as $catSlug) {
        $pivotStmt->execute([
            'article_id'  => $articleId,
            'category_id' => $catIds[$catSlug],
        ]);
    }
    $inserted++;
}
echo "  + $inserted articles (with category links)\n";
echo "Done.\n";