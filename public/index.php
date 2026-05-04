<?php declare(strict_types=1);

use App\Core\Container;
use App\Core\Database;
use App\Core\Router;
use App\Core\View;

$root = dirname(__DIR__);

require $root . '/vendor/autoload.php';

$config = require $root . '/app/Config/config.php';

if ($config['app']['debug']) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
    ini_set('display_errors', '0');
}

$pdo = Database::connection($config['db']);
Container::boot($pdo);
View::smarty($config);

$router = new Router();
$registerRoutes = require $root . '/routes/web.php';
$registerRoutes($router);

echo $router->dispatch($_SERVER['REQUEST_URI'] ?? '/');