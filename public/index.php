<?php

declare(strict_types=1);

use App\Article\ArticleRepository;
use App\Article\ArticleRepositoryInterface;
use App\Category\CategoryRepository;
use App\Category\CategoryRepositoryInterface;
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

View::smarty($config);

$container = new Container();
$container->bind(PDO::class, static fn() => Database::connection($config['db']));
$container->bind(ArticleRepositoryInterface::class, static fn(Container $c) => $c->get(ArticleRepository::class));
$container->bind(CategoryRepositoryInterface::class, static fn(Container $c) => $c->get(CategoryRepository::class));

$router = new Router($container);
$registerRoutes = require $root . '/routes/web.php';
$registerRoutes($router);

echo $router->dispatch($_SERVER['REQUEST_URI'] ?? '/');
