<?php

declare(strict_types=1);

use App\Core\Database;
use App\Core\Migrator;

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from the command line.\n");
    exit(1);
}

$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';

$config = require $root . '/app/Config/config.php';
$pdo = Database::connection($config['db']);

$migrator = new Migrator($pdo, __DIR__ . '/migrations');
$action = $argv[1] ?? 'migrate';

switch ($action) {
    case 'migrate':
        echo "Migrating...\n";
        $n = $migrator->migrate();
        echo "Done. ($n migration(s) applied)\n";
        break;

    case 'rollback':
        echo "Rolling back the last batch...\n";
        $n = $migrator->rollback();
        echo "Done. ($n migration(s) reverted)\n";
        break;

    case 'fresh':
        echo "Rolling back everything and re-applying...\n";
        $n = $migrator->fresh();
        echo "Done. ($n migration(s) applied from scratch)\n";
        break;

    case 'status':
        foreach ($migrator->status() as $name => $state) {
            echo sprintf("  [%-7s] %s\n", $state, $name);
        }
        break;

    default:
        fwrite(STDERR, "Unknown action: $action\n");
        fwrite(STDERR, "Usage: php database/migrate.php [migrate|rollback|status|fresh]\n");
        exit(1);
}
