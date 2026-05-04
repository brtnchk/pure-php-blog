<?php declare(strict_types=1);

use App\Core\Database;
use App\Database\DatabaseSeeder;

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from the command line.\n");
    exit(1);
}

$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';

$config = require $root . '/app/Config/config.php';
$pdo    = Database::connection($config['db']);

echo "Seeding database '{$config['db']['name']}' on {$config['db']['host']}...\n";

(new DatabaseSeeder(
    $pdo,
    require __DIR__ . '/seeds/categories.php',
    require __DIR__ . '/seeds/articles.php',
))->run();

echo "Done.\n";