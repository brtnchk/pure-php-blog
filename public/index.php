<?php declare(strict_types=1);

use App\Core\Database;
use App\Core\Router;
use App\Core\View;

$root = dirname(__DIR__);

if (is_file($root . '/vendor/autoload.php')) {
    require $root . '/vendor/autoload.php';
}
require $root . '/src/autoload.php';

$config = require $root . '/src/Config/config.php';

if ($config['app']['debug']) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
    ini_set('display_errors', '0');
}

Database::connection($config['db']);
View::smarty($config);

$router = new Router();
$registerRoutes = require $root . '/routes/web.php';
$registerRoutes($router);

echo $router->dispatch($_SERVER['REQUEST_URI'] ?? '/');